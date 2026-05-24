<?php

namespace App\Modules\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Models\ServiceCategory;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Models\ServiceOrderItem;
use App\Modules\Settings\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Display the touch-friendly Service dashboard.
     */
    public function index(Request $request)
    {
        $categories = ServiceCategory::where('is_active', true)
            ->with(['items' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        // Get open orders created by any service staff (or currently active staff) to allow resuming
        $openOrders = ServiceOrder::where('status', 'open')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $settings = CompanySetting::first() ?? new CompanySetting();

        return view('service.index', compact('categories', 'openOrders', 'settings'));
    }

    /**
     * Store a new service order (or update an existing one if ID passed).
     */
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'nullable|exists:service_orders,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.service_item_id' => 'required|exists:service_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $staffId = Session::get('staff_id');

            if ($request->filled('order_id')) {
                $order = ServiceOrder::findOrFail($request->order_id);
                // Clear old items to replace them
                $order->items()->delete();
            } else {
                $order = new ServiceOrder();
                $order->staff_member_id = $staffId;
                $order->status = 'open';
                $order->payment_status = 'unpaid';
            }

            $order->customer_name = $request->customer_name;
            $order->customer_phone = $request->customer_phone;
            $order->vehicle_number = strtoupper($request->vehicle_number);
            $order->notes = $request->notes;
            $order->save(); // Save order to get ID

            // Add new items
            foreach ($request->items as $itemData) {
                $serviceItem = ServiceItem::find($itemData['service_item_id']);
                
                $order->items()->create([
                    'service_item_id' => $serviceItem->id,
                    'name' => $serviceItem->name,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Recalculate total
            $order->recalculateTotal();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comandă înregistrată cu succes!',
                'order' => $order->load('items')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Eroare: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show order details (JSON or printable receipt).
     */
    public function showOrder(ServiceOrder $order)
    {
        return response()->json($order->load('items'));
    }

    /**
     * Finalize and pay service order.
     */
    public function completeOrder(Request $request, ServiceOrder $order)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,mixed',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $order->status = 'completed';
            $order->payment_status = 'paid';
            $order->payment_method = $request->payment_method;
            $order->completed_at = now();
            if ($request->filled('notes')) {
                $order->notes = ($order->notes ? $order->notes . "\n" : "") . "Plată: " . $request->notes;
            }
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Comandă finalizată și achitată cu succes!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la finalizare: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel service order.
     */
    public function cancelOrder(Request $request, ServiceOrder $order)
    {
        try {
            $order->status = 'cancelled';
            $order->payment_status = 'unpaid';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Comandă anulată cu succes!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Eroare la anulare: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compile daily shift report data for service staff member.
     */
    private function compileDailyReportData($staffId): array
    {
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        // Total orders today (excluding cancelled)
        $totalOrdersCount = ServiceOrder::where('staff_member_id', $staffId)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Completed/Paid orders today
        $completedOrdersCount = ServiceOrder::where('staff_member_id', $staffId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        // Total revenue today
        $totalRevenue = ServiceOrder::where('staff_member_id', $staffId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');

        // Cash revenue today
        $cashRevenue = ServiceOrder::where('staff_member_id', $staffId)
            ->where('status', 'completed')
            ->where('payment_method', 'cash')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');

        // Card revenue today
        $cardRevenue = ServiceOrder::where('staff_member_id', $staffId)
            ->where('status', 'completed')
            ->where('payment_method', 'card')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');

        // Mixed/Other revenue today
        $mixedRevenue = ServiceOrder::where('staff_member_id', $staffId)
            ->where('status', 'completed')
            ->where('payment_method', 'mixed')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->sum('total');

        // Services sold today
        $servicesSold = DB::table('service_order_items')
            ->join('service_orders', 'service_order_items.service_order_id', '=', 'service_orders.id')
            ->where('service_orders.staff_member_id', $staffId)
            ->where('service_orders.status', 'completed')
            ->whereBetween('service_orders.created_at', [$startOfDay, $endOfDay])
            ->select(
                'service_order_items.name',
                DB::raw('SUM(service_order_items.quantity) as total_qty'),
                DB::raw('SUM(service_order_items.line_total) as total_value')
            )
            ->groupBy('service_order_items.name')
            ->orderBy('total_qty', 'desc')
            ->get();

        return [
            'staff_name' => Session::get('staff_name'),
            'date' => now()->format('d.m.Y'),
            'total_orders_count' => $totalOrdersCount,
            'completed_orders_count' => $completedOrdersCount,
            'total_revenue' => (float)$totalRevenue,
            'cash_revenue' => (float)$cashRevenue,
            'card_revenue' => (float)$cardRevenue,
            'mixed_revenue' => (float)$mixedRevenue,
            'services_sold' => $servicesSold
        ];
    }

    /**
     * Print shift daily report (80mm thermal receipt style).
     */
    public function printDailyReport()
    {
        try {
            $staffId = Session::get('staff_id');
            if (!$staffId) {
                return redirect()->route('staff.login')->with('error', 'Sesiune expirată.');
            }

            $report = $this->compileDailyReportData($staffId);
            $settings = CompanySetting::first() ?? new CompanySetting();

            return view('service.print-daily-report', compact('report', 'settings'));
        } catch (\Exception $e) {
            return back()->with('error', 'Eroare la generarea raportului: ' . $e->getMessage());
        }
    }

    /**
     * Download shift daily report as PDF (80mm format).
     */
    public function pdfDailyReport()
    {
        try {
            $staffId = Session::get('staff_id');
            if (!$staffId) {
                return redirect()->route('staff.login')->with('error', 'Sesiune expirată.');
            }

            $report = $this->compileDailyReportData($staffId);
            $settings = CompanySetting::first() ?? new CompanySetting();

            $pdf = Pdf::loadView('service.print-daily-report', compact('report', 'settings'));
            // Standard 80mm roll size for DomPDF is custom size: width 80mm (approx 226pt), height auto (approx 500pt or more)
            $pdf->setPaper([0, 0, 226, 700], 'portrait');

            $filename = 'raport-servicii-' . Str::slug(Session::get('staff_name')) . '-' . now()->format('d-m-Y') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            return back()->with('error', 'Eroare la generarea PDF: ' . $e->getMessage());
        }
    }
}
