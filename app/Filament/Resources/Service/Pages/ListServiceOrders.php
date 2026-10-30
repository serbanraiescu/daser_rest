<?php

namespace App\Filament\Resources\Service\Pages;

use App\Filament\Resources\Service\ServiceOrderResource;
use App\Filament\Resources\Service\ServiceInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceOrders extends ListRecords
{
    protected static string $resource = ServiceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('invoices')->label('Registru facturi')->icon('heroicon-o-document-text')
                ->url(ServiceInvoiceResource::getUrl()),
        ];
    }
}
