/**
 * Fixed Pack Builder - Reliable Save & Drag/Drop
 */

class FixedPackBuilder {
    constructor() {
        // Core pack data - this is what we save
        this.currentPack = {
            id: null,
            name: 'My New Backpack',
            description: '',
            sections: [
                { id: 'main', name: 'Main Pack', icon: '🎒', items: [], collapsed: false },
                { id: 'worn', name: 'Worn Items', icon: '👕', items: [], collapsed: false },
                { id: 'consumables', name: 'Consumables', icon: '🍎', items: [], collapsed: false }
            ]
        };
        
        this.gearLibrary = [];
        this.hasUnsavedChanges = false;
        this.isSaving = false;
        
        this.init();
    }
    
    init() {
        console.log('🎒 Initializing Fixed Pack Builder...');
        
        // Check jQuery
        if (typeof $ === 'undefined') {
            console.error('❌ jQuery not loaded!');
            return;
        }
        
        this.loadGearLibrary();
        this.loadExistingPack();
        this.setupEventHandlers();
        this.render();
        
        console.log('✅ Pack Builder Ready');
    }
    
    setupEventHandlers() {
        const self = this;
        
        // Save button
        $('#save-pack-btn').on('click', () => {
            self.savePack();
        });
        
        // Add item button click
        $(document).on('click', '.gear-item .add-btn', function(e) {
            e.stopPropagation();
            const gearId = $(this).closest('.gear-item').data('gear-id');
            self.addItemToPack(gearId, 'main');
        });
        
        // Setup drag and drop
        this.setupDragDrop();
        
        // Pack name editing
        $(document).on('click', '.pack-name', function() {
            const currentName = $(this).text();
            $(this).replaceWith(`<input type="text" class="pack-name-input" value="${currentName}">`);
            $('.pack-name-input').focus().select();
        });
        
        $(document).on('blur keypress', '.pack-name-input', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            const newName = $(this).val() || 'My Backpack';
            self.currentPack.name = newName;
            $(this).replaceWith(`<h2 class="pack-name">${newName}</h2>`);
            self.markUnsaved();
        });
        
        // Section controls
        $(document).on('click', '.add-section-btn', () => {
            self.addSection();
        });
        
        $(document).on('click', '.section-header', function(e) {
            if ($(e.target).hasClass('section-delete') || $(e.target).hasClass('section-name-input')) return;
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
            const newName = $(this).val();
            self.updateSectionName(sectionId, newName);
        });
        
