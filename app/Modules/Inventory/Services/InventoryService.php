<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Procesează o intrare de marfă.
     *
     * @param  int         $inventoryItemId
     * @param  float       $quantity         Cantitatea primită (pozitivă)
     * @param  string|null $note             Notă / furnizor
     * @param  int|null    $createdBy        ID-ul utilizatorului care face intrarea
     * @return StockMovement
     */
    public function receiveStock(
        int $inventoryItemId,
        float $quantity,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Cantitatea pentru intrare trebuie să fie pozitivă.');
        }

        return DB::transaction(function () use ($inventoryItemId, $quantity, $note, $createdBy) {
            /** @var InventoryItem $item */
            $item = InventoryItem::lockForUpdate()->findOrFail($inventoryItemId);

            $stockBefore = (float) $item->current_stock;
            $stockAfter  = $stockBefore + $quantity;

            $item->current_stock = $stockAfter;
            $item->save();

            return StockMovement::create([
                'inventory_item_id' => $item->id,
                'type'              => StockMovement::TYPE_IN,
                'quantity'          => $quantity,
                'stock_before'      => $stockBefore,
                'stock_after'       => $stockAfter,
                'note'              => $note,
                'created_by'        => $createdBy,
            ]);
        });
    }

    /**
     * Ajustează stocul la o valoare reală (din inventar fizic).
     *
     * @param  int         $inventoryItemId
     * @param  float       $newRealStock     Stocul real fizic numărat
     * @param  string|null $reason           Motiv ajustare
     * @param  int|null    $createdBy
     * @return StockMovement
     */
    public function adjustStock(
        int $inventoryItemId,
        float $newRealStock,
        ?string $reason = null,
        ?int $createdBy = null
    ): StockMovement {
        return DB::transaction(function () use ($inventoryItemId, $newRealStock, $reason, $createdBy) {
            /** @var InventoryItem $item */
            $item = InventoryItem::lockForUpdate()->findOrFail($inventoryItemId);

            $stockBefore = (float) $item->current_stock;
            $difference  = $newRealStock - $stockBefore;

            $item->current_stock = $newRealStock;
            $item->save();

            return StockMovement::create([
                'inventory_item_id' => $item->id,
                'type'              => StockMovement::TYPE_ADJUSTMENT,
                'quantity'          => $difference, // pozitiv = surplus, negativ = lipsă
                'stock_before'      => $stockBefore,
                'stock_after'       => $newRealStock,
                'note'              => $reason,
                'created_by'        => $createdBy,
            ]);
        });
    }

    /**
     * Înregistrează o pierdere / deșeu manual.
     */
    public function recordWaste(
        int $inventoryItemId,
        float $quantity,
        ?string $note = null,
        ?int $createdBy = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Cantitatea pentru pierdere trebuie să fie pozitivă.');
        }

        return DB::transaction(function () use ($inventoryItemId, $quantity, $note, $createdBy) {
            /** @var InventoryItem $item */
            $item = InventoryItem::lockForUpdate()->findOrFail($inventoryItemId);

            $stockBefore = (float) $item->current_stock;
            $stockAfter  = max(0, $stockBefore - $quantity);

            $item->current_stock = $stockAfter;
            $item->save();

            return StockMovement::create([
                'inventory_item_id' => $item->id,
                'type'              => StockMovement::TYPE_WASTE,
                'quantity'          => -$quantity,
                'stock_before'      => $stockBefore,
                'stock_after'       => $stockAfter,
                'note'              => $note,
                'created_by'        => $createdBy,
            ]);
        });
    }
}
