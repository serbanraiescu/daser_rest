<?php

namespace App\Modules\Inventory\Observers;

use App\Modules\Inventory\Services\StockDeductionService;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(
        protected StockDeductionService $deductionService
    ) {}

    /**
     * Declanșat înainte de salvarea unui order.
     * Verifică dacă statusul s-a schimbat la 'paid'.
     */
    public function updating(Order $order): void
    {
        // Procesăm DOAR dacă statusul devine 'paid' și stocul nu a fost dedus
        if ($order->isDirty('status')
            && $order->status === 'paid'
            && !$order->isStockDeducted()
        ) {
            try {
                $this->deductionService->deductForOrder($order);
            } catch (\Throwable $e) {
                // Nu blocăm salvarea comenzii dacă deducerea eșuează
                // Loggăm eroarea pentru debug
                Log::error("Eroare la deducerea stocului pentru comanda #{$order->order_number}: " . $e->getMessage(), [
                    'order_id' => $order->id,
                    'exception' => $e,
                ]);
            }
        }
    }
}
