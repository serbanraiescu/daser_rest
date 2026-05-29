<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\InventorySnapshotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInventorySnapshots extends ManageRecords
{
    protected static string $resource = InventorySnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Inventar Lunar Nou'),
        ];
    }
}
