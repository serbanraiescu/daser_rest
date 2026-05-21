<div class="p-4 relative">
    <!-- Header: Table & Time -->
    <div class="flex justify-between items-start mb-3">
        <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Masa</span>
            <span class="text-2xl font-black text-gray-800" x-text="order.table_number"></span>
        </div>
        <div class="text-right">
             <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ora</span>
             <div class="text-lg font-bold text-gray-800" x-text="formatTime(order.created_at)"></div>
             <div class="text-xs font-mono text-red-500 mt-1" x-text="'+ ' + timeElapsed(order.created_at)"></div>
        </div>
    </div>
    
    <!-- Divider -->
    <div class="h-px bg-gray-100 mb-3"></div>
    
    <!-- Items List -->
    <div class="space-y-2 mb-4">
        <!-- Initial Items -->
        <template x-for="item in order.items.filter(i => !isAddedLater(i, order))" :key="item.id">
            <div class="pb-2 border-b border-gray-50 last:border-0">
                <div class="flex justify-between items-start">
                    <div class="flex items-start space-x-2">
                        <span class="font-bold text-gray-700 bg-gray-100 px-1.5 rounded text-sm" x-text="item.quantity + 'x'"></span>
                        <span class="text-gray-800 font-medium leading-tight" x-text="item.name"></span>
                    </div>
                </div>
                
                <!-- Notes Block -->
                <template x-if="item.notes">
                    <div class="mt-1 ml-6 p-2 bg-yellow-50 border border-yellow-200 rounded-md text-yellow-800 text-sm font-semibold shadow-sm">
                        <div class="flex items-center space-x-1 mb-0.5">
                            <svg class="w-3 h-3 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span class="uppercase tracking-wider text-[10px] text-yellow-600 font-bold">Observații:</span>
                        </div>
                        <span x-text="item.notes" class="block"></span>
                    </div>
                </template>
            </div>
        </template>

        <!-- Added Items Section Header -->
        <template x-if="order.items.some(i => isAddedLater(i, order))">
            <div class="flex items-center space-x-2 my-3">
                <div class="h-px bg-amber-200 flex-grow"></div>
                <span class="text-[9px] font-bold text-amber-700 uppercase tracking-widest bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200 animate-pulse flex items-center space-x-1">
                    <span class="inline-block w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                    <span>Produse Adăugate Ulterior</span>
                </span>
                <div class="h-px bg-amber-200 flex-grow"></div>
            </div>
        </template>

        <!-- Added Items -->
        <template x-for="item in order.items.filter(i => isAddedLater(i, order))" :key="item.id">
            <div class="pb-2 border-b border-amber-100 last:border-0 bg-amber-50/70 p-2 rounded-lg border-l-4 border-amber-500 shadow-sm transition-all hover:bg-amber-100/50">
                <div class="flex justify-between items-start">
                    <div class="flex items-start space-x-2">
                        <span class="font-bold text-amber-800 bg-amber-100 px-1.5 rounded text-sm border border-amber-200" x-text="item.quantity + 'x'"></span>
                        <span class="text-amber-900 font-bold leading-tight text-sm" x-text="item.name"></span>
                    </div>
                    <!-- Small indicator badge -->
                    <span class="text-[9px] font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded border border-amber-200 uppercase tracking-wider">Nou</span>
                </div>
                
                <!-- Notes Block -->
                <template x-if="item.notes">
                    <div class="mt-1.5 ml-6 p-2 bg-yellow-50 border border-yellow-200 rounded-md text-yellow-800 text-xs font-semibold shadow-xs">
                        <div class="flex items-center space-x-1 mb-0.5">
                            <svg class="w-3 h-3 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span class="uppercase tracking-wider text-[9px] text-yellow-600 font-bold">Observații:</span>
                        </div>
                        <span x-text="item.notes" class="block"></span>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <!-- Order Level Notes -->
    <template x-if="order.notes">
         <div class="bg-yellow-50 p-2 rounded text-xs text-yellow-800 mb-4 border border-yellow-200">
            <strong>Note Comandă:</strong> <span x-text="order.notes"></span>
        </div>
    </template>

    <!-- Action Button -->
    <button 
        @click="updateStatus(order.id, '{{ $nextStatus }}')"
        class="w-full py-3 {{ $btnColor }} text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all transform active:scale-95 flex justify-center items-center uppercase tracking-wider text-sm"
    >
        {{ $btnText }}
    </button>
</div>
