/**
 * Simple Pack Builder - Easy UX for Adding Items
 */

class SimplePackBuilder {
    constructor() {
        this.currentPack = {
            id: null,
            name: 'My New Backpack',
            description: '',
            capacity_l: 65,
            type: 'custom',
            sections: [
                { id: 'main', name: 'Main Pack', icon: '🎒', items: [], collapsed: false },
                { id: 'worn', name: 'Worn Items', icon: '👕', items: [], collapsed: false },
                { id: 'consumables', name: 'Consumables', icon: '🍎', items: [], collapsed: false }
            ]
        };
        this.gearLibrary = [];
        this.filteredGear = [];
        this.currentCategory = 'all';
        this.searchTerm = '';
        this.hasUnsavedChanges = false;
        this.isSaving = false;
        
        this.init();
    }
    
    init() {
        console.log('🎒 Simple Pack Builder: Initializing...');
        this.setupEventListeners();
        this.loadGearLibrary();
        this.loadExistingPack();
        this.render();
    }
    
    setupEventListeners() {
        // Search
        $(document).on('input', '#gear-search', (e) => {
            this.searchTerm = e.target.value.toLowerCase();
            this.filterGear();
        });
        
        // Category filters
        $(document).on('click', '.category-btn', (e) => {
            $('.category-btn').removeClass('active');
            $(e.target).addClass('active');
            this.currentCategory = $(e.target).data('category');
            this.filterGear();
        });
        
        // Add item to pack (click)
        $(document).on('click', '.add-btn', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const gearId = $(e.target).closest('.gear-item').data('gear-id');
            this.addItemToPack(gearId);
        });
        
        // Removed debug event listeners for cleaner console output
        
        // Drag & Drop Setup
        this.setupDragAndDrop();
        
        // Section toggle (collapse/expand)
        $(document).on('click', '.section-header', (e) => {
            if ($(e.target).hasClass('section-delete') || $(e.target).hasClass('section-name-input')) {
                return; // Don't toggle if clicking delete or name input
            }
            const sectionId = $(e.target).closest('.pack-section').data('section-id');
            this.toggleSection(sectionId);
        });
        
        // Add new section
        $(document).on('click', '.add-section-btn', () => {
            this.addNewSection();
        });
        
        // Delete section
        $(document).on('click', '.section-delete', (e) => {
            e.stopPropagation();
            const sectionId = $(e.target).closest('.pack-section').data('section-id');
            this.deleteSection(sectionId);
        });
        
        // Item quantity controls
        $(document).on('click', '.qty-btn', (e) => {
            const $item = $(e.target).closest('.pack-item');
            const sectionId = $item.closest('.pack-section').data('section-id');
            const itemId = $item.data('item-id');
            const action = $(e.target).hasClass('qty-increase') ? 'increase' : 'decrease';
            this.updateItemQuantity(sectionId, itemId, action);
        });
        
        // Remove item
        $(document).on('click', '.remove-btn', (e) => {
            const $item = $(e.target).closest('.pack-item');
            const sectionId = $item.closest('.pack-section').data('section-id');
            const itemId = $item.data('item-id');
            this.removeItem(sectionId, itemId);
        });
        
        // Save pack
        $(document).on('click', '#save-pack-btn', () => {
            this.savePack();
        });
        
        // Section name editing
        $(document).on('change', '.section-name-input', (e) => {
            const sectionId = $(e.target).closest('.pack-section').data('section-id');
            const newName = e.target.value;
            this.updateSectionName(sectionId, newName);
        });
        
