@extends('layouts.public')

@section('content')
<div class="py-16 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-8 border-b pb-4">{{ $title }}</h1>
        
        <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
            {!! $content !!}
        </div>

        @if(empty($content))
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl p-12 text-center">
                <p class="text-gray-500 italic">Conținutul pentru această pagină nu a fost încă adăugat din panoul de control.</p>
            </div>
        @endif
    </div>
</div>
@endsection
