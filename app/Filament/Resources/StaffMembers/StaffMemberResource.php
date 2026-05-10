<?php

namespace App\Filament\Resources\StaffMembers;

use App\Filament\Resources\StaffMembers\Pages\ManageStaffMembers;
use App\Modules\Staff\Models\StaffMember;
use BackedEnum;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Forms\Form;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;



class StaffMemberResource extends Resource
{
    protected static ?string $model = StaffMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    // protected static ?string $navigationGroup = 'Staff';

    protected static ?string $recordTitleAttribute = 'name';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('pin_code')
                    ->password()
                    ->label('PIN Code')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->length(4)
                    ->numeric(),
                Select::make('role')
                    ->options([
                        'waiter' => 'Waiter',
                        'kitchen' => 'Kitchen',
                        'bar' => 'Bar',
                        'manager' => 'Manager',
                    ])
                    ->required()
                    ->default('waiter'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'manager' => 'danger',
                        'kitchen', 'bar' => 'warning',
                        'waiter' => 'success',
                    }),
                ToggleColumn::make('is_active'),
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
            'index' => ManageStaffMembers::route('/'),
        ];
    }
}
