@extends('layouts.public')

@section('content')
<div class="bg-white min-h-screen pb-20" x-data="menuApp({ 
    activeMenuId: {{ $menus->first()?->id ?? 'null' }},
    currency: '{{ $settings->currency ?? 'RON' }}'
})">
    <!-- Menu Header -->
    <div class="bg-gray-100 py-12 text-center relative">
        <template x-if="tableNumber !== 'WEB'">
            <div class="absolute top-4 left-4 bg-orange-600 text-white px-4 py-2 rounded-full font-bold shadow-lg">
                Masa: <span x-text="tableNumber"></span>
            </div>
        </template>
        <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ __('Our Menu') }}</h1>
        <p class="text-gray-600 max-w-2xl mx-auto px-4">{{ __('Discover our delicious offerings, crafted with care.') }}</p>
        
        @if(!$settings->enable_ordering)
        <div class="mt-6 mx-auto max-w-md bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
            <p class="font-bold">Momentan nu preluăm comenzi online.</p>
            <p>Vă rugăm să ne contactați telefonic sau să reveniți mai târziu.</p>
        </div>
        @endif
    </div>

    <!-- Menus Tabs (Sticky) -->
    <div class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-gray-200 shadow-sm transition-all duration-300" :class="{'top-0': true}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-center space-x-8 py-4 overflow-x-auto no-scrollbar">
                @foreach($menus as $menu)
                    <button 
                        @click="setActiveMenu({{ $menu->id }})"
                        :class="activeMenu === {{ $menu->id }} ? 'text-orange-600 border-b-2 border-orange-600' : 'text-gray-500 hover:text-gray-900'"
                        class="pb-2 text-lg font-bold whitespace-nowrap transition-colors"
                    >
                        {{ $menu->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @foreach($menus as $menu)
            <div x-show="activeMenu === {{ $menu->id }}">
                
                @if($menu->categories->isEmpty())
                    <div class="text-center py-20">
                        <p class="text-gray-500 text-lg">{{ __('No items available in this menu yet.') }}</p>
                    </div>
                @else
                    <!-- Category Quick Links for this Menu -->
                    <div class="flex flex-wrap gap-4 mb-12 justify-center">
                        @foreach($menu->categories as $category)
                            <a href="#cat-{{ $category->id }}" class="px-4 py-2 bg-gray-50 rounded-full text-sm font-semibold text-gray-700 hover:bg-orange-100 hover:text-orange-600 transition-colors">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>

                    <div class="space-y-24">
                        @foreach($menu->categories as $category)
                            <div id="cat-{{ $category->id }}" class="scroll-mt-32">
                                <h2 class="text-3xl font-bold text-gray-900 mb-10 text-center relative">
                                    <span class="bg-white px-6 relative z-10">{{ $category->name }}</span>
                                    <div class="absolute inset-0 flex items-center justify-center -z-0">
                                        <div class="w-full h-px bg-gray-200 max-w-md"></div>
                                    </div>
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                    @foreach($category->products as $product)
                                        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-gray-100 overflow-hidden flex flex-col h-full group">
                                            @if($product->image)
                                                <div class="h-48 overflow-hidden relative">
                                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" 
                                                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 {{ !$product->is_available ? 'grayscale opacity-80' : '' }}">
                                                    @if(!$product->is_available)
                                                        <div class="absolute inset-0 flex items-center justify-center bg-black/10">
                                                            <span class="bg-black/60 text-white px-3 py-1 rounded text-sm font-bold">{{ __('Indisponibil') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                            
                    <div class="p-6 flex-grow flex flex-col">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $product->name }}</h3>
                                @if($product->measurement_value)
                                    <span class="text-sm text-gray-500">{{ (float)$product->measurement_value }} {{ $product->measurement_unit }}</span>
                                @endif
                            </div>
                            <span class="text-orange-600 font-bold text-lg whitespace-nowrap">
                                {{ number_format($product->price, 2) }} {{ $settings->currency ?? 'RON' }}
                            </span>
                        </div>
                        <p class="text-gray-500 text-sm mb-4 line-clamp-3 flex-grow">{{ $product->description }}</p>
                        
                        <button 
                            @click="openModal({{ $product->toJson() }})"
                            @if(!$product->is_available) disabled @endif
                            class="w-full mt-auto font-semibold py-3 rounded-xl transition-all flex items-center justify-center space-x-2 
                            {{ $product->is_available 
                                ? 'bg-gray-100 hover:bg-orange-600 hover:text-white text-gray-900 group-btn' 
                                : 'bg-gray-200 text-gray-400 cursor-not-allowed' 
                            }}"
                        >
                                                    @if($product->is_available)
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 group-btn-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        <span>{{ $product->variations->count() > 0 ? __('Customize') : __('Add to Order') }}</span>
                                                    @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                        <span>{{ __('Indisponibil') }}</span>
                                                    @endif
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Variation Modal -->
    <div x-show="isModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" aria-modal="true"
         style="display: none;"
    >
        <!-- Overlay -->
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()"></div>

        <!-- Panel -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="isModalOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]"
            >
                <!-- Close Button -->
                <button @click="closeModal()" class="absolute top-4 right-4 z-10 p-2 bg-white/50 hover:bg-white rounded-full text-gray-500 hover:text-gray-900 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Scrollable Content -->
                <div class="overflow-y-auto p-0 pb-24">
                    <!-- Image -->
                    <template x-if="selectedProduct?.image">
                        <div class="h-56 w-full relative">
                            <img :src="'/storage/' + selectedProduct.image" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <h3 class="absolute bottom-4 left-6 text-2xl font-bold text-white drop-shadow-md" x-text="selectedProduct?.name"></h3>
                        </div>
                    </template>
                    <template x-if="!selectedProduct?.image">
                        <div class="p-6 pb-2 border-b border-gray-100">
                             <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    <span x-text="selectedProduct?.name"></span>
                    <span x-show="selectedProduct?.measurement_value" class="text-base text-gray-500 font-normal ml-2" x-text="'(' + parseFloat(selectedProduct?.measurement_value) + ' ' + (selectedProduct?.measurement_unit ?? '') + ')'"></span>
                </h3>
                        </div>
                    </template>

                    <div class="p-6 space-y-6">
                        <!-- Description -->
                        <p class="text-gray-600 leading-relaxed" x-text="selectedProduct?.description"></p>

                        <!-- Ingredients -->
                        <template x-if="selectedProduct?.ingredients && selectedProduct.ingredients.length > 0">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">{{ __('Ingredients') }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="ingredient in selectedProduct.ingredients" :key="ingredient.id">
                                        <span class="px-2 py-1 bg-green-50 text-green-700 rounded-md text-xs font-medium border border-green-100" x-text="ingredient.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Nutrition & Allergens (Collapsible or just visible) -->
                        <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600">
                            <div class="grid grid-cols-2 gap-4">
                                <template x-if="selectedProduct?.nutritional_data && (selectedProduct.nutritional_data.calories > 0 || selectedProduct.nutritional_data.protein > 0)">
                                    <div>
                                        <h4 class="font-bold text-gray-900 mb-1">Nutriție (100g)</h4>
                                        <ul class="space-y-1">
                                            <li class="flex justify-between"><span>Calorii:</span> <span class="font-medium" x-text="selectedProduct.nutritional_data.calories + ' kcal'"></span></li>
                                            <li class="flex justify-between"><span>Proteine:</span> <span class="font-medium" x-text="selectedProduct.nutritional_data.protein + ' g'"></span></li>
                                        </ul>
                                    </div>
                                </template>
                                <template x-if="selectedProduct?.allergens">
                                    <div>
                                        <h4 class="font-bold text-gray-900 mb-1">Alergeni</h4>
                                        <p class="text-xs text-red-500 font-medium" x-text="selectedProduct.allergens"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Variations -->
                        <template x-if="selectedProduct?.variations && selectedProduct.variations.length > 0">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">{{ __('Choose Variation') }}</h4>
                                <div class="relative">
                                    <select 
                                        class="w-full rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 py-3 pl-4 pr-10 appearance-none bg-white text-gray-900 font-medium cursor-pointer"
                                        @change="selectedVariation = selectedProduct.variations.find(v => v.id == $event.target.value)"
                                    >
                                        <option value="" disabled selected>{{ __('Select an option...') }}</option>
                                        <template x-for="variation in selectedProduct.variations" :key="variation.id">
                                            <option :value="variation.id" x-text="variation.name + (variation.price > 0 ? ' (+' + parseFloat(variation.price).toFixed(2) + ' ' + currency + ')' : '')"></option>
                                        </template>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Special Instructions -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">{{ __('Special Instructions') }}</h4>
                            <textarea x-model="notes" 
                                      class="w-full rounded-xl border-gray-200 focus:border-orange-500 focus:ring-orange-500 text-sm" 
                                      rows="2" 
                                      placeholder="{{ __('Ex: No onions, extra sauce, etc...') }}"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions (Sticky Bottom) -->
                <div class="absolute bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100">
                    <div class="flex items-center space-x-4 w-full">
                        <div class="flex-shrink-0 flex items-center border border-gray-200 rounded-xl">
                            <button @click="decreaseQuantity()" class="px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-l-xl transition">-</button>
                            <span x-text="quantity" class="px-4 font-bold text-lg min-w-[30px] text-center"></span>
                            <button @click="increaseQuantity()" class="px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-r-xl transition">+</button>
                        </div>
                        
                        @if($settings->enable_ordering)
                        <button 
                            @click="addToCart()"
                            :disabled="selectedProduct?.variations?.length > 0 && !selectedVariation"
                            :class="(selectedProduct?.variations?.length > 0 && !selectedVariation) ? 'bg-gray-300 cursor-not-allowed' : 'bg-orange-600 hover:bg-orange-700 text-white'"
                            class="flex-grow font-bold py-3 rounded-xl transition-all flex items-center justify-center space-x-2 shadow-lg shadow-orange-600/20"
                        >
                            <span>{{ __('Add to Order') }}</span>
                            <span class="bg-white/20 px-2 py-0.5 rounded text-sm" x-text="getCurrentTotal().toFixed(2) + ' ' + currency"></span>
                        </button>
                        @else
                        <button disabled class="flex-grow font-bold py-3 rounded-xl bg-gray-400 text-white cursor-not-allowed">
                            Comenzile sunt oprite
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Cart Summary -->
    <div x-show="cart.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-6 left-0 right-0 px-4 z-40 pointer-events-none"
    >
        <div class="max-w-7xl mx-auto flex justify-end pointer-events-auto">
            <button @click="showCart = true" id="cart-fab" class="bg-orange-600 text-white shadow-lg rounded-full px-8 py-4 flex items-center space-x-4 hover:bg-orange-700 transition-transform duration-200">
                <span class="font-bold text-lg" x-text="cart.length + ' ' + '{{ __('items') }}'"></span>
                <span class="h-6 w-px bg-white/30"></span>
                <span class="font-bold text-lg" x-text="getCartTotal() + ' ' + currency"></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Cart Modal -->
    <div x-show="showCart" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         role="dialog" aria-modal="true"
         style="display: none;"
    >
        <div x-show="showCart" x-transition.opacity class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCart = false"></div>

        <div class="relative min-h-screen flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div x-show="showCart" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                 x-transition:enter-end="translate-y-0 sm:scale-100 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0 sm:scale-100 opacity-100"
                 x-transition:leave-end="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                 class="relative bg-white sm:rounded-2xl rounded-t-2xl shadow-xl w-full max-w-lg flex flex-col max-h-[90vh]"
            >
                <!-- Header -->
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Your Cart') }}</h3>
                    <button @click="showCart = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Cart Items -->
                <div class="overflow-y-auto p-6 space-y-6 flex-grow">
                    <template x-if="cart.length === 0">
                        <div class="text-center py-12 text-gray-500">
                            <svg class="h-12 w-12 mx-auto mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p>{{ __('Cart is empty.') }}</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="flex gap-4">
                            <!-- Image -->
                            <div class="h-20 w-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                                <template x-if="item.image">
                                    <img :src="'/storage/' + item.image" class="w-full h-full object-cover">
                                </template>
                            </div>
                            
                            <!-- Info -->
                            <div class="flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-900" x-text="item.name"></h4>
                                        <p class="text-sm text-gray-500" x-text="item.variation"></p>
                                    </div>
                                    <p class="font-bold text-orange-600" x-text="(item.price * item.quantity).toFixed(2) + ' ' + currency"></p>
                                </div>
                                
                                <div class="mt-2 flex justify-between items-center">
                                    <div class="flex items-center space-x-2 text-sm text-gray-500" x-show="item.notes">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span x-text="item.notes" class="truncate max-w-[150px]"></span>
                                    </div>

                                    <div class="flex items-center space-x-3 ml-auto">
                                        <div class="flex items-center border border-gray-200 rounded-lg px-2">
                                            <span class="text-sm font-semibold px-2" x-text="item.quantity + 'x'"></span>
                                        </div>
                                        <button @click="cart.splice(index, 1)" class="text-red-500 hover:text-red-700 p-1">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-gray-600 font-medium">Total</span>
                        <span class="text-2xl font-bold text-gray-900" x-text="getCartTotal() + ' ' + currency"></span>
                    </div>

                    <button 
                        @click="sendOrder()"
                        :disabled="cart.length === 0 || isLoading"
                        class="w-full bg-orange-600 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-orange-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center"
                    >
                        <svg x-show="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isLoading ? '{{ __('Processing...') }}' : '{{ __('Send Order') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="//unpkg.com/alpinejs" defer></script>
<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .group-btn:hover svg { color: white; }
    [x-cloak] { display: none !important; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('menuApp', (config) => ({
            activeMenu: config.activeMenuId,
            currency: config.currency,
            cart: [],
            showCart: false,
            selectedProduct: null,
            selectedVariation: null,
            quantity: 1,
            notes: '',
            tableNumber: new URLSearchParams(window.location.search).get('table') || 'WEB',
            isModalOpen: false,
            isLoading: false,

            setActiveMenu(id) {
                this.activeMenu = id;
                window.scrollTo({top: 0, behavior: 'smooth'});
            },

            openModal(product) {
                this.selectedProduct = product;
                this.selectedVariation = null;
                this.quantity = 1;
                this.notes = '';
                this.isModalOpen = true;
            },
            
            closeModal() {
                this.isModalOpen = false;
            },
            
            increaseQuantity() {
                this.quantity++;
            },
            
            decreaseQuantity() {
                if (this.quantity > 1) this.quantity--;
            },

            getCurrentTotal() {
                if (!this.selectedProduct) return 0;
                let price = parseFloat(this.selectedProduct.price);
                if (this.selectedVariation) {
                    price += parseFloat(this.selectedVariation.price);
                }
                return price * this.quantity;
            },

            addToCart() {
                if (!this.selectedProduct) return;
                
                let product = this.selectedProduct;
                let variation = this.selectedVariation;
                
                let price = parseFloat(product.price);
                let variationName = '';

                if (variation) {
                    price += parseFloat(variation.price);
                    variationName = variation.name;
                }

                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: price, 
                    variation: variationName,
                    variation_id: variation ? variation.id : null, // Added missing ID
                    image: product.image,
                    quantity: this.quantity,
                    notes: this.notes
                });
                
                this.isModalOpen = false;
                
                // Simple visual feedback
                const el = document.getElementById('cart-fab');
                if(el) {
                    el.classList.add('scale-110');
                    setTimeout(() => el.classList.remove('scale-110'), 200);
                }
            },

            getCartTotal() {
                return this.cart.reduce((total, item) => total + (parseFloat(item.price) * item.quantity), 0).toFixed(2);
            },

            async sendOrder() {
                if (this.cart.length === 0) return;
                this.isLoading = true;

                try {
                    const response = await fetch('/checkout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            items: this.cart,
                            table_number: this.tableNumber,
                            payment_method: 'cash'
                        })
                    });

                    const result = await response.json();

                    if (response.ok) {
                        this.cart = [];
                        this.showCart = false;
                        alert("{{ __('Order #') }}" + result.order_number + "{{ __('has been sent successfully!') }}");
                    } else {
                        alert("{{ __('Error:') }} " + (result.message || "{{ __('Something went wrong.') }}"));
                    }
                } catch (error) {
                    console.error('Checkout error:', error);
                    alert("{{ __('Connection error. Check console.') }}");
                } finally {
                    this.isLoading = false;
                }
            }
        }));
    });
</script>
@endsection
