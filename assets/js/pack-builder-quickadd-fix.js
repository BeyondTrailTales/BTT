/**
 * Pack Builder Quick Add Fix
 * Ensures gear data is properly attached when using quickAddItem
 */

(function() {
    'use strict';
    
    // Wait for PackBuilder to be loaded
    function waitForPackBuilder() {
        if (typeof PackBuilder !== 'undefined' && PackBuilder.quickAddItem) {
            overrideQuickAddItem();
        } else {
            setTimeout(waitForPackBuilder, 100);
        }
    }
    
    function overrideQuickAddItem() {
        // Save original function
        const originalQuickAddItem = PackBuilder.quickAddItem;
        
        // Override with version that properly attaches data
        PackBuilder.quickAddItem = function(gearId) {
            const gear = this.state.gearLibrary.find(g => g.id == gearId);
            if (!gear) {
                console.error('Gear not found:', gearId);
                return;
            }

            console.log('Quick adding item:', gear);

            // Find the first available section or create a default one
            let targetSection = $('.pack-section').first();
            if (targetSection.length === 0) {
                // If no pack builder sections, check if we're in builder view
                if ($('#view-builder').is(':visible')) {
                    // Create a default section
                    const sectionHtml = `
                        <div class="pack-section" data-section-id="main">
                            <div class="section-header">
                                <span class="section-handle">≡</span>
                                <input type="text" class="section-name" value="Main Pack">
                                <span class="section-weight">0g</span>
                                <button class="btn-section-toggle">▼</button>
                            </div>
                            <div class="section-items dropzone" data-section="main">
                                <div class="dropzone-placeholder">Drop gear here</div>
                            </div>
                        </div>
                    `;
                    $('#sections-list').append(sectionHtml);
                    targetSection = $('.pack-section').first();
                } else {
                    alert('Please create or edit a pack first');
                    return;
                }
            }

            const dropzone = targetSection.find('.dropzone, .section-items');
            
            // Remove placeholder if exists
            dropzone.find('.dropzone-placeholder').remove();

            // Create pack item element with proper data attached
            const packItem = $(`
                <div class="pack-item" data-item-id="${gear.id}">
                    <span class="item-handle">≡</span>
                    <span class="item-icon">${gear.icon || '📦'}</span>
                    <span class="item-name">${gear.name}</span>
                    <input type="number" class="item-qty" value="1" min="1" max="99">
                    <span class="item-weight">${this.formatWeight(gear.weight_g || gear.weight || 0)}</span>
                    <button class="btn-remove-item" title="Remove">×</button>
                </div>
            `);

            // CRITICAL: Attach the complete gear data to the element
            packItem.data('item', {
                id: gear.id,
                gear_id: gear.id,
                name: gear.name,
                weight_g: gear.weight_g || gear.weight || 0,
                quantity: 1,
                category: gear.category || 'other',
                brand: gear.brand || '',
                price: gear.price || 0,
                notes: gear.notes || '',
                icon: gear.icon || '📦',
                worn: gear.worn || false,
                consumable: gear.consumable || false
            });
            
            // Bind events
            packItem.find('.btn-remove-item').on('click', function() {
                packItem.fadeOut(200, function() {
                    packItem.remove();
                    if (window.PackBuilderCRUD) {
                        PackBuilderCRUD.state.isDirty = true;
                        PackBuilderCRUD.updateWeightSummary();
                    }
                    
                    // Add placeholder if no items left
                    if (dropzone.find('.pack-item').length === 0) {
                        dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
                    }
                });
            });

            packItem.find('.item-qty').on('change', function() {
                const qty = parseInt($(this).val()) || 1;
                const weight = (gear.weight_g || gear.weight || 0) * qty;
                packItem.find('.item-weight').text(PackBuilder.formatWeight(weight));
                
                // Update the data
                const itemData = packItem.data('item');
                itemData.quantity = qty;
                packItem.data('item', itemData);
                
                if (window.PackBuilderCRUD) {
                    PackBuilderCRUD.state.isDirty = true;
                    PackBuilderCRUD.updateWeightSummary();
                }
            });

            // Add to section
            dropzone.append(packItem);
            
            // Mark as dirty and update weights
            if (window.PackBuilderCRUD) {
                PackBuilderCRUD.state.isDirty = true;
                PackBuilderCRUD.updateWeightSummary();
            }
            
            // Show success notification
            const notification = $(`
                <div class="toast toast-success" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
                    ✅ ${gear.name} added to pack
                </div>
            `);
            $('body').append(notification);
            notification.fadeIn(300).delay(2000).fadeOut(300, function() {
                $(this).remove();
            });
            
            console.log('Item added successfully');
        };
    }
    
    // Start the process
    waitForPackBuilder();
    
})();