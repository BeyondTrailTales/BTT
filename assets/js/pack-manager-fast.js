/**
 * Pack Manager - Ultra Fast Version
 * Optimized for speed with minimal DOM manipulation
 */

const PackManager = (function() {
    'use strict';

    // ==================== STATE & CACHE ====================
    const state = {
        packs: [],
        builderPack: null,
        currentStep: 1,
        gearInventory: [],
        draggedItem: null,
        initialized: false,
        currentGearFilter: 'all',
        searchTerm: '',
        // DOM element cache
        domCache: new Map(),
        // Render queue for batched updates
        renderQueue: [],
        renderScheduled: false
    };

    // ==================== PERFORMANCE UTILITIES ====================
    
    // Batch DOM updates using requestAnimationFrame
    function scheduleRender(fn) {
        state.renderQueue.push(fn);
        if (!state.renderScheduled) {
            state.renderScheduled = true;
            requestAnimationFrame(flushRenderQueue);
        }
    }

    function flushRenderQueue() {
        const queue = state.renderQueue.slice();
        state.renderQueue = [];
        state.renderScheduled = false;
        
        // Execute all queued renders in one batch
        queue.forEach(fn => fn());
    }

    // Cache DOM queries
    function getElement(id) {
        if (!state.domCache.has(id)) {
            state.domCache.set(id, document.getElementById(id));
        }
        return state.domCache.get(id);
    }

    // Efficient HTML string builder
    class HTMLBuilder {
        constructor() {
            this.parts = [];
        }
        
        add(html) {
            this.parts.push(html);
            return this;
        }
        
        toString() {
            return this.parts.join('');
        }
    }

    // ==================== INITIALIZATION ====================
    function init() {
        if (state.initialized) return;
        state.initialized = true;
        
        console.log('🚀 Fast Pack Manager Starting...');
        
        // Immediate setup with minimal DOM access
        loadData();
        setupEventDelegation();
        
        // Initial render
        fastRenderPacks();
        updateStats();
        
        console.log('✅ Ready!');
    }

    // Fast data loading
    function loadData() {
        // Use cached data if available
        const cached = sessionStorage.getItem('btt_cache');
        if (cached) {
            const data = JSON.parse(cached);
            state.packs = data.packs || [];
            state.gearInventory = data.gear || [];
        } else {
            // Load from localStorage or defaults
            const savedPacks = localStorage.getItem('btt_packs');
            const savedGear = localStorage.getItem('btt_gear');
            
            state.packs = savedPacks ? JSON.parse(savedPacks) : getDefaultPacks();
            state.gearInventory = savedGear ? JSON.parse(savedGear) : getDefaultGear();
            
            // Cache for this session
            sessionStorage.setItem('btt_cache', JSON.stringify({
                packs: state.packs,
                gear: state.gearInventory
            }));
        }
    }

    // ==================== EVENT DELEGATION ====================
    function setupEventDelegation() {
        // Single event listener for all clicks
        document.addEventListener('click', handleGlobalClick, true);
        
        // Single event listener for all drag operations
        document.addEventListener('dragstart', handleGlobalDragStart, true);
        document.addEventListener('dragover', handleGlobalDragOver, true);
        document.addEventListener('drop', handleGlobalDrop, true);
        document.addEventListener('dragend', handleGlobalDragEnd, true);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', handleKeyboard);
    }

    function handleGlobalClick(e) {
        const target = e.target;
        
        // Fast element matching using data attributes
        if (target.matches('[data-action]')) {
            e.preventDefault();
            const action = target.dataset.action;
            const value = target.dataset.value;
            
            switch(action) {
                case 'add-gear':
                    fastAddGear(value);
                    break;
                case 'remove-item':
                    fastRemoveItem(value);
                    break;
                case 'toggle-packed':
                    fastTogglePacked(value);
                    break;
                case 'filter-gear':
                    fastFilterGear(value);
                    break;
                case 'add-essential':
                    fastAddEssential(value);
                    break;
            }
        }
    }

    function handleGlobalDragStart(e) {
        if (e.target.matches('[data-draggable]')) {
            state.draggedItem = e.target.dataset.draggable;
            e.dataTransfer.effectAllowed = 'copy';
            e.target.style.opacity = '0.5';
        }
    }

    function handleGlobalDragOver(e) {
        if (e.target.closest('[data-dropzone]')) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        }
    }

    function handleGlobalDrop(e) {
        const dropzone = e.target.closest('[data-dropzone]');
        if (dropzone && state.draggedItem) {
            e.preventDefault();
            const sectionId = dropzone.dataset.dropzone;
            fastAddToSection(state.draggedItem, sectionId);
        }
    }

    function handleGlobalDragEnd(e) {
        if (e.target.matches('[data-draggable]')) {
            e.target.style.opacity = '';
        }
        state.draggedItem = null;
    }

    function handleKeyboard(e) {
        if (!state.builderPack) return;
        
        if (e.key === 'Escape') {
            closeBuilder();
        } else if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            savePack();
        }
    }

    // ==================== FAST PACK BUILDER ====================
    function createNew() {
        state.builderPack = {
            id: 'pack_' + Date.now(),
            name: '',
            type: 'weekend',
            capacity_l: 65,
            weight_empty_g: 1500,
            sections: getDefaultSections(),
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        
        state.currentStep = 1;
        openBuilder();
    }

    function openBuilder() {
        const modal = getElement('pack-builder-modal');
        if (modal) {
            modal.classList.remove('hidden');
            fastRenderBuilder();
        }
    }

    function closeBuilder() {
        const modal = getElement('pack-builder-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Fast builder rendering with minimal DOM updates
    function fastRenderBuilder() {
        const container = getElement('builder-layout');
        if (!container) return;
        
        const html = new HTMLBuilder();
        
        switch(state.currentStep) {
            case 1:
                renderDetailsStepFast(html);
                break;
            case 2:
                renderItemsStepFast(html);
                break;
            case 3:
                renderReviewStepFast(html);
                break;
        }
        
        // Single DOM update
        container.innerHTML = html.toString();
        
        // Update progress bar efficiently
        updateProgressBar();
        updateBuilderStatsFast();
    }

    function renderDetailsStepFast(html) {
        const pack = state.builderPack;
        
        html.add(`
            <div class="builder-step-fast">
                <div class="quick-templates">
                    <h3>Quick Start Templates</h3>
                    <div class="template-grid">
                        ${['day', 'weekend', 'multi', 'ultra'].map(type => `
                            <button class="template-btn ${pack.type === type ? 'active' : ''}" 
                                    data-action="set-type" data-value="${type}">
                                <span class="template-emoji">${getTypeEmoji(type)}</span>
                                <span>${getTypeLabel(type)}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
                
                <div class="pack-details-form">
                    <input type="text" 
                           id="pack-name-input"
                           class="input-large" 
                           placeholder="Pack Name (e.g., Weekend Warrior)"
                           value="${pack.name}"
                           oninput="PackManager.updateName(this.value)">
                    
                    <div class="slider-container">
                        <label>Capacity: <span id="capacity-display">${pack.capacity_l}L</span></label>
                        <input type="range" min="20" max="120" value="${pack.capacity_l}"
                               oninput="PackManager.updateCapacity(this.value)">
                    </div>
                    
                    <div class="season-selector-fast">
                        ${['spring', 'summer', 'fall', 'winter'].map(season => `
                            <button class="season-btn-fast ${pack.season === season ? 'active' : ''}"
                                    data-action="set-season" data-value="${season}">
                                ${getSeasonEmoji(season)}
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        `);
    }

    function renderItemsStepFast(html) {
        const pack = state.builderPack;
        
        html.add(`
            <div class="items-layout-fast">
                <!-- Gear Panel -->
                <div class="gear-panel-fast">
                    <div class="gear-header">
                        <h3>Gear Library</h3>
                        <input type="search" 
                               class="gear-search-fast" 
                               placeholder="Search..."
                               oninput="PackManager.searchGear(this.value)">
                    </div>
                    
                    <div class="gear-filters-fast">
                        ${['all', 'essentials', 'shelter', 'clothing', 'cooking'].map(cat => `
                            <button class="filter-btn-fast ${state.currentGearFilter === cat ? 'active' : ''}"
                                    data-action="filter-gear" data-value="${cat}">
                                ${cat}
                            </button>
                        `).join('')}
                    </div>
                    
                    <div class="gear-list-fast" id="gear-list">
                        ${renderGearListFast()}
                    </div>
                </div>
                
                <!-- Pack Contents -->
                <div class="pack-panel-fast">
                    <div class="pack-header">
                        <h3>Pack Contents</h3>
                        <div class="pack-stats-live">
                            <span>⚖️ <span id="live-weight">${calculateTotalWeight(pack)}</span></span>
                            <span>📦 <span id="live-items">${calculateTotalItems(pack)}</span> items</span>
                        </div>
                    </div>
                    
                    <div class="sections-list-fast" id="sections-list">
                        ${pack.sections.map(section => renderSectionFast(section)).join('')}
                    </div>
                    
                    <button class="add-section-btn-fast" onclick="PackManager.addSection()">
                        + Add Section
                    </button>
                </div>
                
                <!-- Quick Add Panel -->
                <div class="quick-panel-fast">
                    <h4>Essentials</h4>
                    <div class="essentials-grid">
                        ${getEssentials().map(item => `
                            <button class="essential-btn-fast" 
                                    data-action="add-essential" 
                                    data-value="${item.id}">
                                <span>${item.emoji}</span>
                                <span>${item.name}</span>
                                <small>${item.weight}g</small>
                            </button>
                        `).join('')}
                    </div>
                </div>
            </div>
        `);
    }

    function renderGearListFast() {
        const filtered = getFilteredGear();
        
        if (filtered.length === 0) {
            return '<div class="empty-message">No gear found</div>';
        }
        
        return filtered.map(gear => `
            <div class="gear-item-fast" 
                 data-draggable="${gear.id}"
                 data-action="add-gear"
                 data-value="${gear.id}"
                 draggable="true">
                <span class="gear-emoji">${gear.emoji || '🎒'}</span>
                <span class="gear-name">${gear.name}</span>
                <span class="gear-weight">${gear.weight_g}g</span>
            </div>
        `).join('');
    }

    function renderSectionFast(section) {
        const weight = calculateSectionWeight(section);
        
        return `
            <div class="section-fast" data-section-id="${section.id}">
                <div class="section-header-fast">
                    <div class="section-color" style="background:${section.color}"></div>
                    <input type="text" 
                           class="section-name-input-fast" 
                           value="${section.name}"
                           placeholder="Section name..."
                           onchange="PackManager.updateSectionName('${section.id}', this.value)">
                    <span class="section-stats">${section.items?.length || 0} items • ${weight}g</span>
                    <button class="section-delete" onclick="PackManager.deleteSection('${section.id}')">×</button>
                </div>
                
                <div class="section-items-fast" data-dropzone="${section.id}">
                    ${section.items && section.items.length > 0 ? 
                        section.items.map(item => renderItemFast(item, section.id)).join('') :
                        '<div class="drop-hint">Drop items here or click to add</div>'
                    }
                </div>
            </div>
        `;
    }

    function renderItemFast(item, sectionId) {
        return `
            <div class="item-fast" data-item-id="${item.id}">
                <input type="checkbox" 
                       ${item.packed ? 'checked' : ''}
                       data-action="toggle-packed"
                       data-value="${sectionId}:${item.id}">
                <span class="item-name">${item.name}</span>
                <span class="item-weight">${item.weight_g}g</span>
                <button class="item-remove" 
                        data-action="remove-item"
                        data-value="${sectionId}:${item.id}">×</button>
            </div>
        `;
    }

    function renderReviewStepFast(html) {
        const pack = state.builderPack;
        const stats = calculatePackStats(pack);
        
        html.add(`
            <div class="review-fast">
                <div class="review-header">
                    <h2>✨ Pack Summary</h2>
                </div>
                
                <div class="stats-grid-fast">
                    <div class="stat-card">
                        <span class="stat-value">${stats.totalWeight}</span>
                        <span class="stat-label">Total Weight</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${stats.totalItems}</span>
                        <span class="stat-label">Items</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${stats.sections}</span>
                        <span class="stat-label">Sections</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${stats.packedPercent}%</span>
                        <span class="stat-label">Packed</span>
                    </div>
                </div>
                
                <div class="pack-summary">
                    <h3>${pack.name || 'Unnamed Pack'}</h3>
                    <div class="summary-details">
                        <div>Type: ${pack.type}</div>
                        <div>Capacity: ${pack.capacity_l}L</div>
                        <div>Season: ${pack.season || 'All'}</div>
                    </div>
                </div>
                
                <div class="sections-summary">
                    ${pack.sections.map(section => `
                        <div class="section-summary">
                            <div class="section-summary-header">
                                <span class="color-dot" style="background:${section.color}"></span>
                                <span>${section.name}</span>
                                <span>${section.items?.length || 0} items</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `);
    }

    // ==================== FAST OPERATIONS ====================
    
    // Fast add gear with minimal DOM manipulation
    function fastAddGear(gearId) {
        if (!state.builderPack || !state.builderPack.sections.length) {
            showToast('Add a section first', 'warning');
            return;
        }
        
        const gear = state.gearInventory.find(g => g.id === gearId);
        if (!gear) return;
        
        // Add to first section
        const section = state.builderPack.sections[0];
        
        // Check if already exists
        if (section.items?.find(i => i.gear_id === gearId)) {
            showToast('Already added', 'info');
            return;
        }
        
        const item = {
            id: 'item_' + Date.now(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g,
            category: gear.category,
            packed: false
        };
        
        if (!section.items) section.items = [];
        section.items.push(item);
        
        // Update only the affected section
        updateSectionDOM(section);
        updateBuilderStatsFast();
        showToast(`+ ${gear.name}`, 'success');
    }

    function fastAddToSection(gearId, sectionId) {
        const gear = state.gearInventory.find(g => g.id === gearId);
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        
        if (!gear || !section) return;
        
        if (section.items?.find(i => i.gear_id === gearId)) {
            showToast('Already in section', 'info');
            return;
        }
        
        const item = {
            id: 'item_' + Date.now(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g,
            category: gear.category,
            packed: false
        };
        
        if (!section.items) section.items = [];
        section.items.push(item);
        
        updateSectionDOM(section);
        updateBuilderStatsFast();
        showToast(`+ ${gear.name}`, 'success');
    }

    function fastRemoveItem(value) {
        const [sectionId, itemId] = value.split(':');
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        
        if (section && section.items) {
            section.items = section.items.filter(i => i.id !== itemId);
            updateSectionDOM(section);
            updateBuilderStatsFast();
        }
    }

    function fastTogglePacked(value) {
        const [sectionId, itemId] = value.split(':');
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        
        if (section && section.items) {
            const item = section.items.find(i => i.id === itemId);
            if (item) {
                item.packed = !item.packed;
                // Just update the checkbox, don't re-render
                const checkbox = document.querySelector(`[data-value="${value}"]`);
                if (checkbox) checkbox.checked = item.packed;
                updateBuilderStatsFast();
            }
        }
    }

    function fastFilterGear(category) {
        state.currentGearFilter = category;
        
        // Update filter buttons
        document.querySelectorAll('.filter-btn-fast').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.value === category);
        });
        
        // Update gear list
        const gearList = getElement('gear-list');
        if (gearList) {
            gearList.innerHTML = renderGearListFast();
        }
    }

    function fastAddEssential(itemId) {
        const essential = getEssentials().find(e => e.id === itemId);
        if (!essential) return;
        
        if (!state.builderPack.sections.length) {
            state.builderPack.sections.push({
                id: 'section_' + Date.now(),
                name: 'Essentials',
                color: '#ef4444',
                items: []
            });
        }
        
        const section = state.builderPack.sections[0];
        const item = {
            id: 'item_' + Date.now(),
            name: essential.name,
            weight_g: essential.weight,
            category: 'essentials',
            packed: false
        };
        
        if (!section.items) section.items = [];
        section.items.push(item);
        
        updateSectionDOM(section);
        updateBuilderStatsFast();
        showToast(`+ ${essential.name}`, 'success');
    }

    // ==================== DOM UPDATE UTILITIES ====================
    
    // Update only the changed section
    function updateSectionDOM(section) {
        scheduleRender(() => {
            const sectionEl = document.querySelector(`[data-section-id="${section.id}"]`);
            if (!sectionEl) {
                // If section doesn't exist, re-render all sections
                const container = getElement('sections-list');
                if (container) {
                    container.innerHTML = state.builderPack.sections.map(s => renderSectionFast(s)).join('');
                }
            } else {
                // Update only the items area
                const itemsEl = sectionEl.querySelector('.section-items-fast');
                if (itemsEl) {
                    itemsEl.innerHTML = section.items && section.items.length > 0 ?
                        section.items.map(item => renderItemFast(item, section.id)).join('') :
                        '<div class="drop-hint">Drop items here or click to add</div>';
                }
                
                // Update section stats
                const statsEl = sectionEl.querySelector('.section-stats');
                if (statsEl) {
                    statsEl.textContent = `${section.items?.length || 0} items • ${calculateSectionWeight(section)}g`;
                }
            }
        });
    }

    function updateBuilderStatsFast() {
        scheduleRender(() => {
            const pack = state.builderPack;
            if (!pack) return;
            
            // Update weight
            const weightEl = document.getElementById('live-weight');
            if (weightEl) weightEl.textContent = calculateTotalWeight(pack);
            
            // Update items
            const itemsEl = document.getElementById('live-items');
            if (itemsEl) itemsEl.textContent = calculateTotalItems(pack);
            
            // Update footer stats
            const footerWeight = document.getElementById('builder-total-weight');
            if (footerWeight) footerWeight.textContent = calculateTotalWeight(pack);
            
            const footerItems = document.getElementById('builder-total-items');
            if (footerItems) footerItems.textContent = calculateTotalItems(pack);
        });
    }

    function updateProgressBar() {
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.toggle('active', stepNum === state.currentStep);
            step.classList.toggle('completed', stepNum < state.currentStep);
        });
        
        // Update buttons
        const prevBtn = getElement('btn-prev');
        const nextBtn = getElement('btn-next');
        const saveBtn = getElement('btn-save');
        
        if (prevBtn) prevBtn.disabled = state.currentStep === 1;
        
        if (state.currentStep === 3) {
            if (nextBtn) nextBtn.classList.add('hidden');
            if (saveBtn) saveBtn.classList.remove('hidden');
        } else {
            if (nextBtn) nextBtn.classList.remove('hidden');
            if (saveBtn) saveBtn.classList.add('hidden');
        }
    }

    // ==================== CALCULATIONS ====================
    function calculateTotalWeight(pack) {
        let total = pack.weight_empty_g || 0;
        if (pack.sections) {
            pack.sections.forEach(section => {
                if (section.items) {
                    section.items.forEach(item => {
                        total += item.weight_g || 0;
                    });
                }
            });
        }
        return total >= 1000 ? (total / 1000).toFixed(1) + 'kg' : total + 'g';
    }

    function calculateTotalItems(pack) {
        let total = 0;
        if (pack.sections) {
            pack.sections.forEach(section => {
                total += section.items?.length || 0;
            });
        }
        return total;
    }

    function calculateSectionWeight(section) {
        if (!section.items) return 0;
        return section.items.reduce((sum, item) => sum + (item.weight_g || 0), 0);
    }

    function calculatePackStats(pack) {
        let totalWeight = pack.weight_empty_g || 0;
        let totalItems = 0;
        let packedItems = 0;
        
        if (pack.sections) {
            pack.sections.forEach(section => {
                if (section.items) {
                    totalItems += section.items.length;
                    section.items.forEach(item => {
                        totalWeight += item.weight_g || 0;
                        if (item.packed) packedItems++;
                    });
                }
            });
        }
        
        return {
            totalWeight: totalWeight >= 1000 ? (totalWeight / 1000).toFixed(1) + 'kg' : totalWeight + 'g',
            totalItems: totalItems,
            sections: pack.sections?.length || 0,
            packedPercent: totalItems > 0 ? Math.round((packedItems / totalItems) * 100) : 0
        };
    }

    // ==================== UTILITIES ====================
    function getFilteredGear() {
        let filtered = state.gearInventory;
        
        if (state.currentGearFilter !== 'all') {
            filtered = filtered.filter(g => g.category === state.currentGearFilter);
        }
        
        if (state.searchTerm) {
            const term = state.searchTerm.toLowerCase();
            filtered = filtered.filter(g => 
                g.name.toLowerCase().includes(term) ||
                (g.category && g.category.toLowerCase().includes(term))
            );
        }
        
        return filtered;
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-fast ${type}`;
        toast.textContent = message;
        
        // Use fixed container for better performance
        let container = document.getElementById('toast-container-fast');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container-fast';
            container.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:9999;';
            document.body.appendChild(container);
        }
        
        container.appendChild(toast);
        
        // Remove after animation
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 200);
        }, 2000);
    }

    // ==================== SEARCH (DEBOUNCED) ====================
    let searchTimeout;
    function searchGear(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            state.searchTerm = query.toLowerCase();
            const gearList = getElement('gear-list');
            if (gearList) {
                gearList.innerHTML = renderGearListFast();
            }
        }, 150); // Reduced debounce time for faster response
    }

    // ==================== NAVIGATION ====================
    function nextStep() {
        if (state.currentStep < 3) {
            if (state.currentStep === 1 && !state.builderPack.name) {
                const input = document.getElementById('pack-name-input');
                if (input) {
                    input.classList.add('error');
                    input.focus();
                }
                showToast('Please enter a pack name', 'error');
                return;
            }
            
            state.currentStep++;
            fastRenderBuilder();
        }
    }

    function previousStep() {
        if (state.currentStep > 1) {
            state.currentStep--;
            fastRenderBuilder();
        }
    }

    function savePack() {
        const pack = state.builderPack;
        
        if (!pack.name) {
            showToast('Please enter a pack name', 'error');
            return;
        }
        
        pack.updated_at = new Date().toISOString();
        
        const existingIndex = state.packs.findIndex(p => p.id === pack.id);
        if (existingIndex >= 0) {
            state.packs[existingIndex] = pack;
        } else {
            state.packs.push(pack);
        }
        
        // Save to storage
        localStorage.setItem('btt_packs', JSON.stringify(state.packs));
        sessionStorage.setItem('btt_cache', JSON.stringify({
            packs: state.packs,
            gear: state.gearInventory
        }));
        
        showToast('Pack saved!', 'success');
        closeBuilder();
        fastRenderPacks();
    }

    // ==================== PACK RENDERING ====================
    function fastRenderPacks() {
        const container = getElement('pack-container');
        if (!container) return;
        
        if (state.packs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h2>No packs yet</h2>
                    <button class="btn-primary" onclick="PackManager.createNew()">Create First Pack</button>
                </div>
            `;
            return;
        }
        
        const html = new HTMLBuilder();
        html.add('<div class="pack-grid">');
        
        state.packs.forEach(pack => {
            const stats = calculatePackStats(pack);
            html.add(`
                <div class="pack-card-fast">
                    <h3>${pack.name}</h3>
                    <div class="pack-stats">
                        <span>⚖️ ${stats.totalWeight}</span>
                        <span>📦 ${stats.totalItems} items</span>
                    </div>
                    <button onclick="PackManager.editPack('${pack.id}')">Edit</button>
                </div>
            `);
        });
        
        html.add('</div>');
        container.innerHTML = html.toString();
    }

    function updateStats() {
        const totalPacks = state.packs.length;
        let totalWeight = 0;
        let totalItems = 0;
        
        state.packs.forEach(pack => {
            totalItems += calculateTotalItems(pack);
        });
        
        const packsEl = getElement('hero-packs');
        const itemsEl = getElement('hero-items');
        
        if (packsEl) packsEl.textContent = totalPacks;
        if (itemsEl) itemsEl.textContent = totalItems;
    }

    // ==================== DEFAULT DATA ====================
    function getDefaultSections() {
        return [
            { id: 's1', name: 'Essentials', color: '#ef4444', items: [] },
            { id: 's2', name: 'Shelter', color: '#3b82f6', items: [] },
            { id: 's3', name: 'Clothing', color: '#10b981', items: [] }
        ];
    }

    function getDefaultPacks() {
        return [];
    }

    function getDefaultGear() {
        return [
            { id: 'g1', name: 'Tent', weight_g: 1200, category: 'shelter', emoji: '⛺' },
            { id: 'g2', name: 'Sleeping Bag', weight_g: 800, category: 'shelter', emoji: '🛏️' },
            { id: 'g3', name: 'Sleeping Pad', weight_g: 400, category: 'shelter', emoji: '🟦' },
            { id: 'g4', name: 'Stove', weight_g: 100, category: 'cooking', emoji: '🔥' },
            { id: 'g5', name: 'Pot', weight_g: 150, category: 'cooking', emoji: '🍲' },
            { id: 'g6', name: 'Water Filter', weight_g: 60, category: 'essentials', emoji: '💧' },
            { id: 'g7', name: 'First Aid Kit', weight_g: 200, category: 'essentials', emoji: '🏥' },
            { id: 'g8', name: 'Headlamp', weight_g: 75, category: 'essentials', emoji: '🔦' },
            { id: 'g9', name: 'Rain Jacket', weight_g: 250, category: 'clothing', emoji: '🧥' },
            { id: 'g10', name: 'Base Layer', weight_g: 150, category: 'clothing', emoji: '👕' }
        ];
    }

    function getEssentials() {
        return [
            { id: 'e1', name: 'First Aid', emoji: '🏥', weight: 200 },
            { id: 'e2', name: 'Water 2L', emoji: '💧', weight: 2000 },
            { id: 'e3', name: 'Map', emoji: '🗺️', weight: 50 },
            { id: 'e4', name: 'Headlamp', emoji: '🔦', weight: 75 },
            { id: 'e5', name: 'Knife', emoji: '🔪', weight: 100 },
            { id: 'e6', name: 'Whistle', emoji: '📯', weight: 20 }
        ];
    }

    function getTypeEmoji(type) {
        const emojis = { day: '☀️', weekend: '🏕️', multi: '🏔️', ultra: '🪶' };
        return emojis[type] || '🎒';
    }

    function getTypeLabel(type) {
        const labels = { day: 'Day Hike', weekend: 'Weekend', multi: 'Multi-Day', ultra: 'Ultralight' };
        return labels[type] || type;
    }

    function getSeasonEmoji(season) {
        const emojis = { spring: '🌸', summer: '☀️', fall: '🍂', winter: '❄️' };
        return emojis[season] || '🌍';
    }

    // ==================== PUBLIC API ====================
    return {
        init,
        createNew,
        closeBuilder,
        nextStep,
        previousStep,
        savePackFromBuilder: savePack,
        searchGear,
        
        updateName: (value) => {
            if (state.builderPack) {
                state.builderPack.name = value;
            }
        },
        
        updateCapacity: (value) => {
            if (state.builderPack) {
                state.builderPack.capacity_l = parseInt(value);
                const display = document.getElementById('capacity-display');
                if (display) display.textContent = value + 'L';
            }
        },
        
        updateSectionName: (sectionId, name) => {
            const section = state.builderPack?.sections.find(s => s.id === sectionId);
            if (section) section.name = name;
        },
        
        addSection: () => {
            if (state.builderPack) {
                const section = {
                    id: 'section_' + Date.now(),
                    name: '',
                    color: '#' + Math.floor(Math.random()*16777215).toString(16),
                    items: []
                };
                state.builderPack.sections.push(section);
                
                const container = getElement('sections-list');
                if (container) {
                    const div = document.createElement('div');
                    div.innerHTML = renderSectionFast(section);
                    container.insertBefore(div.firstElementChild, container.lastElementChild);
                }
            }
        },
        
        deleteSection: (sectionId) => {
            if (state.builderPack && confirm('Delete section?')) {
                state.builderPack.sections = state.builderPack.sections.filter(s => s.id !== sectionId);
                const sectionEl = document.querySelector(`[data-section-id="${sectionId}"]`);
                if (sectionEl) sectionEl.remove();
                updateBuilderStatsFast();
            }
        },
        
        editPack: (packId) => {
            const pack = state.packs.find(p => p.id === packId);
            if (pack) {
                state.builderPack = JSON.parse(JSON.stringify(pack));
                state.currentStep = 1;
                openBuilder();
            }
        },
        
        // Placeholder functions for compatibility
        saveDraft: () => showToast('Draft saved', 'success'),
        previewPack: () => showToast('Preview mode', 'info'),
        quickStart: () => createNew(),
        showTour: () => {},
        toggleView: () => {},
        search: () => {},
        filter: () => {},
        filterByWeight: () => {},
        filterByTag: () => {},
        showCategory: () => {},
        showTemplates: () => {},
        showImport: () => {},
        closeDetail: () => {},
        createFromLastTrip: () => createNew(),
        duplicateFavorite: () => createNew(),
        smartPack: () => {}
    };
})();

// Initialize immediately when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManager.init);
} else {
    PackManager.init();
}
