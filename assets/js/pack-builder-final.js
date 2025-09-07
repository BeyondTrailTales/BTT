/**
 * Final Pack Builder - Working Drag/Drop & Save
 */

class FinalPackBuilder {
    constructor() {
        this.currentPack = {
            id: null,
            name: 'My New Backpack',
            description: '',
            sections: [
                { id: 'main', name: 'Main Compartment', icon: '🎒', items: [], collapsed: false },
                { id: 'lid', name: 'Top Lid', icon: '🏔️', items: [], collapsed: false },
                { id: 'pockets', name: 'Side Pockets', icon: '🎯', items: [], collapsed: false },
                { id: 'external', name: 'External', icon: '🔗', items: [], collapsed: false }
            ]
        };
        
        this.gearLibrary = [];
        this.filteredGear = [];
        this.currentCategory = 'all';
        this.searchTerm = '';
        this.hasUnsavedChanges = false;
        this.isSaving = false;
        this.draggedData = null; // Store drag data here instead of dataTransfer
        
        // Items panel state
        this.itemsPanelOpen = false;
        this.itemsPanelFilter = 'all';
        this.itemsPanelSearch = '';
        this.associatedTrips = [];
        
        this.init();
    }
    
    init() {
        console.log('🎒 Initializing Final Pack Builder...');
        this.loadGearLibrary();
        this.loadExistingPack();
        this.setupEventHandlers();
        this.render();
    }
    
    isCustomItem(item) {
        // Check if item is custom - handles both boolean true and numeric 1
        return !item.gear_id || item.is_custom === true || item.is_custom === 1;
    }
    
    setupEventHandlers() {
        const self = this;
        
        // Save button
        $('#save-pack-btn').on('click', async () => await this.savePack());
        
        // Search functionality
        $('#gear-search').on('input', function() {
            self.filterGear();
        });
        
        // Category filter buttons
        $(document).on('click', '.category-btn', function() {
            $('.category-btn').removeClass('active');
            $(this).addClass('active');
            self.currentCategory = $(this).data('category');
            self.filterGear();
        });
        
        // Add item click
        $(document).on('click', '.add-btn', function(e) {
            e.stopPropagation();
            const gearId = $(this).closest('.gear-item').data('gear-id');
            self.addItemToPack(gearId, 'main');
        });
        
        // Drag from gear library
        $(document).on('dragstart', '.gear-item', function(e) {
            const gearId = $(this).data('gear-id');
            self.draggedData = {
                type: 'new-item',
                gearId: gearId
            };
            $(this).addClass('dragging');
            console.log('Dragging new item:', gearId);
        });
        
        // Drag pack items between sections
        $(document).on('dragstart', '.pack-item', function(e) {
            const itemId = $(this).data('item-id');
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.draggedData = {
                type: 'move-item',
                itemId: itemId,
                sourceSectionId: sectionId
            };
            $(this).addClass('dragging');
            console.log('Dragging pack item:', itemId, 'from section:', sectionId);
        });
        
        // Drag end cleanup
        $(document).on('dragend', '.gear-item, .pack-item', function() {
            $(this).removeClass('dragging');
            $('.section-items').removeClass('drag-over drag-ready');
            self.draggedData = null; // Clear drag data
        });
        
        // Drop zones
        $(document).on('dragover', '.section-items', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $(document).on('dragleave', '.section-items', function() {
            $(this).removeClass('drag-over');
        });
        
        $(document).on('drop', '.section-items', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            if (!self.draggedData) return;
            
            const targetSectionId = $(this).closest('.pack-section').data('section-id');
            console.log('Drop in section:', targetSectionId, 'Data:', self.draggedData);
            
            if (self.draggedData.type === 'new-item') {
                self.addItemToPack(self.draggedData.gearId, targetSectionId);
            } else if (self.draggedData.type === 'move-item') {
                if (self.draggedData.sourceSectionId !== targetSectionId) {
                    self.moveItemBetweenSections(
                        self.draggedData.itemId,
                        self.draggedData.sourceSectionId,
                        targetSectionId
                    );
                }
            }
            
            self.draggedData = null;
        });
        
        // Section management
        $(document).on('click', '.add-section-btn', () => this.addSection());
        
        // Add custom item button
        $(document).on('click', '.add-custom-item-btn', function() {
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.addCustomItem(sectionId);
        });
        
        $(document).on('click', '.section-header', function(e) {
            if ($(e.target).hasClass('section-delete') || 
                $(e.target).hasClass('section-name-input')) return;
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.toggleSection(sectionId);
        });
        
        $(document).on('click', '.section-delete', function(e) {
            e.stopPropagation();
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.deleteSection(sectionId);
        });
        
        $(document).on('change', '.section-name-input', function() {
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.updateSectionName(sectionId, $(this).val());
        });
        
        $(document).on('click', '.remove-btn', function() {
            const itemId = $(this).closest('.pack-item').data('item-id');
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.removeItem(sectionId, itemId);
        });
        
        // Pack name
        $(document).on('click', '.pack-name', function() {
            const name = $(this).text();
            $(this).replaceWith(`<input class="pack-name-input" value="${name}">`);
            $('.pack-name-input').focus().select();
        });
        
        $(document).on('blur keypress', '.pack-name-input', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            self.currentPack.name = $(this).val() || 'My Backpack';
            $(this).replaceWith(`<h2 class="pack-name">${self.currentPack.name}</h2>`);
            self.markUnsaved();
        });
        
