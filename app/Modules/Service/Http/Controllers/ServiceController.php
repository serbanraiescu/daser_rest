<?php

namespace App\Modules\Service\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Service\Models\ServiceCategory;
use App\Modules\Service\Models\ServiceCustomer;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Models\ServiceOrderItem;
use App\Modules\Settings\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    /**
     * Display the touch-friendly Service dashboard.
     */
    public function index(Request $request)
    {
        $activeItems = fn ($query) => $query->where('is_active', true)->orderBy('sort_order');

        $categories = ServiceCategory::whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'items' => $activeItems,
                'children' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with(['items' => $activeItems])
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        // Get open orders created by any service staff (or currently active staff) to allow resuming
        $openOrders = ServiceOrder::where('status', 'open')
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $settings = CompanySetting::first() ?? new CompanySetting();
        $customers = ServiceCustomer::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'fiscal_code', 'phone']);

        return view('service.index', compact('categories', 'openOrders', 'settings', 'customers'));
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
            'items.*.service_item_id' => [
                'required',
                Rule::exists('service_items', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $staffId = Session::get('staff_id');

            if ($request->filled('order_id')) {
                $order = ServiceOrder::where('status', 'open')->lockForUpdate()->findOrFail($request->order_id);
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
            $order->vehicle_number = strtoupper($request->vehicle_number ?? '');
            $order->notes = $request->notes;
            $order->save(); // Save order to get ID

            // Add new items
            foreach ($request->items as $itemData) {
                $serviceItem = ServiceItem::where('is_active', true)->findOrFail($itemData['service_item_id']);
                
                $order->items()->create([
                    'service_item_id' => $serviceItem->id,
                    'name' => $serviceItem->name,
                    'unit' => $serviceItem->unit,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $serviceItem->price,
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
        abort_unless($order->status === 'open', 404);

        return response()->json($order->load('items'));
    }

    /**
     * Finalize and pay service order.
     */
    public function completeOrder(Request $request, ServiceOrder $order)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,card,mixed,protocol,client_account',
            'service_customer_id' => [
                'exclude_unless:payment_method,client_account', 'required', 'integer',
                Rule::exists('service_customers', 'id')->where('is_active', true),
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $order) {
            $lockedOrder = ServiceOrder::lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'open') {
                throw ValidationException::withMessages(['order' => 'Fișa nu mai este deschisă. Reîncărcați lista înainte de a continua.']);
            }
            if (! $lockedOrder->items()->exists()) {
                throw ValidationException::withMessages(['items' => 'Fișa trebuie să conțină minimum un serviciu.']);
            }
            $onAccount = $validated['payment_method'] === 'client_account';
            $lockedOrder->recalculateTotal();
            $lockedOrder->service_customer_id = $onAccount ? $validated['service_customer_id'] : null;
            $lockedOrder->status = 'completed';
            $lockedOrder->payment_status = $onAccount ? 'unpaid' : 'paid';
            $lockedOrder->payment_method = $validated['payment_method'];
            $lockedOrder->completed_at = now();
            if (! empty($validated['notes'])) {
                $lockedOrder->notes = ($lockedOrder->notes ? $lockedOrder->notes . "\n" : '') . 'Plată: ' . $validated['notes'];
            }
            $lockedOrder->save();
        }, 3);

        return response()->json([
            'success' => true,
            'message' => $validated['payment_method'] === 'client_account'
                ? 'Fișa a fost înregistrată pe contul clientului și așteaptă facturarea.'
                : 'Comandă finalizată și achitată cu succes!',
        ]);
    }

    /**
     * Cancel service order.
     */
    public function cancelOrder(Request $request, ServiceOrder $order)
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = ServiceOrder::lockForUpdate()->findOrFail($order->id);
            abort_unless($lockedOrder->status === 'open', 422, 'Fișa nu mai este deschisă.');
            $lockedOrder->status = 'cancelled';
            $lockedOrder->payment_status = 'unpaid';
            $lockedOrder->save();
        }, 3);

        return response()->json(['success' => true, 'message' => 'Comandă anulată cu succes!']);
    }

    /**
     * Compile daily shift report data for service staff member.
     */
    private function compileDailyReportData($staffId): array
    {
        return app(\App\Modules\Service\Services\ServiceReportService::class)
            ->dailyForStaff((int) $staffId, Session::get('staff_name'));
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
