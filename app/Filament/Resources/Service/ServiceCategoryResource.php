<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ManageServiceCategories;
use App\Modules\Service\Models\ServiceCategory;
use App\Modules\Settings\Models\CompanySetting;
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

class ServiceCategoryResource extends Resource
{
    protected static ?string $model = ServiceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?string $navigationLabel = 'Categorii servicii';
    protected static ?string $modelLabel = 'Categorie servicii';
    protected static ?string $pluralModelLabel = 'Categorii servicii';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Denumire Categorie')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Slug (Opțional)')
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descriere')
                    ->columnSpanFull()
                    ->rows(2),
                TextInput::make('sort_order')
                    ->label('Ordine Sortare')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Activă')
                    ->required()
                    ->default(true),
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
                TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Activă'),
                TextColumn::make('sort_order')
                    ->label('Ordine')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Adăugată la')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageServiceCategories::route('/'),
        ];
    }
}
