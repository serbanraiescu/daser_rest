<div class="space-y-4">
    @foreach($category->products as $product)
        <div class="bg-white p-4 rounded-2xl border border-gray-100 hover:border-primary/20 hover:shadow-lg transition-all duration-300 group">
            <div class="flex items-center gap-4">
                
                <!-- Product Info (left side) -->
                <div class="flex-grow min-w-0">
                    <div class="flex justify-between items-start gap-4 mb-1">
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 group-hover:text-primary transition-colors truncate">
                            {{ $product->name }}
                            @if($product->is_frozen)
                                <span class="ml-1 inline-block text-blue-400" title="Produs congelat">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </span>
                            @endif
                        </h3>
                        <div class="text-base sm:text-lg font-black text-gray-900 whitespace-nowrap">
                            {{ number_format($product->price, 2) }} <span class="text-xs font-normal text-gray-400">{{ $settings->currency ?? 'RON' }}</span>
                        </div>
                    </div>

                    @if($product->measurement_value)
                        <div class="text-[10px] font-bold text-primary uppercase tracking-wider mb-1">
                            {{ (float)$product->measurement_value }} {{ $product->measurement_unit }}
                        </div>
                    @endif

                    <p class="text-gray-500 text-xs mb-2 line-clamp-2 leading-relaxed">
                        {{ $product->description }}
                    </p>

                    <!-- Ingredients Summary -->
                    <div class="flex flex-wrap gap-1.5 items-center">
                        @if($product->ingredients->isNotEmpty())
                            <span class="text-[9px] uppercase font-bold text-gray-400">Ingrediente:</span>
                            <span class="text-xs text-gray-500 italic line-clamp-1">
                                {{ $product->ingredients->take(4)->pluck('name')->implode(', ') }}@if($product->ingredients->count() > 4)...@endif
                            </span>
                        @endif

                        @if($product->allergenRelations->isNotEmpty())
                            <div class="flex gap-1 ml-1">
                                @foreach($product->allergenRelations as $allergen)
                                    <span class="px-1.5 py-0.5 bg-red-50 text-red-600 rounded text-[9px] font-bold border border-red-100 uppercase">{{ $allergen->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Product Image or Placeholder (right side, small) -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 rounded-xl overflow-hidden relative border border-gray-100 bg-gray-50">
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-orange-50/50 flex items-center justify-center text-orange-500">
                            <svg class="w-6 h-6 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Action Button -->
                <div class="flex-shrink-0">
                    @if($settings->enable_ordering && $product->is_available)
                        <button 
                            @click="openModal({{ $product->toJson() }})"
                            class="bg-primary text-white p-2 sm:p-2.5 rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md shadow-primary/20 flex items-center justify-center"
                            title="Adaugă"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    @else
                        <button 
                            @click="openModal({{ $product->toJson() }})"
                            class="bg-gray-100 text-gray-950 px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl font-bold text-xs hover:bg-gray-200 transition-all flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Detalii</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>
    @endforeach
</div>
