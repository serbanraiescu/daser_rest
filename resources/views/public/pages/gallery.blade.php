@extends('layouts.public')

@section('content')
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ open: false, activeImage: '' }">
        <h1 class="text-4xl font-bold text-gray-900 mb-8 border-b pb-4 text-center">{{ $title }}</h1>
        
        @if(!empty($gallery))
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($gallery as $image)
                    @php $imageUrl = asset('storage/' . $image); @endphp
                    <div class="group relative aspect-square overflow-hidden rounded-2xl shadow-md hover:shadow-xl transition-all duration-500 cursor-pointer"
                         @click="activeImage = '{{ $imageUrl }}'; open = true">
                        <img src="{{ $imageUrl }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Gallery Image">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white font-bold bg-primary px-4 py-2 rounded-full shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform">Mărește</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-2xl p-20 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Nicio imagine încă</h3>
                <p class="text-gray-500">Adăugați fotografii din panoul de administrare pentru a popula galeria.</p>
            </div>
        @endif

        <!-- Lightbox Modal -->
        <div x-show="open" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
             @keydown.escape.window="open = false"
             style="display: none;">
            
            <button @click="open = false" class="absolute top-6 right-6 text-white hover:text-primary transition-colors">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <img :src="activeImage" class="max-w-full max-h-full rounded-lg shadow-2xl object-contain animate-zoom-in" @click.away="open = false">
        </div>
    </div>
</div>
@endsection
