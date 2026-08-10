@extends('layouts.public')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center min-h-[90vh] overflow-hidden" 
         style="background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.7)), url('{{ $settings?->hero_background_image ? asset('storage/' . $settings->hero_background_image) : 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974' }}'); background-size: cover; background-position: center;">
        
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-1/4 -left-10 w-64 h-64 bg-primary/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 -right-10 w-96 h-96 bg-primary/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:text-left relative z-10 w-full">
            <div class="inline-block px-4 py-1 rounded-full bg-primary/20 text-primary border border-primary/30 text-sm font-bold tracking-widest uppercase mb-6 animate-fade-in">
                {{ $settings->site_name ?? 'Daser Restaurant OS' }}
            </div>
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold text-white mb-8 leading-tight tracking-tight">
                @php
                    $title = $settings->hero_title ?? __('Taste the Extraordinary');
                    $parts = explode(' ', $title);
                    $last = array_pop($parts);
                @endphp
                {!! implode(' ', $parts) !!} <span class="text-primary">{{ $last }}</span>
            </h1>
            <p class="text-xl md:text-2xl text-gray-200 mb-12 max-w-2xl font-light leading-relaxed">
                {{ $settings->hero_description ?? __('Savor artisanal dishes crafted with passion and the finest local ingredients.') }}
            </p>
            <div class="flex flex-col md:flex-row gap-6 justify-center md:justify-start">
                <a href="{{ route('menu.index') }}" class="bg-primary text-white text-xl px-10 py-5 rounded-2xl font-bold transition-all hover:scale-105 hover:shadow-2xl hover:shadow-primary/30 flex items-center justify-center gap-3 group">
                    <span>{{ __('Vezi Meniu Complet') }}</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
                <a href="{{ route('gallery') }}" class="bg-white/10 hover:bg-white/20 backdrop-blur-xl text-white text-xl px-10 py-5 rounded-2xl font-bold border border-white/20 transition-all flex items-center justify-center">
                    {{ __('Galerie Foto') }}
                </a>
            </div>
        </div>
    </div>

    @php
        $featuredImages = $featuredAlbums->flatMap(fn ($album) => $album->images->map(fn ($image) => ['album' => $album, 'image' => $image]))->take(6);
    @endphp

    @if($featuredImages->isNotEmpty())
        <section class="bg-gray-50 py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between gap-6 mb-10">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-primary">Momente speciale</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Galerii & Evenimente</h2>
                    </div>
                    <a href="{{ route('gallery') }}" class="hidden sm:inline-flex text-sm font-bold text-primary hover:underline">Vezi toate fotografiile</a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-5">
                    @foreach($featuredImages as $entry)
                        <a href="{{ route('gallery') }}#album-{{ $entry['album']->id }}" class="group relative aspect-[4/3] overflow-hidden rounded-2xl bg-gray-200">
                            <img src="{{ asset('storage/'.$entry['image']->image) }}"
                                 alt="{{ $entry['image']->alt_text ?: $entry['image']->caption ?: $entry['album']->title }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pt-10 pb-3 text-sm font-semibold text-white">{{ $entry['album']->title }}</span>
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('gallery') }}" class="mt-8 sm:hidden inline-flex text-sm font-bold text-primary">Vezi toate fotografiile</a>
            </div>
        </section>
    @endif

    @if($settings->show_google_reviews && $settings->google_rating)
        <section class="bg-white py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-sm font-bold uppercase tracking-widest text-primary">Google</p>
                <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">{{ $settings->google_reviews_title ?: 'Ce spun clienții noștri' }}</h2>

                <div class="mt-8 inline-flex flex-col items-center rounded-3xl border border-gray-100 bg-gray-50 px-10 py-8 shadow-sm">
                    <div class="text-5xl font-black text-gray-900">{{ number_format((float) $settings->google_rating, 1, ',', '.') }}</div>
                    <div class="mt-3 flex gap-1 text-2xl text-amber-400" aria-label="Rating {{ $settings->google_rating }} din 5">
                        @for($star = 1; $star <= 5; $star++)
                            <span class="{{ $star <= round((float) $settings->google_rating) ? '' : 'text-gray-300' }}">★</span>
                        @endfor
                    </div>
                    @if($settings->google_review_count)
                        <p class="mt-2 text-sm text-gray-500">din {{ number_format($settings->google_review_count, 0, ',', '.') }} recenzii pe Google</p>
                    @endif
                </div>

                <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                    @if($settings->google_reviews_url)
                        <a href="{{ $settings->google_reviews_url }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl border border-gray-200 bg-white px-6 py-3 font-bold text-gray-800 hover:border-primary hover:text-primary transition-colors">Vezi recenziile pe Google</a>
                    @endif
                    @if($settings->google_review_form_url)
                        <a href="{{ $settings->google_review_form_url }}" target="_blank" rel="noopener noreferrer" class="rounded-2xl bg-primary px-6 py-3 font-bold text-white shadow-lg shadow-primary/20 hover:scale-105 transition-transform">Lasă-ne o recenzie</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <!-- Contact & Hours Section -->
    <div id="contact" class="bg-white py-20">
         <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16">
                <!-- Contact Info -->
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-8">{{ __('Visit Us') }}</h2>
                    <p class="text-xl text-gray-600 mb-4">{{ $settings->address ?? '123 Culinary Ave, Food City' }}</p>
                    <p class="text-xl text-gray-600 mb-8">
                        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="hover:text-primary transition-colors">
                            {{ $settings->contact_phone ?? '+40 700 123 456' }}
                        </a>
                    </p>
                    
                    @if($settings?->social_links)
                    <div class="flex space-x-4 mt-6">
                        @foreach($settings->social_links as $social)
                            <a href="{{ $social['url'] }}" target="_blank" class="text-gray-400 hover:text-primary transform hover:scale-110 transition-all">
                                <span class="capitalize font-semibold">{{ $social['platform'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Opening Hours -->
                <div class="bg-gray-50 p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="h-6 w-6 text-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('Opening Hours') }}
                    </h2>
                    <div class="space-y-4">
                        @if($settings)
                            @foreach($settings->getFormattedOpeningHours() as $slot)
                                <div class="flex justify-between items-center border-b border-gray-200 pb-2 last:border-0 last:pb-0">
                                    <span class="font-medium text-gray-700">{{ $slot['day'] }}</span>
                                    <span class="text-gray-500">{{ $slot['hours'] }}</span>
                                </div>
                            @endforeach
                        @else
                            <p class="text-gray-500">{{ __('Schedule not available.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
         </div>
    </div>
@endsection
