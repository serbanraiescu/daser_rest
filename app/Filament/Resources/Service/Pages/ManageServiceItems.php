<?php

namespace App\Filament\Resources\Service\Pages;

use App\Filament\Resources\Service\ServiceItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceItems extends ManageRecords
{
    protected static string $resource = ServiceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
