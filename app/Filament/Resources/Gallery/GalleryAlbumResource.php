<?php

namespace App\Filament\Resources\Gallery;

use App\Filament\Resources\Gallery\Pages\ManageGalleryAlbums;
use App\Modules\Gallery\Models\GalleryAlbum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GalleryAlbumResource extends Resource
{
    protected static ?string $model = GalleryAlbum::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Conținut';
    protected static ?string $navigationLabel = 'Galerii & Evenimente';
    protected static ?string $modelLabel = 'album';
    protected static ?string $pluralModelLabel = 'galerii și evenimente';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Titlu album / eveniment')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->helperText('Se generează automat din titlu dacă rămâne gol.')
                ->maxLength(255),
            DatePicker::make('event_date')->label('Data evenimentului'),
            TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
            Textarea::make('description')->label('Descriere')->columnSpanFull(),
            Toggle::make('show_on_homepage')->label('Afișează pe prima pagină')->default(false),
            Toggle::make('is_active')->label('Album activ')->default(true),
            Repeater::make('images')
                ->relationship()
                ->label('Fotografii')
                ->schema([
                    FileUpload::make('image')
                        ->label('Imagine')
                        ->image()
                        ->disk('public')
                        ->directory('gallery/events')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('caption')->label('Descriere scurtă'),
                    TextInput::make('alt_text')->label('Text alternativ (SEO/accesibilitate)'),
                    TextInput::make('sort_order')->label('Ordine')->numeric()->default(0),
                ])
                ->columns(3)
                ->orderColumn('sort_order')
                ->reorderable()
                ->collapsible()
                ->addActionLabel('Adaugă fotografie')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images.image')->label('Copertă')->disk('public')->limit(1),
                TextColumn::make('title')->label('Titlu')->searchable()->sortable(),
                TextColumn::make('event_date')->label('Data')->date('d.m.Y')->sortable(),
                TextColumn::make('images_count')->counts('images')->label('Fotografii'),
                IconColumn::make('show_on_homepage')->label('Prima pagină')->boolean(),
                IconColumn::make('is_active')->label('Activ')->boolean(),
                TextColumn::make('sort_order')->label('Ordine')->sortable(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGalleryAlbums::route('/')];
    }
}
