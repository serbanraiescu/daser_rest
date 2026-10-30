<?php

namespace App\Modules\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Services\ServiceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceReportApiController extends Controller
{
    public function daily(Request $request, ServiceReportService $reports): JsonResponse
    {
        $request->validate(['date' => ['sometimes', 'required', 'date_format:Y-m-d']]);

        return $this->report($request, $reports, 'daily', $request->query('date'));
    }

    public function monthly(Request $request, ServiceReportService $reports): JsonResponse
    {
        $request->validate(['month' => ['sometimes', 'required', 'date_format:Y-m']]);
        $month = $request->query('month');

        return $this->report($request, $reports, 'monthly', $month ? $month.'-01' : null);
    }

    private function report(Request $request, ServiceReportService $reports, string $period, ?string $date): JsonResponse
    {
        $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $range = $reports->range($period, $date);
        $data = $reports->build($range['start'], $range['end'], null, false);
        $orders = $reports->ordersForPeriod($range['start'], $range['end'])
            ->with(['items', 'staff:id,name', 'invoice:id,number'])
            ->orderByRaw('COALESCE(completed_at, created_at) ASC')->orderBy('id')
            ->paginate((int) $request->query('per_page', 100));

        return response()->json([
            'meta' => ['period' => $period] + $data['meta'],
            'summary' => $data['kpis'],
            'uninvoiced' => $data['uninvoiced'],
            'invoices' => $data['invoices'],
            'services' => $data['services'],
            'staff' => $data['staff'],
            'orders' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'data' => $orders->getCollection()->map(fn ($order) => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'customer_id' => $order->service_customer_id,
                    'customer_name' => $order->customer_name,
                    'vehicle_number' => $order->vehicle_number,
                    'operator_name' => $order->staff?->name,
                    'total' => round((float) $order->total, 2),
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'invoice_number' => $order->invoice?->number,
                    'created_at' => $order->created_at->copy()->setTimezone($reports->timezone())->toIso8601String(),
                    'completed_at' => $order->completed_at?->copy()->setTimezone($reports->timezone())->toIso8601String(),
                    'items' => $order->items->map(fn ($item) => [
                        'name' => $item->name, 'unit' => $item->unit,
                        'quantity' => (float) $item->quantity, 'unit_price' => (float) $item->unit_price,
                        'line_total' => (float) $item->line_total,
                    ])->all(),
                ])->all(),
            ],
        ]);
    }
}
