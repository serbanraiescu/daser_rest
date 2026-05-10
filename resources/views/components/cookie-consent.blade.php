@php
    $cookieSettings = $settings->cookie_consent ?? [];
    $enabled = $cookieSettings['enabled'] ?? false;
    $message = $cookieSettings['message'] ?? 'Acest site folosește cookies pentru o experiență mai bună.';
    $buttonText = $cookieSettings['button_text'] ?? 'Accept';
@endphp

@if($enabled)
<div x-data="{ 
        show: false,
        init() {
            if (!localStorage.getItem('cookie_consent_accepted')) {
                setTimeout(() => this.show = true, 1000);
            }
        },
        accept() {
            localStorage.setItem('cookie_consent_accepted', 'true');
            this.show = false;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak
    class="fixed bottom-0 left-0 right-0 z-[100] p-4 md:p-6"
>
    <div class="max-w-4xl mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4 text-gray-600 dark:text-gray-300">
            <div class="bg-primary/10 p-3 rounded-full hidden sm:block">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-sm md:text-base leading-relaxed">
                {{ $message }}
                <a href="/politica-de-confidentialitate" class="underline hover:text-primary transition-colors ml-1">Află mai multe.</a>
            </p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button @click="accept" class="w-full md:w-auto bg-primary text-white px-8 py-3 rounded-xl font-bold hover:opacity-90 transition-all shadow-lg shadow-primary/20">
                {{ $buttonText }}
            </button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endif
