<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Service\Actions\RecordServiceInvoice;
use App\Filament\Resources\Service\ServiceInvoiceResource;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UninvoicedServiceOrders extends TableWidget
{
    protected static ?int $sort = -10;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check() && (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public function table(Table $table): Table
    {
        $currency = CompanySetting::first()?->currency ?? 'RON';

        return $table
            ->query(ServiceOrder::query()->uninvoiced()->with(['customer', 'staff'])
                ->when(! static::canView(), fn ($query) => $query->whereRaw('1 = 0')))
            ->heading('Vulcanizare — de facturat')
            ->description('Fișe finalizate pe cont client, încă nefacturate. Selectează fișele aceluiași client pentru o singură factură.')
            ->defaultSort('completed_at')
            ->columns([
                TextColumn::make('id')->label('Fișa')->sortable(),
                TextColumn::make('customer_name')->label('Client')->searchable()->sortable(),
                TextColumn::make('vehicle_number')->label('Mașină')->searchable()->placeholder('—'),
                TextColumn::make('completed_at')->label('Finalizată la')->dateTime('d.m.Y H:i')->timezone(config('service_reporting.timezone'))->sortable(),
                TextColumn::make('total')->label('De facturat')->money($currency)->sortable()
                    ->summarize(Sum::make()->label('Total de facturat')->money($currency)),
                TextColumn::make('staff.name')->label('Operator')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service_customer_id')->label('Client')->relationship('customer', 'name')->searchable()->preload(),
            ])
            ->actions([RecordServiceInvoice::single()])
            ->bulkActions([RecordServiceInvoice::bulk()])
            ->headerActions([
                Action::make('invoices')->label('Registru facturi')->icon('heroicon-o-document-text')
                    ->url(ServiceInvoiceResource::getUrl()),
            ])
            ->emptyStateHeading('Nu sunt fișe de facturat')
            ->emptyStateDescription('Fișele finalizate cu „Pe cont client” vor apărea aici.')
            ->paginated([10, 25, 50]);
    }
}
