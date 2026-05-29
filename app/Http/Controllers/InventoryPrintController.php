<?php

namespace App\Http\Controllers;

use App\Modules\Inventory\Models\InventorySnapshot;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\Http\Request;

class InventoryPrintController extends Controller
{
    /**
     * Afișează inventarul A4 pentru print — cu stocuri din snapshot.
     */
    public function show(InventorySnapshot $snapshot)
    {
        $snapshot->load([
            'items.inventoryItem',
        ]);

        // Sortare alfabetică după numele produsului
        $items = $snapshot->items->sortBy('inventoryItem.name');

        $settings    = CompanySetting::first();
        $companyName = $settings?->site_name ?? 'Restaurant OS';

        return view('inventory.print-inventory', compact('snapshot', 'items', 'companyName'));
    }
}
