<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Modules\Tables\Models\Area;
use App\Modules\Tables\Models\Table;
use Filament\Actions\Action;

class TableMapPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Floor Plan';
    
    // protected static ?string $navigationGroup = 'Restaurant Management';

    protected static string $view = 'filament.pages.table-map-page';
    
    public $activeAreaId;

    public function mount()
    {
        $firstArea = Area::first();
        if($firstArea) {
            $this->activeAreaId = $firstArea->id;
        }
    }

    public function createAreaAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\CreateAction::make('createArea')
            ->label('+ New Area')
            ->modalWidth('sm')
            ->model(Area::class)
            ->form([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nume Zonă')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                \Filament\Forms\Components\Hidden::make('color')->default('#3b82f6'),
                \Filament\Forms\Components\Hidden::make('width')->default(1000),
                \Filament\Forms\Components\Hidden::make('height')->default(1000),
            ])
            ->after(function ($record) {
                $this->activeAreaId = $record->id;
                // Force a full re-render of the component to update the tab list
                $this->dispatch('$refresh'); 
            });
    }

    public function editAreaAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\EditAction::make('editArea')
            ->label('Edit Area')
            ->icon('heroicon-m-pencil-square')
            ->record(fn () => Area::find($this->activeAreaId))
            ->modalWidth('sm')
            ->form([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nume Zonă')
                    ->required()
                    ->maxLength(255),
            ])
            ->after(function () {
                $this->dispatch('$refresh');
            });
    }

    public function deleteAreaAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\DeleteAction::make('deleteArea')
            ->label('Delete Area')
            ->icon('heroicon-m-trash')
            ->record(fn () => Area::find($this->activeAreaId))
            ->requiresConfirmation()
            ->after(function () {
                $this->activeAreaId = Area::first()?->id;
                $this->dispatch('$refresh');
            });
    }
    
    public function editTableAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\EditAction::make('editTable')
            ->label('Edit Table Details')
            ->record(fn (array $arguments) => Table::find($arguments['id'] ?? null))
            ->modalWidth('sm')
            ->form([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nume Masă')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('seats')
                    ->label('Număr Locuri')
                    ->numeric()
                    ->required()
                    ->default(4),
            ])
            ->after(function () {
                $this->dispatch('$refresh');
            });
    }

    public function createTable($type = 'square')
    {
        if (!$this->activeAreaId) return;

        Table::create([
            'area_id' => $this->activeAreaId,
            'name' => 'New Table',
            'shape' => $type,
            'x' => 50,
            'y' => 50,
            'seats' => 4,
            'width' => 80,
            'height' => 80,
        ]);
        
        $this->dispatch('tables-updated'); // Refresh/Notify
    }

    public function updateTablePosition($id, $x, $y)
    {
        $table = Table::find($id);
        if ($table) {
            $table->update(['x' => $x, 'y' => $y]);
        }
    }
    
    public function updateTableSize($id, $width, $height)
    {
        $table = Table::find($id);
        if ($table) {
            $table->update(['width' => $width, 'height' => $height]);
        }
    }

    public function deleteTable($id)
    {
        Table::destroy($id);
        $this->dispatch('tables-updated');
    }

    protected function getViewData(): array
    {
        return [
            'areas' => Area::all(),
            'tables' => $this->activeAreaId ? Table::where('area_id', $this->activeAreaId)->get() : [],
        ];
    }
}
