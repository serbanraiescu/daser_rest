<?php

namespace App\Filament\Pages;

use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

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
        $this->selectedDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(7)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function viewOrderItems(int $orderId): void
    {
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
        $dateRange = $this->getDateRange();
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // 1. KPI-uri Generale
        $ordersQuery = ServiceOrder::whereBetween('created_at', [$start, $end]);
        
        $totalOrders = $ordersQuery->count();
        $successfulOrders = (clone $ordersQuery)->where('status', 'completed')->count();
        $cancelledOrders = (clone $ordersQuery)->where('status', 'cancelled')->count();
        $totalRevenue = (clone $ordersQuery)->where('status', 'completed')->sum('total');
        $averageValue = $successfulOrders > 0 ? $totalRevenue / $successfulOrders : 0;

        // 2. Vânzări Servicii
        $serviceSales = DB::table('service_order_items')
            ->join('service_orders', 'service_order_items.service_order_id', '=', 'service_orders.id')
            ->where('service_orders.status', 'completed')
            ->whereBetween('service_orders.created_at', [$start, $end])
            ->select(
                'service_order_items.name',
                DB::raw('SUM(service_order_items.quantity) as quantity_sold'),
                DB::raw('SUM(service_order_items.line_total) as revenue')
            )
            ->groupBy('service_order_items.name')
            ->orderBy('quantity_sold', 'desc')
            ->get()
            ->toArray();

        // 3. Istoric Comenzi Servicii Detaliat
        $history = ServiceOrder::with('staff')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Performanță Angajați
        $staffSalesMap = [];
        $cashRevenue = 0.0;
        $cardRevenue = 0.0;
        $mixedRevenue = 0.0;
        $protocolRevenue = 0.0;
        $unpaidRevenue = 0.0;

        foreach ($history as $order) {
            if ($order->status !== 'completed') {
                continue;
            }

            // Pay method breakdown
            if ($order->payment_method === 'cash') {
                $cashRevenue += floatval($order->total);
            } elseif ($order->payment_method === 'card') {
                $cardRevenue += floatval($order->total);
            } elseif ($order->payment_method === 'mixed') {
                $mixedRevenue += floatval($order->total);
            } elseif ($order->payment_method === 'protocol') {
                $protocolRevenue += floatval($order->total);
            } else {
                $unpaidRevenue += floatval($order->total);
            }

            // Staff sales map
            $staffName = $order->staff ? $order->staff->name : 'Nespecificat';
            if (!isset($staffSalesMap[$staffName])) {
                $staffSalesMap[$staffName] = (object)[
                    'staff_name' => $staffName,
                    'orders_count' => 0,
                    'total_sales' => 0.0,
                ];
            }
            $staffSalesMap[$staffName]->orders_count++;
            $staffSalesMap[$staffName]->total_sales += floatval($order->total);
        }

        // Sort staff desc by sales value
        usort($staffSalesMap, function ($a, $b) {
            return $b->total_sales <=> $a->total_sales;
        });

        return [
            'kpis' => [
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => floatval($totalRevenue),
                'average_value' => floatval($averageValue),
                'cash_revenue' => $cashRevenue,
                'card_revenue' => $cardRevenue,
                'mixed_revenue' => $mixedRevenue,
                'protocol_revenue' => $protocolRevenue,
                'unpaid_revenue' => $unpaidRevenue,
            ],
            'services' => $serviceSales,
            'staff' => $staffSalesMap,
            'history' => $history,
            'range_title' => $this->getRangeTitle($start, $end),
        ];
    }

    protected function getDateRange(): array
    {
        switch ($this->period) {
            case 'weekly':
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfWeek()->toDateTimeString(),
                    'end' => $carbon->endOfWeek()->endOfDay()->toDateTimeString(),
                ];
            case 'monthly':
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfMonth()->toDateTimeString(),
                    'end' => $carbon->endOfMonth()->endOfDay()->toDateTimeString(),
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($this->startDate ?: now()->subDays(7))->startOfDay()->toDateTimeString(),
                    'end' => Carbon::parse($this->endDate ?: now())->endOfDay()->toDateTimeString(),
                ];
            case 'daily':
            default:
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfDay()->toDateTimeString(),
                    'end' => $carbon->endOfDay()->toDateTimeString(),
                ];
        }
    }

    protected function getRangeTitle($start, $end): string
    {
        $startF = Carbon::parse($start)->format('d.m.Y');
        $endF = Carbon::parse($end)->format('d.m.Y');
        return $startF === $endF ? "Ziua: $startF" : "Perioada: $startF - $endF";
    }
}
