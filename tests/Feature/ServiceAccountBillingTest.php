<?php

namespace Tests\Feature;

use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Services\ServiceBillingService;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\Validation\ValidationException;
use Tests\ServiceTestCase;

class ServiceAccountBillingTest extends ServiceTestCase
{
    public function test_account_completion_requires_a_saved_active_client_and_keeps_the_order_unpaid(): void
    {
        $customer = $this->customer();
        $order = $this->order();

        $this->asServiceOperator()->postJson("/service/orders/{$order->id}/complete", [
            'payment_method' => 'client_account', 'service_customer_id' => $customer->id,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('service_orders', [
            'id' => $order->id, 'status' => 'completed', 'payment_status' => 'unpaid',
            'payment_method' => 'client_account', 'service_customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);
        $this->assertNotNull($order->refresh()->completed_at);
        $this->assertSame(1, ServiceOrder::uninvoiced()->count());
    }

    public function test_missing_or_inactive_customer_cannot_complete_an_account_order(): void
    {
        $order = $this->order();
        $inactive = $this->customer(['is_active' => false]);
        foreach ([null, $inactive->id, 999999] as $customerId) {
            $this->asServiceOperator()->postJson("/service/orders/{$order->id}/complete", [
                'payment_method' => 'client_account', 'service_customer_id' => $customerId,
            ])->assertUnprocessable();
            $this->assertSame('open', $order->refresh()->status);
        }
        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_cash_card_mixed_and_protocol_keep_the_existing_completion_flow(): void
    {
        foreach (['cash', 'card', 'mixed', 'protocol'] as $method) {
            $order = $this->order();
            $this->asServiceOperator()->postJson("/service/orders/{$order->id}/complete", [
                'payment_method' => $method,
            ])->assertOk();
            $this->assertDatabaseHas('service_orders', [
                'id' => $order->id, 'payment_method' => $method, 'payment_status' => 'paid', 'status' => 'completed',
            ]);
        }
        $this->assertSame(0, ServiceOrder::uninvoiced()->count());
    }

    public function test_finalization_cannot_be_repeated_or_used_to_switch_a_client(): void
    {
        $customer = $this->customer();
        $other = $this->customer(['name' => 'Alt client', 'fiscal_code' => 'TEST456']);
        $order = $this->accountOrder($customer);
        $this->asServiceOperator()->postJson("/service/orders/{$order->id}/complete", [
            'payment_method' => 'client_account', 'service_customer_id' => $other->id,
        ])->assertUnprocessable();
        $this->assertEquals($customer->id, $order->refresh()->service_customer_id);
    }

    public function test_operator_authentication_role_and_module_activation_are_enforced(): void
    {
        $order = $this->order();
        $endpoint = "/service/orders/{$order->id}/complete";
        $this->postJson($endpoint, ['payment_method' => 'cash'])->assertRedirect('/staff');
        $this->withSession(['staff_id' => $this->operator->id, 'staff_role' => 'waiter'])
            ->postJson($endpoint, ['payment_method' => 'cash'])->assertForbidden();
        CompanySetting::first()->update(['enable_service_module' => false]);
        $this->asServiceOperator()->postJson($endpoint, ['payment_method' => 'cash'])->assertNotFound();
        $this->assertSame('open', $order->refresh()->status);
    }

    public function test_catalog_prices_are_authoritative_when_an_order_is_created(): void
    {
        $order = $this->order();
        $item = $order->items()->first()->serviceItem;
        $this->asServiceOperator()->postJson('/service/orders', [
            'vehicle_number' => 'test-02',
            'items' => [['service_item_id' => $item->id, 'quantity' => 2, 'unit_price' => 0.01]],
        ])->assertOk()->assertJsonPath('order.total', 251);
    }

    public function test_one_invoice_can_cover_selected_works_of_one_customer_and_keeps_its_actual_amount(): void
    {
        $customer = $this->customer();
        $first = $this->accountOrder($customer, '125.50');
        $second = $this->accountOrder($customer, '74.50');
        $remaining = $this->accountOrder($customer, '25.00');
        $invoice = $this->billing()->recordInvoice([$first->id, $second->id], $this->invoiceData(['amount' => '195.00']));

        $this->assertEquals('195.00', $invoice->amount);
        $this->assertEquals('200.00', $invoice->orders_total);
        $this->assertSame(2, $invoice->orders()->count());
        $this->assertEquals([$remaining->id], ServiceOrder::uninvoiced()->pluck('id')->all());
        $this->assertSame('unpaid', $first->refresh()->payment_status);
        $this->assertDatabaseCount('service_invoices', 1);
    }

    public function test_mixed_clients_missing_orders_and_already_invoiced_orders_are_rejected_atomically(): void
    {
        $first = $this->accountOrder();
        $other = $this->accountOrder($this->customer(['name' => 'Alt client', 'fiscal_code' => 'TEST456']));
        foreach ([[$first->id, $other->id], [$first->id, 999999], []] as $ids) {
            $this->assertInvalidInvoice($ids, $this->invoiceData());
            $this->assertDatabaseCount('service_invoices', 0);
        }
        $this->billing()->recordInvoice([$first->id], $this->invoiceData());
        $this->assertInvalidInvoice([$first->id, $other->id], $this->invoiceData(['number' => 'TEST-002']));
        $this->assertDatabaseCount('service_invoices', 1);
        $this->assertNull($other->refresh()->service_invoice_id);
    }

    public function test_invoice_details_require_a_unique_number_valid_date_and_positive_two_decimal_amount(): void
    {
        $order = $this->accountOrder();
        foreach ([
            ['number' => '   '], ['amount' => '0'], ['amount' => '-1.00'], ['amount' => '1.001'],
            ['amount' => '10000000000.00'], ['issued_on' => 'not-a-date'], ['issued_on' => '2026-10-31'],
        ] as $invalid) {
            $this->assertInvalidInvoice([$order->id], $this->invoiceData($invalid));
            $this->assertNull($order->refresh()->service_invoice_id);
        }
        $this->billing()->recordInvoice([$order->id], $this->invoiceData());
        $second = $this->accountOrder();
        $this->assertInvalidInvoice([$second->id], $this->invoiceData());
        $this->assertNull($second->refresh()->service_invoice_id);
    }

    public function test_disabled_module_cannot_record_an_invoice(): void
    {
        $order = $this->accountOrder();
        CompanySetting::first()->update(['enable_service_module' => false]);
        try {
            $this->billing()->recordInvoice([$order->id], $this->invoiceData());
            $this->fail('Inactive service module accepted an invoice.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $exception) {
            $this->assertContains($exception->getStatusCode(), [403, 404]);
        }
        $this->assertDatabaseCount('service_invoices', 0);
    }

    public function test_completed_account_order_cannot_change_its_customer_or_amount(): void
    {
        $order = $this->accountOrder();
        $this->expectException(ValidationException::class);
        $order->update(['total' => 1]);
    }

    public function test_completed_account_items_cannot_be_changed(): void
    {
        $order = $this->accountOrder();
        $this->expectException(ValidationException::class);
        $order->items()->first()->update(['quantity' => 5]);
    }

    public function test_referenced_customer_cannot_be_deleted(): void
    {
        $customer = $this->customer();
        $this->accountOrder($customer);
        $this->expectException(ValidationException::class);
        $customer->delete();
    }

    public function test_invoice_date_accepts_today_in_romania_after_local_midnight(): void
    {
        config(['service_reporting.timezone' => 'Europe/Bucharest']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-30 22:30:00', 'UTC'));
        $order = $this->accountOrder();
        $invoice = $this->billing()->recordInvoice([$order->id], $this->invoiceData(['issued_on' => '2026-10-31']));
        $this->assertSame('2026-10-31', $invoice->issued_on->format('Y-m-d'));
    }

    public function test_new_migration_refuses_rollback_after_operational_customer_data_exists(): void
    {
        $this->customer();
        $migration = require database_path('migrations/2026_10_30_090000_add_service_customer_accounts.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_open_cash_and_duplicate_order_selections_cannot_be_marked_invoiced(): void
    {
        $open = $this->order();
        $cash = $this->order();
        $cash->update(['status' => 'completed', 'payment_method' => 'cash', 'payment_status' => 'paid', 'completed_at' => now()]);
        $account = $this->accountOrder();
        foreach ([[$open->id], [$cash->id], [$account->id, $account->id]] as $selection) {
            $this->assertInvalidInvoice($selection, $this->invoiceData());
        }
        $this->assertDatabaseCount('service_invoices', 0);
    }

    private function billing(): ServiceBillingService
    {
        return app(ServiceBillingService::class);
    }

    private function invoiceData(array $overrides = []): array
    {
        return array_merge(['number' => 'TEST-001', 'amount' => '125.50', 'issued_on' => '2026-10-30'], $overrides);
    }

    private function assertInvalidInvoice(array $ids, array $data): void
    {
        try {
            $this->billing()->recordInvoice($ids, $data);
            $this->fail('Invalid invoice request was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }
}
