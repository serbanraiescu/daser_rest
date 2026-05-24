<?php

namespace App\Filament\Resources\Service\Pages;

use App\Filament\Resources\Service\ServiceOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceOrder extends CreateRecord
{
    protected static string $resource = ServiceOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Any custom pre-creation mutations can go here
        return $data;
    }
}
