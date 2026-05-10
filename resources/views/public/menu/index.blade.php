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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        @foreach($menus as $menu)
            <div x-show="activeMenu === {{ $menu->id }}" x-cloak>
                
                @if($menu->categories->isEmpty())
                    <div class="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                        <p class="text-gray-500 text-lg">{{ __('No items available in this menu yet.') }}</p>
                    </div>
                @else
                    <!-- Category Quick Links -->
                    <div class="flex flex-wrap gap-2 mb-16 justify-center">
                        @foreach($menu->categories as $category)
                            <a href="#cat-{{ $category->id }}" class="px-5 py-2.5 bg-white border border-gray-200 rounded-full text-sm font-medium text-gray-600 hover:border-primary hover:text-primary transition-all shadow-sm">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>

                    <div class="space-y-24">
                        @foreach($menu->categories as $category)
                            <div id="cat-{{ $category->id }}" class="scroll-mt-32">
                                <div class="text-center mb-12">
                                    <h2 class="text-3xl md:text-4xl font-serif font-bold text-gray-900 mb-4 inline-block relative px-10">
                                        {{ $category->name }}
                                        <div class="absolute top-1/2 left-0 w-8 h-px bg-primary/40"></div>
                                        <div class="absolute top-1/2 right-0 w-8 h-px bg-primary/40"></div>
                                    </h2>
                                    @if($category->image)
                                        <div class="mt-4 max-w-lg mx-auto h-32 rounded-2xl overflow-hidden opacity-50 grayscale hover:grayscale-0 transition-all duration-700">
                                            <img src="{{ asset('storage/' . $category->image) }}" class="w-full h-full object-cover">
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="space-y-6">
                                    @foreach($category->products as $product)
                                        <div class="bg-white p-5 md:p-6 rounded-2xl border border-gray-100 hover:border-primary/20 hover:shadow-xl hover:shadow-gray-200/40 transition-all duration-300 group">
                                            <div class="flex flex-col md:flex-row gap-6">
                                                
                                                <!-- Product Info -->
                                                <div class="flex-grow order-2 md:order-1">
                                                    <div class="flex justify-between items-start mb-1">
                                                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-primary transition-colors">
                                                            {{ $product->name }}
                                                            @if($product->is_frozen)
                                                                <span class="ml-2 inline-block" title="Produs congelat">
                                                                    <svg class="w-4 h-4 text-blue-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                                </span>
                                                            @endif
                                                        </h3>
                                                        <div class="hidden md:block text-xl font-black text-gray-900">
                                                            {{ number_format($product->price, 2) }} <span class="text-sm font-normal text-gray-400">{{ $settings->currency ?? 'RON' }}</span>
                                                        </div>
                                                    </div>

                                                    @if($product->measurement_value)
                                                        <div class="text-xs font-bold text-primary uppercase tracking-widest mb-2">
                                                            {{ (float)$product->measurement_value }} {{ $product->measurement_unit }}
                                                        </div>
                                                    @endif

                                                    <p class="text-gray-500 text-sm mb-3 leading-relaxed">
                                                        {{ $product->description }}
                                                    </p>

                                                    <!-- Ingredients & Allergens Summary -->
                                                    <div class="flex flex-wrap gap-2 items-center">
                                                        @if($product->ingredients->isNotEmpty())
                                                            <div class="text-[10px] uppercase font-bold text-gray-400">Ingrediente:</div>
                                                            <div class="text-xs text-gray-500 italic">
                                                                {{ $product->ingredients->take(4)->pluck('name')->implode(', ') }}@if($product->ingredients->count() > 4)...@endif
                                                            </div>
                                                        @endif

                                                        @if($product->allergens->isNotEmpty())
                                                            <div class="flex gap-1 ml-2">
                                                                @foreach($product->allergens as $allergen)
                                                                    <span class="px-1.5 py-0.5 bg-red-50 text-red-600 rounded text-[10px] font-bold border border-red-100 uppercase">{{ $allergen->name }}</span>
                                                                @endforeach
                                                            </div>
                                                        @elseif($product->allergens_legacy)
                                                             <span class="px-1.5 py-0.5 bg-red-50 text-red-600 rounded text-[10px] font-bold border border-red-100 uppercase">Alergeni</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Image Thumbnail (Optional) -->
                                                @if($product->image)
                                                    <div class="w-full md:w-32 h-48 md:h-32 flex-shrink-0 rounded-xl overflow-hidden order-1 md:order-2">
                                                        <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                                    </div>
                                                @endif

                                                <!-- Mobile Price & Global Action -->
                                                <div class="flex items-center justify-between md:justify-end gap-4 order-3 w-full md:w-auto">
                                                    <div class="md:hidden text-2xl font-black text-gray-900">
                                                        {{ number_format($product->price, 2) }} <span class="text-sm font-normal text-gray-400">{{ $settings->currency ?? 'RON' }}</span>
                                                    </div>
                                                    
                                                    @if($settings->enable_ordering && $product->is_available)
                                                        <button 
                                                            @click="openModal({{ $product->toJson() }})"
                                                            class="bg-primary text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                            <span>{{ __('Adaugă') }}</span>
                                                        </button>
                                                    @else
                                                        <button 
                                                            @click="openModal({{ $product->toJson() }})"
                                                            class="bg-gray-100 text-gray-900 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-200 transition-all flex items-center gap-2"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                            <span>{{ __('Vezi Detalii') }}</span>
                                                        </button>
                                                    @endif
                                                </div>
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
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">{{ __('Ingrediente') }}</h4>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="ingredient in selectedProduct.ingredients" :key="ingredient.id">
                                        <span class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium border border-gray-200" x-text="ingredient.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Special Markers (Frozen) -->
                        <template x-if="selectedProduct?.is_frozen">
                            <div class="flex items-center space-x-2 text-blue-600 bg-blue-50 px-4 py-2 rounded-xl border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span class="text-sm font-semibold" x-text="selectedProduct.frozen_note || '* Produs provenit din produs congelat.'"></span>
                            </div>
                        </template>

                        <!-- Nutrition & Allergens -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <template x-if="selectedProduct?.nutritional_data && (selectedProduct.nutritional_data.calories > 0 || selectedProduct.nutritional_data.protein > 0)">
                                <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
                                    <h4 class="font-bold text-gray-900 mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                        {{ __('Nutriție (100g)') }}
                                    </h4>
                                    <div class="grid grid-cols-2 gap-y-2 text-sm text-gray-600">
                                        <span>Calorii:</span> <span class="font-bold text-right" x-text="selectedProduct.nutritional_data.calories + ' kcal'"></span>
                                        <span>Proteine:</span> <span class="font-bold text-right" x-text="selectedProduct.nutritional_data.protein + ' g'"></span>
                                        <span>Grăsimi:</span> <span class="font-bold text-right" x-text="(selectedProduct.nutritional_data.fats || 0) + ' g'"></span>
                                        <span>Carbohidrați:</span> <span class="font-bold text-right" x-text="(selectedProduct.nutritional_data.carbs || 0) + ' g'"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="(selectedProduct?.allergens && selectedProduct.allergens.length > 0) || selectedProduct?.allergens_legacy">
                                <div class="bg-red-50/50 rounded-2xl p-5 border border-red-100">
                                    <h4 class="font-bold text-red-900 mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        {{ __('Alergeni') }}
                                    </h4>
                                    <div class="flex flex-wrap gap-2">
                                        <!-- New Allergens -->
                                        <template x-for="allergen in selectedProduct.allergens" :key="allergen.id">
                                            <span class="px-2 py-1 bg-white text-red-700 rounded-md text-xs font-bold border border-red-200 uppercase tracking-tighter" x-text="allergen.name"></span>
                                        </template>
                                        <!-- Fallback Legacy -->
                                        <template x-if="selectedProduct.allergens.length === 0 && selectedProduct.allergens_legacy">
                                            <p class="text-xs text-red-700 font-medium italic" x-text="selectedProduct.allergens_legacy"></p>
                                        </template>
                                    </div>
                                </div>
                            </template>
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
                        @if($settings->enable_ordering)
                            <div class="flex-shrink-0 flex items-center border border-gray-200 rounded-xl">
                                <button @click="decreaseQuantity()" class="px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-l-xl transition">-</button>
                                <span x-text="quantity" class="px-4 font-bold text-lg min-w-[30px] text-center"></span>
                                <button @click="increaseQuantity()" class="px-4 py-3 text-gray-600 hover:bg-gray-100 rounded-r-xl transition">+</button>
                            </div>
                            
                            <button 
                                @click="addToCart()"
                                :disabled="selectedProduct?.variations?.length > 0 && !selectedVariation"
                                :class="(selectedProduct?.variations?.length > 0 && !selectedVariation) ? 'bg-gray-300 cursor-not-allowed' : 'bg-primary hover:opacity-90 text-white'"
                                class="flex-grow font-bold py-3 rounded-xl transition-all flex items-center justify-center space-x-2 shadow-lg shadow-primary/20"
                            >
                                <span>{{ __('Adaugă în Coș') }}</span>
                                <span class="bg-white/20 px-2 py-0.5 rounded text-sm" x-text="getCurrentTotal().toFixed(2) + ' ' + currency"></span>
                            </button>
                        @else
                            <button @click="closeModal()" class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition-all">
                                {{ __('Închide') }}
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
                        class="w-full bg-primary hover:opacity-90 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center"
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
                this.selectedProduct = {
                    ...product,
                    allergens_legacy: product.allergens // Save legacy string before override if any
                };
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
