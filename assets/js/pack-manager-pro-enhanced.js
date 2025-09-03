/**
 * Pack Manager Pro Enhanced - Professional Backpacking System with Edit Support
 * Built for ADA compliance and responsive design
 * Fully integrated with backend API for persistent storage
 */

const PackManagerProEnhanced = (function() {
    'use strict';

    // ==================== STATE MANAGEMENT ====================
    const state = {
        mode: 'create', // 'create' or 'edit'
        backpackId: null,
        backpack: null,
        currentStep: 1,
        draggedItem: null,
        currentFilter: 'all',
        searchTerm: '',
        activeSection: null,
        loading: false,
        saving: false,
        errors: [],
        isDirty: false,
        gearDatabase: [],
        customGear: [],
        focusTrap: null,
        previousFocus: null
    };

    // Gear database (simplified for edit mode demonstration)
    const SAMPLE_GEAR = [
        { id: 'tent-ul-1p', name: 'Zpacks Duplex', weight_g: 538, category: 'shelter', price: 699, brand: 'Zpacks', emoji: '⛺' },
        { id: 'quilt-20f', name: 'EE Revelation 20°F', weight_g: 570, category: 'sleep', price: 340, brand: 'Enlightened Equipment', emoji: '🛌' },
        { id: 'pad-xlite', name: 'Thermarest NeoAir XLite', weight_g: 340, category: 'sleep', price: 210, brand: 'Thermarest', emoji: '🟦' },
        { id: 'stove-brs', name: 'BRS-3000T', weight_g: 25, category: 'cooking', price: 17, brand: 'BRS', emoji: '🔥' },
        { id: 'pot-toaks-550', name: 'TOAKS Titanium 550ml', weight_g: 65, category: 'cooking', price: 34, brand: 'TOAKS', emoji: '🍲' },
        { id: 'filter-sawyer-mini', name: 'Sawyer Mini', weight_g: 57, category: 'water', price: 24, brand: 'Sawyer', emoji: '💧' },
        { id: 'pack-nero', name: 'Zpacks Nero 38L', weight_g: 298, category: 'packs', price: 290, brand: 'Zpacks', emoji: '🎒' },
        { id: 'rain-frogg', name: 'Frogg Toggs UL Jacket', weight_g: 155, category: 'clothing', price: 25, brand: 'Frogg Toggs', emoji: '🧥' },
        { id: 'headlamp-nitecore', name: 'Nitecore NU25 400', weight_g: 57, category: 'navigation', price: 39, brand: 'Nitecore', emoji: '🔦' },
        { id: 'trekking-poles', name: 'Cascade Mountain Tech', weight_g: 476, category: 'navigation', price: 30, brand: 'CMT', emoji: '🥾' }
    ];

    // ==================== INITIALIZATION ====================
    function init() {
        console.log('🎒 Pack Manager Pro Enhanced Initializing...');
        loadGearDatabase();
        setupEventListeners();
        setupAccessibility();
        console.log('✅ Pack Manager Pro Enhanced Ready!');
    }

    function loadGearDatabase() {
        // In production, this would load from the API
        state.gearDatabase = SAMPLE_GEAR;
        
        // Load custom gear from localStorage
        const customGear = localStorage.getItem('btt_custom_gear');
        if (customGear) {
            state.customGear = JSON.parse(customGear);
        }
    }

    function setupEventListeners() {
        // Global event delegation
        document.addEventListener('click', handleGlobalClick);
        document.addEventListener('input', handleGlobalInput);
        document.addEventListener('change', handleGlobalChange);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboardShortcuts);
        
        // Drag and drop will be set up when modal opens
    }

    function setupAccessibility() {
        // Create aria-live region for announcements
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        liveRegion.id = 'pack-manager-announcements';
        document.body.appendChild(liveRegion);
    }

    // ==================== MODAL MANAGEMENT ====================
    async function open(options = {}) {
        const { mode = 'create', backpackId = null, onSave = null } = options;
        
        state.mode = mode;
        state.backpackId = backpackId;
        state.onSave = onSave;
        state.loading = true;
        state.errors = [];
        state.isDirty = false;
        
        // Store current focus for restoration
        state.previousFocus = document.activeElement;
        
        // Show modal with loading state
        showModal();
        renderLoadingState();
        
        try {
            if (mode === 'edit' && backpackId) {
                // Load existing backpack
                await loadBackpack(backpackId);
            } else {
                // Create new backpack
                createNewBackpack();
            }
            
            state.loading = false;
            renderModal();
            setupDragAndDrop();
            setupFocusTrap();
            
            // Announce to screen readers
            announce(`${mode === 'edit' ? 'Edit' : 'Create'} backpack dialog opened`);
            
            // Focus first input
            setTimeout(() => {
                const firstInput = document.querySelector('#pack-name-input');
                if (firstInput) firstInput.focus();
            }, 100);
            
        } catch (error) {
            console.error('Error opening Pack Manager:', error);
            state.loading = false;
            state.errors = [error.message];
            renderError();
        }
    }

    async function loadBackpack(id) {
        try {
            const backpack = await PackManagerAPI.getBackpack(id);
            
            // Normalize the backpack structure
            state.backpack = {
                id: backpack.id,
                name: backpack.name || '',
                description: backpack.description || '',
                type: backpack.type || 'custom',
                capacity_l: backpack.capacity_l || 65,
                weight_empty_g: backpack.weight_empty_g || 0,
                sections: backpack.sections || createDefaultSections(),
                total_weight_g: backpack.total_weight_g || 0,
                base_weight_g: backpack.base_weight_g || 0,
                total_items: backpack.total_items || 0
            };
            
            // Ensure sections have proper structure
            state.backpack.sections = state.backpack.sections.map(section => ({
                id: section.id || generateId(),
                name: section.name || 'Unnamed Section',
                order: section.order || 0,
                color: section.color || '#10b981',
                items: (section.items || []).map(item => ({
                    id: item.id || generateId(),
                    gear_id: item.gear_id || null,
                    name: item.name || 'Unnamed Item',
                    weight_g: item.weight_g || 0,
                    quantity: item.quantity || 1,
                    notes: item.notes || '',
                    worn: item.worn || false,
                    consumable: item.consumable || false,
                    category: item.category || 'other',
                    emoji: item.emoji || '📦'
                }))
            }));
            
        } catch (error) {
            console.error('Error loading backpack:', error);
            throw error;
        }
    }

    function createNewBackpack() {
        state.backpack = {
            id: null,
            name: '',
            description: '',
            type: 'weekend',
            capacity_l: 65,
            weight_empty_g: 0,
            sections: createDefaultSections(),
            total_weight_g: 0,
            base_weight_g: 0,
            total_items: 0
        };
    }

    function createDefaultSections() {
        return [
            {
                id: generateId(),
                name: 'Main Compartment',
                order: 0,
                color: '#10b981',
                items: []
            },
            {
                id: generateId(),
                name: 'Top Lid',
                order: 1,
                color: '#3b82f6',
                items: []
            },
            {
                id: generateId(),
                name: 'Side Pockets',
                order: 2,
                color: '#8b5cf6',
                items: []
            },
            {
                id: generateId(),
                name: 'Hip Belt',
                order: 3,
                color: '#ef4444',
                items: []
            }
        ];
    }

    // ==================== RENDERING ====================
    function showModal() {
        let modal = document.getElementById('pack-manager-modal-enhanced');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pack-manager-modal-enhanced';
            modal.className = 'pack-manager-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'pack-manager-title');
            
            // Add critical inline styles to fix layout issues
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 99999;
                display: none;
            `;
            
            document.body.appendChild(modal);
        }
        
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.padding = '2rem';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function renderLoadingState() {
        const modal = document.getElementById('pack-manager-modal-enhanced');
        modal.innerHTML = `
            <div class="modal-overlay" data-action="close-modal"></div>
            <div class="modal-container">
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading backpack data...</p>
                </div>
            </div>
        `;
    }

    function renderModal() {
        const modal = document.getElementById('pack-manager-modal-enhanced');
        const isEdit = state.mode === 'edit';
        
        modal.innerHTML = `
            <div class="modal-overlay" data-action="close-modal" style="
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(4px);
                z-index: 1;
            "></div>
            <div class="modal-container" style="
                position: relative;
                width: 90%;
                max-width: 1200px;
                height: 85vh;
                max-height: 85vh;
                background: var(--forest-canopy, #0a2818);
                border: 1px solid var(--glass-border, rgba(74, 222, 128, 0.2));
                border-radius: 1rem;
                display: flex;
                flex-direction: column;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                z-index: 2;
            ">
                <div class="modal-header" style="
                    padding: 1rem 1.5rem;
                    background: rgba(15, 56, 35, 0.8);
                    backdrop-filter: blur(10px);
                    border-bottom: 1px solid rgba(74, 222, 128, 0.2);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-shrink: 0;
                ">
                    <h2 id="pack-manager-title" class="modal-title" style="
                        font-size: 1.5rem;
                        font-weight: bold;
                        color: #4ade80;
                        margin: 0;
                    ">
                        ${isEdit ? 'Edit' : 'Create'} Backpack
                    </h2>
                    <button class="btn-close" data-action="close-modal" aria-label="Close dialog" style="
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        background: rgba(239, 68, 68, 0.1);
                        border: 1px solid rgba(239, 68, 68, 0.3);
                        color: #f87171;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        font-size: 1.5rem;
                    ">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                
                <div class="modal-body" style="
                    flex: 1;
                    overflow-y: auto;
                    overflow-x: hidden;
                    padding: 1.5rem;
                    background: #0f3823;
                    min-height: 0;
                ">
                    ${renderPackDetails()}
                    ${renderGearManager()}
                </div>
                
                <div class="modal-footer" style="
                    padding: 1rem 1.5rem;
                    background: rgba(15, 56, 35, 0.8);
                    backdrop-filter: blur(10px);
                    border-top: 1px solid rgba(74, 222, 128, 0.2);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-shrink: 0;
                ">
                    <div class="footer-stats" style="
                        display: flex;
                        gap: 2rem;
                        color: rgba(255, 255, 255, 0.7);
                    ">
                        <span>Total Weight: <strong id="total-weight" style="color: #4ade80; font-weight: 600;">${formatWeight(state.backpack.total_weight_g)}</strong></span>
                        <span>Base Weight: <strong id="base-weight" style="color: #4ade80; font-weight: 600;">${formatWeight(state.backpack.base_weight_g)}</strong></span>
                        <span>Items: <strong id="total-items" style="color: #4ade80; font-weight: 600;">${state.backpack.total_items}</strong></span>
                    </div>
                    <div class="footer-actions" style="display: flex; gap: 1rem;">
                        <button class="btn btn-secondary" data-action="close-modal" style="
                            padding: 0.75rem 1.5rem;
                            border-radius: 0.5rem;
                            font-weight: 600;
                            cursor: pointer;
                            background: rgba(74, 222, 128, 0.1);
                            color: #4ade80;
                            border: 1px solid #4ade80;
                        ">Cancel</button>
                        <button class="btn btn-primary" data-action="save-backpack" ${state.saving ? 'disabled' : ''} style="
                            padding: 0.75rem 1.5rem;
                            border-radius: 0.5rem;
                            font-weight: 600;
                            cursor: pointer;
                            background: #4ade80;
                            color: #0a2818;
                            border: none;
                            ${state.saving ? 'opacity: 0.5; cursor: not-allowed;' : ''}
                        ">
                            ${state.saving ? 'Saving...' : (isEdit ? 'Update' : 'Create')} Backpack
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPackDetails() {
        return `
            <div class="pack-details">
                <div class="form-group">
                    <label for="pack-name-input">Pack Name *</label>
                    <input 
                        type="text" 
                        id="pack-name-input" 
                        class="form-control" 
                        value="${state.backpack.name}" 
                        placeholder="e.g., Weekend Warrior"
                        required
                        aria-required="true"
                    >
                </div>
                
                <div class="form-group">
                    <label for="pack-description">Description</label>
                    <textarea 
                        id="pack-description" 
                        class="form-control" 
                        placeholder="Notes about this pack configuration..."
                    >${state.backpack.description}</textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="pack-capacity">Capacity (L)</label>
                        <input 
                            type="number" 
                            id="pack-capacity" 
                            class="form-control" 
                            value="${state.backpack.capacity_l}" 
                            min="0" 
                            step="5"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="pack-weight">Empty Weight (g)</label>
                        <input 
                            type="number" 
                            id="pack-weight" 
                            class="form-control" 
                            value="${state.backpack.weight_empty_g}" 
                            min="0" 
                            step="10"
                        >
                    </div>
                </div>
            </div>
        `;
    }

    function renderGearManager() {
        return `
            <div class="gear-manager">
                <div class="gear-tabs">
                    <button class="tab-button active" data-tab="organize">Organize Gear</button>
                    <button class="tab-button" data-tab="library">Gear Library</button>
                </div>
                
                <div class="tab-content active" data-tab-content="organize">
                    ${renderSections()}
                </div>
                
                <div class="tab-content" data-tab-content="library">
                    ${renderGearLibrary()}
                </div>
            </div>
        `;
    }

    function renderSections() {
        return `
            <div class="sections-container">
                <div class="sections-header">
                    <h3>Pack Sections</h3>
                    <button class="btn btn-sm btn-secondary" data-action="add-section">
                        + Add Section
                    </button>
                </div>
                
                <div class="sections-list">
                    ${state.backpack.sections.map(section => renderSection(section)).join('')}
                </div>
                
                <div class="remove-zone" id="remove-zone">
                    <div class="remove-zone-inner">
                        <span>🗑️ Drop here to remove</span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderSection(section) {
        const sectionWeight = calculateSectionWeight(section);
        
        return `
            <div class="pack-section" data-section-id="${section.id}">
                <div class="section-header" style="background-color: ${section.color}20; border-left: 4px solid ${section.color};">
                    <div class="section-title">
                        <span class="drag-handle" aria-label="Drag to reorder">≡</span>
                        <input 
                            type="text" 
                            class="section-name-input" 
                            value="${section.name}" 
                            data-section-id="${section.id}"
                            aria-label="Section name"
                        >
                    </div>
                    <div class="section-stats">
                        <span>${section.items.length} items</span>
                        <span>${formatWeight(sectionWeight)}</span>
                        <button class="btn-icon" data-action="delete-section" data-section-id="${section.id}" aria-label="Delete section">
                            ×
                        </button>
                    </div>
                </div>
                
                <div class="section-items sortable-list" data-section-id="${section.id}">
                    ${section.items.map(item => renderPackItem(item, section.id)).join('')}
                    ${section.items.length === 0 ? '<div class="empty-section">Drop items here</div>' : ''}
                </div>
            </div>
        `;
    }

    function renderPackItem(item, sectionId) {
        return `
            <div class="pack-item" data-item-id="${item.id}" data-section-id="${sectionId}">
                <div class="item-main">
                    <span class="item-emoji">${item.emoji || '📦'}</span>
                    <span class="item-name">${item.name}</span>
                    <div class="item-controls">
                        <input 
                            type="number" 
                            class="item-qty" 
                            value="${item.quantity}" 
                            min="1" 
                            data-item-id="${item.id}"
                            aria-label="Quantity"
                        >
                        <span class="item-weight">${item.weight_g * item.quantity}g</span>
                        <button class="btn-icon" data-action="edit-item" data-item-id="${item.id}" aria-label="Edit item">
                            ✏️
                        </button>
                        <button class="btn-icon" data-action="remove-item" data-item-id="${item.id}" aria-label="Remove item">
                            ×
                        </button>
                    </div>
                </div>
                ${item.notes ? `<div class="item-notes">${item.notes}</div>` : ''}
            </div>
        `;
    }

    function renderGearLibrary() {
        const allGear = [...state.gearDatabase, ...state.customGear];
        const categories = [...new Set(allGear.map(g => g.category))];
        
        return `
            <div class="gear-library">
                <div class="library-header">
                    <input 
                        type="search" 
                        id="gear-search" 
                        class="search-input" 
                        placeholder="Search gear..."
                        aria-label="Search gear"
                    >
                    <button class="btn btn-sm btn-secondary" data-action="add-custom-gear">
                        + Custom Item
                    </button>
                </div>
                
                <div class="category-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    ${categories.map(cat => `
                        <button class="filter-btn" data-filter="${cat}">${cat}</button>
                    `).join('')}
                </div>
                
                <div class="gear-grid sortable-source">
                    ${allGear.map(gear => renderGearCard(gear)).join('')}
                </div>
            </div>
        `;
    }

    function renderGearCard(gear) {
        return `
            <div class="gear-card" data-gear-id="${gear.id}" draggable="true">
                <div class="gear-emoji">${gear.emoji || '📦'}</div>
                <div class="gear-info">
                    <div class="gear-name">${gear.name}</div>
                    <div class="gear-meta">
                        <span>${gear.weight_g}g</span>
                        ${gear.brand ? `<span>${gear.brand}</span>` : ''}
                    </div>
                </div>
                <button class="btn-add-gear" data-action="quick-add" data-gear-id="${gear.id}" aria-label="Add ${gear.name} to pack">
                    +
                </button>
            </div>
        `;
    }

    function renderError() {
        const modal = document.getElementById('pack-manager-modal-enhanced');
        modal.innerHTML = `
            <div class="modal-overlay" data-action="close-modal"></div>
            <div class="modal-container">
                <div class="error-state">
                    <h2>Error Loading Backpack</h2>
                    <p>${state.errors.join('<br>')}</p>
                    <button class="btn btn-primary" data-action="retry">Retry</button>
                    <button class="btn btn-secondary" data-action="close-modal">Close</button>
                </div>
            </div>
        `;
    }

    // ==================== DRAG AND DROP ====================
    function setupDragAndDrop() {
        if (typeof Sortable === 'undefined') {
            console.warn('Sortable.js not loaded, drag and drop disabled');
            return;
        }

        // Setup sortable for each section
        document.querySelectorAll('.section-items').forEach(list => {
            new Sortable(list, {
                group: 'pack-items',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'ghost',
                chosenClass: 'chosen',
                dragClass: 'dragging',
                onAdd: handleItemAdd,
                onRemove: handleItemRemove,
                onUpdate: handleItemReorder
            });
        });

        // Setup gear library as source
        const gearGrid = document.querySelector('.gear-grid');
        if (gearGrid) {
            new Sortable(gearGrid, {
                group: {
                    name: 'gear-source',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150
            });
        }

        // Setup remove zone
        const removeZone = document.getElementById('remove-zone');
        if (removeZone) {
            new Sortable(removeZone, {
                group: 'pack-items',
                onAdd: function(evt) {
                    const itemId = evt.item.dataset.itemId;
                    removeItemFromPack(itemId);
                    evt.item.remove();
                }
            });
        }
    }

    function handleItemAdd(evt) {
        const gearId = evt.item.dataset.gearId;
        const sectionId = evt.to.dataset.sectionId;
        
        if (gearId && sectionId) {
            addGearToSection(gearId, sectionId);
            evt.item.remove(); // Remove the cloned element
        }
        
        markDirty();
        updateStats();
    }

    function handleItemRemove(evt) {
        markDirty();
        updateStats();
    }

    function handleItemReorder(evt) {
        const sectionId = evt.to.dataset.sectionId;
        const itemId = evt.item.dataset.itemId;
        const newIndex = evt.newIndex;
        
        reorderItemInSection(itemId, sectionId, newIndex);
        markDirty();
    }

    // ==================== EVENT HANDLERS ====================
    function handleGlobalClick(e) {
        const target = e.target;
        const action = target.closest('[data-action]')?.dataset.action;
        
        if (!action) return;
        
        e.preventDefault();
        
        switch (action) {
            case 'close-modal':
                closeModal();
                break;
            case 'save-backpack':
                saveBackpack();
                break;
            case 'add-section':
                addSection();
                break;
            case 'delete-section':
                deleteSection(target.dataset.sectionId);
                break;
            case 'remove-item':
                removeItemFromPack(target.dataset.itemId);
                break;
            case 'edit-item':
                editItem(target.dataset.itemId);
                break;
            case 'quick-add':
                quickAddGear(target.dataset.gearId);
                break;
            case 'add-custom-gear':
                showCustomGearDialog();
                break;
            case 'retry':
                open({ mode: state.mode, backpackId: state.backpackId });
                break;
        }
    }

    function handleGlobalInput(e) {
        const target = e.target;
        
        if (target.id === 'pack-name-input') {
            state.backpack.name = target.value;
            markDirty();
        } else if (target.id === 'pack-description') {
            state.backpack.description = target.value;
            markDirty();
        } else if (target.id === 'pack-capacity') {
            state.backpack.capacity_l = parseFloat(target.value) || 0;
            markDirty();
        } else if (target.id === 'pack-weight') {
            state.backpack.weight_empty_g = parseFloat(target.value) || 0;
            markDirty();
            updateStats();
        } else if (target.classList.contains('section-name-input')) {
            const sectionId = target.dataset.sectionId;
            updateSectionName(sectionId, target.value);
        } else if (target.classList.contains('item-qty')) {
            const itemId = target.dataset.itemId;
            updateItemQuantity(itemId, parseInt(target.value) || 1);
        } else if (target.id === 'gear-search') {
            filterGear(target.value);
        }
    }

    function handleGlobalChange(e) {
        const target = e.target;
        
        // Handle tab switching
        if (target.classList.contains('tab-button')) {
            switchTab(target.dataset.tab);
        }
        
        // Handle filter buttons
        if (target.classList.contains('filter-btn')) {
            filterByCategory(target.dataset.filter);
        }
    }

    function handleKeyboardShortcuts(e) {
        if (!document.getElementById('pack-manager-modal-enhanced')?.classList.contains('active')) {
            return;
        }
        
        // Escape to close
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal();
        }
        
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveBackpack();
        }
    }

    // ==================== ACTIONS ====================
    function addSection() {
        const newSection = {
            id: generateId(),
            name: 'New Section',
            order: state.backpack.sections.length,
            color: getRandomColor(),
            items: []
        };
        
        state.backpack.sections.push(newSection);
        markDirty();
        renderModal();
        setupDragAndDrop();
        
        announce('New section added');
    }

    function deleteSection(sectionId) {
        if (!confirm('Delete this section and all its items?')) return;
        
        const index = state.backpack.sections.findIndex(s => s.id === sectionId);
        if (index > -1) {
            state.backpack.sections.splice(index, 1);
            markDirty();
            renderModal();
            setupDragAndDrop();
            updateStats();
            
            announce('Section deleted');
        }
    }

    function addGearToSection(gearId, sectionId) {
        const gear = [...state.gearDatabase, ...state.customGear].find(g => g.id === gearId);
        const section = state.backpack.sections.find(s => s.id === sectionId);
        
        if (!gear || !section) return;
        
        const newItem = {
            id: generateId(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g || 0,
            quantity: 1,
            notes: '',
            worn: false,
            consumable: gear.category === 'food' || gear.category === 'water',
            category: gear.category || 'other',
            emoji: gear.emoji || '📦'
        };
        
        section.items.push(newItem);
        markDirty();
        updateStats();
        
        announce(`${gear.name} added to ${section.name}`);
    }

    function removeItemFromPack(itemId) {
        for (const section of state.backpack.sections) {
            const index = section.items.findIndex(i => i.id === itemId);
            if (index > -1) {
                const item = section.items[index];
                section.items.splice(index, 1);
                markDirty();
                updateStats();
                
                announce(`${item.name} removed from pack`);
                break;
            }
        }
    }

    function updateSectionName(sectionId, name) {
        const section = state.backpack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = name;
            markDirty();
        }
    }

    function updateItemQuantity(itemId, quantity) {
        for (const section of state.backpack.sections) {
            const item = section.items.find(i => i.id === itemId);
            if (item) {
                item.quantity = Math.max(1, quantity);
                markDirty();
                updateStats();
                break;
            }
        }
    }

    function quickAddGear(gearId) {
        // Add to the first section by default
        if (state.backpack.sections.length > 0) {
            addGearToSection(gearId, state.backpack.sections[0].id);
            renderModal();
            setupDragAndDrop();
        }
    }

    async function saveBackpack() {
        if (!state.backpack.name) {
            alert('Please enter a pack name');
            document.getElementById('pack-name-input').focus();
            return;
        }
        
        state.saving = true;
        renderModal();
        
        try {
            let result;
            
            if (state.mode === 'edit' && state.backpackId) {
                // Update existing backpack
                result = await PackManagerAPI.updateBackpack(state.backpackId, state.backpack);
            } else {
                // Create new backpack
                result = await PackManagerAPI.createBackpack(state.backpack);
            }
            
            state.saving = false;
            state.isDirty = false;
            
            // Call onSave callback if provided
            if (state.onSave) {
                state.onSave(result);
            }
            
            // Show success message
            BTTUtils.showToast(
                state.mode === 'edit' ? 'Backpack updated successfully!' : 'Backpack created successfully!',
                'success'
            );
            
            closeModal();
            
            // Reload the backpack list if on the backpacks page
            if (typeof loadBackpacks === 'function') {
                loadBackpacks();
            }
            
        } catch (error) {
            console.error('Error saving backpack:', error);
            state.saving = false;
            state.errors = [error.message];
            BTTUtils.showToast('Failed to save backpack: ' + error.message, 'error');
            renderModal();
        }
    }

    function closeModal() {
        if (state.isDirty && !confirm('You have unsaved changes. Are you sure you want to close?')) {
            return;
        }
        
        const modal = document.getElementById('pack-manager-modal-enhanced');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
        }
        
        document.body.style.overflow = '';
        
        // Restore focus
        if (state.previousFocus) {
            state.previousFocus.focus();
        }
        
        // Clear focus trap
        if (state.focusTrap) {
            state.focusTrap.deactivate();
            state.focusTrap = null;
        }
        
        // Reset state
        state.mode = 'create';
        state.backpackId = null;
        state.backpack = null;
        state.isDirty = false;
        
        announce('Backpack dialog closed');
    }

    // ==================== UTILITY FUNCTIONS ====================
    function generateId() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    function formatWeight(grams) {
        if (grams < 1000) {
            return `${grams}g`;
        }
        return `${(grams / 1000).toFixed(2)}kg`;
    }

    function calculateSectionWeight(section) {
        return section.items.reduce((total, item) => {
            return total + (item.weight_g * item.quantity);
        }, 0);
    }

    function updateStats() {
        let totalWeight = state.backpack.weight_empty_g || 0;
        let baseWeight = totalWeight;
        let itemCount = 0;
        
        for (const section of state.backpack.sections) {
            for (const item of section.items) {
                const itemWeight = item.weight_g * item.quantity;
                
                if (!item.worn) {
                    totalWeight += itemWeight;
                    if (!item.consumable) {
                        baseWeight += itemWeight;
                    }
                }
                
                itemCount += item.quantity;
            }
        }
        
        state.backpack.total_weight_g = totalWeight;
        state.backpack.base_weight_g = baseWeight;
        state.backpack.total_items = itemCount;
        
        // Update UI
        const totalWeightEl = document.getElementById('total-weight');
        const baseWeightEl = document.getElementById('base-weight');
        const totalItemsEl = document.getElementById('total-items');
        
        if (totalWeightEl) totalWeightEl.textContent = formatWeight(totalWeight);
        if (baseWeightEl) baseWeightEl.textContent = formatWeight(baseWeight);
        if (totalItemsEl) totalItemsEl.textContent = itemCount;
    }

    function markDirty() {
        state.isDirty = true;
    }

    function announce(message) {
        const liveRegion = document.getElementById('pack-manager-announcements');
        if (liveRegion) {
            liveRegion.textContent = message;
            // Clear after announcement
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    }

    function getRandomColor() {
        const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b', '#14b8a6'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    function setupFocusTrap() {
        // Simple focus trap implementation
        const modal = document.querySelector('.modal-container');
        if (!modal) return;
        
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        modal.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }

    function switchTab(tabName) {
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tabName);
        });
        
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.dataset.tabContent === tabName);
        });
    }

    function filterByCategory(category) {
        state.currentFilter = category;
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === category);
        });
        
        // Re-render gear library with filter
        renderModal();
        setupDragAndDrop();
    }

    function filterGear(searchTerm) {
        state.searchTerm = searchTerm.toLowerCase();
        // Re-render gear library with search
        renderModal();
        setupDragAndDrop();
    }

    // ==================== PUBLIC API ====================
    return {
        init,
        open,
        close: closeModal
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManagerProEnhanced.init);
} else {
    PackManagerProEnhanced.init();
}

// Make it globally available
window.PackManagerProEnhanced = PackManagerProEnhanced;
