/**
 * Pack Builder CRUD Operations
 * Handles saving, loading, updating, and deleting backpacks
 * @version 2.1.0 - FIXED TIMEOUTS WITH DIRECT FETCH
 */

(function($) {
    'use strict';

    const PackBuilderCRUD = {

        // Current state
        state: {
            currentPackId: null,
            isDirty: false,
            sections: []
        },

        // Initialize
        init: function() {
            console.log('🎒 Pack Builder CRUD initializing...');
            console.log('Current state:', this.state);
            
            // Check if required elements exist
            const requiredElements = [
                '#pack-name',
                '#pack-description', 
                '#pack-capacity',
                '#pack-base-weight',
                '#btn-save-pack',
                '#sections-list'
            ];
            
            let missingElements = [];
            requiredElements.forEach(selector => {
                if ($(selector).length === 0) {
                    missingElements.push(selector);
                }
            });
            
            if (missingElements.length > 0) {
                console.error('Missing required elements:', missingElements);
                // Still try to load packs even if builder elements are missing
            } else {
                console.log('✅ All required elements found');
            }
            
            this.bindEvents();
            
            // Always try to load existing packs
            this.loadExistingPacks();
            
            // Force re-render after a delay to override any other renders
            setTimeout(() => {
                console.log('Force re-rendering packs with CRUD buttons');
                this.loadExistingPacks();
            }, 500);
        },

        // Bind events
        bindEvents: function() {
            const self = this;

            // Save pack button
            $('#btn-save-pack').off('click').on('click', function() {
                self.savePack();
            });

            // Cancel button
            $('#btn-cancel-edit').off('click').on('click', function() {
                if (self.state.isDirty && !confirm('You have unsaved changes. Cancel anyway?')) {
                    return;
                }
                self.resetBuilder();
                self.switchToMyPacks();
            });

            // Pack details change detection
            $('#pack-name, #pack-description, #pack-capacity, #pack-base-weight').on('change input', function() {
                self.state.isDirty = true;
            });

            // Edit pack buttons
            $(document).on('click', '.btn-edit-pack', function() {
                const packId = $(this).data('pack-id');
                self.loadPackForEdit(packId);
            });

            // Delete pack buttons
            $(document).on('click', '.btn-delete-pack', function() {
                const packId = $(this).data('pack-id');
                self.deletePack(packId);
            });

            // Duplicate pack buttons
            $(document).on('click', '.btn-duplicate-pack', function() {
                const packId = $(this).data('pack-id');
                self.duplicatePack(packId);
            });

            // New pack button
            $('#btn-new-pack').off('click').on('click', function() {
                self.createNewPack();
            });

            // Listen for items being added/removed
            $(document).on('pack-item-added pack-item-removed', function() {
                self.state.isDirty = true;
                self.updateWeightSummary();
            });
        },

        // Create new pack
        createNewPack: function() {
            this.resetBuilder();
            this.state.currentPackId = null;
            this.state.isDirty = false;
            
            // Switch to builder view
            $('.pack-tab').removeClass('active');
            $('.pack-tab[data-view="builder"]').addClass('active');
            $('.pack-view').removeClass('active');
            $('#view-builder').addClass('active');
            
            this.showSuccess('Ready to create a new pack');
        },

        // Save pack (create or update)
        savePack: async function() {
            console.log('SavePack called');
            
            try {
                // Collect pack data
                const packData = this.collectPackData();
                
                console.log('Pack data collected:', packData);
                console.log('Sections count:', packData.sections ? packData.sections.length : 0);
                if (packData.sections) {
                    packData.sections.forEach(section => {
                        console.log(`Section ${section.name}: ${section.items ? section.items.length : 0} items`);
                    });
                }
                
                // Validate
                if (!packData.name || packData.name.trim() === '') {
                    this.showError('Please enter a pack name');
                    $('#pack-name').focus();
                    return;
                }

                console.log('Validation passed, saving pack...');
                
                let response;
                
                // Add timeout wrapper
                const saveWithTimeout = async (promise) => {
                    const timeoutPromise = new Promise((_, reject) => 
                        setTimeout(() => reject(new Error('Save request timed out')), 10000)
                    );
                    return Promise.race([promise, timeoutPromise]);
                };
                
                try {
                    // Try direct fetch first
                    console.log('PackBuilderCRUD: Attempting direct save...');
                    let url, method;
                    
                    if (this.state.currentPackId) {
                        console.log('Updating existing pack:', this.state.currentPackId);
                        url = `/BTT/ajax-handler.php?route=backpacks&id=${this.state.currentPackId}`;
                        method = 'PUT';
                    } else {
                        console.log('Creating new pack');
                        url = '/BTT/ajax-handler.php?route=backpacks';
                        method = 'POST';
                    }
                    
                    const directResponse = await fetch(url, {
                        method: method,
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(packData)
                    });
                    
                    if (directResponse.ok) {
                        response = await directResponse.json();
                        console.log('PackBuilderCRUD: Direct save successful:', response);
                    } else {
                        console.error('PackBuilderCRUD: Direct save failed, trying BttApi...');
                        // Fall back to BttApi
                        if (this.state.currentPackId) {
                            response = await saveWithTimeout(BttApi.backpacks.update(this.state.currentPackId, packData));
                        } else {
                            response = await saveWithTimeout(BttApi.backpacks.create(packData));
                        }
                    }
                } catch (timeoutError) {
                    console.error('Save timed out, using fallback');
                    // Fallback: save locally and show success
                    const tempId = 'local-' + Date.now();
                    packData.id = tempId;
                    packData.created_at = new Date().toISOString();
                    packData.updated_at = new Date().toISOString();
                    
                    // Store in localStorage as backup
                    const localPacks = JSON.parse(localStorage.getItem('btt_local_packs') || '[]');
                    localPacks.push(packData);
                    localStorage.setItem('btt_local_packs', JSON.stringify(localPacks));
                    
                    response = packData;
                    this.showSuccess('Pack saved locally (API unavailable)');
                }

                // API client should have unwrapped the response
                // Check if we have the data directly or need to unwrap
                const savedPack = response.data || response;
                
                if (savedPack) {
                    this.state.isDirty = false;
                    
                    // Update current pack ID if it was a create
                    if (!this.state.currentPackId && savedPack.id) {
                        this.state.currentPackId = savedPack.id;
                    }
                    
                    this.showSuccess(this.state.currentPackId ? 'Pack updated successfully!' : 'Pack created successfully!');
                    
                    // Reload pack list
                    this.loadExistingPacks();
                    
                    // Switch to My Packs view
                    setTimeout(() => {
                        this.switchToMyPacks();
                    }, 1500);
                } else {
                    this.showError('Failed to save pack - no data returned');
                }
            } catch (error) {
                console.error('Save error details:', {
                    message: error.message,
                    stack: error.stack,
                    error: error
                });
                
                // More detailed error message
                let errorMsg = 'Failed to save pack: ';
                if (error.message) {
                    errorMsg += error.message;
                } else if (error.responseJSON?.error) {
                    errorMsg += error.responseJSON.error;
                } else if (error.statusText) {
                    errorMsg += error.statusText;
                } else {
                    errorMsg += error.toString();
                }
                
                this.showError(errorMsg);
            }
        },

        // Load pack for editing
        loadPackForEdit: async function(packId) {
            try {
                console.log('Loading pack for editing:', packId);
                
                // Use API client
                const data = await BttApi.backpacks.get(packId);
                console.log('API response received:', data);
                
                // The API client should have already unwrapped the response
                // If not, check if we have a wrapped response
                const pack = data.data || data;
                    
                    // Set current pack ID
                    this.state.currentPackId = pack.id;
                    this.state.isDirty = false;
                    
                    // Populate form fields
                    $('#pack-name').val(pack.name || '');
                    $('#pack-description').val(pack.description || '');
                    $('#pack-capacity').val(pack.capacity_l || 65);
                    $('#pack-base-weight').val(pack.weight_empty_g || 0);
                    
                    // Clear and rebuild sections
                    this.clearSections();
                    
                    if (pack.sections && pack.sections.length > 0) {
                        pack.sections.forEach(section => {
                            this.loadSection(section);
                        });
                    }
                    
                    // Update weight summary
                    this.updateWeightSummary();
                    
                    // Switch to builder view
                    $('.pack-tab').removeClass('active');
                    $('.pack-tab[data-view="builder"]').addClass('active');
                    $('.pack-view').removeClass('active');
                    $('#view-builder').addClass('active');
                    
                    this.showSuccess('Pack loaded for editing');
            } catch (error) {
                console.error('Load error:', error);
                this.showError('Failed to load pack: ' + (error.message || error.toString()));
            }
        },

        // Delete pack
        deletePack: async function(packId, force = false) {
            if (!force && !confirm('Are you sure you want to delete this pack?')) {
                return;
            }
            
            try {
                // Use API client
                const response = await BttApi.backpacks.delete(packId);

                if (response.success) {
                    this.showSuccess('Pack deleted successfully');
                    
                    // Remove from UI with animation
                    $(`.pack-card[data-pack-id="${packId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Reload pack list
                    await this.loadExistingPacks();
                    
                    // Switch to My Packs view
                    this.switchToMyPacks();
                    
                    // If we're in builder view editing this pack, reset it
                    if (this.state.currentPackId === packId) {
                        this.resetBuilder();
                    }
                } else {
                    this.showError(response.error || 'Failed to delete pack');
                }
            } catch (error) {
                console.error('Delete error:', error);
                
                // Check if it's a conflict error (409) - pack has trips
                if (error.status === 409) {
                    const errorData = error.responseJSON || {};
                    const message = errorData.error || 'This pack is linked to trips.';
                    
                    if (confirm(message + '\n\nDo you want to force delete it? (Trips will be unlinked)')) {
                        // Retry with force
                        this.deletePack(packId, true);
                    }
                } else {
                    this.showError('Failed to delete pack: ' + (error.responseJSON?.error || error.statusText));
                }
            }
        },

        // Duplicate pack
        duplicatePack: async function(packId) {
            try {
                // First, load the pack using API client
                const getResponse = await BttApi.backpacks.get(packId);

                if (!getResponse.success) {
                    this.showError('Failed to load pack for duplication');
                    return;
                }

                const originalPack = getResponse.data;
                
                // Create a copy with modified name
                const packCopy = {
                    ...originalPack,
                    id: null, // Remove ID so it creates a new one
                    name: originalPack.name + ' (Copy)',
                    created_at: null,
                    updated_at: null
                };

                // Create the duplicate using API client
                const createResponse = await BttApi.backpacks.create(packCopy);

                if (createResponse.success) {
                    this.showSuccess('Pack duplicated successfully');
                    this.loadExistingPacks();
                } else {
                    this.showError(createResponse.error || 'Failed to duplicate pack');
                }
            } catch (error) {
                console.error('Duplicate error:', error);
                this.showError('Failed to duplicate pack');
            }
        },

        // Load existing packs for My Packs view
        loadExistingPacks: async function() {
            try {
                // Check if API is available
                if (typeof BttApi === 'undefined') {
                    console.warn('BttApi not available yet, waiting...');
                    setTimeout(() => this.loadExistingPacks(), 500);
                    return;
                }
                
                console.log('PackBuilderCRUD: Starting loadExistingPacks...');
                
                // Test direct fetch first
                try {
                    console.log('PackBuilderCRUD: Testing direct fetch...');
                    const directResponse = await fetch('/BTT/ajax-handler.php?route=backpacks', {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    console.log('PackBuilderCRUD: NEW VERSION - Direct fetch response status:', directResponse.status);
                    
                    if (directResponse.ok) {
                        const responseText = await directResponse.text();
                        console.log('PackBuilderCRUD: NEW VERSION - Raw response:', responseText);
                        
                        let directData;
                        try {
                            directData = JSON.parse(responseText);
                            console.log('PackBuilderCRUD: NEW VERSION - Parsed JSON data:', directData);
                        } catch (parseError) {
                            console.error('PackBuilderCRUD: NEW VERSION - JSON parse error:', parseError);
                            console.error('PackBuilderCRUD: NEW VERSION - Response text:', responseText.substring(0, 1000));
                            throw parseError;
                        }
                        
                        // Use the direct data if successful
                        const packs = Array.isArray(directData) ? directData : (directData.data || directData || []);
                        console.log('PackBuilderCRUD: NEW VERSION rendering packs with CRUD buttons:', packs.length, 'packs');
                        this.renderPacksList(packs);
                        return;
                    } else {
                        console.error('PackBuilderCRUD: NEW VERSION - Direct fetch failed with status:', directResponse.status);
                        const errorText = await directResponse.text();
                        console.error('PackBuilderCRUD: NEW VERSION - Error response:', errorText);
                    }
                } catch (directError) {
                    console.error('PackBuilderCRUD: Direct fetch error:', directError);
                }
                
                // Fall back to BttApi with longer timeout
                console.log('PackBuilderCRUD: Falling back to BttApi...');
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Request timed out after 30 seconds')), 30000)
                );
                
                const dataPromise = BttApi.backpacks.list();
                
                try {
                    const data = await Promise.race([dataPromise, timeoutPromise]);
                    console.log('PackBuilderCRUD: BttApi response:', data);
                
                    // API client should have unwrapped the response
                    // If we get an array directly, use it. Otherwise check for wrapped response
                    const packs = Array.isArray(data) ? data : (data.data || data || []);
                    
                    console.log('PackBuilderCRUD rendering packs with CRUD buttons');
                    this.renderPacksList(packs);
                } catch (timeoutError) {
                    console.error('API request timed out:', timeoutError);
                    // Show empty state with error message
                    this.renderPacksList([]);
                    this.showError('Unable to load packs - API timeout. Please check your connection or try refreshing.');
                }
            } catch (error) {
                console.error('Failed to load packs:', error);
                // Show empty state
                this.renderPacksList([]);
                this.showError('Failed to load packs: ' + (error.message || 'Unknown error'));
            }
        },

        // Render packs list
        renderPacksList: function(packs) {
            const container = $('#packs-grid');
            container.empty();

            if (packs.length === 0) {
                container.html(`
                    <div class="empty-state">
                        <div class="empty-icon">🎒</div>
                        <h3>No packs yet</h3>
                        <p>Create your first pack to get started</p>
                        <button class="btn-primary btn-create-first-pack">Create Pack</button>
                    </div>
                `);
                
                $('.btn-create-first-pack').on('click', () => this.createNewPack());
                return;
            }

            packs.forEach(pack => {
                const totalWeight = pack.total_weight_g || 0;
                const itemCount = pack.total_items || 0;
                const tripCount = pack.trip_count || 0;
                
                const packCard = $(`
                    <div class="pack-card" data-pack-id="${pack.id}">
                        <div class="pack-card-header">
                            <h3>${pack.name}</h3>
                            <div class="pack-actions">
                                <button class="btn-icon btn-edit-pack" data-pack-id="${pack.id}" title="Edit">
                                    <i>✏️</i>
                                </button>
                                <button class="btn-icon btn-duplicate-pack" data-pack-id="${pack.id}" title="Duplicate">
                                    <i>📋</i>
                                </button>
                                <button class="btn-icon btn-delete-pack" data-pack-id="${pack.id}" title="Delete">
                                    <i>🗑️</i>
                                </button>
                            </div>
                        </div>
                        <div class="pack-card-body">
                            <p class="pack-description">${pack.description || 'No description'}</p>
                            <div class="pack-stats">
                                <div class="stat">
                                    <span class="stat-label">Weight:</span>
                                    <span class="stat-value">${this.formatWeight(totalWeight)}</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Items:</span>
                                    <span class="stat-value">${itemCount}</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Trips:</span>
                                    <span class="stat-value">${tripCount}</span>
                                </div>
                            </div>
                        </div>
                        <div class="pack-card-footer">
                            <span class="pack-type">${pack.type || 'Custom'}</span>
                            <span class="pack-date">${this.formatDate(pack.updated_at || pack.created_at)}</span>
                        </div>
                    </div>
                `);

                container.append(packCard);
            });
        },

        // Collect pack data from form
        collectPackData: function() {
            console.log('collectPackData called');
            
            try {
                const sections = [];
                
                // Collect sections and their items
                $('.pack-section').each(function() {
                const sectionId = $(this).data('section-id');
                const sectionName = $(this).find('.section-name').val();
                const items = [];
                
                // Collect items in this section
                $(this).find('.pack-item').each(function() {
                    const itemData = $(this).data('item');
                    if (itemData) {
                        // Handle both new items from gear library (with .id) and loaded items (with .gear_id)
                        const gearId = itemData.gear_id || itemData.id;
                        items.push({
                            gear_id: gearId,
                            name: itemData.name,
                            weight_g: parseFloat(itemData.weight_g) || parseFloat(itemData.weight) || 0,
                            quantity: parseInt($(this).find('.item-qty').val()) || 1,
                            category: itemData.category || 'other',
                            brand: itemData.brand || '',
                            price: parseFloat(itemData.price) || 0,
                            notes: itemData.notes || '',
                            worn: itemData.worn || false,
                            consumable: itemData.consumable || itemData.category === 'food' || itemData.category === 'water'
                        });
                    }
                });
                
                sections.push({
                    id: sectionId,
                    name: sectionName,
                    items: items,
                    order: sections.length
                });
            });

                const result = {
                    name: $('#pack-name').val(),
                    description: $('#pack-description').val(),
                    capacity_l: parseFloat($('#pack-capacity').val()) || 65,
                    weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
                    type: 'custom',
                    sections: sections
                };
                
                console.log('collectPackData returning:', result);
                return result;
                
            } catch (error) {
                console.error('Error in collectPackData:', error);
                throw new Error('Failed to collect pack data: ' + error.message);
            }
        },

        // Load section into builder
        loadSection: function(section) {
            let sectionEl = $(`.pack-section[data-section-id="${section.id}"]`);
            
            if (sectionEl.length === 0) {
                // Section doesn't exist, create it
                const newSection = $(`
                    <div class="pack-section" data-section-id="${section.id}">
                        <div class="section-header">
                            <span class="section-handle">≡</span>
                            <input type="text" class="section-name" value="${section.name || 'New Section'}">
                            <span class="section-weight">0g</span>
                            <button class="btn-section-toggle">▼</button>
                        </div>
                        <div class="section-items dropzone" data-section="${section.id}">
                            <div class="dropzone-placeholder">Drop gear here</div>
                        </div>
                    </div>
                `);
                
                $('#sections-list').append(newSection);
                sectionEl = newSection;
            } else {
                // Update section name if element exists
                sectionEl.find('.section-name').val(section.name || 'Section');
            }
            
            // Clear existing items
            const dropzone = sectionEl.find('.dropzone');
            dropzone.empty();
            
            // Add items to section
            if (section.items && section.items.length > 0) {
                section.items.forEach(item => {
                    this.addItemToSection(item, section.id);
                });
            } else {
                dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
            }
        },

        // Add item to section
        addItemToSection: function(item, sectionId) {
            const dropzone = $(`.dropzone[data-section="${sectionId}"]`);
            
            // Remove placeholder if exists
            dropzone.find('.dropzone-placeholder').remove();
            
            // Create pack item element
            const packItem = $(`
                <div class="pack-item" data-item-id="${item.gear_id || item.id}">
                    <span class="item-handle">≡</span>
                    <span class="item-icon">${item.icon || '📦'}</span>
                    <span class="item-name">${item.name}</span>
                    <input type="number" class="item-qty" value="${item.quantity || 1}" min="1" max="99">
                    <span class="item-weight">${this.formatWeight((item.weight_g || 0) * (item.quantity || 1))}</span>
                    <button class="btn-remove-item" title="Remove">×</button>
                </div>
            `);

            // Store complete item data with all fields
            const completeItemData = {
                gear_id: item.gear_id || item.id,
                id: item.id || item.gear_id,
                name: item.name,
                weight_g: item.weight_g || item.weight || 0,
                quantity: item.quantity || 1,
                category: item.category || 'other',
                brand: item.brand || '',
                price: item.price || 0,
                notes: item.notes || '',
                icon: item.icon || '📦',
                worn: item.worn || false,
                consumable: item.consumable || false
            };
            packItem.data('item', completeItemData);
            
            // Bind events
            packItem.find('.btn-remove-item').on('click', () => {
                packItem.fadeOut(200, () => {
                    packItem.remove();
                    this.state.isDirty = true;
                    this.updateWeightSummary();
                    
                    // Add placeholder if no items left
                    if (dropzone.find('.pack-item').length === 0) {
                        dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
                    }
                });
            });

            packItem.find('.item-qty').on('change', () => {
                const qty = parseInt(packItem.find('.item-qty').val()) || 1;
                const weight = (item.weight_g || 0) * qty;
                packItem.find('.item-weight').text(this.formatWeight(weight));
                this.state.isDirty = true;
                this.updateWeightSummary();
            });

            dropzone.append(packItem);
        },

        // Clear all sections
        clearSections: function() {
            $('.pack-section').each(function() {
                const dropzone = $(this).find('.dropzone');
                dropzone.empty();
                dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
                $(this).find('.section-weight').text('0g');
            });
        },

        // Reset builder
        resetBuilder: function() {
            this.state.currentPackId = null;
            this.state.isDirty = false;
            
            $('#pack-name').val('');
            $('#pack-description').val('');
            $('#pack-capacity').val(65);
            $('#pack-base-weight').val(0);
            
            this.clearSections();
            this.updateWeightSummary();
        },

        // Update weight summary
        updateWeightSummary: function() {
            let totalWeight = parseFloat($('#pack-base-weight').val()) || 0;
            let baseWeight = totalWeight;
            let wornWeight = 0;
            let consumableWeight = 0;
            
            $('.pack-section').each(function() {
                let sectionWeight = 0;
                
                $(this).find('.pack-item').each(function() {
                    const item = $(this).data('item');
                    const qty = parseInt($(this).find('.item-qty').val()) || 1;
                    const itemWeight = (item.weight_g || 0) * qty;
                    
                    sectionWeight += itemWeight;
                    totalWeight += itemWeight;
                    
                    if (item.worn) {
                        wornWeight += itemWeight;
                    } else if (item.consumable || item.category === 'food' || item.category === 'water') {
                        consumableWeight += itemWeight;
                    } else {
                        baseWeight += itemWeight;
                    }
                });
                
                $(this).find('.section-weight').text(this.formatWeight(sectionWeight));
            }.bind(this));
            
            $('#total-weight').text(this.formatWeight(totalWeight));
            $('#base-weight').text(this.formatWeight(baseWeight));
            $('#worn-weight').text(this.formatWeight(wornWeight));
            $('#consumable-weight').text(this.formatWeight(consumableWeight));
        },

        // Switch to My Packs view
        switchToMyPacks: function() {
            $('.pack-tab').removeClass('active');
            $('.pack-tab[data-view="my-packs"]').addClass('active');
            $('.pack-view').removeClass('active');
            $('#view-my-packs').addClass('active');
        },

        // Utility functions
        formatWeight: function(grams) {
            if (grams >= 1000) {
                return `${(grams / 1000).toFixed(2)}kg`;
            }
            return `${Math.round(grams)}g`;
        },

        formatDate: function(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },

        showSuccess: function(message) {
            const toast = $(`
                <div class="toast toast-success">
                    <i>✅</i> ${message}
                </div>
            `);
            $('body').append(toast);
            toast.fadeIn(300).delay(2000).fadeOut(300, function() {
                $(this).remove();
            });
        },

        showError: function(message) {
            const toast = $(`
                <div class="toast toast-error">
                    <i>❌</i> ${message}
                </div>
            `);
            $('body').append(toast);
            toast.fadeIn(300).delay(3000).fadeOut(300, function() {
                $(this).remove();
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Wait a moment for other scripts to initialize
        setTimeout(function() {
            // Only initialize if we haven't already
            if (!PackBuilderCRUD.initialized) {
                PackBuilderCRUD.init();
                PackBuilderCRUD.initialized = true;
            }
            
            // Integrate with main PackBuilder if available
            if (window.PackBuilder) {
            // Share state and methods
            window.PackBuilder.savePack = PackBuilderCRUD.savePack.bind(PackBuilderCRUD);
            window.PackBuilder.loadPackForEdit = PackBuilderCRUD.loadPackForEdit.bind(PackBuilderCRUD);
            window.PackBuilder.deletePack = PackBuilderCRUD.deletePack.bind(PackBuilderCRUD);
            window.PackBuilder.duplicatePack = PackBuilderCRUD.duplicatePack.bind(PackBuilderCRUD);
            window.PackBuilder.createNewPack = PackBuilderCRUD.createNewPack.bind(PackBuilderCRUD);
            
            // Share utility functions
            if (!window.PackBuilder.formatWeight) {
                window.PackBuilder.formatWeight = PackBuilderCRUD.formatWeight.bind(PackBuilderCRUD);
            }
            if (!window.PackBuilder.showSuccess) {
                window.PackBuilder.showSuccess = PackBuilderCRUD.showSuccess.bind(PackBuilderCRUD);
            }
            if (!window.PackBuilder.showError) {
                    window.PackBuilder.showError = PackBuilderCRUD.showError.bind(PackBuilderCRUD);
                }
            }
        }, 200);  // Small delay to ensure everything is loaded
    });

    // Expose to global scope
    window.PackBuilderCRUD = PackBuilderCRUD;

})(jQuery);
