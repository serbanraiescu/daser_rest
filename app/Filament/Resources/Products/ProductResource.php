<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Modules\Menu\Models\Product;
use App\Modules\Settings\Models\CompanySetting;
use BackedEnum;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Menu';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Product Tabs')
                    ->tabs([
                        // Tab 1: General Info
                        \Filament\Forms\Components\Tabs\Tab::make('General Info')
                            ->schema([
                                \Filament\Forms\Components\Grid::make(3)
                                    ->schema([
                                        Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2),
                                    ]),

                                \Filament\Forms\Components\Grid::make(4)
                                    ->schema([
                                        TextInput::make('price')
                                            ->required()
                                            ->numeric()
                                            ->prefix(function () {
                                                $settings = CompanySetting::first();
                                                return $settings ? $settings->currency : 'RON';
                                            }),
                                        Select::make('vat_rate')
                                            ->label('Cotă TVA')
                                            ->options(function () {
                                                $settings = CompanySetting::first();
                                                if (!$settings) {
                                                    return [];
                                                }

                                                $rates = $settings->vat_rates;
                                                
                                                // Robustness check: Ensure it's an array
                                                if (!is_array($rates)) {
                                                    if (is_string($rates)) {
                                                        // Try standard JSON decode
                                                        $decoded = json_decode($rates, true);
                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                            $rates = $decoded;
                                                        } elseif (str_contains($rates, ',')) {
                                                            // Fallback: Explode by comma (e.g. "19,9")
                                                            $rates = explode(',', $rates);
                                                        } else {
                                                            // Single value string
                                                            $rates = [$rates];
                                                        }
                                                    } else {
                                                        $rates = (array)$rates;
                                                    }
                                                }
                                                
                                                if (empty($rates)) {
                                                    return [];
                                                }

                                                $options = [];
                                                foreach ($rates as $rate) {
                                                    $val = trim((string)$rate);
                                                    if ($val !== '') {
                                                        $options[$val] = "$val%";
                                                    }
                                                }
                                                return $options;
                                            }),
                                        TextInput::make('measurement_value')
                                            ->label('Cantitate')
                                            ->numeric(),
                                        Select::make('measurement_unit')
                                            ->label('Unitate')
                                            ->options(function () {
                                                $defaultUnits = [
                                                    'g' => 'Grame (g)',
                                                    'kg' => 'Kilograme (kg)',
                                                    'ml' => 'Mililitri (ml)',
                                                    'l' => 'Litri (l)',
                                                    'buc' => 'Bucăți (buc)',
                                                    'portie' => 'Porție',
                                                ];

                                                $settings = CompanySetting::first();
                                                if (!$settings) {
                                                    return $defaultUnits;
                                                }

                                                $units = $settings->measurement_units;

                                                // Robustness check
                                                if (empty($units)) {
                                                    return $defaultUnits;
                                                }

                                                if (!is_array($units)) {
                                                     if (is_string($units)) {
                                                        $decoded = json_decode($units, true);
                                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                            $units = $decoded;
                                                        } elseif (str_contains($units, ',')) {
                                                            $units = explode(',', $units);
                                                        } else {
                                                            $units = [$units];
                                                        }
                                                     } else {
                                                         $units = (array)$units;
                                                     }
                                                }
                                                
                                                if (empty($units)) {
                                                    return $defaultUnits;
                                                }

                                                $options = [];
                                                foreach ($units as $unit) {
                                                    $val = trim((string)$unit);
                                                    if ($val !== '') {
                                                        $options[$val] = $val;
                                                    }
                                                }
                                                return $options;
                                            }),
                                    ]),
                                Textarea::make('description')
                                    ->columnSpanFull(),
                                FileUpload::make('image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->columnSpanFull(),
                                
                                Section::make('Settings')
                                    ->schema([
                                        Toggle::make('is_active')->default(true),
                                        Toggle::make('is_available')->default(true),
                                        TextInput::make('sort_order')->numeric()->default(0),
                                        Toggle::make('has_variations')
                                            ->live()
                                            ->default(false),
                                    ])->columns(4),

                                Repeater::make('variations')
                                    ->relationship()
                                    ->schema([
                                        TextInput::make('name')->required(),
                                        TextInput::make('price')->numeric()->default(0),
                                        TextInput::make('stock')->numeric(),
                                    ])
                                    ->visible(fn ($get) => $get('has_variations'))
                                    ->columnSpanFull(),
                            ]),

                        // Tab 2: Ingredients
                        \Filament\Forms\Components\Tabs\Tab::make('Recipe & Ingredients')
                            ->schema([
                                Select::make('ingredients')
                                    ->label('Ingrediente')
                                    ->relationship('ingredients', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                            ]),

                        // Tab 3: Nutritional Values & Allergens
                        \Filament\Forms\Components\Tabs\Tab::make('Nutritional Values & Allergens')
                            ->schema([
                                Section::make('Valori Nutriționale (per 100g)')
                                    ->description('Introduceți valorile medii.')
                                    ->schema([
                                        TextInput::make('nutritional_data.calories')->label('Calorii (kcal)')->numeric(),
                                        TextInput::make('nutritional_data.fats')->label('Grăsimi (g)')->numeric(),
                                        TextInput::make('nutritional_data.carbs')->label('Carbohidrați (g)')->numeric(),
                                        TextInput::make('nutritional_data.protein')->label('Proteine (g)')->numeric(),
                                        TextInput::make('nutritional_data.salt')->label('Sare (g)')->numeric(),
                                    ])->columns(3),
                                
                                Section::make('Alergeni & Caracteristici Speciale')
                                    ->schema([
                                        Select::make('allergenRelations')
                                            ->label('Alergeni (Sistem Nou)')
                                            ->relationship('allergenRelations', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),

                                        Toggle::make('is_frozen')
                                            ->label('Produs Congelat / Decongelat')
                                            ->live(),

                                        TextInput::make('frozen_note')
                                            ->label('Notă Produs Congelat')
                                            ->placeholder('Ex: Produs provenit din produs congelat.')
                                            ->visible(fn (Get $get) => $get('is_frozen'))
                                            ->columnSpanFull(),

                                        Section::make('Legacy Data')
                                            ->collapsed()
                                            ->schema([
                                                Textarea::make('allergens')
                                                    ->label('Alergeni (Vechi / Fallback)')
                                                    ->placeholder('Ex: Gluten, Ouă, Lapte...')
                                                    ->rows(2)
                                                    ->readOnly()
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                ImageColumn::make('image'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->sortable(),
                TextColumn::make('price')
                    ->prefix('$')
                    ->numeric(2)
                    ->sortable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active'),
                \Filament\Tables\Columns\ToggleColumn::make('is_available'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
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
            'index' => ManageProducts::route('/'),
        ];
    }
}
