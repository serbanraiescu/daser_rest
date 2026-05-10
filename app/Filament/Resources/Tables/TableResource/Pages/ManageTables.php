<?php

namespace App\Filament\Resources\Tables\TableResource\Pages;

use App\Filament\Resources\Tables\TableResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTables extends ManageRecords
{
    protected static string $resource = TableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
