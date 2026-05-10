<?php

namespace App\Filament\Resources\Tables;

use App\Filament\Resources\Tables\TableResource\Pages;
use App\Modules\Tables\Models\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table as FilamentTable;
use Filament\Tables;

class TableResource extends Resource
{
    protected static ?string $model = Table::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Restaurant Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('seats')
                    ->numeric()
                    ->default(4)
                    ->required(),
                Forms\Components\Select::make('area_id')
                    ->relationship('area', 'name')
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                    ]),
                Forms\Components\Select::make('shape')
                    ->options([
                        'round' => 'Round',
                        'square' => 'Square',
                        'rectangle' => 'Rectangle',
                    ])
                    ->default('square')
                    ->required(),
                
                Forms\Components\Section::make('Map Positioning')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('x')->numeric()->default(0),
                                Forms\Components\TextInput::make('y')->numeric()->default(0),
                                Forms\Components\TextInput::make('width')->numeric()->default(80),
                                Forms\Components\TextInput::make('height')->numeric()->default(80),
                            ]),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(FilamentTable $table): FilamentTable
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('seats')->sortable(),
                Tables\Columns\TextColumn::make('area.name')->sortable(),
                Tables\Columns\TextColumn::make('shape'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area')
                    ->relationship('area', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTables::route('/'),
        ];
    }
}
