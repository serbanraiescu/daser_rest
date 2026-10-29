@extends('layouts.service')

@section('content')
<script>
function serviceDesk(categories, openOrders) {
    return {
        categories, openOrders, activeRootId: null, activeSubcategoryId: null, cart: [], orderId: null,
        vehicleNumber: '', customerName: '', customerPhone: '', notes: '', showDetails: false,
        showOpenOrders: false, showMobileCart: false, showPaymentModal: false,
        paymentMethod: 'cash', paymentNotes: '', busy: false,

        init() { if (this.categories.length) this.selectRoot(this.categories[0].id); },
        activeRoot() { return this.categories.find(category => category.id === this.activeRootId) ?? null; },
        subcategories() { return this.activeRoot()?.children ?? []; },
        visibleItems() {
            const root = this.activeRoot();
            if (!root) return [];
            if (!this.activeSubcategoryId) return root.items ?? [];
            return root.children.find(category => category.id === this.activeSubcategoryId)?.items ?? [];
        },
        selectRoot(rootId) {
            this.activeRootId = rootId;
            this.activeSubcategoryId = this.activeRoot()?.children?.[0]?.id ?? null;
        },
        addToCart(item) {
            const existing = this.cart.find(line => line.service_item_id === item.id);
            if (existing) existing.quantity = Number(existing.quantity) + 1;
            else this.cart.push({
                service_item_id: item.id, name: item.name, unit_price: Number(item.price),
                quantity: 1, unit: item.unit || 'buc.', notes: '',
            });
        },
        changeQuantity(itemId, amount) {
            const line = this.cart.find(item => item.service_item_id === itemId);
            if (!line) return;
            line.quantity = Number(line.quantity) + amount;
            if (line.quantity <= 0) this.cart = this.cart.filter(item => item.service_item_id !== itemId);
        },
        total() { return this.cart.reduce((sum, item) => sum + Number(item.unit_price) * Number(item.quantity), 0); },
        clearForm() {
            this.orderId = null; this.cart = []; this.vehicleNumber = ''; this.customerName = '';
            this.customerPhone = ''; this.notes = ''; this.paymentNotes = ''; this.showDetails = false;
        },
        loadOrder(order) {
            this.clearForm();
            this.orderId = order.id; this.vehicleNumber = order.vehicle_number || '';
            this.customerName = order.customer_name || ''; this.customerPhone = order.customer_phone || '';
            this.notes = order.notes || ''; this.showDetails = Boolean(this.customerName || this.customerPhone || this.notes);
            this.cart = order.items.map(item => ({
                service_item_id: item.service_item_id, name: item.name, unit_price: Number(item.unit_price),
                quantity: Number(item.quantity), unit: item.unit || 'buc.', notes: item.notes || '',
            }));
            this.showOpenOrders = false; this.showMobileCart = true;
        },
        async request(url, options) {
            const response = await fetch(url, options);
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) throw new Error(data.message || 'Operația nu a putut fi finalizată.');
            return data;
        },
        async saveOrder(openPayment = false) {
            if (!this.cart.length || this.busy) return;
            this.busy = true;
            try {
                const data = await this.request('{{ route('service.orders.store') }}', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({
                        order_id: this.orderId, vehicle_number: this.vehicleNumber, customer_name: this.customerName,
                        customer_phone: this.customerPhone, notes: this.notes,
                        items: this.cart.map(item => ({service_item_id: item.service_item_id, quantity: item.quantity, notes: item.notes})),
                    }),
                });
                this.orderId = data.order.id;
                if (openPayment) this.showPaymentModal = true;
                else window.location.reload();
            } catch (error) { alert(error.message); }
            finally { this.busy = false; }
        },
        async finalizePayment() {
            if (!this.orderId || this.busy) return;
            this.busy = true;
            try {
                await this.request(`/service/orders/${this.orderId}/complete`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({payment_method: this.paymentMethod, notes: this.paymentNotes}),
                });
                window.location.reload();
            } catch (error) { alert(error.message); }
            finally { this.busy = false; }
        },
        async cancelOrder() {
            if (!this.orderId || this.busy || !confirm('Anulezi această fișă?')) return;
            this.busy = true;
            try {
                await this.request(`/service/orders/${this.orderId}/cancel`, {
                    method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                });
                window.location.reload();
            } catch (error) { alert(error.message); }
            finally { this.busy = false; }
        },
    };
}
</script>

