<?php

namespace App\Filament\Resources\Service\Pages;

use App\Filament\Resources\Service\ServiceCustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceCustomers extends ManageRecords
{
    protected static string $resource = ServiceCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
