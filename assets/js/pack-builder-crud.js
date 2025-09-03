/**
 * Pack Builder CRUD Operations
 * Handles saving, loading, updating, and deleting backpacks
 * @version 2.0.0
 */

(function($) {
    'use strict';

    const PackBuilderCRUD = {
        // API endpoints
        api: {
            base: '/BTT/api/index.php?route=backpacks',
            get: (id) => `/BTT/api/index.php?route=backpacks&id=${id}`,
            create: '/BTT/api/index.php?route=backpacks',
            update: (id) => `/BTT/api/index.php?route=backpacks&id=${id}`,
            delete: (id) => `/BTT/api/index.php?route=backpacks&id=${id}`
        },

        // Current state
        state: {
            currentPackId: null,
            isDirty: false,
            sections: []
        },

        // Initialize
        init: function() {
            console.log('🎒 Pack Builder CRUD initializing...');
            this.bindEvents();
            this.loadExistingPacks();
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
            // Collect pack data
            const packData = this.collectPackData();
            
            // Validate
            if (!packData.name || packData.name.trim() === '') {
                this.showError('Please enter a pack name');
                $('#pack-name').focus();
                return;
            }

            try {
                let response;
                
                if (this.state.currentPackId) {
                    // Update existing pack
                    response = await $.ajax({
                        url: this.api.update(this.state.currentPackId),
                        method: 'PUT',
                        contentType: 'application/json',
                        data: JSON.stringify(packData)
                    });
                } else {
                    // Create new pack
                    response = await $.ajax({
                        url: this.api.create,
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(packData)
                    });
                }

                if (response.success) {
                    this.state.isDirty = false;
                    
                    // Update current pack ID if it was a create
                    if (!this.state.currentPackId && response.data && response.data.id) {
                        this.state.currentPackId = response.data.id;
                    }
                    
                    this.showSuccess(this.state.currentPackId ? 'Pack updated successfully!' : 'Pack created successfully!');
                    
                    // Reload pack list
                    this.loadExistingPacks();
                    
                    // Switch to My Packs view
                    setTimeout(() => {
                        this.switchToMyPacks();
                    }, 1500);
                } else {
                    this.showError(response.error || 'Failed to save pack');
                }
            } catch (error) {
                console.error('Save error:', error);
                this.showError('Failed to save pack: ' + (error.responseJSON?.error || error.statusText));
            }
        },

        // Load pack for editing
        loadPackForEdit: async function(packId) {
            try {
                const response = await $.ajax({
                    url: this.api.get(packId),
                    method: 'GET'
                });

                if (response.success) {
                    const pack = response.data;
                    
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
                } else {
                    this.showError('Failed to load pack');
                }
            } catch (error) {
                console.error('Load error:', error);
                this.showError('Failed to load pack');
            }
        },

        // Delete pack
        deletePack: async function(packId, force = false) {
            if (!force && !confirm('Are you sure you want to delete this pack? This cannot be undone.')) {
                return;
            }

            try {
                const url = force ? 
                    `${this.api.delete(packId)}&force=true` : 
                    this.api.delete(packId);
                    
                const response = await $.ajax({
                    url: url,
                    method: 'DELETE'
                });

                if (response.success) {
                    this.showSuccess('Pack deleted successfully');
                    
                    // Remove from UI
                    $(`.pack-card[data-pack-id="${packId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Reload pack list
                    this.loadExistingPacks();
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
                // First, load the pack
                const getResponse = await $.ajax({
                    url: this.api.get(packId),
                    method: 'GET'
                });

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

                // Create the duplicate
                const createResponse = await $.ajax({
                    url: this.api.create,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(packCopy)
                });

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
                const response = await $.ajax({
                    url: this.api.base,
                    method: 'GET'
                });

                if (response.success && response.data) {
                    this.renderPacksList(response.data);
                }
            } catch (error) {
                console.error('Failed to load packs:', error);
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
                        items.push({
                            gear_id: itemData.id,
                            name: itemData.name,
                            weight_g: itemData.weight_g || 0,
                            quantity: parseInt($(this).find('.item-qty').val()) || 1,
                            category: itemData.category || 'other',
                            notes: itemData.notes || '',
                            worn: false,
                            consumable: itemData.category === 'food' || itemData.category === 'water'
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

            return {
                name: $('#pack-name').val(),
                description: $('#pack-description').val(),
                capacity_l: parseFloat($('#pack-capacity').val()) || 65,
                weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
                type: 'custom',
                sections: sections
            };
        },

        // Load section into builder
        loadSection: function(section) {
            const sectionEl = $(`.pack-section[data-section-id="${section.id}"]`);
            
            if (sectionEl.length === 0) {
                // Section doesn't exist, create it
                // This would need to be implemented based on your section creation logic
                return;
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

            // Store item data
            packItem.data('item', item);
            
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
        PackBuilderCRUD.init();
        
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
    });

    // Expose to global scope
    window.PackBuilderCRUD = PackBuilderCRUD;

})(jQuery);
