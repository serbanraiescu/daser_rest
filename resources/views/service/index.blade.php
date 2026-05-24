@extends('layouts.service')

@section('content')
<div class="min-h-screen bg-gray-950 flex flex-col h-screen overflow-hidden select-none" x-data="{
    categories: {{ $categories->toJson() }},
    activeCategoryId: {{ $categories->first()?->id ?? 'null' }},
    openOrders: {{ $openOrders->toJson() }},
    cart: [],
    
    // Form fields
    orderId: null,
    vehicleNumber: '',
    customerName: '',
    customerPhone: '',
    notes: '',
    
    // Modal states
    showPaymentModal: false,
    paymentMethod: 'cash',
    paymentNotes: '',
    
    // Cart operations
    addToCart(item) {
        let existing = this.cart.find(c => c.service_item_id === item.id);
        if (existing) {
            existing.quantity++;
        } else {
            this.cart.push({
                service_item_id: item.id,
                name: item.name,
                unit_price: item.price,
                quantity: 1,
                unit: item.unit ?? 'buc',
                notes: ''
            });
        }
    },
    
    updateQuantity(itemId, amount) {
        let item = this.cart.find(c => c.service_item_id === itemId);
        if (item) {
            item.quantity = parseFloat(item.quantity) + amount;
            if (item.quantity <= 0) {
                this.cart = this.cart.filter(c => c.service_item_id !== itemId);
            }
        }
    },
    
    getCartTotal() {
        return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    },
    
    clearForm() {
        this.orderId = null;
        this.cart = [];
        this.vehicleNumber = '';
        this.customerName = '';
        this.customerPhone = '';
        this.notes = '';
    },
    
    loadOrder(order) {
        this.clearForm();
        this.orderId = order.id;
        this.vehicleNumber = order.vehicle_number ?? '';
        this.customerName = order.customer_name ?? '';
        this.customerPhone = order.customer_phone ?? '';
        this.notes = order.notes ?? '';
        
        order.items.forEach(item => {
            this.cart.push({
                service_item_id: item.service_item_id,
                name: item.name,
                unit_price: parseFloat(item.unit_price),
                quantity: parseFloat(item.quantity),
                unit: item.unit ?? 'buc',
                notes: item.notes ?? ''
            });
        });
    },
    
    async saveOrder(shouldComplete = false) {
        if (this.cart.length === 0) {
            alert('Coșul este gol! Adaugă cel puțin un serviciu.');
            return;
        }
        
        try {
            const response = await fetch('/service/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    order_id: this.orderId,
                    customer_name: this.customerName,
                    customer_phone: this.customerPhone,
                    vehicle_number: this.vehicleNumber,
                    notes: this.notes,
                    items: this.cart
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.orderId = data.order.id;
                
                if (shouldComplete) {
                    this.showPaymentModal = true;
                } else {
                    alert('Comandă salvată cu succes ca Deschisă!');
                    window.location.reload();
                }
            } else {
                alert('Eroare: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Eroare la salvarea comenzii.');
        }
    },
    
    async finalizePayment() {
        if (!this.orderId) return;
        
        try {
            const response = await fetch(`/service/orders/${this.orderId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    payment_method: this.paymentMethod,
                    notes: this.paymentNotes
                })
            });
            
            const data = await response.json();
            if (data.success) {
                alert('Comandă finalizată și plătită cu succes!');
                this.showPaymentModal = false;
                window.location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Eroare la procesarea plății.');
        }
    },
    
    async cancelCurrentOrder() {
        if (!this.orderId) return;
        if (!confirm('Ești sigur că vrei să ANULEZI complet această comandă?')) return;
        
        try {
            const response = await fetch(`/service/orders/${this.orderId}/cancel`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            });
            
            const data = await response.json();
            if (data.success) {
                alert('Comandă anulată!');
                window.location.reload();
            } else {
                alert('Eroare: ' + data.message);
            }
        } catch (e) {
            console.error(e);
            alert('Eroare la anularea comenzii.');
        }
    }
}">

    <!-- Top Navigation Header -->
    <header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center font-bold text-gray-950 text-xl shadow-md">S</div>
            <div>
                <h1 class="text-lg font-black tracking-tight text-white uppercase">{{ $settings->site_name ?? 'Restaurant OS' }}</h1>
                <p class="text-xs text-amber-500 font-bold uppercase tracking-wider">Service Module</p>
            </div>
        </div>
        
        <!-- Center Quick Info / Date -->
        <div class="hidden md:flex items-center gap-4 text-sm text-gray-400">
            <span class="bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-700">Operator: <strong class="text-white">{{ session('staff_name') }}</strong></span>
            <span class="bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-700">Tura: <strong class="text-white">{{ now()->format('d.m.Y') }}</strong></span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
            <a href="{{ route('service.print-daily-report') }}" target="_blank" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-xl text-sm font-bold text-gray-200 transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Raport Zilnic
            </a>
            
            <form action="{{ route('staff.logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-950/40 hover:bg-red-900 border border-red-800/80 rounded-xl text-sm font-bold text-red-200 transition-all cursor-pointer">
                    Deconectare
                </button>
            </form>
        </div>
    </header>

    <!-- Main Content Layout Split -->
    <main class="flex-1 flex overflow-hidden">

        <!-- LEFT SIDE: Catalog & Categories -->
        <section class="flex-1 flex flex-col overflow-hidden bg-gray-950 p-6">
            <!-- Category Selector Tabs -->
            <div class="flex gap-2 overflow-x-auto pb-4 no-scrollbar border-b border-gray-800 mb-6">
                <template x-for="cat in categories" :key="cat.id">
                    <button 
                        @click="activeCategoryId = cat.id"
                        class="px-5 py-3 rounded-2xl font-bold text-sm tracking-wide transition-all whitespace-nowrap cursor-pointer shadow-md"
                        :class="activeCategoryId === cat.id ? 'bg-amber-500 text-gray-950 font-black scale-105' : 'bg-gray-900 hover:bg-gray-800 text-gray-300'"
                        x-text="cat.name"
                    ></button>
                </template>
            </div>

            <!-- Active Category Services Grid -->
            <div class="flex-1 overflow-y-auto no-scrollbar pb-10">
                <template x-for="cat in categories" :key="cat.id">
                    <div x-show="activeCategoryId === cat.id" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                        <template x-for="item in cat.items" :key="item.id">
                            <div 
                                @click="addToCart(item)"
                                class="bg-gray-900 border border-gray-800 rounded-3xl p-5 hover:border-amber-500/50 hover:shadow-lg transition-all cursor-pointer transform hover:-translate-y-1 select-none flex flex-col justify-between min-h-[140px] group"
                            >
                                <div>
                                    <div class="flex justify-between items-start">
                                        <h3 class="font-extrabold text-white text-base group-hover:text-amber-400 transition-colors" x-text="item.name"></h3>
                                        <span class="px-2 py-0.5 bg-gray-800 rounded-lg text-gray-400 text-[10px] font-bold uppercase tracking-wider" x-text="item.unit"></span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2 line-clamp-2" x-text="item.description || 'Fără descriere adițională.'"></p>
                                </div>
                                
                                <div class="mt-4 flex justify-between items-end border-t border-gray-800/60 pt-3">
                                    <span class="text-xs text-gray-400 font-semibold">Tarif standard</span>
                                    <span class="text-lg font-black text-white"><span x-text="item.price.toFixed(2)"></span> <span class="text-xs text-amber-500 font-bold">{{ $settings->currency ?? 'RON' }}</span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Active / Open Orders Section -->
            <div class="h-[220px] bg-gray-900 border-t border-gray-800 rounded-t-3xl p-5 flex flex-col">
                <h2 class="text-sm font-black text-amber-500 uppercase tracking-wider mb-3">Comenzi Active în Service (Deschise)</h2>
                <div class="flex-1 overflow-x-auto flex gap-4 pb-2 no-scrollbar">
                    <template x-for="order in openOrders" :key="order.id">
                        <div 
                            @click="loadOrder(order)"
                            class="bg-gray-950 border border-gray-800/80 hover:border-amber-500/50 rounded-2xl p-4 w-[240px] flex-shrink-0 cursor-pointer transition-all hover:shadow-md flex flex-col justify-between relative group"
                            :class="orderId === order.id ? 'border-amber-500 bg-amber-500/5' : ''"
                        >
                            <!-- Badge index -->
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs font-black text-white uppercase" x-text="order.vehicle_number || 'FĂRĂ NR.'"></span>
                                    <p class="text-[10px] text-gray-400 mt-0.5 line-clamp-1" x-text="order.customer_name || 'Client ocazional'"></p>
                                </div>
                                <span class="text-[10px] bg-amber-500/10 text-amber-500 border border-amber-500/20 px-2 py-0.5 rounded-full font-bold uppercase">DESCHISĂ</span>
                            </div>
                            
                            <div class="mt-4 flex justify-between items-end border-t border-gray-800/60 pt-2">
                                <span class="text-[9px] text-gray-400" x-text="new Date(order.created_at).toLocaleTimeString('ro-RO', {hour: '2-digit', minute:'2-digit'})"></span>
                                <span class="text-sm font-black text-white"><span x-text="parseFloat(order.total).toFixed(2)"></span> <span class="text-[10px] text-amber-500">{{ $settings->currency ?? 'RON' }}</span></span>
                            </div>
                        </div>
                    </template>
                    
                    <template x-if="openOrders.length === 0">
                        <div class="flex-1 flex items-center justify-center text-gray-500 text-xs">
                            Nicio comandă deschisă în acest moment. Puteți iniția una nouă din dreapta!
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- RIGHT SIDE: Cart / Ticket Detail -->
        <section class="w-[420px] bg-gray-900 border-l border-gray-800 flex flex-col h-full shadow-2xl relative">
            <div class="p-5 border-b border-gray-800 flex justify-between items-center bg-gray-900/50">
                <div>
                    <h2 class="text-base font-black text-white uppercase tracking-tight" x-text="orderId ? 'Modificare Comandă #' + orderId : 'Comandă Nouă'"></h2>
                    <p class="text-xs text-gray-400 mt-0.5">Adaugă servicii în partea stângă</p>
                </div>
                <button 
                    @click="clearForm()" 
                    class="p-2 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-xl transition-all cursor-pointer border border-gray-700"
                    title="Coș nou"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <!-- Customer & Car details inputs -->
            <div class="p-5 border-b border-gray-800 space-y-3 bg-gray-950/20">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Număr Mașină *</label>
                        <input 
                            type="text" 
                            x-model="vehicleNumber" 
                            class="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white uppercase font-bold focus:border-amber-500 focus:outline-none placeholder-gray-600" 
                            placeholder="B-99-AAA"
                        >
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Nume Client</label>
                        <input 
                            type="text" 
                            x-model="customerName" 
                            class="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none placeholder-gray-600" 
                            placeholder="ex: Ion Popescu"
                        >
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Telefon Client</label>
                        <input 
                            type="text" 
                            x-model="customerPhone" 
                            class="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none placeholder-gray-600" 
                            placeholder="ex: 0722000000"
                        >
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Observații</label>
                        <input 
                            type="text" 
                            x-model="notes" 
                            class="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none placeholder-gray-600" 
                            placeholder="ex: Verifică janta"
                        >
                    </div>
                </div>
            </div>

            <!-- Cart Items list -->
            <div class="flex-1 overflow-y-auto p-5 space-y-4 no-scrollbar">
                <template x-for="item in cart" :key="item.service_item_id">
                    <div class="bg-gray-950 border border-gray-800/80 rounded-2xl p-4 space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-extrabold text-white text-xs" x-text="item.name"></h4>
                                <span class="text-[10px] text-gray-400"><span x-text="item.unit_price.toFixed(2)"></span> / <span x-text="item.unit"></span></span>
                            </div>
                            <span class="text-xs font-black text-white"><span x-text="(item.unit_price * item.quantity).toFixed(2)"></span> <span class="text-[10px] text-amber-500">{{ $settings->currency ?? 'RON' }}</span></span>
                        </div>
                        
                        <!-- Controls qty & notes -->
                        <div class="flex justify-between items-center gap-4 border-t border-gray-800/60 pt-3">
                            <input 
                                type="text" 
                                x-model="item.notes" 
                                class="bg-transparent border-0 border-b border-gray-800 text-[10px] text-gray-400 focus:border-amber-500 focus:outline-none flex-1 placeholder-gray-700" 
                                placeholder="Notă serviciu (ex: stânga față)"
                            >
                            
                            <div class="flex items-center bg-gray-900 border border-gray-800 rounded-xl p-0.5">
                                <button @click="updateQuantity(item.service_item_id, -1)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 font-extrabold cursor-pointer">-</button>
                                <span class="px-3 text-xs font-extrabold text-white" x-text="item.quantity"></span>
                                <button @click="updateQuantity(item.service_item_id, 1)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-800 font-extrabold cursor-pointer">+</button>
                            </div>
                        </div>
                    </div>
                </template>
                
                <template x-if="cart.length === 0">
                    <div class="h-full flex flex-col items-center justify-center text-gray-600 py-20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-700 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="text-xs font-bold uppercase tracking-wider">Coșul de servicii este gol</p>
                        <p class="text-[10px] text-gray-500 mt-1">Alege un serviciu din stânga</p>
                    </div>
                </template>
            </div>

            <!-- Footer: Summary & Actions -->
            <div class="p-5 border-t border-gray-800 bg-gray-950/80 backdrop-blur-md">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Total General</span>
                    <span class="text-2xl font-black text-white"><span x-text="getCartTotal().toFixed(2)"></span> <span class="text-sm text-amber-500 font-bold">{{ $settings->currency ?? 'RON' }}</span></span>
                </div>
                
                <div class="grid grid-cols-2 gap-3" x-show="cart.length > 0">
                    <!-- If new order or edit -->
                    <button 
                        @click="saveOrder(false)" 
                        class="w-full py-3.5 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded-2xl text-xs font-bold text-gray-200 uppercase tracking-wide transition-all cursor-pointer text-center"
                    >
                        Salvează Deschisă
                    </button>
                    
                    <button 
                        @click="saveOrder(true)" 
                        class="w-full py-3.5 bg-amber-500 hover:bg-amber-400 rounded-2xl text-xs font-black text-gray-950 uppercase tracking-wide transition-all cursor-pointer text-center shadow-lg shadow-amber-500/10"
                    >
                        Finalizează (Plată)
                    </button>
                </div>
                
                <!-- Cancel order button if editing an open order -->
                <div class="mt-3" x-show="orderId !== null && cart.length > 0">
                    <button 
                        @click="cancelCurrentOrder()" 
                        class="w-full py-2 bg-red-950/30 hover:bg-red-900 border border-red-900/60 rounded-xl text-[10px] font-bold text-red-300 uppercase tracking-wider transition-all cursor-pointer text-center"
                    >
                        Anulează Comanda Complet
                    </button>
                </div>
            </div>
        </section>
    </main>

    <!-- Payment Modal Dialog (Alpine state) -->
    <div 
        x-show="showPaymentModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm"
        x-cloak
    >
        <div class="bg-gray-900 border border-gray-800 rounded-3xl w-full max-w-md p-6 overflow-hidden shadow-2xl space-y-6">
            <div class="flex justify-between items-center border-b border-gray-800 pb-4">
                <div>
                    <h3 class="text-base font-black text-white uppercase tracking-tight">Finalizare Plată</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Alege metoda de plată pentru client</p>
                </div>
                <button 
                    @click="showPaymentModal = false" 
                    class="p-1 bg-gray-850 hover:bg-gray-800 text-gray-500 hover:text-white rounded-lg transition-all cursor-pointer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <!-- Big Cash/Card selector -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Metodă Plată</label>
                <div class="grid grid-cols-3 gap-2">
                    <button 
                        @click="paymentMethod = 'cash'"
                        class="p-4 rounded-2xl border text-xs font-black uppercase tracking-wider transition-all cursor-pointer"
                        :class="paymentMethod === 'cash' ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 scale-105' : 'bg-gray-950 border-gray-800 text-gray-400 hover:bg-gray-850'"
                    >
                        💰 Cash
                    </button>
                    <button 
                        @click="paymentMethod = 'card'"
                        class="p-4 rounded-2xl border text-xs font-black uppercase tracking-wider transition-all cursor-pointer"
                        :class="paymentMethod === 'card' ? 'bg-blue-500/10 border-blue-500 text-blue-400 scale-105' : 'bg-gray-950 border-gray-800 text-gray-400 hover:bg-gray-850'"
                    >
                        💳 Card / POS
                    </button>
                    <button 
                        @click="paymentMethod = 'mixed'"
                        class="p-4 rounded-2xl border text-xs font-black uppercase tracking-wider transition-all cursor-pointer"
                        :class="paymentMethod === 'mixed' ? 'bg-amber-500/10 border-amber-500 text-amber-400 scale-105' : 'bg-gray-950 border-gray-800 text-gray-400 hover:bg-gray-850'"
                    >
                        🔀 Mixtă
                    </button>
                </div>
            </div>
            
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1">Mențiuni Plată</label>
                <input 
                    type="text" 
                    x-model="paymentNotes" 
                    class="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-3 text-xs text-white focus:border-amber-500 focus:outline-none placeholder-gray-700" 
                    placeholder="ex: Rest dat 20 lei sau detaliu mixt"
                >
            </div>
            
            <div class="bg-gray-950/80 rounded-2xl p-4 border border-gray-800/80 flex justify-between items-center">
                <span class="text-xs font-bold text-gray-400 uppercase">Valoare de plată</span>
                <span class="text-xl font-black text-white"><span x-text="getCartTotal().toFixed(2)"></span> <span class="text-xs text-amber-500 font-bold">{{ $settings->currency ?? 'RON' }}</span></span>
            </div>
            
            <button 
                @click="finalizePayment()" 
                class="w-full py-4 bg-emerald-500 hover:bg-emerald-400 rounded-2xl text-xs font-extrabold text-gray-950 uppercase tracking-wider transition-all cursor-pointer text-center shadow-lg shadow-emerald-500/10"
            >
                Confirmă Plată & Închide Comanda
            </button>
        </div>
    </div>

</div>
@endsection
