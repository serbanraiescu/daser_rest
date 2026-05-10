<?php

namespace App\Filament\Resources\Menu\Pages;

use App\Filament\Resources\Menu\IngredientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIngredients extends ManageRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
