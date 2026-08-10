<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Modules\Menu\Models\Menu;
use App\Modules\Menu\Services\ProductCsvImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('downloadCsvTemplate')
                ->label('Descarcă model CSV')
                ->color('gray')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $header = 'categorie;categorie_en;categorie_de;categorie_it;categorie_fr;nume;nume_en;nume_de;nume_it;nume_fr;pret;descriere;descriere_en;descriere_de;descriere_it;descriere_fr;tva;cantitate;unitate;activ;disponibil;ordine;alergeni;destinatie';
                    $example = 'Pizza;Pizza;Pizza;Pizza;Pizza;Margherita;Margherita Pizza;Pizza Margherita;Pizza Margherita;Pizza Margherita;32,00;Sos de roșii și mozzarella;Tomato sauce and mozzarella;Tomatensauce und Mozzarella;Salsa di pomodoro e mozzarella;Sauce tomate et mozzarella;9;450;g;1;1;10;Gluten|Lapte;kitchen';

                    return response()->streamDownload(function () use ($header, $example): void {
                        echo "\xEF\xBB\xBF{$header}\r\n{$example}\r\n";
                    }, 'model-import-produse-multilingv.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
            Action::make('importCsv')
                ->label('Importă CSV')
                ->color('success')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    Placeholder::make('csv_instructions')
                        ->label('Cum pregătești CSV-ul')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div class="text-sm"><p><strong>Obligatoriu:</strong> categorie, nume, pret.</p>'
                            .'<p><strong>Traduceri:</strong> categorie_en/de/it/fr, nume_en/de/it/fr și descriere_en/de/it/fr.</p>'
                            .'<p><strong>Opțional:</strong> descriere, tva, cantitate, unitate, activ, disponibil, ordine și alergeni.</p>'
                            .'<p>Pentru mâncare: <code>destinatie=kitchen</code>. Pentru băuturi: <code>destinatie=bar</code>.</p>'
                            .'<p>Prima linie conține antetele. Separator: <code>;</code>, <code>,</code> sau TAB. Alergenii se separă cu <code>|</code>.</p>'
                            .'<p class="mt-2"><strong>Recomandare:</strong> descarcă modelul CSV și dă-l AI-ului împreună cu PDF-ul, cerând să păstreze exact antetele.</p></div>'
                        ))
                        ->columnSpanFull(),
                    Select::make('menu_id')
                        ->label('Meniu')
                        ->options(Menu::query()->orderBy('sort_order')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('Categoriile noi din CSV vor fi create în acest meniu.'),
                    Select::make('default_destination')
                        ->label('Destinație implicită pentru categorii noi')
                        ->options([
                            'kitchen' => 'Bucătărie',
                            'bar' => 'Bar',
                        ])
                        ->default('kitchen')
                        ->required(),
                    Checkbox::make('update_existing')
                        ->label('Actualizează produsele existente cu același nume și aceeași categorie')
                        ->default(true),
                    FileUpload::make('csv_file')
                        ->label('Fișier CSV')
                        ->disk('local')
                        ->directory('temp-imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required()
                        ->helperText('Obligatoriu: categorie, nume, preț. Separator acceptat: virgulă, punct și virgulă sau TAB.'),
                ])
                ->action(function (array $data, ProductCsvImporter $importer): void {
                    $fileKey = $data['csv_file'];

                    try {
                        $result = $importer->import(
                            Storage::disk('local')->path($fileKey),
                            (int) $data['menu_id'],
                            $data['default_destination'],
                            (bool) ($data['update_existing'] ?? false),
                        );

                        $body = "Create: {$result['created']}; actualizate: {$result['updated']}; "
                            . "omise: {$result['skipped']}; categorii noi: {$result['categories_created']}; "
                            . "traduceri salvate: {$result['translations_saved']}.";

                        if ($result['warnings'] !== []) {
                            $body .= ' Avertismente: '.implode(' ', array_slice($result['warnings'], 0, 3));
                        }

                        Notification::make()
                            ->success()
                            ->title('Import produse finalizat')
                            ->body($body)
                            ->persistent($result['warnings'] !== [])
                            ->send();
                    } catch (\Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Importul produselor a eșuat')
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();
                    } finally {
                        Storage::disk('local')->delete($fileKey);
                    }
                }),
        ];
    }
}
