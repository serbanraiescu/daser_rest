<?php

namespace App\Filament\Pages;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class InventoryReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Raport Inventar';
    protected static ?string $navigationLabel = 'Raport Inventar';
    protected static ?string $navigationGroup = 'Inventar';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.inventory-report';

    public string $period = 'monthly';
    public string $selectedDate = '';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->startDate    = now()->startOfMonth()->format('Y-m-d');
        $this->endDate      = now()->format('Y-m-d');
    }

    public function getReportData(): array
    {
        $dateRange = $this->getDateRange();
        $start     = $dateRange['start'];
        $end       = $dateRange['end'];

        // Total intrări în perioadă
        $totalIn = StockMovement::where('type', StockMovement::TYPE_IN)
            ->whereBetween('created_at', [$start, $end])
            ->sum(DB::raw('ABS(quantity)'));

        // Total ieșiri (vânzări + pierderi + ajustări negative)
        $totalOut = StockMovement::whereIn('type', [
                StockMovement::TYPE_SALE,
                StockMovement::TYPE_OUT,
                StockMovement::TYPE_WASTE,
            ])
            ->whereBetween('created_at', [$start, $end])
            ->sum(DB::raw('ABS(quantity)'));

        // Consum per produs (vânzări)
        $consumption = StockMovement::with('inventoryItem')
            ->where('type', StockMovement::TYPE_SALE)
            ->whereBetween('created_at', [$start, $end])
            ->select('inventory_item_id', DB::raw('SUM(ABS(quantity)) as total_consumed'))
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_consumed')
            ->get();

        // Produse cu stoc scăzut
        $lowStock = InventoryItem::where('is_active', true)
            ->whereNotNull('minimum_stock')
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->get();

        // Toate mișcările din perioadă
        $movements = StockMovement::with('inventoryItem')
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();

        return [
            'total_in'    => (float) $totalIn,
            'total_out'   => (float) $totalOut,
            'consumption' => $consumption,
            'low_stock'   => $lowStock,
            'movements'   => $movements,
            'range_title' => $this->getRangeTitle($start, $end),
        ];
    }

    protected function getDateRange(): array
    {
        switch ($this->period) {
            case 'daily':
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfDay()->toDateTimeString(),
                    'end'   => $carbon->copy()->endOfDay()->toDateTimeString(),
                ];
            case 'weekly':
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfWeek()->toDateTimeString(),
                    'end'   => $carbon->copy()->endOfWeek()->endOfDay()->toDateTimeString(),
                ];
            case 'custom':
                return [
                    'start' => Carbon::parse($this->startDate ?: now()->subDays(7))->startOfDay()->toDateTimeString(),
                    'end'   => Carbon::parse($this->endDate ?: now())->endOfDay()->toDateTimeString(),
                ];
            case 'monthly':
            default:
                $carbon = Carbon::parse($this->selectedDate ?: now());
                return [
                    'start' => $carbon->startOfMonth()->toDateTimeString(),
                    'end'   => $carbon->copy()->endOfMonth()->endOfDay()->toDateTimeString(),
                ];
        }
    }

    protected function getRangeTitle(string $start, string $end): string
    {
        $s = Carbon::parse($start)->format('d.m.Y');
        $e = Carbon::parse($end)->format('d.m.Y');
        return $s === $e ? "Data: $s" : "Perioada: $s – $e";
    }
}
