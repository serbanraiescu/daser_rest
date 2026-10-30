<?php

namespace App\Filament\Resources\Service\Pages;

use App\Filament\Resources\Service\ServiceInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceInvoices extends ListRecords
{
    protected static string $resource = ServiceInvoiceResource::class;
}
