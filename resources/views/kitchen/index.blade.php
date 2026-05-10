@extends('layouts.staff')

@section('content')
<div class="bg-gray-900 min-h-screen pt-4 pb-20 relative" x-data="kitchenApp('{{ $destination }}')">
    
    <!-- START OVERLAY (Forces Interaction for Audio) -->
    <div x-show="showStartOverlay" 
         class="fixed inset-0 z-50 bg-gray-900/95 backdrop-blur flex flex-col items-center justify-center text-center p-4 transition-opacity duration-500"
         style="display: none;"
    >
        <div class="mb-8">
            <svg class="w-24 h-24 text-orange-500 mx-auto mb-4 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
            <h1 class="text-4xl font-black text-white mb-2">Kitchen Display System</h1>
            <p class="text-gray-400 text-lg">Click to enable sound alerts & realtime updates</p>
        </div>
        <button @click="startApp()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 px-12 rounded-full text-xl shadow-lg transform transition hover:scale-105">
            START KDS 🚀
        </button>
    </div>

    <!-- Header -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 flex justify-between items-center text-white">
        <div class="flex items-center space-x-4">
            <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
            <div class="flex space-x-2 items-center">
                <span class="px-3 py-1 bg-gray-800 rounded-full text-xs font-mono" x-show="lastUpdated">Updated: <span x-text="lastUpdated"></span></span>
                <span class="px-3 py-1 bg-green-900 text-green-300 rounded-full text-xs font-bold animate-pulse" x-show="isLoading">Syncing...</span>
                <span class="px-3 py-1 bg-red-900 text-red-300 rounded-full text-xs font-bold" x-show="connectionError" style="display: none;">OFFLINE</span>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm font-mono text-gray-500" x-text="orders.length + ' active orders'"></span>
            <button @click="toggleSound()" class="p-2 rounded-full hover:bg-gray-800 transition relative" :class="soundEnabled ? 'text-green-400' : 'text-gray-500'">
                <svg x-show="soundEnabled" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>
                <svg x-show="!soundEnabled" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" /></svg>
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6 h-[calc(100vh-140px)]">
        
        <!-- PENDING (NOU) -->
        <div class="bg-gray-800 rounded-2xl flex flex-col border-t-4 border-blue-500 shadow-xl overflow-hidden">
            <div class="p-4 bg-gray-800/50 backdrop-blur border-b border-gray-700 sticky top-0 z-10 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white uppercase tracking-wider">Nou (Pending)</h2>
                <span class="bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded-full" x-text="orders.filter(o => o.status === 'pending').length">0</span>
            </div>
            <div class="flex-grow overflow-y-auto p-4 space-y-4 chrome-scrollbar">
                <template x-for="order in orders.filter(o => o.status === 'pending')" :key="order.id">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-l-4 border-blue-500 animate-slide-in">
                        @include('kitchen.partials.card', ['nextStatus' => 'preparing', 'btnColor' => 'bg-orange-600 hover:bg-orange-700', 'btnText' => 'Start Cooking'])
                    </div>
                </template>
                <div x-show="orders.filter(o => o.status === 'pending').length === 0" class="text-gray-500 text-center py-10 italic">
                    Nicio comandă nouă.
                </div>
            </div>
        </div>

        <!-- PREPARING (IN MODELING) -->
        <div class="bg-gray-800 rounded-2xl flex flex-col border-t-4 border-orange-500 shadow-xl overflow-hidden">
            <div class="p-4 bg-gray-800/50 backdrop-blur border-b border-gray-700 sticky top-0 z-10 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white uppercase tracking-wider">În Preparare</h2>
                <span class="bg-orange-600 text-white text-xs font-bold px-2 py-1 rounded-full" x-text="orders.filter(o => o.status === 'preparing').length">0</span>
            </div>
            <div class="flex-grow overflow-y-auto p-4 space-y-4 chrome-scrollbar">
               <template x-for="order in orders.filter(o => o.status === 'preparing')" :key="order.id">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-l-4 border-orange-500">
                        @include('kitchen.partials.card', ['nextStatus' => 'ready', 'btnColor' => 'bg-green-600 hover:bg-green-700', 'btnText' => 'Mark Ready'])
                    </div>
                </template>
                 <div x-show="orders.filter(o => o.status === 'preparing').length === 0" class="text-gray-500 text-center py-10 italic">
                    Bucătăria e liberă.
                </div>
            </div>
        </div>

        <!-- READY (GATA) -->
        <div class="bg-gray-800 rounded-2xl flex flex-col border-t-4 border-green-500 shadow-xl overflow-hidden">
            <div class="p-4 bg-gray-800/50 backdrop-blur border-b border-gray-700 sticky top-0 z-10 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white uppercase tracking-wider">Gata de Servire</h2>
                <span class="bg-green-600 text-white text-xs font-bold px-2 py-1 rounded-full" x-text="orders.filter(o => o.status === 'ready').length">0</span>
            </div>
            <div class="flex-grow overflow-y-auto p-4 space-y-4 chrome-scrollbar">
                <template x-for="order in orders.filter(o => o.status === 'ready')" :key="order.id">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-l-4 border-green-500 opacity-90 hover:opacity-100 transition-opacity">
                        @include('kitchen.partials.card', ['nextStatus' => 'delivered', 'btnColor' => 'bg-gray-800 hover:bg-gray-900', 'btnText' => 'Delivered'])
                    </div>
                </template>
                 <div x-show="orders.filter(o => o.status === 'ready').length === 0" class="text-gray-500 text-center py-10 italic">
                    Nimic de servit.
                </div>
            </div>
        </div>

    </div>
