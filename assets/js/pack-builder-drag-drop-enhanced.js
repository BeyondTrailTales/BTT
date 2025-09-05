/**
 * Pack Builder Drag & Drop with Section Toggle
 * Complete solution for drag/drop and section collapse functionality
 */

(function($) {
    'use strict';
    
    const PackDragDrop = {
        draggedElement: null,
        draggedData: null,
        dropIndicator: null,
        
        init: function() {
            console.log('Initializing Enhanced Pack Drag & Drop');
            
            // Create drop indicator
            this.dropIndicator = $('<div class="drop-indicator"></div>');
            $('body').append(this.dropIndicator);
            
            // Initialize all features
            this.setupSectionToggles();
            this.setupGearDragging();
            this.setupDropZones();
            this.setupPackItemDragging();
            this.observeChanges();
            
            // Add styles
            this.injectStyles();
        },
        
        // Setup section expand/collapse functionality
        setupSectionToggles: function() {
            const self = this;
            
            // Handle existing sections
            this.initializeSectionToggles();
            
            // Use event delegation for dynamically added sections
            $(document).on('click', '.btn-section-toggle, .section-header', function(e) {
                // Don't toggle if clicking on input or other interactive elements
                if ($(e.target).is('input, button:not(.btn-section-toggle), .section-handle')) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                
                const section = $(this).closest('.pack-section');
                self.toggleSection(section);
            });
        },
        
        initializeSectionToggles: function() {
            $('.pack-section').each(function() {
                const section = $(this);
                const toggleBtn = section.find('.btn-section-toggle');
                const content = section.find('.section-items, .dropzone');
                
                // Set initial state
                if (!section.hasClass('collapsed')) {
                    section.addClass('expanded');
                    toggleBtn.html('▼');
                    content.show();
                }
            });
        },
        
        toggleSection: function(section) {
            const toggleBtn = section.find('.btn-section-toggle');
            const content = section.find('.section-items, .dropzone');
            const isExpanded = section.hasClass('expanded');
            
            if (isExpanded) {
                // Collapse
                section.removeClass('expanded').addClass('collapsed');
                toggleBtn.html('▶');
                content.slideUp(200);
            } else {
                // Expand
                section.removeClass('collapsed').addClass('expanded');
                toggleBtn.html('▼');
                content.slideDown(200);
            }
            
            // Save state to maintain after page refresh (optional)
            const sectionId = section.data('section-id');
            if (sectionId) {
                localStorage.setItem(`pack-section-${sectionId}-collapsed`, !isExpanded);
            }
        },
        
        // Setup gear library dragging
        setupGearDragging: function() {
            const self = this;
            
            $(document).on('dragstart', '.gear-list-item', function(e) {
                self.draggedElement = $(this);
                
                // Get gear data
                const itemDataStr = self.draggedElement.attr('data-item');
                if (itemDataStr) {
                    try {
                        self.draggedData = JSON.parse(itemDataStr);
                        self.draggedData.source = 'library';
                    } catch (err) {
                        console.error('Failed to parse gear data:', err);
                        // Fallback data extraction
                        self.draggedData = {
                            id: self.draggedElement.attr('data-gear-id'),
                            name: self.draggedElement.find('.gear-row-name, .gear-name').first().text(),
                            weight_g: parseInt(self.draggedElement.find('.gear-row-weight, .gear-weight').first().text()) || 0,
                            category: self.draggedElement.find('.gear-row-category, .gear-category').first().text() || 'other',
                            source: 'library'
                        };
                    }
                }
                
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('text/plain', 'gear');
                
                self.draggedElement.addClass('dragging');
                
                // Expand all sections during drag for easier dropping
                $('.pack-section.collapsed').each(function() {
                    $(this).data('was-collapsed', true);
                    self.toggleSection($(this));
                });
            });
            
            $(document).on('dragend', '.gear-list-item', function() {
                $(this).removeClass('dragging');
                self.hideDropIndicator();
                
                // Collapse sections that were auto-expanded
                $('.pack-section').each(function() {
                    if ($(this).data('was-collapsed')) {
                        self.toggleSection($(this));
                        $(this).removeData('was-collapsed');
                    }
                });
                
                self.draggedElement = null;
                self.draggedData = null;
            });
        },
        
        // Setup drop zones
        setupDropZones: function() {
            const self = this;
            
            $(document).on('dragover', '.dropzone, .section-items', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                
                const dropzone = $(this);
                dropzone.addClass('drag-over');
                
                // Show drop position
                const afterElement = self.getDragAfterElement(dropzone[0], e.originalEvent.clientY);
                if (afterElement) {
                    $(afterElement).before(self.dropIndicator);
                } else {
                    dropzone.append(self.dropIndicator);
                }
                self.showDropIndicator();
            });
            
            $(document).on('dragleave', '.dropzone, .section-items', function(e) {
                const dropzone = $(this);
                const relatedTarget = $(e.relatedTarget);
                
                // Only remove class if we're truly leaving the dropzone
                if (!relatedTarget.closest(dropzone).length) {
                    dropzone.removeClass('drag-over');
                }
            });
            
            $(document).on('drop', '.dropzone, .section-items', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const dropzone = $(this);
                dropzone.removeClass('drag-over');
                self.hideDropIndicator();
                
                if (self.draggedData && self.draggedData.source === 'library') {
                    // Adding from library
                    self.addItemToSection(self.draggedData, dropzone);
                } else if (self.draggedElement && self.draggedElement.hasClass('pack-item')) {
                    // Moving existing item
                    const afterElement = self.getDragAfterElement(dropzone[0], e.originalEvent.clientY);
                    if (afterElement) {
                        $(afterElement).before(self.draggedElement);
                    } else {
                        dropzone.append(self.draggedElement);
                    }
                    self.updateWeights();
                    self.markDirty();
                }
                
                self.draggedElement = null;
                self.draggedData = null;
            });
        },
        
        // Setup pack item dragging for reordering
        setupPackItemDragging: function() {
            const self = this;
            
            $(document).on('dragstart', '.pack-item', function(e) {
                self.draggedElement = $(this);
                self.draggedData = self.draggedElement.data('item');
                
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', 'item');
                
                self.draggedElement.addClass('dragging');
            });
            
            $(document).on('dragend', '.pack-item', function() {
                $(this).removeClass('dragging');
                self.hideDropIndicator();
            });
        },
        
        // Add item from library to pack
        addItemToSection: function(gearData, dropzone) {
            const self = this;
            
            // Remove placeholder
            dropzone.find('.dropzone-placeholder').remove();
            
            // Create pack item
            const packItem = $(`
                <div class="pack-item" draggable="true" data-item-id="${gearData.id}">
                    <span class="item-handle">≡</span>
                    <span class="item-icon">${gearData.icon || this.getCategoryIcon(gearData.category)}</span>
                    <span class="item-name">${gearData.name}</span>
                    <input type="number" class="item-qty" value="1" min="1" max="99">
                    <span class="item-weight">${this.formatWeight(gearData.weight_g || 0)}</span>
                    <button class="btn-remove-item" title="Remove">×</button>
                </div>
            `);
            
            // Attach data
            const completeData = {
                id: gearData.id,
                gear_id: gearData.id,
                name: gearData.name,
                weight_g: gearData.weight_g || gearData.weight || 0,
                quantity: 1,
                category: gearData.category || 'other',
                brand: gearData.brand || '',
                price: gearData.price || 0,
                notes: gearData.notes || '',
                icon: gearData.icon || this.getCategoryIcon(gearData.category),
                worn: gearData.worn || false,
                consumable: gearData.consumable || false
            };
            packItem.data('item', completeData);
            
            // Bind events
            packItem.find('.btn-remove-item').on('click', function() {
                packItem.fadeOut(200, function() {
                    packItem.remove();
                    self.updateWeights();
                    self.markDirty();
                    
                    // Add placeholder if empty
                    if (dropzone.find('.pack-item').length === 0) {
                        dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
                    }
                });
            });
            
            packItem.find('.item-qty').on('change', function() {
                const qty = parseInt($(this).val()) || 1;
                const itemData = packItem.data('item');
                itemData.quantity = qty;
                packItem.data('item', itemData);
                
                const weight = (itemData.weight_g || 0) * qty;
                packItem.find('.item-weight').text(self.formatWeight(weight));
                
                self.updateWeights();
                self.markDirty();
            });
            
            // Insert at drop position
            const afterElement = this.getDragAfterElement(dropzone[0], this.dropIndicator.offset().top);
            if (afterElement && afterElement !== this.dropIndicator[0]) {
                $(afterElement).before(packItem);
            } else {
                dropzone.append(packItem);
            }
            
            // Update and notify
            this.updateWeights();
            this.markDirty();
            packItem.hide().fadeIn(300);
            this.showNotification(`Added ${gearData.name} to pack`);
        },
        
        // Helper functions
        getDragAfterElement: function(container, y) {
            const draggableElements = [...container.querySelectorAll('.pack-item:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        },
        
        updateWeights: function() {
            $('.pack-section').each(function() {
                let sectionWeight = 0;
                
                $(this).find('.pack-item').each(function() {
                    const itemData = $(this).data('item');
                    const qty = parseInt($(this).find('.item-qty').val()) || 1;
                    sectionWeight += (itemData.weight_g || 0) * qty;
                });
                
                $(this).find('.section-weight').text(PackDragDrop.formatWeight(sectionWeight));
            });
            
            // Update total via CRUD if available
            if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.updateWeightSummary === 'function') {
                window.PackBuilderCRUD.updateWeightSummary();
            }
        },
        
        markDirty: function() {
            if (window.PackBuilderCRUD) {
                window.PackBuilderCRUD.state.isDirty = true;
            }
        },
        
        formatWeight: function(grams) {
            if (grams >= 1000) {
                return `${(grams / 1000).toFixed(2)}kg`;
            }
            return `${Math.round(grams)}g`;
        },
        
        getCategoryIcon: function(category) {
            const icons = {
                'shelter': '⛺',
                'sleep': '🛌',
                'cooking': '🔥',
                'water': '💧',
                'navigation': '🧭',
                'clothing': '👕',
                'hygiene': '🧼',
                'first-aid': '🏥',
                'electronics': '🔋',
                'food': '🍎',
                'other': '📦'
            };
            return icons[category] || '📦';
        },
        
        showDropIndicator: function() {
            this.dropIndicator.addClass('visible');
        },
        
        hideDropIndicator: function() {
            this.dropIndicator.removeClass('visible');
        },
        
        showNotification: function(message) {
            const notification = $(`
                <div class="drag-notification">
                    ✅ ${message}
                </div>
            `);
            $('body').append(notification);
            notification.fadeIn(300).delay(2000).fadeOut(300, function() {
                $(this).remove();
            });
        },
        
        observeChanges: function() {
            const self = this;
            const observer = new MutationObserver(function(mutations) {
                // Re-initialize section toggles for new sections
                self.initializeSectionToggles();
            });
            
            const sectionsContainer = document.getElementById('sections-list');
            if (sectionsContainer) {
                observer.observe(sectionsContainer, { childList: true, subtree: true });
            }
        },
        
        injectStyles: function() {
            const styles = `
                <style id="pack-drag-drop-styles">
                    /* Drag & Drop Styles */
                    .dragging {
                        opacity: 0.5;
                        cursor: grabbing !important;
                    }
                    
                    .drag-over {
                        background: rgba(88, 204, 2, 0.1) !important;
                        border: 2px dashed #58cc02 !important;
                        box-shadow: 0 0 10px rgba(88, 204, 2, 0.3) !important;
                    }
                    
                    .drop-indicator {
                        height: 3px;
                        background: #58cc02;
                        margin: 4px 0;
                        opacity: 0;
                        transition: opacity 0.2s;
                        position: relative;
                    }
                    
                    .drop-indicator.visible {
                        opacity: 1;
                    }
                    
                    .drop-indicator::before {
                        content: '';
                        position: absolute;
                        left: 0;
                        top: -5px;
                        width: 12px;
                        height: 12px;
                        background: #58cc02;
                        border-radius: 50%;
                        box-shadow: 0 0 5px rgba(88, 204, 2, 0.5);
                    }
                    
                    /* Gear items cursor */
                    .gear-list-item {
                        cursor: grab !important;
                    }
                    
                    .gear-list-item:active {
                        cursor: grabbing !important;
                    }
                    
                    /* Pack items */
                    .pack-item {
                        cursor: move !important;
                        transition: all 0.2s;
                    }
                    
                    .pack-item:hover {
                        transform: translateX(2px);
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    
                    /* Section toggle styles */
                    .btn-section-toggle {
                        background: none;
                        border: none;
                        color: #58cc02;
                        font-size: 16px;
                        cursor: pointer;
                        padding: 4px 8px;
                        transition: transform 0.2s;
                        user-select: none;
                    }
                    
                    .btn-section-toggle:hover {
                        transform: scale(1.2);
                    }
                    
                    .pack-section.collapsed .section-items,
                    .pack-section.collapsed .dropzone {
                        display: none;
                    }
                    
                    .pack-section.expanded .section-items,
                    .pack-section.expanded .dropzone {
                        display: block;
                    }
                    
                    .section-header {
                        cursor: pointer;
                        user-select: none;
                    }
                    
                    .section-header:hover {
                        background: rgba(88, 204, 2, 0.05);
                    }
                    
                    /* Placeholder */
                    .dropzone-placeholder {
                        color: rgba(255, 255, 255, 0.5);
                        text-align: center;
                        padding: 20px;
                        font-style: italic;
                    }
                    
                    /* Notification */
                    .drag-notification {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: #58cc02;
                        color: white;
                        padding: 12px 20px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                        z-index: 10000;
                        font-weight: 500;
                    }
                    
                    /* Handle */
                    .item-handle {
                        cursor: grab;
                        user-select: none;
                        padding: 0 8px;
                        color: #666;
                    }
                    
                    .item-handle:active {
                        cursor: grabbing;
                    }
                    
                    /* Ensure dropzones have min height */
                    .dropzone, .section-items {
                        min-height: 50px;
                        padding: 8px;
                        border-radius: 6px;
                        transition: all 0.2s;
                    }
                </style>
            `;
            
            if (!$('#pack-drag-drop-styles').length) {
                $('head').append(styles);
            }
        }
    };
    
    // Initialize when ready
    $(document).ready(function() {
        setTimeout(function() {
            PackDragDrop.init();
        }, 500);
    });
    
    // Export for global access
    window.PackDragDrop = PackDragDrop;
    
})(jQuery);