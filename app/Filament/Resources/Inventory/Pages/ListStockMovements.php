<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Read-only
    }
}
