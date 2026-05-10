<?php

namespace App\Filament\Pages;

use App\Modules\Menu\Models\Category;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Settings\Models\CompanySetting;
use App\Modules\Staff\Models\StaffMember;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;

class PlatformSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $title = 'Configurare Platformă';
    protected static ?string $navigationLabel = 'Configurare Platformă';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.platform-settings';

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
                Tabs::make('PlatformSettings')
                    ->tabs([
                        // TAB: GENERAL & FISCAL
                        Tab::make('General & Fiscal')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('site_name')
                                        ->label('Nume Site')
                                        ->required()
                                        ->default('Daser Restaurant'),
                                    Select::make('default_language')
                                        ->label('Limba Implicită')
                                        ->options(['ro' => 'Română', 'en' => 'English'])
                                        ->default('ro')
                                        ->required(),
                                ]),
                                Section::make('Operațional')
                                    ->schema([
                                        Toggle::make('enable_ordering')->label('Comenzi Online')->default(true),
                                        Toggle::make('enable_delivery')->label('Livrare la Domiciliu')->default(true),
                                    ])->columns(2),
                                Section::make('Date Fiscale')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('fiscal_code')->label('Cod Fiscal (CUI)'),
                                            TextInput::make('trade_register')->label('Nr. Reg. Com.'),
                                            TextInput::make('currency')->label('Monedă')->default('RON'),
                                        ]),
                                        Textarea::make('fiscal_address')->label('Adresa Fiscală')->rows(2),
                                        TagsInput::make('vat_rates')
                                            ->label('Cote TVA (%)')
                                            ->placeholder('Adaugă cotă (ex: 19, 9, 5)'),
                                        TagsInput::make('measurement_units')
                                            ->label('Unități de Măsură')
                                            ->placeholder('Adaugă unitate (ex: g, buc, ml)'),
                                    ]),
                                Section::make('Integrare SPV')
                                    ->schema([
                                        TextInput::make('spv_token')
                                            ->label('Token SPV (OAuth)')
                                            ->password()
                                            ->revealable(),
                                    ]),
                            ]),

                        // TAB: DESIGN & COLORS
                        Tab::make('Design & Culori')
                            ->icon('heroicon-o-swatch')
                            ->schema([
                                Section::make('Tematică Vizuală')
                                    ->description('Alege culorile care se vor aplica global pe site.')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            ColorPicker::make('frontend_colors.primary')
                                                ->label('Culoare Principală')
                                                ->default('#eab308'),
                                            ColorPicker::make('frontend_colors.secondary')
                                                ->label('Culoare Secundară')
                                                ->default('#111827'),
                                            ColorPicker::make('frontend_colors.background')
                                                ->label('Fundal Site')
                                                ->default('#ffffff'),
                                            ColorPicker::make('frontend_colors.text')
                                                ->label('Culoare Text')
                                                ->default('#1f2937'),
                                        ]),
                                    ]),
                                FileUpload::make('company_logo')
                                    ->label('Logo Site')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('public'),
                            ]),

                        // TAB: PAGINA ACASA & CONTACT
                        Tab::make('Acasă & Contact')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('Hero Section (Acasă)')
                                    ->schema([
                                        TextInput::make('hero_title')->label('Titlu Hero'),
                                        Textarea::make('hero_description')->label('Subtitlu Hero')->rows(2),
                                        FileUpload::make('hero_background_image')
                                            ->label('Imagine Fundal Hero')
                                            ->image()
                                            ->directory('settings')
                                            ->disk('public'),
                                    ]),
                                Section::make('Contact & Social')
                                    ->schema([
                                        TextInput::make('contact_phone')->label('Telefon Contact')->tel(),
                                        Textarea::make('address')->label('Adresă Fizică')->rows(2),
                                        Repeater::make('social_links')
                                            ->label('Link-uri Sociale')
                                            ->schema([
                                                Select::make('platform')
                                                    ->options([
                                                        'facebook' => 'Facebook',
                                                        'instagram' => 'Instagram',
                                                        'tiktok' => 'TikTok',
                                                        'whatsapp' => 'WhatsApp',
                                                    ])->required(),
                                                TextInput::make('url')->url()->required(),
                                            ])->columns(2),
                                    ]),
                                Section::make('Program de Funcționare')
                                    ->schema([
                                        Repeater::make('opening_hours')
                                            ->schema([
                                                Select::make('day')
                                                    ->options([
                                                        'Monday' => 'Luni',
                                                        'Tuesday' => 'Marți',
                                                        'Wednesday' => 'Miercuri',
                                                        'Thursday' => 'Joi',
                                                        'Friday' => 'Vineri',
                                                        'Saturday' => 'Sâmbătă',
                                                        'Sunday' => 'Duminică',
                                                    ])->required(),
                                                TextInput::make('hours')->placeholder('09:00 - 22:00')->required(),
                                            ])->columns(2)->defaultItems(7),
                                    ]),
                            ]),

                        // TAB: PAGINI LEGALE & INFO
                        Tab::make('Pagini & Legal')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                RichEditor::make('about_content')
                                    ->label('Despre Noi')
                                    ->columnSpanFull(),
                                RichEditor::make('terms_content')
                                    ->label('Termeni și Condiții')
                                    ->columnSpanFull(),
                                RichEditor::make('gdpr_content')
                                    ->label('GDPR (Protecția Datelor)')
                                    ->columnSpanFull(),
                                RichEditor::make('privacy_content')
                                    ->label('Politică de Confidențialitate')
                                    ->columnSpanFull(),
                            ]),

                        // TAB: COOKIES & GALERIE
                        Tab::make('Alte Setări')
                            ->icon('heroicon-o-ellipsis-horizontal')
                            ->schema([
                                Section::make('Modul Cookie Consent')
                                    ->description('Configurare banner legal cookies.')
                                    ->schema([
                                        Toggle::make('cookie_consent.enabled')->label('Activează Modulul')->default(true),
                                        Textarea::make('cookie_consent.message')
                                            ->label('Mesaj Banner')
                                            ->default('Acest site folosește cookies pentru o experiență mai bună.'),
                                        TextInput::make('cookie_consent.button_text')
                                            ->label('Text Buton Acceptare')
                                            ->default('Accept'),
                                    ]),
                                Section::make('Galerie Evenimente')
                                    ->schema([
                                        FileUpload::make('gallery_content')
                                            ->label('Imagini Galerie')
                                            ->image()
                                            ->multiple()
                                            ->directory('gallery')
                                            ->disk('public')
                                            ->reorderable(),
                                    ]),
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
            ->title('Toate setările au fost salvate cu succes!')
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
            ->modalDescription('ATENȚIE! Această acțiune va șterge TOATE datele din sistem. Ești sigur?')
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
                ->title('Sistemul a fost resetat!')
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->danger()->title('Eroare')->body($e->getMessage())->send();
        }
    }
}
