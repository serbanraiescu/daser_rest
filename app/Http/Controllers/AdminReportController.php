<?php

namespace App\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Models\CompanySetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminReportController extends Controller
{
    /**
     * Compile report data based on parameters.
     */
    private function compileReportData(Request $request): array
    {
        $period = $request->query('period', 'daily');
        $selectedDate = $request->query('selectedDate', now()->format('Y-m-d'));
        $startDate = $request->query('startDate', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->query('endDate', now()->format('Y-m-d'));

        // Calculate date range matching Filament OrderReports
        $dateRange = $this->getDateRange($period, $selectedDate, $startDate, $endDate);
        $start = $dateRange['start'];
        $end = $dateRange['end'];

        // 1. General KPIs
        $ordersQuery = Order::whereBetween('created_at', [$start, $end]);
        
        $totalOrders = $ordersQuery->count();
        $successfulOrders = (clone $ordersQuery)->whereIn('status', ['paid', 'delivered'])->count();
        $cancelledOrders = (clone $ordersQuery)->where('status', 'cancelled')->count();
        $totalRevenue = (clone $ordersQuery)->whereIn('status', ['paid', 'delivered'])->sum('total');
        $averageValue = $successfulOrders > 0 ? $totalRevenue / $successfulOrders : 0;

        // 2. Product Sales
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

        // 3. Detailed History
        $history = Order::with('waiter')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Waiter Performance & Payment Breakdown
        $waiterSalesMap = [];
        $cashRevenue = 0.0;
        $cardRevenue = 0.0;
        $onlineRevenue = 0.0;

        foreach ($history as $order) {
            if (!in_array($order->status, ['paid', 'delivered'])) {
                continue;
            }

            // Pay method breakdown
            if ($order->payment_method === 'cash') {
                $cashRevenue += floatval($order->total);
            } elseif ($order->payment_method === 'card') {
                $cardRevenue += floatval($order->total);
            } else {
                $onlineRevenue += floatval($order->total);
            }

            // Waiter sales map
            $waiterName = \App\Filament\Pages\OrderReports::resolveWaiterName($order);
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

        // Sort waiters desc by sales value
        usort($waiterSalesMap, function ($a, $b) {
            return $b->total_sales <=> $a->total_sales;
        });

        $settings = CompanySetting::first() ?? new CompanySetting();
        $currency = $settings->currency ?? 'RON';

        return [
            'kpis' => [
                'total_orders' => $totalOrders,
                'successful_orders' => $successfulOrders,
                'cancelled_orders' => $cancelledOrders,
                'total_revenue' => floatval($totalRevenue),
                'average_value' => floatval($averageValue),
                'cash_revenue' => $cashRevenue,
                'card_revenue' => $cardRevenue,
                'online_revenue' => $onlineRevenue,
            ],
            'products' => $productSales,
            'waiters' => $waiterSalesMap,
            'history' => $history,
            'range_title' => $this->getRangeTitle($start, $end),
            'settings' => $settings,
            'currency' => $currency,
            'period' => $period,
            'selectedDate' => $selectedDate,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Show report in standalone print view.
     */
    public function printReport(Request $request)
    {
        try {
            $data = $this->compileReportData($request);
            return view('admin.print-order-report', $data);
        } catch (\Exception $e) {
            return back()->with('error', 'Eroare la generarea printului: ' . $e->getMessage());
        }
    }

    /**
     * Download report as PDF.
     */
    public function pdfReport(Request $request)
    {
        try {
            $data = $this->compileReportData($request);
            
            $pdf = Pdf::loadView('admin.print-order-report', $data);
            $pdf->setPaper('a4', 'portrait');
            
            $filename = 'raport-admin-' . Str::slug($data['range_title']) . '-' . now()->format('d-m-Y') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Eroare la generarea PDF-ului: ' . $e->getMessage());
        }
    }

    /**
     * Helper to compute date range.
     */
    private function getDateRange($period, $selectedDate, $startDate, $endDate): array
    {
        switch ($period) {
            case 'weekly':
                $carbon = Carbon::parse($selectedDate ?: now());
                return [
                    'start' => $carbon->startOfWeek()->toDateTimeString(),
                    'end' => $carbon->endOfWeek()->endOfDay()->toDateTimeString(),
                ];
            case 'monthly':
                $carbon = Carbon::parse($selectedDate ?: now());
                return [
                    'start' => $carbon->startOfMonth()->toDateTimeString(),
                    'end' => $carbon->endOfMonth()->endOfDay()->toDateTimeString(),
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($startDate ?: now()->subDays(7))->startOfDay()->toDateTimeString(),
                    'end' => Carbon::parse($endDate ?: now())->endOfDay()->toDateTimeString(),
                ];
            case 'daily':
            default:
                $carbon = Carbon::parse($selectedDate ?: now());
                return [
                    'start' => $carbon->startOfDay()->toDateTimeString(),
                    'end' => $carbon->endOfDay()->toDateTimeString(),
                ];
        }
    }

    /**
     * Helper to compute label range title.
     */
    private function getRangeTitle($start, $end): string
    {
        $startF = Carbon::parse($start)->format('d.m.Y');
        $endF = Carbon::parse($end)->format('d.m.Y');
        return $startF === $endF ? "Ziua $startF" : "Perioada $startF - $endF";
    }
}
