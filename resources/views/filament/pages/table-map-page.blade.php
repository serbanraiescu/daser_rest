<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    
    <div x-data="tableEditor()" class="flex flex-col h-[calc(100vh-12rem)]">
        
        <!-- Header Controls (Area Tabs + Edit Mode) -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm gap-4">
            <div class="flex space-x-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 scrollbar-hide">
                @foreach($areas as $area)
                    <button 
                        wire:click="$set('activeAreaId', {{ $area->id }})"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors whitespace-nowrap border
                        {{ $activeAreaId === $area->id 
                            ? 'bg-primary-600 border-primary-600 text-white shadow-md' 
                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' 
                        }}"
                    >
                        {{ $area->name }}
                    </button>
                @endforeach
                
                <div class="ml-2">
                    {{ $this->createAreaAction }}
                </div>
            </div>
            
            <div class="flex items-center space-x-3 shrink-0">
                <!-- Area Actions (Edit/Delete) -->
                @if($activeAreaId)
                    <div class="flex items-center space-x-1 mr-2">
                        <x-filament::icon-button 
                            icon="heroicon-m-pencil-square" 
                            color="gray"
                            tooltip="Rename Area"
                            wire:click="mountAction('editArea')"
                        />
                        <x-filament::icon-button 
                            icon="heroicon-m-trash" 
                            color="danger"
                            tooltip="Delete Area"
                            wire:click="mountAction('deleteArea')"
                        />
                    </div>
                @endif

                 <div class="flex items-center space-x-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button 
                        @click="toggleEditMode(false)"
                        :class="!editMode ? 'bg-white dark:bg-gray-600 shadow text-primary-600' : 'text-gray-500'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
                    >
                        View
                    </button>
                    <button 
                         @click="toggleEditMode(true)"
                        :class="editMode ? 'bg-white dark:bg-gray-600 shadow text-primary-600' : 'text-gray-500'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
                    >
                        Edit
                    </button>
                </div>
                
                <!-- Add Table Buttons (Always Visible) -->
                <div class="flex space-x-2 pl-4 border-l border-gray-200 dark:border-gray-700">
                     <button wire:click="createTable('square'); editMode = true" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm flex items-center gap-2" title="Add Square Table">
                        <div class="w-3 h-3 bg-gray-400 rounded-sm"></div> <span class="hidden sm:inline">Square</span>
                    </button>
                    <button wire:click="createTable('round'); editMode = true" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm flex items-center gap-2" title="Add Round Table">
                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div> <span class="hidden sm:inline">Round</span>
                    </button>
                     <button wire:click="createTable('rectangle'); editMode = true" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm flex items-center gap-2" title="Add Rectangle Table">
                        <div class="w-4 h-2 bg-gray-400 rounded-sm"></div> <span class="hidden sm:inline">Rect</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="relative flex-1 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-inner w-full h-full flex items-center justify-center p-4" id="map-container">
            
            <!-- Scalable Map Content -->
            <div id="map-canvas" class="relative bg-white dark:bg-gray-800 shadow-sm transition-transform origin-center" 
                 style="width: 1000px; height: 800px; min-width: 1000px; min-height: 800px;">
                
                <!-- Grid Background -->
                <div class="absolute inset-0 opacity-20 pointer-events-none" 
                     style="background-image: radial-gradient(#6b7280 1px, transparent 1px); background-size: 20px 20px;">
                </div>

                @if($activeAreaId)
                    <!-- Tables Loop -->
                    @foreach($tables as $table)
                        <!-- Table Item (Same as before) -->
                        <div 
                            class="absolute flex flex-col items-center justify-center font-bold text-white shadow-lg transition-all table-item group"
                            data-id="{{ $table->id }}"
                            data-x="{{ $table->x }}"
                            data-y="{{ $table->y }}"
                            wire:dblclick.stop="mountAction('editTable', { id: {{ $table->id }} })"
                            style="
                                transform: translate({{ $table->x }}px, {{ $table->y }}px);
                                width: {{ $table->width }}px; 
                                height: {{ $table->height }}px;
                                background-color: {{ $table->seats > 4 ? '#ea580c' : ($table->shape === 'round' ? '#10b981' : '#3b82f6') }};
                                border-radius: {{ $table->shape === 'round' ? '50%' : '12px' }};
                                touch-action: none;
                                user-select: none;
                                z-index: 10;
                            "
                            :class="editMode ? 'cursor-move ring-2 ring-primary-500 ring-offset-2 ring-offset-gray-50 dark:ring-offset-gray-900 shadow-xl' : 'cursor-pointer hover:brightness-110'"
                        >
                            <span class="text-xs md:text-sm drop-shadow-md pointer-events-none">{{ $table->name }}</span>
                            <div class="flex items-center gap-1 mt-0.5 pointer-events-none">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-80" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-[10px] md:text-xs font-normal opacity-90">{{ $table->seats }}</span>
                            </div>
                            <!-- Edit Controls -->
                            <div x-show="editMode" class="absolute -top-2 -right-2 z-20" style="display: none;" x-show.important="editMode">
                                 <button wire:click.stop="deleteTable({{ $table->id }})" class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-sm hover:bg-red-600 focus:outline-none transform hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-full shadow-sm mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-1.447-.894L15 7m0 13V7m0 0L9 7" />
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Select an area above to manage the floor plan</p>
                    </div>
                @endif
            </div>
        </div>
        
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tableEditor', () => ({
                editMode: false,
                interactInstance: null,

                toggleEditMode(val) {
                    this.editMode = val;
                    // Force re-evaluation of interactable status if needed, 
                    // usually Alpine reactivity handles the classes/attributes, 
                    // but InteractJS needs explicit enable/disable calls often.
                    this.updateInteractState();
                },

                updateInteractState() {
                    interact('.table-item').draggable({ enabled: this.editMode });
                    interact('.table-item').resizable({ enabled: this.editMode });
                },

                init() {
                    const self = this;
                    
                    // Auto-scaling logic
                    this.autoScale();
                    window.addEventListener('resize', () => this.autoScale());
                    
                    // Initialize Interact.js
                    interact('.table-item')
                        .draggable({
                            enabled: false, // Start disabled (View mode)
                            inertia: true,
                            modifiers: [
                                interact.modifiers.restrictRect({
                                    restriction: 'parent',
                                    endOnly: true
                                })
                            ],
                            autoScroll: true,
                            listeners: {
                                move (event) {
                                    if (!self.editMode) return;
                                    
                                    var target = event.target;
                                    // Adjust dx/dy by scale factor to keep movement synced with cursor
                                    // Scale is stored on the container or calculated 
                                    var container = document.getElementById('map-canvas');
                                    var scale = self.getScale(); // Helper to get current scale
                                    
                                    var x = (parseFloat(target.getAttribute('data-x')) || 0) + (event.dx / scale);
                                    var y = (parseFloat(target.getAttribute('data-y')) || 0) + (event.dy / scale);

                                    // translate the element
                                    target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

                                    // update the posiion attributes
                                    target.setAttribute('data-x', x);
                                    target.setAttribute('data-y', y);
                                },
                                end (event) {
                                    if (!self.editMode) return;
                                    
                                    var target = event.target;
                                    var x = (parseFloat(target.getAttribute('data-x')) || 0);
                                    var y = (parseFloat(target.getAttribute('data-y')) || 0);
                                    
                                    // Call Livewire to save in background
                                    @this.updateTablePosition(target.dataset.id, x, y);
                                }
                            }
                        })
                        .resizable({
                            enabled: false, // Start disabled
                            edges: { left: true, right: true, bottom: true, top: true },
                            modifiers: [
                                // keep the edges inside the parent
                                interact.modifiers.restrictEdges({
                                    outer: 'parent'
                                }),
                                // minimum size
                                interact.modifiers.restrictSize({
                                    min: { width: 50, height: 50 }
                                }),
                            ],
                            listeners: {
                                move (event) {
                                    if (!self.editMode) return;
                                    
                                    var scale = self.getScale();
                                    var target = event.target;
                                    var x = (parseFloat(target.getAttribute('data-x')) || 0);
                                    var y = (parseFloat(target.getAttribute('data-y')) || 0);

                                    // update the element's style
                                    target.style.width = (event.rect.width / scale) + 'px';
                                    target.style.height = (event.rect.height / scale) + 'px';

                                    // translate when resizing from top or left edges
                                    x += (event.deltaRect.left / scale);
                                    y += (event.deltaRect.top / scale);

                                    target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

                                    target.setAttribute('data-x', x);
                                    target.setAttribute('data-y', y);
                                },
                                end (event) {
                                     if (!self.editMode) return;
                                     var target = event.target;
                                     // We need to parse styles back to numbers if needed, passing updated width/height
                                     var w = parseFloat(target.style.width);
                                     var h = parseFloat(target.style.height);
                                     @this.updateTableSize(target.dataset.id, w, h);
                                }
                            }
                        });
                        
                    // Watch for editMode changes to enable/disable interaction
                    // Interact.js enabled() method expects boolean
                    this.$watch('editMode', (value) => {
                         this.updateInteractState();
                    });
                },
                
                getScale() {
                    const container = document.getElementById('map-container');
                    const canvas = document.getElementById('map-canvas');
                    if (!container || !canvas) return 1;
                    
                    // Simple logic: if canvas is transformed, get scale from style
                    // But we are setting it via autoScale
                    const style = window.getComputedStyle(canvas);
                    const matrix = new WebKitCSSMatrix(style.transform);
                    return matrix.a; // 'a' is the scale X value in a simple 2d matrix
                },
                
                autoScale() {
                    const container = document.getElementById('map-container');
                    const canvas = document.getElementById('map-canvas');
                    if (!container || !canvas) return;

                    const containerWidth = container.clientWidth - 32; // padding
                    const containerHeight = container.clientHeight - 32;
                    const canvasWidth = 1000; // Fixed canvas size
                    const canvasHeight = 800;

                    const scaleX = containerWidth / canvasWidth;
                    const scaleY = containerHeight / canvasHeight;
                    
                    // Use the smaller scale to fit entirely, or max 1 if we don't want to upscale too much
                    // User said "fit to screen", so we scale down.
                    const scale = Math.min(scaleX, scaleY, 1); 
                    
                    canvas.style.transform = `scale(${scale})`;
                }
            }))
        })
    </script>
</x-filament-panels::page>
