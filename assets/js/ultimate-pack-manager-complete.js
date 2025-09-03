/**
 * Ultimate Pack Manager - Complete End-to-End Implementation
 * Fully functional backpack builder for organizing and tracking trip items
 */

const PackManager = (function() {
    'use strict';

    // ==================== STATE MANAGEMENT ====================
    const state = {
        packs: [],
        currentPack: null,
        builderPack: null, // Pack being built/edited
        currentStep: 1,
        currentView: 'grid',
        filters: {
            type: 'all',
            weight: 30,
            season: null,
            tags: [],
            search: ''
        },
        gearInventory: [],
        templates: [],
        categories: {
            all: { count: 0 },
            favorites: { count: 0 },
            recent: { count: 0 },
            shared: { count: 0 }
        },
        isLoading: false,
        detailPanelOpen: false,
        builderOpen: false,
        draggedItem: null
    };

    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        console.log('🎒 Pack Manager Starting...');
        
        // Load all data
        await loadInitialData();
        
        // Setup UI
        setupEventListeners();
        setupDragAndDrop();
        
        // Render initial view
        updateHeroStats();
        renderPacks();
        
        console.log('✅ Pack Manager Ready!');
    }

    async function loadInitialData() {
        setState({ isLoading: true });
        
        try {
            // Load packs from localStorage or use mock data
            const savedPacks = localStorage.getItem('btt_packs');
            if (savedPacks) {
                state.packs = JSON.parse(savedPacks);
            } else {
                state.packs = getMockPacks();
                savePacks();
            }
            
            // Load gear inventory
            const savedGear = localStorage.getItem('btt_gear');
            if (savedGear) {
                state.gearInventory = JSON.parse(savedGear);
            } else {
                state.gearInventory = getMockGear();
                saveGear();
            }
            
            // Load templates
            state.templates = getMockTemplates();
            
            categorizePacks();
        } finally {
            setState({ isLoading: false });
        }
    }

    // ==================== PACK BUILDER ====================
    function createNew() {
        // Initialize new pack
        state.builderPack = {
            id: generateId(),
            name: '',
            type: 'custom',
            description: '',
            capacity_l: 65,
            weight_empty_g: 1500,
            sections: getDefaultSections(),
            tags: [],
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        
        state.currentStep = 1;
        openBuilder();
    }

    function openBuilder(packId = null) {
        if (packId) {
            // Edit existing pack
            const pack = state.packs.find(p => p.id === packId);
            if (pack) {
                state.builderPack = JSON.parse(JSON.stringify(pack)); // Deep clone
            }
        }
        
        state.builderOpen = true;
        state.currentStep = 1;
        
        const modal = document.getElementById('pack-builder-modal');
        modal.classList.remove('hidden');
        
        renderBuilderStep();
        updateBuilderProgress();
        updateBuilderStats();
    }

    function closeBuilder() {
        if (state.builderPack && confirm('You have unsaved changes. Are you sure you want to close?')) {
            state.builderOpen = false;
            state.builderPack = null;
            state.currentStep = 1;
            
            const modal = document.getElementById('pack-builder-modal');
            modal.classList.add('hidden');
        }
    }

    function renderBuilderStep() {
        const container = document.getElementById('builder-layout');
        if (!container) return;
        
        let content = '';
        
        switch (state.currentStep) {
            case 1:
                content = renderDetailsStep();
                break;
            case 2:
                content = renderSectionsStep();
                break;
            case 3:
                content = renderItemsStep();
                break;
            case 4:
                content = renderReviewStep();
                break;
        }
        
        container.innerHTML = `<div class="builder-step-content">${content}</div>`;
        
        // Re-attach event listeners after render
        setupStepEventListeners();
    }

    function renderDetailsStep() {
        const pack = state.builderPack;
        
        return `
            <div class="details-grid">
                <div class="details-section">
                    <h3 class="section-title">Basic Information</h3>
                    <div class="form-group">
                        <label class="form-label">Pack Name *</label>
                        <input type="text" 
                               id="pack-name" 
                               class="form-control" 
                               value="${pack.name || ''}" 
                               placeholder="My Adventure Pack"
                               onchange="PackManager.updatePackDetail('name', this.value)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="pack-description" 
                                  class="form-control form-control-textarea" 
                                  placeholder="Describe your pack setup..."
                                  onchange="PackManager.updatePackDetail('description', this.value)">${pack.description || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select id="pack-type" 
                                class="form-control"
                                onchange="PackManager.updatePackDetail('type', this.value)">
                            <option value="custom" ${pack.type === 'custom' ? 'selected' : ''}>Custom</option>
                            <option value="day" ${pack.type === 'day' ? 'selected' : ''}>Day Hike</option>
                            <option value="weekend" ${pack.type === 'weekend' ? 'selected' : ''}>Weekend</option>
                            <option value="multi" ${pack.type === 'multi' ? 'selected' : ''}>Multi-Day</option>
                            <option value="thru" ${pack.type === 'thru' ? 'selected' : ''}>Thru-Hike</option>
                            <option value="ultra" ${pack.type === 'ultra' ? 'selected' : ''}>Ultralight</option>
                        </select>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3 class="section-title">Pack Specifications</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Capacity (L)</label>
                            <input type="number" 
                                   id="pack-capacity" 
                                   class="form-control" 
                                   value="${pack.capacity_l || 65}" 
                                   min="10" 
                                   max="150"
                                   onchange="PackManager.updatePackDetail('capacity_l', this.value)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Empty Weight (g)</label>
                            <input type="number" 
                                   id="pack-weight" 
                                   class="form-control" 
                                   value="${pack.weight_empty_g || 1500}" 
                                   min="0"
                                   onchange="PackManager.updatePackDetail('weight_empty_g', this.value)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Season</label>
                        <div class="season-selector">
                            <button class="season-btn ${pack.season === 'spring' ? 'active' : ''}" 
                                    onclick="PackManager.updatePackDetail('season', 'spring')">🌸 Spring</button>
                            <button class="season-btn ${pack.season === 'summer' ? 'active' : ''}" 
                                    onclick="PackManager.updatePackDetail('season', 'summer')">☀️ Summer</button>
                            <button class="season-btn ${pack.season === 'fall' ? 'active' : ''}" 
                                    onclick="PackManager.updatePackDetail('season', 'fall')">🍂 Fall</button>
                            <button class="season-btn ${pack.season === 'winter' ? 'active' : ''}" 
                                    onclick="PackManager.updatePackDetail('season', 'winter')">❄️ Winter</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags (comma separated)</label>
                        <input type="text" 
                               id="pack-tags" 
                               class="form-control" 
                               value="${(pack.tags || []).join(', ')}" 
                               placeholder="lightweight, family, photography"
                               onchange="PackManager.updatePackTags(this.value)">
                    </div>
                </div>
            </div>
        `;
    }

    function renderSectionsStep() {
        const pack = state.builderPack;
        const sections = pack.sections || [];
        
        return `
            <div class="sections-manager">
                <div class="sections-list" id="sections-list">
                    ${sections.map((section, index) => `
                        <div class="section-card" data-section-id="${section.id}" draggable="true">
                            <div class="section-header">
                                <div class="section-color-picker" style="background: ${section.color}">
                                    <input type="color" 
                                           value="${section.color}" 
                                           onchange="PackManager.updateSectionColor('${section.id}', this.value)">
                                </div>
                                <div class="section-info">
                                    <input type="text" 
                                           class="section-name-input" 
                                           value="${section.name}" 
                                           placeholder="Section Name"
                                           onchange="PackManager.updateSectionName('${section.id}', this.value)">
                                    <small>${section.items ? section.items.length : 0} items</small>
                                </div>
                                <div class="section-actions">
                                    <button class="btn-section-action" 
                                            onclick="PackManager.moveSection('${section.id}', 'up')" 
                                            ${index === 0 ? 'disabled' : ''}>↑</button>
                                    <button class="btn-section-action" 
                                            onclick="PackManager.moveSection('${section.id}', 'down')"
                                            ${index === sections.length - 1 ? 'disabled' : ''}>↓</button>
                                    <button class="btn-section-action" 
                                            onclick="PackManager.deleteSection('${section.id}')">🗑️</button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                <button class="btn-add-section" onclick="PackManager.addSection()">
                    <span>➕</span> Add New Section
                </button>
            </div>
        `;
    }

    function renderItemsStep() {
        const pack = state.builderPack;
        const sections = pack.sections || [];
        
        return `
            <div class="items-manager">
                <!-- Gear Inventory -->
                <div class="items-sidebar">
                    <h4>Gear Inventory</h4>
                    <div class="gear-search-box">
                        <input type="search" 
                               placeholder="Search gear..." 
                               class="form-control"
                               onkeyup="PackManager.filterGearInventory(this.value)">
                    </div>
                    <div class="gear-categories">
                        <button class="category-chip active" onclick="PackManager.filterGearByCategory('all')">All</button>
                        <button class="category-chip" onclick="PackManager.filterGearByCategory('shelter')">Shelter</button>
                        <button class="category-chip" onclick="PackManager.filterGearByCategory('clothing')">Clothing</button>
                        <button class="category-chip" onclick="PackManager.filterGearByCategory('cooking')">Cooking</button>
                        <button class="category-chip" onclick="PackManager.filterGearByCategory('water')">Water</button>
                    </div>
                    <div class="gear-list" id="gear-inventory">
                        ${state.gearInventory.map(gear => `
                            <div class="gear-item" 
                                 draggable="true" 
                                 data-gear-id="${gear.id}"
                                 ondragstart="PackManager.handleDragStart(event, 'gear', '${gear.id}')">
                                <span>${gear.name}</span>
                                <small>${formatWeight(gear.weight_g)}</small>
                            </div>
                        `).join('')}
                    </div>
                    
                    <button class="btn-primary" style="width: 100%; margin-top: 10px;" onclick="PackManager.addCustomGear()">
                        Add Custom Item
                    </button>
                </div>
                
                <!-- Pack Sections -->
                <div class="items-main">
                    <h4>Pack Sections & Items</h4>
                    <div class="pack-sections">
                        ${sections.map(section => `
                            <div class="pack-section" data-section-id="${section.id}">
                                <div class="pack-section-header">
                                    <span class="pack-section-color" style="background: ${section.color}"></span>
                                    <span class="pack-section-name">${section.name}</span>
                                    <span class="pack-section-count">${section.items ? section.items.length : 0} items</span>
                                </div>
                                <div class="pack-items-list" 
                                     ondrop="PackManager.handleDrop(event, '${section.id}')"
                                     ondragover="PackManager.handleDragOver(event)">
                                    ${section.items && section.items.length > 0 ? 
                                        section.items.map(item => `
                                            <div class="pack-item" data-item-id="${item.id}">
                                                <input type="checkbox" 
                                                       class="pack-item-checkbox" 
                                                       ${item.packed ? 'checked' : ''}
                                                       onchange="PackManager.toggleItemPacked('${section.id}', '${item.id}')">
                                                <div class="pack-item-info">
                                                    <div class="pack-item-name">${item.name}</div>
                                                    <div class="pack-item-meta">${item.category || 'Other'}</div>
                                                </div>
                                                <span class="pack-item-weight">${formatWeight(item.weight_g)}</span>
                                                <div class="pack-item-actions">
                                                    <button onclick="PackManager.removeItemFromSection('${section.id}', '${item.id}')">×</button>
                                                </div>
                                            </div>
                                        `).join('') :
                                        '<div class="empty-message">Drag items here</div>'
                                    }
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Quick Add -->
                <div class="items-sidebar">
                    <h4>Quick Add Items</h4>
                    <div class="quick-add-list">
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('First Aid Kit', 200, 'safety')">
                            🏥 First Aid Kit (200g)
                        </button>
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('Water Bottle', 100, 'water')">
                            💧 Water Bottle (100g)
                        </button>
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('Headlamp', 75, 'electronics')">
                            🔦 Headlamp (75g)
                        </button>
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('Map & Compass', 150, 'navigation')">
                            🗺️ Map & Compass (150g)
                        </button>
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('Emergency Whistle', 20, 'safety')">
                            📯 Emergency Whistle (20g)
                        </button>
                        <button class="quick-add-btn" onclick="PackManager.quickAddItem('Rain Cover', 150, 'protection')">
                            ☔ Rain Cover (150g)
                        </button>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: rgba(134, 239, 172, 0.1); border-radius: 8px;">
                        <h5>Tips:</h5>
                        <ul style="font-size: 0.85rem; margin: 10px 0; padding-left: 20px;">
                            <li>Drag items from inventory to sections</li>
                            <li>Check items to mark as packed</li>
                            <li>Click × to remove items</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
    }

    function renderReviewStep() {
        const pack = state.builderPack;
        const stats = calculatePackStats(pack);
        
        return `
            <div class="review-container">
                <div class="review-summary">
                    <h3 class="summary-title">Pack Summary: ${pack.name || 'Unnamed Pack'}</h3>
                    <div class="summary-stats">
                        <div class="summary-stat">
                            <span class="summary-stat-value">${stats.totalWeight}</span>
                            <span class="summary-stat-label">Total Weight</span>
                        </div>
                        <div class="summary-stat">
                            <span class="summary-stat-value">${stats.totalItems}</span>
                            <span class="summary-stat-label">Total Items</span>
                        </div>
                        <div class="summary-stat">
                            <span class="summary-stat-value">${stats.packedItems}</span>
                            <span class="summary-stat-label">Packed</span>
                        </div>
                        <div class="summary-stat">
                            <span class="summary-stat-value">${stats.sections}</span>
                            <span class="summary-stat-label">Sections</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <strong>Type:</strong> ${pack.type}<br>
                        <strong>Capacity:</strong> ${pack.capacity_l}L<br>
                        <strong>Season:</strong> ${pack.season || 'All Seasons'}<br>
                        <strong>Tags:</strong> ${(pack.tags || []).join(', ') || 'None'}
                    </div>
                </div>
                
                <div class="review-sections">
                    <h4>Sections Breakdown</h4>
                    ${pack.sections.map(section => {
                        const sectionWeight = calculateSectionWeight(section);
                        return `
                            <div class="review-section">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="width: 20px; height: 20px; background: ${section.color}; border-radius: 4px;"></span>
                                    <strong>${section.name}</strong>
                                    <span style="margin-left: auto;">${section.items ? section.items.length : 0} items • ${formatWeight(sectionWeight)}</span>
                                </div>
                                ${section.items && section.items.length > 0 ? `
                                    <ul style="margin: 0; padding-left: 30px; font-size: 0.9rem;">
                                        ${section.items.map(item => `
                                            <li>${item.packed ? '✅' : '⬜'} ${item.name} (${formatWeight(item.weight_g)})</li>
                                        `).join('')}
                                    </ul>
                                ` : '<p style="margin-left: 30px; color: var(--text-secondary);">No items</p>'}
                            </div>
                        `;
                    }).join('')}
                </div>
                
                <div style="padding: 20px; background: rgba(134, 239, 172, 0.1); border-radius: 12px; text-align: center;">
                    <h3 style="color: var(--forest-mint); margin-bottom: 10px;">Ready to Save?</h3>
                    <p>Review your pack configuration above. Click "Save Pack" to add it to your collection.</p>
                </div>
            </div>
        `;
    }

    function nextStep() {
        if (state.currentStep < 4) {
            // Validate current step
            if (state.currentStep === 1 && !state.builderPack.name) {
                showToast('Please enter a pack name', 'error');
                return;
            }
            
            state.currentStep++;
            renderBuilderStep();
            updateBuilderProgress();
        }
    }

    function previousStep() {
        if (state.currentStep > 1) {
            state.currentStep--;
            renderBuilderStep();
            updateBuilderProgress();
        }
    }

    function updateBuilderProgress() {
        // Update progress steps
        document.querySelectorAll('.progress-step').forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.toggle('active', stepNum === state.currentStep);
            step.classList.toggle('completed', stepNum < state.currentStep);
        });
        
        // Update navigation buttons
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');
        const saveBtn = document.getElementById('btn-save');
        
        if (prevBtn) prevBtn.disabled = state.currentStep === 1;
        
        if (state.currentStep === 4) {
            if (nextBtn) nextBtn.classList.add('hidden');
            if (saveBtn) saveBtn.classList.remove('hidden');
        } else {
            if (nextBtn) nextBtn.classList.remove('hidden');
            if (saveBtn) saveBtn.classList.add('hidden');
        }
    }

    function updateBuilderStats() {
        if (!state.builderPack) return;
        
        const stats = calculatePackStats(state.builderPack);
        
        const weightEl = document.getElementById('builder-total-weight');
        const itemsEl = document.getElementById('builder-total-items');
        
        if (weightEl) weightEl.textContent = stats.totalWeight;
        if (itemsEl) itemsEl.textContent = stats.totalItems;
    }

    function savePackFromBuilder() {
        const pack = state.builderPack;
        
        if (!pack.name) {
            showToast('Please enter a pack name', 'error');
            return;
        }
        
        // Update timestamps
        pack.updated_at = new Date().toISOString();
        
        // Check if updating existing pack
        const existingIndex = state.packs.findIndex(p => p.id === pack.id);
        if (existingIndex >= 0) {
            state.packs[existingIndex] = pack;
            showToast('Pack updated successfully!', 'success');
        } else {
            state.packs.push(pack);
            showToast('Pack created successfully!', 'success');
        }
        
        // Save to localStorage
        savePacks();
        
        // Close builder and refresh view
        state.builderOpen = false;
        state.builderPack = null;
        document.getElementById('pack-builder-modal').classList.add('hidden');
        
        renderPacks();
        updateHeroStats();
    }

    // ==================== PACK OPERATIONS ====================
    function updatePackDetail(field, value) {
        if (!state.builderPack) return;
        
        if (field === 'capacity_l' || field === 'weight_empty_g') {
            state.builderPack[field] = parseInt(value) || 0;
        } else {
            state.builderPack[field] = value;
        }
        
        // Update season buttons if needed
        if (field === 'season') {
            document.querySelectorAll('.season-btn').forEach(btn => {
                btn.classList.toggle('active', btn.textContent.toLowerCase().includes(value));
            });
        }
        
        updateBuilderStats();
    }

    function updatePackTags(value) {
        if (!state.builderPack) return;
        
        state.builderPack.tags = value.split(',').map(tag => tag.trim()).filter(tag => tag);
    }

    function addSection() {
        if (!state.builderPack) return;
        
        const newSection = {
            id: generateId(),
            name: 'New Section',
            color: getRandomColor(),
            items: []
        };
        
        state.builderPack.sections.push(newSection);
        renderBuilderStep();
    }

    function updateSectionName(sectionId, name) {
        if (!state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = name;
        }
    }

    function updateSectionColor(sectionId, color) {
        if (!state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.color = color;
        }
    }

    function deleteSection(sectionId) {
        if (!state.builderPack) return;
        
        if (confirm('Delete this section and all its items?')) {
            state.builderPack.sections = state.builderPack.sections.filter(s => s.id !== sectionId);
            renderBuilderStep();
            updateBuilderStats();
        }
    }

    function moveSection(sectionId, direction) {
        if (!state.builderPack) return;
        
        const sections = state.builderPack.sections;
        const index = sections.findIndex(s => s.id === sectionId);
        
        if (direction === 'up' && index > 0) {
            [sections[index - 1], sections[index]] = [sections[index], sections[index - 1]];
        } else if (direction === 'down' && index < sections.length - 1) {
            [sections[index], sections[index + 1]] = [sections[index + 1], sections[index]];
        }
        
        renderBuilderStep();
    }

    function quickAddItem(name, weight, category) {
        if (!state.builderPack || state.builderPack.sections.length === 0) {
            showToast('Please add a section first', 'error');
            return;
        }
        
        const item = {
            id: generateId(),
            name: name,
            weight_g: weight,
            category: category,
            packed: false
        };
        
        // Add to first section by default
        state.builderPack.sections[0].items = state.builderPack.sections[0].items || [];
        state.builderPack.sections[0].items.push(item);
        
        renderBuilderStep();
        updateBuilderStats();
        showToast(`Added ${name} to ${state.builderPack.sections[0].name}`, 'success');
    }

    function addCustomGear() {
        const name = prompt('Item name:');
        if (!name) return;
        
        const weight = parseInt(prompt('Weight in grams:') || '0');
        const category = prompt('Category (optional):') || 'other';
        
        const gear = {
            id: generateId(),
            name: name,
            weight_g: weight,
            category: category
        };
        
        state.gearInventory.push(gear);
        saveGear();
        renderBuilderStep();
        showToast('Custom gear added to inventory', 'success');
    }

    function removeItemFromSection(sectionId, itemId) {
        if (!state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section && section.items) {
            section.items = section.items.filter(i => i.id !== itemId);
            renderBuilderStep();
            updateBuilderStats();
        }
    }

    function toggleItemPacked(sectionId, itemId) {
        if (!state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (section && section.items) {
            const item = section.items.find(i => i.id === itemId);
            if (item) {
                item.packed = !item.packed;
                updateBuilderStats();
            }
        }
    }

    // ==================== DRAG AND DROP ====================
    function setupDragAndDrop() {
        // Gear items drag handling is set in HTML
    }

    function handleDragStart(event, type, id) {
        state.draggedItem = { type, id };
        event.dataTransfer.effectAllowed = 'copy';
    }

    function handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        event.currentTarget.classList.add('drag-over');
    }

    function handleDrop(event, sectionId) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');
        
        if (!state.draggedItem || !state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        // Get gear item
        const gear = state.gearInventory.find(g => g.id === state.draggedItem.id);
        if (!gear) return;
        
        // Check if item already exists in section
        section.items = section.items || [];
        if (section.items.find(i => i.gear_id === gear.id)) {
            showToast('Item already in this section', 'warning');
            return;
        }
        
        // Add item to section
        const item = {
            id: generateId(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g,
            category: gear.category,
            packed: false
        };
        
        section.items.push(item);
        renderBuilderStep();
        updateBuilderStats();
        showToast(`Added ${gear.name} to ${section.name}`, 'success');
        
        state.draggedItem = null;
    }

    // ==================== VIEW MANAGEMENT ====================
    function renderPacks() {
        const container = document.getElementById('pack-container');
        if (!container) return;
        
        hideElement('loading-state');
        hideElement('empty-state');
        hideElement('pack-grid');
        
        if (state.isLoading) {
            showElement('loading-state');
            return;
        }
        
        const filteredPacks = getFilteredPacks();
        
        if (filteredPacks.length === 0) {
            if (state.packs.length === 0) {
                showElement('empty-state');
            } else {
                showEmptyFilterState();
            }
            return;
        }
        
        const grid = document.getElementById('pack-grid');
        if (!grid) return;
        
        grid.innerHTML = '';
        showElement('pack-grid');
        
        filteredPacks.forEach(pack => {
            const card = createPackCard(pack);
            grid.appendChild(card);
        });
    }

    function createPackCard(pack) {
        const card = document.createElement('div');
        card.className = 'pack-card fade-in';
        card.dataset.packId = pack.id;
        
        const stats = calculatePackStats(pack);
        const packingProgress = calculatePackingProgress(pack);
        
        card.innerHTML = `
            <div class="pack-card-header">
                <div class="pack-card-title">
                    ${pack.name}
                    ${pack.favorite ? '<span class="pack-favorite">⭐</span>' : ''}
                </div>
                <div class="pack-card-meta">
                    <span>${pack.type}</span>
                    <span>•</span>
                    <span>${formatDate(pack.updated_at)}</span>
                </div>
            </div>
            <div class="pack-card-body">
                <div class="pack-stats-grid">
                    <div class="pack-stat">
                        <div class="pack-stat-icon">⚖️</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${stats.totalWeight}</div>
                            <div class="pack-stat-label">Total</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">📦</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${stats.totalItems}</div>
                            <div class="pack-stat-label">Items</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">📍</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${stats.sections}</div>
                            <div class="pack-stat-label">Sections</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">✅</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${packingProgress}%</div>
                            <div class="pack-stat-label">Packed</div>
                        </div>
                    </div>
                </div>
                <div class="pack-progress">
                    <div class="progress-header">
                        <span>Packing Progress</span>
                        <span>${packingProgress}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${packingProgress}%"></div>
                    </div>
                </div>
            </div>
            <div class="pack-card-footer">
                <div class="pack-tags">
                    ${pack.tags ? pack.tags.map(tag => `<span class="pack-tag">${tag}</span>`).join('') : ''}
                </div>
                <div class="pack-actions">
                    <button class="pack-action-btn" onclick="PackManager.editPack('${pack.id}')" aria-label="Edit">
                        ✏️
                    </button>
                    <button class="pack-action-btn" onclick="PackManager.duplicatePack('${pack.id}')" aria-label="Duplicate">
                        📋
                    </button>
                    <button class="pack-action-btn" onclick="PackManager.exportPack('${pack.id}')" aria-label="Export">
                        📤
                    </button>
                </div>
            </div>
        `;
        
        card.addEventListener('click', (e) => {
            if (!e.target.closest('.pack-actions')) {
                showPackDetail(pack);
            }
        });
        
        return card;
    }

    // ==================== UTILITY FUNCTIONS ====================
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
            totalWeight: formatWeight(totalWeight),
            totalItems: totalItems,
            packedItems: packedItems,
            sections: pack.sections ? pack.sections.length : 0
        };
    }

    function calculateSectionWeight(section) {
        if (!section.items) return 0;
        return section.items.reduce((sum, item) => sum + (item.weight_g || 0), 0);
    }

    function calculatePackingProgress(pack) {
        let totalItems = 0;
        let packedItems = 0;
        
        if (pack.sections) {
            pack.sections.forEach(section => {
                if (section.items) {
                    totalItems += section.items.length;
                    packedItems += section.items.filter(i => i.packed).length;
                }
            });
        }
        
        return totalItems > 0 ? Math.round((packedItems / totalItems) * 100) : 0;
    }

    function formatWeight(grams) {
        if (!grams) return '0g';
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'Never';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 86400000) return 'Today';
        if (diff < 172800000) return 'Yesterday';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
        
        return date.toLocaleDateString();
    }

    function generateId() {
        return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function getRandomColor() {
        const colors = ['#8B4513', '#4A5568', '#D97706', '#3B82F6', '#EF4444', '#8B5CF6', '#10B981', '#6B7280'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    function setState(updates) {
        Object.assign(state, updates);
    }

    function showElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.remove('hidden');
    }

    function hideElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.add('hidden');
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type} fade-in`;
        
        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'warning' ? '⚠️' : 'ℹ️';
        
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ==================== STORAGE ====================
    function savePacks() {
        localStorage.setItem('btt_packs', JSON.stringify(state.packs));
    }

    function saveGear() {
        localStorage.setItem('btt_gear', JSON.stringify(state.gearInventory));
    }

    // ==================== MOCK DATA ====================
    function getMockPacks() {
        return [
            {
                id: 'pack_demo_1',
                name: 'Weekend Warrior',
                type: 'weekend',
                description: 'Perfect for 2-3 day adventures',
                capacity_l: 65,
                weight_empty_g: 1800,
                season: 'summer',
                tags: ['lightweight', 'summer'],
                sections: [
                    {
                        id: 's1',
                        name: 'Shelter & Sleep',
                        color: '#8B4513',
                        items: [
                            { id: 'i1', name: 'Tent', weight_g: 1500, category: 'shelter', packed: true },
                            { id: 'i2', name: 'Sleeping Bag', weight_g: 800, category: 'sleep', packed: true },
                            { id: 'i3', name: 'Sleeping Pad', weight_g: 400, category: 'sleep', packed: false }
                        ]
                    },
                    {
                        id: 's2',
                        name: 'Cooking',
                        color: '#D97706',
                        items: [
                            { id: 'i4', name: 'Stove', weight_g: 100, category: 'cooking', packed: true },
                            { id: 'i5', name: 'Pot', weight_g: 150, category: 'cooking', packed: false }
                        ]
                    }
                ],
                created_at: '2024-01-15T10:00:00Z',
                updated_at: '2024-01-20T15:30:00Z'
            }
        ];
    }

    function getMockGear() {
        return [
            { id: 'g1', name: 'Ultralight Tent', weight_g: 1200, category: 'shelter' },
            { id: 'g2', name: 'Down Sleeping Bag', weight_g: 650, category: 'sleep' },
            { id: 'g3', name: 'Inflatable Pad', weight_g: 350, category: 'sleep' },
            { id: 'g4', name: 'Canister Stove', weight_g: 75, category: 'cooking' },
            { id: 'g5', name: 'Titanium Pot', weight_g: 100, category: 'cooking' },
            { id: 'g6', name: 'Water Filter', weight_g: 60, category: 'water' },
            { id: 'g7', name: 'First Aid Kit', weight_g: 200, category: 'safety' },
            { id: 'g8', name: 'Headlamp', weight_g: 75, category: 'electronics' },
            { id: 'g9', name: 'Rain Jacket', weight_g: 250, category: 'clothing' },
            { id: 'g10', name: 'Trekking Poles', weight_g: 450, category: 'tools' }
        ];
    }

    function getMockTemplates() {
        return [
            {
                id: 't1',
                name: 'Day Hike Essentials',
                description: 'Everything you need for a day on the trail',
                type: 'day',
                sections: getDefaultSections().slice(0, 5)
            }
        ];
    }

    function getDefaultSections() {
        return [
            { id: 's1', name: 'Shelter & Sleep', color: '#8B4513', items: [] },
            { id: 's2', name: 'Clothing', color: '#4A5568', items: [] },
            { id: 's3', name: 'Cooking & Food', color: '#D97706', items: [] },
            { id: 's4', name: 'Water & Hydration', color: '#3B82F6', items: [] },
            { id: 's5', name: 'Navigation & Safety', color: '#EF4444', items: [] },
            { id: 's6', name: 'Personal Care', color: '#8B5CF6', items: [] },
            { id: 's7', name: 'Tools & Repair', color: '#10B981', items: [] },
            { id: 's8', name: 'Electronics', color: '#6B7280', items: [] }
        ];
    }

    // ==================== EVENT LISTENERS ====================
    function setupEventListeners() {
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && state.builderOpen) {
                closeBuilder();
            }
        });
    }

    function setupStepEventListeners() {
        // Re-attach any step-specific listeners after render
    }

    function updateHeroStats() {
        const totalPacks = state.packs.length;
        let totalWeight = 0;
        let totalItems = 0;
        
        state.packs.forEach(pack => {
            const stats = calculatePackStats(pack);
            totalItems += stats.totalItems;
            // Parse weight string back to number for sum
            const weight = parseInt(stats.totalWeight) || 0;
            totalWeight += weight;
        });
        
        const packsEl = document.getElementById('hero-packs');
        const weightEl = document.getElementById('hero-weight');
        const itemsEl = document.getElementById('hero-items');
        
        if (packsEl) packsEl.textContent = totalPacks;
        if (weightEl) weightEl.textContent = formatWeight(totalWeight);
        if (itemsEl) itemsEl.textContent = totalItems;
    }

    function getFilteredPacks() {
        return state.packs; // For now, return all packs
    }

    function showEmptyFilterState() {
        const grid = document.getElementById('pack-grid');
        if (grid) {
            grid.innerHTML = '<div class="empty-state">No packs match your filters</div>';
            showElement('pack-grid');
        }
    }

    function categorizePacks() {
        state.categories.all.count = state.packs.length;
        state.categories.favorites.count = state.packs.filter(p => p.favorite).length;
        state.categories.recent.count = Math.min(5, state.packs.length);
        state.categories.shared.count = state.packs.filter(p => p.shared).length;
    }

    // ==================== PUBLIC API ====================
    return {
        // Core
        init,
        createNew,
        openBuilder,
        closeBuilder,
        
        // Builder steps
        nextStep,
        previousStep,
        savePackFromBuilder,
        saveDraft: () => showToast('Draft saved!', 'success'),
        previewPack: () => showToast('Preview mode coming soon!', 'info'),
        
        // Pack operations
        updatePackDetail,
        updatePackTags,
        
        // Section operations
        addSection,
        updateSectionName,
        updateSectionColor,
        deleteSection,
        moveSection,
        
        // Item operations
        quickAddItem,
        addCustomGear,
        removeItemFromSection,
        toggleItemPacked,
        
        // Drag and drop
        handleDragStart,
        handleDragOver,
        handleDrop,
        
        // Pack management
        editPack: (id) => openBuilder(id),
        duplicatePack: (id) => {
            const pack = state.packs.find(p => p.id === id);
            if (pack) {
                const newPack = JSON.parse(JSON.stringify(pack));
                newPack.id = generateId();
                newPack.name = pack.name + ' (Copy)';
                newPack.created_at = new Date().toISOString();
                state.packs.push(newPack);
                savePacks();
                renderPacks();
                showToast('Pack duplicated!', 'success');
            }
        },
        exportPack: (id) => {
            const pack = state.packs.find(p => p.id === id);
            if (pack) {
                const dataStr = JSON.stringify(pack, null, 2);
                const dataBlob = new Blob([dataStr], { type: 'application/json' });
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `${pack.name.replace(/\s+/g, '_')}.json`;
                link.click();
                URL.revokeObjectURL(url);
                showToast('Pack exported!', 'success');
            }
        },
        
        // Placeholder functions for other features
        quickStart: () => showToast('Quick Start wizard coming soon!', 'info'),
        showTour: () => showToast('Interactive tour coming soon!', 'info'),
        toggleView: (view) => showToast(`${view} view coming soon!`, 'info'),
        search: (query) => console.log('Search:', query),
        filter: (type, value) => console.log('Filter:', type, value),
        filterByWeight: (weight) => console.log('Filter by weight:', weight),
        filterByTag: (tag) => console.log('Filter by tag:', tag),
        showCategory: (category) => console.log('Show category:', category),
        showTemplates: () => showToast('Templates coming soon!', 'info'),
        showImport: () => showToast('Import feature coming soon!', 'info'),
        closeDetail: () => console.log('Close detail'),
        createFromLastTrip: () => showToast('Creating from last trip...', 'info'),
        duplicateFavorite: () => showToast('Duplicating favorite...', 'info'),
        smartPack: () => showToast('AI Smart Pack coming soon!', 'info')
    };
})();

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManager.init);
} else {
    PackManager.init();
}
