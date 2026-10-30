<?php

namespace App\Filament\Pages;

use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Pages\Page;
use App\Modules\Service\Services\ServiceReportService;

class ServiceReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Rapoarte & Istoric Vulcanizare';
    protected static ?string $navigationLabel = 'Rapoarte Vulcanizare';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.service-reports';

    public string $period = 'daily'; // daily, weekly, monthly, custom
    public string $selectedDate = '';
    public string $startDate = '';
    public string $endDate = '';
    
    public ?array $selectedServiceOrderItems = null;
    public ?string $selectedVehicleNumber = null;
    public ?int $selectedOrderId = null;
    public bool $showServiceOrderModal = false;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public function mount(): void
    {
        $this->selectedDate = now(config('service_reporting.timezone'))->format('Y-m-d');
        $this->startDate = now(config('service_reporting.timezone'))->subDays(7)->format('Y-m-d');
        $this->endDate = now(config('service_reporting.timezone'))->format('Y-m-d');
    }

    public function viewOrderItems(int $orderId): void
    {
        abort_unless(static::canAccess(), 403);
        $order = ServiceOrder::with('items')->find($orderId);
        if ($order) {
            $this->selectedOrderId = $order->id;
            $this->selectedVehicleNumber = $order->vehicle_number ?: 'Nespecificat';
            $this->selectedServiceOrderItems = $order->items->toArray();
            $this->showServiceOrderModal = true;
        }
    }

    public function closeOrderModal(): void
    {
        $this->showServiceOrderModal = false;
        $this->selectedServiceOrderItems = null;
        $this->selectedVehicleNumber = null;
        $this->selectedOrderId = null;
    }

    public function getReportData(): array
    {
        abort_unless(static::canAccess(), 403);
        $range = $this->getDateRange();

        return app(ServiceReportService::class)->build($range['start'], $range['end']);
    }

    protected function getDateRange(): array
    {
        $this->validate([
            'period' => 'required|in:daily,weekly,monthly,custom',
            'selectedDate' => 'nullable|date_format:Y-m-d',
            'startDate' => 'nullable|date_format:Y-m-d',
            'endDate' => 'nullable|date_format:Y-m-d',
        ]);

        return app(ServiceReportService::class)->range($this->period, $this->selectedDate, $this->startDate, $this->endDate);
    }
}
