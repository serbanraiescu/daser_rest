<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <x-filament::button type="submit">Salvează configurația API</x-filament::button>
    </form>

    <x-filament::section heading="Conectare în n8n">
        <div class="space-y-3 text-sm">
            <p>Folosește un nod HTTP Request cu metoda GET și credențial Header Auth. Numele antetului este <code>Authorization</code>, iar valoarea este <code>Bearer TOKENUL_CONFIGURAT</code>.</p>
            <dl class="space-y-3">
                <div>
                    <dt class="font-semibold">Raport zilnic</dt>
                    <dd class="break-all"><code>{{ $dailyUrl }}?date=YYYY-MM-DD</code></dd>
                </div>
                <div>
                    <dt class="font-semibold">Raport lunar</dt>
                    <dd class="break-all"><code>{{ $monthlyUrl }}?month=YYYY-MM</code></dd>
                </div>
            </dl>
            <p>Fusul orar al rapoartelor este {{ $timezone }}. Pentru perioade încheiate, n8n transmite ziua sau luna precedentă.</p>
            <p>Pentru toate lucrările, citește paginile cu <code>page</code> până la <code>orders.last_page</code> și combină listele <code>orders.data</code>. Folosește sumarul primei pagini o singură dată.</p>
            <p>Programarea și trimiterea e-mailurilor se configurează în n8n.</p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
