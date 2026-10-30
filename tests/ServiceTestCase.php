<?php

namespace Tests;

use App\Modules\Licensing\Http\Middleware\CheckLicenseStatus;
use App\Modules\Service\Models\ServiceCustomer;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use App\Modules\Staff\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

abstract class ServiceTestCase extends TestCase
{
    use RefreshDatabase;

    protected StaffMember $operator;

    protected function beforeRefreshingDatabase(): void
    {
        // Never refresh the developer's configured database, even when .env is present.
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
            'app.timezone' => 'UTC',
            'session.driver' => 'array',
            'cache.default' => 'array',
        ]);
        DB::purge('sqlite');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-30 12:00:00', 'UTC'));
        $this->withoutMiddleware(CheckLicenseStatus::class);
        CompanySetting::create(['site_name' => 'Service test', 'enable_service_module' => true, 'currency' => 'RON']);
        $this->operator = StaffMember::create([
            'name' => 'Operator test', 'pin_code' => '1234', 'role' => 'service', 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    protected function asServiceOperator(): static
    {
        return $this->withSession([
            'staff_id' => $this->operator->id, 'staff_name' => $this->operator->name, 'staff_role' => 'service',
        ]);
    }

    protected function customer(array $attributes = []): ServiceCustomer
    {
        return ServiceCustomer::create(array_merge([
            'name' => 'Client test', 'fiscal_code' => 'TEST123', 'phone' => '0700000000', 'is_active' => true,
        ], $attributes));
    }

    protected function order(array $attributes = []): ServiceOrder
    {
        $item = ServiceItem::create(['name' => 'Montaj test', 'price' => '125.50', 'unit' => 'buc', 'is_active' => true]);
        $order = ServiceOrder::create(array_merge([
            'staff_member_id' => $this->operator->id, 'status' => 'open', 'payment_status' => 'unpaid',
            'vehicle_number' => 'TEST-01',
        ], $attributes));
        $order->items()->create([
            'service_item_id' => $item->id, 'name' => $item->name, 'quantity' => 1, 'unit_price' => $item->price,
            'unit' => 'buc',
        ]);

        return $order->refresh();
    }

    protected function accountOrder(?ServiceCustomer $customer = null, string $amount = '125.50', array $attributes = []): ServiceOrder
    {
        $customer ??= $this->customer();
        $order = $this->order();
        $order->items()->first()->update(['unit_price' => $amount]);
        $order->refresh()->update(array_merge([
            'status' => 'completed', 'payment_method' => 'client_account', 'payment_status' => 'unpaid',
            'service_customer_id' => $customer->id, 'customer_name' => $customer->name, 'completed_at' => now(),
        ], $attributes));

        return $order->refresh();
    }
}