<div x-data="serviceDesk(@js($categories), @js($openOrders))" class="min-h-screen bg-slate-100 text-slate-950 lg:h-screen lg:overflow-hidden">
    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-3 py-3 shadow-sm sm:px-5">
        <div>
            <h1 class="text-lg font-black sm:text-2xl">Vulcanizare</h1>
            <p class="text-xs font-bold text-slate-500">Operator: {{ session('staff_name') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="showOpenOrders = !showOpenOrders" class="rounded-xl bg-blue-100 px-3 py-3 text-sm font-black text-blue-800">
                Fișe deschise (<span x-text="openOrders.length"></span>)
            </button>
            <form action="{{ route('staff.logout') }}" method="POST">
                @csrf
                <button class="rounded-xl bg-slate-800 px-3 py-3 text-sm font-black text-white">Ieșire</button>
            </form>
        </div>
    </header>

    <div x-show="showOpenOrders" x-cloak class="absolute inset-x-2 top-20 z-50 max-h-[70vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl sm:left-auto sm:right-4 sm:w-[420px]">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-black">Fișe deschise</h2>
            <button @click="showOpenOrders = false" class="rounded-lg bg-slate-100 px-3 py-2 font-black">Închide</button>
        </div>
        <div class="space-y-2">
            <template x-for="order in openOrders" :key="order.id">
                <button type="button" @click="loadOrder(order)" class="flex w-full items-center justify-between rounded-xl border-2 border-slate-200 p-4 text-left active:border-blue-500">
                    <span><strong class="block text-lg" x-text="order.vehicle_number || 'Fără număr auto'"></strong><small class="text-slate-500" x-text="order.customer_name || 'Client ocazional'"></small></span>
                    <strong class="text-lg"><span x-text="Number(order.total).toFixed(2)"></span> lei</strong>
                </button>
            </template>
            <p x-show="!openOrders.length" class="py-8 text-center font-bold text-slate-500">Nu există fișe deschise.</p>
        </div>
    </div>

    <main class="lg:flex lg:h-[calc(100vh-73px)]">
        <section class="flex min-w-0 flex-1 flex-col p-3 sm:p-5 lg:overflow-hidden">
            <div class="grid grid-cols-2 gap-3">
                <template x-for="category in categories" :key="category.id">
                    <button type="button" @click="selectRoot(category.id)" class="min-h-16 rounded-2xl border-4 px-4 py-3 text-xl font-black transition" :class="activeRootId === category.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-white bg-white text-slate-700 shadow-sm'" x-text="category.name"></button>
                </template>
            </div>

            <div class="mt-3 flex gap-2 overflow-x-auto pb-2" x-show="subcategories().length">
                <template x-for="category in subcategories()" :key="category.id">
                    <button type="button" @click="activeSubcategoryId = category.id" class="min-h-14 shrink-0 rounded-xl border-2 px-5 py-3 text-base font-black" :class="activeSubcategoryId === category.id ? 'border-amber-500 bg-amber-400 text-slate-950' : 'border-slate-300 bg-white text-slate-700'" x-text="category.name"></button>
                </template>
            </div>

            <div class="mt-2 grid flex-1 grid-cols-2 content-start gap-3 overflow-y-auto pb-28 sm:grid-cols-3 xl:grid-cols-4 lg:pb-4">
                <template x-for="item in visibleItems()" :key="item.id">
                    <button type="button" @click="addToCart(item)" class="flex min-h-32 flex-col justify-between rounded-2xl border-2 border-slate-200 bg-white p-4 text-left shadow-sm transition active:scale-95 active:border-emerald-500 active:bg-emerald-50">
                        <strong class="text-base leading-tight" x-text="item.name"></strong>
                        <span class="mt-3 text-right text-2xl font-black text-emerald-700"><span x-text="Number(item.price).toLocaleString('ro-RO', {maximumFractionDigits: 2})"></span> lei</span>
                    </button>
                </template>
                <p x-show="!visibleItems().length" class="col-span-full py-10 text-center text-lg font-bold text-slate-500">Nu sunt servicii active aici.</p>
            </div>
        </section>

        <div x-show="showMobileCart" x-cloak @click="showMobileCart = false" class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

        <aside class="fixed inset-y-0 right-0 z-40 flex w-[92vw] max-w-[440px] flex-col border-l border-slate-200 bg-white shadow-2xl transition-transform lg:static lg:w-[430px] lg:translate-x-0" :class="showMobileCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
            <div class="flex items-center justify-between border-b border-slate-200 p-4">
                <div><h2 class="text-xl font-black" x-text="orderId ? 'Fișa #' + orderId : 'Fișă nouă'"></h2><p class="text-sm font-bold text-slate-500">Apasă serviciile din listă</p></div>
                <div class="flex gap-2"><button @click="clearForm()" class="rounded-xl bg-slate-100 px-3 py-3 text-sm font-black">Fișă nouă</button><button @click="showMobileCart = false" class="rounded-xl bg-slate-800 px-3 py-3 font-black text-white lg:hidden">×</button></div>
            </div>

            <div class="border-b border-slate-200 p-4">
                <label class="mb-1 block text-sm font-black">Număr mașină <span class="font-medium text-slate-400">(opțional)</span></label>
                <input x-model="vehicleNumber" type="text" placeholder="B 123 ABC" class="w-full rounded-xl border-2 border-slate-300 px-4 py-3 text-xl font-black uppercase outline-none focus:border-blue-600">
                <button type="button" @click="showDetails = !showDetails" class="mt-3 text-sm font-black text-blue-700"><span x-text="showDetails ? 'Ascunde detaliile clientului' : '+ Detalii client (opțional)'"></span></button>
                <div x-show="showDetails" x-cloak class="mt-3 grid gap-2">
                    <input x-model="customerName" type="text" placeholder="Nume client" class="rounded-xl border-2 border-slate-300 px-4 py-3 font-bold">
                    <input x-model="customerPhone" type="tel" placeholder="Telefon" class="rounded-xl border-2 border-slate-300 px-4 py-3 font-bold">
                    <input x-model="notes" type="text" placeholder="Observații" class="rounded-xl border-2 border-slate-300 px-4 py-3 font-bold">
                </div>
            </div>

            <div class="flex-1 space-y-2 overflow-y-auto p-4">
                <template x-for="item in cart" :key="item.service_item_id">
                    <div class="rounded-xl border-2 border-slate-200 p-3">
                        <div class="flex justify-between gap-3"><strong class="leading-tight" x-text="item.name"></strong><strong class="whitespace-nowrap"><span x-text="(item.unit_price * item.quantity).toFixed(2)"></span> lei</strong></div>
                        <div class="mt-3 flex items-center justify-end gap-2"><button @click="changeQuantity(item.service_item_id, -1)" class="h-12 w-12 rounded-xl bg-red-100 text-2xl font-black text-red-700">−</button><span class="min-w-10 text-center text-xl font-black" x-text="item.quantity"></span><button @click="changeQuantity(item.service_item_id, 1)" class="h-12 w-12 rounded-xl bg-emerald-100 text-2xl font-black text-emerald-700">+</button></div>
                    </div>
                </template>
                <div x-show="!cart.length" class="py-16 text-center text-lg font-black text-slate-400">Alege un serviciu</div>
            </div>

            <div class="border-t border-slate-200 bg-white p-4">
                <div class="mb-3 flex items-center justify-between"><span class="text-lg font-black">TOTAL</span><span class="text-3xl font-black text-emerald-700"><span x-text="total().toFixed(2)"></span> lei</span></div>
                <div class="grid grid-cols-3 gap-2" x-show="cart.length"><button @click="saveOrder(false)" :disabled="busy" class="rounded-xl bg-slate-200 px-2 py-4 text-sm font-black text-slate-800 disabled:opacity-50">Lasă deschisă</button><button @click="saveOrder(true)" :disabled="busy" class="col-span-2 rounded-xl bg-emerald-500 px-3 py-4 text-xl font-black text-white shadow-lg disabled:opacity-50">ÎNCASEAZĂ</button></div>
                <button x-show="orderId" @click="cancelOrder()" :disabled="busy" class="mt-2 w-full rounded-xl bg-red-100 py-3 text-sm font-black text-red-700 disabled:opacity-50">Anulează fișa</button>
            </div>
        </aside>
    </main>

    <button x-show="cart.length && !showMobileCart" x-cloak @click="showMobileCart = true" class="fixed bottom-4 left-4 right-4 z-20 flex items-center justify-between rounded-2xl bg-emerald-500 px-5 py-4 text-xl font-black text-white shadow-2xl lg:hidden"><span>VEZI FIȘA</span><span><span x-text="total().toFixed(2)"></span> lei</span></button>

    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <div class="w-full max-w-lg rounded-3xl bg-white p-5 shadow-2xl">
            <h2 class="text-center text-2xl font-black">Cum plătește?</h2>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <template x-for="method in [{value: 'cash', label: 'NUMERAR'}, {value: 'card', label: 'CARD'}, {value: 'mixed', label: 'MIXT'}, {value: 'protocol', label: 'PROTOCOL'}]" :key="method.value">
                    <button type="button" @click="paymentMethod = method.value" class="min-h-20 rounded-2xl border-4 text-lg font-black" :class="paymentMethod === method.value ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-700'" x-text="method.label"></button>
                </template>
            </div>
            <div class="my-5 text-center"><p class="text-sm font-black text-slate-500">TOTAL DE PLATĂ</p><p class="text-4xl font-black text-emerald-700"><span x-text="total().toFixed(2)"></span> lei</p></div>
            <button @click="finalizePayment()" :disabled="busy" class="w-full rounded-2xl bg-emerald-500 py-5 text-xl font-black text-white disabled:opacity-50">CONFIRMĂ PLATA</button>
            <button @click="showPaymentModal = false" :disabled="busy" class="mt-2 w-full rounded-xl bg-slate-100 py-4 font-black text-slate-700">Înapoi</button>
        </div>
    </div>
</div>
@endsection
