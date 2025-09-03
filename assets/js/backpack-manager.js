/**
 * BackpackManager - Advanced backpack management system
 */
const BackpackManager = (function() {
    'use strict';

    // State management
    let state = {
        currentBackpackId: null,
        backpacks: [],
        currentBackpack: null,
        gearInventory: [],
        templates: [],
        isDirty: false,
        autoSaveTimer: null
    };

    // Constants
    const API_BASE = '/BTT/api/backpacks';
    const GEAR_API = '/BTT/api/gear';
    const TEMPLATES_API = '/BTT/api/templates/backpack';
    const AUTOSAVE_DELAY = 3000; // 3 seconds

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', init);

    /**
     * Initialize the backpack manager
     */
    async function init() {
        await loadBackpacks();
        await loadGearInventory();
        await loadTemplates();
        setupEventListeners();
        setupKeyboardShortcuts();
        restoreLastBackpack();
    }

    /**
     * Setup global event listeners
     */
    function setupEventListeners() {
        // Autosave on changes
        document.addEventListener('input', handleAutosave);
        document.addEventListener('change', handleAutosave);

        // Drag and drop for sections and items
        setupDragAndDrop();

        // Offline support
        window.addEventListener('online', syncOfflineChanges);
        window.addEventListener('offline', showOfflineNotification);
    }

    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveCurrentBackpack();
            }
            // Ctrl/Cmd + N for new backpack
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                toggleCreatePanel();
            }
            // Escape to close panels
            if (e.key === 'Escape') {
                closeAllPanels();
            }
        });
    }

    /**
     * Load all user backpacks
     */
    async function loadBackpacks() {
        try {
            const response = await fetch(API_BASE);
            if (!response.ok) throw new Error('Failed to load backpacks');
            
            state.backpacks = await response.json();
            renderBackpacksList();
        } catch (error) {
            console.error('Error loading backpacks:', error);
            showNotification('Failed to load backpacks', 'error');
        }
    }

    /**
     * Load gear inventory
     */
    async function loadGearInventory() {
        try {
            const response = await fetch(GEAR_API);
            if (!response.ok) throw new Error('Failed to load gear');
            
            state.gearInventory = await response.json();
            renderGearInventory();
        } catch (error) {
            console.error('Error loading gear:', error);
            // Load default gear if API fails
            loadDefaultGear();
        }
    }

    /**
     * Load backpack templates
     */
    async function loadTemplates() {
        try {
            const response = await fetch(TEMPLATES_API);
            if (!response.ok) throw new Error('Failed to load templates');
            
            state.templates = await response.json();
        } catch (error) {
            console.error('Error loading templates:', error);
            // Load default templates
            loadDefaultTemplates();
        }
    }

    /**
     * Render backpacks list
     */
    function renderBackpacksList() {
        const container = document.getElementById('backpacks-list');
        if (!container) return;

        if (state.backpacks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No backpacks yet</p>
                    <button class="btn btn-primary" onclick="BackpackManager.toggleCreatePanel()">
                        Create Your First Backpack
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = state.backpacks.map(backpack => `
            <div class="backpack-item ${backpack.id === state.currentBackpackId ? 'active' : ''}" 
                 data-id="${backpack.id}"
                 onclick="BackpackManager.selectBackpack('${backpack.id}')"
                 role="button"
                 tabindex="0"
                 aria-label="Select ${escapeHtml(backpack.name)}">
                <div class="backpack-info">
                    <h4>${escapeHtml(backpack.name)}</h4>
                    <div class="backpack-meta">
                        <span>📦 ${backpack.capacity_l || 65}L</span>
                        <span>⚖️ ${formatWeight(backpack.base_weight_g || 0)}</span>
                        <span>🎯 ${backpack.items_count || 0} items</span>
                    </div>
                </div>
                <div class="backpack-actions" onclick="event.stopPropagation()">
                    <button class="btn-icon-only" onclick="BackpackManager.duplicateBackpack('${backpack.id}')" 
                            aria-label="Duplicate backpack">📋</button>
                    <button class="btn-icon-only" onclick="BackpackManager.deleteBackpack('${backpack.id}')" 
                            aria-label="Delete backpack">🗑️</button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Select and load a backpack
     */
    async function selectBackpack(backpackId) {
        if (state.isDirty) {
            const confirmed = await confirmUnsavedChanges();
            if (!confirmed) return;
        }

        try {
            const response = await fetch(`${API_BASE}/${backpackId}`);
            if (!response.ok) throw new Error('Failed to load backpack');
            
            state.currentBackpack = await response.json();
            state.currentBackpackId = backpackId;
            
            // Store in localStorage for persistence
            localStorage.setItem('lastBackpackId', backpackId);
            
            renderPackBuilder();
            updateBackpacksList();
            showNotification('Backpack loaded', 'success');
        } catch (error) {
            console.error('Error loading backpack:', error);
            showNotification('Failed to load backpack', 'error');
        }
    }

    /**
     * Render the pack builder interface
     */
    function renderPackBuilder() {
        const emptyState = document.getElementById('pack-builder-empty');
        const packBuilder = document.getElementById('pack-builder');
        
        if (!state.currentBackpack) {
            emptyState.hidden = false;
            packBuilder.hidden = true;
            return;
        }

        emptyState.hidden = true;
        packBuilder.hidden = false;

        // Update header
        document.getElementById('current-backpack-name').textContent = state.currentBackpack.name;
        
        // Update progress
        updatePackingProgress();
        
        // Update weight summary
        updateWeightSummary();
        
        // Render sections
        renderSections();
        
        // Update suggestions
        updateSmartSuggestions();
    }

    /**
     * Render backpack sections
     */
    function renderSections() {
        const container = document.getElementById('sections-container');
        if (!container) return;

        const sections = state.currentBackpack.sections || getDefaultSections();
        
        container.innerHTML = sections.map((section, index) => `
            <div class="section" data-section-id="${section.id}" draggable="true">
                <div class="section-header" onclick="BackpackManager.toggleSection('${section.id}')">
                    <div class="section-title">
                        <span class="section-color" style="background: ${section.color}"></span>
                        <h3>${escapeHtml(section.name)}</h3>
                    </div>
                    <div class="section-stats">
                        <span>${section.items?.length || 0} items</span>
                        <span>${formatWeight(calculateSectionWeight(section))}</span>
                    </div>
                    <div class="section-actions" onclick="event.stopPropagation()">
                        <button class="btn-icon-only" onclick="BackpackManager.editSection('${section.id}')" 
                                aria-label="Edit section">✏️</button>
                        <button class="btn-icon-only" onclick="BackpackManager.deleteSection('${section.id}')" 
                                aria-label="Delete section">🗑️</button>
                    </div>
                </div>
                <div class="section-content" id="section-content-${section.id}">
                    ${renderSectionItems(section)}
                    <button class="btn btn-sm btn-secondary" onclick="BackpackManager.addItemToSection('${section.id}')">
                        ➕ Add Item
                    </button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Render items in a section
     */
    function renderSectionItems(section) {
        if (!section.items || section.items.length === 0) {
            return '<p class="empty-state">No items in this section</p>';
        }

        return `
            <div class="items-list">
                ${section.items.map(item => `
                    <div class="item-row ${item.packed ? 'packed' : ''}" 
                         data-item-id="${item.id}"
                         draggable="true">
                        <input type="checkbox" 
                               class="item-checkbox"
                               ${item.packed ? 'checked' : ''}
                               onchange="BackpackManager.toggleItemPacked('${section.id}', '${item.id}')"
                               aria-label="Mark ${escapeHtml(item.name)} as packed">
                        <div class="item-info">
                            <span class="item-name">${escapeHtml(item.name)}</span>
                            <span class="item-meta">
                                ${item.brand ? escapeHtml(item.brand) + ' • ' : ''}
                                ${item.category || 'Other'}
                                ${item.worn ? ' • Worn' : ''}
                                ${item.consumable ? ' • Consumable' : ''}
                            </span>
                        </div>
                        <span class="item-weight">${formatWeight(item.weight_g || 0)}</span>
                        <div class="item-actions">
                            <button class="btn-icon-only" onclick="BackpackManager.editItem('${section.id}', '${item.id}')" 
                                    aria-label="Edit item">✏️</button>
                            <button class="btn-icon-only" onclick="BackpackManager.removeItem('${section.id}', '${item.id}')" 
                                    aria-label="Remove item">🗑️</button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Render gear inventory
     */
    function renderGearInventory() {
        const container = document.getElementById('gear-list');
        if (!container) return;

        if (state.gearInventory.length === 0) {
            container.innerHTML = '<p class="empty-state">No gear in inventory</p>';
            return;
        }

        container.innerHTML = state.gearInventory.map(gear => `
            <div class="gear-item" data-gear-id="${gear.id}">
                <div class="gear-item-info">
                    <div class="gear-item-name">${escapeHtml(gear.name)}</div>
                    <div class="gear-item-meta">
                        ${gear.brand ? escapeHtml(gear.brand) + ' • ' : ''}
                        ${gear.category} • ${formatWeight(gear.weight_g)}
                    </div>
                </div>
                <div class="gear-item-actions">
                    <button class="btn-icon-only" onclick="BackpackManager.addGearToBackpack('${gear.id}')" 
                            aria-label="Add to backpack">➕</button>
                    <button class="btn-icon-only" onclick="BackpackManager.editGear('${gear.id}')" 
                            aria-label="Edit gear">✏️</button>
                    <button class="btn-icon-only" onclick="BackpackManager.deleteGear('${gear.id}')" 
                            aria-label="Delete gear">🗑️</button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Toggle create panel
     */
    function toggleCreatePanel() {
        const panel = document.getElementById('create-panel');
        const button = event.target.closest('button');
        
        panel.hidden = !panel.hidden;
        button.setAttribute('aria-expanded', !panel.hidden);
        
        if (!panel.hidden) {
            document.getElementById('new-backpack-name').focus();
        }
    }

    /**
     * Show templates panel
     */
    function showTemplates() {
        const panel = document.getElementById('templates-panel');
        panel.hidden = false;
        
        const container = document.getElementById('templates-list');
        container.innerHTML = state.templates.map(template => `
            <div class="template-card" onclick="BackpackManager.createFromTemplate('${template.id}')">
                <div class="template-name">${escapeHtml(template.name)}</div>
                <div class="template-description">${escapeHtml(template.description)}</div>
                <div class="template-stats">
                    <span class="template-stat">📦 ${template.capacity_l}L</span>
                    <span class="template-stat">📍 ${template.sections?.length || 0} sections</span>
                    <span class="template-stat">🎯 ${template.total_items || 0} items</span>
                </div>
            </div>
        `).join('');
    }

    /**
     * Create backpack from form
     */
    async function createBackpack(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const backpackData = {
            name: formData.get('name'),
            capacity_l: parseInt(formData.get('capacity_l')),
            weight_empty_g: parseInt(formData.get('weight_empty_g')),
            type: formData.get('type'),
            sections: getDefaultSections(),
            created_at: new Date().toISOString()
        };

        try {
            const response = await fetch(API_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(backpackData)
            });

            if (!response.ok) throw new Error('Failed to create backpack');
            
            const newBackpack = await response.json();
            state.backpacks.push(newBackpack);
            
            renderBackpacksList();
            selectBackpack(newBackpack.id);
            toggleCreatePanel();
            form.reset();
            
            showNotification('Backpack created successfully!', 'success');
            triggerGamification('backpack_created');
        } catch (error) {
            console.error('Error creating backpack:', error);
            showNotification('Failed to create backpack', 'error');
        }
    }

    /**
     * Create backpack from template
     */
    async function createFromTemplate(templateId) {
        const template = state.templates.find(t => t.id === templateId);
        if (!template) return;

        const name = prompt('Name your new backpack:', template.name);
        if (!name) return;

        try {
            const backpackData = {
                ...template,
                id: generateId(),
                name: name,
                created_at: new Date().toISOString(),
                template_id: templateId
            };

            const response = await fetch(API_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(backpackData)
            });

            if (!response.ok) throw new Error('Failed to create from template');
            
            const newBackpack = await response.json();
            state.backpacks.push(newBackpack);
            
            renderBackpacksList();
            selectBackpack(newBackpack.id);
            
            showNotification('Backpack created from template!', 'success');
        } catch (error) {
            console.error('Error creating from template:', error);
            showNotification('Failed to create from template', 'error');
        }
    }

    /**
     * Duplicate a backpack
     */
    async function duplicateBackpack(backpackId) {
        const backpack = backpackId ? 
            state.backpacks.find(b => b.id === backpackId) : 
            state.currentBackpack;
            
        if (!backpack) return;

        const name = prompt('Name for the duplicate:', backpack.name + ' (Copy)');
        if (!name) return;

        try {
            const duplicateData = {
                ...backpack,
                id: generateId(),
                name: name,
                created_at: new Date().toISOString()
            };

            const response = await fetch(API_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(duplicateData)
            });

            if (!response.ok) throw new Error('Failed to duplicate');
            
            const newBackpack = await response.json();
            state.backpacks.push(newBackpack);
            
            renderBackpacksList();
            showNotification('Backpack duplicated!', 'success');
        } catch (error) {
            console.error('Error duplicating:', error);
            showNotification('Failed to duplicate backpack', 'error');
        }
    }

    /**
     * Delete a backpack
     */
    async function deleteBackpack(backpackId) {
        if (!confirm('Are you sure you want to delete this backpack?')) return;

        try {
            const response = await fetch(`${API_BASE}/${backpackId}`, {
                method: 'DELETE'
            });

            if (!response.ok) throw new Error('Failed to delete');
            
            state.backpacks = state.backpacks.filter(b => b.id !== backpackId);
            
            if (state.currentBackpackId === backpackId) {
                state.currentBackpack = null;
                state.currentBackpackId = null;
                renderPackBuilder();
            }
            
            renderBackpacksList();
            showNotification('Backpack deleted', 'success');
        } catch (error) {
            console.error('Error deleting:', error);
            showNotification('Failed to delete backpack', 'error');
        }
    }

    /**
     * Export current backpack
     */
    function exportBackpack() {
        if (!state.currentBackpack) return;

        const dataStr = JSON.stringify(state.currentBackpack, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `${state.currentBackpack.name.replace(/\s+/g, '_')}_${Date.now()}.json`;
        link.click();
        
        URL.revokeObjectURL(url);
        showNotification('Backpack exported!', 'success');
    }

    /**
     * Import backpack from file
     */
    async function importBackpack(event) {
        const file = event.target.files[0];
        if (!file) return;

        try {
            const text = await file.text();
            const backpackData = JSON.parse(text);
            
            // Validate structure
            if (!backpackData.name || !backpackData.sections) {
                throw new Error('Invalid backpack format');
            }

            // Generate new ID and timestamp
            backpackData.id = generateId();
            backpackData.created_at = new Date().toISOString();
            backpackData.name = backpackData.name + ' (Imported)';

            const response = await fetch(API_BASE, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(backpackData)
            });

            if (!response.ok) throw new Error('Failed to import');
            
            const newBackpack = await response.json();
            state.backpacks.push(newBackpack);
            
            renderBackpacksList();
            selectBackpack(newBackpack.id);
            
            showNotification('Backpack imported successfully!', 'success');
        } catch (error) {
            console.error('Error importing:', error);
            showNotification('Failed to import backpack', 'error');
        }
        
        // Reset file input
        event.target.value = '';
    }

    /**
     * Toggle item packed status
     */
    function toggleItemPacked(sectionId, itemId) {
        const section = state.currentBackpack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const item = section.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.packed = !item.packed;
        item.packed_at = item.packed ? new Date().toISOString() : null;
        
        updatePackingProgress();
        updateWeightSummary();
        markDirty();
    }

    /**
     * Pack all items
     */
    function packAll() {
        if (!state.currentBackpack) return;
        
        state.currentBackpack.sections.forEach(section => {
            if (section.items) {
                section.items.forEach(item => {
                    item.packed = true;
                    item.packed_at = new Date().toISOString();
                });
            }
        });
        
        renderSections();
        updatePackingProgress();
        updateWeightSummary();
        markDirty();
        showNotification('All items packed!', 'success');
    }

    /**
     * Unpack all items
     */
    function unpackAll() {
        if (!state.currentBackpack) return;
        
        state.currentBackpack.sections.forEach(section => {
            if (section.items) {
                section.items.forEach(item => {
                    item.packed = false;
                    item.packed_at = null;
                });
            }
        });
        
        renderSections();
        updatePackingProgress();
        updateWeightSummary();
        markDirty();
        showNotification('All items unpacked', 'success');
    }

    /**
     * Update packing progress
     */
    function updatePackingProgress() {
        if (!state.currentBackpack) return;
        
        let totalItems = 0;
        let packedItems = 0;
        
        state.currentBackpack.sections.forEach(section => {
            if (section.items) {
                totalItems += section.items.length;
                packedItems += section.items.filter(i => i.packed).length;
            }
        });
        
        const percentage = totalItems > 0 ? Math.round((packedItems / totalItems) * 100) : 0;
        
        document.getElementById('packing-percentage').textContent = `${percentage}%`;
        document.getElementById('packing-bar').value = percentage;
        
        // Trigger gamification on 100%
        if (percentage === 100 && totalItems > 0) {
            triggerGamification('pack_complete');
        }
    }

    /**
     * Update weight summary
     */
    function updateWeightSummary() {
        if (!state.currentBackpack) return;
        
        let baseWeight = state.currentBackpack.weight_empty_g || 0;
        let wornWeight = 0;
        let consumableWeight = 0;
        
        state.currentBackpack.sections.forEach(section => {
            if (section.items) {
                section.items.forEach(item => {
                    const weight = item.weight_g || 0;
                    if (item.worn) {
                        wornWeight += weight;
                    } else if (item.consumable) {
                        consumableWeight += weight;
                    } else {
                        baseWeight += weight;
                    }
                });
            }
        });
        
        const totalWeight = baseWeight + consumableWeight;
        
        document.getElementById('base-weight').textContent = formatWeight(baseWeight);
        document.getElementById('worn-weight').textContent = formatWeight(wornWeight);
        document.getElementById('consumable-weight').textContent = formatWeight(consumableWeight);
        document.getElementById('total-weight').textContent = formatWeight(totalWeight);
    }

    /**
     * Add gear item to inventory
     */
    async function addGearItem(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const gearData = {
            id: generateId(),
            name: formData.get('name'),
            weight_g: parseInt(formData.get('weight_g')),
            category: formData.get('category'),
            brand: formData.get('brand'),
            created_at: new Date().toISOString()
        };

        try {
            // In production, this would save to API
            state.gearInventory.push(gearData);
            renderGearInventory();
            
            form.reset();
            toggleAddGear();
            showNotification('Gear added to inventory!', 'success');
        } catch (error) {
            console.error('Error adding gear:', error);
            showNotification('Failed to add gear', 'error');
        }
    }

    /**
     * Add gear to current backpack
     */
    function addGearToBackpack(gearId) {
        if (!state.currentBackpack) {
            showNotification('Please select a backpack first', 'warning');
            return;
        }

        const gear = state.gearInventory.find(g => g.id === gearId);
        if (!gear) return;

        // Show section selector
        const sections = state.currentBackpack.sections;
        const sectionNames = sections.map(s => s.name).join('\n');
        const sectionIndex = prompt(`Add to which section?\n${sectionNames}\n\nEnter section number (1-${sections.length}):`);
        
        if (!sectionIndex) return;
        
        const section = sections[parseInt(sectionIndex) - 1];
        if (!section) {
            showNotification('Invalid section', 'error');
            return;
        }

        // Add gear as item to section
        if (!section.items) section.items = [];
        
        const item = {
            ...gear,
            id: generateId(),
            gear_id: gear.id,
            packed: false,
            quantity: 1,
            added_at: new Date().toISOString()
        };
        
        section.items.push(item);
        
        renderSections();
        updateWeightSummary();
        markDirty();
        showNotification(`${gear.name} added to ${section.name}`, 'success');
    }

    /**
     * Search gear inventory
     */
    function searchGear(query) {
        const filtered = state.gearInventory.filter(gear => 
            gear.name.toLowerCase().includes(query.toLowerCase()) ||
            gear.brand?.toLowerCase().includes(query.toLowerCase()) ||
            gear.category.toLowerCase().includes(query.toLowerCase())
        );
        
        renderFilteredGear(filtered);
    }

    /**
     * Filter gear by category
     */
    function filterGear(category) {
        const filtered = category ? 
            state.gearInventory.filter(gear => gear.category === category) :
            state.gearInventory;
            
        renderFilteredGear(filtered);
    }

    /**
     * Render filtered gear
     */
    function renderFilteredGear(gearList) {
        const container = document.getElementById('gear-list');
        if (!container) return;

        if (gearList.length === 0) {
            container.innerHTML = '<p class="empty-state">No matching gear found</p>';
            return;
        }

        container.innerHTML = gearList.map(gear => `
            <div class="gear-item" data-gear-id="${gear.id}">
                <div class="gear-item-info">
                    <div class="gear-item-name">${escapeHtml(gear.name)}</div>
                    <div class="gear-item-meta">
                        ${gear.brand ? escapeHtml(gear.brand) + ' • ' : ''}
                        ${gear.category} • ${formatWeight(gear.weight_g)}
                    </div>
                </div>
                <div class="gear-item-actions">
                    <button class="btn-icon-only" onclick="BackpackManager.addGearToBackpack('${gear.id}')" 
                            aria-label="Add to backpack">➕</button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Toggle add gear form
     */
    function toggleAddGear() {
        const form = document.getElementById('add-gear-form');
        const button = event.target.closest('button');
        
        form.hidden = !form.hidden;
        button?.setAttribute('aria-expanded', !form.hidden);
        
        if (!form.hidden) {
            document.getElementById('gear-name').focus();
        }
    }

    /**
     * Setup drag and drop
     */
    function setupDragAndDrop() {
        let draggedElement = null;
        
        // Section drag and drop
        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('section')) {
                draggedElement = e.target;
                e.target.style.opacity = '0.5';
            } else if (e.target.classList.contains('item-row')) {
                draggedElement = e.target;
                e.target.style.opacity = '0.5';
            }
        });
        
        document.addEventListener('dragend', (e) => {
            if (e.target.classList.contains('section') || e.target.classList.contains('item-row')) {
                e.target.style.opacity = '';
            }
        });
        
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });
        
        document.addEventListener('drop', (e) => {
            e.preventDefault();
            
            if (!draggedElement) return;
            
            // Handle section reordering
            if (draggedElement.classList.contains('section')) {
                const dropTarget = e.target.closest('.section');
                if (dropTarget && dropTarget !== draggedElement) {
                    const container = document.getElementById('sections-container');
                    const sections = Array.from(container.children);
                    const draggedIndex = sections.indexOf(draggedElement);
                    const targetIndex = sections.indexOf(dropTarget);
                    
                    if (draggedIndex < targetIndex) {
                        dropTarget.after(draggedElement);
                    } else {
                        dropTarget.before(draggedElement);
                    }
                    
                    // Update data model
                    reorderSections(draggedIndex, targetIndex);
                }
            }
            
            // Handle item moving between sections
            if (draggedElement.classList.contains('item-row')) {
                const dropSection = e.target.closest('.section');
                if (dropSection) {
                    const itemsList = dropSection.querySelector('.items-list');
                    if (itemsList && !itemsList.contains(draggedElement)) {
                        itemsList.appendChild(draggedElement);
                        
                        // Update data model
                        moveItemToSection(
                            draggedElement.dataset.itemId,
                            dropSection.dataset.sectionId
                        );
                    }
                }
            }
            
            draggedElement = null;
        });
    }

    /**
     * Reorder sections in data model
     */
    function reorderSections(fromIndex, toIndex) {
        if (!state.currentBackpack) return;
        
        const sections = state.currentBackpack.sections;
        const [moved] = sections.splice(fromIndex, 1);
        sections.splice(toIndex, 0, moved);
        
        markDirty();
    }

    /**
     * Move item to different section
     */
    function moveItemToSection(itemId, targetSectionId) {
        if (!state.currentBackpack) return;
        
        let item = null;
        let sourceSection = null;
        
        // Find and remove item from source section
        state.currentBackpack.sections.forEach(section => {
            const index = section.items?.findIndex(i => i.id === itemId);
            if (index !== undefined && index >= 0) {
                sourceSection = section;
                item = section.items.splice(index, 1)[0];
            }
        });
        
        // Add to target section
        if (item) {
            const targetSection = state.currentBackpack.sections.find(s => s.id === targetSectionId);
            if (targetSection) {
                if (!targetSection.items) targetSection.items = [];
                targetSection.items.push(item);
                
                markDirty();
                updateWeightSummary();
                showNotification(`Moved ${item.name} to ${targetSection.name}`, 'success');
            }
        }
    }

    /**
     * Update smart suggestions
     */
    function updateSmartSuggestions() {
        const container = document.getElementById('packing-suggestions');
        if (!container || !state.currentBackpack) return;
        
        const suggestions = generateSmartSuggestions();
        
        container.innerHTML = `
            <ul class="suggestions-list">
                ${suggestions.map(s => `<li>${s}</li>`).join('')}
            </ul>
        `;
    }

    /**
     * Generate smart packing suggestions
     */
    function generateSmartSuggestions() {
        const suggestions = [];
        
        if (!state.currentBackpack) return suggestions;
        
        // Weight suggestions
        const totalWeight = calculateTotalWeight();
        if (totalWeight > 10000) {
            suggestions.push('⚖️ Your pack is over 10kg. Consider reducing weight for comfort.');
        }
        
        // Missing essentials
        const hasWater = checkForCategory('water');
        const hasFirstAid = checkForCategory('first_aid');
        const hasShelter = checkForCategory('shelter');
        
        if (!hasWater) suggestions.push('💧 Don\'t forget water and purification!');
        if (!hasFirstAid) suggestions.push('🏥 Add a first aid kit for safety');
        if (!hasShelter) suggestions.push('⛺ Consider shelter based on your trip');
        
        // Type-specific suggestions
        if (state.currentBackpack.type === 'winter') {
            suggestions.push('❄️ Pack extra insulation and emergency gear');
        }
        
        if (state.currentBackpack.type === 'ultralight') {
            suggestions.push('🪶 Focus on multi-use items to save weight');
        }
        
        return suggestions.length > 0 ? suggestions : ['✅ Your pack looks well organized!'];
    }

    /**
     * Mark data as dirty (needs saving)
     */
    function markDirty() {
        state.isDirty = true;
        scheduleAutosave();
    }

    /**
     * Handle autosave
     */
    function handleAutosave() {
        if (state.currentBackpack) {
            markDirty();
        }
    }

    /**
     * Schedule autosave
     */
    function scheduleAutosave() {
        clearTimeout(state.autoSaveTimer);
        state.autoSaveTimer = setTimeout(saveCurrentBackpack, AUTOSAVE_DELAY);
    }

    /**
     * Save current backpack
     */
    async function saveCurrentBackpack() {
        if (!state.currentBackpack || !state.isDirty) return;

        try {
            const response = await fetch(`${API_BASE}/${state.currentBackpack.id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(state.currentBackpack)
            });

            if (!response.ok) throw new Error('Failed to save');
            
            state.isDirty = false;
            showNotification('Changes saved', 'success', 1000);
        } catch (error) {
            console.error('Error saving:', error);
            showNotification('Failed to save changes', 'error');
        }
    }

    /**
     * Confirm unsaved changes
     */
    async function confirmUnsavedChanges() {
        return confirm('You have unsaved changes. Do you want to continue?');
    }

    /**
     * Restore last backpack from localStorage
     */
    function restoreLastBackpack() {
        const lastId = localStorage.getItem('lastBackpackId');
        if (lastId && state.backpacks.find(b => b.id === lastId)) {
            selectBackpack(lastId);
        }
    }

    /**
     * Close all panels
     */
    function closeAllPanels() {
        document.querySelectorAll('.collapsible-panel').forEach(panel => {
            panel.hidden = true;
        });
    }

    /**
     * Utility functions
     */
    function generateId() {
        return 'bp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatWeight(grams) {
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }

    function calculateSectionWeight(section) {
        if (!section.items) return 0;
        return section.items.reduce((sum, item) => sum + (item.weight_g || 0), 0);
    }

    function calculateTotalWeight() {
        if (!state.currentBackpack) return 0;
        
        let total = state.currentBackpack.weight_empty_g || 0;
        state.currentBackpack.sections.forEach(section => {
            total += calculateSectionWeight(section);
        });
        return total;
    }

    function checkForCategory(category) {
        if (!state.currentBackpack) return false;
        
        return state.currentBackpack.sections.some(section =>
            section.items?.some(item => item.category === category)
        );
    }

    function getDefaultSections() {
        return [
            { id: 's1', name: 'Shelter & Sleep', color: '#8B4513', items: [] },
            { id: 's2', name: 'Clothing & Footwear', color: '#4A5568', items: [] },
            { id: 's3', name: 'Cooking & Food', color: '#D97706', items: [] },
            { id: 's4', name: 'Water & Hydration', color: '#3B82F6', items: [] },
            { id: 's5', name: 'Navigation & Safety', color: '#EF4444', items: [] },
            { id: 's6', name: 'Personal & Hygiene', color: '#8B5CF6', items: [] },
            { id: 's7', name: 'Tools & Repair', color: '#10B981', items: [] },
            { id: 's8', name: 'Electronics', color: '#6B7280', items: [] }
        ];
    }

    function loadDefaultGear() {
        state.gearInventory = [
            { id: 'g1', name: 'Tent', weight_g: 1500, category: 'shelter', brand: 'REI' },
            { id: 'g2', name: 'Sleeping Bag', weight_g: 800, category: 'sleep_system', brand: 'Mountain Hardware' },
            { id: 'g3', name: 'Backpack Rain Cover', weight_g: 150, category: 'other', brand: 'Osprey' },
            { id: 'g4', name: 'Water Filter', weight_g: 75, category: 'water', brand: 'Sawyer' },
            { id: 'g5', name: 'First Aid Kit', weight_g: 200, category: 'first_aid', brand: 'Adventure Medical' }
        ];
    }

    function loadDefaultTemplates() {
        state.templates = [
            {
                id: 't1',
                name: 'Day Hike Essentials',
                description: 'Light pack for single-day adventures',
                capacity_l: 30,
                sections: getDefaultSections().slice(0, 4),
                total_items: 15
            },
            {
                id: 't2',
                name: 'Weekend Adventure',
                description: 'Complete setup for 2-3 day trips',
                capacity_l: 50,
                sections: getDefaultSections().slice(0, 6),
                total_items: 35
            },
            {
                id: 't3',
                name: 'Thru-Hiker Setup',
                description: 'Ultralight configuration for long-distance hiking',
                capacity_l: 65,
                sections: getDefaultSections(),
                total_items: 50
            }
        ];
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info', duration = 3000) {
        // Update status region for screen readers
        const statusRegion = document.getElementById('status-region');
        if (statusRegion) {
            statusRegion.textContent = message;
        }

        // Visual notification (you can enhance this with a toast library)
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    /**
     * Trigger gamification
     */
    function triggerGamification(action) {
        // Integrate with BTT gamification system
        if (window.BTTGamification) {
            window.BTTGamification.trigger(action);
        }
    }

    /**
     * Sync offline changes when back online
     */
    function syncOfflineChanges() {
        if (state.isDirty) {
            saveCurrentBackpack();
        }
        showNotification('Back online - syncing changes', 'info');
    }

    /**
     * Show offline notification
     */
    function showOfflineNotification() {
        showNotification('You are offline - changes will sync when connection returns', 'warning');
    }

    /**
     * Update backpacks list highlighting
     */
    function updateBackpacksList() {
        document.querySelectorAll('.backpack-item').forEach(item => {
            if (item.dataset.id === state.currentBackpackId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    /**
     * Edit backpack name inline
     */
    function editBackpackName() {
        if (!state.currentBackpack) return;
        
        const newName = prompt('Edit backpack name:', state.currentBackpack.name);
        if (newName && newName !== state.currentBackpack.name) {
            state.currentBackpack.name = newName;
            document.getElementById('current-backpack-name').textContent = newName;
            markDirty();
            
            // Update in list
            const backpack = state.backpacks.find(b => b.id === state.currentBackpackId);
            if (backpack) {
                backpack.name = newName;
                renderBackpacksList();
            }
        }
    }

    /**
     * Toggle section visibility
     */
    function toggleSection(sectionId) {
        const content = document.getElementById(`section-content-${sectionId}`);
        if (content) {
            content.hidden = !content.hidden;
        }
    }

    /**
     * Add section to backpack
     */
    function showAddSection() {
        const name = prompt('Section name:');
        if (!name) return;
        
        const colors = ['#8B4513', '#4A5568', '#D97706', '#3B82F6', '#EF4444', '#8B5CF6', '#10B981', '#6B7280'];
        const color = colors[Math.floor(Math.random() * colors.length)];
        
        const section = {
            id: generateId(),
            name: name,
            color: color,
            items: [],
            created_at: new Date().toISOString()
        };
        
        if (!state.currentBackpack.sections) {
            state.currentBackpack.sections = [];
        }
        
        state.currentBackpack.sections.push(section);
        renderSections();
        markDirty();
        showNotification('Section added', 'success');
    }

    // Public API
    return {
        init,
        toggleCreatePanel,
        showTemplates,
        createBackpack,
        createFromTemplate,
        selectBackpack,
        duplicateBackpack,
        deleteBackpack,
        exportBackpack,
        importBackpack,
        toggleItemPacked,
        packAll,
        unpackAll,
        addGearItem,
        addGearToBackpack,
        searchGear,
        filterGear,
        toggleAddGear,
        editBackpackName,
        toggleSection,
        showAddSection,
        addItemToSection: (sectionId) => {
            const gearName = prompt('Add item (enter name or select from gear):');
            if (!gearName) return;
            
            const section = state.currentBackpack.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            if (!section.items) section.items = [];
            
            const item = {
                id: generateId(),
                name: gearName,
                weight_g: parseInt(prompt('Weight in grams:') || '0'),
                category: 'other',
                packed: false,
                added_at: new Date().toISOString()
            };
            
            section.items.push(item);
            renderSections();
            updateWeightSummary();
            markDirty();
        },
        editSection: (sectionId) => {
            const section = state.currentBackpack.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            const newName = prompt('Edit section name:', section.name);
            if (newName && newName !== section.name) {
                section.name = newName;
                renderSections();
                markDirty();
            }
        },
        deleteSection: (sectionId) => {
            if (!confirm('Delete this section and all its items?')) return;
            
            state.currentBackpack.sections = state.currentBackpack.sections.filter(s => s.id !== sectionId);
            renderSections();
            updateWeightSummary();
            markDirty();
            showNotification('Section deleted', 'success');
        },
        editItem: (sectionId, itemId) => {
            const section = state.currentBackpack.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            const item = section.items.find(i => i.id === itemId);
            if (!item) return;
            
            const newName = prompt('Edit item name:', item.name);
            if (newName && newName !== item.name) {
                item.name = newName;
                renderSections();
                markDirty();
            }
        },
        removeItem: (sectionId, itemId) => {
            if (!confirm('Remove this item?')) return;
            
            const section = state.currentBackpack.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            section.items = section.items.filter(i => i.id !== itemId);
            renderSections();
            updateWeightSummary();
            markDirty();
            showNotification('Item removed', 'success');
        },
        editGear: (gearId) => {
            const gear = state.gearInventory.find(g => g.id === gearId);
            if (!gear) return;
            
            const newName = prompt('Edit gear name:', gear.name);
            if (newName && newName !== gear.name) {
                gear.name = newName;
                renderGearInventory();
            }
        },
        deleteGear: (gearId) => {
            if (!confirm('Delete this gear item?')) return;
            
            state.gearInventory = state.gearInventory.filter(g => g.id !== gearId);
            renderGearInventory();
            showNotification('Gear deleted', 'success');
        }
    };
})();

// Initialize when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', BackpackManager.init);
} else {
    BackpackManager.init();
}