        // Warn before leaving with unsaved changes
        $(window).on('beforeunload', (e) => {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
        
        // Pack name editing
        $(document).on('click', '.pack-name', (e) => {
            const $name = $(e.target);
            const currentName = $name.text();
            const $input = $(`<input type="text" class="pack-name-input" value="${currentName}">`);
            $name.replaceWith($input);
            $input.focus().select();
        });
        
        $(document).on('blur keyup', '.pack-name-input', (e) => {
            if (e.type === 'keyup' && e.key !== 'Enter') return;
            
            const $input = $(e.target);
            const newName = $input.val() || 'My Backpack';
            this.currentPack.name = newName;
            $input.replaceWith(`<h2 class="pack-name">${newName}</h2>`);
            this.markUnsaved();
        });
    }
    
    setupDragAndDrop() {
        console.log('🎯 Setting up drag & drop...');
        
        // Test if drag events are working at all
        setTimeout(() => {
            const gearItems = $('.gear-item');
            console.log('🔍 Found gear items:', gearItems.length);
            gearItems.each((idx, elem) => {
                const isDraggable = $(elem).attr('draggable');
                console.log(`   Item ${idx}: draggable="${isDraggable}", id="${$(elem).data('gear-id')}"`);
            });
        }, 2000);
        
        // Make gear items draggable
        $(document).on('dragstart', '.gear-item', (e) => {
            console.log('🎯 DRAGSTART EVENT FIRED!', e.currentTarget, 'Target:', e.target);
            
            // Don't drag if clicking on the add button
            if ($(e.target).hasClass('add-btn') || $(e.target).closest('.add-btn').length > 0) {
                console.log('❌ Preventing drag from add button');
                e.preventDefault();
                return false;
            }
            
            const gearId = $(e.currentTarget).data('gear-id');
            console.log('🎯 Gear ID:', gearId);
            
            const gearData = this.gearLibrary.find(item => item.id == gearId);
            console.log('🎯 Gear Data:', gearData);
            
            if (gearData) {
                try {
                    e.originalEvent.dataTransfer.setData('application/json', JSON.stringify(gearData));
                    e.originalEvent.dataTransfer.effectAllowed = 'copy';
                    
                    $(e.currentTarget).addClass('dragging');
                    $('.section-items').addClass('drag-ready');
                    
                    console.log('✅ Successfully set drag data for:', gearData.name);
                } catch (error) {
                    console.error('❌ Error setting drag data:', error);
                }
            } else {
                console.error('❌ Gear data not found for ID:', gearId);
            }
        });
        
        // Cleanup after drag ends
        $(document).on('dragend', '.gear-item', (e) => {
            $(e.currentTarget).removeClass('dragging');
            $('.section-items, .pack-section').removeClass('drag-ready drag-over');
        });
        
        // Section drop zone events
        $(document).on('dragover', '.section-items', (e) => {
            // Remove console log spam - it's working
            e.preventDefault();
            
            // Check what's being dragged to set correct drop effect
            const hasMoveData = e.originalEvent.dataTransfer.types.includes('text/item-move');
            e.originalEvent.dataTransfer.dropEffect = hasMoveData ? 'move' : 'copy';
            
            $(e.currentTarget).addClass('drag-over');
            $(e.currentTarget).closest('.pack-section').addClass('drag-over');
        });
        
        $(document).on('dragleave', '.section-items', (e) => {
            // Only remove drag-over if leaving the actual drop zone
            if (!$(e.currentTarget).is(e.relatedTarget) && 
                !$.contains(e.currentTarget, e.relatedTarget)) {
                $(e.currentTarget).removeClass('drag-over');
                $(e.currentTarget).closest('.pack-section').removeClass('drag-over');
            }
        });
        
        // Handle drop
        $(document).on('drop', '.section-items', (e) => {
            console.log('🎯 Drop event fired on section-items', e.currentTarget);
            e.preventDefault();
            
            const $dropZone = $(e.currentTarget);
            const targetSectionId = $dropZone.closest('.pack-section').data('section-id');
            console.log('🎯 Target section ID:', targetSectionId);
            
            $dropZone.removeClass('drag-over');
            $dropZone.closest('.pack-section').removeClass('drag-over');
            
            try {
                // Check if it's a gear item from library
                const gearJsonData = e.originalEvent.dataTransfer.getData('application/json');
                console.log('🎯 Raw drag data:', gearJsonData);
                
                if (gearJsonData) {
                    const gearData = JSON.parse(gearJsonData);
                    console.log('🎯 Parsed gear data:', gearData);
                    console.log('🎯 Dropped gear:', gearData.name, '→', targetSectionId);
                    this.addItemToPack(gearData.id, targetSectionId);
                    
                    // Visual feedback for successful drop
                    this.showDropSuccess($dropZone);
                    return;
                } else {
                    console.log('❌ No gear JSON data found in drop');
                }
                
                // Check if it's a pack item being moved between sections
                const itemMoveData = e.originalEvent.dataTransfer.getData('text/item-move');
                console.log('🔄 Pack item move data:', itemMoveData);
                
                if (itemMoveData) {
                    const moveData = JSON.parse(itemMoveData);
                    console.log('🔄 Moving item:', moveData.itemName, moveData.sourceSectionId, '→', targetSectionId);
                    
                    if (moveData.sourceSectionId !== targetSectionId) {
                        this.moveItemBetweenSections(moveData.itemId, moveData.sourceSectionId, targetSectionId);
                        this.showDropSuccess($dropZone);
                    }
                    return;
                } else {
                    console.log('❌ No pack item move data found in drop');
                }
                
            } catch (error) {
                console.error('❌ Drop error:', error);
                this.showToast('Failed to add/move item', 'error');
            }
        });
        
        // Pack item drag & drop (for moving between sections)
        $(document).on('dragstart', '.pack-item', (e) => {
            console.log('🔄 Pack item dragstart event fired', e.currentTarget);
            
            // Don't drag if clicking on buttons
            if ($(e.target).hasClass('qty-btn') || $(e.target).hasClass('remove-btn') || 
                $(e.target).closest('.pack-item-actions').length > 0) {
                console.log('❌ Preventing drag from button');
                e.preventDefault();
                return false;
            }
            
            const $item = $(e.currentTarget);
            const itemId = $item.data('item-id');
            const itemName = $item.find('.pack-item-name').text();
            const sourceSectionId = $item.closest('.pack-section').data('section-id');
            
            console.log('🔄 Pack item data:', { itemId, itemName, sourceSectionId });
            
            const moveData = {
                itemId: itemId,
                itemName: itemName,
                sourceSectionId: sourceSectionId
            };
            
            try {
                e.originalEvent.dataTransfer.setData('text/item-move', JSON.stringify(moveData));
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                
                $item.addClass('dragging');
                $('.section-items').not($item.closest('.section-items')).addClass('drag-ready');
                
                console.log('✅ Successfully set pack item drag data:', itemName, 'from', sourceSectionId);
            } catch (error) {
                console.error('❌ Error setting pack item drag data:', error);
            }
        });
        
        // Cleanup after pack item drag ends
        $(document).on('dragend', '.pack-item', (e) => {
            $(e.currentTarget).removeClass('dragging');
            $('.section-items, .pack-section').removeClass('drag-ready drag-over');
        });
        
        console.log('✅ Drag & drop setup complete');
    }
    
    loadGearLibrary() {
        console.log('📦 Loading gear library...');
        
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (Array.isArray(response)) {
                    this.gearLibrary = response;
                    this.filterGear();
                    console.log(`✅ Loaded ${response.length} gear items`);
                } else {
                    console.error('❌ Invalid gear response:', response);
                    this.showToast('Failed to load gear library', 'error');
                }
            },
            error: (xhr, status, error) => {
                console.error('❌ Gear loading failed:', error);
                this.showToast('Failed to load gear library', 'error');
                // Show empty state
                this.renderGearList([]);
            }
        });
    }
    
    loadExistingPack() {
        // Check if we're editing an existing pack
        const urlParams = new URLSearchParams(window.location.search);
        const packId = urlParams.get('id');
        
        if (packId) {
            console.log('📝 Loading existing pack:', packId);
            this.currentPack.id = packId;
            
            $.ajax({
                url: `/BTT/ajax-handler.php?route=backpacks&id=${packId}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    if (response.success && response.data) {
                        console.log('📦 Loaded pack data:', response.data);
                        console.log('📦 Sections from server:', response.data.sections);
                        
                        // Merge loaded data with defaults, ensuring all sections have required properties
                        const loadedSections = response.data.sections || [];
                        
                        // Ensure each section has all required properties
                        const sections = loadedSections.map(section => ({
                            id: section.id,
                            name: section.name || 'Unnamed Section',
                            icon: section.icon || '📦',
                            items: Array.isArray(section.items) ? section.items.map(item => ({
                                ...item,
                                id: item.id || `item_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                                gear_id: item.gear_id || null,
                                quantity: item.quantity || 1
                            })) : [],
                            collapsed: section.collapsed !== undefined ? section.collapsed : false
                        }));
                        
                        // If no sections loaded, use defaults
                        if (sections.length === 0) {
                            sections.push(
                                { id: 'main', name: 'Main Pack', icon: '🎒', items: [], collapsed: false },
                                { id: 'worn', name: 'Worn Items', icon: '👕', items: [], collapsed: false },
                                { id: 'consumables', name: 'Consumables', icon: '🍎', items: [], collapsed: false }
                            );
                        }
                        
                        // Ensure all sections have unique IDs
                        const sectionIds = new Set();
                        sections.forEach(section => {
                            if (sectionIds.has(section.id)) {
                                section.id = `${section.id}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
                            }
                            sectionIds.add(section.id);
                        });
                        
                        this.currentPack = {
                            ...this.currentPack,
                            ...response.data,
                            sections: sections
                        };
                        this.render();
                        console.log('✅ Pack loaded successfully');
                        console.log('📦 Current pack sections:', this.currentPack.sections);
                    }
                },
                error: () => {
                    console.error('❌ Failed to load pack');
                    this.showToast('Failed to load pack', 'error');
                }
            });
        }
    }
    
    filterGear() {
        this.filteredGear = this.gearLibrary.filter(item => {
            const matchesCategory = this.currentCategory === 'all' || 
                                  item.category === this.currentCategory;
            const matchesSearch = this.searchTerm === '' || 
                                 item.name.toLowerCase().includes(this.searchTerm) ||
                                 (item.category && item.category.toLowerCase().includes(this.searchTerm));
            
            return matchesCategory && matchesSearch;
        });
        
        this.renderGearList();
    }
    
    addItemToPack(gearId, targetSectionId = null) {
        const gearItem = this.gearLibrary.find(item => item.id == gearId);
        if (!gearItem) {
            console.error('❌ Gear item not found:', gearId);
            return;
        }
        
        console.log('➕ Adding item to pack:', gearItem);
        
        // If no target section specified, ask user or use default
        let sectionId = targetSectionId;
        if (!sectionId) {
            // Simple logic: put in first non-collapsed section or main
            const availableSection = this.currentPack.sections.find(s => !s.collapsed) || this.currentPack.sections[0];
            sectionId = availableSection.id;
        }
        
        const sectionIndex = this.currentPack.sections.findIndex(s => s.id === sectionId);
        if (sectionIndex === -1) {
            console.error('❌ Section not found:', sectionId);
            return;
        }
        
        const section = this.currentPack.sections[sectionIndex];
        
        // Ensure section has items array
        if (!section.items) {
            section.items = [];
        }
        
        // Check if item already exists in this section
        const existingItem = section.items.find(item => item.gear_id == gearId);
        if (existingItem) {
            // Increase quantity
            existingItem.quantity += 1;
            console.log(`📈 Increased quantity of ${gearItem.name} to ${existingItem.quantity}`);
            this.showToast(`📈 Increased ${gearItem.name} quantity to ${existingItem.quantity}`, 'success');
        } else {
            // Add new item
            const newItem = {
                id: `item_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
                gear_id: String(gearItem.id),  // Ensure gear_id is string
                name: gearItem.name,
                weight_g: gearItem.weight_g || 0,
                category: gearItem.category || 'other',
                icon: this.getItemIcon(gearItem.category),
                quantity: 1,
                notes: gearItem.notes || ''
            };
            
            section.items.push(newItem);
            console.log(`✅ Added new item ${gearItem.name} to section ${section.name}`);
            this.showToast(`✅ Added ${gearItem.name} to ${section.name}`, 'success');
        }
        
        // Update the section in the array
        this.currentPack.sections[sectionIndex] = section;
        
        console.log('📦 Section after adding item:', JSON.stringify(section, null, 2));
        
        this.renderPackSections();
        this.updateWeights();
        this.markUnsaved();
    }
    
    toggleSection(sectionId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.collapsed = !section.collapsed;
            this.renderPackSections();
        }
    }
    
    addNewSection() {
        const sectionId = `section_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
        const newSection = {
            id: sectionId,
            name: 'New Section',
            icon: '📦',
            items: [],
            collapsed: false
        };
        
        console.log('➕ Adding new section:', newSection);
        console.log('📦 Sections before add:', JSON.stringify(this.currentPack.sections, null, 2));
        
        this.currentPack.sections.push(newSection);
        
        console.log('📦 Sections after add:', JSON.stringify(this.currentPack.sections, null, 2));
        
        this.renderPackSections();
        this.showToast('➕ Added new section', 'success');
        this.markUnsaved();
        
        // Focus on the name input
        setTimeout(() => {
            $(`.pack-section[data-section-id="${sectionId}"] .section-name-input`).focus().select();
        }, 100);
    }
    
    deleteSection(sectionId) {
        const sectionIndex = this.currentPack.sections.findIndex(s => s.id === sectionId);
        if (sectionIndex !== -1) {
            const section = this.currentPack.sections[sectionIndex];
            if (section.items.length > 0) {
                if (!confirm(`Delete "${section.name}" section with ${section.items.length} items?`)) {
                    return;
                }
            }
            
            this.currentPack.sections.splice(sectionIndex, 1);
            this.renderPackSections();
            this.updateWeights();
            this.showToast(`🗑️ Deleted ${section.name} section`, 'info');
            this.markUnsaved();
        }
    }
    
    updateSectionName(sectionId, newName) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = newName;
            this.markUnsaved();
        }
    }
    
    moveItemBetweenSections(itemId, sourceSectionId, targetSectionId) {
        console.log('🔄 Moving item between sections:', { itemId, sourceSectionId, targetSectionId });
        console.log('📦 Current sections before move:', JSON.stringify(this.currentPack.sections, null, 2));
        
        // Find section indices for better tracking
        const sourceSectionIndex = this.currentPack.sections.findIndex(s => s.id === sourceSectionId);
        const targetSectionIndex = this.currentPack.sections.findIndex(s => s.id === targetSectionId);
        
        if (sourceSectionIndex === -1 || targetSectionIndex === -1) {
            console.error('❌ Source or target section not found', { 
                sourceSectionId, 
                targetSectionId,
                availableSections: this.currentPack.sections.map(s => s.id) 
            });
            return;
        }
        
        const sourceSection = this.currentPack.sections[sourceSectionIndex];
        const targetSection = this.currentPack.sections[targetSectionIndex];
        
        // Ensure sections have items array
        if (!sourceSection.items) sourceSection.items = [];
        if (!targetSection.items) targetSection.items = [];
        
        // Find the item in source section
        const itemIndex = sourceSection.items.findIndex(item => item.id === itemId);
        if (itemIndex === -1) {
            console.error('❌ Item not found in source section', { 
                itemId, 
                availableItems: sourceSection.items.map(i => ({ id: i.id, name: i.name }))
            });
            return;
        }
        
        // Move the item
        const item = sourceSection.items.splice(itemIndex, 1)[0];
        console.log('📦 Moving item:', item);
        
        // Check if item already exists in target section (by gear_id)
        const existingItemIndex = targetSection.items.findIndex(existing => existing.gear_id === item.gear_id);
        if (existingItemIndex !== -1) {
            // Merge quantities
            targetSection.items[existingItemIndex].quantity += item.quantity;
            console.log('📦 Merged with existing item, new quantity:', targetSection.items[existingItemIndex].quantity);
            this.showToast(`🔄 Moved ${item.name} to ${targetSection.name} (quantity: ${targetSection.items[existingItemIndex].quantity})`, 'success');
        } else {
            // Add item to target section
            targetSection.items.push(item);
            console.log('📦 Added item to target section');
            this.showToast(`🔄 Moved ${item.name} from ${sourceSection.name} to ${targetSection.name}`, 'success');
        }
        
        // Update sections array to ensure changes persist
        this.currentPack.sections[sourceSectionIndex] = sourceSection;
        this.currentPack.sections[targetSectionIndex] = targetSection;
        
        console.log('📦 Sections after move:', JSON.stringify(this.currentPack.sections, null, 2));
        
        // Re-render and update
        this.renderPackSections();
        this.updateWeights();
        // Don't auto-save on moves - too many DB calls
        this.markUnsaved();
    }
    
    showDropSuccess($dropZone) {
        $dropZone.css({
            'background': 'rgba(34, 197, 94, 0.2)',
            'border-color': '#22c55e'
        });
        
        setTimeout(() => {
            $dropZone.css({
                'background': '',
                'border-color': ''
            });
        }, 1000);
    }
    
    updateItemQuantity(sectionId, itemId, action) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        const item = section?.items.find(i => i.id === itemId);
        
        if (!item) return;
        
        if (action === 'increase') {
            item.quantity = Math.min(item.quantity + 1, 99);
        } else if (action === 'decrease') {
            item.quantity = Math.max(item.quantity - 1, 1);
        }
        
        this.renderPackSections();
        this.updateWeights();
        this.markUnsaved();
    }
    
    removeItem(sectionId, itemId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            const itemIndex = section.items.findIndex(i => i.id === itemId);
            if (itemIndex !== -1) {
                const item = section.items[itemIndex];
                section.items.splice(itemIndex, 1);
                this.renderPackSections();
                this.updateWeights();
                this.showToast(`🗑️ Removed ${item.name}`, 'info');
                this.markUnsaved();
            }
        }
    }
    
    updateWeights() {
        let totalWeight = 0;
        let totalItems = 0;
        let baseWeight = 0;
        let wornWeight = 0;
        let consumableWeight = 0;
        
        this.currentPack.sections.forEach(section => {
            let sectionWeight = 0;
            let sectionItems = 0;
            
            section.items.forEach(item => {
                const itemWeight = (item.weight_g || 0) * item.quantity;
                sectionWeight += itemWeight;
                sectionItems += item.quantity;
                totalWeight += itemWeight;
                totalItems += item.quantity;
                
                // Categorize weights
                if (section.id === 'worn') {
                    wornWeight += itemWeight;
                } else if (section.id === 'consumables') {
                    consumableWeight += itemWeight;
                } else {
                    baseWeight += itemWeight;
                }
            });
            
            // Update section weight display
            $(`.pack-section[data-section-id="${section.id}"] .section-weight`).text(this.formatWeight(sectionWeight));
        });
        
        // Update overall stats
        $('.pack-stat-value.total-weight').text(this.formatWeight(totalWeight));
        $('.pack-stat-value.total-items').text(totalItems);
        $('.weight-stat-value.base-weight').text(this.formatWeight(baseWeight));
        $('.weight-stat-value.worn-weight').text(this.formatWeight(wornWeight));
        $('.weight-stat-value.consumable-weight').text(this.formatWeight(consumableWeight));
        $('.weight-stat-value.total-weight').text(this.formatWeight(totalWeight));
    }
    
    formatWeight(grams) {
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return Math.round(grams) + 'g';
    }
    
    getItemIcon(category) {
        const icons = {
            shelter: '🏕️',
            sleep: '🛏️',
            clothing: '👕',
            cooking: '🍳',
            water: '💧',
            food: '🍞',
            navigation: '🧭',
            safety: '🚨',
            tools: '🔧',
            electronics: '📱',
            personal: '🧴',
            other: '📦'
        };
        return icons[category] || '📦';
    }
    
    render() {
        this.renderGearList();
        this.renderPackHeader();
        this.renderPackSections();
        this.updateWeights();
    }
    
    renderGearList() {
        if (this.filteredGear.length === 0) {
            $('#gear-list').html(`
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <div class="empty-state-text">No gear found</div>
                    <div class="empty-state-subtext">Try adjusting your search or category filter</div>
                </div>
            `);
            return;
        }
        
        const gearHtml = this.filteredGear.map(item => `
            <div class="gear-item" data-gear-id="${item.id}" draggable="true" title="Drag to add to pack or click button">
                <div class="gear-item-icon">${this.getItemIcon(item.category)}</div>
                <div class="gear-item-info">
                    <div class="gear-item-name">${item.name}</div>
                    <div class="gear-item-weight">${this.formatWeight(item.weight_g || 0)}</div>
                </div>
                <button class="add-btn" onmousedown="event.stopPropagation();" onclick="event.stopPropagation();">+ Add</button>
            </div>
        `).join('');
        
        $('#gear-list').html(gearHtml);
    }
    
    renderPackHeader() {
        $('.pack-name').text(this.currentPack.name);
    }
    
    renderPackSections() {
        const sectionsHtml = this.currentPack.sections.map(section => {
            const itemsHtml = section.items.length > 0 
                ? section.items.map(item => `
                    <div class="pack-item" data-item-id="${item.id}" draggable="true">
                        <div class="pack-item-icon">${item.icon}</div>
                        <div class="pack-item-info">
                            <div class="pack-item-name">${item.name}</div>
                            <div class="pack-item-details">
                                <span>${this.formatWeight(item.weight_g || 0)}</span>
                                <span>${item.category}</span>
                            </div>
                        </div>
                        <div class="pack-item-actions">
                            <button class="qty-btn qty-decrease">-</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn qty-increase">+</button>
                            <button class="remove-btn">×</button>
                        </div>
                    </div>
                `).join('')
                : '<div class="section-empty">No items yet - click "Add" on any gear item to add it here</div>';
            
            return `
                <div class="pack-section" data-section-id="${section.id}">
                    <div class="section-header ${section.collapsed ? 'collapsed' : ''}">
                        <span class="section-toggle">▼</span>
                        <span class="section-icon">${section.icon}</span>
                        <input type="text" class="section-name-input" value="${section.name}" placeholder="Section name">
                        <span class="section-weight">0g</span>
                        <button class="section-delete">×</button>
                    </div>
                    <div class="section-content">
                        <div class="section-items ${section.items.length > 0 ? 'has-items' : ''}">
                            ${itemsHtml}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        $('#pack-sections').html(sectionsHtml + `
            <div class="add-section-btn">+ Add New Section</div>
        `);
    }
    
    markUnsaved() {
        this.hasUnsavedChanges = true;
        $('#save-pack-btn').addClass('has-changes').html('💾 Save Pack *');
    }
    
    markSaved() {
        this.hasUnsavedChanges = false;
        $('#save-pack-btn').removeClass('has-changes').html('💾 Save Pack');
    }
    
    savePack(isAutoSave = false) {
        // Prevent concurrent saves
        if (this.isSaving) {
            console.log('⚠️ Save already in progress, skipping...');
            return;
        }
        
        this.isSaving = true;
        $('#save-pack-btn').prop('disabled', true).html('⏳ Saving...');
        
        console.log('💾 Saving pack...');
        console.log('📦 Sections being saved:', JSON.stringify(this.currentPack.sections, null, 2));
        
        // Debug: Log each section and its items
        this.currentPack.sections.forEach((section, idx) => {
            console.log(`📂 Section ${idx}: ${section.id} (${section.name})`);
            console.log(`   - Has ${section.items ? section.items.length : 0} items`);
            if (section.items) {
                section.items.forEach((item, itemIdx) => {
                    console.log(`     ${itemIdx + 1}. ${item.name} (gear_id: ${item.gear_id})`);
                });
            }
        });
        
        // Ensure all sections have required properties
        const cleanedSections = this.currentPack.sections.map(section => ({
            id: section.id || `section_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
            name: section.name || 'Unnamed Section',
            icon: section.icon || '📦',
            collapsed: section.collapsed || false,
            items: (section.items || []).map(item => ({
                id: item.id,
                gear_id: item.gear_id,
                name: item.name,
                weight_g: item.weight_g,
                category: item.category,
                quantity: item.quantity || 1,
                icon: item.icon,
                notes: item.notes || ''
            }))
        }));
        
        const packData = {
            id: this.currentPack.id,
            name: this.currentPack.name,
            description: this.currentPack.description || '',
            capacity_l: 65,
            type: 'custom',
            sections: cleanedSections
        };
        
        console.log('📤 Final save data:', JSON.stringify(packData, null, 2));
        
        const url = this.currentPack.id 
            ? `/BTT/ajax-handler.php?route=backpacks&id=${this.currentPack.id}`
            : '/BTT/ajax-handler.php?route=backpacks';
        const method = this.currentPack.id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(packData),
            contentType: 'application/json',
            success: (response) => {
                console.log('💾 Save response:', response);
                if (response.success) {
                    if (!this.currentPack.id && response.data?.id) {
                        this.currentPack.id = response.data.id;
                        // Update URL to include ID
                        window.history.replaceState({}, '', `?id=${this.currentPack.id}`);
                    }
                    
                    // Show detailed confirmation with database stats
                    if (response.stats) {
                        const stats = response.stats;
                        let message = `💾 Pack saved! ${stats.total_items} items (${stats.total_quantity} total qty) confirmed in database`;
                        
                        if (stats.sections && stats.sections.length > 0) {
                            // Format section names nicely
                            const sectionNames = {
                                'main': '🎒 Main',
                                'worn': '👕 Worn',
                                'consumables': '🍎 Food',
                                'emergency': '🚨 Emergency',
                                'electronics': '📱 Electronics',
                                'cooking': '🍳 Cooking',
                                'shelter': '🏕️ Shelter'
                            };
                            
                            const sectionDetails = stats.sections.map(s => {
                                const name = sectionNames[s.section] || `📦 ${s.section}`;
                                return `${name}: ${s.item_types}`;
                            }).join(' • ');
                            
                            message += `<br><small style="opacity: 0.8">${sectionDetails}</small>`;
                        }
                        
                        this.showDetailedNotification(message, 'success', !isAutoSave ? 4000 : 2000);
                    } else {
                        this.showToast('💾 Pack saved successfully!', 'success');
                    }
                    
                    // Mark as saved
                    this.markSaved();
                    
                    console.log('✅ Pack saved successfully with stats:', response.stats);
                } else {
                    console.error('❌ Save failed:', response);
                    this.showToast('❌ Failed to save pack: ' + (response.message || 'Unknown error'), 'error');
                    $('#save-pack-btn').prop('disabled', false).html('💾 Save Pack *');
                }
                
                // Reset saving state
                this.isSaving = false;
                $('#save-pack-btn').prop('disabled', false);
            },
            error: (xhr, status, error) => {
                console.error('❌ Save failed:', error);
                this.showToast('❌ Failed to save pack: ' + error, 'error');
                
                // Reset saving state on error
                this.isSaving = false;
                $('#save-pack-btn').prop('disabled', false).html('💾 Save Pack *');
            }
        });
    }
    
    showToast(message, type = 'info') {
        const toast = $(`<div class="toast toast-${type}">${message}</div>`);
        $('body').append(toast);
        
        // Add styles if not already added
        if (!$('#toast-styles').length) {
            $('head').append(`
                <style id="toast-styles">
                    .toast {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: rgba(0, 0, 0, 0.8);
                        color: white;
                        padding: 12px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 1000;
                        animation: slideIn 0.3s ease;
                        max-width: 300px;
                        backdrop-filter: blur(10px);
                    }
                    .toast-success { border-left: 4px solid #10b981; }
                    .toast-error { border-left: 4px solid #ef4444; }
                    .toast-info { border-left: 4px solid #3b82f6; }
                    @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
                    
                    .notification-detailed {
                        position: fixed;
                        top: 20px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: rgba(0, 0, 0, 0.9);
                        color: white;
                        padding: 16px 24px;
                        border-radius: 12px;
                        font-size: 16px;
                        z-index: 1001;
                        animation: dropIn 0.4s ease;
                        max-width: 500px;
                        backdrop-filter: blur(10px);
                        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                        text-align: center;
                        line-height: 1.5;
                    }
                    .notification-detailed.success {
                        border: 2px solid #10b981;
                        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%), rgba(0, 0, 0, 0.9);
                    }
                    .notification-detailed.error {
                        border: 2px solid #ef4444;
                        background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.2) 100%), rgba(0, 0, 0, 0.9);
                    }
                    @keyframes dropIn { 
                        from { 
                            transform: translate(-50%, -100%); 
                            opacity: 0;
                        } 
                        to { 
                            transform: translate(-50%, 0); 
                            opacity: 1;
                        } 
                    }
                </style>
            `);
        }
        
        setTimeout(() => {
            toast.fadeOut(300, () => toast.remove());
        }, 3000);
    }
    
    showDetailedNotification(message, type = 'info', duration = 4000) {
        const notification = $(`<div class="notification-detailed ${type}">${message}</div>`);
        $('body').append(notification);
        
        setTimeout(() => {
            notification.fadeOut(400, () => notification.remove());
        }, duration);
    }
}

// Initialize when document is ready
$(document).ready(() => {
    console.log('🎒 Document ready, initializing SimplePackBuilder...');
    try {
        window.packBuilder = new SimplePackBuilder();
        console.log('✅ SimplePackBuilder initialized successfully');
    } catch (error) {
        console.error('❌ Failed to initialize SimplePackBuilder:', error);
    }
});