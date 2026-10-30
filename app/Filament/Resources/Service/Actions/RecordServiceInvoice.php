<?php

namespace App\Filament\Resources\Service\Actions;

use App\Filament\Resources\Service\ServiceInvoiceResource;
use App\Modules\Service\Models\ServiceOrder;
use App\Modules\Service\Services\ServiceBillingService;
use App\Modules\Settings\Models\CompanySetting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RecordServiceInvoice
{
    public static function single(): Action
    {
        return Action::make('recordInvoice')
            ->label('Marchează facturat')->icon('heroicon-o-document-check')->color('success')
            ->visible(fn (ServiceOrder $record): bool => static::enabled()
                && $record->status === 'completed' && $record->payment_method === 'client_account'
                && $record->service_invoice_id === null)
            ->modalHeading('Înregistrează factura emisă')
            ->modalDescription(fn (ServiceOrder $record): string => 'Fișa #'.$record->id.' — '.$record->customer_name.'. Factura trebuie emisă în aplicația de facturare. Această operație păstrează evidența și nu înregistrează o plată.')
            ->modalSubmitActionLabel('Marchează facturat')
            ->form(static::fields())
            ->fillForm(fn (ServiceOrder $record): array => ['amount' => number_format($record->total, 2, '.', ''), 'issued_on' => static::today()])
            ->action(fn (ServiceOrder $record, array $data, Action $action) => static::save([$record->id], $data, $action));
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('recordInvoice')
            ->label('Marchează facturat')->icon('heroicon-o-document-check')->color('success')
            ->visible(fn (): bool => static::enabled())
            ->modalHeading('Înregistrează factura pentru fișele selectate')
            ->modalDescription(fn (Collection $records): string => $records->count().' fișe selectate. Selectează numai fișele aceluiași client. Factura se emite extern; înregistrarea ei nu confirmă încasarea.')
            ->modalSubmitActionLabel('Marchează facturat')
            ->form(static::fields())
            ->fillForm(fn (Collection $records): array => [
                'amount' => number_format($records->sum(fn ($record) => (int) round($record->total * 100)) / 100, 2, '.', ''),
                'issued_on' => static::today(),
            ])
            ->action(fn (Collection $records, array $data, BulkAction $action) => static::save($records->modelKeys(), $data, $action))
            ->deselectRecordsAfterCompletion();
    }

    protected static function fields(): array
    {
        return [
            TextInput::make('number')->label('Număr factură (serie și număr)')->required()->maxLength(100),
            TextInput::make('amount')->label('Suma facturată')->numeric()->required()->minValue(0.01)
                ->maxValue(9999999999.99)->step(0.01)->rules(['decimal:0,2'])
                ->suffix(CompanySetting::first()?->currency ?? 'RON')
                ->helperText('Inițial este totalul fișelor selectate. Introdu suma finală de pe factura emisă.'),
            DatePicker::make('issued_on')->label('Data facturii')->required()->maxDate(static::today())->displayFormat('d.m.Y'),
        ];
    }

    protected static function today(): string
    {
        return now(config('service_reporting.timezone', 'Europe/Bucharest'))->toDateString();
    }

    protected static function enabled(): bool
    {
        return auth()->check() && (bool) CompanySetting::first()?->enable_service_module;
    }

    protected static function save(array $orderIds, array $data, Action|BulkAction $action): void
    {
        abort_unless(static::enabled(), 403);

        try {
            $invoice = app(ServiceBillingService::class)->recordInvoice($orderIds, $data, auth()->id());
        } catch (ValidationException $exception) {
            Notification::make()->title('Factura nu a fost înregistrată')
                ->body(collect($exception->errors())->flatten()->implode(' '))->danger()->persistent()->send();
            $action->halt();
            return;
        }

        Notification::make()->title('Factura '.$invoice->number.' a fost înregistrată')->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('invoice')->label('Vezi factura')
                    ->url(ServiceInvoiceResource::getUrl('view', ['record' => $invoice])),
            ])->send();
    }
}
