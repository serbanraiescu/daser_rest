<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListStockMovements;
use App\Modules\Inventory\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Form;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Inventar';
    protected static ?string $navigationLabel = 'Mișcări Stoc';
    protected static ?string $modelLabel = 'Mișcare Stoc';
    protected static ?string $pluralModelLabel = 'Mișcări Stoc';
    protected static ?int $navigationSort = 2;

    // Read-only — dezactivăm toate acțiunile de creare/editare
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('inventoryItem.name')
                    ->label('Produs Inventar')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\BadgeColumn::make('type')
                    ->label('Tip')
                    ->formatStateUsing(fn (string $state): string => StockMovement::TYPES[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'in'         => 'success',
                        'sale'       => 'warning',
                        'out'        => 'warning',
                        'adjustment' => 'info',
                        'waste'      => 'danger',
                        default      => 'gray',
                    }),
                TextColumn::make('quantity')
                    ->label('Cantitate')
                    ->numeric(3)
                    ->color(fn (StockMovement $record): string => (float) $record->quantity >= 0 ? 'success' : 'danger'),
                TextColumn::make('stock_before')
                    ->label('Stoc Înainte')
                    ->numeric(3)
                    ->color('gray'),
                TextColumn::make('stock_after')
                    ->label('Stoc După')
                    ->numeric(3)
                    ->weight('bold'),
                TextColumn::make('note')
                    ->label('Notă')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tip Mișcare')
                    ->options(StockMovement::TYPES),
                SelectFilter::make('inventory_item_id')
                    ->label('Produs Inventar')
                    ->relationship('inventoryItem', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // Vizualizare only — fără editare
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
