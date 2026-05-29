<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ManageInventoryItems;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Services\InventoryService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventar';
    protected static ?string $navigationLabel = 'Produse Inventar';
    protected static ?string $modelLabel = 'Produs Inventar';
    protected static ?string $pluralModelLabel = 'Produse Inventar';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informatii Produs')
                    ->schema([
                        TextInput::make('name')
                            ->label('Denumire')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label('SKU / Cod Intern')
                            ->nullable()
                            ->maxLength(100),
                        Select::make('unit')
                            ->label('Unitate de Masura')
                            ->options([
                                'buc' => 'Bucati (buc)',
                                'kg'  => 'Kilograme (kg)',
                                'g'   => 'Grame (g)',
                                'l'   => 'Litri (l)',
                                'ml'  => 'Mililitri (ml)',
                            ])
                            ->required()
                            ->default('buc'),
                    ])->columns(3),

                Section::make('Stoc')
                    ->schema([
                        TextInput::make('current_stock')
                            ->label('Stoc Curent')
                            ->numeric()
                            ->step(0.001)
                            ->default(0)
                            ->required(),
                        TextInput::make('minimum_stock')
                            ->label('Stoc Minim (alerta)')
                            ->numeric()
                            ->step(0.001)
                            ->nullable()
                            ->helperText('Daca stocul scade sub aceasta valoare, apare badge-ul "Stoc scazut".'),
                        Toggle::make('track_inventory')
                            ->label('Urmarire Stoc Activa')
                            ->default(true),
                        Toggle::make('is_active')
                            ->label('Produs Activ')
                            ->default(true),
                    ])->columns(4),

                Section::make('Observatii')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Note interne')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Produs')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unit')
                    ->label('Unitate')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('current_stock')
                    ->label('Stoc Curent')
                    ->numeric(3)
                    ->sortable()
                    ->color(function (InventoryItem $record): string {
                        if ($record->minimum_stock !== null && (float) $record->current_stock <= (float) $record->minimum_stock) {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->weight('bold'),
                TextColumn::make('minimum_stock')
                    ->label('Stoc Minim')
                    ->numeric(3)
                    ->placeholder('-')
                    ->color('gray'),
                \Filament\Tables\Columns\BadgeColumn::make('stock_status')
                    ->label('Status')
                    ->getStateUsing(function (InventoryItem $record): string {
                        if ($record->minimum_stock !== null && (float) $record->current_stock <= (float) $record->minimum_stock) {
                            return 'Stoc Scazut';
                        }
                        return 'OK';
                    })
                    ->color(function (string $state): string {
                        return $state === 'Stoc Scazut' ? 'danger' : 'success';
                    }),
                ToggleColumn::make('is_active')
                    ->label('Activ'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('unit')
                    ->label('Unitate')
                    ->options([
                        'buc' => 'Bucati',
                        'kg'  => 'Kilograme',
                        'g'   => 'Grame',
                        'l'   => 'Litri',
                        'ml'  => 'Mililitri',
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Activi')
                    ->falseLabel('Inactivi'),
            ])
            ->actions([
                // Actiune: Intrare Marfa
                Action::make('receive_stock')
                    ->label('Intrare Stoc')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        TextInput::make('quantity')
                            ->label('Cantitate primita')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0.001)
                            ->required(),
                        TextInput::make('note')
                            ->label('Furnizor / Nota')
                            ->nullable(),
                        TextInput::make('purchase_price')
                            ->label('Pret achizitie (optional)')
                            ->numeric()
                            ->nullable()
                            ->prefix('RON'),
                    ])
                    ->action(function (InventoryItem $record, array $data): void {
                        $note = $data['note'] ?? '';
                        if (!empty($data['purchase_price'])) {
                            $note .= ' | Pret: ' . $data['purchase_price'] . ' RON';
                        }

                        app(InventoryService::class)->receiveStock(
                            $record->id,
                            (float) $data['quantity'],
                            $note ?: null,
                            Auth::id()
                        );

                        Notification::make()
                            ->success()
                            ->title('Stoc actualizat')
                            ->body('S-au adaugat ' . $data['quantity'] . ' ' . $record->unit . ' la ' . $record->name . '.')
                            ->send();
                    })
                    ->modalHeading('Intrare Marfa'),

                // Actiune: Ajustare Stoc (inventar fizic)
                Action::make('adjust_stock')
                    ->label('Ajustare')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form(function (InventoryItem $record): array {
                        return [
                            Placeholder::make('current')
                                ->label('Stoc scriptic actual')
                                ->content(number_format((float) $record->current_stock, 3) . ' ' . $record->unit),
                            TextInput::make('new_stock')
                                ->label('Stoc real fizic numarat')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0)
                                ->required(),
                            Textarea::make('reason')
                                ->label('Motiv ajustare')
                                ->rows(2)
                                ->nullable(),
                        ];
                    })
                    ->action(function (InventoryItem $record, array $data): void {
                        app(InventoryService::class)->adjustStock(
                            $record->id,
                            (float) $data['new_stock'],
                            $data['reason'] ?? null,
                            Auth::id()
                        );

                        Notification::make()
                            ->success()
                            ->title('Stoc ajustat')
                            ->body('Stocul pentru ' . $record->name . ' a fost actualizat la ' . $data['new_stock'] . ' ' . $record->unit . '.')
                            ->send();
                    })
                    ->modalHeading('Ajustare Inventar'),

                EditAction::make(),
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
            'index' => ManageInventoryItems::route('/'),
        ];
    }
}
