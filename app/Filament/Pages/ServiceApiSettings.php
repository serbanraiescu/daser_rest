<?php

namespace App\Filament\Pages;

use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class ServiceApiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $title = 'API notificări';
    protected static ?string $navigationGroup = 'Configurare';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.service-api-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && (bool) CompanySetting::first()?->enable_service_module;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'enabled' => CompanySetting::firstOrNew()->serviceReportApiEnabled(),
            'token' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('enabled')
                ->label('Activează API-ul pentru rapoarte')
                ->helperText('Permite accesul la rapoartele zilnice și lunare cu tokenul configurat.'),
            TextInput::make('token')
                ->label('Token nou')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->minLength(32)
                ->maxLength(255)
                ->rules(['nullable', 'regex:/\A\S+\z/u'])
                ->helperText('Lasă gol pentru a păstra tokenul curent. Pentru înlocuire, generează un token, copiază-l în credențialul n8n și salvează. După salvare câmpul se golește.')
                ->suffixAction(Action::make('generateToken')
                    ->label('Generează token')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Set $set): void {
                        abort_unless(static::canAccess(), 403);
                        $set('token', bin2hex(random_bytes(32)));
                    })),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $settings = CompanySetting::firstOrNew();
        $token = $data['token'] ?? '';

        if ($data['enabled'] && strlen($token ?: $settings->serviceReportApiToken()) < 32) {
            throw ValidationException::withMessages(['data.token' => 'Generează sau introdu un token de minimum 32 de caractere.']);
        }

        if ($token !== '') {
            $settings->service_report_api_token = $token;
        }

        $settings->service_report_api_enabled = (bool) $data['enabled'];
        $settings->save();
        $this->form->fill(['enabled' => $settings->serviceReportApiEnabled(), 'token' => '']);

        Notification::make()->success()->title('Configurația API a fost salvată')->send();
    }

    protected function getViewData(): array
    {
        return [
            'dailyUrl' => route('api.service.reports.daily'),
            'monthlyUrl' => route('api.service.reports.monthly'),
            'timezone' => config('service_reporting.timezone'),
        ];
    }
}
