<?php

namespace App\Modules\Menu\Services;

use App\Modules\Menu\Models\Allergen;
use App\Modules\Menu\Models\Category;
use App\Modules\Menu\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductCsvImporter
{
    private const HEADER_ALIASES = [
        'category' => ['category', 'categorie', 'category_name', 'nume_categorie'],
        'name' => ['name', 'nume', 'denumire', 'produs', 'product'],
        'name_en' => ['name_en', 'nume_en', 'english_name', 'nume_engleza'],
        'price' => ['price', 'pret', 'pret_ron', 'price_ron'],
        'description' => ['description', 'descriere'],
        'description_en' => ['description_en', 'descriere_en', 'english_description', 'descriere_engleza'],
        'vat_rate' => ['vat_rate', 'tva', 'cota_tva'],
        'measurement_value' => ['measurement_value', 'cantitate', 'gramaj', 'volum'],
        'measurement_unit' => ['measurement_unit', 'unitate', 'um'],
        'is_active' => ['is_active', 'active', 'activ', 'stare'],
        'is_available' => ['is_available', 'available', 'disponibil'],
        'sort_order' => ['sort_order', 'ordine', 'pozitie'],
        'allergens' => ['allergens', 'alergeni'],
        'destination' => ['destination', 'destinatie'],
    ];

    public function import(
        string $filePath,
        int $menuId,
        string $defaultDestination = 'kitchen',
        bool $updateExisting = true,
    ): array {
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Fișierul CSV nu a putut fi deschis.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new RuntimeException('Fișierul CSV este gol.');
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter);
            if ($headers === false) {
                throw new RuntimeException('Antetul CSV nu a putut fi citit.');
            }

            $columnIndexes = $this->mapHeaders($headers);
            foreach (['category', 'name', 'price'] as $requiredColumn) {
                if (!array_key_exists($requiredColumn, $columnIndexes)) {
                    throw new RuntimeException("Lipsește coloana obligatorie: {$requiredColumn}.");
                }
            }

            $result = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'categories_created' => 0,
                'warnings' => [],
            ];
            $rowNumber = 1;

            DB::transaction(function () use (
                $handle,
                $delimiter,
                $columnIndexes,
                $menuId,
                $defaultDestination,
                $updateExisting,
                &$result,
                &$rowNumber,
            ): void {
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rowNumber++;
                    if ($this->isEmptyRow($row)) {
                        continue;
                    }

                    $categoryName = $this->value($row, $columnIndexes, 'category');
                    $name = $this->value($row, $columnIndexes, 'name');
                    $rawPrice = $this->value($row, $columnIndexes, 'price');

                    if ($categoryName === '' || $name === '' || $rawPrice === '') {
                        $result['skipped']++;
                        $result['warnings'][] = "Rândul {$rowNumber}: categorie, nume sau preț lipsă.";
                        continue;
                    }

                    $price = $this->parseDecimal($rawPrice);
                    if ($price === null || $price < 0) {
                        $result['skipped']++;
                        $result['warnings'][] = "Rândul {$rowNumber}: preț invalid «{$rawPrice}» pentru {$name}.";
                        continue;
                    }

                    $destination = strtolower($this->value($row, $columnIndexes, 'destination'));
                    if (!in_array($destination, ['kitchen', 'bar'], true)) {
                        $destination = $defaultDestination;
                    }

                    $category = Category::query()
                        ->where('menu_id', $menuId)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($categoryName)])
                        ->first();

                    if (!$category) {
                        $category = new Category();
                        $category->menu_id = $menuId;
                        $category->name = $categoryName;
                        $category->destination = $destination;
                        $category->is_active = true;
                        $category->sort_order = 0;
                        $category->save();
                        $result['categories_created']++;
                    }

                    $product = Product::query()
                        ->where('category_id', $category->id)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                        ->first();

                    if ($product && !$updateExisting) {
                        $result['skipped']++;
                        continue;
                    }

                    $attributes = [
                        'category_id' => $category->id,
                        'name' => $name,
                        'price' => $price,
                    ];

                    $this->addOptionalString($attributes, 'description', $row, $columnIndexes);
                    $this->addOptionalString($attributes, 'name_en', $row, $columnIndexes);
                    $this->addOptionalString($attributes, 'description_en', $row, $columnIndexes);
                    $this->addOptionalString($attributes, 'vat_rate', $row, $columnIndexes);
                    $this->addOptionalString($attributes, 'measurement_unit', $row, $columnIndexes);

                    $measurement = $this->parseDecimal($this->value($row, $columnIndexes, 'measurement_value'));
                    if ($measurement !== null) {
                        $attributes['measurement_value'] = $measurement;
                    }

                    $sortOrder = $this->parseInteger($this->value($row, $columnIndexes, 'sort_order'));
                    if ($sortOrder !== null) {
                        $attributes['sort_order'] = $sortOrder;
                    }

                    foreach (['is_active', 'is_available'] as $booleanColumn) {
                        $rawBoolean = $this->value($row, $columnIndexes, $booleanColumn);
                        if ($rawBoolean !== '') {
                            $attributes[$booleanColumn] = $this->parseBoolean($rawBoolean);
                        }
                    }

                    if ($product) {
                        $product->update($attributes);
                        $result['updated']++;
                    } else {
                        $attributes['is_active'] ??= true;
                        $attributes['is_available'] ??= true;
                        $attributes['sort_order'] ??= 0;
                        $product = Product::create($attributes);
                        $result['created']++;
                    }

                    $allergens = $this->splitList($this->value($row, $columnIndexes, 'allergens'));
                    if ($allergens !== []) {
                        $allergenIds = Allergen::query()
                            ->whereIn(DB::raw('LOWER(name)'), array_map([Str::class, 'lower'], $allergens))
                            ->pluck('id')
                            ->all();
                        $product->allergenRelations()->syncWithoutDetaching($allergenIds);

                        if (count($allergenIds) !== count($allergens)) {
                            $result['warnings'][] = "Rândul {$rowNumber}: unii alergeni ai produsului {$name} nu există și au fost ignorați.";
                        }
                    }
                }
            });

            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter(string $line): string
    {
        $counts = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function mapHeaders(array $headers): array
    {
        $normalizedHeaders = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
        $indexes = [];

        foreach (self::HEADER_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search($alias, $normalizedHeaders, true);
                if ($index !== false) {
                    $indexes[$canonical] = $index;
                    break;
                }
            }
        }

        return $indexes;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', trim($header));
        $header = Str::ascii(Str::lower($header));

        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $header), '_');
    }

    private function value(array $row, array $indexes, string $column): string
    {
        if (!array_key_exists($column, $indexes)) {
            return '';
        }

        return trim((string) ($row[$indexes[$column]] ?? ''));
    }

    private function addOptionalString(array &$attributes, string $key, array $row, array $indexes): void
    {
        $value = $this->value($row, $indexes, $key);
        if ($value !== '') {
            $attributes[$key] = $value;
        }
    }

    private function parseDecimal(string $value): ?float
    {
        $value = trim(str_replace(["\u{00A0}", ' RON', ' lei', ' LEI'], '', $value));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(['.', ','], ['', '.'], $value)
                : str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function parseInteger(string $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null;
    }

    private function parseBoolean(string $value): bool
    {
        return !in_array(Str::lower(trim($value)), ['0', 'false', 'nu', 'no', 'inactiv', 'indisponibil'], true);
    }

    private function splitList(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[|,]/', $value) ?: []))));
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }
}
