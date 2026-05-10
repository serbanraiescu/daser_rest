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
        :root {
            --primary-color: {{ $settings->frontend_colors['primary'] ?? '#eab308' }};
            --secondary-color: {{ $settings->frontend_colors['secondary'] ?? '#111827' }};
            --bg-color: {{ $settings->frontend_colors['background'] ?? '#ffffff' }};
            --text-color: {{ $settings->frontend_colors['text'] ?? '#1f2937' }};
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-color); color: var(--text-color); }
        .text-primary { color: var(--primary-color) !important; }
        .bg-primary { background-color: var(--primary-color) !important; }
        .border-primary { border-color: var(--primary-color) !important; }
        .hover\:text-primary:hover { color: var(--primary-color) !important; }
        .hover\:bg-primary:hover { background-color: var(--primary-color) !important; }
    </style>
</head>
<body class="antialiased flex flex-col min-h-screen">
    <x-cookie-consent :settings="$settings" />
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
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary font-medium">{{ __('Home') }}</a>
                    <a href="{{ route('menu.index') }}" class="text-gray-700 hover:text-primary font-medium">{{ __('Meniu') }}</a>
                    <a href="{{ route('gallery') }}" class="text-gray-700 hover:text-primary font-medium">{{ __('Galerie') }}</a>
                    <a href="{{ route('about') }}" class="text-gray-700 hover:text-primary font-medium">{{ __('Despre Noi') }}</a>
                </div>
                <div>
                     <a href="{{ route('menu.index') }}" class="bg-primary hover:opacity-90 text-white px-6 py-2 rounded-full font-semibold transition-all shadow-lg shadow-primary/20">
                        Comandă Online
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
    <footer class="bg-gray-900 text-white py-16 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <h3 class="text-xl font-bold mb-6 text-white">{{ $settings->site_name ?? 'Daser' }}</h3>
                    <p class="text-gray-400 leading-relaxed">{{ $settings->hero_description ?? 'Calitate și gust în fiecare porție.' }}</p>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-6 text-white">{{ __('Pagini') }}</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('about') }}" class="text-gray-400 hover:text-primary transition-colors">Despre Noi</a></li>
                        <li><a href="{{ route('gallery') }}" class="text-gray-400 hover:text-primary transition-colors">Galerie Foto</a></li>
                        <li><a href="{{ route('menu.index') }}" class="text-gray-400 hover:text-primary transition-colors">Meniu Restaurant</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-6 text-white">{{ __('Legal') }}</h3>
                    <ul class="space-y-4">
                        <li><a href="{{ route('terms') }}" class="text-gray-400 hover:text-primary transition-colors">Termeni și Condiții</a></li>
                        <li><a href="{{ route('gdpr') }}" class="text-gray-400 hover:text-primary transition-colors">GDPR</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-gray-400 hover:text-primary transition-colors">Confidențialitate</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-bold mb-6 text-white">{{ __('Contact') }}</h3>
                    <p class="text-gray-400 mb-2">{{ $settings->address ?? 'Adresă nesetată' }}</p>
                    <p class="text-primary font-bold mb-4">{{ $settings->contact_phone ?? 'Telefon nesetat' }}</p>
                    @if($settings?->social_links)
                        <div class="flex space-x-4">
                            @foreach($settings->social_links as $social)
                                <a href="{{ $social['url'] }}" target="_blank" class="bg-gray-800 p-2 rounded-lg text-gray-400 hover:text-primary hover:bg-gray-700 transition-all">
                                    {{ substr($social['platform'], 0, 2) }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
             <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
                 <p class="text-gray-500">&copy; {{ date('Y') }} {{ $settings->site_name ?? 'Daser Restaurant' }}. Toate drepturile rezervate.</p>
                 <div class="flex items-center gap-2 text-gray-700">
                    <span>Powered by</span>
                    <span class="font-bold">RestaurantOS</span>
                 </div>
             </div>
        </div>
    </footer>
</body>
</html>
