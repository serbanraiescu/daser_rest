<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ListServiceInvoices;
use App\Filament\Resources\Service\Pages\ViewServiceInvoice;
use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceInvoiceResource extends Resource
{
    protected static ?string $model = ServiceInvoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?string $navigationLabel = 'Facturi vulcanizare';
    protected static ?string $modelLabel = 'factură înregistrată';
    protected static ?string $pluralModelLabel = 'Facturi vulcanizare';
    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $currency = CompanySetting::first()?->currency ?? 'RON';

        return $infolist->schema([
            Section::make('Factura emisă extern')
                ->description('Aceasta este evidența facturării. Înregistrarea facturii nu confirmă încasarea ei.')
                ->schema([
                    TextEntry::make('number')->label('Număr factură'),
                    TextEntry::make('issued_on')->label('Data facturii')->date('d.m.Y'),
                    TextEntry::make('customer_name')->label('Client'),
                    TextEntry::make('customer_fiscal_code')->label('CUI')->placeholder('—'),
                    TextEntry::make('amount')->label('Suma facturată')->money($currency),
                    TextEntry::make('orders_total')->label('Total fișe asociate')->money($currency),
                    TextEntry::make('recorded_at')->label('Înregistrată la')->dateTime('d.m.Y H:i')->timezone(config('service_reporting.timezone')),
                    TextEntry::make('createdBy.name')->label('Înregistrată de')->placeholder('—'),
                ])->columns(2),
            Section::make('Fișe incluse')->schema([
                RepeatableEntry::make('orders')->label('')->schema([
                    TextEntry::make('id')->label('Fișa'),
                    TextEntry::make('vehicle_number')->label('Mașină')->placeholder('—'),
                    TextEntry::make('completed_at')->label('Finalizată la')->dateTime('d.m.Y H:i')->timezone(config('service_reporting.timezone')),
                    TextEntry::make('total')->label('Total')->money($currency),
                ])->columns(4),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currency = CompanySetting::first()?->currency ?? 'RON';

        return $table->defaultSort('recorded_at', 'desc')
            ->columns([
                TextColumn::make('number')->label('Număr factură')->searchable()->sortable(),
                TextColumn::make('issued_on')->label('Data facturii')->date('d.m.Y')->sortable(),
                TextColumn::make('customer_name')->label('Client')->searchable(),
                TextColumn::make('amount')->label('Suma facturată')->money($currency)->sortable(),
                TextColumn::make('orders_total')->label('Total fișe')->money($currency),
                TextColumn::make('orders_count')->label('Fișe')->counts('orders'),
                TextColumn::make('recorded_at')->label('Înregistrată la')->dateTime('d.m.Y H:i')->timezone(config('service_reporting.timezone'))->toggleable(),
            ])
            ->filters([
                SelectFilter::make('service_customer_id')->label('Client')->relationship('customer', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceInvoices::route('/'),
            'view' => ViewServiceInvoice::route('/{record}'),
        ];
    }
}
