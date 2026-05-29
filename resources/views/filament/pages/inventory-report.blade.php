<x-filament-panels::page>
    {{-- Filtre perioadă --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Perioadă</label>
                <select wire:model.live="period"
                    class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-amber-400 px-3 py-2">
                    <option value="daily">Azi</option>
                    <option value="weekly">Săptămână</option>
                    <option value="monthly">Lună</option>
                    <option value="custom">Personalizat</option>
                </select>
            </div>

            @if(in_array($period, ['daily', 'weekly', 'monthly']))
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Dată referință</label>
                    <input type="date" wire:model.live="selectedDate"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2">
                </div>
            @endif

            @if($period === 'custom')
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">De la</label>
                    <input type="date" wire:model.live="startDate"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Până la</label>
                    <input type="date" wire:model.live="endDate"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg px-3 py-2">
                </div>
            @endif
        </div>
    </div>

    @php $report = $this->getReportData(); @endphp

    {{-- Titlu perioadă --}}
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 font-medium">{{ $report['range_title'] }}</p>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl p-4">
            <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">Total Intrări</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($report['total_in'], 3) }}</p>
            <p class="text-xs text-green-500 mt-1">unități primite</p>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-700 rounded-xl p-4">
            <p class="text-xs text-orange-600 dark:text-orange-400 font-medium mb-1">Total Ieșiri</p>
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ number_format($report['total_out'], 3) }}</p>
            <p class="text-xs text-orange-500 mt-1">unități consumate</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-4">
            <p class="text-xs text-red-600 dark:text-red-400 font-medium mb-1">Stoc Scăzut</p>
            <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $report['low_stock']->count() }}</p>
            <p class="text-xs text-red-500 mt-1">produse sub minim</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium mb-1">Produse Consumate</p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $report['consumption']->count() }}</p>
            <p class="text-xs text-blue-500 mt-1">tipuri de produse</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Produse cu stoc scăzut --}}
        @if($report['low_stock']->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-700 overflow-hidden">
            <div class="px-4 py-3 bg-red-50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-700">
                <h3 class="font-semibold text-red-700 dark:text-red-300 text-sm flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                    Alertă Stoc Scăzut
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($report['low_stock'] as $item)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->name }}</p>
                        <p class="text-xs text-gray-500">Minim: {{ $item->minimum_stock }} {{ $item->unit }}</p>
                    </div>
                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs font-bold rounded-full">
                        {{ number_format($item->current_stock, 3) }} {{ $item->unit }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Top Consum --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Top Consum Perioadă</h3>
            </div>
            @if($report['consumption']->isEmpty())
                <div class="px-4 py-8 text-center text-sm text-gray-400">Nicio vânzare înregistrată.</div>
            @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($report['consumption']->take(10) as $row)
                <div class="px-4 py-3 flex items-center justify-between">
                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $row->inventoryItem?->name ?? 'N/A' }}</p>
                    <div class="text-right">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($row->total_consumed, 3) }}
                        </span>
                        <span class="text-xs text-gray-500 ml-1">{{ $row->inventoryItem?->unit }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Mișcări recente --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Mișcări Stoc în Perioadă</h3>
            <span class="text-xs text-gray-400">{{ $report['movements']->count() }} înregistrări</span>
        </div>
        @if($report['movements']->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-gray-400">Nicio mișcare în această perioadă.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Produs</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tip</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantitate</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stoc Înainte</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stoc După</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notă</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($report['movements']->take(50) as $m)
                    @php
                        $typeLabels = \App\Modules\Inventory\Models\StockMovement::TYPES;
                        $typeColors = [
                            'in'         => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            'sale'       => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            'out'        => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                            'adjustment' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'waste'      => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">
                            {{ $m->inventoryItem?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $typeColors[$m->type] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $typeLabels[$m->type] ?? $m->type }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right font-mono font-bold {{ (float)$m->quantity >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ (float)$m->quantity >= 0 ? '+' : '' }}{{ number_format($m->quantity, 3) }}
                        </td>
                        <td class="px-4 py-2 text-right text-gray-500 font-mono text-xs">
                            {{ number_format($m->stock_before, 3) }}
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-xs font-bold text-gray-800 dark:text-gray-200">
                            {{ number_format($m->stock_after, 3) }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs max-w-xs truncate">
                            {{ $m->note ?? '—' }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">
                            {{ $m->created_at->format('d.m.Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-filament-panels::page>
