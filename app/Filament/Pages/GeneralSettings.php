<?php

namespace App\Filament\Pages;

use App\Modules\Menu\Models\Category;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Models\CompanySetting;
use App\Modules\Staff\Models\StaffMember;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;


class GeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $title = 'Setări Generale';
    protected static ?string $navigationLabel = 'Setări Generale';
    // protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.general-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::firstOrNew();
        $this->form->fill($settings->attributesToArray());
    }



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        \Filament\Forms\Components\Tabs\Tab::make('General')
                            ->schema([
                                Toggle::make('enable_ordering')
                                    ->label('Comenzi Online')
                                    ->helperText('Permite clienților să plaseze comenzi prin site.')
                                    ->default(true),
                                Toggle::make('enable_delivery')
                                    ->label('Livrare la Domiciliu')
                                    ->helperText('Activează opțiunea de livrare pentru comenzi.')
                                    ->default(true),
                                Select::make('default_language')
                                    ->label('Limba de Afișare')
                                    ->options([
                                        'ro' => 'Română',
                                        'en' => 'English',
                                    ])
                                    ->default('ro')
                                    ->required(),
                            ]),
                        
                        \Filament\Forms\Components\Tabs\Tab::make('Date Fiscale & TVA')
                            ->schema([
                                \Filament\Forms\Components\TagsInput::make('measurement_units')
                                    ->label('Unități de Măsură')
                                    ->placeholder('Adaugă unitate (ex: g, buc, ml)')
                                    ->helperText('Scrie unitatea și apasă Enter.')
                                    ->separator(',')
                                    ->reorderable()
                                    ->default(['g', 'kg', 'ml', 'l', 'buc', 'portie']),

                                \Filament\Forms\Components\Grid::make(2)
                                    ->schema([
                                        \Filament\Forms\Components\TagsInput::make('vat_rates')
                                            ->label('Cote TVA Disponibile (%)')
                                            ->placeholder('Adaugă cotă (ex: 19, 9, 5)')
                                            ->helperText('Scrie valoarea și apasă Enter.')
                                            ->separator(',')
                                            ->reorderable(),
                                        
                                        TextInput::make('currency')
                                            ->label('Monedă (ex: RON, USD, EUR)')
                                            ->default('RON'),
                                    ]),

                                TextInput::make('fiscal_code')
                                    ->label('Cod Fiscal (CUI)'),
                                TextInput::make('trade_register')
                                    ->label('Nr. Reg. Com.'),
                                Textarea::make('fiscal_address')
                                    ->label('Adresa Fiscală')
                                    ->rows(2),
                            ]),

                        \Filament\Forms\Components\Tabs\Tab::make('Integrare SPV')
                            ->schema([
                                TextInput::make('spv_token')
                                    ->label('Token SPV (OAuth)')
                                    ->password()
                                    ->revealable()
                                    ->helperText('Introduceți aici token-ul generat pentru accesul ANAF e-Factura.'),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = CompanySetting::firstOrNew();
        $settings->fill($this->form->getState());
        $settings->save();

        Notification::make() 
            ->success()
            ->title('Setări salvate!')
            ->send();
    }

    public function resetSystemAction(): Action
    {
        return Action::make('resetSystem')
            ->label('RESETARE TOTALĂ SISTEM')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Resetare Completă')
            ->modalDescription('ATENȚIE! Această acțiune va șterge TOATE datele din sistem: Comenzi, Produse, Categorii, Meniuri și Angajați. Va păstra doar contul de Admin și Setările de bază. Ești sigur?')
            ->modalSubmitActionLabel('Da, Șterge Tot')
            ->action(function () {
                $this->performSystemReset();
            });
    }

    protected function performSystemReset()
    {
        try {
            DB::beginTransaction();

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            Order::truncate();
            DB::table('order_items')->truncate();
            Product::truncate();
            Category::truncate();
            Menu::truncate();
            StaffMember::truncate();
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            DB::commit();

            Notification::make()
                ->success()
                ->title('Sistemul a fost resetat cu succes!')
                ->body('Toate datele operaționale au fost șterse.')
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Eroare la resetare')
                ->body($e->getMessage())
                ->send();
        }
    }
}
