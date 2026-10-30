<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ListServiceOrders;
use App\Filament\Resources\Service\Pages\CreateServiceOrder;
use App\Filament\Resources\Service\Pages\EditServiceOrder;
use App\Filament\Resources\Service\Pages\ViewServiceOrder;
use App\Filament\Resources\Service\Actions\RecordServiceInvoice;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess() && ! $record->hasLockedAccount();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && ! $record->hasLockedAccount();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccess();
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
                        ->rules([fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                            if ($value === 'completed' && $get('payment_method') === 'client_account') {
                                $fail('Finalizați fișele pe cont client din terminalul Vulcanizare. Aici le puteți salva deschise.');
                            }
                        }])
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            if ($state === 'completed') {
                                $set('completed_at', now()->toDateTimeString());
                                $set('payment_status', $get('payment_method') === 'client_account' ? 'unpaid' : 'paid');
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
                        ->default('unpaid')
                        ->disabled(fn (Get $get): bool => $get('payment_method') === 'client_account')
                        ->dehydrated(),
                    Select::make('payment_method')
                        ->label('Metodă Plată')
                        ->options([
                            'cash' => 'Cash / Numerar',
                            'card' => 'Card',
                            'mixed' => 'Mixtă',
                            'protocol' => 'Încasare Protocol / Protocol',
                            'client_account' => 'Pe cont client',
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'client_account') {
                                $set('payment_status', 'unpaid');
                            } else {
                                $set('service_customer_id', null);
                            }
                        })
                        ->nullable(),
                ]),

                Select::make('service_customer_id')
                    ->label('Client pentru facturare')
                    ->relationship('customer', 'name', fn (Builder $query) => $query->where('is_active', true))
                    ->searchable()->preload()
                    ->visible(fn (Get $get): bool => $get('payment_method') === 'client_account')
                    ->required(fn (Get $get): bool => $get('payment_method') === 'client_account')
                    ->rules([Rule::exists('service_customers', 'id')->where('is_active', true)])
                    ->helperText('Finalizarea pe cont client se face din terminalul operatorului. Fișele finalizate sunt păstrate în evidență și nu mai pot fi editate sau șterse.'),

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
                                    $set('unit', $item->unit);
                                }
                            })
                            ->columnSpan(2),
                        Hidden::make('unit'),
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
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'cash' => 'Numerar', 'card' => 'Card', 'mixed' => 'Mixtă',
                        'protocol' => 'Protocol', 'client_account' => 'Pe cont client', default => '—',
                    })
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('invoice.number')
                    ->label('Factură')
                    ->searchable()
                    ->placeholder(fn (ServiceOrder $record): string => $record->status === 'completed' && $record->payment_method === 'client_account' ? 'De facturat' : '—')
                    ->url(fn (ServiceOrder $record): ?string => $record->service_invoice_id
                        ? ServiceInvoiceResource::getUrl('view', ['record' => $record->service_invoice_id]) : null),
                TextColumn::make('completed_at')
                    ->label('Finalizată la')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('uninvoiced')->label('De facturat')->query(fn (Builder $query): Builder => $query->uninvoiced()),
                Filter::make('invoiced')->label('Facturate')->query(fn (Builder $query): Builder => $query->whereNotNull('service_invoice_id')),
                SelectFilter::make('service_customer_id')->label('Client')->relationship('customer', 'name')->searchable()->preload(),
            ])
            ->actions([
                ViewAction::make(),
                RecordServiceInvoice::single(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    RecordServiceInvoice::bulk(),
                    DeleteBulkAction::make()->before(function (Collection $records, DeleteBulkAction $action): void {
                        abort_unless(static::canAccess(), 403);
                        if ($records->contains(fn (ServiceOrder $record): bool => ! static::canDelete($record))) {
                            Notification::make()->title('Fișele finalizate pe cont client nu pot fi șterse.')->danger()->send();
                            $action->halt();
                        }
                    }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceOrders::route('/'),
            'create' => CreateServiceOrder::route('/create'),
            'edit' => EditServiceOrder::route('/{record}/edit'),
            'view' => ViewServiceOrder::route('/{record}'),
        ];
    }
}