        // Inline quantity editing
        $(document).on('click', '.pack-item-quantity', function(e) {
            e.stopPropagation();
            const $this = $(this);
            const itemId = $this.data('item-id');
            const sectionId = $this.data('section-id');
            const currentQty = parseInt($this.text().replace('Qty: ', ''));
            
            $this.replaceWith(`<input type="number" class="pack-item-quantity-input" value="${currentQty}" min="1" data-item-id="${itemId}" data-section-id="${sectionId}">`);
            $('.pack-item-quantity-input').focus().select();
        });
        
        $(document).on('blur keypress', '.pack-item-quantity-input', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            const itemId = $(this).data('item-id');
            const sectionId = $(this).data('section-id');
            let newQty = parseInt($(this).val()) || 1;
            if (newQty < 1) newQty = 1;
            
            self.updateItemQuantity(sectionId, itemId, newQty);
        });
        
        // Inline item name editing for custom items
        $(document).on('click', '.pack-item-name.editable', function(e) {
            e.stopPropagation();
            const $this = $(this);
            const itemId = $this.data('item-id');
            const sectionId = $this.data('section-id');
            const currentName = $this.text();
            
            $this.replaceWith(`<input type="text" class="pack-item-name-input" value="${currentName}" data-item-id="${itemId}" data-section-id="${sectionId}">`);
            $('.pack-item-name-input').focus().select();
        });
        
        $(document).on('blur keypress', '.pack-item-name-input', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            const itemId = $(this).data('item-id');
            const sectionId = $(this).data('section-id');
            const newName = $(this).val() || 'Custom Item';
            
            self.updateItemName(sectionId, itemId, newName);
        });
        
        // Inline weight editing for custom items
        $(document).on('click', '.pack-item-weight.editable', function(e) {
            e.stopPropagation();
            const $this = $(this);
            const itemId = $this.data('item-id');
            const sectionId = $this.data('section-id');
            const section = self.currentPack.sections.find(s => s.id === sectionId);
            const item = section?.items.find(i => i.id === itemId);
            const currentWeight = item?.weight_g || 0;
            
            $this.replaceWith(`<input type="number" class="pack-item-weight-input" value="${currentWeight}" min="0" data-item-id="${itemId}" data-section-id="${sectionId}" placeholder="grams">`);
            $('.pack-item-weight-input').focus().select();
        });
        
        $(document).on('blur keypress', '.pack-item-weight-input', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            const itemId = $(this).data('item-id');
            const sectionId = $(this).data('section-id');
            const newWeight = parseInt($(this).val()) || 0;
            
            self.updateItemWeight(sectionId, itemId, newWeight);
        });
        
        // Inline category editing for custom items
        $(document).on('click', '.gear-category-badge.editable', function(e) {
            e.stopPropagation();
            const $this = $(this);
            const itemId = $this.data('item-id');
            const sectionId = $this.data('section-id');
            const section = self.currentPack.sections.find(s => s.id === sectionId);
            const item = section?.items.find(i => i.id === itemId);
            const currentCategory = item?.category || 'other';
            
            const categories = ['shelter', 'sleep', 'clothing', 'cooking', 'water', 'food', 'navigation', 'safety', 'tools', 'electronics', 'personal', 'other'];
            const select = `<select class="pack-item-category-select" data-item-id="${itemId}" data-section-id="${sectionId}">
                ${categories.map(cat => `<option value="${cat}" ${cat === currentCategory ? 'selected' : ''}>${self.formatCategory(cat)}</option>`).join('')}
            </select>`;
            
            $this.replaceWith(select);
            $('.pack-item-category-select').focus();
        });
        
        $(document).on('blur change', '.pack-item-category-select', function(e) {
            const itemId = $(this).data('item-id');
            const sectionId = $(this).data('section-id');
            const newCategory = $(this).val();
            
            self.updateItemCategory(sectionId, itemId, newCategory);
        });
    }
    
    loadGearLibrary() {
        // Use the same API URL pattern as the gear page
        const apiUrl = window.BTT?.apiUrl || window.location.origin + '/BTT/ajax-handler.php';
        const gearEndpoint = `${apiUrl}?route=gear`;
        
        $.get(gearEndpoint)
            .done(data => {
                console.log('Loaded gear library:', data);
                
                // Debug: Check which items are marked as custom
                if (data && Array.isArray(data)) {
                    const customItems = data.filter(item => item.is_custom);
                    console.log('Custom gear items found:', customItems);
                }
                
                this.gearLibrary = data || [];
                this.filteredGear = this.gearLibrary;
                this.renderGearList();
            })
            .fail((xhr, status, error) => {
                console.error('Failed to load gear:', {
                    status: status,
                    error: error,
                    url: gearEndpoint,
                    response: xhr.responseText
                });
                this.gearLibrary = [];
                this.filteredGear = [];
                this.renderGearList();
            });
    }
    
    filterGear() {
        const searchTerm = $('#gear-search').val().toLowerCase();
        
        this.filteredGear = this.gearLibrary.filter(item => {
            const matchesSearch = searchTerm === '' || 
                                 item.name.toLowerCase().includes(searchTerm) ||
                                 (item.category && item.category.toLowerCase().includes(searchTerm));
            
            const matchesCategory = this.currentCategory === 'all' || 
                                   item.category === this.currentCategory;
            
            return matchesSearch && matchesCategory;
        });
        
        this.renderGearList();
    }
    
    loadExistingPack() {
        const urlParams = new URLSearchParams(window.location.search);
        const packId = urlParams.get('id');
        if (!packId) return;
        
        $.get(`/BTT/ajax-handler.php?route=backpacks&id=${packId}`)
            .done(response => {
                if (response.success && response.data) {
                    const data = response.data;
                    this.currentPack = {
                        id: data.id,
                        name: data.name || 'My Backpack',
                        description: data.description || '',
                        sections: data.sections || this.currentPack.sections
                    };
                    this.render();
                }
            });
    }
    
    addItemToPack(gearId, sectionId) {
        const gear = this.gearLibrary.find(g => g.id == gearId);
        if (!gear) return;
        
        console.log('Adding gear to pack:', gear.name, 'is_custom:', gear.is_custom);
        
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const existing = section.items.find(i => i.gear_id == gearId);
        if (existing) {
            existing.quantity++;
            this.showToast(`Increased ${gear.name} quantity to ${existing.quantity}`);
        } else {
            section.items.push({
                id: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                gear_id: gear.id,
                name: gear.name,
                weight_g: gear.weight_g || 0,
                category: gear.category || 'other',
                quantity: 1,
                is_custom: gear.is_custom  // Preserve original is_custom value (true/false or 1/0)
            });
            this.showToast(`Added ${gear.name} to ${section.name}`);
        }
        
        this.render();
        this.markUnsaved();
        
        // Update panel if open
        if (this.itemsPanelOpen) {
            this.renderItemsPanel();
        }
    }
    
    moveItemBetweenSections(itemId, sourceId, targetId) {
        const source = this.currentPack.sections.find(s => s.id === sourceId);
        const target = this.currentPack.sections.find(s => s.id === targetId);
        
        if (!source || !target) return;
        
        const itemIndex = source.items.findIndex(i => i.id === itemId);
        if (itemIndex === -1) return;
        
        const item = source.items.splice(itemIndex, 1)[0];
        
        const existing = target.items.find(i => i.gear_id === item.gear_id);
        if (existing) {
            existing.quantity += item.quantity;
            this.showToast(`Merged ${item.name} (total: ${existing.quantity})`);
        } else {
            target.items.push(item);
            this.showToast(`Moved ${item.name} to ${target.name}`);
        }
        
        this.render();
        this.markUnsaved();
    }
    
    removeItem(sectionId, itemId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const index = section.items.findIndex(i => i.id === itemId);
        if (index !== -1) {
            const item = section.items[index];
            section.items.splice(index, 1);
            this.render();
            this.markUnsaved();
            this.showToast(`Removed ${item.name}`);
        }
    }
    
    addSection() {
        this.currentPack.sections.push({
            id: 'section_' + Date.now(),
            name: 'New Section',
            icon: '📦',
            items: [],
            collapsed: false
        });
        this.render();
        this.markUnsaved();
    }
    
    addCustomItem(sectionId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const customItem = {
            id: 'custom_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            name: 'Custom Item',
            weight_g: 0,
            category: 'other',
            quantity: 1,
            is_custom: 1,
            pending_save_to_library: true  // Flag to save to gear library
            // Note: no gear_id for custom items yet
        };
        
        section.items.push(customItem);
        this.render();
        this.markUnsaved();
        this.showToast('Added custom item - click name to edit');
    }
    
    deleteSection(sectionId) {
        const index = this.currentPack.sections.findIndex(s => s.id === sectionId);
        if (index === -1) return;
        
        const section = this.currentPack.sections[index];
        if (section.items.length > 0) {
            if (!confirm(`Delete "${section.name}" with ${section.items.length} items?`)) {
                return;
            }
        }
        
        this.currentPack.sections.splice(index, 1);
        this.render();
        this.markUnsaved();
    }
    
    updateSectionName(sectionId, name) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = name || 'Unnamed';
            this.markUnsaved();
        }
    }
    
    toggleSection(sectionId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.collapsed = !section.collapsed;
            this.renderSections();
        }
    }
    
    async savePack() {
        if (this.isSaving) return;
        
        // Don't save if drag operation is in progress
        if (this.draggedData) {
            this.showToast('Please complete the drag operation first', 'warning');
            return;
        }
        
        this.isSaving = true;
        $('#save-pack-btn').prop('disabled', true).html('⏳ Saving...');
        
        // First save any pending custom items to the gear library
        try {
            await this.saveCustomItemsToLibrary();
        } catch (error) {
            console.warn('Some custom items failed to save to library, continuing with pack save:', error);
        }
        
        // Clean up temporary flags and ensure proper data format for backend
        const cleanedSections = this.currentPack.sections.map(section => ({
            id: section.id,
            name: section.name,
            icon: section.icon,
            collapsed: section.collapsed || false,
            items: section.items.filter(item => {
                // Filter out any invalid items
                return item && (item.gear_id || item.name) && item.name !== '';
            }).map(item => {
                // Prepare item data for backend
                const cleanItem = {
                    id: item.id,
                    name: item.name || 'Unnamed Item',
                    weight_g: parseInt(item.weight_g) || 0,
                    category: item.category || 'other',
                    quantity: parseInt(item.quantity) || 1
                };
                
                // Include gear_id if it exists (items from gear library)
                if (item.gear_id) {
                    cleanItem.gear_id = item.gear_id;
                }
                
                // Include is_custom flag for consistency
                if (item.is_custom) {
                    cleanItem.is_custom = item.is_custom;
                }
                
                // Include notes if they exist
                if (item.notes) {
                    cleanItem.notes = item.notes;
                }
                
                return cleanItem;
            })
        }));
        
        const data = {
            id: this.currentPack.id,
            name: this.currentPack.name,
            description: this.currentPack.description,
            sections: cleanedSections
        };
        
        console.log('Saving pack data:', JSON.stringify(data, null, 2));
        
        const url = this.currentPack.id 
            ? `/BTT/ajax-handler.php?route=backpacks&id=${this.currentPack.id}`
            : '/BTT/ajax-handler.php?route=backpacks';
        const method = this.currentPack.id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: (response) => {
                if (!this.currentPack.id && response.data?.id) {
                    this.currentPack.id = response.data.id;
                    window.history.replaceState({}, '', `?id=${this.currentPack.id}`);
                }
                
                if (response.stats) {
                    let msg = `✅ Saved! ${response.stats.total_items} items in database`;
                    this.showToast(msg, 'success', 4000);
                } else {
                    this.showToast('Pack saved!', 'success');
                }
                
                this.hasUnsavedChanges = false;
                $('#save-pack-btn').removeClass('has-changes').html('💾 Save Pack');
                
                // Update panel if open - in case section structure changed
                if (this.itemsPanelOpen) {
                    this.renderItemsPanel();
                    // Reload associated trips if we now have a pack ID
                    if (this.currentPack.id && (!this.associatedTrips || this.associatedTrips.length === 0)) {
                        this.loadAssociatedTrips();
                    }
                }
            },
            error: (xhr, status, error) => {
                console.error('Save failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });
                
                let errorMsg = 'Failed to save pack';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg += ': ' + response.error;
                    }
                } catch (e) {
                    // If response isn't JSON, show generic error
                    if (xhr.status === 500) {
                        errorMsg += ' (Server error)';
                    } else if (xhr.status === 400) {
                        errorMsg += ' (Invalid data)';
                    }
                }
                
                this.showToast(errorMsg, 'error');
            },
            complete: () => {
                this.isSaving = false;
                $('#save-pack-btn').prop('disabled', false);
            }
        });
    }
    
    updateItemQuantity(sectionId, itemId, quantity) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const item = section.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.quantity = quantity;
        this.render();
        this.markUnsaved();
        this.showToast(`Updated ${item.name} quantity to ${quantity}`);
    }
    
    updateItemName(sectionId, itemId, name) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const item = section.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.name = name;
        // Only mark as needing to save to library if it's a truly custom item (no gear_id) and not already saved
        if (!item.gear_id && !item.pending_save_to_library && item.name !== 'Custom Item') {
            item.pending_save_to_library = true;
        }
        this.render();
        this.markUnsaved();
        this.showToast(`Updated item name to "${name}"`);
    }
    
    updateItemWeight(sectionId, itemId, weight) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const item = section.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.weight_g = weight;
        // Only mark as needing to save to library if it's a truly custom item (no gear_id)
        if (!item.gear_id && !item.pending_save_to_library) {
            item.pending_save_to_library = true;
        }
        this.render();
        this.markUnsaved();
        this.showToast(`Updated weight to ${this.formatWeight(weight)}`);
    }
    
    updateItemCategory(sectionId, itemId, category) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const item = section.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.category = category;
        // Only mark as needing to save to library if it's a truly custom item (no gear_id)
        if (!item.gear_id && !item.pending_save_to_library) {
            item.pending_save_to_library = true;
        }
        this.render();
        this.markUnsaved();
        this.showToast(`Updated category to ${this.formatCategory(category)}`);
    }
    
    async saveCustomItemsToLibrary() {
        const customItemsToSave = [];
        const itemsToUpdate = [];
        
        // Find all custom items that need to be saved to library
        this.currentPack.sections.forEach(section => {
            section.items.forEach(item => {
                if (item.pending_save_to_library && !item.gear_id && item.name && item.name !== 'Custom Item') {
                    customItemsToSave.push({
                        name: item.name,
                        category: item.category,
                        weight_g: item.weight_g,
                        notes: item.notes || '',
                        tags: []
                    });
                    itemsToUpdate.push(item); // Keep reference to update later
                }
            });
        });
        
        if (customItemsToSave.length === 0) return;
        
        console.log(`Saving ${customItemsToSave.length} custom items to gear library:`, customItemsToSave);
        
        // Save each custom item to the gear library
        const savePromises = customItemsToSave.map(gearData => {
            return $.ajax({
                url: '/BTT/ajax-handler.php?route=gear',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(gearData)
            });
        });
        
        try {
            const results = await Promise.allSettled(savePromises);
            
            // Update the items with their new gear IDs
            let savedCount = 0;
            let failedCount = 0;
            
            results.forEach((result, index) => {
                const item = itemsToUpdate[index];
                if (result.status === 'fulfilled' && result.value.data && result.value.data.id) {
                    item.gear_id = result.value.data.id;
                    item.pending_save_to_library = false;
                    item.is_custom = true;
                    savedCount++;
                    console.log(`Successfully saved ${item.name} to gear library with ID: ${item.gear_id}`);
                } else {
                    failedCount++;
                    console.error('Failed to save custom item:', item.name, result.reason || result.value);
                    // Remove the pending flag even if save failed to prevent retry loops
                    item.pending_save_to_library = false;
                }
            });
            
            if (savedCount > 0) {
                this.showToast(`Added ${savedCount} custom items to your gear library`, 'success');
                // Reload gear library to include new items
                this.loadGearLibrary();
            }
            
            if (failedCount > 0) {
                console.warn(`Failed to save ${failedCount} custom items to library, but continuing with pack save`);
            }
        } catch (error) {
            console.error('Failed to save custom items to library:', error);
            // Clear pending flags to prevent retry loops
            itemsToUpdate.forEach(item => {
                item.pending_save_to_library = false;
            });
        }
    }
    
    markUnsaved() {
        this.hasUnsavedChanges = true;
        $('#save-pack-btn').addClass('has-changes').html('💾 Save Pack *');
    }
    
    render() {
        this.renderGearList();
        this.renderPackHeader();
        this.renderSections();
        this.updateWeights();
    }
    
    renderGearList() {
        const html = this.filteredGear.map(item => `
            <div class="gear-item${this.isCustomItem(item) ? ' custom-gear' : ''}" data-gear-id="${item.id}" data-category="${item.category || 'other'}" draggable="true">
                <div class="gear-item-icon">${this.getIcon(item.category)}</div>
                <div class="gear-item-info">
                    <div class="gear-item-name">${item.name}${this.isCustomItem(item) ? ' <span class="custom-badge">✏️</span>' : ''}</div>
                    <div class="gear-item-meta">
                        <span class="gear-category gear-category-badge ${item.category || 'other'}">${this.formatCategory(item.category)}</span>
                        <span class="gear-item-weight">${this.formatWeight(item.weight_g)}</span>
                    </div>
                </div>
                <button class="add-btn">+ Add</button>
            </div>
        `).join('');
        
        $('#gear-list').html(html || '<div class="empty-state">No gear found</div>');
    }
    
    renderPackHeader() {
        $('.pack-name').text(this.currentPack.name);
    }
    
    renderSections() {
        // Debug: Log items and their is_custom status
        console.log('Rendering sections, items:', this.currentPack.sections.map(s => ({
            section: s.name,
            items: s.items.map(i => ({ name: i.name, gear_id: i.gear_id, is_custom: i.is_custom }))
        })));
        
        const html = this.currentPack.sections.map(section => `
            <div class="pack-section" data-section-id="${section.id}">
                <div class="section-header ${section.collapsed ? 'collapsed' : ''}">
                    <span class="section-toggle">▼</span>
                    <span class="section-icon">${section.icon}</span>
                    <input type="text" class="section-name-input" value="${section.name}">
                    <span class="section-weight">${this.formatWeight(
                        section.items.reduce((sum, i) => sum + (i.weight_g * i.quantity), 0)
                    )}</span>
                    <button class="section-delete">×</button>
                </div>
                <div class="section-content" ${section.collapsed ? 'style="display:none"' : ''}>
                    <div class="section-items">
                        ${section.items.map(item => `
                            <div class="pack-item${item.pending_save_to_library ? ' pending-library-save' : ''}" data-item-id="${item.id}" data-category="${item.category || 'other'}" draggable="true">
                                <div class="pack-item-icon">${this.getIcon(item.category)}</div>
                                <div class="pack-item-info">
                                    <div class="pack-item-name${this.isCustomItem(item) ? ' editable' : ''}" data-item-id="${item.id}" data-section-id="${section.id}">${item.name}</div>
                                    <div class="pack-item-details">
                                        <span class="gear-category-badge ${item.category || 'other'}${this.isCustomItem(item) ? ' editable' : ''}" data-item-id="${item.id}" data-section-id="${section.id}">${this.formatCategory(item.category)}</span>
                                        <span class="${this.isCustomItem(item) ? 'pack-item-weight editable' : ''}" data-item-id="${item.id}" data-section-id="${section.id}">${this.formatWeight(item.weight_g)}</span>
                                        <span class="pack-item-quantity" data-item-id="${item.id}" data-section-id="${section.id}">Qty: ${item.quantity}</span>
                                    </div>
                                </div>
                                <button class="remove-btn">×</button>
                            </div>
                        `).join('') || '<div class="section-empty">Drop items here</div>'}
                        ${section.items.length === 0 || section.items.length > 0 ? `<button class="add-custom-item-btn">+ Add Custom Item</button>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
        
        $('#pack-sections').html(html + '<div class="add-section-btn">+ Add New Section</div>');
    }
    
    updateWeights() {
        let total = 0;
        let items = 0;
        
        this.currentPack.sections.forEach(s => {
            s.items.forEach(i => {
                total += (i.weight_g || 0) * i.quantity;
                items += i.quantity;
            });
        });
        
        $('.total-weight').text(this.formatWeight(total));
        $('.total-items').text(items);
    }
    
    formatWeight(g) {
        return g >= 1000 ? (g/1000).toFixed(1) + 'kg' : Math.round(g) + 'g';
    }
    
    formatCategory(category) {
        if (!category) return 'Other';
        // Convert category to title case
        return category.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    getIcon(category) {
        const icons = {
            shelter: '⛺', sleep: '🛌', clothing: '👕',
            cooking: '🔥', water: '💧', food: '🍞',
            navigation: '🧭', 'first-aid': '🏥', tools: '🔧',
            electronics: '📱', hygiene: '🧼', footwear: '🥾',
            'rain-gear': '☔', emergency: '🚨', repair: '🔨',
            'food-storage': '📦', ultralight: '🪶',
            electronics: '📱', personal: '🧴', other: '📦'
        };
        return icons[category] || '📦';
    }
    
    showToast(msg, type = 'info', duration = 3000) {
        const toast = $(`<div class="toast toast-${type}">${msg}</div>`);
        $('body').append(toast);
        setTimeout(() => toast.addClass('show'), 10);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    // Items Panel Methods
    toggleItemsPanel() {
        const panel = document.getElementById('packed-items-panel');
        if (!panel) return;
        
        this.itemsPanelOpen = !this.itemsPanelOpen;
        
        if (this.itemsPanelOpen) {
            panel.classList.add('open');
            document.body.style.overflow = 'hidden';
            this.initItemsPanelEvents();
            this.renderItemsPanel();
            // Load trips if we have a pack ID
            if (this.currentPack.id) {
                this.loadAssociatedTrips();
            }
        } else {
            panel.classList.remove('open');
            document.body.style.overflow = '';
        }
    }
    
    refreshItemsPanel() {
        const refreshBtn = document.querySelector('.btn-refresh');
        if (refreshBtn) {
            refreshBtn.classList.add('refreshing');
            refreshBtn.disabled = true;
        }
        
        // Show loading state briefly
        const loadingState = document.getElementById('items-loading');
        const itemsList = document.getElementById('items-list');
        if (loadingState && itemsList) {
            loadingState.style.display = 'flex';
            itemsList.style.display = 'none';
        }
        
        // Simulate loading delay for visual feedback
        setTimeout(async () => {
            // Reload associated trips if we have a pack ID
            if (this.currentPack.id) {
                await this.loadAssociatedTrips();
            }
            
            // Re-render the panel
            this.renderItemsPanel();
            
            // Remove loading state
            if (refreshBtn) {
                refreshBtn.classList.remove('refreshing');
                refreshBtn.disabled = false;
            }
            
            this.showToast('Items refreshed', 'success');
        }, 300);
    }
    
    initItemsPanelEvents() {
        const self = this;
        
        // Only bind once
        if (this.itemsPanelEventsInitialized) return;
        this.itemsPanelEventsInitialized = true;
        
        // Search
        $('#items-search').on('input', function() {
            self.itemsPanelSearch = $(this).val();
            self.renderItemsPanel();
        });
        
        // Filter
        $('#items-filter').on('change', function() {
            self.itemsPanelFilter = $(this).val();
            self.renderItemsPanel();
        });
        
        // Close on backdrop click
        $('#packed-items-panel').on('click', function(e) {
            if (e.target === this) {
                self.toggleItemsPanel();
            }
        });
    }
    
    renderItemsPanel() {
        const itemsList = document.getElementById('items-list');
        const emptyState = document.getElementById('items-empty');
        const loadingState = document.getElementById('items-loading');
        
        if (!itemsList) return;
        
        // Hide loading
        loadingState.style.display = 'none';
        
        // Collect all items from sections
        let allItems = [];
        this.currentPack.sections.forEach(section => {
            section.items.forEach(item => {
                allItems.push({
                    ...item,
                    sectionId: section.id,
                    sectionName: section.name
                });
            });
        });
        
        // Apply filters
        let filteredItems = allItems;
        
        // Section filter
        if (this.itemsPanelFilter !== 'all') {
            filteredItems = filteredItems.filter(item => item.sectionId === this.itemsPanelFilter);
        }
        
        // Search filter
        if (this.itemsPanelSearch) {
            const searchLower = this.itemsPanelSearch.toLowerCase();
            filteredItems = filteredItems.filter(item =>
                item.name.toLowerCase().includes(searchLower) ||
                (item.brand && item.brand.toLowerCase().includes(searchLower))
            );
        }
        
        // Clear and render
        itemsList.innerHTML = '';
        
        if (filteredItems.length === 0) {
            emptyState.style.display = 'block';
            itemsList.style.display = 'none';
            this.updateItemsPanelSummary(0, 0);
            return;
        }
        
        emptyState.style.display = 'none';
        itemsList.style.display = 'block';
        
        // Group by section
        const itemsBySection = {};
        filteredItems.forEach(item => {
            if (!itemsBySection[item.sectionName]) {
                itemsBySection[item.sectionName] = [];
            }
            itemsBySection[item.sectionName].push(item);
        });
        
        // Render sections
        let totalItems = 0;
        let totalWeight = 0;
        
        Object.entries(itemsBySection).forEach(([sectionName, items]) => {
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'section-group';
            
            const sectionWeight = items.reduce((sum, item) => 
                sum + ((item.weight || 0) * (item.quantity || 1)), 0
            );
            
            totalItems += items.length;
            totalWeight += sectionWeight;
            
            sectionDiv.innerHTML = `
                <div class="section-header">
                    <span>${this.escapeHtml(sectionName)}</span>
                    <span class="section-stats">${items.length} items • ${this.formatWeight(sectionWeight)}</span>
                </div>
            `;
            
            const itemsList = document.createElement('div');
            itemsList.className = 'section-items';
            
            items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-card';
                const itemWeight = (item.weight || 0) * (item.quantity || 1);
                const category = (item.category || 'other').toLowerCase();
                
                itemDiv.innerHTML = `
                    <div class="item-info">
                        <span class="item-category">${category}</span>
                        <span class="item-name">${this.escapeHtml(item.name)}${item.quantity > 1 ? ` (${item.quantity}x)` : ''}</span>
                    </div>
                    <div class="item-weight">${this.formatWeight(itemWeight)}</div>
                `;
                
                itemsList.appendChild(itemDiv);
            });
            
            sectionDiv.appendChild(itemsList);
            document.getElementById('items-list').appendChild(sectionDiv);
        });
        
        this.updateItemsPanelSummary(totalItems, totalWeight);
        
        // Show associated trips if any
        if (this.associatedTrips && this.associatedTrips.length > 0) {
            const tripsDiv = document.createElement('div');
            tripsDiv.className = 'associated-trips';
            tripsDiv.innerHTML = `
                <h4 style="margin: 1.5rem 0 1rem 0; color: var(--text-secondary);">
                    Associated Trips (${this.associatedTrips.length})
                </h4>
            `;
            
            this.associatedTrips.forEach(trip => {
                const tripElement = this.createTripElement(trip);
                tripsDiv.appendChild(tripElement);
            });
            
            itemsList.appendChild(tripsDiv);
        }
    }
    
    createTripElement(trip) {
        const div = document.createElement('div');
        div.className = 'trip-card';
        
        const hasPackingData = trip.packingData && trip.packingData.items;
        const progress = hasPackingData ? trip.packingData.summary : { packed: 0, total: 0, percent: 0 };
        
        div.innerHTML = `
            <div class="trip-header">
                <div class="trip-info">
                    <h5 class="trip-name">${this.escapeHtml(trip.title || trip.name || 'Unnamed Trip')}</h5>
                    <div class="trip-dates">${this.formatTripDates(trip.start_date, trip.end_date)}</div>
                </div>
                <div class="trip-progress">
                    <div class="progress-text">${progress.packed}/${progress.total} packed</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress.percent}%"></div>
                    </div>
                </div>
            </div>
        `;
        
        if (hasPackingData) {
            const checklistDiv = document.createElement('div');
            checklistDiv.className = 'trip-checklist';
            checklistDiv.style.marginTop = '1rem';
            
            // Show items with checkboxes
            trip.packingData.items.forEach(item => {
                if (item.gear_id || item.type === 'gear') {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'checklist-item';
                    itemDiv.innerHTML = `
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                ${item.is_packed ? 'checked' : ''} 
                                onchange="window.packBuilder.toggleTripItem(${trip.id}, ${item.gear_id || item.id}, this.checked)">
                            <span class="item-name">${this.escapeHtml(item.name)}</span>
                            <span class="item-qty">${item.quantity > 1 ? `x${item.quantity}` : ''}</span>
                        </label>
                    `;
                    checklistDiv.appendChild(itemDiv);
                }
            });
            
            div.appendChild(checklistDiv);
        } else {
            div.innerHTML += '<div class="no-packing-data">Loading packing list...</div>';
        }
        
        return div;
    }
    
    formatTripDates(startDate, endDate) {
        if (!startDate) return 'No date set';
        
        const start = new Date(startDate);
        const end = endDate ? new Date(endDate) : start;
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        
        if (isNaN(start.getTime())) {
            return 'Invalid date';
        }
        
        if (start.toDateString() === end.toDateString()) {
            return start.toLocaleDateString('en-US', options);
        } else {
            return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', options)}`;
        }
    }
    
    updateItemsPanelSummary(totalItems, totalWeight) {
        document.getElementById('total-items').textContent = totalItems;
        document.getElementById('total-weight').textContent = this.formatWeight(totalWeight);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Load trips associated with this backpack
    async loadAssociatedTrips() {
        if (!this.currentPack.id) return;
        
        try {
            // Use the BTT_API to fetch trips for this backpack
            const response = await fetch(`/BTT/ajax-handler.php?route=trips&backpack_id=${this.currentPack.id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.associatedTrips = result.data || [];
                // Load packing data for each trip
                for (const trip of this.associatedTrips) {
                    await this.loadTripPackingData(trip);
                }
                // Re-render panel with trips
                this.renderItemsPanel();
            }
        } catch (error) {
            console.error('Failed to load associated trips:', error);
            this.associatedTrips = [];
        }
    }
    
    // Load packing data for a trip
    async loadTripPackingData(trip) {
        try {
            const response = await fetch(`/BTT/ajax-handler.php?route=trips/${trip.id}/packing-list`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                trip.packingData = result.data;
            }
        } catch (error) {
            console.error(`Failed to load packing data for trip ${trip.id}:`, error);
            trip.packingData = null;
        }
    }
    
    // Toggle packed item in trip
    async toggleTripItem(tripId, itemId, isPacked) {
        try {
            const response = await fetch(`/BTT/ajax-handler.php?route=trips/${tripId}/packing-list&sub_action=gear&item_id=${itemId}`, {
                method: 'PUT',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ is_packed: isPacked })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update local state
                const trip = this.associatedTrips.find(t => t.id === tripId);
                if (trip && trip.packingData && trip.packingData.items) {
                    const item = trip.packingData.items.find(i => 
                        (i.gear_id && i.gear_id == itemId) || (i.id === `gear-${itemId}`)
                    );
                    if (item) {
                        item.is_packed = isPacked;
                        
                        // Update summary
                        const packed = trip.packingData.items.filter(i => i.is_packed).length;
                        trip.packingData.summary.packed = packed;
                        trip.packingData.summary.percent = Math.floor((packed / trip.packingData.summary.total) * 100);
                    }
                }
                
                // Re-render
                this.renderItemsPanel();
                this.showToast(isPacked ? 'Item marked as packed' : 'Item marked as unpacked', 'success');
            }
        } catch (error) {
            console.error('Failed to update trip item:', error);
            this.showToast('Failed to update packing status', 'error');
        }
    }
}

// Initialize
$(document).ready(() => {
    console.log('Starting Final Pack Builder...');
    window.packBuilder = new FinalPackBuilder();
    // Expose as PackBuilder for button onclick
    window.PackBuilder = window.packBuilder;
});