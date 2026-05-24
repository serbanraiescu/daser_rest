@if($heroStyle !== 'hidden')
    <div class="relative bg-gray-50 border-b border-gray-150/60 {{ $heroStyle === 'centered' ? 'py-10 text-center' : 'py-6 text-left' }}">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="{{ $heroStyle === 'compact' ? 'max-w-3xl' : 'mx-auto text-center' }} space-y-3">
                
                <!-- Table indicator if active -->
                <template x-if="tableNumber !== 'WEB'">
                    <div class="inline-flex bg-orange-600 text-white px-4 py-1.5 rounded-full font-bold shadow-sm text-xs uppercase tracking-wider mb-2">
                        Masa: <span x-text="tableNumber" class="ml-1"></span>
                    </div>
                </template>

                <!-- Title & Description -->
                <h1 class="{{ $heroStyle === 'centered' ? 'text-3xl sm:text-4xl' : 'text-2xl sm:text-3xl' }} font-extrabold text-gray-900 tracking-tight">
                    {{ $settings->hero_title ?: __('Our Menu') }}
                </h1>
                
                <p class="text-sm sm:text-base text-gray-500 {{ $heroStyle === 'centered' ? 'max-w-2xl mx-auto' : '' }}">
                    {{ $settings->hero_description ?: __('Discover our delicious offerings, crafted with care.') }}
                </p>
                
                <!-- Compact ordering disabled alert -->
                @if(!$settings->enable_ordering)
                    <div class="mt-4 p-3 bg-red-50 border border-red-100 text-red-800 text-xs sm:text-sm rounded-xl shadow-xs flex items-center gap-2 max-w-lg {{ $heroStyle === 'centered' ? 'mx-auto justify-center' : '' }}" role="alert">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div>
                            <span class="font-bold">Momentan nu preluăm comenzi online.</span> Vă rugăm să comandați la ospătar sau telefonic.
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endif
