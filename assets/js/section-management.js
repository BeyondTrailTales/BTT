/**
 * Section Management Enhancement
 * Properly initialize dropzones for new sections and ensure database saving
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        if (!window.packBuilder) {
            console.error('Pack builder not initialized');
            return;
        }
        
        // Override the addSection method
        const originalAddSection = window.packBuilder.addSection;
        
        window.packBuilder.addSection = function() {
            const sectionId = 'custom-' + Date.now();
            const sectionName = prompt('Enter section name:', 'Custom Section') || 'Custom Section';
            
            const newSection = $(`
                <div class="pack-section" data-section="${sectionId}">
                    <div class="section-header">
                        <button class="section-toggle">▼</button>
                        <span class="section-icon">📦</span>
                        <input type="text" class="section-name" value="${sectionName}" placeholder="Section name">
                        <span class="section-weight">0g</span>
                        <button class="btn-delete-section" title="Delete section">×</button>
                    </div>
                    <div class="section-content">
                        <div class="gear-drop-zone dropzone" data-section="${sectionId}">
                            <p class="drop-hint">Drag gear here or click to add items</p>
                        </div>
                    </div>
                </div>
            `);
            
            // Add styles for the delete button
            newSection.find('.btn-delete-section').css({
                'margin-left': 'auto',
                'background': 'none',
                'border': 'none',
                'color': '#ef4444',
                'cursor': 'pointer',
                'padding': '4px 8px',
                'font-size': '1.2em'
            });
            
            // Append to pack sections
            $('#pack-sections').append(newSection);
            
            // Initialize as dropzone with jQuery UI
            const dropzone = newSection.find('.dropzone');
            
            // Make it droppable
            dropzone.droppable({
                accept: '.gear-item, .pack-item',
                tolerance: 'pointer',
                hoverClass: 'drag-over',
                drop: function(event, ui) {
                    const item = ui.draggable;
                    const targetDropzone = $(this);
                    
                    if (item.hasClass('gear-item')) {
                        // Adding new gear from the library
                        packBuilder.addItemToPack(item, targetDropzone);
                        
                        // Don't remove the original from the gear library
                        ui.helper.remove();
                    } else if (item.hasClass('pack-item')) {
                        // Moving existing item between sections
                        item.detach().appendTo(targetDropzone);
                        targetDropzone.find('.drop-hint').hide();
                        
                        packBuilder.updateWeights();
                        packBuilder.updateSectionStats();
                        packBuilder.isDirty = true;
                    }
                }
            });
            
            // Make it sortable for reordering items within
            dropzone.sortable({
                items: '.pack-item',
                handle: '.item-handle',
                connectWith: '.dropzone',
                placeholder: 'pack-item-placeholder',
                tolerance: 'pointer',
                receive: function(event, ui) {
                    $(this).find('.drop-hint').hide();
                    packBuilder.updateWeights();
                    packBuilder.updateSectionStats();
                    packBuilder.isDirty = true;
                }
            });
            
            // Section toggle functionality
            newSection.find('.section-toggle').on('click', function(e) {
                e.stopPropagation();
                const content = newSection.find('.section-content');
                const isExpanded = content.is(':visible');
                
                if (isExpanded) {
                    content.slideUp(200);
                    $(this).text('▶');
                } else {
                    content.slideDown(200);
                    $(this).text('▼');
                }
            });
            
            // Delete section functionality
            newSection.find('.btn-delete-section').on('click', (e) => {
                e.stopPropagation();
                const sectionName = newSection.find('.section-name').val();
                const itemCount = newSection.find('.pack-item').length;
                
                // Confirm if section has items
                if (itemCount > 0) {
                    if (!confirm(`This section "${sectionName}" contains ${itemCount} item(s). Delete anyway?`)) {
                        return;
                    }
                }
                
                // Animate removal
                newSection.fadeOut(300, () => {
                    newSection.remove();
                    packBuilder.updateWeights();
                    packBuilder.updateSectionStats();
                    
                    if (window.DuoConfirm) {
                        DuoConfirm.packDeleted(`Section: ${sectionName}`);
                    } else {
                        packBuilder.showToast(`Deleted section: ${sectionName}`, 'info');
                    }
                    
                    packBuilder.isDirty = true;
                });
            });
            
            // Section name change
            newSection.find('.section-name').on('change', () => {
                packBuilder.isDirty = true;
            });
            
            // Show success message
            if (window.DuoConfirm) {
                DuoConfirm.itemAdded(`Section: ${sectionName}`);
            } else {
                this.showToast('New section added', 'success');
            }
            
            this.isDirty = true;
        };
        
        // Re-initialize dropzones for existing sections on page load
        function initializeAllDropzones() {
            $('.dropzone').each(function() {
                const $dropzone = $(this);
                
                // Skip if already initialized
                if ($dropzone.hasClass('ui-droppable')) {
                    return;
                }
                
                // Make droppable
                $dropzone.droppable({
                    accept: '.gear-item, .pack-item',
                    tolerance: 'pointer',
                    hoverClass: 'drag-over',
                    drop: function(event, ui) {
                        const item = ui.draggable;
                        const targetDropzone = $(this);
                        
                        if (item.hasClass('gear-item')) {
                            packBuilder.addItemToPack(item, targetDropzone);
                            ui.helper.remove();
                        } else if (item.hasClass('pack-item')) {
                            item.detach().appendTo(targetDropzone);
                            targetDropzone.find('.drop-hint').hide();
                            
                            packBuilder.updateWeights();
                            packBuilder.updateSectionStats();
                            packBuilder.isDirty = true;
                        }
                    }
                });
                
                // Make sortable
                $dropzone.sortable({
                    items: '.pack-item',
                    handle: '.item-handle',
                    connectWith: '.dropzone',
                    placeholder: 'pack-item-placeholder',
                    tolerance: 'pointer',
                    receive: function(event, ui) {
                        $(this).find('.drop-hint').hide();
                        packBuilder.updateWeights();
                        packBuilder.updateSectionStats();
                        packBuilder.isDirty = true;
                    }
                });
            });
        }
        
        // Initialize on ready and when switching views
        setTimeout(initializeAllDropzones, 500);
        
        // Re-initialize when switching to builder view
        $(document).on('click', '.view-tab[data-view="builder"]', function() {
            setTimeout(initializeAllDropzones, 100);
        });
        
        // Override gatherPackData to include custom sections properly
        const originalGatherPackData = window.packBuilder.gatherPackData;
        
        window.packBuilder.gatherPackData = function() {
            const packData = {
                name: $('.pack-name-input').val() || 'Untitled Pack',
                description: $('.pack-notes').val() || '',
                sections: [],
                items: []
            };
            
            let totalWeight = 0;
            let totalItems = 0;
            
            // Gather all sections including custom ones
            $('.pack-section').each(function() {
                const $section = $(this);
                const sectionId = $section.data('section') || 'main';
                const sectionName = $section.find('.section-name').val() || 
                                  $section.find('.section-title').text() || 
                                  'Unnamed Section';
                
                const sectionData = {
                    id: sectionId,
                    name: sectionName,
                    section_type: sectionId,
                    items: []
                };
                
                // Gather items in this section
                $section.find('.pack-item').each(function() {
                    const $item = $(this);
                    const itemId = $item.data('item-id');
                    const qty = parseInt($item.find('.item-qty-display').text()) || 
                               parseInt($item.find('.item-qty').val()) || 1;
                    const baseWeight = parseInt($item.find('.item-weight').data('base-weight')) || 0;
                    const itemName = $item.find('.item-name').text();
                    
                    const gearItem = packBuilder.gear.find(g => g.id == itemId);
                    
                    const itemData = {
                        gear_id: itemId,
                        name: itemName,
                        quantity: qty,
                        weight_g: baseWeight,
                        category: gearItem?.category || 'other',
                        worn: 0,
                        consumable: 0,
                        section: sectionId,
                        position: $item.index()
                    };
                    
                    sectionData.items.push(itemData);
                    packData.items.push(itemData);
                    
                    totalWeight += baseWeight * qty;
                    totalItems += qty;
                });
                
                packData.sections.push(sectionData);
            });
            
            packData.total_weight_g = totalWeight;
            packData.total_items = totalItems;
            
            return packData;
        };
    });
    
    // Add styles for sections
    const styles = `
        <style>
        /* Section Styles */
        .pack-section {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .pack-section:hover {
            border-color: rgba(88, 204, 2, 0.3);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .section-toggle {
            background: none;
            border: none;
            color: #58cc02;
            font-size: 0.875rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .section-icon {
            font-size: 1.25rem;
        }
        
        .section-name {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid transparent;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            color: white;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .section-name:hover,
        .section-name:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(88, 204, 2, 0.3);
        }
        
        .section-weight {
            color: #58cc02;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0 0.75rem;
        }
        
        .btn-delete-section {
            background: none;
            border: none;
            color: rgba(239, 68, 68, 0.7);
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            font-size: 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            opacity: 0.5;
        }
        
        .section-header:hover .btn-delete-section {
            opacity: 1;
        }
        
        .btn-delete-section:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .section-content {
            padding: 0.75rem;
        }
        
        .dropzone {
            min-height: 80px;
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .dropzone.drag-over {
            border-color: #58cc02;
            background: rgba(88, 204, 2, 0.05);
        }
        
        .drop-hint {
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.875rem;
            padding: 1rem;
        }
        
        .pack-item-placeholder {
            height: 50px;
            background: rgba(88, 204, 2, 0.1);
            border: 2px dashed #58cc02;
            border-radius: 0.5rem;
            margin-bottom: 0.375rem;
        }
        
        /* Add Section Button Enhancement */
        #add-section {
            width: 100%;
            margin-top: 1rem;
            border-style: dashed;
            background: rgba(88, 204, 2, 0.05);
        }
        
        #add-section:hover {
            background: rgba(88, 204, 2, 0.1);
            border-style: solid;
        }
        </style>
    `;
    
    if (!$('#section-management-styles').length) {
        $('head').append(styles);
    }
    
})(jQuery);
