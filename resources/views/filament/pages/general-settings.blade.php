<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end mt-4">
            <x-filament::button type="submit">
                Salvează Modificările
            </x-filament::button>
        </div>
    </form>

    <div class="mt-12 pt-8 border-t border-gray-200">
        <h3 class="text-lg font-medium text-red-600 mb-2">Zonă Periculoasă</h3>
        <p class="text-gray-500 mb-4 text-sm">Acțiunile de aici sunt ireversibile. Folosește-le cu precauție doar când vrei să cureți sistemul pentru livrare.</p>
        
        {{ $this->resetSystemAction }}
    </div>
</x-filament-panels::page>
