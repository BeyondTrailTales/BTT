/**
 * Pack Manager - Improved Version
 * Fixed text colors and better section naming
 */

const PackManager = (function() {
    'use strict';

    // ==================== STATE ====================
    const state = {
        packs: [],
        builderPack: null,
        currentStep: 1,
        gearInventory: [],
        draggedItem: null,
        currentFilter: 'all',
        searchTerm: ''
    };

    // ==================== INITIALIZATION ====================
    function init() {
        console.log('🎒 Pack Manager Initializing...');
        loadDefaultGear();
        loadPacks();
        setupEventListeners();
        console.log('✅ Pack Manager Ready!');
    }

    function loadDefaultGear() {
        state.gearInventory = [
            // Shelter
            { id: 'tent', name: 'Ultralight Tent', weight: 1200, category: 'shelter', emoji: '⛺' },
            { id: 'sleeping-bag', name: 'Down Sleeping Bag', weight: 650, category: 'shelter', emoji: '🛏️' },
            { id: 'pad', name: 'Inflatable Pad', weight: 350, category: 'shelter', emoji: '🟦' },
            { id: 'tarp', name: 'Emergency Tarp', weight: 200, category: 'shelter', emoji: '🏕️' },
            
            // Cooking
            { id: 'stove', name: 'Canister Stove', weight: 75, category: 'cooking', emoji: '🔥' },
            { id: 'pot', name: 'Titanium Pot', weight: 100, category: 'cooking', emoji: '🍲' },
            { id: 'spork', name: 'Titanium Spork', weight: 20, category: 'cooking', emoji: '🥄' },
            { id: 'filter', name: 'Water Filter', weight: 60, category: 'cooking', emoji: '💧' },
            { id: 'bottle', name: 'Water Bottle', weight: 100, category: 'cooking', emoji: '🍶' },
            
            // Clothing
            { id: 'jacket', name: 'Rain Jacket', weight: 280, category: 'clothing', emoji: '🧥' },
            { id: 'fleece', name: 'Fleece Layer', weight: 350, category: 'clothing', emoji: '👕' },
            { id: 'pants', name: 'Hiking Pants', weight: 300, category: 'clothing', emoji: '👖' },
            { id: 'hat', name: 'Sun Hat', weight: 50, category: 'clothing', emoji: '🧢' },
            { id: 'gloves', name: 'Gloves', weight: 40, category: 'clothing', emoji: '🧤' },
            
            // Essentials
            { id: 'first-aid', name: 'First Aid Kit', weight: 200, category: 'essentials', emoji: '🏥' },
            { id: 'water-2l', name: 'Water 2L', weight: 2000, category: 'essentials', emoji: '💧' },
            { id: 'map', name: 'Map', weight: 50, category: 'essentials', emoji: '🗺️' },
            { id: 'headlamp', name: 'Headlamp', weight: 75, category: 'essentials', emoji: '🔦' },
            { id: 'knife', name: 'Knife', weight: 100, category: 'essentials', emoji: '🔪' },
            { id: 'whistle', name: 'Whistle', weight: 20, category: 'essentials', emoji: '📯' },
            { id: 'compass', name: 'Compass', weight: 30, category: 'essentials', emoji: '🧭' },
            { id: 'lighter', name: 'Lighter', weight: 20, category: 'essentials', emoji: '🔥' }
        ];
    }

    function loadPacks() {
        const saved = localStorage.getItem('btt_packs');
        state.packs = saved ? JSON.parse(saved) : [];
    }

    function setupEventListeners() {
        // Use event delegation for better performance
        document.addEventListener('click', handleClick);
        document.addEventListener('dragstart', handleDragStart);
        document.addEventListener('dragover', handleDragOver);
        document.addEventListener('drop', handleDrop);
        document.addEventListener('dragend', handleDragEnd);
        document.addEventListener('input', handleInput);
    }

    // ==================== EVENT HANDLERS ====================
    function handleClick(e) {
        const action = e.target.closest('[data-action]')?.dataset.action;
        if (!action) return;

        switch(action) {
            case 'create-pack':
                openBuilder();
                break;
            case 'close-builder':
                closeBuilder();
                break;
            case 'next-step':
                nextStep();
                break;
            case 'prev-step':
                prevStep();
                break;
            case 'save-pack':
                savePack();
                break;
            case 'add-section':
                addSection();
                break;
            case 'remove-section':
                removeSection(e.target.closest('.section-fast').dataset.sectionId);
                break;
            case 'remove-item':
                removeItem(e.target.closest('.item-fast'));
                break;
            case 'filter-gear':
                filterGear(e.target.dataset.category);
                break;
            case 'add-essential':
                addEssential(e.target.dataset.itemId);
                break;
        }
    }

    function handleDragStart(e) {
        const draggable = e.target.closest('[data-draggable]');
        if (draggable) {
            state.draggedItem = draggable.dataset.draggable;
            e.dataTransfer.effectAllowed = 'copy';
            draggable.classList.add('dragging');
        }
    }

    function handleDragOver(e) {
        const dropzone = e.target.closest('[data-dropzone]');
        if (dropzone) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        }
    }

    function handleDrop(e) {
        const dropzone = e.target.closest('[data-dropzone]');
        if (dropzone && state.draggedItem) {
            e.preventDefault();
            const sectionId = dropzone.dataset.dropzone;
            addItemToSection(state.draggedItem, sectionId);
            dropzone.classList.remove('drag-over');
        }
    }

    function handleDragEnd(e) {
        document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
        document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        state.draggedItem = null;
    }

    function handleInput(e) {
        if (e.target.classList.contains('gear-search-fast')) {
            searchGear(e.target.value);
        } else if (e.target.classList.contains('section-name-input-fast')) {
            updateSectionName(e.target);
        }
    }

    // ==================== PACK BUILDER ====================
    function openBuilder() {
        state.builderPack = {
            id: Date.now().toString(),
            name: '',
            description: '',
            type: 'weekend',
            season: 'summer',
            weight: 0,
            sections: getDefaultSections()
        };
        state.currentStep = 1;
        
        const modal = document.getElementById('pack-builder-modal');
        modal.classList.remove('hidden');
        renderBuilderStep();
    }

    function closeBuilder() {
        const modal = document.getElementById('pack-builder-modal');
        modal.classList.add('hidden');
        state.builderPack = null;
    }

    function getDefaultSections() {
        return [
            { id: 's1', name: 'Top Section', color: '#ff9500', items: [], position: 'TOP' },
            { id: 's2', name: 'Middle Section', color: '#007aff', items: [], position: 'MIDDLE' },
            { id: 's3', name: 'Bottom Section', color: '#34c759', items: [], position: 'BOTTOM' }
        ];
    }

    function renderBuilderStep() {
        const container = document.getElementById('builder-layout');
        
        switch(state.currentStep) {
            case 1:
                renderDetailsStep(container);
                break;
            case 2:
                renderSectionsStep(container);
                break;
            case 3:
                renderItemsStep(container);
                break;
            case 4:
                renderReviewStep(container);
                break;
        }
        
        updateProgressBar();
        updateBuilderStats();
    }

    function renderDetailsStep(container) {
        container.innerHTML = `
            <div class="builder-step-fast">
                <h3 style="color: #ffffff; margin-bottom: 20px;">Pack Details</h3>
                
                <div class="quick-templates">
                    <h4 style="color: rgba(255,255,255,0.8); margin-bottom: 15px;">Quick Start Templates</h4>
                    <div class="template-grid">
                        <button class="template-btn" data-template="day">
                            <span class="template-emoji">☀️</span>
                            <span style="color: #ffffff;">Day Hike</span>
                        </button>
                        <button class="template-btn" data-template="weekend">
                            <span class="template-emoji">🏕️</span>
                            <span style="color: #ffffff;">Weekend</span>
                        </button>
                        <button class="template-btn" data-template="week">
                            <span class="template-emoji">🎒</span>
                            <span style="color: #ffffff;">Week Trip</span>
                        </button>
                        <button class="template-btn" data-template="ultra">
                            <span class="template-emoji">⚡</span>
                            <span style="color: #ffffff;">Ultralight</span>
                        </button>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <label style="color: rgba(255,255,255,0.8); display: block; margin-bottom: 8px;">Pack Name</label>
                    <input type="text" class="input-large" placeholder="e.g., Weekend in the Alps" 
                           value="${state.builderPack.name}" 
                           onchange="PackManager.updatePackName(this.value)"
                           style="color: #ffffff;">
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="color: rgba(255,255,255,0.8); display: block; margin-bottom: 8px;">Description</label>
                    <textarea class="input-large" rows="3" placeholder="Notes about this pack..."
                              onchange="PackManager.updatePackDesc(this.value)"
                              style="color: #ffffff;">${state.builderPack.description}</textarea>
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="color: rgba(255,255,255,0.8); display: block; margin-bottom: 8px;">Season</label>
                    <div class="season-selector-fast">
                        <button class="season-btn-fast ${state.builderPack.season === 'spring' ? 'active' : ''}" 
                                onclick="PackManager.setSeason('spring')">🌸</button>
                        <button class="season-btn-fast ${state.builderPack.season === 'summer' ? 'active' : ''}" 
                                onclick="PackManager.setSeason('summer')">☀️</button>
                        <button class="season-btn-fast ${state.builderPack.season === 'fall' ? 'active' : ''}" 
                                onclick="PackManager.setSeason('fall')">🍂</button>
                        <button class="season-btn-fast ${state.builderPack.season === 'winter' ? 'active' : ''}" 
                                onclick="PackManager.setSeason('winter')">❄️</button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderSectionsStep(container) {
        const sectionsHTML = state.builderPack.sections.map((section, index) => 
            renderSectionEditor(section, index)
        ).join('');
        
        container.innerHTML = `
            <div class="builder-step-fast">
                <h3 style="color: #ffffff; margin-bottom: 20px;">Organize Your Pack Sections</h3>
                <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">
                    Organize your gear by sections (Top, Middle, Bottom, etc.)
                </p>
                <div class="sections-list-fast" id="sections-container">
                    ${sectionsHTML}
                    <button class="add-section-btn-fast" data-action="add-section">
                        + Add New Section
                    </button>
                </div>
            </div>
        `;
    }

    function renderSectionEditor(section, index) {
        const positionLabel = getSectionPositionLabel(index);
        
        return `
            <div class="section-fast" data-section-id="${section.id}">
                <div class="section-header-fast">
                    <span class="section-color" style="background: ${section.color}"></span>
                    <input class="section-name-input-fast" 
                           value="${section.name}" 
                           placeholder="Section name"
                           style="color: #ffffff;">
                    <span class="section-position-label">${positionLabel}</span>
                    <span class="section-stats">${section.items.length} items</span>
                    ${index > 2 ? `<button class="section-delete" data-action="remove-section">×</button>` : ''}
                </div>
            </div>
        `;
    }

    function getSectionPositionLabel(index) {
        const positions = ['TOP', 'MIDDLE', 'BOTTOM', 'EXTRA 1', 'EXTRA 2', 'EXTRA 3'];
        return positions[index] || `EXTRA ${index - 2}`;
    }

    function renderItemsStep(container) {
        const gearHTML = renderGearLibrary();
        const packHTML = renderPackContents();
        const essentialsHTML = renderEssentials();
        
        container.innerHTML = `
            <div class="items-layout-fast">
                <div class="gear-panel-fast">
                    ${gearHTML}
                </div>
                <div class="pack-panel-fast">
                    ${packHTML}
                </div>
                <div class="quick-panel-fast">
                    ${essentialsHTML}
                </div>
            </div>
        `;
    }

    function renderGearLibrary() {
        const categories = ['all', 'shelter', 'cooking', 'clothing', 'essentials'];
        const filterButtons = categories.map(cat => `
            <button class="filter-btn-fast ${state.currentFilter === cat ? 'active' : ''}" 
                    data-action="filter-gear" 
                    data-category="${cat}"
                    style="color: ${state.currentFilter === cat ? '#ffffff' : 'rgba(255,255,255,0.8)'};">
                ${cat.charAt(0).toUpperCase() + cat.slice(1)}
            </button>
        `).join('');
        
        const items = state.gearInventory
            .filter(item => state.currentFilter === 'all' || item.category === state.currentFilter)
            .filter(item => !state.searchTerm || item.name.toLowerCase().includes(state.searchTerm.toLowerCase()))
            .map(item => `
                <div class="gear-item-fast" data-draggable="${item.id}" draggable="true">
                    <span class="gear-emoji">${item.emoji}</span>
                    <span class="gear-name">${item.name}</span>
                    <span class="gear-weight">${item.weight}g</span>
                </div>
            `).join('');
        
        return `
            <div class="gear-header">
                <h3 style="color: #ffffff;">Gear Library</h3>
                <input type="search" 
                       class="gear-search-fast" 
                       placeholder="Search..."
                       style="margin-bottom: 10px;">
            </div>
            <div class="gear-filters-fast">
                ${filterButtons}
            </div>
            <div class="gear-list-fast">
                ${items}
            </div>
        `;
    }

    function renderPackContents() {
        const sectionsHTML = state.builderPack.sections.map((section, index) => {
            const positionLabel = getSectionPositionLabel(index);
            const sectionWeight = section.items.reduce((sum, item) => sum + item.weight, 0);
            
            const itemsHTML = section.items.map(item => `
                <div class="item-fast" data-item-id="${item.id}">
                    <input type="checkbox" ${item.packed ? 'checked' : ''}>
                    <span class="item-name">${item.name}</span>
                    <span class="item-weight">${item.weight}g</span>
                    <button class="item-remove" data-action="remove-item">×</button>
                </div>
            `).join('');
            
            return `
                <div class="section-fast" data-section-id="${section.id}">
                    <div class="section-header-fast">
                        <span class="section-color" style="background: ${section.color}"></span>
                        <span style="color: #ffffff; font-weight: 600;">${section.name}</span>
                        <span class="section-position-label">${positionLabel}</span>
                        <span class="section-stats">${section.items.length} items • ${sectionWeight}g</span>
                    </div>
                    <div class="section-items-fast" data-dropzone="${section.id}">
                        ${itemsHTML || '<div class="drop-hint">Drop items here</div>'}
                    </div>
                </div>
            `;
        }).join('');
        
        const totalWeight = state.builderPack.sections.reduce((sum, section) => 
            sum + section.items.reduce((sSum, item) => sSum + item.weight, 0), 0);
        const totalItems = state.builderPack.sections.reduce((sum, section) => 
            sum + section.items.length, 0);
        
        return `
            <div class="pack-header">
                <h3 style="color: #ffffff;">Pack Contents</h3>
                <div class="pack-stats-live">
                    <span>⚖️ ${(totalWeight/1000).toFixed(1)}kg</span>
                    <span>📦 ${totalItems} items</span>
                </div>
            </div>
            <div class="sections-list-fast" id="pack-sections">
                ${sectionsHTML}
            </div>
        `;
    }

    function renderEssentials() {
        const essentials = [
            { id: 'first-aid', name: 'First Aid', weight: 200, emoji: '🏥' },
            { id: 'water-2l', name: 'Water 2L', weight: 2000, emoji: '💧' },
            { id: 'map', name: 'Map', weight: 50, emoji: '🗺️' },
            { id: 'headlamp', name: 'Headlamp', weight: 75, emoji: '🔦' },
            { id: 'knife', name: 'Knife', weight: 100, emoji: '🔪' },
            { id: 'whistle', name: 'Whistle', weight: 20, emoji: '📯' }
        ];
        
        const buttons = essentials.map(item => `
            <button class="essential-btn-fast" data-action="add-essential" data-item-id="${item.id}">
                <span>${item.emoji}</span>
                <span>${item.name}</span>
                <small>${item.weight}g</small>
            </button>
        `).join('');
        
        return `
            <h4 style="color: #ffffff;">Essentials</h4>
            <div class="essentials-grid">
                ${buttons}
            </div>
        `;
    }

    function renderReviewStep(container) {
        const totalWeight = state.builderPack.sections.reduce((sum, section) => 
            sum + section.items.reduce((sSum, item) => sSum + item.weight, 0), 0);
        const totalItems = state.builderPack.sections.reduce((sum, section) => 
            sum + section.items.length, 0);
        
        const sectionsHTML = state.builderPack.sections.map((section, index) => {
            const sectionWeight = section.items.reduce((sum, item) => sum + item.weight, 0);
            const positionLabel = getSectionPositionLabel(index);
            
            return `
                <div class="section-summary">
                    <div class="section-summary-header">
                        <span class="color-dot" style="background: ${section.color}"></span>
                        <span style="color: #ffffff; font-weight: 600;">${section.name}</span>
                        <span class="section-position-label">${positionLabel}</span>
                        <span style="color: rgba(255,255,255,0.7);">${section.items.length} items • ${sectionWeight}g</span>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = `
            <div class="review-fast">
                <div class="review-header">
                    <h2 style="color: #ffffff;">Review Your Pack</h2>
                    <p style="color: rgba(255,255,255,0.7);">Everything looks good! Ready to save your pack?</p>
                </div>
                
                <div class="stats-grid-fast">
                    <div class="stat-card">
                        <span class="stat-value">${(totalWeight/1000).toFixed(1)}kg</span>
                        <span class="stat-label">Total Weight</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${totalItems}</span>
                        <span class="stat-label">Total Items</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${state.builderPack.sections.length}</span>
                        <span class="stat-label">Sections</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value">${state.builderPack.season}</span>
                        <span class="stat-label">Season</span>
                    </div>
                </div>
                
                <div class="pack-summary">
                    <h3 style="color: #ffffff;">Pack: ${state.builderPack.name || 'Unnamed Pack'}</h3>
                    <div class="summary-details">
                        <p style="color: rgba(255,255,255,0.8);">${state.builderPack.description || 'No description'}</p>
                    </div>
                </div>
                
                <div class="sections-summary">
                    ${sectionsHTML}
                </div>
            </div>
        `;
    }

    // ==================== ACTIONS ====================
    function addSection() {
        const sectionIndex = state.builderPack.sections.length;
        const positionNames = ['Top Section', 'Middle Section', 'Bottom Section', 
                               'Extra Section 1', 'Extra Section 2', 'Extra Section 3'];
        const sectionName = positionNames[sectionIndex] || `Extra Section ${sectionIndex - 2}`;
        
        const newSection = {
            id: `s${Date.now()}`,
            name: sectionName,
            color: '#' + Math.floor(Math.random()*16777215).toString(16),
            items: [],
            position: getSectionPositionLabel(sectionIndex)
        };
        
        state.builderPack.sections.push(newSection);
        renderBuilderStep();
    }

    function removeSection(sectionId) {
        state.builderPack.sections = state.builderPack.sections.filter(s => s.id !== sectionId);
        renderBuilderStep();
    }

    function addItemToSection(itemId, sectionId) {
        const item = state.gearInventory.find(g => g.id === itemId);
        if (!item) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        // Check if item already exists
        if (!section.items.find(i => i.id === itemId)) {
            section.items.push({
                id: itemId,
                name: item.name,
                weight: item.weight,
                packed: false
            });
            renderBuilderStep();
            showToast('Item added!', 'success');
        }
    }

    function removeItem(itemElement) {
        if (!itemElement) return;
        
        const sectionElement = itemElement.closest('.section-fast');
        const sectionId = sectionElement.dataset.sectionId;
        const itemId = itemElement.dataset.itemId;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.items = section.items.filter(i => i.id !== itemId);
            renderBuilderStep();
        }
    }

    function addEssential(itemId) {
        const item = state.gearInventory.find(g => g.id === itemId);
        if (!item) return;
        
        // Add to first section by default
        if (state.builderPack.sections.length > 0) {
            const section = state.builderPack.sections[0];
            if (!section.items.find(i => i.id === itemId)) {
                section.items.push({
                    id: itemId,
                    name: item.name,
                    weight: item.weight,
                    packed: false
                });
                renderBuilderStep();
                showToast(`${item.name} added to ${section.name}!`, 'success');
            }
        }
    }

    function filterGear(category) {
        state.currentFilter = category;
        renderBuilderStep();
    }

    function searchGear(term) {
        state.searchTerm = term;
        // Debounce search
        clearTimeout(state.searchTimeout);
        state.searchTimeout = setTimeout(() => {
            renderBuilderStep();
        }, 300);
    }

    function updateSectionName(input) {
        const sectionElement = input.closest('.section-fast');
        const sectionId = sectionElement.dataset.sectionId;
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = input.value;
        }
    }

    // ==================== NAVIGATION ====================
    function nextStep() {
        if (state.currentStep < 4) {
            state.currentStep++;
            renderBuilderStep();
        }
    }

    function prevStep() {
        if (state.currentStep > 1) {
            state.currentStep--;
            renderBuilderStep();
        }
    }

    function updateProgressBar() {
        document.querySelectorAll('.progress-step').forEach((step, index) => {
            step.classList.toggle('active', index + 1 === state.currentStep);
            step.classList.toggle('completed', index + 1 < state.currentStep);
        });
        
        document.getElementById('btn-prev').disabled = state.currentStep === 1;
        document.getElementById('btn-next').style.display = state.currentStep === 4 ? 'none' : 'block';
        document.getElementById('btn-save').classList.toggle('hidden', state.currentStep !== 4);
    }

    function updateBuilderStats() {
        const totalWeight = state.builderPack ? 
            state.builderPack.sections.reduce((sum, section) => 
                sum + section.items.reduce((sSum, item) => sSum + item.weight, 0), 0) : 0;
        const totalItems = state.builderPack ? 
            state.builderPack.sections.reduce((sum, section) => 
                sum + section.items.length, 0) : 0;
        
        const weightEl = document.getElementById('builder-total-weight');
        const itemsEl = document.getElementById('builder-total-items');
        
        if (weightEl) weightEl.textContent = `${(totalWeight/1000).toFixed(1)}kg`;
        if (itemsEl) itemsEl.textContent = totalItems;
    }

    // ==================== SAVE & UTILITIES ====================
    function savePack() {
        if (!state.builderPack.name) {
            showToast('Please enter a pack name', 'error');
            return;
        }
        
        state.packs.push({...state.builderPack});
        localStorage.setItem('btt_packs', JSON.stringify(state.packs));
        
        showToast('Pack saved successfully!', 'success');
        closeBuilder();
        renderPacks();
    }

    function renderPacks() {
        const container = document.getElementById('pack-grid');
        if (!container) return;
        
        if (state.packs.length === 0) {
            document.getElementById('empty-state')?.classList.remove('hidden');
            document.getElementById('pack-grid')?.classList.add('hidden');
            return;
        }
        
        document.getElementById('empty-state')?.classList.add('hidden');
        document.getElementById('pack-grid')?.classList.remove('hidden');
        
        const packsHTML = state.packs.map(pack => {
            const weight = pack.sections.reduce((sum, section) => 
                sum + section.items.reduce((sSum, item) => sSum + item.weight, 0), 0);
            const items = pack.sections.reduce((sum, section) => 
                sum + section.items.length, 0);
            
            return `
                <div class="pack-card-fast">
                    <h3 style="color: #ffffff;">${pack.name}</h3>
                    <div class="pack-stats">
                        <span style="color: #86efac;">⚖️ ${(weight/1000).toFixed(1)}kg</span>
                        <span style="color: #86efac;">📦 ${items} items</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.7);">${pack.description || 'No description'}</p>
                </div>
            `;
        }).join('');
        
        container.innerHTML = packsHTML;
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast-fast ${type}`;
        toast.textContent = message;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 200);
        }, 3000);
    }

    // ==================== PUBLIC API ====================
    return {
        init,
        createNew: openBuilder,
        closeBuilder,
        nextStep,
        prevStep,
        savePackFromBuilder: savePack,
        updatePackName: (name) => { state.builderPack.name = name; },
        updatePackDesc: (desc) => { state.builderPack.description = desc; },
        setSeason: (season) => { 
            state.builderPack.season = season; 
            renderBuilderStep();
        },
        // Placeholder functions for main page
        quickStart: () => openBuilder(),
        showTour: () => showToast('Tour coming soon!', 'info'),
        toggleView: () => showToast('View toggle coming soon!', 'info'),
        search: () => {},
        filter: () => {},
        filterByWeight: () => {},
        showTemplates: () => showToast('Templates coming soon!', 'info'),
        showImport: () => showToast('Import coming soon!', 'info'),
        createFromLastTrip: () => showToast('Coming soon!', 'info'),
        duplicateFavorite: () => showToast('Coming soon!', 'info'),
        smartPack: () => showToast('AI features coming soon!', 'info'),
        showCategory: () => {},
        filterByTag: () => {},
        importFromFile: () => showToast('Import coming soon!', 'info'),
        saveDraft: () => showToast('Draft saved!', 'success'),
        previewPack: () => showToast('Preview coming soon!', 'info')
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    PackManager.init();
});
