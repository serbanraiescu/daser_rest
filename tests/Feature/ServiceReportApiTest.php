<?php

namespace Tests\Feature;

use App\Modules\Service\Services\ServiceBillingService;
use App\Modules\Settings\Models\CompanySetting;
use Tests\ServiceTestCase;

class ServiceReportApiTest extends ServiceTestCase
{
    private const TOKEN = 'test-only-service-report-token-1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        config(['service_reporting.api_token' => self::TOKEN, 'service_reporting.timezone' => 'Europe/Bucharest']);
    }

    public function test_report_access_requires_a_configured_bearer_token_and_enabled_module(): void
    {
        $url = '/api/v1/service/reports/daily?date=2026-10-30';
        $this->get($url)->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
        $this->withToken('incorrect')->getJson($url)->assertUnauthorized();
        $this->getJson($url.'&token='.self::TOKEN)->assertUnauthorized();

        config(['service_reporting.api_token' => '']);
        $this->withToken(self::TOKEN)->getJson($url)->assertStatus(503);

        config(['service_reporting.api_token' => self::TOKEN]);
        CompanySetting::first()->update(['enable_service_module' => false]);
        $this->withToken(self::TOKEN)->getJson($url)->assertNotFound();
    }

    public function test_report_validates_periods_and_pagination(): void
    {
        foreach ([
            '/daily?date=2026-02-30', '/daily?date=30.10.2026', '/daily?date=tomorrow',
            '/monthly?month=2026-13', '/monthly?month=2026-10-01',
            '/daily?page=0', '/daily?per_page=101',
        ] as $suffix) {
            $this->withToken(self::TOKEN)->getJson('/api/v1/service/reports'.$suffix)->assertUnprocessable();
        }
    }

    public function test_daily_report_separates_collection_protocol_and_account_values(): void
    {
        foreach (['cash' => 100, 'card' => 50, 'mixed' => 25, 'protocol' => 10] as $method => $amount) {
            $this->completedOrder($method, (string) $amount, '2026-10-30 10:00:00');
        }
        $this->accountOrder(amount: '200.00');
        $response = $this->withToken(self::TOKEN)->getJson('/api/v1/service/reports/daily?date=2026-10-30')->assertOk();

        $this->assertEquals(5, $response->json('summary.successful_orders'));
        $this->assertEquals(175, $response->json('summary.total_revenue'));
        $this->assertEquals(385, $response->json('summary.completed_value'));
        $this->assertEquals(100, $response->json('summary.cash_revenue'));
        $this->assertEquals(50, $response->json('summary.card_revenue'));
        $this->assertEquals(25, $response->json('summary.mixed_revenue'));
        $this->assertEquals(10, $response->json('summary.protocol_revenue'));
        $this->assertEquals(200, $response->json('summary.client_account_revenue'));
        $this->assertEquals(200, $response->json('uninvoiced.period.amount'));
        $this->assertEquals(200, $response->json('uninvoiced.current.amount'));
        $response->assertJsonPath('meta.timezone', 'Europe/Bucharest')->assertJsonPath('meta.currency', 'RON');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString(self::TOKEN, $response->getContent());
        $this->assertStringNotContainsString('pin_code', $response->getContent());
    }

    public function test_daily_period_uses_completion_time_and_bucharest_midnight_boundaries(): void
    {
        $inside = $this->completedOrder('cash', '75.00', '2026-10-29 22:00:00');
        $inside->update(['created_at' => '2026-10-01 10:00:00']);
        $this->completedOrder('cash', '100.00', '2026-10-29 21:59:59');
        $this->completedOrder('cash', '200.00', '2026-10-30 22:00:00');
        $response = $this->withToken(self::TOKEN)->getJson('/api/v1/service/reports/daily?date=2026-10-30')->assertOk();

        $this->assertEquals(1, $response->json('summary.successful_orders'));
        $this->assertEquals(75, $response->json('summary.total_revenue'));
        $this->assertEquals(1, $response->json('orders.total'));
    }

    public function test_monthly_report_keeps_prior_period_uninvoiced_balances_and_counts_invoice_amount_once(): void
    {
        $customer = $this->customer();
        $first = $this->accountOrder($customer, '100.00', ['completed_at' => '2026-10-02 10:00:00']);
        $second = $this->accountOrder($customer, '50.00', ['completed_at' => '2026-10-05 10:00:00']);
        $this->accountOrder($customer, '25.00', ['completed_at' => '2026-10-10 10:00:00']);
        $this->accountOrder($customer, '40.00', ['completed_at' => '2026-09-10 10:00:00']);
        app(ServiceBillingService::class)->recordInvoice([$first->id, $second->id], [
            'number' => 'REPORT-001', 'amount' => '145.00', 'issued_on' => '2026-10-30',
        ]);
        $response = $this->withToken(self::TOKEN)->getJson('/api/v1/service/reports/monthly?month=2026-10')->assertOk();

        $this->assertEquals(175, $response->json('summary.client_account_revenue'));
        $this->assertEquals(0, $response->json('summary.total_revenue'));
        $this->assertEquals(25, $response->json('uninvoiced.period.amount'));
        $this->assertEquals(65, $response->json('uninvoiced.current.amount'));
        $this->assertEquals(1, $response->json('invoices.count'));
        $this->assertEquals(145, $response->json('invoices.amount'));
        $this->assertCount(1, $response->json('invoices.records'));
        $this->assertEquals(65, $response->json('uninvoiced.clients.0.amount'));
    }

    public function test_order_details_are_paginated_without_reducing_summary_totals(): void
    {
        $this->completedOrder('cash', '10.00', '2026-10-30 08:00:00');
        $this->completedOrder('cash', '20.00', '2026-10-30 09:00:00');
        $this->completedOrder('cash', '30.00', '2026-10-30 10:00:00');
        $response = $this->withToken(self::TOKEN)
            ->getJson('/api/v1/service/reports/daily?date=2026-10-30&per_page=2&page=2')->assertOk();

        $this->assertEquals(3, $response->json('orders.total'));
        $this->assertEquals(2, $response->json('orders.current_page'));
        $this->assertCount(1, $response->json('orders.data'));
        $this->assertEquals(60, $response->json('summary.total_revenue'));
    }

    public function test_report_preserves_fractional_surface_and_groups_matching_names_by_unit(): void
    {
        $carpet = $this->completedOrder('cash', '5.00', '2026-10-30 08:00:00');
        $carpet->items()->first()->update(['name' => 'Curățare', 'unit' => 'm²', 'quantity' => 2.5]);
        $piece = $this->completedOrder('cash', '5.00', '2026-10-30 09:00:00');
        $piece->items()->first()->update(['name' => 'Curățare', 'unit' => 'buc.']);

        $response = $this->withToken(self::TOKEN)
            ->getJson('/api/v1/service/reports/daily?date=2026-10-30')->assertOk();

        $services = collect($response->json('services'))->keyBy('unit');
        $this->assertCount(2, $services);
        $this->assertEquals(2.5, $services['m²']['quantity_sold']);
        $this->assertEquals(12.5, $services['m²']['revenue']);
        $this->assertEquals(1, $services['buc.']['quantity_sold']);
        $this->assertEquals(5, $services['buc.']['revenue']);
        $this->assertEquals(17.5, $response->json('summary.total_revenue'));
        $response->assertJsonPath('orders.data.0.items.0.unit', 'm²');
        $this->assertEquals(2.5, $response->json('orders.data.0.items.0.quantity'));
        $this->assertEquals(12.5, $response->json('orders.data.0.items.0.line_total'));

        $report = app(\App\Modules\Service\Services\ServiceReportService::class)
            ->dailyForStaff($this->operator->id, $this->operator->name);
        $this->assertEquals(2.5, $report['services_sold']->firstWhere('unit', 'm²')->total_qty);
        $this->assertStringContainsString('2.5 m²', view('service.print-daily-report', compact('report'))->render());
    }

    private function completedOrder(string $method, string $amount, string $completedAt)
    {
        $order = $this->order();
        $order->items()->first()->update(['unit_price' => $amount]);
        $order->refresh()->update([
            'status' => 'completed', 'payment_status' => 'paid', 'payment_method' => $method,
            'completed_at' => $completedAt,
        ]);

        return $order->refresh();
    }
}
