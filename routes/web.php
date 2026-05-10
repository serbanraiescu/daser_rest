<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Licensing\Http\Controllers\LicenseController;
use App\Modules\Public\Http\Controllers\LandingOneController;
use App\Modules\Deployment\Http\Controllers\DeploymentController;

// Public Frontend
Route::get('/', [LandingOneController::class, 'index'])->name('home');
Route::get('/menu', [\App\Modules\Public\Http\Controllers\MenuController::class, 'index'])->name('menu.index');

// Licensing Routes
Route::prefix('setup/license')->group(function () {
    Route::get('/', [LicenseController::class, 'show'])->name('license.show');
    Route::post('/activate', [LicenseController::class, 'activate'])->name('license.activate');
});

// Deployment
Route::get('/__deploy/run', [DeploymentController::class, 'run']);

// Orders
Route::post('/checkout', [\App\Modules\Orders\Http\Controllers\OrderController::class, 'store'])->name('checkout');

// Staff Auth
Route::get('/staff', [\App\Modules\Staff\Http\Controllers\StaffAuthController::class, 'login'])->name('staff.login');
Route::post('/staff/verify', [\App\Modules\Staff\Http\Controllers\StaffAuthController::class, 'verify'])->name('staff.verify');
Route::post('/staff/logout', [\App\Modules\Staff\Http\Controllers\StaffAuthController::class, 'logout'])->name('staff.logout');

// Staff Apps (Protected)
Route::middleware([\App\Http\Middleware\StaffAuthMiddleware::class])->group(function () {
    Route::get('/waiter', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'index'])->name('waiter.index');
    Route::get('/waiter/menu/{table}', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'menu'])->name('waiter.menu');
    Route::post('/waiter/order/store', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'storeOrder'])->name('waiter.order.store');
    Route::get('/waiter/order/{table}', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'showOrder'])->name('waiter.order.show');
    Route::get('/waiter/api/order/{table}', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'getOrderJson'])->name('waiter.api.order');
    Route::post('/waiter/order/{order}/pay', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'payOrder'])->name('waiter.order.pay');
    Route::post('/waiter/order/{order}/pay-partial', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'payPartial'])->name('waiter.order.pay-partial');
    Route::get('/waiter/order/{order}/print', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'printBill'])->name('waiter.order.print');
    Route::post('/waiter/order/item/{item}/remove', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'removeItem'])->name('waiter.order.item.remove');
    Route::post('/waiter/order/item/{item}/update', [\App\Modules\Staff\Http\Controllers\WaiterController::class, 'updateQuantity'])->name('waiter.order.item.update');
    
    Route::get('/kitchen', [\App\Modules\Kitchen\Http\Controllers\KitchenController::class, 'index'])->name('kitchen.index');
    Route::get('/bar', [\App\Modules\Kitchen\Http\Controllers\KitchenController::class, 'barIndex'])->name('bar.index');
    Route::get('/kitchen/api/orders', [\App\Modules\Kitchen\Http\Controllers\KitchenController::class, 'getOrders'])->name('kitchen.api.orders');
    Route::post('/kitchen/api/orders/{order}/status', [\App\Modules\Kitchen\Http\Controllers\KitchenController::class, 'updateStatus'])->name('kitchen.api.update-status');
});
