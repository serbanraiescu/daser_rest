<?php

namespace App\Filament\Resources\Menu\Pages;

use App\Filament\Resources\Menu\IngredientResource;
use App\Modules\Menu\Models\Ingredient;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageIngredients extends ManageRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('importCsv')
                ->label('Importă CSV')
                ->color('success')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('csv_file')
                        ->label('Fișier CSV')
                        ->disk('local') // Saved to private storage/app/temp-imports
                        ->directory('temp-imports')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'text/plain'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $fileKey = $data['csv_file'];
                    $filePath = Storage::disk('local')->path($fileKey);
                    
                    if (($handle = fopen($filePath, 'r')) !== false) {
                        // Read first row to determine headers & delimiter
                        $firstLine = fgets($handle);
                        
                        // Detect delimiter (comma or semicolon)
                        $delimiter = (str_contains($firstLine, ';') && !str_contains($firstLine, ',')) ? ';' : ',';
                        
                        // Parse header row
                        rewind($handle);
                        $header = fgetcsv($handle, 1000, $delimiter);
                        
                        // Sanitize and lowercase header fields
                        $headerMap = array_map(function($h) {
                            return strtolower(trim(preg_replace('/[^A-Za-z0-9_]/', '', $h)));
                        }, $header);
                        
                        // Search for name and unit columns
                        $nameIndex = null;
                        $unitIndex = null;
                        $activeIndex = null;
                        
                        foreach ($headerMap as $index => $colName) {
                            if (in_array($colName, ['name', 'nume', 'denumire', 'ingredient'])) {
                                $nameIndex = $index;
                            } elseif (in_array($colName, ['unit', 'unitate', 'um'])) {
                                $unitIndex = $index;
                            } elseif (in_array($colName, ['is_active', 'active', 'activ', 'stare'])) {
                                $activeIndex = $index;
                            }
                        }
                        
                        // Fallback indexes if headers not matched
                        if ($nameIndex === null) $nameIndex = 0;
                        if ($unitIndex === null) $unitIndex = 1;
                        
                        $count = 0;
                        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                            if (empty($row) || count($row) === 0) continue;
                            
                            $name = isset($row[$nameIndex]) ? trim((string)$row[$nameIndex]) : null;
                            if (empty($name)) continue;
                            
                            $unit = isset($row[$unitIndex]) ? trim((string)$row[$row[$unitIndex] ?? $unitIndex]) : '';
                            // Let's get actual unit safely
                            if (isset($row[$unitIndex])) {
                                $unit = trim((string)$row[$unitIndex]);
                            }
                            
                            $isActive = true;
                            if ($activeIndex !== null && isset($row[$activeIndex])) {
                                $activeVal = strtolower(trim((string)$row[$activeIndex]));
                                $isActive = !in_array($activeVal, ['0', 'false', 'nu', 'inactiv']);
                            }
                            
                            // 1. Case-insensitive lookup to avoid duplicates
                            $existing = Ingredient::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                            
                            if ($existing) {
                                // 2. If ingredient exists, update the unit ONLY if CSV has it filled
                                $updateData = [];
                                if ($unit !== '') {
                                    $updateData['unit'] = $unit;
                                }
                                if ($activeIndex !== null) {
                                    $updateData['is_active'] = $isActive;
                                }
                                
                                if (!empty($updateData)) {
                                    $existing->update($updateData);
                                }
                            } else {
                                // Create new ingredient
                                Ingredient::create([
                                    'name' => $name,
                                    'unit' => $unit !== '' ? $unit : 'g', // fallback if empty
                                    'is_active' => $isActive,
                                ]);
                            }
                            $count++;
                        }
                        fclose($handle);
                    }
                    
                    // 3. Delete temporary file from private disk
                    Storage::disk('local')->delete($fileKey);
                    
                    Notification::make()
                        ->success()
                        ->title("Import Finalizat!")
                        ->body("Au fost procesate cu succes $count ingrediente.")
                        ->send();
                }),
        ];
    }
}
