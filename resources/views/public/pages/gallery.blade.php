@extends('layouts.public')

@section('content')
<div class="py-16 bg-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ open: false, activeImage: '', activeAlt: '' }">
        <h1 class="text-4xl font-bold text-gray-900 mb-10 border-b pb-4 text-center">{{ $title }}</h1>

        @forelse($albums as $album)
            <section id="album-{{ $album->id }}" class="mb-16 last:mb-0 scroll-mt-24">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ $album->title }}</h2>
                        @if($album->description)
                            <p class="mt-2 text-gray-600 max-w-3xl">{{ $album->description }}</p>
                        @endif
                    </div>
                    @if($album->event_date)
                        <time class="text-sm font-semibold text-primary">{{ $album->event_date->format('d.m.Y') }}</time>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($album->images as $image)
                        @php($imageUrl = asset('storage/'.$image->image))
                        <button type="button"
                                class="group relative aspect-square overflow-hidden rounded-2xl shadow-md hover:shadow-xl transition-all duration-500 text-left"
                                @click="activeImage = @js($imageUrl); activeAlt = @js($image->alt_text ?: $image->caption ?: $album->title); open = true">
                            <img src="{{ $imageUrl }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                 loading="lazy"
                                 alt="{{ $image->alt_text ?: $image->caption ?: $album->title }}">
                            @if($image->caption)
                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pt-10 pb-3 text-sm font-medium text-white">{{ $image->caption }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-2xl p-20 text-center">
                <h3 class="text-xl font-medium text-gray-900 mb-2">Nicio galerie publicată încă</h3>
                <p class="text-gray-500">Fotografiile vor apărea aici după publicarea unui album.</p>
            </div>
        @endforelse

        <div x-show="open" x-cloak x-transition.opacity
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
             @keydown.escape.window="open = false" @click.self="open = false">
            <button type="button" @click="open = false" class="absolute top-6 right-6 text-white text-4xl" aria-label="Închide">&times;</button>
            <img :src="activeImage" :alt="activeAlt" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl object-contain">
        </div>
    </div>
</div>
@endsection
