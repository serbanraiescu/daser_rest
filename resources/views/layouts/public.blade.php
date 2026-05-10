<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->site_name ?? 'Daser Restaurant' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased flex flex-col min-h-screen">
    <!-- Navbar -->
    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('home') }}">
                        @if($settings?->company_logo)
                            <img class="h-12 w-auto" src="{{ asset('storage/' . $settings->company_logo) }}" alt="Logo">
                        @else
                            <span class="text-2xl font-bold text-orange-600">{{ $settings->site_name ?? 'Restaurant' }}</span>
                        @endif
                    </a>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-orange-600 font-medium">{{ __('Home') }}</a>
                    <a href="{{ route('home') }}#menu" class="text-gray-700 hover:text-orange-600 font-medium">{{ __('Menu') }}</a>
                    <a href="{{ route('home') }}#about" class="text-gray-700 hover:text-orange-600 font-medium">{{ __('About') }}</a>
                    <a href="{{ route('home') }}#contact" class="text-gray-700 hover:text-orange-600 font-medium">{{ __('Contact') }}</a>
                </div>
                <div>
                     <a href="{{ route('menu.index') }}" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-full font-semibold transition-all">
                        Order Online
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="flex-grow pt-20">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 text-center md:text-left">
                <div>
                    <h3 class="text-lg font-bold mb-4">{{ __('About Us') }}</h3>
                    <p class="text-gray-400">{{ $settings->hero_description ?? 'Quality food, excellent service.' }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">{{ __('Contact') }}</h3>
                    <p class="text-gray-400">{{ $settings->address ?? 'Address not set' }}</p>
                    <p class="text-gray-400">{{ $settings->contact_phone ?? 'Phone not set' }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-4">Social</h3>
                    @if($settings?->social_links)
                        <div class="flex justify-center md:justify-start space-x-4">
                            @foreach($settings->social_links as $social)
                                <a href="{{ $social['url'] }}" target="_blank" class="text-gray-400 hover:text-white transition-colors capitalize">
                                    {{ $social['platform'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
             <div class="border-t border-gray-800 pt-8 text-center">
                 <p class="text-gray-500">&copy; {{ date('Y') }} {{ $settings->site_name ?? 'Daser Restaurant' }}. All rights reserved.</p>
                 <p class="text-gray-700 text-sm mt-2">Powered by RestaurantOS</p>
             </div>
        </div>
    </footer>
</body>
</html>
