/**
 * Complete Pack Builder Drag and Drop System
 * Handles all drag-drop functionality with persistence
 */

(function($) {
    'use strict';

    const PackBuilderDragDrop = {
        
        init: function() {
            console.log('🎯 Initializing complete drag-drop system...');
            this.setupGearLibraryDragDrop();
            this.setupPackSectionDragDrop();
            this.setupPersistence();
            this.restoreState();
        },

        // ==================== Gear Library to Sections ====================
        setupGearLibraryDragDrop: function() {
            const self = this;

            // Make gear items draggable
            $(document).off('dragstart', '.gear-item').on('dragstart', '.gear-item', function(e) {
                const $item = $(this);
                const gearData = {
                    id: $item.data('gear-id'),
                    name: $item.data('name'),
                    weight_g: parseInt($item.data('weight')) || 0,
                    category: $item.data('category'),
                    icon: $item.data('icon'),
                    source: 'library'
                };

                e.originalEvent.dataTransfer.setData('application/json', JSON.stringify(gearData));
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                
                $item.addClass('dragging');
                $('.gear-drop-zone').addClass('drag-active');
                
                console.log('🎒 Dragging from library:', gearData.name);
            });

            // Clean up after drag
            $(document).off('dragend', '.gear-item').on('dragend', '.gear-item', function(e) {
                $(this).removeClass('dragging');
                $('.gear-drop-zone').removeClass('drag-active drag-over');
            });
        },

        // ==================== Pack Sections Drag Drop ====================
        setupPackSectionDragDrop: function() {
            const self = this;

            // Make pack items draggable between sections
            $(document).off('dragstart', '.pack-item').on('dragstart', '.pack-item', function(e) {
                const $item = $(this);
                const itemData = {
                    id: $item.data('item-id'),
                    name: $item.data('name'),
                    weight_g: parseInt($item.data('weight')) || 0,
                    quantity: parseInt($item.data('quantity')) || 1,
                    category: $item.data('category'),
                    icon: $item.data('icon'),
                    source: 'section',
                    currentSection: $item.closest('.pack-section').data('section')
                };

                e.originalEvent.dataTransfer.setData('application/json', JSON.stringify(itemData));
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                
                $item.addClass('dragging');
                $('.gear-drop-zone').addClass('drag-active');
                
                console.log('🔄 Moving item between sections:', itemData.name);
            });

            // Clean up after moving items
            $(document).off('dragend', '.pack-item').on('dragend', '.pack-item', function(e) {
                $(this).removeClass('dragging');
                $('.gear-drop-zone').removeClass('drag-active drag-over');
            });

            // Handle drop zones
            $(document).off('dragover', '.gear-drop-zone').on('dragover', '.gear-drop-zone', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(this).addClass('drag-over');
            });

            $(document).off('dragleave', '.gear-drop-zone').on('dragleave', '.gear-drop-zone', function(e) {
                $(this).removeClass('drag-over');
            });

            $(document).off('drop', '.gear-drop-zone').on('drop', '.gear-drop-zone', function(e) {
                e.preventDefault();
                const $dropZone = $(this);
                const targetSection = $dropZone.data('section');
                
                try {
                    const itemData = JSON.parse(e.originalEvent.dataTransfer.getData('application/json'));
                    
                    if (itemData.source === 'library') {
                        self.addGearToSection(itemData, targetSection);
                    } else if (itemData.source === 'section') {
                        self.moveItemBetweenSections(itemData, targetSection);
                    }
                } catch (error) {
                    console.error('Drop error:', error);
                }

                $dropZone.removeClass('drag-over drag-active');
            });
        },

        // ==================== Add Gear from Library ====================
        addGearToSection: function(gearData, targetSection) {
            console.log('➕ Adding gear to section:', gearData.name, '→', targetSection);
            
            // Check for duplicates
            const $targetSection = $(`.pack-section[data-section="${targetSection}"]`);
            const existingItem = $targetSection.find(`.pack-item[data-gear-id="${gearData.id}"]`);
            
            if (existingItem.length > 0) {
                // Increment quantity instead of duplicating
                const currentQuantity = parseInt(existingItem.data('quantity')) || 1;
                const newQuantity = currentQuantity + 1;
                existingItem.data('quantity', newQuantity);
                existingItem.find('.item-quantity').text(`×${newQuantity}`);
                this.updateSectionWeight(targetSection);
                this.savePackState();
                console.log('📈 Increased quantity to:', newQuantity);
                return;
            }

            // Create new pack item
            const itemId = 'item_' + Date.now();
            const itemHtml = this.createPackItemHtml(gearData, itemId, targetSection);
            
            const $dropZone = $targetSection.find('.gear-drop-zone');
            
            // Remove empty message if exists
            $dropZone.find('.drop-hint').remove();
            
            // Add item to section
            $dropZone.append(itemHtml);
            
            // Update weights and save
            this.updateSectionWeight(targetSection);
            this.savePackState();
            
            // Show success feedback
            this.showItemAddedFeedback(gearData.name, targetSection);
        },

        // ==================== Move Items Between Sections ====================
        moveItemBetweenSections: function(itemData, targetSection) {
            if (itemData.currentSection === targetSection) {
                console.log('Item already in target section');
                return;
            }

            console.log('🔄 Moving item:', itemData.name, itemData.currentSection, '→', targetSection);
            
            // Remove from current section
            $(`.pack-item[data-item-id="${itemData.id}"]`).remove();
            
            // Add to target section
            const $targetSection = $(`.pack-section[data-section="${targetSection}"]`);
            const $dropZone = $targetSection.find('.gear-drop-zone');
            
            // Remove empty message if exists
            $dropZone.find('.drop-hint').remove();
            
            // Create item in new section
            const itemHtml = this.createPackItemHtml(itemData, itemData.id, targetSection);
            $dropZone.append(itemHtml);
            
            // Update weights for both sections
            this.updateSectionWeight(itemData.currentSection);
            this.updateSectionWeight(targetSection);
            this.savePackState();
            
            // Show feedback
            this.showItemMovedFeedback(itemData.name, itemData.currentSection, targetSection);
        },

        // ==================== HTML Generation ====================
        createPackItemHtml: function(itemData, itemId, section) {
            const weight = itemData.weight_g || 0;
            const quantity = itemData.quantity || 1;
            
            return `
                <div class="pack-item" draggable="true" 
                     data-item-id="${itemId}" 
                     data-gear-id="${itemData.id}" 
                     data-name="${itemData.name}" 
                     data-weight="${weight}" 
                     data-quantity="${quantity}" 
                     data-category="${itemData.category}" 
                     data-icon="${itemData.icon}">
                    <div class="pack-item-content">
                        <div class="pack-item-icon">${itemData.icon || '📦'}</div>
                        <div class="pack-item-details">
                            <div class="pack-item-name">${itemData.name}</div>
                            <div class="pack-item-meta">
                                <span class="pack-item-weight">${this.formatWeight(weight)}</span>
                                <span class="item-quantity">×${quantity}</span>
                            </div>
                        </div>
                        <div class="pack-item-actions">
                            <button class="btn-quantity-down" data-item-id="${itemId}">-</button>
                            <button class="btn-quantity-up" data-item-id="${itemId}">+</button>
                            <button class="btn-remove-item" data-item-id="${itemId}">×</button>
                        </div>
                    </div>
                </div>
            `;
        },

        // ==================== Weight Management ====================
        updateSectionWeight: function(sectionId) {
            const $section = $(`.pack-section[data-section="${sectionId}"]`);
            let totalWeight = 0;
            let totalItems = 0;

            $section.find('.pack-item').each(function() {
                const weight = parseInt($(this).data('weight')) || 0;
                const quantity = parseInt($(this).data('quantity')) || 1;
                totalWeight += weight * quantity;
                totalItems += quantity;
            });

            $section.find('.section-weight').text(this.formatWeight(totalWeight));
            
            // Update overall pack weight
            this.updateTotalPackWeight();
        },

        updateTotalPackWeight: function() {
            let totalWeight = 0;
            let totalItems = 0;

            $('.pack-section').each(function() {
                const $section = $(this);
                $section.find('.pack-item').each(function() {
                    const weight = parseInt($(this).data('weight')) || 0;
                    const quantity = parseInt($(this).data('quantity')) || 1;
                    totalWeight += weight * quantity;
                    totalItems += quantity;
                });
            });

            $('#quick-total-weight').text(this.formatWeight(totalWeight));
            $('#quick-total-items').text(totalItems);
        },

        formatWeight: function(grams) {
            if (grams >= 1000) {
                return (grams / 1000).toFixed(1) + 'kg';
            }
            return grams + 'g';
        },

        // ==================== Persistence ====================
        setupPersistence: function() {
            const self = this;

            // Save on quantity changes
            $(document).off('click', '.btn-quantity-up').on('click', '.btn-quantity-up', function(e) {
                e.preventDefault();
                const itemId = $(this).data('item-id');
                self.changeItemQuantity(itemId, 1);
            });

            $(document).off('click', '.btn-quantity-down').on('click', '.btn-quantity-down', function(e) {
                e.preventDefault();
                const itemId = $(this).data('item-id');
                self.changeItemQuantity(itemId, -1);
            });

            // Remove items
            $(document).off('click', '.btn-remove-item').on('click', '.btn-remove-item', function(e) {
                e.preventDefault();
                const itemId = $(this).data('item-id');
                self.removeItem(itemId);
            });
        },

        changeItemQuantity: function(itemId, change) {
            const $item = $(`.pack-item[data-item-id="${itemId}"]`);
            const currentQuantity = parseInt($item.data('quantity')) || 1;
            const newQuantity = Math.max(1, currentQuantity + change);
            
            $item.data('quantity', newQuantity);
            $item.find('.item-quantity').text(`×${newQuantity}`);
            
            const sectionId = $item.closest('.pack-section').data('section');
            this.updateSectionWeight(sectionId);
            this.savePackState();
        },

        removeItem: function(itemId) {
            const $item = $(`.pack-item[data-item-id="${itemId}"]`);
            const sectionId = $item.closest('.pack-section').data('section');
            
            $item.remove();
            
            this.updateSectionWeight(sectionId);
            this.savePackState();
            
            // Show empty message if section is now empty
            const $section = $(`.pack-section[data-section="${sectionId}"]`);
            if ($section.find('.pack-item').length === 0) {
                const $dropZone = $section.find('.gear-drop-zone');
                $dropZone.append('<p class="drop-hint">Drag gear here or use the gear library</p>');
            }
        },

        // ==================== State Persistence ====================
        savePackState: function() {
            const currentPackId = window.PackBuilderCRUD?.state?.currentPackId;
            if (!currentPackId) return;

            const packState = this.collectPackState();
            localStorage.setItem('btt_current_pack_id', currentPackId);
            localStorage.setItem('btt_pack_state_' + currentPackId, JSON.stringify(packState));
            
            console.log('💾 Saved pack state for pack ID:', currentPackId);
        },

        restoreState: function() {
            const savedPackId = localStorage.getItem('btt_current_pack_id');
            if (savedPackId && window.PackBuilderCRUD) {
                console.log('🔄 Restoring pack state for ID:', savedPackId);
                setTimeout(() => {
                    if (typeof window.PackBuilderCRUD.loadPackForEdit === 'function') {
                        window.PackBuilderCRUD.loadPackForEdit(savedPackId);
                    }
                }, 1000);
            }
        },

        collectPackState: function() {
            const sections = {};
            
            $('.pack-section').each(function() {
                const sectionId = $(this).data('section');
                const items = [];
                
                $(this).find('.pack-item').each(function() {
                    items.push({
                        id: $(this).data('item-id'),
                        gear_id: $(this).data('gear-id'),
                        name: $(this).data('name'),
                        weight_g: parseInt($(this).data('weight')) || 0,
                        quantity: parseInt($(this).data('quantity')) || 1,
                        category: $(this).data('category')
                    });
                });
                
                sections[sectionId] = {
                    name: $(this).find('.section-name').val(),
                    items: items
                };
            });
            
            return {
                sections: sections,
                timestamp: Date.now()
            };
        },

        // ==================== Feedback ====================
        showItemAddedFeedback: function(itemName, sectionName) {
            const message = `✅ Added "${itemName}" to ${sectionName}`;
            this.showToast(message, 'success');
        },

        showItemMovedFeedback: function(itemName, fromSection, toSection) {
            const message = `🔄 Moved "${itemName}" from ${fromSection} to ${toSection}`;
            this.showToast(message, 'info');
        },

        showToast: function(message, type) {
            // Simple toast notification
            const toast = $(`<div class="pack-toast pack-toast-${type}">${message}</div>`);
            $('body').append(toast);
            
            setTimeout(() => {
                toast.addClass('show');
            }, 100);
            
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };

    // Auto-initialize when document is ready
    $(document).ready(function() {
        // Wait for other scripts to load
        setTimeout(() => {
            PackBuilderDragDrop.init();
        }, 800);
    });

    // Make available globally
    window.PackBuilderDragDrop = PackBuilderDragDrop;

})(jQuery);