</div>

<audio id="alert-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<script>
    function kitchenApp(destination) {
        return {
            destination: destination,
            orders: [],
            isLoading: false,
            soundEnabled: false,
            showStartOverlay: true,
            lastUpdated: null,
            connectionError: false,
            pollInterval: null,

            startApp() {
                this.soundEnabled = true; // User gesture allows audio later
                this.showStartOverlay = false;
                this.fetchOrders();
                
                // Start Polling
                if (this.pollInterval) clearInterval(this.pollInterval);
                this.pollInterval = setInterval(() => this.fetchOrders(), 10000);
            },

            async fetchOrders() {
                this.isLoading = true;
                this.connectionError = false;
                
                try {
                    // Add timestamp to prevent caching
                    const timestamp = new Date().getTime();
                    const response = await fetch(`{{ route("kitchen.api.orders") }}?destination=${this.destination}&t=${timestamp}`);
                    
                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const newOrders = await response.json();
                    
                    // Check for NEW orders (compare ID lists)
                    if (this.orders.length > 0 && this.soundEnabled) {
                        const oldIds = this.orders.map(o => o.id);
                        const hasNewOrder = newOrders.some(o => !oldIds.includes(o.id) && o.status === 'pending');
                        
                        if (hasNewOrder) {
                            console.log('New order detected! Playing sound...');
                            this.playSound();
                        }
                    } else if (newOrders.length > 0 && this.orders.length === 0 && this.soundEnabled) {
                         // First load has pending orders? Maybe play sound too
                         if(newOrders.some(o => o.status === 'pending')) {
                             this.playSound();
                         }
                    }

                    this.orders = newOrders;
                    this.lastUpdated = new Date().toLocaleTimeString();
                    console.log('Orders synced:', this.orders.length);

                } catch (error) {
                    console.error('Fetch error:', error);
                    this.connectionError = true;
                } finally {
                    this.isLoading = false;
                }
            },

            async updateStatus(orderId, newStatus) {
                const orderIndex = this.orders.findIndex(o => o.id === orderId);
                if (orderIndex === -1) return;
                
                // Optimistic Update
                const oldStatus = this.orders[orderIndex].status;
                this.orders[orderIndex].status = newStatus;

                try {
                    const response = await fetch(`/kitchen/api/orders/${orderId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ 
                            status: newStatus,
                            destination: this.destination
                        })
                    });
                    
                    if(!response.ok) throw new Error('Update failed');
                    
                } catch (error) {
                    console.error('Update status failed:', error);
                    this.orders[orderIndex].status = oldStatus;
                    alert('Status update failed. Check connection.');
                }
            },
            
            toggleSound() {
                this.soundEnabled = !this.soundEnabled;
                if(this.soundEnabled) this.playSound();
            },

            playSound() {
                const audio = document.getElementById('alert-sound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.warn('Audio blocked:', e));
                }
            },
            
            formatTime(dateString) {
                if(!dateString) return '--:--';
                return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },
            
            timeElapsed(dateString) {
                if(!dateString) return '0m';
                const diff = new Date() - new Date(dateString);
                const minutes = Math.floor(diff / 60000);
                return minutes + 'm';
            }
        }
    }
</script>

<style>
    .chrome-scrollbar::-webkit-scrollbar { width: 6px; }
    .chrome-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); }
    .chrome-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    .chrome-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.4); }
    
    @keyframes slideIn {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .animate-slide-in {
        animation: slideIn 0.3s ease-out forwards;
    }
    
    [x-cloak] { display: none !important; }
</style>
@endsection
