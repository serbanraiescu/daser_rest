<?php

namespace App\Filament\Pages;

use App\Modules\Settings\Models\CompanySetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Forms\Form;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Setari Site';
    protected static ?string $title = 'Setari Site';
    // protected static ?string $navigationGroup = 'Settings';
    
    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::first();
        if ($settings) {
            $this->form->fill($settings->attributesToArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // General
                TextInput::make('site_name')
                    ->label('Nume Site')
                    ->required()
                    ->maxLength(255)
                    ->default('Daser Restaurant'),
                FileUpload::make('company_logo')
                    ->label('Logo Companie')
                    ->image()
                    ->directory('settings'),


                // Hero
                TextInput::make('hero_title')
                    ->label('Titlu Principal (Hero)')
                    ->default('Welcome to Our Restaurant')
                    ->required(),
                Textarea::make('hero_description')
                    ->label('Descriere / Subtitlu')
                    ->rows(2),
                FileUpload::make('hero_background_image')
                    ->label('Imagine Fundal Hero')
                    ->image()
                    ->directory('settings'),

                // Contact
                TextInput::make('contact_phone')
                    ->label('Telefon Contact')
                    ->tel(),
                Textarea::make('address')
                    ->label('Adresa')
                    ->rows(3),

                // Schedule
                Repeater::make('opening_hours')
                    ->label('Program Funcționare')
                    ->schema([
                        Select::make('day')
                            ->label('Ziua')
                            ->options([
                                'Monday' => 'Luni',
                                'Tuesday' => 'Marți',
                                'Wednesday' => 'Miercuri',
                                'Thursday' => 'Joi',
                                'Friday' => 'Vineri',
                                'Saturday' => 'Sâmbătă',
                                'Sunday' => 'Duminică',
                            ])
                            ->required(),
                        TextInput::make('hours')
                            ->label('Ore')
                            ->placeholder('09:00 - 22:00')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(7)
                    ->reorderable(true),

                // Social
                Repeater::make('social_links')
                    ->label('Social Media')
                    ->schema([
                        Select::make('platform')
                            ->label('Platforma')
                            ->options([
                                'facebook' => 'Facebook',
                                'instagram' => 'Instagram',
                                'tiktok' => 'TikTok',
                                'whatsapp' => 'WhatsApp',
                            ])
                            ->required(),
                        TextInput::make('url')
                            ->label('Link URL')
                            ->url()
                            ->required(),
                    ])
                    ->columns(2),


            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvează Setările')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        $settings = CompanySetting::firstOrNew();
        $settings->fill($data);
        $settings->save();

        Notification::make() 
            ->success()
            ->title('Setări salvate cu succes!')
            ->send();
    }
}
