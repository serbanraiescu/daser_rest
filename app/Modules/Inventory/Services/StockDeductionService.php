<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockDeductionService
{
    /**
     * Scade automat stocul pentru o comandă finalizată (status = paid).
     *
     * Metoda este idempotentă: dacă stock_deducted_at este setat, nu face nimic.
     *
     * @param  Order $order
     * @return bool  true dacă a procesat, false dacă era deja procesat
     */
    public function deductForOrder(Order $order): bool
    {
        // Idempotency check — nu deduce de două ori
        if ($order->isStockDeducted()) {
            return false;
        }

        $settings = CompanySetting::first();
        $preventNegative = $settings?->prevent_negative_stock ?? false;

        DB::transaction(function () use ($order, $preventNegative) {

            // Re-lock comanda în tranzacție pentru a preveni concurența
            $order = Order::lockForUpdate()->find($order->id);

            // Double-check după lock
            if ($order->isStockDeducted()) {
                return;
            }

            // Încarcă produsele cu ingredientele și inventory items asociate
            $order->load([
                'items.product.ingredients.inventoryItem',
            ]);

            foreach ($order->items as $orderItem) {
                // Skip dacă produsul nu mai există
                if (!$orderItem->product) {
                    continue;
                }

                $this->processOrderItem($orderItem, $order, $preventNegative);
            }

            // Marchează comanda ca procesată — idempotency stamp
            $order->stock_deducted_at = now();
            $order->save();
        });

        return true;
    }

    /**
     * Procesează un singur order item și scade ingredientele urmărite.
     */
    protected function processOrderItem($orderItem, Order $order, bool $preventNegative): void
    {
        $product        = $orderItem->product;
        $quantitySold   = (int) $orderItem->quantity;

        foreach ($product->ingredients as $ingredient) {
            // Skip ingrediente care nu urmăresc stocul
            if (!$ingredient->track_stock) {
                continue;
            }

            // Skip ingrediente fără inventory item configurat
            if (!$ingredient->inventory_item_id || !$ingredient->inventoryItem) {
                continue;
            }

            // quantity_used din pivot (rețetă) × stock_quantity_per_unit × cantitate vândută
            $quantityUsed        = (float) ($ingredient->pivot->quantity_used ?? 1);
            $stockQtyPerUnit     = (float) ($ingredient->stock_quantity_per_unit ?? 1);
            $totalToDeduct       = $quantitySold * $quantityUsed * $stockQtyPerUnit;

            if ($totalToDeduct <= 0) {
                continue;
            }

            $this->deductFromItem(
                $ingredient->inventoryItem,
                $totalToDeduct,
                $order,
                $preventNegative
            );
        }
    }

    /**
     * Deduce cantitatea dintr-un InventoryItem și creează StockMovement.
     */
    protected function deductFromItem(
        InventoryItem $item,
        float $amount,
        Order $order,
        bool $preventNegative
    ): void {
        // Re-lock inventory item pentru siguranță în concurență
        $item = InventoryItem::lockForUpdate()->find($item->id);

        $stockBefore = (float) $item->current_stock;

        if ($preventNegative && ($stockBefore - $amount) < 0) {
            // Dacă prevent_negative_stock e activ, scadem doar cât avem
            Log::warning("Stoc insuficient pentru {$item->name}. Necesar: {$amount}, Disponibil: {$stockBefore}. Comanda: {$order->order_number}");
            $amount = $stockBefore;
        }

        $stockAfter = $stockBefore - $amount;

        $item->current_stock = $stockAfter;
        $item->save();

        StockMovement::create([
            'inventory_item_id' => $item->id,
            'type'              => StockMovement::TYPE_SALE,
            'quantity'          => -$amount,
            'stock_before'      => $stockBefore,
            'stock_after'       => $stockAfter,
            'note'              => "Comanda #{$order->order_number}",
            'reference_type'    => Order::class,
            'reference_id'      => $order->id,
        ]);
    }
}
