/**
 * Pack Builder Persistence System
 * Maintains pack state and active pack across page refreshes
 */

$(document).ready(function() {
    console.log('📦 Initializing pack persistence system...');
    
    const PackPersistence = {
        STORAGE_KEY: 'btt_pack_builder_state',
        ACTIVE_PACK_KEY: 'btt_active_pack_id',
        
        init: function() {
            this.bindEvents();
            this.restoreActivePackOnLoad();
        },
        
        bindEvents: function() {
            // Save state when pack is opened
            $(document).on('packOpened', (e, packId) => {
                this.saveActivePack(packId);
                console.log('📝 Saved active pack:', packId);
            });
            
            // Save state when pack is modified
            $(document).on('packModified', (e, packData) => {
                this.savePackState(packData);
                console.log('💾 Saved pack state');
            });
            
            // Clear active pack when returning to list
            $(document).on('click', '#btn-back-to-list', () => {
                this.clearActivePack();
                console.log('🔙 Cleared active pack - returning to list');
            });
            
            // Save state before page unload
            $(window).on('beforeunload', () => {
                this.saveCurrentPackState();
            });
        },
        
        saveActivePack: function(packId) {
            try {
                localStorage.setItem(this.ACTIVE_PACK_KEY, packId);
                console.log('✅ Active pack saved:', packId);
            } catch (error) {
                console.warn('⚠️ Failed to save active pack:', error);
            }
        },
        
        getActivePack: function() {
            try {
                return localStorage.getItem(this.ACTIVE_PACK_KEY);
            } catch (error) {
                console.warn('⚠️ Failed to get active pack:', error);
                return null;
            }
        },
        
        clearActivePack: function() {
            try {
                localStorage.removeItem(this.ACTIVE_PACK_KEY);
                localStorage.removeItem(this.STORAGE_KEY);
            } catch (error) {
                console.warn('⚠️ Failed to clear active pack:', error);
            }
        },
        
        savePackState: function(packData) {
            try {
                const state = {
                    packId: packData.id,
                    packData: packData,
                    timestamp: Date.now(),
                    sections: this.getCurrentSectionsState()
                };
                
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify(state));
            } catch (error) {
                console.warn('⚠️ Failed to save pack state:', error);
            }
        },
        
        getCurrentSectionsState: function() {
            const sections = {};
            
            $('.pack-section').each(function() {
                const sectionId = $(this).data('section');
                const items = [];
                
                $(this).find('.pack-item').each(function() {
                    const $item = $(this);
                    items.push({
                        itemId: $item.data('item-id'),
                        gearId: $item.data('gear-id'),
                        name: $item.data('name'),
                        weight: parseInt($item.data('weight')) || 0,
                        quantity: parseInt($item.data('quantity')) || 1,
                        category: $item.data('category'),
                        icon: $item.data('icon')
                    });
                });
                
                sections[sectionId] = {
                    name: $(this).find('.section-name').val() || sectionId,
                    items: items,
                    expanded: !$(this).hasClass('collapsed')
                };
            });
            
            return sections;
        },
        
        saveCurrentPackState: function() {
            const activePackId = this.getActivePack();
            if (!activePackId) return;
            
            // Get current pack data if available
            const packData = window.PackBuilder?.state?.currentPack || { id: activePackId };
            this.savePackState(packData);
        },
        
        restoreActivePackOnLoad: function() {
            setTimeout(() => {
                const activePackId = this.getActivePack();
                if (activePackId) {
                    console.log('🔄 Restoring active pack:', activePackId);
                    this.openPack(activePackId);
                }
            }, 1500); // Wait for other systems to initialize
        },
        
        openPack: function(packId) {
            // Try different ways to open the pack depending on what's available
            if (window.PackBuilder && typeof window.PackBuilder.loadPack === 'function') {
                window.PackBuilder.loadPack(packId);
            } else if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.loadPackForEdit === 'function') {
                window.PackBuilderCRUD.loadPackForEdit(packId);
            } else {
                // Fallback: trigger click on pack item if it exists
                const $packItem = $(`.pack-item-card[data-pack-id="${packId}"], .pack-card[data-pack-id="${packId}"]`);
                if ($packItem.length) {
                    $packItem.find('.btn-edit-pack, .btn-open-pack').first().click();
                } else {
                    console.warn('⚠️ Could not find way to open pack:', packId);
                    this.clearActivePack(); // Clear if pack no longer exists
                }
            }
        },
        
        restorePackState: function(packId) {
            try {
                const savedState = localStorage.getItem(this.STORAGE_KEY);
                if (!savedState) return false;
                
                const state = JSON.parse(savedState);
                if (state.packId !== packId) return false;
                
                console.log('🔄 Restoring pack state:', state);
                
                // Restore sections
                if (state.sections) {
                    Object.keys(state.sections).forEach(sectionId => {
                        const sectionData = state.sections[sectionId];
                        const $section = $(`.pack-section[data-section="${sectionId}"]`);
                        
                        if ($section.length) {
                            // Restore section name
                            $section.find('.section-name').val(sectionData.name);
                            
                            // Restore items
                            const $dropZone = $section.find('.gear-drop-zone');
                            $dropZone.empty();
                            
                            sectionData.items.forEach(itemData => {
                                const itemHtml = this.createRestoredItemHtml(itemData);
                                $dropZone.append(itemHtml);
                            });
                            
                            // Restore expanded state
                            if (sectionData.expanded) {
                                $section.removeClass('collapsed');
                            } else {
                                $section.addClass('collapsed');
                            }
                        }
                    });
                }
                
                return true;
            } catch (error) {
                console.warn('⚠️ Failed to restore pack state:', error);
                return false;
            }
        },
        
        createRestoredItemHtml: function(itemData) {
            const formatWeight = (grams) => {
                if (grams >= 1000) {
                    return (grams / 1000).toFixed(1) + 'kg';
                }
                return grams + 'g';
            };
            
            return `
                <div class="pack-item" draggable="true"
                     data-item-id="${itemData.itemId}"
                     data-gear-id="${itemData.gearId}"
                     data-name="${itemData.name}"
                     data-weight="${itemData.weight}"
                     data-quantity="${itemData.quantity}"
                     data-category="${itemData.category}"
                     data-icon="${itemData.icon}">
                    <div class="pack-item-content">
                        <div class="pack-item-icon">${itemData.icon}</div>
                        <div class="pack-item-details">
                            <div class="pack-item-name">${itemData.name}</div>
                            <div class="pack-item-meta">
                                <span class="pack-item-weight">${formatWeight(itemData.weight)}</span>
                                <span class="pack-item-quantity" data-quantity="${itemData.quantity}">×${itemData.quantity}</span>
                            </div>
                        </div>
                        <div class="pack-item-actions">
                            <button class="btn-quantity-down" title="Decrease quantity">-</button>
                            <button class="btn-quantity-up" title="Increase quantity">+</button>
                            <button class="btn-remove-item" title="Remove item">×</button>
                        </div>
                    </div>
                </div>
            `;
        }
    };
    
    // Initialize persistence system
    PackPersistence.init();
    
    // Make globally available
    window.PackPersistence = PackPersistence;
    
    console.log('✅ Pack persistence system ready');
});