<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end mt-4">
            <x-filament::button type="submit" size="lg">
                Salvează Toate Setările Platformei
            </x-filament::button>
        </div>
    </form>

    <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-medium text-red-600 mb-2">Zonă Periculoasă (Mentenanță)</h3>
        <p class="text-gray-500 mb-4 text-sm">Acțiunile de mai jos sunt ireversibile și sunt destinate doar administratorilor sistemului.</p>
        
        {{ $this->resetSystemAction }}
    </div>
</x-filament-panels::page>
