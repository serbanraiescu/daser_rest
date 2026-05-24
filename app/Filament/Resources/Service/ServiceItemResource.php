<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ManageServiceItems;
use App\Modules\Service\Models\ServiceItem;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ServiceItemResource extends Resource
{
    protected static ?string $model = ServiceItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?string $navigationLabel = 'Servicii';
    protected static ?string $modelLabel = 'Serviciu';
    protected static ?string $pluralModelLabel = 'Servicii';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public static function form(Form $form): Form
    {
        $settings = CompanySetting::first();
        $units = $settings?->measurement_units ?? ['buc', 'set', 'oră', 'km'];

        return $form
            ->schema([
                Select::make('service_category_id')
                    ->label('Categorie Serviciu')
                    ->relationship('category', 'name')
                    ->required()
                    ->placeholder('Selectează o categorie'),
                TextInput::make('name')
                    ->label('Denumire Serviciu')
                    ->required()
                    ->maxLength(255),
                TextInput::make('price')
                    ->label('Preț')
                    ->required()
                    ->numeric()
                    ->prefix($settings?->currency ?? 'RON')
                    ->default(0.00),
                TextInput::make('unit')
                    ->label('Unitate de Măsură')
                    ->datalist($units)
                    ->placeholder('Alege sau scrie unitatea (ex: buc, set, oră, km)')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Ordine Sortare')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Activ')
                    ->required()
                    ->default(true),
                Textarea::make('description')
                    ->label('Descriere')
                    ->columnSpanFull()
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $settings = CompanySetting::first();
        $currency = $settings?->currency ?? 'RON';

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Denumire')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categorie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Preț')
                    ->money($currency)
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unitate'),
                ToggleColumn::make('is_active')
                    ->label('Activ'),
                TextColumn::make('sort_order')
                    ->label('Ordine')
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
            'index' => ManageServiceItems::route('/'),
        ];
    }
}
