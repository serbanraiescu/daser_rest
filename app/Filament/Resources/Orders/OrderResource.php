<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages;
use App\Modules\Orders\Models\Order;
use BackedEnum;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Forms\Form;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Restaurant Management';

    protected static ?string $recordTitleAttribute = 'order_number';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('order_number')
                    ->required()
                    ->maxLength(255)
                    ->disabled() // Auto-generated usually
                    ->dehydrated(false),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'preparing' => 'Preparing',
                        'ready' => 'Ready',
                        'delivered' => 'Delivered',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                TextInput::make('table_number')
                    ->maxLength(255),
                TextInput::make('total')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(), // Calculated from items
                TextInput::make('payment_method'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'preparing' => 'warning',
                        'ready' => 'success',
                        'delivered', 'paid' => 'primary',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('table_number')
                    ->sortable(),
                TextColumn::make('total')
                    ->prefix('$')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
