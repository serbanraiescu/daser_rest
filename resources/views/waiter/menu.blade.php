@extends('layouts.staff')

@section('content')
<div class="min-h-screen bg-white pb-24" x-data="{
    activeCategoryId: {{ $categories->first()?->id ?? 'null' }},
    cart: [],
    categories: {{ $categories->toJson() }},
    table_number: '{{ $table->name }}',
    isSending: false,
    
    // Variations Modal
    showVariations: false,
    selectedProduct: null,

    getProducts() {
        const cat = this.categories.find(c => c.id === this.activeCategoryId);
        return cat ? cat.products : [];
    },

    selectProduct(product) {
        if (product.variations.length > 0) {
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

    removeFromCart(index) {
        this.cart.splice(index, 1);
    },

    getTotal() {
        return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2);
    },

    async sendOrder() {
        if (this.cart.length === 0) return;
        this.isSending = true;
        
        try {
            const response = await fetch('{{ route('waiter.order.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    items: this.cart,
                    table_number: this.table_number,
                    payment_method: 'cash' // Default for waiter
                })
            });

            const result = await response.json();
            if (result.success) {
                window.location.href = result.redirect;
            } else {
                alert('Eroare: ' + result.message);
            }
        } catch (e) {
            alert('Eroare de rețea.');
        } finally {
            this.isSending = false;
        }
    }
}">
    <!-- Header -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-30 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('waiter.index') }}" class="text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-black text-gray-900">Masa {{ $table->name }}</h1>
            </div>
            <div class="flex items-center gap-2">
                 <button @click="sendOrder()" 
                        :disabled="cart.length === 0 || isSending"
                        class="bg-green-600 text-white px-6 py-2 rounded-xl font-bold text-sm shadow-lg shadow-green-600/20 disabled:opacity-50 transition-all">
                    <span x-show="!isSending">Trimite (<span x-text="cart.length"></span>)</span>
                    <span x-show="isSending">Se trimite...</span>
                </button>
            </div>
        </div>

        <!-- Category Horizontal Scroll -->
        <div class="bg-gray-50/50 border-b border-gray-100 overflow-x-auto no-scrollbar scroll-smooth p-2">
            <div class="flex space-x-2 px-2 max-w-4xl mx-auto">
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="activeCategoryId = cat.id"
                            class="px-5 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all border-2"
                            :class="activeCategoryId === cat.id 
                                ? 'bg-gray-900 border-gray-900 text-white shadow-md transform scale-105' 
                                : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300'"
                            x-text="cat.name"></button>
                </template>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 pt-4 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Products Grid -->
        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-3">
                <template x-for="prod in getProducts()" :key="prod.id">
                    <button @click="selectProduct(prod)"
                            class="flex flex-col text-left bg-white border border-gray-100 rounded-2xl p-3 shadow-sm hover:shadow-md transition-all active:scale-95 group relative overflow-hidden">
                        
                        <div x-show="prod.image" class="w-full aspect-square rounded-xl mb-3 overflow-hidden bg-gray-50">
                            <img :src="'/storage/' + prod.image" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div x-show="!prod.image" class="w-full aspect-square rounded-xl mb-3 bg-gray-50 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-200" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/></svg>
                        </div>

                        <h3 class="font-bold text-gray-900 text-sm leading-tight mb-1" x-text="prod.name"></h3>
                        <p class="text-orange-600 font-black text-sm" x-text="parseFloat(prod.price).toFixed(2) + ' {{ $settings->currency ?? 'RON' }}'"></p>
                        
                        <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="bg-orange-600 text-white p-1 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                            </div>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Order Summary (Sidebar on Desktop, Bottom on Mobile) -->
        <div class="bg-gray-50 rounded-3xl p-6 h-fit sticky top-24 border border-gray-100">
            <h2 class="text-lg font-black text-gray-900 mb-6 flex items-center gap-2">
                Coș Comandă
                <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded-lg text-xs" x-text="cart.length"></span>
            </h2>

            <!-- Empty State -->
            <template x-if="cart.length === 0">
                <div class="py-12 text-center text-gray-400">
                    <p class="text-sm font-medium">Alege produse pentru a începe comanda.</p>
                </div>
            </template>

            <!-- Items -->
            <div class="space-y-3 mb-8 max-h-[40vh] overflow-y-auto pr-2 no-scrollbar">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="bg-white p-3 rounded-2xl flex items-center justify-between shadow-sm border border-white">
                        <div class="flex items-center gap-3">
                            <div class="flex flex-col items-center bg-gray-50 rounded-xl px-2 py-1">
                                <button @click="item.quantity++" class="text-gray-400 hover:text-orange-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <span class="font-black text-gray-900 text-sm" x-text="item.quantity"></span>
                                <button @click="if(item.quantity > 1) item.quantity--; else removeFromCart(index)" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-xs leading-none mb-1" x-text="item.name"></h4>
                                <p class="text-[10px] text-gray-400 font-bold" x-text="(item.price * item.quantity).toFixed(2) + ' RON'"></p>
                            </div>
                        </div>
                        <button @click="removeFromCart(index)" class="text-gray-300 hover:text-red-500 p-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 pt-6 space-y-4">
                <div class="flex justify-between items-end">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total</p>
                    <p class="text-3xl font-black text-gray-900 leading-none">
                        <span x-text="getTotal()"></span>
                        <span class="text-sm">RON</span>
                    </p>
                </div>
                
                <button @click="sendOrder()" 
                        :disabled="cart.length === 0 || isSending"
                        class="w-full bg-orange-600 text-white py-4 rounded-2xl font-black shadow-lg shadow-orange-600/20 hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50">
                    <span x-show="!isSending">TRIMITE COMANDA</span>
                    <span x-show="isSending">PROCESARE...</span>
                </button>
            </div>
        </div>
    </div>
    <!-- Variations Modal -->
    <div x-show="showVariations" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-cloak>
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showVariations = false"></div>
        
        <div class="bg-white w-full max-w-sm rounded-[2rem] shadow-2xl relative overflow-hidden flex flex-col p-6">
            <h3 class="text-xl font-black text-gray-900 mb-2" x-text="selectedProduct?.name"></h3>
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-6">Alege opțiunea dorită</p>

            <div class="space-y-3">
                <template x-if="selectedProduct">
                    <template x-for="variation in selectedProduct.variations" :key="variation.id">
                        <button @click="addToCart(selectedProduct, variation)"
                                class="w-full flex justify-between items-center p-4 rounded-2xl border-2 border-gray-100 hover:border-orange-600 hover:bg-orange-50 transition-all group">
                            <span class="font-bold text-gray-700 group-hover:text-orange-900" x-text="variation.name"></span>
                            <span class="font-black text-orange-600" x-text="'+' + parseFloat(variation.price).toFixed(2) + ' RON'"></span>
                        </button>
                    </template>
                </template>
            </div>

            <button @click="showVariations = false" class="mt-8 w-full py-4 text-gray-400 font-bold hover:text-gray-600 transition-colors">
                Anulează
            </button>
        </div>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    [x-cloak] { display: none !important; }
</style>
@endsection
