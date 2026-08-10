@if(!$menu->categories->isEmpty())
    <div class="sticky top-[56px] z-30 bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm transition-all duration-300 py-2.5 overflow-x-auto no-scrollbar scroll-smooth print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-2 py-0.5 overflow-x-auto no-scrollbar scroll-smooth justify-start sm:justify-center">
                @foreach($menu->categories as $category)
                    <a 
                        href="#cat-{{ $category->id }}" 
                        class="px-4 py-1.5 bg-gray-50 border border-gray-200 hover:border-primary hover:text-primary rounded-full text-xs font-semibold text-gray-600 whitespace-nowrap transition-all shadow-2xs"
                    >
                        {{ $category->display_name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
