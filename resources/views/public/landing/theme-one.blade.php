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
                {{ $settings->site_name ?? 'RestaurantOS' }}
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

    <!-- Additional Content / About could go here -->

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
                        @if($settings?->opening_hours)
                            @foreach($settings->opening_hours as $slot)
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