        $(document).on('click', '.remove-btn', function() {
            const itemId = $(this).closest('.pack-item').data('item-id');
            const sectionId = $(this).closest('.pack-section').data('section-id');
            self.removeItem(sectionId, itemId);
        });
    }
    
    setupDragDrop() {
        const self = this;
        
        // Make gear items draggable from library
        $(document).on('dragstart', '.gear-item', function(e) {
            const gearId = $(this).data('gear-id');
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            e.originalEvent.dataTransfer.setData('gear-id', gearId);
            e.originalEvent.dataTransfer.setData('drag-type', 'new-item');
            $(this).addClass('dragging');
        });
        
        $(document).on('dragend', '.gear-item', function() {
            $(this).removeClass('dragging');
            $('.section-items').removeClass('drag-ready drag-over');
        });
        
        // Make pack items draggable between sections
        $(document).on('dragstart', '.pack-item', function(e) {
            const itemId = $(this).data('item-id');
            const sourceSectionId = $(this).closest('.pack-section').data('section-id');
            
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('item-id', itemId);
            e.originalEvent.dataTransfer.setData('source-section', sourceSectionId);
            e.originalEvent.dataTransfer.setData('drag-type', 'move-item');
            
            $(this).addClass('dragging');
            $('.section-items').not($(this).closest('.section-items')).addClass('drag-ready');
        });
        
        $(document).on('dragend', '.pack-item', function() {
            $(this).removeClass('dragging');
            $('.section-items').removeClass('drag-ready drag-over');
        });
        
        // Make sections droppable
        $(document).on('dragover', '.section-items', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('drag-over');
        });
        
        $(document).on('dragleave', '.section-items', function() {
            $(this).removeClass('drag-over');
        });
        
        $(document).on('drop', '.section-items', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const targetSectionId = $(this).closest('.pack-section').data('section-id');
            const dragType = e.originalEvent.dataTransfer.getData('drag-type');
            
            if (dragType === 'new-item') {
                // Adding new item from gear library
                const gearId = e.originalEvent.dataTransfer.getData('gear-id');
                if (gearId) {
                    self.addItemToPack(gearId, targetSectionId);
                }
            } else if (dragType === 'move-item') {
                // Moving item between sections
                const itemId = e.originalEvent.dataTransfer.getData('item-id');
                const sourceSectionId = e.originalEvent.dataTransfer.getData('source-section');
                
                if (itemId && sourceSectionId && sourceSectionId !== targetSectionId) {
                    self.moveItemBetweenSections(itemId, sourceSectionId, targetSectionId);
                }
            }
        });
    }
    
    loadGearLibrary() {
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'GET',
            success: (response) => {
                this.gearLibrary = response || [];
                this.renderGearList();
            },
            error: () => {
                console.error('Failed to load gear');
                this.renderGearList();
            }
        });
    }
    
    loadExistingPack() {
        const urlParams = new URLSearchParams(window.location.search);
        const packId = urlParams.get('id');
        
        if (!packId) return;
        
        this.currentPack.id = packId;
        
        $.ajax({
            url: `/BTT/ajax-handler.php?route=backpacks&id=${packId}`,
            method: 'GET',
            success: (response) => {
                if (response.success && response.data) {
                    const data = response.data;
                    this.currentPack.id = data.id;
                    this.currentPack.name = data.name || 'My Backpack';
                    this.currentPack.description = data.description || '';
                    
                    // Load sections with items
                    if (data.sections && Array.isArray(data.sections)) {
                        this.currentPack.sections = data.sections.map(section => ({
                            id: section.id,
                            name: section.name || 'Unnamed',
                            icon: section.icon || '📦',
                            items: section.items || [],
                            collapsed: section.collapsed || false
                        }));
                    }
                    
                    this.render();
                }
            }
        });
    }
    
    addItemToPack(gearId, sectionId) {
        const gear = this.gearLibrary.find(g => g.id == gearId);
        if (!gear) return;
        
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        // Check if item already exists
        const existing = section.items.find(item => item.gear_id == gearId);
        if (existing) {
            existing.quantity++;
        } else {
            section.items.push({
                id: 'item_' + Date.now(),
                gear_id: gear.id,
                name: gear.name,
                weight_g: gear.weight_g || 0,
                category: gear.category || 'other',
                quantity: 1,
                icon: this.getCategoryIcon(gear.category)
            });
        }
        
        this.renderSections();
        this.updateWeights();
        this.markUnsaved();
        this.showToast(`Added ${gear.name} to ${section.name}`);
    }
    
    removeItem(sectionId, itemId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const index = section.items.findIndex(i => i.id === itemId);
        if (index !== -1) {
            const item = section.items[index];
            section.items.splice(index, 1);
            this.renderSections();
            this.updateWeights();
            this.markUnsaved();
            this.showToast(`Removed ${item.name}`);
        }
    }
    
    moveItemBetweenSections(itemId, sourceSectionId, targetSectionId) {
        const sourceSection = this.currentPack.sections.find(s => s.id === sourceSectionId);
        const targetSection = this.currentPack.sections.find(s => s.id === targetSectionId);
        
        if (!sourceSection || !targetSection) return;
        
        // Find and remove item from source
        const itemIndex = sourceSection.items.findIndex(i => i.id === itemId);
        if (itemIndex === -1) return;
        
        const item = sourceSection.items.splice(itemIndex, 1)[0];
        
        // Check if same item already exists in target
        const existingItem = targetSection.items.find(i => i.gear_id === item.gear_id);
        if (existingItem) {
            existingItem.quantity += item.quantity;
            this.showToast(`Merged ${item.name} (total: ${existingItem.quantity})`, 'info');
        } else {
            targetSection.items.push(item);
            this.showToast(`Moved ${item.name} to ${targetSection.name}`, 'success');
        }
        
        this.renderSections();
        this.updateWeights();
        this.markUnsaved();
    }
    
    addSection() {
        const newSection = {
            id: 'section_' + Date.now(),
            name: 'New Section',
            icon: '📦',
            items: [],
            collapsed: false
        };
        
        this.currentPack.sections.push(newSection);
        this.renderSections();
        this.markUnsaved();
        this.showToast('Added new section', 'success');
    }
    
    deleteSection(sectionId) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        if (section.items.length > 0) {
            if (!confirm(`Delete "${section.name}" with ${section.items.length} items?`)) {
                return;
            }
        }
        
        const index = this.currentPack.sections.findIndex(s => s.id === sectionId);
        this.currentPack.sections.splice(index, 1);
        this.renderSections();
        this.updateWeights();
        this.markUnsaved();
        this.showToast(`Deleted ${section.name}`, 'info');
    }
    
    updateSectionName(sectionId, newName) {
        const section = this.currentPack.sections.find(s => s.id === sectionId);
        if (section) {
            section.name = newName || 'Unnamed Section';
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
    
    savePack() {
        if (this.isSaving) return;
        
        this.isSaving = true;
        $('#save-pack-btn').prop('disabled', true).html('⏳ Saving...');
        
        const packData = {
            id: this.currentPack.id,
            name: this.currentPack.name,
            description: this.currentPack.description,
            sections: this.currentPack.sections
        };
        
        console.log('Saving pack data:', packData);
        
        const url = this.currentPack.id 
            ? `/BTT/ajax-handler.php?route=backpacks&id=${this.currentPack.id}`
            : '/BTT/ajax-handler.php?route=backpacks';
        const method = this.currentPack.id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(packData),
            success: (response) => {
                console.log('Save response:', response);
                
                if (!this.currentPack.id && response.data?.id) {
                    this.currentPack.id = response.data.id;
                    window.history.replaceState({}, '', `?id=${this.currentPack.id}`);
                }
                
                if (response.stats) {
                    this.showSaveConfirmation(response.stats);
                } else {
                    this.showToast('Pack saved!', 'success');
                }
                
                this.hasUnsavedChanges = false;
                $('#save-pack-btn').removeClass('has-changes').html('💾 Save Pack');
            },
            error: (xhr) => {
                console.error('Save failed:', xhr);
                this.showToast('Failed to save pack', 'error');
                $('#save-pack-btn').html('💾 Save Pack *');
            },
            complete: () => {
                this.isSaving = false;
                $('#save-pack-btn').prop('disabled', false);
            }
        });
    }
    
    showSaveConfirmation(stats) {
        let msg = `✅ Saved! ${stats.total_items} items confirmed in database`;
        if (stats.sections && stats.sections.length > 0) {
            msg += '<br><small>';
            stats.sections.forEach(s => {
                msg += `${s.section}: ${s.item_types} items • `;
            });
            msg = msg.slice(0, -3) + '</small>';
        }
        this.showToast(msg, 'success', 4000);
    }
    
    markUnsaved() {
        this.hasUnsavedChanges = true;
        $('#save-pack-btn').addClass('has-changes').html('💾 Save Pack *');
    }
    
    getCategoryIcon(category) {
        const icons = {
            shelter: '🏕️', sleep: '🛏️', clothing: '👕',
            cooking: '🍳', water: '💧', food: '🍞',
            navigation: '🧭', safety: '🚨', tools: '🔧',
            electronics: '📱', personal: '🧴', other: '📦'
        };
        return icons[category] || '📦';
    }
    
    updateWeights() {
        let totalWeight = 0;
        let totalItems = 0;
        
        this.currentPack.sections.forEach(section => {
            section.items.forEach(item => {
                totalWeight += (item.weight_g || 0) * item.quantity;
                totalItems += item.quantity;
            });
        });
        
        $('.total-weight').text(this.formatWeight(totalWeight));
        $('.total-items').text(totalItems);
    }
    
    formatWeight(grams) {
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return Math.round(grams) + 'g';
    }
    
    render() {
        this.renderGearList();
        this.renderPackHeader();
        this.renderSections();
        this.updateWeights();
    }
    
    renderGearList() {
        const html = this.gearLibrary.map(item => `
            <div class="gear-item" data-gear-id="${item.id}" draggable="true">
                <div class="gear-item-icon">${this.getCategoryIcon(item.category)}</div>
                <div class="gear-item-info">
                    <div class="gear-item-name">${item.name}</div>
                    <div class="gear-item-weight">${this.formatWeight(item.weight_g)}</div>
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
        const html = this.currentPack.sections.map(section => {
            const itemsHtml = section.items.map(item => `
                <div class="pack-item" data-item-id="${item.id}" draggable="true">
                    <div class="pack-item-icon">${item.icon || this.getCategoryIcon(item.category)}</div>
                    <div class="pack-item-info">
                        <div class="pack-item-name">${item.name}</div>
                        <div class="pack-item-details">
                            <span>${this.formatWeight(item.weight_g)}</span>
                            <span>Qty: ${item.quantity}</span>
                        </div>
                    </div>
                    <button class="remove-btn">×</button>
                </div>
            `).join('');
            
            return `
                <div class="pack-section" data-section-id="${section.id}">
                    <div class="section-header ${section.collapsed ? 'collapsed' : ''}">
                        <span class="section-toggle">▼</span>
                        <span class="section-icon">${section.icon}</span>
                        <input type="text" class="section-name-input" value="${section.name}" placeholder="Section name">
                        <span class="section-weight">${this.formatWeight(
                            section.items.reduce((sum, item) => sum + (item.weight_g * item.quantity), 0)
                        )}</span>
                        <button class="section-delete">×</button>
                    </div>
                    <div class="section-content" ${section.collapsed ? 'style="display:none"' : ''}>
                        <div class="section-items">
                            ${itemsHtml || '<div class="section-empty">Drop items here</div>'}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        $('#pack-sections').html(html + '<div class="add-section-btn">+ Add New Section</div>');
    }
    
    showToast(message, type = 'info', duration = 3000) {
        const toast = $(`<div class="toast toast-${type}">${message}</div>`);
        $('body').append(toast);
        
        // Add animation
        setTimeout(() => toast.addClass('show'), 10);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

// Initialize
$(document).ready(() => {
    console.log('Document ready, starting Fixed Pack Builder...');
    window.packBuilder = new FixedPackBuilder();
});