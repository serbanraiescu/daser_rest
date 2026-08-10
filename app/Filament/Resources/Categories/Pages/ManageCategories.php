<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Modules\Menu\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('exportCategories')
                ->label('Exportă categoriile pentru AI')
                ->color('gray')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'wb');
                        fwrite($handle, "\xEF\xBB\xBF");

                        fputcsv($handle, [
                            'menu',
                            'categorie',
                            'categorie_en',
                            'categorie_de',
                            'categorie_it',
                            'categorie_fr',
                            'destinatie',
                            'ordine',
                            'activ',
                        ], ';', '"', '');

                        Category::query()
                            ->with(['menu', 'translations'])
                            ->orderBy('menu_id')
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->chunk(200, function ($categories) use ($handle): void {
                                foreach ($categories as $category) {
                                    $translations = $category->translations->keyBy('locale');

                                    fputcsv($handle, [
                                        $category->menu?->name ?? '',
                                        $category->name,
                                        $translations->get('en')?->name ?? '',
                                        $translations->get('de')?->name ?? '',
                                        $translations->get('it')?->name ?? '',
                                        $translations->get('fr')?->name ?? '',
                                        $category->destination ?? 'kitchen',
                                        $category->sort_order ?? 0,
                                        $category->is_active ? 1 : 0,
                                    ], ';', '"', '');
                                }
                            });

                        fclose($handle);
                    }, 'categorii-existente-pentru-ai-'.now()->format('Y-m-d').'.csv', [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }
}
