<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ManageInventorySnapshots;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventorySnapshot;
use App\Modules\Inventory\Models\InventorySnapshotItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventorySnapshotResource extends Resource
{
    protected static ?string $model = InventorySnapshot::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationGroup = 'Inventar';
    protected static ?string $navigationLabel = 'Inventare Lunare';
    protected static ?string $modelLabel = 'Inventar Lunar';
    protected static ?string $pluralModelLabel = 'Inventare Lunare';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detalii Inventar')
                    ->schema([
                        TextInput::make('name')
                            ->label('Denumire')
                            ->placeholder('Ex: Inventar Mai 2026')
                            ->required()
                            ->maxLength(255),
                        DatePicker::make('snapshot_date')
                            ->label('Data Inventarului')
                            ->required()
                            ->default(now()),
                        Textarea::make('notes')
                            ->label('Observații generale')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Denumire')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('snapshot_date')
                    ->label('Data')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Produse')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label('Creat')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('snapshot_date', 'desc')
            ->actions([
                // Generează snapshot-ul cu stocurile curente
                Action::make('generate')
                    ->label('Generează Stocuri')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generare Snapshot Stocuri')
                    ->modalDescription('Această acțiune va înregistra stocurile scriptice actuale pentru toate produsele active. Poți completa ulterior stocul fizic.')
                    ->action(function (InventorySnapshot $record): void {
                        DB::transaction(function () use ($record) {
                            // Șterge itemele vechi dacă se re-generează
                            $record->items()->delete();

                            $items = InventoryItem::where('is_active', true)
                                ->where('track_inventory', true)
                                ->get();

                            foreach ($items as $item) {
                                InventorySnapshotItem::create([
                                    'inventory_snapshot_id' => $record->id,
                                    'inventory_item_id'     => $item->id,
                                    'system_stock'          => $item->current_stock,
                                    'physical_stock'        => null,
                                    'difference'            => null,
                                    'observations'          => null,
                                ]);
                            }
                        });

                        Notification::make()
                            ->success()
                            ->title('Snapshot generat')
                            ->body('Stocurile scriptice au fost înregistrate. Completează stocul fizic și printează pentru inventar.')
                            ->send();
                    }),

                // Print A4
                Action::make('print')
                    ->label('Print A4')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (InventorySnapshot $record): string => route('inventory.print', $record))
                    ->openUrlInNewTab(),

                EditAction::make(),

                \Filament\Tables\Actions\DeleteAction::make(),
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
            'index' => ManageInventorySnapshots::route('/'),
        ];
    }
}
