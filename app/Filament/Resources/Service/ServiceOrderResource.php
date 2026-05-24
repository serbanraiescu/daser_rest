<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ListServiceOrders;
use App\Filament\Resources\Service\Pages\CreateServiceOrder;
use App\Filament\Resources\Service\Pages\EditServiceOrder;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ServiceOrderResource extends Resource
{
    protected static ?string $model = ServiceOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?string $navigationLabel = 'Comenzi servicii';
    protected static ?string $modelLabel = 'Comandă servicii';
    protected static ?string $pluralModelLabel = 'Comenzi servicii';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public static function form(Form $form): Form
    {
        $settings = CompanySetting::first();
        $currency = $settings?->currency ?? 'RON';

        return $form
            ->schema([
                Grid::make(3)->schema([
                    Select::make('staff_member_id')
                        ->label('Operator / Angajat')
                        ->relationship('staff', 'name')
                        ->placeholder('Alege angajatul')
                        ->required(),
                    TextInput::make('vehicle_number')
                        ->label('Număr Înmatriculare / Mașină')
                        ->placeholder('ex: B-123-ABC')
                        ->maxLength(50),
                    DateTimePicker::make('completed_at')
                        ->label('Finalizată la'),
                ]),

                Grid::make(3)->schema([
                    TextInput::make('customer_name')
                        ->label('Nume Client')
                        ->maxLength(255),
                    TextInput::make('customer_phone')
                        ->label('Telefon Client')
                        ->tel()
                        ->maxLength(50),
                    TextInput::make('total')
                        ->label('Total General')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix($currency),
                ]),

                Grid::make(3)->schema([
                    Select::make('status')
                        ->label('Stare Comandă')
                        ->options([
                            'open' => 'Deschisă',
                            'completed' => 'Finalizată',
                            'cancelled' => 'Anulată',
                        ])
                        ->required()
                        ->default('open')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'completed') {
                                $set('completed_at', now()->toDateTimeString());
                                $set('payment_status', 'paid');
                            }
                        }),
                    Select::make('payment_status')
                        ->label('Stare Plată')
                        ->options([
                            'unpaid' => 'Neachitată',
                            'paid' => 'Achitată',
                            'partial' => 'Parțială',
                        ])
                        ->required()
                        ->default('unpaid'),
                    Select::make('payment_method')
                        ->label('Metodă Plată')
                        ->options([
                            'cash' => 'Cash / Numerar',
                            'card' => 'Card',
                            'mixed' => 'Mixtă',
                        ])
                        ->nullable(),
                ]),

                Textarea::make('notes')
                    ->label('Notițe / Observații')
                    ->columnSpanFull()
                    ->rows(2),

                Repeater::make('items')
                    ->label('Servicii Comandate')
                    ->relationship('items')
                    ->schema([
                        Select::make('service_item_id')
                            ->label('Serviciu')
                            ->options(ServiceItem::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $item = ServiceItem::find($state);
                                if ($item) {
                                    $set('name', $item->name);
                                    $set('unit_price', $item->price);
                                }
                            })
                            ->columnSpan(2),
                        TextInput::make('name')
                            ->label('Denumire Snapshot')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('quantity')
                            ->label('Cantitate')
                            ->numeric()
                            ->required()
                            ->default(1.00)
                            ->columnSpan(1),
                        TextInput::make('unit_price')
                            ->label('Preț Unitar')
                            ->numeric()
                            ->required()
                            ->default(0.00)
                            ->prefix($currency)
                            ->columnSpan(1),
                        TextInput::make('notes')
                            ->label('Notă specială')
                            ->columnSpan(2),
                    ])
                    ->columns(8)
                    ->columnSpanFull()
                    ->defaultItems(1)
                    ->createItemButtonLabel('Adaugă Serviciu în Comandă'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $settings = CompanySetting::first();
        $currency = $settings?->currency ?? 'RON';

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('vehicle_number')
                    ->label('Număr Mașină')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('customer_name')
                    ->label('Client')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('staff.name')
                    ->label('Angajat / Operator')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money($currency)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Stare')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Stare Plată')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'paid' => 'success',
                        'partial' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Metodă Plată')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Finalizată la')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceOrders::route('/'),
            'create' => CreateServiceOrder::route('/create'),
            'edit' => EditServiceOrder::route('/{record}/edit'),
        ];
    }
}
