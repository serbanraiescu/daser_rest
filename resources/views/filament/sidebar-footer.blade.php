@php
    $logoBlackPath = base_path('public/images/daser_tech_logo_black.png');
    $logoBlackBase64 = '';
    if (file_exists($logoBlackPath)) {
        $logoBlackBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoBlackPath));
    }

    $logoWhitePath = base_path('public/images/daser_tech_logo_white.png');
    $logoWhiteBase64 = '';
    if (file_exists($logoWhitePath)) {
        $logoWhiteBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoWhitePath));
    }
@endphp

<div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 text-center flex flex-col items-center gap-1">
    <span class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold">Powered by</span>
    <div class="mt-1">
        @if($logoBlackBase64)
            <!-- Light Mode Logo (Black) -->
            <img src="{{ $logoBlackBase64 }}" class="h-6 w-auto block dark:hidden" alt="Daser Technologies">
        @endif
        @if($logoWhiteBase64)
            <!-- Dark Mode Logo (White) -->
            <img src="{{ $logoWhiteBase64 }}" class="h-6 w-auto hidden dark:block" alt="Daser Technologies">
        @endif
    </div>
</div>
