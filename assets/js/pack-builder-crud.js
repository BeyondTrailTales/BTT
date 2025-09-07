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
            // Prevent multiple initializations
            if (this._initInProgress || this._initialized) {
                console.log('🎒 Pack Builder CRUD already initialized or in progress');
                return;
            }
            
            this._initInProgress = true;
            
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
                console.warn('Pack builder elements not found (view may be hidden):', missingElements);
                // This is OK - the builder view might be hidden
                // Elements will be available when user switches to builder view
            } else {
                console.log('✅ All required elements found');
            }
            
            this.bindEvents();
            
            // Load existing packs only once
            this.loadExistingPacks();
            
            this._initialized = true;
            this._initInProgress = false;
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

            // Edit pack buttons now use direct links to pack-builder.php

            // Delete pack buttons - use off() first to prevent multiple bindings
            $(document).off('click', '.btn-delete-pack').on('click', '.btn-delete-pack', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const packId = $(this).data('pack-id');
                console.log('Delete button clicked for pack:', packId);
                self.deletePack(packId);
            });

            // Duplicate pack buttons - use off() first to prevent multiple bindings
            $(document).off('click', '.btn-duplicate-pack').on('click', '.btn-duplicate-pack', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const packId = $(this).data('pack-id');
                console.log('Duplicate button clicked for pack:', packId);
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
            
            // Show save animation
            if (window.SaveAnimation) {
                window.SaveAnimation.showSaving('Saving your backpack...');
                const saveBtn = $('#btn-save-pack');
                if (saveBtn.length) {
                    window.SaveAnimation.setButtonLoading(saveBtn[0], true);
                }
            }
            
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
                    if (window.SaveAnimation) {
                        window.SaveAnimation.showError('Missing pack name', 'Please enter a name for your pack');
                    }
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
                    const isNewPack = !this.state.currentPackId;
                    if (isNewPack && savedPack.id) {
                        this.state.currentPackId = savedPack.id;
                    }
                    
                    // Show success animation
                    if (window.SaveAnimation) {
                        const packName = savedPack.name || packData.name || 'Backpack';
                        const message = isNewPack ? 'Backpack created!' : 'Backpack updated!';
                        const details = `"${packName}" saved to database (ID: ${savedPack.id})`;
                        window.SaveAnimation.showSuccess(message, details);
                    }
                    
                    // Check for achievements
                    if (response.achievements && response.achievements.length > 0) {
                        // Queue achievements for display
                        if (window.achievementManager) {
                            response.achievements.forEach(achievement => {
                                window.achievementManager.queueAchievement(achievement);
                            });
                        }
                    }
                    
                    // Use Duolingo-style notification
                    if (window.DuoNotify) {
                        document.dispatchEvent(new CustomEvent('backpack:saved', {
                            detail: { name: packData.name, id: savedPack.id }
                        }));
                    } else {
                        this.showSuccess(this.state.currentPackId ? 'Pack updated successfully!' : 'Pack created successfully!');
                    }
                    
                    // Trigger achievement check
                    if (window.achievementManager && window.achievementManager.triggerAchievementCheck) {
                        const isNewPack = !this.state.currentPackId;
                        const totalWeight = this.calculateTotalWeight();
                        const itemCount = this.countItems();
                        const sectionsCount = this.countSections();
                        
                        window.achievementManager.triggerAchievementCheck({
                            action: isNewPack ? 'backpack_created' : 'backpack_saved',
                            total_weight: totalWeight,
                            item_count: itemCount,
                            sections_count: sectionsCount,
                            pack_id: savedPack.id,
                            pack_name: packData.name
                        });
                    }
                    
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
                
                // Show error animation
                if (window.SaveAnimation) {
                    let errorDetails = error.message;
                    if (error.message && error.message.includes('timeout')) {
                        errorDetails = 'Request timed out - please try again';
                    } else if (error.statusText) {
                        errorDetails = error.statusText;
                    }
                    window.SaveAnimation.showError('Failed to save backpack', errorDetails);
                }
                
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
            } finally {
                // Reset button state
                if (window.SaveAnimation) {
                    const saveBtn = $('#btn-save-pack');
                    if (saveBtn.length) {
                        window.SaveAnimation.setButtonLoading(saveBtn[0], false);
                    }
                }
            }
        },

        // Load pack for editing
        loadPackForEdit: async function(packId) {
            try {
                console.log('Loading pack for editing:', packId);
                
                // Try direct fetch with timeout to prevent hanging
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout
                
                try {
                    const response = await fetch(`/BTT/ajax-handler.php?route=backpacks&id=${packId}`, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    });
                    
                    clearTimeout(timeoutId);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log('Direct fetch response received:', data);
                    
                    // The response should contain the pack data
                    const pack = data;
                    
                    // Set current pack ID
                    this.state.currentPackId = pack.id;
                    this.state.isDirty = false;
                    
                    // Populate form fields - using correct field IDs
                    $('#pack-name-input').val(pack.name || '');
                    $('#pack-description').val(pack.description || '');
                    $('#pack-capacity').val(pack.capacity_l || 65);
                    $('#pack-type').val(pack.pack_type || 'custom');
                    
                    // Update display elements
                    $('#pack-name-display').text(pack.name || 'New Pack');
                    $('#quick-total-weight').text((pack.total_weight_g || 0) + 'g');
                    $('#quick-total-items').text(pack.total_items || 0);
                    
                    // Switch to builder view first
                    $('.pack-tab').removeClass('active btn-primary').addClass('btn-ghost');
                    $('.pack-tab[data-view="builder"]').removeClass('btn-ghost').addClass('active');
                    $('.pack-view').removeClass('active').hide();
                    $('#view-builder').addClass('active').show();
                    
                    // Clear and rebuild sections
                    this.clearSections();
                    
                    console.log('Pack data loaded:', pack);
                    
                    if (pack.sections && pack.sections.length > 0) {
                        // Sections already organized by ajax-handler.php
                        pack.sections.forEach(section => {
                            this.loadSection(section);
                        });
                    } else if (pack.items && pack.items.length > 0) {
                        // Legacy format: items array without sections
                        console.log('Legacy format detected, organizing items into sections');
                        const sectionMap = {};
                        
                        pack.items.forEach(item => {
                            const sectionId = item.section || 'main';
                            if (!sectionMap[sectionId]) {
                                sectionMap[sectionId] = {
                                    id: sectionId,
                                    name: this.getSectionName(sectionId),
                                    items: []
                                };
                            }
                            sectionMap[sectionId].items.push(item);
                        });
                        
                        Object.values(sectionMap).forEach(section => {
                            this.loadSection(section);
                        });
                    } else {
                        console.log('No items found in pack');
                        // Ensure at least the main section exists
                        this.ensureDefaultSections();
                    }
                    
                    // Show success notification
                    this.showToast(`📦 Loaded "${pack.name}" with ${pack.sections ? pack.sections.reduce((total, section) => total + (section.items ? section.items.length : 0), 0) : 0} items`, 'success');
                    
                    // Update weight summary
                    this.updateWeightSummary();
                    
                    // Switch to builder view
                    $('.pack-tab').removeClass('active');
                    $('.pack-tab[data-view="builder"]').addClass('active');
                    $('.pack-view').removeClass('active');
                    $('#view-builder').addClass('active');
                    
                    this.showSuccess('Pack loaded for editing');
                    
                } catch (fetchError) {
                    console.error('Direct fetch failed:', fetchError);
                    
                    // If fetch was aborted, show timeout message
                    if (fetchError.name === 'AbortError') {
                        this.showError('Request timed out. Please check your connection and try again.');
                        return;
                    }
                    
                    // For other errors, just throw to outer catch
                    throw fetchError;
                }
            } catch (error) {
                console.error('Load error:', error);
                this.showError('Failed to load pack: ' + (error.message || error.toString()));
                this.showToast(`❌ Failed to load pack: ${error.message || 'Database error'}`, 'error');
            }
        },

        // Delete pack
        deletePack: async function(packId, force = false) {
            if (!force && !confirm('Are you sure you want to delete this pack?')) {
                return;
            }
            
            try {
                // Try direct fetch with timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                const fetchResponse = await fetch(`/BTT/ajax-handler.php?route=backpacks&id=${packId}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!fetchResponse.ok) {
                    throw new Error(`HTTP error! status: ${fetchResponse.status}`);
                }
                
                const response = await fetchResponse.json();

                if (response.success) {
                    // Use Duolingo-style notification
                    if (window.DuoNotify) {
                        window.DuoNotify.success('Pack Deleted! 🗑️', 'The pack has been removed from your collection.');
                    } else {
                        this.showSuccess('Pack deleted successfully');
                    }
                    
                    // Show toast notification
                    this.showToast('🗑️ Pack deleted from database', 'success');
                    
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
                    this.showToast(`❌ Failed to delete pack: ${response.error || 'Database error'}`, 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                
                // If fetch was aborted, show timeout message
                if (error.name === 'AbortError') {
                    this.showError('Request timed out. Please check your connection and try again.');
                } else if (error.message && error.message.includes('409')) {
                    // Conflict error - pack has trips
                    const message = 'This pack is linked to trips.';
                    if (confirm(message + '\n\nDo you want to force delete it? (Trips will be unlinked)')) {
                        // Retry with force
                        this.deletePack(packId, true);
                    }
                } else {
                    this.showError('Failed to delete pack: ' + (error.message || error.toString()));
                    this.showToast(`❌ Database error: Could not delete pack`, 'error');
                }
            }
        },

        // Duplicate pack
        duplicatePack: async function(packId) {
            try {
                // First, load the pack using direct fetch
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);
                
                const fetchResponse = await fetch(`/BTT/ajax-handler.php?route=backpacks&id=${packId}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!fetchResponse.ok) {
                    throw new Error(`HTTP error! status: ${fetchResponse.status}`);
                }
                
                const originalPack = await fetchResponse.json();
                
                if (!originalPack) {
                    this.showError('Failed to load pack for duplication');
                    return;
                }
                
                // Create a copy with modified name
                const packCopy = {
                    ...originalPack,
                    id: null, // Remove ID so it creates a new one
                    name: originalPack.name + ' (Copy)',
                    created_at: null,
                    updated_at: null
                };

                // Create the duplicate using direct fetch
                const createController = new AbortController();
                const createTimeoutId = setTimeout(() => createController.abort(), 5000);
                
                const createFetchResponse = await fetch('/BTT/ajax-handler.php?route=backpacks', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(packCopy),
                    signal: createController.signal
                });
                
                clearTimeout(createTimeoutId);
                
                if (!createFetchResponse.ok) {
                    throw new Error(`HTTP error! status: ${createFetchResponse.status}`);
                }
                
                const createResponse = await createFetchResponse.json();

                if (createResponse.success) {
                    // Use Duolingo-style notification
                    if (window.DuoNotify) {
                        window.DuoNotify.success('Pack Duplicated! 📋', `Created a copy of "${originalPack.name}"`);
                    } else {
                        this.showSuccess('Pack duplicated successfully');
                    }
                    this.loadExistingPacks();
                } else {
                    this.showError(createResponse.error || 'Failed to duplicate pack');
                }
            } catch (error) {
                console.error('Duplicate error:', error);
                
                // If fetch was aborted, show timeout message
                if (error.name === 'AbortError') {
                    this.showError('Request timed out. Please check your connection and try again.');
                } else {
                    this.showError('Failed to duplicate pack: ' + (error.message || error.toString()));
                }
            }
        },

        // Load existing packs for My Packs view
        loadExistingPacks: async function() {
            // Prevent concurrent loads
            if (this._loadingPacks) {
                console.log('Already loading packs, skipping duplicate call');
                return;
            }
            
            this._loadingPacks = true;
            
            try {
                // Check if API is available with a maximum retry count
                if (typeof BttApi === 'undefined') {
                    this._apiRetryCount = (this._apiRetryCount || 0) + 1;
                    
                    if (this._apiRetryCount > 10) {
                        console.error('BttApi not available after 10 retries, giving up');
                        this._loadingPacks = false;
                        // Try direct fetch instead
                    } else {
                        console.warn(`BttApi not available yet, retry ${this._apiRetryCount}/10...`);
                        setTimeout(() => {
                            this._loadingPacks = false;
                            this.loadExistingPacks();
                        }, 500);
                        return;
                    }
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
                    // Use Duolingo-style notification
                    if (window.DuoNotify) {
                        window.DuoNotify.error('Connection Issue! 🌐', 'Unable to load packs. Please check your connection or try refreshing.');
                    } else {
                        this.showError('Unable to load packs - API timeout. Please check your connection or try refreshing.');
                    }
                }
            } catch (error) {
                console.error('Failed to load packs:', error);
                // Show empty state
                this.renderPacksList([]);
                this.showError('Failed to load packs: ' + (error.message || 'Unknown error'));
            } finally {
                this._loadingPacks = false;
            }
        },

        // Render packs list
        renderPacksList: function(packs) {
            console.log('Rendering packs list with', packs.length, 'packs');
            
            // Hide ALL loading and empty states first
            $('#packs-loading').hide();
            $('#packs-empty').hide();
            
            // Use the correct container
            const container = $('#packs-grid-items');
            
            if (!packs || packs.length === 0) {
                // Show empty state, hide packs grid
                container.hide();
                $('#packs-empty').show();
                
                // Bind create button
                $('#create-first-pack').off('click').on('click', () => this.createNewPack());
                return;
            }
            
            // We have packs - make sure container is visible
            container.empty().show();
            
            // Also ensure the parent container is visible
            $('#packs-grid').show();
            $('#view-my-packs').show();

            packs.forEach(pack => {
                const totalWeight = pack.total_weight_g || 0;
                const itemCount = pack.total_items || 0;
                const tripCount = pack.trip_count || 0;
                
                const packCard = $(`
                    <div class="pack-card" data-pack-id="${pack.id}">
                        <div class="pack-card-header">
                            <h3>${pack.name}</h3>
                            <div class="pack-actions">
                                <a href="/BTT/pack-builder.php?id=${pack.id}" class="btn-icon btn-edit-pack" title="Edit">
                                    <i>✏️</i>
                                </a>
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
                    const sectionId = $(this).data('section') || $(this).data('section-id');
                    const sectionName = $(this).find('.section-name').val() || 'Main Pack';
                    const items = [];
                    
                    console.log(`Processing section: ${sectionId} (${sectionName})`);
                    
                    // Collect items in this section
                    $(this).find('.pack-item').each(function() {
                        console.log('Processing pack item:', $(this));
                        
                        // Try multiple ways to get item data
                        let itemData = $(this).data('item');
                        
                        // If no stored data, collect from element attributes
                        if (!itemData) {
                            itemData = {
                                id: $(this).data('gear-id'),
                                gear_id: $(this).data('gear-id'),
                                name: $(this).data('name'),
                                weight_g: parseInt($(this).data('weight')) || 0,
                                weight: parseInt($(this).data('weight')) || 0,
                                category: $(this).data('category') || 'other',
                                icon: $(this).data('icon'),
                                quantity: parseInt($(this).data('quantity')) || 1
                            };
                        }
                        
                        if (itemData && (itemData.id || itemData.gear_id)) {
                            console.log('Item data found:', itemData);
                            
                            // Handle both new items from gear library (with .id) and loaded items (with .gear_id)
                            const gearId = itemData.gear_id || itemData.id;
                            
                            // Get current quantity from multiple possible sources
                            const currentQty = parseInt($(this).find('.pack-item-quantity').data('quantity')) || 
                                             parseInt($(this).find('.item-qty-display').text()) || 
                                             parseInt($(this).find('.item-qty').val()) || 
                                             parseInt($(this).data('quantity')) ||
                                             itemData.quantity || 1;
                            
                            items.push({
                                gear_id: gearId,
                                name: itemData.name,
                                weight_g: parseFloat(itemData.weight_g) || parseFloat(itemData.weight) || 0,
                                quantity: currentQty,
                                category: itemData.category || 'other',
                                brand: itemData.brand || '',
                                price: parseFloat(itemData.price) || 0,
                                notes: itemData.notes || '',
                                worn: itemData.worn || false,
                                consumable: itemData.consumable || itemData.category === 'food' || itemData.category === 'water'
                            });
                        } else {
                            console.warn('No valid item data found for pack item:', $(this));
                        }
                    });
                    
                    if (items.length > 0 || sectionId === 'main') {
                        sections.push({
                            id: sectionId,
                            name: sectionName,
                            items: items,
                            order: sections.length
                        });
                    }
                });

                const result = {
                    name: $('#pack-name-input').val() || $('#pack-name').val() || $('#pack-name-display').text() || 'New Pack',
                    description: $('#pack-description').val() || '',
                    capacity_l: parseFloat($('#pack-capacity').val()) || 65,
                    weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
                    type: $('#pack-type').val() || 'custom',
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
            console.log('Loading section:', section);
            
            let sectionEl = $(`.pack-section[data-section="${section.id}"]`);
            
            if (sectionEl.length === 0 && section.id !== 'main' && section.id !== 'worn' && section.id !== 'consumables') {
                // Section doesn't exist and is not a default section, create it
                const newSection = $(`
                    <div class="pack-section" data-section="${section.id}">
                        <div class="section-header">
                            <button class="section-toggle">▼</button>
                            <span class="section-icon">📦</span>
                            <input type="text" class="section-name" value="${section.name || this.getSectionName(section.id)}" placeholder="Section name">
                            <span class="section-weight">0g</span>
                            <button class="btn-delete-section" title="Delete section">×</button>
                        </div>
                        <div class="section-content">
                            <div class="gear-drop-zone dropzone" data-section="${section.id}">
                                <p class="drop-hint">Drag gear here</p>
                            </div>
                        </div>
                    </div>
                `);
                
                $('#pack-sections').append(newSection);
                sectionEl = newSection;
                
                // Bind delete button for new sections
                sectionEl.find('.btn-delete-section').on('click', () => this.deleteSection(section.id));
            } else if (sectionEl.length > 0) {
                // Update section name if element exists
                sectionEl.find('.section-name').val(section.name || this.getSectionName(section.id));
            }
            
            // Clear existing items
            const dropzone = sectionEl.find('.gear-drop-zone, .dropzone');
            dropzone.empty();
            
            // Add items to section
            if (section.items && section.items.length > 0) {
                console.log(`Adding ${section.items.length} items to section ${section.id}`);
                section.items.forEach(item => {
                    this.addItemToSection(item, section.id);
                });
            } else {
                dropzone.html('<p class="drop-hint">Drag gear here</p>');
            }
            
            // Update section weight
            this.updateSectionWeight(section.id);
        },

        // Add item to section
        addItemToSection: function(item, sectionId) {
            const dropzone = $(`.gear-drop-zone[data-section="${sectionId}"], .dropzone[data-section="${sectionId}"]`);
            
            if (!dropzone.length) {
                console.error(`Dropzone not found for section: ${sectionId}`);
                return;
            }
            
            // Remove placeholder if exists
            dropzone.find('.dropzone-placeholder').remove();
            
            // Get icon for item
            const icon = item.icon || this.getCategoryIcon(item.category || 'other');
            
            // Create pack item element
            const packItem = $(`
                <div class="pack-item" data-item-id="${item.gear_id || item.id}">
                    <span class="item-handle">≡</span>
                    <span class="item-icon">${icon}</span>
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
                weight_g: parseFloat(item.weight_g) || parseFloat(item.weight) || 0,
                quantity: parseInt(item.quantity) || 1,
                category: item.category || 'other',
                brand: item.brand || '',
                price: parseFloat(item.price) || 0,
                notes: item.notes || item.description || '',
                icon: icon,
                worn: item.worn || false,
                consumable: item.consumable || false,
                section: sectionId
            };
            packItem.data('item', completeItemData);
            
            // Bind events
            packItem.find('.btn-remove-item').on('click', () => {
                packItem.fadeOut(200, () => {
                    packItem.remove();
                    this.state.isDirty = true;
                    this.updateSectionWeight(sectionId);
                    this.updateWeightSummary();
                    
                    // Add placeholder if no items left
                    if (dropzone.find('.pack-item').length === 0) {
                        dropzone.html('<div class="dropzone-placeholder">Drop gear here</div>');
                    }
                });
            });

            // Quantity controls
            const qtyDecrease = packItem.find('.qty-decrease');
            const qtyIncrease = packItem.find('.qty-increase');
            const qtyDisplay = packItem.find('.item-qty-display');
            
            qtyDecrease.on('click', () => {
                const currentQty = parseInt(qtyDisplay.text()) || 1;
                if (currentQty > 1) {
                    const newQty = currentQty - 1;
                    qtyDisplay.text(newQty);
                    const itemData = packItem.data('item');
                    if (itemData) {
                        itemData.quantity = newQty;
                        packItem.find('.item-weight').text(itemData.weight_g * newQty + 'g');
                        packItem.data('item', itemData);
                        this.state.isDirty = true;
                        this.updateSectionWeight(sectionId);
                        this.updateWeightSummary();
                    }
                }
            });
            
            qtyIncrease.on('click', () => {
                const currentQty = parseInt(qtyDisplay.text()) || 1;
                if (currentQty < 99) {
                    const newQty = currentQty + 1;
                    qtyDisplay.text(newQty);
                    const itemData = packItem.data('item');
                    if (itemData) {
                        itemData.quantity = newQty;
                        packItem.find('.item-weight').text(itemData.weight_g * newQty + 'g');
                        packItem.data('item', itemData);
                        this.state.isDirty = true;
                        this.updateSectionWeight(sectionId);
                        this.updateWeightSummary();
                    }
                }
            });

            dropzone.append(packItem);
            
            // Update section weight after adding item
            this.updateSectionWeight(sectionId);
        },

        // Clear all sections
        clearSections: function() {
            // Only clear items from existing sections, don't remove default sections
            $('.pack-section').each(function() {
                const dropzone = $(this).find('.dropzone');
                dropzone.empty();
                dropzone.html('<p class="drop-hint">Drag gear here</p>');
                $(this).find('.section-weight').text('0g');
            });
            
            // Remove any custom sections (not main, worn, or consumables)
            $('.pack-section').each(function() {
                const sectionId = $(this).data('section');
                if (sectionId !== 'main' && sectionId !== 'worn' && sectionId !== 'consumables') {
                    $(this).remove();
                }
            });
        },

        // Reset builder
        resetBuilder: function() {
            this.state.currentPackId = null;
            this.state.isDirty = false;
            
            $('#pack-name-input').val('');
            $('#pack-description').val('');
            $('#pack-capacity').val(65);
            $('#pack-type').val('custom');
            
            // Update display elements
            $('#pack-name-display').text('New Pack');
            $('#quick-total-weight').text('0g');
            $('#quick-total-items').text('0');
            
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

        // Helper methods for achievements
        calculateTotalWeight: function() {
            let totalWeight = 0;
            const sections = document.querySelectorAll('.pack-section');
            
            sections.forEach(section => {
                const items = section.querySelectorAll('.pack-item');
                items.forEach(item => {
                    const weight = parseFloat(item.dataset.weight) || 0;
                    totalWeight += weight;
                });
            });
            
            return totalWeight / 1000; // Convert to kg
        },

        countItems: function() {
            return document.querySelectorAll('.pack-item').length;
        },

        countSections: function() {
            const sections = document.querySelectorAll('.pack-section');
            let count = 0;
            
            sections.forEach(section => {
                const items = section.querySelectorAll('.pack-item');
                if (items.length > 0) {
                    count++;
                }
            });
            
            return count;
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
        },
        
        // Helper function to get section name
        getSectionName: function(sectionId) {
            const sectionNames = {
                'main': 'Main Pack',
                'worn': 'Worn Items',
                'consumables': 'Consumables',
                'emergency': 'Emergency Kit',
                'electronics': 'Electronics',
                'cooking': 'Cooking Gear',
                'shelter': 'Shelter System'
            };
            return sectionNames[sectionId] || 'Section';
        },
        
        // Helper function to get category icon
        getCategoryIcon: function(category) {
            const icons = {
                'shelter': '⛺',
                'sleep': '🛌',
                'cooking': '🔥',
                'water': '💧',
                'clothing': '👕',
                'navigation': '🗺️',
                'hygiene': '🧼',
                'first-aid': '🏥',
                'electronics': '📱',
                'food': '🍔',
                'footwear': '👟',
                'repair': '🔧',
                'other': '📦',
                'tools': '🔧',
                'pack': '🎒'
            };
            return icons[category] || '📦';
        },
        
        // Update individual section weight
        updateSectionWeight: function(sectionId) {
            const section = $(`.pack-section[data-section-id="${sectionId}"]`);
            let sectionWeight = 0;
            
            section.find('.pack-item').each(function() {
                const item = $(this).data('item');
                const qty = parseInt($(this).find('.item-qty').val()) || 1;
                sectionWeight += (parseFloat(item.weight_g) || 0) * qty;
            });
            
            section.find('.section-weight').text(this.formatWeight(sectionWeight));
        },
        
        // Ensure default sections exist
        ensureDefaultSections: function() {
            if ($('.pack-section[data-section-id="main"]').length === 0) {
                const mainSection = {
                    id: 'main',
                    name: 'Main Pack',
                    items: []
                };
                this.loadSection(mainSection);
            }
        },
        
        // Delete a section
        deleteSection: function(sectionId) {
            if (sectionId === 'main') {
                this.showError('Cannot delete the main pack section');
                return;
            }
            
            if (confirm('Delete this section and all its items?')) {
                $(`.pack-section[data-section-id="${sectionId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
                this.state.isDirty = true;
                this.updateWeightSummary();
            }
        },
        
        // Toast notification utility
        showToast: function(message, type = 'info') {
            const toast = $(`<div class="pack-toast pack-toast-${type}">${message}</div>`);
            $('body').append(toast);
            
            setTimeout(() => toast.addClass('show'), 100);
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if we're on the backpacks page
        if (!$('body').hasClass('backpacks-forest-page') && !$('#view-my-packs').length) {
            console.log('PackBuilderCRUD: Not on backpacks page, skipping initialization');
            return;
        }
        
        // Wait a moment for other scripts to initialize
        setTimeout(function() {
            // Skip initialization here - let backpacks-init.js handle it
            console.log('PackBuilderCRUD: Skipping self-initialization (handled by backpacks-init.js)');
            
            // Integrate with main PackBuilder if available
            if (window.PackBuilder && !window.PackBuilder._crudIntegrated) {
                console.log('PackBuilderCRUD: Integrating with PackBuilder');
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
                
                // Mark as integrated
                window.PackBuilder._crudIntegrated = true;
            }
        }, 200);  // Small delay to ensure everything is loaded
    });

    // Expose to global scope
    window.PackBuilderCRUD = PackBuilderCRUD;

})(jQuery);
