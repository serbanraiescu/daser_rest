<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        
        <div class="flex justify-end mt-4">
            <x-filament::button type="submit" size="lg">
                Salvează Toate Setările Platformei
            </x-filament::button>
        </div>
    </form>

    <div class="mt-10 rounded-xl border border-success-200 bg-success-50 p-6 dark:border-success-800 dark:bg-success-950/30">
        <h3 class="text-lg font-semibold text-success-700 dark:text-success-300">Actualizare platformă</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            După actualizarea codului din cPanel Git Version Control, folosește butonul pentru a rula migrările și a curăța cache-ul. Datele existente nu sunt șterse.
        </p>
        <div class="mt-4">
            {{ $this->deployUpdateAction }}
        </div>
    </div>

    <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-800 space-y-6">
        <div>
            <h3 class="text-lg font-medium text-red-600 mb-2">Zonă Periculoasă (Mentenanță)</h3>
            <p class="text-gray-500 text-sm">Acțiunile de mai jos sunt ireversibile și sunt destinate doar administratorilor sistemului.</p>
        </div>
        
        <div class="flex flex-wrap gap-4">
            {{ $this->clearOrderHistoryAction }}
            {{ $this->resetSystemAction }}
        </div>
    </div>
</x-filament-panels::page>
