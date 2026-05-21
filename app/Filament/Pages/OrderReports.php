<?php

namespace App\Filament\Pages;

use App\Modules\Orders\Models\Order;
use App\Modules\Staff\Models\StaffMember;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class OrderReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Rapoarte & Istoric Comenzi';
    protected static ?string $navigationLabel = 'Rapoarte & Istoric';
    protected static ?string $navigationGroup = 'Restaurant Management';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.order-reports';

    public string $period = 'daily'; // daily, weekly, monthly, custom
    public string $selectedDate = '';
    public string $startDate = '';
    public string $endDate = '';
    
    public ?array $selectedOrderItems = null;
    public ?string $selectedOrderNumber = null;
    public bool $showOrderModal = false;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(7)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function viewOrderItems(int $orderId): void
    {
        $order = Order::with('items')->find($orderId);
        if ($order) {
            $this->selectedOrderNumber = $order->order_number;
            $this->selectedOrderItems = $order->items->toArray();
            $this->showOrderModal = true;
        }
    }

    public function closeOrderModal(): void
    {
        $this->showOrderModal = false;
        $this->selectedOrderItems = null;
        $this->selectedOrderNumber = null;
    }

    public static function resolveWaiterName($order): string
    {
        if ($order->waiter) {
            return $order->waiter->name;
        }

        // Try to extract from notes
        if ($order->notes && preg_match('/Ospătar \(([^)]+)\)/i', $order->notes, $matches)) {
            return $matches[1];
        }

        if ($order->notes && preg_match('/Comandă Ospătar \(([^)]+)\)/i', $order->notes, $matches)) {
            return $matches[1];
        }

        // If table_number is present, it's a customer ordering at a table (QR code)
        if (!empty($order->table_number)) {
            return 'Client (Masa ' . $order->table_number . ')';
        }

        return 'Comandă Online';
    }

    public function getReportData(): array
    {
        $dateRange = $this->getDateRange();
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // 1. KPI-uri Generale
        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);
        
        $totalOrders = $ordersQuery->count();
        $successfulOrders = (clone $ordersQuery)->whereIn('status', ['paid', 'delivered'])->count();
        $cancelledOrders = (clone $ordersQuery)->where('status', 'cancelled')->count();
        $totalRevenue = (clone $ordersQuery)->whereIn('status', ['paid', 'delivered'])->sum('total');
        $averageValue = $successfulOrders > 0 ? $totalRevenue / $successfulOrders : 0;

        // 2. Vânzări Produse (Grupate pe nume din order_items)
        $productSales = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['paid', 'delivered'])
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'order_items.name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('order_items.name')
            ->orderBy('quantity_sold', 'desc')
            ->get()
            ->toArray();

        // 4. Istoric Comenzi Detaliat
        $history = Order::with('waiter')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Performanță Ospătari (Calculată în PHP pentru a folosi helperul inteligent)
        $waiterSalesMap = [];
        foreach ($history as $order) {
            if (!in_array($order->status, ['paid', 'delivered'])) {
                continue;
            }
            $waiterName = self::resolveWaiterName($order);
            if (!isset($waiterSalesMap[$waiterName])) {
                $waiterSalesMap[$waiterName] = (object)[
                    'waiter_name' => $waiterName,
                    'orders_count' => 0,
                    'total_sales' => 0.0,
                ];
            }
            $waiterSalesMap[$waiterName]->orders_count++;
            $waiterSalesMap[$waiterName]->total_sales += floatval($order->total);
        }

        // Sort desc by sales value
        usort($waiterSalesMap, function ($a, $b) {
            return $b->total_sales <=> $a->total_sales;
        });

        return [
            'kpis' => [
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => floatval($totalRevenue),
                'average_value' => floatval($averageValue),
            ],
            'products' => $productSales,
            'waiters' => $waiterSalesMap,
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
