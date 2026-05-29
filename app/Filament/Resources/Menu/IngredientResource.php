<?php

namespace App\Filament\Resources\Menu;

use App\Filament\Resources\Menu\Pages\ManageIngredients;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\Ingredient;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Menu';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informații Ingredient')
                    ->schema([
                        TextInput::make('name')
                            ->label('Denumire')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('unit')
                            ->label('Unitate de măsură')
                            ->placeholder('g, ml, buc'),
                        Toggle::make('is_active')
                            ->label('Activ')
                            ->default(true),
                    ])->columns(3),

                Section::make('Urmărire Stoc Inventar')
                    ->description('Leagă acest ingredient de un produs din inventar pentru scădere automată la vânzare.')
                    ->schema([
                        Toggle::make('track_stock')
                            ->label('Urmărire stoc activă')
                            ->helperText('Activează pentru a lega ingredientul de inventar.')
                            ->live()
                            ->default(false),

                        Select::make('inventory_item_id')
                            ->label('Produs Inventar')
                            ->relationship('inventoryItem', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('track_stock'))
                            ->helperText('Selectează produsul din inventar care va fi scăzut.')
                            ->nullable(),

                        TextInput::make('stock_quantity_per_unit')
                            ->label('Cantitate scăzută per unitate rețetă')
                            ->numeric()
                            ->step(0.001)
                            ->minValue(0.001)
                            ->visible(fn (Get $get) => $get('track_stock'))
                            ->helperText('Ex: 0.200 = 200g per unitate ingredient din rețetă.')
                            ->nullable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ingredient')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unitate'),
                IconColumn::make('track_stock')
                    ->label('Stoc Activ')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('inventoryItem.name')
                    ->label('Produs Inventar')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('stock_quantity_per_unit')
                    ->label('Cant./Unit')
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label('Activ'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIngredients::route('/'),
        ];
    }
}
