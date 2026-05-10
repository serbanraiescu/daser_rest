<?php

namespace App\Modules\Kitchen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function index(): View
    {
        $settings = \App\Modules\Settings\Models\CompanySetting::first() ?? new \App\Modules\Settings\Models\CompanySetting();
        $title = 'Kitchen Monitor';
        $destination = 'kitchen';
        return view('kitchen.index', compact('settings', 'title', 'destination'));
    }

    public function barIndex(): View
    {
        $settings = \App\Modules\Settings\Models\CompanySetting::first() ?? new \App\Modules\Settings\Models\CompanySetting();
        $title = 'Bar Monitor';
        $destination = 'bar';
        return view('kitchen.index', compact('settings', 'title', 'destination'));
    }

    public function getOrders(Request $request): JsonResponse
    {
        $destination = $request->query('destination', 'kitchen');

        // Fetch orders that have at least one item for this destination that is NOT delivered
        // AND the order itself is not paid or cancelled
        $orders = Order::with(['items' => function($query) use ($destination) {
                $query->where('destination', $destination)->with('variation');
            }])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->whereHas('items', function($query) use ($destination) {
                $query->where('destination', $destination)
                      ->where('status', '!=', 'delivered');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Map the "status" of the order object in JSON to match the collective status of items for THIS destination
        // This keeps the frontend Kanban columns working without changes
        foreach ($orders as $order) {
            $itemStatuses = $order->items->pluck('status')->unique();
            
            if ($itemStatuses->contains('pending')) {
                $order->status = 'pending';
            } elseif ($itemStatuses->contains('preparing')) {
                $order->status = 'preparing';
            } elseif ($itemStatuses->contains('ready')) {
                $order->status = 'ready';
            } else {
                $order->status = 'delivered';
            }
        }

        return response()->json($orders)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,delivered',
            'destination' => 'required|in:kitchen,bar'
        ]);

        $newStatus = $request->status;
        $destination = $request->destination;

        // Update items for this destination ONLY
        $order->items()->where('destination', $destination)->update(['status' => $newStatus]);

        // Check if we should update the main order status
        // If all items in the entire order are delivered, mark order as ready or delivered
        $allItemStatuses = $order->items()->pluck('status')->unique();
        
        if ($allItemStatuses->count() === 1 && $allItemStatuses->first() === 'delivered') {
             $order->update(['status' => 'delivered']);
        } elseif ($allItemStatuses->contains('preparing')) {
             $order->update(['status' => 'preparing']);
        } elseif ($allItemStatuses->contains('ready') && !$allItemStatuses->contains('pending') && !$allItemStatuses->contains('preparing')) {
             $order->update(['status' => 'ready']);
        }
        // If some are delivered but others are pending, the Order status stays at the "lowest" active state

        return response()->json(['success' => true]);
    }
}
