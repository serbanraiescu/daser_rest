@extends('layouts.public')

@section('content')
    <!-- Hero Section -->
    <div class="relative flex items-center min-h-[80vh]" 
         style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('{{ $settings?->hero_background_image ? asset('storage/' . $settings->hero_background_image) : 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974' }}'); background-size: cover; background-position: center;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:text-left relative z-10 w-full">
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold text-white mb-6 leading-tight">
                {{ $settings->hero_title ?? __('Taste the Extraordinary') }}
            </h1>
            <p class="text-lg md:text-2xl text-gray-200 mb-10 max-w-2xl font-light">
                {{ $settings->hero_description ?? __('Savor artisanal dishes crafted with passion and the finest local ingredients.') }}
            </p>
            <div class="flex flex-col md:flex-row gap-4 justify-center md:justify-start">
                <a href="{{ route('menu.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white text-lg px-8 py-4 rounded-full font-bold transition-transform hover:scale-105 shadow-lg">
                    {{ __('View Full Menu') }}
                </a>
                <a href="#contact" class="bg-white/10 hover:bg-white/20 backdrop-blur text-white text-lg px-8 py-4 rounded-full font-bold border border-white/30 transition-all">
                    {{ __('Book a Table') }}
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
                        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="hover:text-orange-600 transition-colors">
                            {{ $settings->contact_phone ?? '+40 700 123 456' }}
                        </a>
                    </p>
                    
                    @if($settings?->social_links)
                    <div class="flex space-x-4 mt-6">
                        @foreach($settings->social_links as $social)
                            <a href="{{ $social['url'] }}" target="_blank" class="text-gray-400 hover:text-orange-600 transform hover:scale-110 transition-all">
                                <span class="capitalize font-semibold">{{ $social['platform'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>

                <!-- Opening Hours -->
                <div class="bg-gray-50 p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <svg class="h-6 w-6 text-orange-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
