<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

try {
    Session::put('staff_id', 1);
    Session::put('staff_name', 'Test Waiter');
    
    $staffId = 1;
    $startOfDay = now()->startOfDay();
    $endOfDay = now()->endOfDay();

    echo "Running query...\n";
    $productsSold = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.staff_id', $staffId)
        ->where('orders.status', 'paid')
        ->whereBetween('orders.created_at', [$startOfDay, $endOfDay])
        ->select(
            'order_items.name',
            DB::raw('SUM(order_items.quantity) as total_qty'),
            DB::raw('SUM(order_items.price * order_items.quantity) as total_value')
        )
        ->groupBy('order_items.name', 'order_items.product_id')
        ->orderBy('total_qty', 'desc')
        ->get();

    echo "Query completed! Total items: " . count($productsSold) . "\n";
    print_r($productsSold);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
