<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Area - {{ $settings->site_name ?? 'Daser Restaurant' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Outfit', sans-serif; }
        /* Dark mode overrides if needed */
        .bg-gray-900 { background-color: #111827; }
    </style>
</head>
<body class="bg-gray-900 text-white antialiased flex flex-col min-h-screen">
    
    <!-- Minimal Staff Header -->
    <header class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex justify-between items-center z-40 relative">
        <div class="flex items-center gap-3">
             @if($settings?->company_logo)
                <img class="h-8 w-auto" src="{{ asset('storage/' . $settings->company_logo) }}" alt="Logo">
            @else
                <span class="text-xl font-bold text-orange-500">{{ $settings->site_name ?? 'Daser' }}</span>
            @endif
            <span class="bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded font-mono uppercase">Staff Only</span>
        </div>
        
        <div>
            <!-- Logout / Exit Button -->
            <!-- Authenticated Staff -->
            @if(session()->has('staff_id'))
            <form action="{{ route('staff.logout') }}" method="post">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-gray-400 hover:text-red-400 transition-colors text-sm font-semibold">
                    <span>Logout</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
            @else
            <a href="/" class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors text-sm font-semibold">
                <span>Exit to Site</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
            @endauth
        </div>
    </header>

    <!-- Content -->
    <main class="flex-grow">
        @yield('content')
    </main>
    
</body>
</html>
