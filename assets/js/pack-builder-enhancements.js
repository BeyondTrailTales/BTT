/**
 * Pack Builder Enhancements
 * Adds inline editing and improved UI for pack builder
 */

(function($) {
    'use strict';

    // Wait for pack builder to be initialized
    $(document).ready(function() {
        // Override the addItemToPack method with inline editing
        if (window.packBuilder) {
            const originalAddItemToPack = window.packBuilder.addItemToPack;
            
            window.packBuilder.addItemToPack = function(gearElement, dropzone) {
                const itemId = gearElement.data('item-id');
                const item = this.gear.find(g => g.id == itemId);
                
                if (!item) return;
                
                // Remove placeholder
                dropzone.find('.drop-hint').remove();
                
                // Create pack item with inline quantity editor
                const packItem = $(`
                    <div class="pack-item" data-item-id="${item.id}">
                        <span class="item-handle">≡</span>
                        <span class="item-icon">${item.icon}</span>
                        <span class="item-name" title="${item.name}">${item.name}</span>
                        <div class="item-qty-wrapper">
                            <button class="qty-decrease" data-action="decrease">−</button>
                            <span class="item-qty-display">1</span>
                            <button class="qty-increase" data-action="increase">+</button>
                        </div>
                        <span class="item-weight" data-base-weight="${item.weight}">${item.weight}g</span>
                        <button class="btn-remove-item" title="Remove">×</button>
                    </div>
                `);
                
                dropzone.append(packItem);
                
                this.updateWeights();
                this.updateSectionStats();
                this.isDirty = true;
                
                // Use Duolingo mini confirmation
                if (window.DuoConfirm) {
                    DuoConfirm.itemAdded(item.name);
                } else {
                    this.showToast(`Added ${item.name}`, 'success');
                }
            };
            
            // Override displayLoadedPack to use inline editing
            const originalDisplayLoadedPack = window.packBuilder.displayLoadedPack;
            
            window.packBuilder.displayLoadedPack = function(pack) {
                // Clear current pack
                $('.pack-item').remove();
                $('.drop-hint').show();
                
                // Set pack details
                $('.pack-name-input').val(pack.name || 'Untitled Pack');
                $('.pack-notes').val(pack.description || pack.notes || '');
                
                // Helper function to create pack item with inline editor
                const createPackItem = (gearItem, quantity) => {
                    return $(`
                        <div class="pack-item" data-item-id="${gearItem.id}">
                            <span class="item-handle">≡</span>
                            <span class="item-icon">${gearItem.icon || '📦'}</span>
                            <span class="item-name" title="${gearItem.name}">${gearItem.name}</span>
                            <div class="item-qty-wrapper">
                                <button class="qty-decrease" data-action="decrease">−</button>
                                <span class="item-qty-display">${quantity || 1}</span>
                                <button class="qty-increase" data-action="increase">+</button>
                            </div>
                            <span class="item-weight" data-base-weight="${gearItem.weight}">${(gearItem.weight * (quantity || 1))}g</span>
                            <button class="btn-remove-item" title="Remove">×</button>
                        </div>
                    `);
                };
                
                // Load items from sections if available
                if (pack.sections && Array.isArray(pack.sections)) {
                    pack.sections.forEach(section => {
                        if (section.items && Array.isArray(section.items)) {
                            section.items.forEach(item => {
                                const gearItemId = item.gear_id || item.gear_item_id;
                                const gearItem = this.gear.find(g => g.id == gearItemId);
                                
                                if (gearItem) {
                                    const sectionId = section.id || section.section_type || 'main';
                                    let targetDropzone = $(`.dropzone[data-section="${sectionId}"]`);
                                    
                                    if (targetDropzone.length === 0) {
                                        targetDropzone = $(`.dropzone[data-section="main"]`);
                                    }
                                    
                                    targetDropzone.find('.drop-hint').remove();
                                    const packItem = createPackItem(gearItem, item.quantity);
                                    targetDropzone.append(packItem);
                                }
                            });
                        }
                    });
                }
                // Fallback to items array if sections not available
                else if (pack.items && Array.isArray(pack.items)) {
                    pack.items.forEach(item => {
                        const gearItemId = item.gear_id || item.gear_item_id;
                        const gearItem = this.gear.find(g => g.id == gearItemId);
                        
                        if (gearItem) {
                            let sectionId = item.section || 'main';
                            let targetDropzone = $(`.dropzone[data-section="${sectionId}"]`);
                            
                            if (targetDropzone.length === 0) {
                                targetDropzone = $(`.dropzone[data-section="main"]`);
                            }
                            
                            targetDropzone.find('.drop-hint').remove();
                            const packItem = createPackItem(gearItem, item.quantity);
                            targetDropzone.append(packItem);
                        }
                    });
                }
                
                // Update weights and stats
                this.updateWeights();
                this.updateSectionStats();
                this.isDirty = false;
            };
        }
        
        // Quantity adjustment handlers
        $(document).on('click', '.qty-decrease, .qty-increase', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $button = $(this);
            const $wrapper = $button.closest('.item-qty-wrapper');
            const $display = $wrapper.find('.item-qty-display');
            const $packItem = $button.closest('.pack-item');
            const $weight = $packItem.find('.item-weight');
            
            let currentQty = parseInt($display.text()) || 1;
            const baseWeight = parseInt($weight.data('base-weight')) || 0;
            const action = $button.data('action');
            
            if (action === 'increase' && currentQty < 99) {
                currentQty++;
            } else if (action === 'decrease' && currentQty > 1) {
                currentQty--;
            }
            
            // Update display
            $display.text(currentQty);
            $weight.text((baseWeight * currentQty) + 'g');
            
            // Flash animation
            $wrapper.addClass('flash');
            setTimeout(() => $wrapper.removeClass('flash'), 200);
            
            // Update pack builder weights
            if (window.packBuilder) {
                packBuilder.updateWeights();
                packBuilder.updateSectionStats();
                packBuilder.isDirty = true;
            }
        });
        
        // Remove item handler
        $(document).on('click', '.btn-remove-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $item = $(this).closest('.pack-item');
            if (window.packBuilder) {
                packBuilder.removeItem($item);
            }
        });
        
        // Override savePack to use inline editing values
        if (window.packBuilder) {
            const originalSavePack = window.packBuilder.savePack;
            
            window.packBuilder.savePack = function() {
                // Check if user is logged in
                if (!BTT.userId || BTT.userId === 'guest') {
                    this.showToast('Please log in to save packs', 'error');
                    return;
                }

                // If we have a pack ID, update instead
                if (this.currentPackId) {
                    this.updatePack();
                    return;
                }
                
                // Gather pack data with inline editing values
                const sections = [];
                const allItems = [];
                let totalWeight = 0;
                let baseWeight = 0;
                let itemCount = 0;
                
                $('.pack-section').each(function() {
                    const sectionName = $(this).find('.section-name').val() || 'Main Compartment';
                    const sectionId = $(this).data('section') || $(this).attr('data-section');
                    const sectionItems = [];
                    
                    $(this).find('.pack-item').each(function() {
                        const itemId = $(this).data('item-id');
                        // Use inline quantity display instead of input
                        const qty = parseInt($(this).find('.item-qty-display').text()) || 1;
                        const weight = parseInt($(this).find('.item-weight').data('base-weight')) || 0;
                        const totalItemWeight = weight * qty;
                        const itemName = $(this).find('.item-name').text();
                        
                        // Find the gear item to get more details
                        const gearItem = window.packBuilder.gear.find(g => g.id == itemId);
                        
                        const item = {
                            gear_id: itemId,
                            name: itemName,
                            quantity: qty,
                            weight_g: weight,
                            category: gearItem?.category || 'other',
                            worn: $(this).closest('.worn-section').length > 0 ? 1 : 0,
                            consumable: 0,
                            section: sectionId,
                            position: $(this).index()
                        };
                        
                        sectionItems.push(item);
                        allItems.push(item);
                        
                        totalWeight += totalItemWeight;
                        itemCount += qty;
                        
                        if (!$(this).closest('.worn-section').length) {
                            baseWeight += totalItemWeight;
                        }
                    });
                    
                    sections.push({
                        id: sectionId,
                        name: sectionName,
                        section_type: sectionId,
                        items: sectionItems
                    });
                });
                
                const packData = {
                    name: $('.pack-name-input').val() || 'Untitled Pack',
                    description: $('.pack-notes').val() || '',
                    base_weight_g: baseWeight,
                    total_weight_g: totalWeight,
                    base_weight: baseWeight / 1000,
                    capacity_l: 65,
                    user_id: BTT.userId,
                    sections: sections,
                    items: allItems
                };
                
                console.log('Saving pack with ' + itemCount + ' items');
                
                // Save to API
                $.ajax({
                    url: BTT.apiUrl + '/?route=backpacks',
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': BTT.csrfToken
                    },
                    data: JSON.stringify(packData),
                    success: (response) => {
                        if (response.success) {
                            this.currentPack = response.data;
                            this.currentPackId = response.data.id;
                            this.isDirty = false;
                            
                            // Calculate weight for display
                            const weightKg = (baseWeight / 1000).toFixed(1) + 'kg';
                            
                            // Show Duolingo confirmation
                            if (window.DuoConfirm) {
                                DuoConfirm.packSaved(itemCount, weightKg);
                            } else {
                                this.showToast('Pack saved successfully! ✅', 'success');
                            }
                            
                            // Update URL if we have an ID
                            if (response.data && response.data.id) {
                                const newUrl = window.location.pathname + '?id=' + response.data.id;
                                window.history.pushState({}, '', newUrl);
                            }
                            
                            // Refresh My Packs view
                            this.loadUserPacks();
                        } else {
                            if (window.DuoConfirm) {
                                DuoConfirm.error(response.message || 'Failed to save pack');
                            } else {
                                this.showToast('Failed to save pack: ' + (response.message || 'Unknown error'), 'error');
                            }
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('Save error:', error);
                        this.showToast('Failed to save pack. Please try again.', 'error');
                    }
                });
            };
            
            // Also override updatePack for editing existing packs
            const originalUpdatePack = window.packBuilder.updatePack;
            
            window.packBuilder.updatePack = function() {
                if (!this.currentPackId) {
                    this.savePack();
                    return;
                }
                
                // Use same gathering logic as savePack
                const sections = [];
                const allItems = [];
                let totalWeight = 0;
                let baseWeight = 0;
                let itemCount = 0;
                
                $('.pack-section').each(function() {
                    const sectionName = $(this).find('.section-name').val() || 'Main Compartment';
                    const sectionId = $(this).data('section') || $(this).attr('data-section');
                    const sectionItems = [];
                    
                    $(this).find('.pack-item').each(function() {
                        const itemId = $(this).data('item-id');
                        const qty = parseInt($(this).find('.item-qty-display').text()) || 1;
                        const weight = parseInt($(this).find('.item-weight').data('base-weight')) || 0;
                        const totalItemWeight = weight * qty;
                        const itemName = $(this).find('.item-name').text();
                        
                        const gearItem = window.packBuilder.gear.find(g => g.id == itemId);
                        
                        const item = {
                            gear_id: itemId,
                            name: itemName,
                            quantity: qty,
                            weight_g: weight,
                            category: gearItem?.category || 'other',
                            worn: 0,
                            consumable: 0,
                            section: sectionId,
                            position: $(this).index()
                        };
                        
                        sectionItems.push(item);
                        allItems.push(item);
                        totalWeight += totalItemWeight;
                        itemCount += qty;
                        baseWeight += totalItemWeight;
                    });
                    
                    sections.push({
                        id: sectionId,
                        name: sectionName,
                        section_type: sectionId,
                        items: sectionItems
                    });
                });
                
                const packData = {
                    name: $('.pack-name-input').val() || 'Untitled Pack',
                    description: $('.pack-notes').val() || '',
                    base_weight_g: baseWeight,
                    total_weight_g: totalWeight,
                    base_weight: baseWeight / 1000,
                    user_id: BTT.userId,
                    sections: sections,
                    items: allItems
                };
                
                // Update via API
                $.ajax({
                    url: BTT.apiUrl + '/?route=backpacks&id=' + this.currentPackId,
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': BTT.csrfToken
                    },
                    data: JSON.stringify(packData),
                    success: (response) => {
                        if (response.success) {
                            this.isDirty = false;
                            
                            // Calculate weight for display
                            const weightKg = (baseWeight / 1000).toFixed(1) + 'kg';
                            
                            // Show Duolingo confirmation
                            if (window.DuoConfirm) {
                                DuoConfirm.packUpdated(itemCount, weightKg);
                            } else {
                                this.showToast('Pack updated successfully! ✅', 'success');
                            }
                            
                            this.loadUserPacks();
                        } else {
                            if (window.DuoConfirm) {
                                DuoConfirm.error('Failed to update pack');
                            } else {
                                this.showToast('Failed to update pack', 'error');
                            }
                        }
                    },
                    error: () => {
                        this.showToast('Failed to update pack', 'error');
                    }
                });
            };
        }
        
        // Override gather pack data for saving
        if (window.packBuilder) {
            const originalGatherPackData = window.packBuilder.gatherPackData;
            
            window.packBuilder.gatherPackData = function() {
                const packData = {
                    name: $('.pack-name-input').val() || 'Untitled Pack',
                    description: $('.pack-notes').val() || '',
                    sections: [],
                    items: []
                };
                
                $('.pack-section').each(function() {
                    const sectionName = $(this).find('.section-name').val();
                    const sectionId = $(this).data('section') || $(this).attr('data-section');
                    const sectionItems = [];
                    
                    $(this).find('.pack-item').each(function() {
                        const itemId = $(this).data('item-id');
                        const qty = parseInt($(this).find('.item-qty-display').text()) || 1;
                        const weight = parseInt($(this).find('.item-weight').data('base-weight')) || 0;
                        const itemName = $(this).find('.item-name').text();
                        
                        // Find the gear item to get more details
                        const gearItem = window.packBuilder.gear.find(g => g.id == itemId);
                        
                        const item = {
                            gear_id: itemId,
                            name: itemName,
                            quantity: qty,
                            weight_g: weight,
                            category: gearItem?.category || 'other',
                            worn: $(this).closest('.worn-section').length > 0 ? 1 : 0,
                            consumable: 0,
                            section: sectionId,
                            position: $(this).index()
                        };
                        
                        sectionItems.push(item);
                        packData.items.push(item);
                    });
                    
                    if (sectionItems.length > 0 || sectionId === 'main') {
                        packData.sections.push({
                            id: sectionId,
                            name: sectionName,
                            section_type: sectionId,
                            items: sectionItems
                        });
                    }
                });
                
                return packData;
        };
            
            // Override exportPack for Duolingo confirmations
            const originalExportPack = window.packBuilder.exportPack;
            
            window.packBuilder.exportPack = function() {
                const packData = this.gatherPackData();
                const json = JSON.stringify(packData, null, 2);
                const blob = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = `${packData.name.replace(/\s+/g, '-')}.json`;
                a.click();
                
                URL.revokeObjectURL(url);
                
                // Show Duolingo confirmation
                if (window.DuoConfirm) {
                    DuoConfirm.packExported(packData.name);
                } else {
                    this.showToast('Pack exported', 'success');
                }
            };
            
            // Override deletePack for Duolingo confirmations
            const originalDeletePack = window.packBuilder.deletePack;
            
            window.packBuilder.deletePack = function(packId) {
                if (!confirm('Are you sure you want to delete this pack?')) {
                    return;
                }
                
                // Get pack name before deletion
                const pack = this.userPacks.find(p => p.id == packId);
                const packName = pack ? pack.name : 'Pack';
                
                $.ajax({
                    url: BTT.apiUrl + '/?route=backpacks&id=' + packId,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-Token': BTT.csrfToken
                    },
                    success: (response) => {
                        if (response.success) {
                            // Show Duolingo confirmation
                            if (window.DuoConfirm) {
                                DuoConfirm.packDeleted(packName);
                            } else {
                                this.showToast('Pack deleted successfully', 'success');
                            }
                            this.loadUserPacks();
                        } else {
                            if (window.DuoConfirm) {
                                DuoConfirm.error('Failed to delete pack');
                            } else {
                                this.showToast('Failed to delete pack', 'error');
                            }
                        }
                    },
                    error: () => {
                        if (window.DuoConfirm) {
                            DuoConfirm.error('Failed to delete pack');
                        } else {
                            this.showToast('Failed to delete pack', 'error');
                        }
                    }
                });
            };
        }
    });
    
    // Add CSS animation for quantity changes
    const style = document.createElement('style');
    style.innerHTML = `
        .item-qty-wrapper.flash {
            animation: qtyFlash 0.2s ease-out;
        }
        
        @keyframes qtyFlash {
            0% { background: rgba(88, 204, 2, 0.3); transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { background: rgba(88, 204, 2, 0.1); transform: scale(1); }
        }
        
        /* Save indicator */
        .save-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 0.75rem 1.25rem;
            background: #58cc02;
            color: white;
            border-radius: 0.5rem;
            font-weight: 600;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(88, 204, 2, 0.3);
        }
        
        .save-indicator.show {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
    
    // Add save indicator
    window.showSaveIndicator = function(message) {
        let indicator = $('.save-indicator');
        if (!indicator.length) {
            indicator = $('<div class="save-indicator"></div>');
            $('body').append(indicator);
        }
        
        indicator.text(message).addClass('show');
        setTimeout(() => indicator.removeClass('show'), 3000);
    };

})(jQuery);
