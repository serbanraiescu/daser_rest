<x-filament-panels::page>
    @php
        $data = $this->getReportData();
        $kpis = $data['kpis'];
        $products = $data['products'];
        $waiters = $data['waiters'];
        $history = $data['history'];
        $rangeTitle = $data['range_title'];
        $currency = \App\Modules\Settings\Models\CompanySetting::first()->currency ?? 'RON';
    @endphp

    <div class="space-y-6">
        <!-- 1. Filters & Navigation Bar -->
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 gap-6">
            <div class="space-y-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                    </svg>
                    Analiză Performanță Vânzări
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                    {{ $rangeTitle }}
                </p>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 w-full xl:w-auto">
                <!-- Period Switcher -->
                <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-xl">
                    <button 
                        wire:click="$set('period', 'daily')" 
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ $period === 'daily' ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Zilnic
                    </button>
                    <button 
                        wire:click="$set('period', 'weekly')" 
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ $period === 'weekly' ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Săptămânal
                    </button>
                    <button 
                        wire:click="$set('period', 'monthly')" 
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ $period === 'monthly' ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Lunar
                    </button>
                    <button 
                        wire:click="$set('period', 'custom')" 
                        class="px-4 py-2 text-xs font-semibold rounded-lg transition-all {{ $period === 'custom' ? 'bg-white dark:bg-gray-600 shadow text-primary-600 dark:text-primary-400' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                    >
                        Personalizat
                    </button>
                </div>

                <!-- Date Inputs based on Period -->
                <div class="flex items-center gap-2">
                    @if($period !== 'custom')
                        <div class="relative w-full sm:w-auto">
                            <input 
                                type="date" 
                                wire:model.live="selectedDate" 
                                class="w-full sm:w-auto px-4 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white"
                            />
                        </div>
                    @else
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <input 
                                type="date" 
                                wire:model.live="startDate" 
                                class="w-full sm:w-auto px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 dark:text-white"
                                placeholder="De la"
                            />
                            <span class="text-gray-400 dark:text-gray-500 text-xs font-bold">-</span>
                            <input 
                                type="date" 
                                wire:model.live="endDate" 
                                class="w-full sm:w-auto px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-primary-500 dark:text-white"
                                placeholder="Până la"
                            />
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Revenue -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all group duration-300">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Vânzări Totale</p>
                    <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpis['total_revenue'], 2) }} <span class="text-sm font-bold text-gray-400 dark:text-gray-500">{{ $currency }}</span></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Exclusiv comenzi anulate</p>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all group duration-300">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-blue-500/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total Comenzi</p>
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $kpis['total_orders'] }}</p>
                    <div class="flex gap-2 items-center text-xs text-gray-400 dark:text-gray-500 mt-1">
                        <span class="text-emerald-500 font-semibold">{{ $kpis['successful_orders'] }} finale</span>
                        <span>•</span>
                        <span class="text-red-500 font-semibold">{{ $kpis['cancelled_orders'] }} anulate</span>
                    </div>
                </div>
            </div>

            <!-- Average Value -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all group duration-300">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Valoare Medie Comandă</p>
                    <div class="p-2.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($kpis['average_value'], 2) }} <span class="text-sm font-bold text-gray-400 dark:text-gray-500">{{ $currency }}</span></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Calculată din comenzi finalizate</p>
                </div>
            </div>

            <!-- Conversion/Efficiency -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all group duration-300">
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Rată Finalizare</p>
                    <div class="p-2.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    @php
                        $rate = $kpis['total_orders'] > 0 ? ($kpis['successful_orders'] / $kpis['total_orders']) * 100 : 0;
                    @endphp
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($rate, 1) }}%</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Procentaj comenzi finalizate cu succes</p>
                </div>
            </div>
        </div>

        <!-- 3. Tables Row: Top Products & Waiter Reports -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products Table -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    Top Produse Vândute
                </h3>

                <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-3">Produs</th>
                                <th class="px-6 py-3 text-center">Cantitate</th>
                                <th class="px-6 py-3 text-right">Total Venituri</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($products as $prod)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-3 font-semibold text-gray-800 dark:text-gray-200">{{ $prod->name }}</td>
                                    <td class="px-6 py-3 text-center text-gray-900 dark:text-white font-bold">{{ $prod->quantity_sold }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($prod->revenue, 2) }} {{ $currency }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                        Niciun produs vândut în perioada selectată.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sales By Waiter Table -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Performanță pe Ospătari
                </h3>

                <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-3">Ospătar</th>
                                <th class="px-6 py-3 text-center">Număr Comenzi</th>
                                <th class="px-6 py-3 text-right">Vânzări Totale</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($waiters as $waiter)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-3 font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $waiter->waiter_name }}
                                    </td>
                                    <td class="px-6 py-3 text-center text-gray-900 dark:text-white font-bold">{{ $waiter->orders_count }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($waiter->total_sales, 2) }} {{ $currency }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                        Nicio activitate înregistrată în perioada selectată.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. Detailed Order History Table -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Istoric Detaliat Comenzi
            </h3>

            <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold">
                        <tr>
                            <th class="px-6 py-3">Număr Comandă</th>
                            <th class="px-6 py-3">Masă</th>
                            <th class="px-6 py-3">Metodă Plată</th>
                            <th class="px-6 py-3">Dată & Oră</th>
                            <th class="px-6 py-3 text-right">Valoare</th>
                            <th class="px-6 py-3">Ospătar</th>
                            <th class="px-6 py-3 text-center">Stare</th>
                            <th class="px-6 py-3 text-center">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($history as $order)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-3 font-semibold text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-bold text-gray-700 dark:text-gray-300">
                                        Masa {{ $order->table_number }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 uppercase text-xs font-bold text-gray-600 dark:text-gray-400">
                                    {{ $order->payment_method === 'cash' ? 'Cash' : ($order->payment_method === 'card' ? 'Card' : 'Online') }}
                                </td>
                                <td class="px-6 py-3 text-xs">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-6 py-3 text-right font-bold text-gray-900 dark:text-white">{{ number_format($order->total, 2) }} {{ $currency }}</td>
                                <td class="px-6 py-3 font-medium">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ \App\Filament\Pages\OrderReports::resolveWaiterName($order) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    @php
                                        $statusConfig = [
                                            'paid' => ['label' => 'Achitată', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400'],
                                            'delivered' => ['label' => 'Livrată', 'class' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/30 dark:text-teal-400'],
                                            'pending' => ['label' => 'În Așteptare', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400'],
                                            'preparing' => ['label' => 'În Pregătire', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400'],
                                            'ready' => ['label' => 'Pregătită', 'class' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-400'],
                                            'cancelled' => ['label' => 'Anulată', 'class' => 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400'],
                                        ][$order->status] ?? ['label' => $order->status, 'class' => 'bg-gray-100 text-gray-700'];
                                    @endphp
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusConfig['class'] }}">
                                        {{ $statusConfig['label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <button 
                                        wire:click="viewOrderItems({{ $order->id }})" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 border border-primary-100 dark:border-primary-900/50 hover:bg-primary-50 dark:hover:bg-primary-950/30 rounded-xl transition-all"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Vezi Produse
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                    Nicio comandă înregistrată în perioada selectată.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Dynamic Order Details Modal -->
    @if($showOrderModal && $selectedOrderItems)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 dark:bg-black/80 backdrop-blur-sm animate-fade-in">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden transform transition-all duration-300 scale-100 flex flex-col max-h-[85vh]">
                
                <!-- Modal Header -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white">
                            Detalii Comandă: {{ $selectedOrderNumber }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Produse adăugate în această comandă</p>
                    </div>
                    <button 
                        wire:click="closeOrderModal" 
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all border border-gray-100 dark:border-gray-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Items List) -->
                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-3">Produs</th>
                                    <th class="px-6 py-3 text-center">Cantitate</th>
                                    <th class="px-6 py-3 text-right">Preț Unitar</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @php $modalTotal = 0; @endphp
                                @foreach($selectedOrderItems as $item)
                                    @php 
                                        $subtotal = $item['price'] * $item['quantity'];
                                        $modalTotal += $subtotal;
                                    @endphp
                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['name'] }}</p>
                                            @if(!empty($item['notes']))
                                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded-lg inline-block">
                                                    Notă: {{ $item['notes'] }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{{ $item['quantity'] }}</td>
                                        <td class="px-6 py-4 text-right">{{ number_format($item['price'], 2) }} {{ $currency }}</td>
                                        <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                            {{ number_format($subtotal, 2) }} {{ $currency }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-6 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Total Comandă</p>
                    <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ number_format($modalTotal, 2) }} {{ $currency }}
                    </p>
                </div>

            </div>
        </div>
    @endif
</x-filament-panels::page>
