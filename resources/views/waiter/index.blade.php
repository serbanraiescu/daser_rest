@extends('layouts.waiter')

@section('content')
<div class="min-h-screen bg-gray-50 h-screen overflow-hidden select-none" x-data="{
    activeAreaId: {{ $areas->first()?->id ?? 'null' }},
    areas: {{ $areas->toJson() }},
    showTablePopup: false,
    selectedTable: null,
    
    // Order Modal State
    showOrderModal: false,
    orderDetails: null,
    isLoadingOrder: false,

    // Menu Modal State
    showMenuModal: false,
    categories: {{ $categories->toJson() }},
    activeCategoryId: {{ $categories->first()?->id ?? 'null' }},
    cart: [],
    isSendingOrder: false,
    showVariations: false,
    selectedProduct: null,
    showMobileCart: false,
    searchQuery: '',

    // Daily Report State
    showReportModal: false,
    reportData: null,
    isLoadingReport: false,

    // Fiscal Data State
    isFiscal: false,
    fiscalData: { company_name: '', cui: '', reg_com: '', address: '' },
    paymentMethod: 'cash',
    
    getActiveTables() {
        const area = this.areas.find(a => a.id === this.activeAreaId);
        return area ? area.tables : [];
    },

    openTable(table) {
        this.selectedTable = table;
        this.showTablePopup = true;
    },

    async viewOrder() {
        if (!this.selectedTable) return;
        this.showTablePopup = false;
        this.showOrderModal = true;
        this.isLoadingOrder = true;
        
        try {
            const response = await fetch(`/waiter/api/order/${this.selectedTable.id}`);
            const data = await response.json();
            if (data.success) {
                this.orderDetails = data.order;
            } else {
                alert('Eroare: ' + data.message);
                this.showOrderModal = false;
            }
        } catch (e) {
            alert('Eroare de rețea.');
            this.showOrderModal = false;
        } finally {
            this.isLoadingOrder = false;
        }
    },

    async openReport() {
        this.showReportModal = true;
        this.isLoadingReport = true;
        this.reportData = null;
        try {
            const response = await fetch('/waiter/api/daily-report');
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (err) {
                console.error('Response was not JSON:', text);
                let errorMsg = text.substring(0, 300);
                if (text.includes('<title>')) {
                    const match = text.match(/<title>(.*?)<\/title>/);
                    if (match && match[1]) {
                        errorMsg = match[1];
                    }
                }
                alert('Eroare la încărcarea raportului: ' + errorMsg);
                this.showReportModal = false;
                return;
            }
            if (data.success) {
                this.reportData = data.report;
            } else {
                alert('Eroare: ' + data.message);
                this.showReportModal = false;
            }
        } catch (e) {
            alert('Eroare de rețea sau de sistem: ' + e.message);
            this.showReportModal = false;
        } finally {
            this.isLoadingReport = false;
        }
    },

    // Menu Logic
    openMenu() {
        this.showTablePopup = false;
        this.showOrderModal = false;
        this.showMenuModal = true;
        this.cart = [];
        this.showMobileCart = false;
        this.searchQuery = '';
    },

    getProducts() {
        if (this.searchQuery && this.searchQuery.trim() !== '') {
            const query = this.searchQuery.toLowerCase().trim();
            let results = [];
            this.categories.forEach(cat => {
                cat.products.forEach(prod => {
                    if (prod.name.toLowerCase().includes(query)) {
                        results.push(prod);
                    }
                });
            });
            return results;
        }
        const cat = this.categories.find(c => c.id === this.activeCategoryId);
        return cat ? cat.products : [];
    },

    getTotal() {
        return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
    },

    selectProduct(product) {
        if (product.variations && product.variations.length > 0) {
            this.selectedProduct = product;
            this.showVariations = true;
        } else {
            this.addToCart(product);
        }
    },

    addToCart(product, variation = null) {
        const cartKey = variation ? `${product.id}-${variation.id}` : product.id;
        const existing = this.cart.find(item => item.cartKey === cartKey);
        
        if (existing) {
            existing.quantity++;
        } else {
            this.cart.push({
                cartKey: cartKey,
                id: product.id,
                name: variation ? `${product.name} (${variation.name})` : product.name,
                price: variation ? parseFloat(product.price) + parseFloat(variation.price) : parseFloat(product.price),
                quantity: 1,
                variation_id: variation ? variation.id : null,
                notes: ''
            });
        }
        this.showVariations = false;
        this.selectedProduct = null;
    },

    async sendOrder() {
        if (this.cart.length === 0 || !this.selectedTable) return;
        this.isSendingOrder = true;
        try {
            const response = await fetch('{{ route('waiter.order.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    items: this.cart,
                    table_number: this.selectedTable.name,
                    payment_method: 'cash'
                })
            });
            const result = await response.json();
            if (result.success) {
                window.location.reload();
            } else { alert('Eroare: ' + result.message); }
        } catch (e) { alert('Eroare de rețea.'); }
        finally { this.isSendingOrder = false; }
    },

    // Global Order Management
    async updateItemQty(itemId, newQty) {
        if (newQty < 1) return;
        try {
            const response = await fetch(`/waiter/order/item/${itemId}/update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ quantity: newQty })
            });
            this.viewOrder();
        } catch (e) { alert('Eroare la actualizare.'); }
    },

    async removeItem(itemId) {
        if (!confirm('Elimini produsul?')) return;
        try {
            const response = await fetch(`/waiter/order/item/${itemId}/remove`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            this.viewOrder();
        } catch (e) { alert('Eroare la eliminare.'); }
    },

    async payOrder() {
        if (!this.orderDetails) return;
        try {
            const response = await fetch(`/waiter/order/${this.orderDetails.id}/pay`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    payment_method: this.paymentMethod,
                    is_fiscal: this.isFiscal,
                    fiscal_data: this.fiscalData
                })
            });
            window.location.reload();
        } catch (e) { alert('Eroare la plată.'); }
    },

    // Split Order State
    isSplitting: false,
    splitItems: {}, // format: { itemId: quantity }

    toggleSplit() {
        this.isSplitting = !this.isSplitting;
        this.splitItems = {};
        this.paymentMethod = 'cash'; // Reset to cash default
    },

    toggleItemSplit(item) {
        if (this.splitItems[item.id]) {
            delete this.splitItems[item.id];
        } else {
            this.splitItems[item.id] = item.quantity;
        }
    },

    getSplitTotal() {
        let total = 0;
        if (!this.orderDetails || !this.orderDetails.items) return 0;
        
        for (const [itemId, qty] of Object.entries(this.splitItems)) {
            const item = this.orderDetails.items.find(i => i.id == itemId);
            if (item) {
                total += parseFloat(item.price) * parseInt(qty);
            }
        }
        return total;
    },

    async paySplitOrder() {
        const itemsToPay = Object.entries(this.splitItems).map(([id, qty]) => ({
            id: parseInt(id),
            quantity: parseInt(qty)
        }));

        if (itemsToPay.length === 0) {
            alert('Selectează cel puțin un produs!');
            return;
        }

        try {
            const response = await fetch(`/waiter/order/${this.orderDetails.id}/pay-partial`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    items: itemsToPay,
                    payment_method: this.paymentMethod,
                    is_fiscal: this.isFiscal,
                    fiscal_data: this.fiscalData
                })
            });
            
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.reload();
            } else {
                alert('Eroare: ' + result.message);
            }
        } catch (e) {
            alert('Eroare la procesarea plății parțiale.');
        }
    },

    printBill() {
        if (!this.orderDetails) return;
        window.open(`/waiter/order/${this.orderDetails.id}/print`, '_blank');
    },

    // Notes Logic
    showNoteModal: false,
    currentNoteItem: null,
    currentNoteText: '',

    openNoteModal(item) {
        this.currentNoteItem = item;
        this.currentNoteText = item.notes || '';
        this.showNoteModal = true;
    },

    saveNote() {
        if (this.currentNoteItem) {
            this.currentNoteItem.notes = this.currentNoteText;
        }
        this.showNoteModal = false;
        this.currentNoteItem = null;
        this.currentNoteText = '';
    }
}">
    <!-- Top Header -->
    <!-- Top Header & Area Selector -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
            
            <!-- Area Selector Tabs (Left) -->
            <div class="flex-grow overflow-x-auto no-scrollbar flex items-center gap-2 pr-4">
                <template x-for="area in areas" :key="area.id">
                    <button 
                        @click="activeAreaId = area.id"
                        class="px-5 py-2.5 rounded-xl text-[10px] font-black transition-all whitespace-nowrap border-2 uppercase tracking-widest"
                        :class="activeAreaId === area.id 
                            ? 'bg-gray-900 border-gray-900 text-white shadow-xl shadow-gray-900/20' 
                            : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                        x-text="area.name"
                    ></button>
                </template>
            </div>
            
            <!-- Logout (Right) -->
            <div class="flex items-center space-x-2 shrink-0 border-l border-gray-100 pl-4 print:hidden">
                <span class="text-[10px] font-bold text-gray-300 hidden sm:block uppercase tracking-widest">
                    {{ Session::get('staff_name') }}
                </span>
                <button @click="openReport()" class="flex items-center gap-1.5 px-3 py-2 bg-gray-50 border border-gray-100 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-900 hover:bg-gray-100 hover:border-gray-200 transition-all">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Raport Zilnic</span>
                </button>
                <form action="{{ route('staff.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tables Map Area -->
    <div class="max-w-7xl mx-auto p-4 md:p-6">
        <template x-if="!activeAreaId">
            <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                <svg class="h-16 w-16 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="text-sm font-bold uppercase tracking-widest">Nicio zonă selectată</p>
            </div>
        </template>

        <div class="relative w-full aspect-[4/3] bg-white rounded-[2.5rem] shadow-2xl shadow-gray-200 border border-gray-100 overflow-hidden select-none" 
             x-show="activeAreaId"
             id="waiter-map-container"
             style="background-image: radial-gradient(#e5e7eb 1.5px, transparent 1.5px); background-size: 24px 24px;">
            
            <div id="waiter-map-canvas" class="relative transition-transform origin-top-left w-[1000px] h-[800px]">
                <template x-for="table in getActiveTables()" :key="table.id">
                    <button 
                        @click="openTable(table)"
                        class="absolute flex flex-col items-center justify-center font-black text-white shadow-lg transition-all hover:scale-105 active:scale-95 group"
                        :style="{
                            transform: `translate(${table.x}px, ${table.y}px)`,
                            width: `${table.width}px`, 
                            height: `${table.height}px`,
                            backgroundColor: table.active_order ? '#ef4444' : '#3b82f6',
                            borderRadius: table.shape === 'round' ? '50%' : '24px'
                        }"
                    >
                        <span class="text-sm md:text-lg mb-0.5" x-text="table.name"></span>
                        <div class="flex items-center gap-1 opacity-70 scale-90">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                            <span class="text-[10px]" x-text="table.seats"></span>
                        </div>
                        
                        <!-- Occupied Pulse -->
                        <template x-if="table.active_order">
                            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 border-2 border-white"></span>
                            </span>
                        </template>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Table Action Popup (Choice) -->
    <div x-show="showTablePopup" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showTablePopup = false"></div>
        
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl relative overflow-hidden p-8 flex flex-col items-center">
            <div class="w-16 h-16 rounded-2xl mb-4 flex items-center justify-center text-white text-xl font-black shadow-lg"
                 :style="{ backgroundColor: selectedTable?.active_order ? '#ef4444' : '#3b82f6' }">
                <span x-text="selectedTable?.name"></span>
            </div>
            
            <h3 class="text-xl font-black text-gray-900 mb-1">Masa <span x-text="selectedTable?.name"></span></h3>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-8" 
               x-text="selectedTable?.active_order ? 'Masa este ocupată' : 'Masa este liberă'"></p>

            <div class="w-full space-y-3">
                <template x-if="!selectedTable?.active_order">
                    <button @click="openMenu()" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-black shadow-xl shadow-gray-900/20 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest text-xs">
                        Deschide Masa
                    </button>
                </template>
                
                <template x-if="selectedTable?.active_order">
                    <div class="space-y-3 w-full">
                        <button @click="viewOrder()" class="w-full bg-red-500 text-white py-4 rounded-2xl font-black shadow-xl shadow-red-500/20 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest text-xs">
                            Vezi Comanda
                        </button>
                        <button @click="openMenu()" class="w-full bg-white border-2 border-gray-100 text-gray-900 py-4 rounded-2xl font-black hover:border-gray-200 active:scale-95 transition-all uppercase tracking-widest text-xs text-center block">
                            Adaugă Produse
                        </button>
                    </div>
                </template>

                <button @click="showTablePopup = false" class="w-full py-4 text-gray-400 font-bold hover:text-gray-600 transition-colors text-[10px] uppercase tracking-widest">
                    Anulează
                </button>
            </div>
        </div>
    </div>

    <!-- Order Details Modal (SPA Fluid) -->
    <div x-show="showOrderModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-10"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md" @click="showOrderModal = false"></div>
        
        <div class="bg-white w-full max-w-4xl h-[85vh] md:h-auto rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-500 text-white flex items-center justify-center font-black text-lg shadow-lg shadow-red-500/20" x-text="selectedTable?.name"></div>
                    <div>
                        <h2 class="text-xl font-black text-gray-900">Comandă Masa <span x-text="selectedTable?.name"></span></h2>
                        <template x-if="orderDetails">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" x-text="orderDetails.order_number"></p>
                        </template>
                    </div>
                </div>
                <button @click="showOrderModal = false" class="p-2 bg-gray-50 rounded-xl text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-grow overflow-y-auto p-6 md:p-8 space-y-8">
                <template x-if="isLoadingOrder">
                    <div class="py-20 flex flex-col items-center justify-center text-gray-400">
                        <svg class="w-10 h-10 animate-spin mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <p class="text-sm font-bold uppercase tracking-widest animate-pulse">Se încarcă detaliile...</p>
                    </div>
                </template>

                <template x-if="orderDetails && !isLoadingOrder">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <!-- Left: Items list -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Produse</h3>
                            <div class="space-y-3">
                                <template x-for="item in orderDetails.items" :key="item.id">
                                    <div class="bg-gray-50/50 p-4 rounded-3xl border border-gray-100 flex justify-between items-center">
                                        <div class="flex items-center gap-4">
                                            <!-- Normal Qty Controls (Hidden when splitting) -->
                                            <div class="flex flex-col items-center gap-1" x-show="!isSplitting">
                                                <button @click.stop="updateItemQty(item.id, item.quantity + 1)" class="text-orange-400 hover:text-orange-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <div class="w-8 h-8 rounded-lg bg-white shadow-sm flex items-center justify-center font-black text-xs text-orange-600" x-text="item.quantity"></div>
                                                <button @click.stop="updateItemQty(item.id, item.quantity - 1)" class="text-orange-400 hover:text-orange-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </div>

                                            <!-- Split Qty Controls (Shown when splitting and selected) -->
                                            <div class="flex flex-col items-center gap-1" x-show="isSplitting && splitItems[item.id]" @click.stop>
                                                <button @click="if(splitItems[item.id] < item.quantity) splitItems[item.id]++" class="text-green-500 hover:text-green-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <div class="w-8 h-8 rounded-lg bg-green-500 text-white shadow-sm flex items-center justify-center font-black text-xs" x-text="splitItems[item.id]"></div>
                                                <button @click="if(splitItems[item.id] > 1) splitItems[item.id]--" class="text-green-500 hover:text-green-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                            </div>

                                            <!-- Split Checkbox Indicator -->
                                            <div x-show="isSplitting" class="mr-2" @click.stop="toggleItemSplit(item)">
                                                <div class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-colors cursor-pointer"
                                                     :class="splitItems[item.id] ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 bg-white'">
                                                     <svg x-show="splitItems[item.id]" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-bold text-gray-900 text-sm" x-text="item.name"></h4>
                                                    <button x-show="!isSplitting" @click.stop="removeItem(item.id)" class="text-red-300 hover:text-red-500">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </div>
                                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter" x-text="item.destination"></p>
                                            </div>
                                        </div>
                                        <p class="font-black text-gray-700 text-sm" x-text="(item.price * (isSplitting && splitItems[item.id] ? splitItems[item.id] : item.quantity)).toFixed(2) + ' RON'"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right: Actions & Total -->
                        <div class="space-y-6">
                            <div class="bg-gray-900 rounded-[2rem] p-8 text-white shadow-2xl shadow-gray-900/20">
                                
                                <div class="flex justify-between items-center mb-6">
                                     <button @click="toggleSplit()" 
                                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border-2 transition-all"
                                            :class="isSplitting ? 'bg-green-500 border-green-500 text-white' : 'border-gray-700 text-gray-400 hover:text-white'">
                                        <span x-text="isSplitting ? 'Anulează Split' : 'Achitare Parțială / Split'"></span>
                                    </button>
                                </div>

                                <div class="flex justify-between items-end mb-8">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" x-text="isSplitting ? 'Total Selectat' : 'Total de Plată'"></span>
                                    <span class="text-4xl font-black" x-text="(isSplitting ? getSplitTotal() : parseFloat(orderDetails.total)).toFixed(2) + ' RON'"></span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-8">
                                    <button @click="paymentMethod = 'cash'" class="py-4 rounded-2xl border-2 transition-all font-black text-xs uppercase tracking-widest"
                                            :class="paymentMethod === 'cash' ? 'bg-white text-gray-900 border-white' : 'border-gray-800 text-gray-500 hover:border-gray-700'">
                                        CASH
                                    </button>
                                    <button @click="paymentMethod = 'card'" class="py-4 rounded-2xl border-2 transition-all font-black text-xs uppercase tracking-widest"
                                            :class="paymentMethod === 'card' ? 'bg-white text-gray-900 border-white' : 'border-gray-800 text-gray-500 hover:border-gray-700'">
                                        CARD
                                    </button>
                                </div>

                                <button @click="isFiscal = !isFiscal" class="w-full flex items-center justify-between p-4 rounded-2xl border border-gray-800 hover:bg-gray-800 transition-colors mb-4">
                                    <span class="text-xs font-bold uppercase tracking-widest">Factură Fiscală</span>
                                    <div class="w-10 h-6 rounded-full relative transition-colors duration-200" :class="isFiscal ? 'bg-blue-500' : 'bg-gray-700'">
                                        <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform duration-200" :style="isFiscal ? 'transform: translateX(16px)' : ''"></div>
                                    </div>
                                </button>

                                <template x-if="isFiscal">
                                    <div class="space-y-3 mb-6 animate-slide-in">
                                        <input type="text" x-model="fiscalData.company_name" placeholder="Nume Companie" class="w-full bg-gray-800 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 placeholder-gray-600">
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="text" x-model="fiscalData.cui" placeholder="CUI" class="w-full bg-gray-800 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 placeholder-gray-600">
                                            <input type="text" x-model="fiscalData.reg_com" placeholder="Reg. Com." class="w-full bg-gray-800 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 placeholder-gray-600">
                                        </div>
                                        <input type="text" x-model="fiscalData.address" placeholder="Adresă" class="w-full bg-gray-800 border-0 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 placeholder-gray-600">
                                    </div>
                                </template>

                                <button @click="isSplitting ? paySplitOrder() : payOrder()" 
                                        class="w-full py-5 rounded-[1.5rem] font-black shadow-xl hover:scale-[1.02] transition-all uppercase tracking-widest text-sm"
                                        :class="isSplitting ? 'bg-green-600 text-white shadow-green-600/20' : 'bg-green-500 text-white shadow-green-500/20'">
                                    <span x-text="isSplitting ? 'ACHITĂ SELECTAT' : 'ÎNCASARE FINALĂ'"></span>
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <button @click="printBill()" class="flex flex-col items-center justify-center p-6 rounded-[2rem] bg-gray-50 border-2 border-gray-100 text-gray-400 hover:border-gray-200 hover:text-gray-600 transition-all group">
                                    <svg class="w-8 h-8 mb-2 opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Notă de Plată</span>
                                </button>
                                <button @click="openMenu()" class="flex flex-col items-center justify-center p-6 rounded-[2rem] bg-orange-50/50 border-2 border-orange-100 text-orange-400 hover:border-orange-200 hover:text-orange-600 transition-all group">
                                    <svg class="w-8 h-8 mb-2 opacity-50 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Adaugă Produse</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Menu Modal (SPA Fluid) -->
    <div x-show="showMenuModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-10"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md" @click="showMenuModal = false"></div>
        
        <div class="bg-white w-full max-w-[95vw] lg:max-w-6xl xl:max-w-7xl h-[92vh] rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gray-900 text-white flex items-center justify-center font-black text-lg" x-text="selectedTable?.name"></div>
                    <h2 class="text-xl font-black text-gray-900">Meniu - Masa <span x-text="selectedTable?.name"></span></h2>
                </div>
                <button @click="showMenuModal = false" class="p-2 bg-gray-50 rounded-xl text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-grow flex flex-col lg:flex-row overflow-hidden">
                <!-- Side Categories (Fluid) -->
                <div class="w-full lg:w-60 shrink-0 border-b lg:border-b-0 lg:border-r border-gray-100 bg-gray-50/50 overflow-x-auto lg:overflow-y-auto p-3 lg:p-4 flex lg:flex-col gap-2 no-scrollbar transition-all duration-300">
                    <template x-for="cat in categories" :key="cat.id">
                        <button @click="activeCategoryId = cat.id"
                                class="px-4 py-2.5 lg:px-5 lg:py-3 rounded-xl lg:rounded-2xl text-[10px] lg:text-xs font-black whitespace-nowrap transition-all border-2 text-left lg:w-full uppercase tracking-widest"
                                :class="activeCategoryId === cat.id 
                                    ? 'bg-gray-900 border-gray-900 text-white shadow-xl shadow-gray-900/10' 
                                    : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                x-text="cat.name"></button>
                    </template>
                </div>

                <!-- Main Menu Body -->
                <div class="flex-grow p-4 lg:p-8 overflow-y-auto flex flex-col">
                    <!-- Search Bar -->
                    <div class="mb-6 shrink-0 relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <input type="text" 
                               x-model="searchQuery" 
                               placeholder="Caută un produs..." 
                               class="w-full bg-gray-50 border-0 rounded-2xl pl-12 pr-10 py-3.5 text-sm focus:ring-2 focus:ring-orange-500 placeholder-gray-400 shadow-inner">
                        <button x-show="searchQuery !== ''" 
                                @click="searchQuery = ''" 
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Products List Container -->
                    <div class="flex-grow">
                        <div x-show="getProducts().length === 0" class="flex flex-col items-center justify-center py-20 text-gray-400" x-cloak>
                            <svg class="w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-sm font-bold uppercase tracking-widest text-center" x-text="searchQuery !== '' ? 'Niciun produs găsit pentru \'' + searchQuery + '\'' : 'Această categorie nu are produse'"></p>
                        </div>

                        <div x-show="getProducts().length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 md:gap-4">
                            <template x-for="prod in getProducts()" :key="prod.id">
                                <button @click="selectProduct(prod)"
                                        class="flex flex-row items-center text-left bg-white border border-gray-100 rounded-2xl p-3 shadow-sm hover:shadow-md hover:border-orange-200 transition-all active:scale-[0.98] group overflow-hidden w-full relative">
                                    <template x-if="prod.image">
                                        <div class="w-12 h-12 rounded-xl shrink-0 overflow-hidden bg-gray-50 mr-3">
                                            <img :src="'/storage/' + prod.image" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        </div>
                                    </template>
                                    <div class="flex-grow min-w-0 pr-12">
                                        <h3 class="font-black text-gray-900 text-xs md:text-sm leading-tight mb-1 truncate" x-text="prod.name"></h3>
                                        <p class="text-orange-600 font-black text-xs md:text-sm" x-text="parseFloat(prod.price).toFixed(2) + ' RON'"></p>
                                    </div>
                                    <!-- Quick Add Plus Icon (All screens) -->
                                    <div class="absolute right-3">
                                        <div class="w-8 h-8 rounded-xl bg-orange-600 text-white flex items-center justify-center shadow-md shadow-orange-600/10 group-hover:scale-105 active:scale-90 transition-all duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                        </div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Cart Sidebar (Fluid) - Hidden on Mobile & Tablet -->
                <div class="hidden lg:flex w-full lg:w-80 shrink-0 border-l border-gray-100 bg-gray-50/50 p-6 flex-col transition-all duration-300">
                    <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-6">Comandă Nouă</h3>
                    <div class="flex-grow overflow-y-auto space-y-3 no-scrollbar mb-6">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="bg-white p-3 rounded-2xl flex items-center justify-between shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center bg-gray-50 rounded-xl px-2 py-0.5">
                                        <button @click="item.quantity++" class="text-gray-400 hover:text-orange-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <span class="font-black text-gray-900 text-[10px]" x-text="item.quantity"></span>
                                        <button @click="if(item.quantity > 1) item.quantity--; else cart.splice(index, 1)" class="text-gray-400 hover:text-red-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                    <div class="max-w-[120px]">
                                        <h4 class="font-bold text-gray-900 text-[10px] truncate" x-text="item.name"></h4>
                                        <p class="text-[9px] text-gray-400 font-bold" x-text="(item.price * item.quantity).toFixed(2) + ' RON'"></p>
                                        <template x-if="item.notes">
                                            <p class="text-[9px] text-orange-600 font-bold italic truncate" x-text="'Cmd: ' + item.notes"></p>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button @click="openNoteModal(item)" class="text-gray-300 hover:text-blue-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="cart.splice(index, 1)" class="text-gray-200 hover:text-red-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="cart.length === 0">
                            <div class="h-full flex flex-col items-center justify-center text-center py-10 opacity-30">
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                <p class="text-[10px] font-black uppercase tracking-widest">Coș Gol</p>
                            </div>
                        </template>
                    </div>

                    <div class="pt-6 border-t border-gray-200">
                        <button @click="sendOrder()" 
                                :disabled="cart.length === 0 || isSendingOrder"
                                class="w-full bg-orange-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-orange-600/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 text-xs uppercase tracking-widest">
                            <span x-show="!isSendingOrder">Trimite Produs</span>
                            <span x-show="isSendingOrder">Trimitere...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Floating Cart Bar -->
            <div x-show="cart.length > 0"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-10"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-10"
                 class="lg:hidden p-4 bg-white border-t border-gray-150 shadow-[0_-10px_25px_-5px_rgba(0,0,0,0.05)] sticky bottom-0 left-0 right-0 z-20 shrink-0">
                <button @click="showMobileCart = true"
                        class="w-full bg-orange-600 text-white p-4 rounded-2xl flex items-center justify-between font-black shadow-lg shadow-orange-600/20 active:scale-95 transition-all text-xs uppercase tracking-widest">
                    <div class="flex items-center gap-2">
                        <span class="bg-white text-orange-600 px-2 py-0.5 rounded-lg text-[10px]" x-text="cart.length"></span>
                        <span>Vezi Comanda</span>
                    </div>
                    <span x-text="getTotal() + ' RON'"></span>
                </button>
            </div>

            <!-- Mobile Bottom Sheet Drawer for Cart -->
            <div x-show="showMobileCart" 
                 class="fixed inset-0 z-50 lg:hidden flex flex-col justify-end"
                 x-cloak>
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showMobileCart = false"
                     x-show="showMobileCart"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"></div>
                
                <!-- Drawer Panel -->
                <div class="bg-white rounded-t-[2.5rem] shadow-2xl relative z-10 w-full max-h-[80vh] flex flex-col"
                     x-show="showMobileCart"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="translate-y-0"
                     x-transition:leave-end="translate-y-full">
                    
                    <!-- Drawer Header -->
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-lg text-xs font-black" x-text="cart.length"></span>
                            <h3 class="text-base font-black text-gray-900 uppercase tracking-widest">Comanda Ta</h3>
                        </div>
                        <button @click="showMobileCart = false" class="p-2 bg-gray-50 rounded-xl text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>

                    <!-- Drawer Content -->
                    <div class="flex-grow overflow-y-auto p-5 space-y-3 no-scrollbar">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="bg-gray-50/50 p-3.5 rounded-2xl flex items-center justify-between border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center bg-white rounded-xl px-2 py-1 shadow-sm border border-gray-100">
                                        <button @click="item.quantity++" class="text-gray-400 hover:text-orange-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <span class="font-black text-gray-900 text-xs my-0.5" x-text="item.quantity"></span>
                                        <button @click="if(item.quantity > 1) item.quantity--; else cart.splice(index, 1)" class="text-gray-400 hover:text-red-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-xs" x-text="item.name"></h4>
                                        <p class="text-[10px] text-gray-400 font-bold" x-text="(item.price * item.quantity).toFixed(2) + ' RON'"></p>
                                        <template x-if="item.notes">
                                            <p class="text-[10px] text-orange-600 font-bold italic mt-0.5" x-text="'Cmd: ' + item.notes"></p>
                                        </template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button @click="openNoteModal(item)" class="p-2 bg-white rounded-xl text-gray-400 hover:text-blue-500 shadow-sm border border-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="cart.splice(index, 1)" class="p-2 bg-white rounded-xl text-gray-400 hover:text-red-500 shadow-sm border border-gray-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Drawer Footer -->
                    <div class="p-5 border-t border-gray-100 shrink-0">
                        <div class="flex justify-between items-end mb-4">
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total de Plată</span>
                            <span class="text-2xl font-black text-gray-900 leading-none" x-text="getTotal() + ' RON'"></span>
                        </div>
                        
                        <button @click="sendOrder(); showMobileCart = false" 
                                :disabled="cart.length === 0 || isSendingOrder"
                                class="w-full bg-orange-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-orange-600/20 active:scale-95 transition-all disabled:opacity-50 text-xs uppercase tracking-widest">
                            <span x-show="!isSendingOrder">Trimite Comanda</span>
                            <span x-show="isSendingOrder">Se trimite...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Variations Modal (Inside SPA) -->
    <div x-show="showVariations" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showVariations = false"></div>
        
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col p-8">
            <h3 class="text-xl font-black text-gray-900 mb-2 truncate" x-text="selectedProduct?.name"></h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-8">Alege opțiunea dorită</p>

            <div class="space-y-3">
                <template x-if="selectedProduct">
                    <template x-for="variation in selectedProduct.variations" :key="variation.id">
                        <button @click="addToCart(selectedProduct, variation)"
                                class="w-full flex justify-between items-center p-5 rounded-2xl border-2 border-gray-100 hover:border-orange-500 hover:bg-orange-50 transition-all group">
                            <span class="font-bold text-gray-700 group-hover:text-orange-900 text-sm" x-text="variation.name"></span>
                            <span class="font-black text-orange-600 text-sm" x-text="'+' + parseFloat(variation.price).toFixed(2) + ' RON'"></span>
                        </button>
                    </template>
                </template>
            </div>

            <button @click="showVariations = false" class="mt-8 w-full py-4 text-gray-400 font-bold hover:text-gray-600 transition-colors text-[10px] uppercase tracking-widest">
                Anulează
            </button>
        </div>
    </div>

    <!-- Note Modal -->
    <div x-show="showNoteModal" 
         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showNoteModal = false"></div>
        
        <div class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col p-8">
            <h3 class="text-xl font-black text-gray-900 mb-2">Adaugă Notă / Opțiuni</h3>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-6" x-text="currentNoteItem?.name"></p>

            <textarea x-model="currentNoteText" 
                      class="w-full bg-gray-50 border-0 rounded-2xl p-4 text-sm focus:ring-2 focus:ring-orange-500 placeholder-gray-400 mb-6" 
                      rows="4" 
                      placeholder="Ex: Fără ceapă, Bine făcut, etc..."></textarea>

            <div class="grid grid-cols-2 gap-4">
                <button @click="showNoteModal = false" class="py-4 text-gray-400 font-bold hover:text-gray-600 transition-colors text-[10px] uppercase tracking-widest">
                    Anulează
                </button>
                <button @click="saveNote()" class="bg-orange-600 text-white py-4 rounded-xl font-black shadow-lg shadow-orange-600/20 hover:scale-[1.02] active:scale-95 transition-all text-xs uppercase tracking-widest">
                    Salvează
                </button>
            </div>
        </div>
    </div>

    <!-- Daily Report Modal (SPA Fluid) -->
    <div x-show="showReportModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-10 print:static print:z-auto print:block print:p-0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-cloak>
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-md print:hidden" @click="showReportModal = false"></div>
        
        <div class="bg-white w-full max-w-2xl h-[85vh] md:h-auto rounded-[2.5rem] shadow-2xl relative overflow-hidden flex flex-col print:h-auto print:shadow-none print:rounded-none print:w-full print:overflow-visible">
            <!-- Header -->
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10 print:hidden">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-500 text-white flex items-center justify-center font-black text-lg shadow-lg shadow-orange-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-900">Raport Zilnic Ospătar</h2>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" x-text="reportData ? reportData.staff_name + ' • ' + reportData.date : ''"></p>
                    </div>
                </div>
                <button @click="showReportModal = false" class="p-2 bg-gray-50 rounded-xl text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-grow overflow-y-auto p-6 md:p-8 space-y-6 print:overflow-visible print:p-0">
                <template x-if="isLoadingReport">
                    <div class="py-20 flex flex-col items-center justify-center text-gray-400 print:hidden">
                        <svg class="w-10 h-10 animate-spin mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <p class="text-sm font-bold uppercase tracking-widest animate-pulse">Se încarcă raportul...</p>
                    </div>
                </template>

                <template x-if="reportData && !isLoadingReport">
                    <div class="space-y-6">
                        <!-- Print header (only visible when printing) -->
                        <div class="hidden print:block border-b border-gray-200 pb-4 mb-6">
                            <h1 class="text-2xl font-black text-gray-900">Raport Zilnic Ospătar</h1>
                            <p class="text-sm font-bold text-gray-600 uppercase tracking-wider mt-1" x-text="'Ospătar: ' + reportData.staff_name"></p>
                            <p class="text-xs text-gray-450 mt-0.5" x-text="'Data raportului: ' + reportData.date"></p>
                        </div>

                        <!-- KPI metrics -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-gray-50/50 p-4 rounded-3xl border border-gray-100 flex flex-col items-center text-center print:border-gray-200 print:bg-white print:p-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest print:text-[8px] print:text-gray-500">Total Comenzi</span>
                                <span class="text-2xl font-black text-gray-900 mt-1 print:text-lg" x-text="reportData.total_orders_count"></span>
                            </div>
                            <div class="bg-gray-50/50 p-4 rounded-3xl border border-gray-100 flex flex-col items-center text-center print:border-gray-200 print:bg-white print:p-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest print:text-[8px] print:text-gray-500">Comenzi Plătite</span>
                                <span class="text-2xl font-black text-gray-900 mt-1 print:text-lg" x-text="reportData.paid_orders_count"></span>
                            </div>
                            <div class="bg-orange-50/35 p-4 rounded-3xl border border-orange-100/50 flex flex-col items-center text-center print:border-gray-200 print:bg-white print:p-2">
                                <span class="text-[10px] font-bold text-orange-600 uppercase tracking-widest print:text-[8px] print:text-gray-500">Total Încasat</span>
                                <span class="text-2xl font-black text-orange-600 mt-1 print:text-lg print:text-black" x-text="reportData.total_revenue.toFixed(2) + ' lei'"></span>
                            </div>
                        </div>

                        <!-- Payment Split -->
                        <div class="bg-gray-50/30 border border-gray-100 rounded-3xl p-5 space-y-3 print:border-gray-200 print:bg-white print:p-3">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest print:text-[9px] print:text-black">Metode de Plată (Comenzi Încasate)</h3>
                            <div class="grid grid-cols-2 gap-4 pt-1">
                                <div class="flex justify-between items-center bg-white p-3 rounded-2xl border border-gray-100/60 print:border-gray-200 print:p-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center print:hidden">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </div>
                                        <span class="text-xs font-bold text-gray-500 uppercase print:text-[10px]">Cash</span>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 print:text-xs" x-text="reportData.cash_revenue.toFixed(2) + ' lei'"></span>
                                </div>
                                <div class="flex justify-between items-center bg-white p-3 rounded-2xl border border-gray-100/60 print:border-gray-200 print:p-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center print:hidden">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                        </div>
                                        <span class="text-xs font-bold text-gray-500 uppercase print:text-[10px]">Card</span>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 print:text-xs" x-text="reportData.card_revenue.toFixed(2) + ' lei'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Products Sold Table -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest print:text-[9px] print:text-black">Produse Vândute</h3>
                            
                            <div class="border border-gray-100 rounded-3xl overflow-hidden bg-white print:border-gray-200 print:rounded-none">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 border-b border-gray-100 print:border-gray-200 print:bg-white">
                                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest print:text-[8px] print:p-2 print:text-black">Produs</th>
                                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center print:text-[8px] print:p-2 print:text-black">Cantitate</th>
                                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right print:text-[8px] print:p-2 print:text-black">Valoare</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 print:divide-gray-200">
                                        <template x-if="reportData.products_sold.length === 0">
                                            <tr>
                                                <td colspan="3" class="p-8 text-center text-xs text-gray-400 font-bold uppercase tracking-wider print:p-4">Nu s-a vândut niciun produs astăzi</td>
                                            </tr>
                                        </template>
                                        <template x-for="item in reportData.products_sold" :key="item.name">
                                            <tr class="hover:bg-gray-50/30 transition-colors">
                                                <td class="p-4 text-xs font-bold text-gray-900 print:p-2 print:text-[10px]" x-text="item.name"></td>
                                                <td class="p-4 text-xs font-black text-gray-600 text-center print:p-2 print:text-[10px]" x-text="'x' + item.total_qty"></td>
                                                <td class="p-4 text-xs font-black text-gray-900 text-right print:p-2 print:text-[10px]" x-text="parseFloat(item.total_value).toFixed(2) + ' lei'"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex justify-between items-center gap-3 rounded-b-[2.5rem] print:hidden">
                <button @click="showReportModal = false" class="py-3 px-4 text-gray-400 font-bold hover:text-gray-600 transition-colors text-[10px] uppercase tracking-widest">
                    Închide
                </button>
                <div class="flex items-center gap-3">
                    <a href="/waiter/pdf-daily-report" target="_blank" class="bg-gray-800 text-white py-3 px-5 rounded-xl font-black shadow-lg shadow-gray-800/20 hover:scale-[1.02] active:scale-95 transition-all text-xs uppercase tracking-widest flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>PDF</span>
                    </a>
                    <button @click="window.open('/waiter/print-daily-report', '_blank')" class="bg-orange-600 text-white py-3 px-5 rounded-xl font-black shadow-lg shadow-orange-600/20 hover:scale-[1.02] active:scale-95 transition-all text-xs uppercase tracking-widest flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Printează</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('waiter-map-container');
            const canvas = document.getElementById('waiter-map-canvas');
            
            const autoScale = () => {
                if (!container || !canvas) return;
                
                // Available dims (subtract header/padding estimate)
                const availableWidth = container.clientWidth;
                const availableHeight = window.innerHeight - 100; // Header + Padding approx

                // Design dims
                const designWidth = 1000;
                const designHeight = 800;

                // Scale to fit (contain)
                const scaleX = availableWidth / designWidth;
                const scaleY = availableHeight / designHeight;
                const scale = Math.min(scaleX, scaleY); // Fit both

                canvas.style.transform = `scale(${scale})`;
                
                // Center it
                canvas.style.transformOrigin = 'top center';
                
                // Set container height to match scaled canvas
                container.style.height = (designHeight * scale) + 'px';
            };

            autoScale();
            window.addEventListener('resize', autoScale);
        });
    </script>
</div>


<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    @media print {
        body {
            background-color: white !important;
            color: black !important;
        }
        
        .bg-white.border-b.border-gray-200.sticky,
        .max-w-7xl,
        #waiter-map-container,
        .fixed.inset-0.bg-gray-900\/80,
        .bg-gray-900\/60,
        .backdrop-blur-md,
        .print\:hidden {
            display: none !important;
        }

        .fixed.inset-0.z-50.flex {
            position: relative !important;
            z-index: auto !important;
            display: block !important;
            padding: 0 !important;
            background: white !important;
            width: 100% !important;
            height: auto !important;
        }

        .bg-white.w-full.max-w-2xl {
            max-width: 100% !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            height: auto !important;
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
        }
    }
</style>
@endsection
