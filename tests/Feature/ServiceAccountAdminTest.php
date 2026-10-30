<?php

namespace Tests\Feature;

use App\Filament\Resources\Service\ServiceCustomerResource;
use App\Filament\Resources\Service\ServiceInvoiceResource;
use App\Filament\Resources\Service\ServiceOrderResource;
use App\Filament\Widgets\UninvoicedServiceOrders;
use App\Models\User;
use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Service\Services\ServiceBillingService;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\ServiceTestCase;

class ServiceAccountAdminTest extends ServiceTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::withoutLazyLoading();
    }

    public function test_single_invoice_action_is_rendered_prefilled_and_removes_the_completed_record(): void
    {
        $order = $this->accountOrder(amount: '125.50');
        Livewire::test(UninvoicedServiceOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->mountTableAction('recordInvoice', $order)
            ->assertTableActionDataSet(['amount' => '125.50', 'issued_on' => '2026-10-30'])
            ->setTableActionData(['number' => 'ADMIN-001', 'amount' => '120.00'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors()
            ->assertCanNotSeeTableRecords([$order]);

        $this->assertDatabaseHas('service_invoices', [
            'number' => 'ADMIN-001', 'amount' => 120, 'orders_total' => 125.5, 'created_by' => auth()->id(),
        ]);
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_bulk_invoice_action_prefills_the_selected_sum_and_records_one_invoice(): void
    {
        $customer = $this->customer();
        $first = $this->accountOrder($customer, '100.00');
        $second = $this->accountOrder($customer, '25.50');
        $remaining = $this->accountOrder($customer, '15.00');
        Livewire::test(UninvoicedServiceOrders::class)
            ->mountTableBulkAction('recordInvoice', [$first, $second])
            ->assertTableBulkActionDataSet(['amount' => '125.50', 'issued_on' => '2026-10-30'])
            ->setTableBulkActionData(['number' => 'ADMIN-002', 'amount' => '125.50'])
            ->callMountedTableBulkAction()
            ->assertHasNoTableBulkActionErrors()
            ->assertCanNotSeeTableRecords([$first, $second])
            ->assertCanSeeTableRecords([$remaining]);

        $this->assertDatabaseCount('service_invoices', 1);
        $this->assertSame(2, ServiceInvoice::first()->orders()->count());
    }

    public function test_mixed_customer_bulk_selection_keeps_all_records_pending(): void
    {
        $first = $this->accountOrder();
        $second = $this->accountOrder($this->customer(['name' => 'Alt client']));
        Livewire::test(UninvoicedServiceOrders::class)
            ->callTableBulkAction('recordInvoice', [$first, $second], [
                'number' => 'ADMIN-003', 'amount' => '251.00', 'issued_on' => '2026-10-30',
            ])
            ->assertCanSeeTableRecords([$first, $second]);
        $this->assertDatabaseCount('service_invoices', 0);
        $this->assertNull($first->refresh()->service_invoice_id);
        $this->assertNull($second->refresh()->service_invoice_id);
    }

    public function test_dashboard_customer_registry_and_read_only_invoice_details_render(): void
    {
        $order = $this->accountOrder();
        $invoice = app(ServiceBillingService::class)->recordInvoice([$order->id], [
            'number' => 'ADMIN-VIEW', 'amount' => '125.50', 'issued_on' => '2026-10-30',
        ], auth()->id());

        $this->get('/admin')->assertOk();
        $this->get(ServiceCustomerResource::getUrl())->assertOk()->assertSee('Clienți vulcanizare');
        $this->get(ServiceInvoiceResource::getUrl())->assertOk()->assertSee('ADMIN-VIEW');
        $this->get(ServiceInvoiceResource::getUrl('view', ['record' => $invoice]))
            ->assertOk()->assertSee('ADMIN-VIEW')->assertSee('Fișe incluse');
        $this->assertFalse(ServiceInvoiceResource::canEdit($invoice));
        $this->assertFalse(ServiceInvoiceResource::canDelete($invoice));
        $this->assertFalse(ServiceOrderResource::canEdit($order->refresh()));
        $this->assertFalse(ServiceOrderResource::canDelete($order));
    }

    public function test_service_reports_and_operator_views_render_with_account_balances(): void
    {
        $this->accountOrder(amount: '150.00');
        $this->get('/admin/service-reports')->assertOk()->assertSee('Pe cont client');
        $this->get('/admin/service-reports/print?period=daily&selectedDate=2026-10-30')
            ->assertOk()->assertSee('150.00')->assertSee('Pe cont client');
        $this->asServiceOperator()->get('/service')->assertOk()->assertSee('PE CONT CLIENT')->assertSee('Alege clientul');
        $this->get('/service/print-daily-report')->assertOk()->assertSee('150.00')->assertSee('Pe cont client');
    }

    public function test_empty_widget_and_disabled_module_do_not_expose_service_records(): void
    {
        Livewire::test(UninvoicedServiceOrders::class)->assertSee('Nu sunt fișe de facturat');
        $order = $this->accountOrder();
        CompanySetting::first()->update(['enable_service_module' => false]);

        $this->assertFalse(UninvoicedServiceOrders::canView());
        Livewire::test(UninvoicedServiceOrders::class)
            ->assertCanNotSeeTableRecords([$order])->assertTableBulkActionHidden('recordInvoice');
        $this->get(ServiceCustomerResource::getUrl())->assertForbidden();
        $this->get(ServiceInvoiceResource::getUrl())->assertForbidden();
        $this->get('/admin')->assertOk()->assertDontSee('Vulcanizare — de facturat');
    }
}
