@extends('layouts.staff')

@section('content')
<div class="min-h-screen bg-gray-50 pb-24" x-data="{ 
    showPayment: false,
    paymentMethod: 'cash',
    isFiscal: false,
    fiscalData: {
        company_name: '',
        cui: '',
        reg_com: '',
        address: '',
        bank_name: '',
        iban: ''
    }
}">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-3xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('waiter.index') }}" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-900">{{ $table->name }}</h1>
                <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Ocupat</span>
            </div>
            
            <a href="{{ route('waiter.menu', $table->id) }}" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg hover:bg-orange-700 transition-colors">
                + Produse
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto p-4 space-y-4">
        <!-- Order Info -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Comanda #{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $order->created_at->format('H:i - d.m.Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-widest font-bold">Total de plată</p>
                    <p class="text-3xl font-black text-gray-900 tracking-tight">{{ number_format($order->total, 2) }} <span class="text-lg font-bold">{{ $settings->currency ?? 'RON' }}</span></p>
                </div>
            </div>

            <!-- Items List -->
            <div class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <div class="py-4 flex justify-between items-center group">
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center gap-1">
                                <form action="{{ route('waiter.order.item.update', $item->id) }}" method="POST" class="flex flex-col items-center">
                                    @csrf
                                    <button name="quantity" value="{{ $item->quantity + 1 }}" class="text-orange-400 hover:text-orange-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <div class="bg-orange-50 text-orange-700 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm">
                                        {{ $item->quantity }}
                                    </div>
                                    @if($item->quantity > 1)
                                        <button name="quantity" value="{{ $item->quantity - 1 }}" class="text-orange-400 hover:text-orange-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    @endif
                                </form>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-gray-900">{{ $item->name }}</h4>
                                    <form action="{{ route('waiter.order.item.remove', $item->id) }}" method="POST" onsubmit="return confirm('Sigur doriți să eliminați acest produs?')">
                                        @csrf
                                        <button class="text-red-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                                @if($item->variation)
                                    <p class="text-xs text-gray-500">{{ $item->variation->name }}</p>
                                @endif
                                @if($item->notes)
                                    <p class="text-xs italic text-gray-400 mt-1">"{{ $item->notes }}"</p>
                                @endif
                            </div>
                        </div>
                        <p class="font-bold text-gray-700">{{ number_format($item->price * $item->quantity, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('waiter.order.print', $order->id) }}" target="_blank" class="bg-white border-2 border-gray-200 text-gray-700 font-bold py-4 rounded-2xl flex flex-col items-center justify-center gap-2 hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Nota de plată
            </a>
            <button @click="showPayment = true" class="bg-green-600 text-white font-bold py-4 rounded-2xl flex flex-col items-center justify-center gap-2 shadow-lg hover:bg-green-700 transition-colors shadow-green-600/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Incasare
            </button>
        </div>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPayment" 
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         x-cloak>
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPayment = false"></div>
        
        <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-900">Finalizare Plată</h3>
                <button @click="showPayment = false" class="text-gray-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('waiter.order.pay', $order->id) }}" method="POST" class="p-6 overflow-y-auto space-y-6">
                @csrf
                
                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 uppercase mb-3">Metoda de Plată</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex flex-col items-center p-4 border-2 rounded-2xl cursor-pointer transition-all"
                               :class="paymentMethod === 'cash' ? 'border-orange-600 bg-orange-50' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" name="payment_method" value="cash" x-model="paymentMethod" class="hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="paymentMethod === 'cash' ? 'text-orange-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="font-bold" :class="paymentMethod === 'cash' ? 'text-orange-900' : 'text-gray-500'">Cash</span>
                        </label>

                        <label class="relative flex flex-col items-center p-4 border-2 rounded-2xl cursor-pointer transition-all"
                               :class="paymentMethod === 'card' ? 'border-orange-600 bg-orange-50' : 'border-gray-100 hover:border-gray-200'">
                            <input type="radio" name="payment_method" value="card" x-model="paymentMethod" class="hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="paymentMethod === 'card' ? 'text-orange-600' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="font-bold" :class="paymentMethod === 'card' ? 'text-orange-900' : 'text-gray-500'">Card</span>
                        </label>
                    </div>
                </div>

                <!-- Fiscal Invoice Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="bg-gray-200 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">Factură Fiscală</p>
                            <p class="text-xs text-gray-500">Solicită date firmă</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_fiscal" value="1" x-model="isFiscal" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>

                <!-- Fiscal Form -->
                <div x-show="isFiscal" x-transition class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 gap-4">
                        <input type="text" name="fiscal_data[company_name]" placeholder="Nume Companie" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:bg-white transition-all" :required="isFiscal">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="text" name="fiscal_data[cui]" placeholder="CUI" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:bg-white transition-all" :required="isFiscal">
                            <input type="text" name="fiscal_data[reg_com]" placeholder="Nr. Reg. Com." class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:bg-white transition-all" :required="isFiscal">
                        </div>
                        <input type="text" name="fiscal_data[address]" placeholder="Adresă Sediu" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:bg-white transition-all" :required="isFiscal">
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-orange-600 text-white font-bold py-4 rounded-2xl shadow-lg hover:bg-orange-700 transition-all flex items-center justify-center gap-3">
                        Înregistrează Încasarea
                        <span class="bg-black/20 px-2 py-0.5 rounded">{{ number_format($order->total, 2) }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
