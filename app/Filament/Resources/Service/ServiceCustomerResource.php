<?php

namespace App\Filament\Resources\Service;

use App\Filament\Resources\Service\Pages\ManageServiceCustomers;
use App\Modules\Service\Models\ServiceCustomer;
use App\Modules\Service\Models\ServiceInvoice;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ServiceCustomerResource extends Resource
{
    protected static ?string $model = ServiceCustomer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Service Module';
    protected static ?string $navigationLabel = 'Clienți vulcanizare';
    protected static ?string $modelLabel = 'client vulcanizare';
    protected static ?string $pluralModelLabel = 'Clienți vulcanizare';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return (bool) (CompanySetting::first()?->enable_service_module ?? false);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess()
            && ! ServiceOrder::where('service_customer_id', $record->getKey())->exists()
            && ! ServiceInvoice::where('service_customer_id', $record->getKey())->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Nume / Denumire firmă')->required()->maxLength(255),
            TextInput::make('fiscal_code')->label('CUI / Cod fiscal')->maxLength(50),
            TextInput::make('phone')->label('Telefon')->tel()->maxLength(50),
            TextInput::make('email')->label('E-mail')->email()->maxLength(255),
            Textarea::make('address')->label('Adresă')->maxLength(1000)->columnSpanFull(),
            Toggle::make('is_active')->label('Activ')->default(true)->required()
                ->helperText('Clienții folosiți pe fișe sau facturi pot fi dezactivați; istoricul lor se păstrează.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Client')->searchable()->sortable(),
                TextColumn::make('fiscal_code')->label('CUI')->searchable(),
                TextColumn::make('phone')->label('Telefon')->searchable(),
                TextColumn::make('email')->label('E-mail')->toggleable(),
                IconColumn::make('is_active')->label('Activ')->boolean(),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Activ')])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServiceCustomers::route('/')];
    }
}
