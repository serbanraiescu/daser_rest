<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Menu\Models\Product;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.notes' => 'nullable|string|max:500', // Matches 'special_instructions' on frontend
            'table_number' => 'nullable|string|max:10',
            'payment_method' => 'required|in:cash,card',
        ]);

        $settings = CompanySetting::first();
        if (!$settings || !$settings->enable_ordering) {
            return response()->json(['message' => 'Comenzile sunt momentan dezactivate.'], 403);
        }

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

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)), // Simple ID for now
                'status' => 'pending',
                'total' => $total,
                'payment_method' => $validated['payment_method'],
                'table_number' => $validated['table_number'] ?? null,
                'notes' => null, // Order-level notes vs Item-level notes
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            return response()->json([
                'message' => 'Comanda a fost trimisă!',
                'order_number' => $order->order_number,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Eroare la procesarea comenzii: ' . $e->getMessage()], 500);
        }
    }
}
