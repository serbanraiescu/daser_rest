<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Tables\Models\Area;
use App\Modules\Tables\Models\Table;

use App\Modules\Menu\Models\Product;
use App\Modules\Menu\Models\Category;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Modules\Orders\Models\OrderFiscalDetail;

class WaiterController extends Controller
{
    public function index()
    {
        $areas = Area::with(['tables' => function($query) {
            // This is a bit expensive but okay for now
            // We'll calculate occupancy in the view or controller
        }])->get();
        
        $settings = \App\Modules\Settings\Models\CompanySetting::first() ?? new \App\Modules\Settings\Models\CompanySetting();
        
        // Enrich tables with active order info
        foreach ($areas as $area) {
            foreach ($area->tables as $table) {
                $table->active_order = \App\Modules\Orders\Models\Order::where('table_number', $table->name)
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->first();
            }
        }

        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => function($q) {
                $q->where('is_available', true)->with('variations');
            }])->get();

        return view('waiter.index', compact('areas', 'settings', 'categories'));
    }

    public function menu($tableId)
    {
        $table = Table::findOrFail($tableId);
        $categories = Category::with(['products' => function($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }, 'products.variations'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
            
        $settings = \App\Modules\Settings\Models\CompanySetting::first() ?? new \App\Modules\Settings\Models\CompanySetting();
        
        return view('waiter.menu', compact('table', 'categories', 'settings'));
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.notes' => 'nullable|string|max:500',
            'table_number' => 'required|string|max:10',
            'payment_method' => 'required|in:cash,card',
        ]);

        // Note: We intentionally skip the settings->enable_ordering check for waiters

        try {
            DB::beginTransaction();

            $total = 0;
            $orderItems = [];

            foreach ($request->input('items') as $itemData) {
                $product = Product::with('category', 'variations')->find($itemData['id']);
                
                $price = $product->price;
                $name = $product->name;
                $variationId = $itemData['variation_id'] ?? null;
                $destination = $product->category->destination ?? 'kitchen';

                if ($variationId) {
                    $variation = $product->variations->where('id', $variationId)->first();
                    if ($variation) {
                        $price += $variation->price;
                        $name .= ' (' . $variation->name . ')';
                    }
                }

                $lineTotal = $price * $itemData['quantity'];
                $total += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variation_id' => $variationId,
                    'name' => $name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'notes' => $itemData['notes'] ?? null,
                    'destination' => $destination,
                ];
            }

            // Check for existing active order for this table
            $order = Order::where('table_number', $validated['table_number'])
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->first();

            if ($order) {
                // Update existing order
                $order->total += $total;
                $order->save();
                
                // Add note about update
                $currentNotes = $order->notes ?? '';
                $newNote = "\n[Add: " . now()->format('H:i') . "]";
                if (strpos($currentNotes, $newNote) === false) {
                    $order->update(['notes' => $currentNotes . $newNote]);
                }
            } else {
                // Create new order
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                    'status' => 'pending',
                    'total' => $total,
                    'payment_method' => $validated['payment_method'],
                    'table_number' => $validated['table_number'],
                    'notes' => 'Comandă Ospătar (' . (session('staff_name') ?? 'Staff') . ')',
                ]);
            }

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comanda a fost trimisă!',
                'redirect' => route('waiter.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Eroare: ' . $e->getMessage()], 500);
        }
    }

    public function showOrder($tableId)
    {
        $table = Table::findOrFail($tableId);
        $order = \App\Modules\Orders\Models\Order::with(['items.variation'])
            ->where('table_number', $table->name)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->firstOrFail();

        $settings = \App\Modules\Settings\Models\CompanySetting::first();
        
        return view('waiter.order-details', compact('table', 'order', 'settings'));
    }

    public function getOrderJson($tableId)
    {
        try {
            $table = Table::findOrFail($tableId);
            $order = \App\Modules\Orders\Models\Order::with(['items.variation', 'fiscalDetails'])
                ->where('table_number', $table->name)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->first();

            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function payOrder(Request $request, $orderId)
    {
        $order = \App\Modules\Orders\Models\Order::findOrFail($orderId);
        
        $request->validate([
            'payment_method' => 'required|in:cash,card',
            'is_fiscal' => 'boolean',
            'fiscal_data' => 'required_if:is_fiscal,true|array'
        ]);

        $order->update([
            'status' => 'paid',
            'payment_method' => $request->payment_method
        ]);

        if ($request->is_fiscal) {
            \App\Modules\Orders\Models\OrderFiscalDetail::create(array_merge(
                ['order_id' => $order->id],
                $request->fiscal_data
            ));
        }

        return redirect()->route('waiter.index')->with('success', 'Comanda a fost încasată cu succes!');
    }

    public function payPartial(Request $request, $orderId)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,card',
            'is_fiscal' => 'boolean',
            'fiscal_data' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $originalOrder = Order::findOrFail($orderId);
            $itemsToPay = $request->items; // Format: [{id: 1, quantity: 2}, ...]

            // Calculate total for the new split order
            $splitTotal = 0;
            $newOrderItems = [];

            foreach ($itemsToPay as $itemData) {
                $originalItem = OrderItem::findOrFail($itemData['id']);
                
                // Validate quantity
                if ($itemData['quantity'] > $originalItem->quantity) {
                    throw new \Exception("Cantitate invalidă pentru " . $originalItem->name);
                }

                $price = $originalItem->price;
                $lineTotal = $price * $itemData['quantity'];
                $splitTotal += $lineTotal;

                // Create item data for new order
                $newOrderItems[] = [
                    'product_id' => $originalItem->product_id,
                    'variation_id' => $originalItem->variation_id,
                    'name' => $originalItem->name,
                    'price' => $price,
                    'quantity' => $itemData['quantity'],
                    'notes' => $originalItem->notes,
                    'destination' => $originalItem->destination,
                ];

                // Reduce quantity or remove item from original order
                if ($originalItem->quantity == $itemData['quantity']) {
                    $originalItem->delete();
                } else {
                    $originalItem->quantity -= $itemData['quantity'];
                    $originalItem->save();
                }
            }

            // Update original order total
            $originalOrder->total -= $splitTotal;
            if ($originalOrder->total < 0) $originalOrder->total = 0; // Safety
            
            // If original order is empty, just mark it paid/closed? 
            // Better to delete it if it has no items, or mark as cancelled/archived.
            // But if we deleted all items above, check count.
            if ($originalOrder->items()->count() == 0) {
                $originalOrder->status = 'paid'; // Or just close it out
                // We'll reuse this order object if we were paying full, but here we are creating a split.
                // If everything was moved, effectively the original order is gone and we have a new one?
                // Actually, if everything is moved, we should probably just update the original order's payment info instead of creating a new one.
                // But for consistency of "Splitting", let's creating the new one and close the old one if empty.
                 $originalOrder->delete(); // Soft delete or hard? Let's delete to clear table.
            } else {
                $originalOrder->save();
            }

            // Create the new "Split" Order
            // Use a suffix for the order number if possible, or just a new random one.
            $newOrder = Order::create([
                'order_number' => $originalOrder->order_number . '-SPLIT-' . Str::random(4),
                'status' => 'paid',
                'total' => $splitTotal,
                'payment_method' => $request->payment_method,
                'table_number' => $originalOrder->table_number,
                'notes' => 'Achitat Parțial din ' . $originalOrder->order_number,
            ]);

            foreach ($newOrderItems as $item) {
                $newOrder->items()->create($item);
            }

            if ($request->is_fiscal && $request->fiscal_data) {
                \App\Modules\Orders\Models\OrderFiscalDetail::create(array_merge(
                    ['order_id' => $newOrder->id],
                    $request->fiscal_data
                ));
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Plata parțială a fost înregistrată!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Eroare: ' . $e->getMessage()], 500);
        }
    }

    public function removeItem($itemId)
    {
        try {
            DB::beginTransaction();
            $item = OrderItem::findOrFail($itemId);
            $order = $item->order;
            
            $order->total -= ($item->price * $item->quantity);
            $order->save();
            
            $item->delete();
            
            // If no more items, cancel the whole order? Or just keep it empty?
            // Let's keep it for now, or if zero items, maybe delete order.
            
            DB::commit();
            return back()->with('success', 'Produsul a fost eliminat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Eroare la eliminarea produsului.');
        }
    }

    public function updateQuantity(Request $request, $itemId)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        
        try {
            DB::beginTransaction();
            $item = OrderItem::findOrFail($itemId);
            $order = $item->order;
            
            // Remove old price
            $order->total -= ($item->price * $item->quantity);
            
            // Update quantity
            $item->quantity = $request->quantity;
            $item->save();
            
            // Add new price
            $order->total += ($item->price * $item->quantity);
            $order->save();
            
            DB::commit();
            return back()->with('success', 'Cantitatea a fost actualizată.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Eroare la actualizarea cantității.');
        }
    }

    public function printBill($orderId)
    {
        $order = \App\Modules\Orders\Models\Order::with(['items.variation'])->findOrFail($orderId);
        $settings = \App\Modules\Settings\Models\CompanySetting::first();
        
        return view('waiter.print-bill', compact('order', 'settings'));
    }
}
