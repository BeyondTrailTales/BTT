/**
 * Gear Library Enhancement JS
 * Handles the enhanced gear library with default/custom gear management
 * @version 2.0.0
 */

(function($) {
    'use strict';

    const GearLibrary = {
        // State management
        state: {
            viewMode: 'both',
            currentView: 'cards',
            gearItems: [],
            filters: {
                search: '',
                category: '',
                sort: 'name',
                sortDir: 'asc'
            },
            preferences: {},
            loading: false
        },

        // API endpoints - use ajax-handler to avoid timeouts
        api: {
            base: '/BTT/ajax-handler.php?route=gear',
            prefs: '/BTT/ajax-handler.php?route=gear&id=prefs'
        },

        // Initialize
        init: function() {
            console.log('🎒 Gear Library initializing...');
            
            // Load preferences first
            this.loadPreferences().then(() => {
                // Load gear items
                this.loadGearItems();
                
                // Bind events
                this.bindEvents();
            });
        },

        // Bind all event handlers
        bindEvents: function() {
            const self = this;

            // View mode toggle
            $('input[name="gear-view-mode"]').on('change', function() {
                self.state.viewMode = $(this).val();
                self.updateViewIndicator();
                self.savePreference('view_mode', self.state.viewMode);
                self.loadGearItems();
            });

            // Search input
            $('#gear-library-search').on('input', self.debounce(function() {
                self.state.filters.search = $(this).val();
                self.filterAndRenderItems();
            }, 300));

            // Category filter
            $('#gear-category-filter').on('change', function() {
                self.state.filters.category = $(this).val();
                self.loadGearItems();
            });

            // Sort select
            $('#gear-sort-select').on('change', function() {
                self.state.filters.sort = $(this).val();
                self.sortAndRenderItems();
            });

            // Add custom gear button
            $('#btn-add-custom-gear').on('click', function() {
                self.showAddGearModal();
            });

            // (Removed manage hidden button - using filters instead)

            // Toggle view button
            $('#btn-toggle-view').on('click', function() {
                self.toggleViewMode();
            });

            // Item actions (delegated)
            $(document).on('click', '.btn-hide-item', function() {
                const itemId = $(this).data('item-id');
                self.hideDefaultItem(itemId);
            });
            
            $(document).on('click', '.btn-restore-item', function() {
                const itemId = $(this).data('item-id');
                self.restoreDefaultItem(itemId);
            });

            $(document).on('click', '.btn-edit-item', function() {
                const itemId = $(this).data('item-id');
                self.editCustomItem(itemId);
            });

            $(document).on('click', '.btn-delete-item', function() {
                const itemId = $(this).data('item-id');
                self.deleteCustomItem(itemId);
            });

            // Make items draggable for pack builder
            $(document).on('dragstart', '.gear-library-item', function(e) {
                const item = $(this).data('item');
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('gear', JSON.stringify(item));
                $(this).addClass('dragging');
            });

            $(document).on('dragend', '.gear-library-item', function() {
                $(this).removeClass('dragging');
            });
        },

        // Load user preferences
        loadPreferences: async function() {
            try {
                const response = await $.ajax({
                    url: this.api.prefs,
                    method: 'GET'
                });

                if (response.success && response.data) {
                    this.state.preferences = response.data;
                    
                    // Apply preferences
                    if (response.data.view_mode) {
                        this.state.viewMode = response.data.view_mode;
                        $(`input[name="gear-view-mode"][value="${response.data.view_mode}"]`).prop('checked', true);
                    }
                }
            } catch (error) {
                console.error('Failed to load preferences:', error);
            }
        },

        // Load gear items from API
        loadGearItems: async function() {
            if (this.state.loading) return;
            
            this.state.loading = true;
            this.showLoading();

            try {
                const params = {
                    view: this.state.viewMode,
                    category: this.state.filters.category,
                    sort: this.state.filters.sort,
                    dir: this.state.filters.sortDir
                };

                const response = await $.ajax({
                    url: this.api.base,
                    method: 'GET',
                    data: params
                });

                // Handle both response formats
                if (Array.isArray(response)) {
                    // Simple array response from ajax-handler
                    this.state.gearItems = response;
                    this.updateStats({total: response.length});
                    this.renderItems();
                } else if (response.success) {
                    // Complex response format
                    this.state.gearItems = response.data.items || [];
                    this.updateStats(response.data.stats);
                    this.renderItems();
                } else {
                    this.state.gearItems = [];
                    this.renderItems();
                }
            } catch (error) {
                console.error('Failed to load gear:', error);
                this.showError('Failed to load gear items');
            } finally {
                this.state.loading = false;
                this.hideLoading();
            }
        },

        // Render gear items
        renderItems: function() {
            const container = $('#gear-items-container');
            container.empty();

            if (this.state.gearItems.length === 0) {
                container.html('<div class="no-items">No gear items found</div>');
                return;
            }

            // Filter by search locally
            let items = this.state.gearItems;
            if (this.state.filters.search) {
                const search = this.state.filters.search.toLowerCase();
                items = items.filter(item => {
                    const searchIn = (
                        item.name + ' ' + 
                        item.category + ' ' + 
                        (item.tags ? item.tags.join(' ') : '') + ' ' +
                        (item.notes || '')
                    ).toLowerCase();
                    return searchIn.includes(search);
                });
            }

            // Render based on view mode
            if (this.state.currentView === 'cards') {
                this.renderCardsView(items, container);
            } else {
                this.renderListView(items, container);
            }
        },

        // Render cards view
        renderCardsView: function(items, container) {
            container.removeClass('gear-items-list').addClass('gear-items-grid');
            
            items.forEach(item => {
                const card = this.createGearCard(item);
                container.append(card);
            });
        },

        // Render list view
        renderListView: function(items, container) {
            container.removeClass('gear-items-grid').addClass('gear-items-list');
            
            const table = $('<table class="gear-list-table"></table>');
            const thead = $(`
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Weight</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            `);
            const tbody = $('<tbody></tbody>');
            
            items.forEach(item => {
                const row = this.createGearRow(item);
                tbody.append(row);
            });
            
            table.append(thead).append(tbody);
            container.append(table);
        },

        // Create gear card element
        createGearCard: function(item) {
            const isDefault = item.is_default;
            const badge = isDefault ? 
                '<span class="badge badge-default">Default</span>' : 
                '<span class="badge badge-custom">Custom</span>';
            
            const actions = this.getItemActions(item);
            
            const card = $(`
                <div class="gear-library-item gear-card ${isDefault ? 'default-item' : 'custom-item'}" 
                     draggable="true" data-item-id="${item.id}">
                    <div class="gear-card-header">
                        <span class="gear-icon">${item.icon || '📦'}</span>
                        ${badge}
                    </div>
                    <div class="gear-card-body">
                        <h4 class="gear-name">${item.name}</h4>
                        <div class="gear-meta">
                            <span class="gear-category">${this.formatCategory(item.category)}</span>
                            <span class="gear-weight">${this.formatWeight(item.weight_g)}</span>
                        </div>
                        ${item.notes ? `<p class="gear-notes" title="${item.notes}">${item.notes}</p>` : ''}
                    </div>
                    <div class="gear-card-footer">
                        ${actions}
                    </div>
                </div>
            `);
            
            card.data('item', item);
            return card;
        },

        // Create gear row for list view
        createGearRow: function(item) {
            const isDefault = item.is_default;
            const badge = isDefault ? 
                '<span class="badge badge-default">Default</span>' : 
                '<span class="badge badge-custom">Custom</span>';
            
            const actions = this.getItemActions(item);
            
            const row = $(`
                <tr class="gear-library-item ${isDefault ? 'default-item' : 'custom-item'}" 
                    draggable="true" data-item-id="${item.id}">
                    <td>
                        <span class="gear-icon">${item.icon || '📦'}</span>
                        ${item.name}
                    </td>
                    <td>${this.formatCategory(item.category)}</td>
                    <td>${this.formatWeight(item.weight_g)}</td>
                    <td>${badge}</td>
                    <td>${actions}</td>
                </tr>
            `);
            
            row.data('item', item);
            return row;
        },

        // Get action buttons for item
        getItemActions: function(item) {
            let actions = '';
            
            if (item.is_default) {
                // If item is hidden, show restore button
                if (item.is_hidden) {
                    actions = `
                        <button class="btn-icon btn-restore-item" data-item-id="${item.id}" title="Restore this item">
                            <i>♻️</i> Restore
                        </button>
                    `;
                } else {
                    // Otherwise show hide button
                    actions = `
                        <button class="btn-icon btn-hide-item" data-item-id="${item.id}" title="Hide this item">
                            <i>👁️</i>
                        </button>
                    `;
                }
            } else {
                actions = `
                    <button class="btn-icon btn-edit-item" data-item-id="${item.id}" title="Edit">
                        <i>✏️</i>
                    </button>
                    <button class="btn-icon btn-delete-item" data-item-id="${item.id}" title="Delete">
                        <i>🗑️</i>
                    </button>
                `;
            }
            
            return actions;
        },

        // Hide default item
        hideDefaultItem: async function(itemId) {
            try {
                const response = await $.ajax({
                    url: `/BTT/api/index.php?route=gear&id=default&action=hide&sub_id=${itemId}`,
                    method: 'POST'
                });

                if (response.success) {
                    // Remove from view
                    $(`[data-item-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Update items array
                    this.state.gearItems = this.state.gearItems.filter(item => item.id !== itemId);
                    
                    this.showSuccess('Item hidden successfully');
                }
            } catch (error) {
                console.error('Failed to hide item:', error);
                this.showError('Failed to hide item');
            }
        },
        
        // Restore hidden default item
        restoreDefaultItem: async function(itemId) {
            try {
                const response = await $.ajax({
                    url: `/BTT/api/index.php?route=gear&id=default&action=hide&sub_id=${itemId}`,
                    method: 'DELETE'  // DELETE method removes from hidden list
                });

                if (response.success) {
                    // Remove from view (since we're in hidden view)
                    $(`[data-item-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Update items array
                    this.state.gearItems = this.state.gearItems.filter(item => item.id !== itemId);
                    
                    // Update count if no more hidden items
                    if (this.state.gearItems.length === 0) {
                        $('#gear-items-container').html('<div class="no-items">No hidden items</div>');
                    }
                    
                    this.showSuccess('Item restored successfully');
                }
            } catch (error) {
                console.error('Failed to restore item:', error);
                this.showError('Failed to restore item');
            }
        },

        // Delete custom item
        deleteCustomItem: async function(itemId) {
            if (!confirm('Are you sure you want to delete this custom gear item?')) {
                return;
            }

            try {
                const response = await $.ajax({
                    url: `/BTT/api/index.php?route=gear&id=${itemId}`,
                    method: 'DELETE'
                });

                if (response.success) {
                    // Remove from view
                    $(`[data-item-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Update items array
                    this.state.gearItems = this.state.gearItems.filter(item => item.id !== itemId);
                    
                    this.showSuccess('Item deleted successfully');
                }
            } catch (error) {
                console.error('Failed to delete item:', error);
                this.showError('Failed to delete item');
            }
        },

        // Edit custom item
        editCustomItem: async function(itemId) {
            const self = this;
            
            // Find the item in state
            const item = this.state.gearItems.find(g => g.id == itemId);
            if (!item) {
                this.showError('Item not found');
                return;
            }
            
            // Show the modal with item data
            const existingModal = $('#custom-gear-panel');
            if (existingModal.length) {
                existingModal.show();
                existingModal.data('edit-id', itemId);
                
                // Populate form with existing data
                $('#custom-name').val(item.name);
                $('#custom-weight').val(item.weight_g);
                $('#custom-category').val(item.category);
                $('#custom-notes').val(item.notes || '');
                
                // Bind close button
                $('#close-custom-gear, #cancel-custom').off('click').on('click', function() {
                    existingModal.hide();
                    existingModal.removeData('edit-id');
                });
                
                // Update button text and bind save for edit
                $('#save-custom').text('Update Item').off('click').on('click', function(e) {
                    e.preventDefault();
                    self.updateCustomGear(itemId);
                });
            }
        },

        // Show add gear modal
        showAddGearModal: function() {
            const self = this;
            // Use existing modal or create new one
            const existingModal = $('#custom-gear-panel');
            if (existingModal.length) {
                existingModal.show();
                existingModal.removeData('edit-id');
                
                // Clear form
                $('#custom-name').val('');
                $('#custom-weight').val('');
                $('#custom-category').val('other');
                $('#custom-notes').val('');
                
                // Bind close button
                $('#close-custom-gear, #cancel-custom').off('click').on('click', function() {
                    existingModal.hide();
                });
                
                // Update button text and bind save
                $('#save-custom').text('Add to Library').off('click').on('click', function(e) {
                    e.preventDefault();
                    self.saveCustomGear();
                });
            }
        },

        // Save custom gear
        saveCustomGear: async function() {
            const data = {
                name: $('#custom-name').val().trim(),
                weight_g: parseFloat($('#custom-weight').val()) || 0,
                category: $('#custom-category').val(),
                notes: $('#custom-notes').val().trim()
            };

            // Validate
            if (!data.name) {
                this.showError('Name is required');
                $('#custom-name').focus();
                return;
            }
            
            if (data.weight_g <= 0 || isNaN(data.weight_g)) {
                this.showError('Weight must be a positive number');
                $('#custom-weight').focus();
                return;
            }

            try {
                const response = await $.ajax({
                    url: this.api.base,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(data)
                });

                if (response.success) {
                    $('#custom-gear-panel').hide();
                    this.showSuccess('Custom gear added successfully');
                    this.loadGearItems();
                }
            } catch (error) {
                console.error('Failed to save gear:', error);
                const errorMsg = error.responseJSON?.error || 'Failed to save gear';
                this.showError(errorMsg);
            }
        },
        
        // Update custom gear
        updateCustomGear: async function(itemId) {
            const data = {
                name: $('#custom-name').val().trim(),
                weight_g: parseFloat($('#custom-weight').val()) || 0,
                category: $('#custom-category').val(),
                notes: $('#custom-notes').val().trim()
            };

            // Validate
            if (!data.name) {
                this.showError('Name is required');
                $('#custom-name').focus();
                return;
            }
            
            if (data.weight_g <= 0 || isNaN(data.weight_g)) {
                this.showError('Weight must be a positive number');
                $('#custom-weight').focus();
                return;
            }

            try {
                const response = await $.ajax({
                    url: `/BTT/api/index.php?route=gear&id=${itemId}`,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify(data)
                });

                if (response.success) {
                    $('#custom-gear-panel').hide();
                    $('#custom-gear-panel').removeData('edit-id');
                    this.showSuccess('Item updated successfully');
                    this.loadGearItems();
                }
            } catch (error) {
                console.error('Failed to update gear:', error);
                const errorMsg = error.responseJSON?.error || 'Failed to update gear';
                this.showError(errorMsg);
            }
        },

        // (Removed showManageHiddenModal and resetHiddenItems - using filters instead)

        // Toggle between card and list view
        toggleViewMode: function() {
            if (this.state.currentView === 'cards') {
                this.state.currentView = 'list';
                $('#view-toggle-text').text('List View');
            } else {
                this.state.currentView = 'cards';
                $('#view-toggle-text').text('Card View');
            }
            this.renderItems();
        },

        // Update view indicator
        updateViewIndicator: function() {
            const text = {
                'both': 'All Gear',
                'default': 'Default Only',
                'custom': 'Custom Only',
                'hidden': 'Hidden Items'
            };
            $('#gear-view-indicator').text(text[this.state.viewMode] || 'All Gear');
        },

        // Update stats
        updateStats: function(stats) {
            if (stats) {
                $('#gear-total-count').text(stats.total_items || 0);
                $('#gear-total-weight').text(this.formatWeight(stats.total_weight || 0));
            }
        },

        // Format weight
        formatWeight: function(grams) {
            const prefs = this.state.preferences;
            if (prefs.preferred_units === 'oz') {
                const oz = (grams / 28.35).toFixed(1);
                return `${oz}oz`;
            }
            
            if (grams >= 1000) {
                return `${(grams / 1000).toFixed(2)}kg`;
            }
            return `${grams}g`;
        },

        // Format category name
        formatCategory: function(category) {
            const formatted = category.replace(/-/g, ' ');
            return formatted.charAt(0).toUpperCase() + formatted.slice(1);
        },

        // Save preference
        savePreference: async function(key, value) {
            const data = {};
            data[key] = value;
            
            try {
                await $.ajax({
                    url: this.api.prefs,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify(data)
                });
            } catch (error) {
                console.error('Failed to save preference:', error);
            }
        },

        // Show loading spinner
        showLoading: function() {
            const container = $('#gear-items-container');
            if (container.find('.loading-spinner').length === 0) {
                container.html(`
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading gear library...</p>
                    </div>
                `);
            }
        },

        // Hide loading spinner
        hideLoading: function() {
            $('#gear-items-container .loading-spinner').remove();
        },

        // Show success message
        showSuccess: function(message) {
            // Create toast notification
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

        // Show error message
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

        // Filter and render items locally
        filterAndRenderItems: function() {
            this.renderItems();
        },

        // Sort and render items
        sortAndRenderItems: function() {
            const sort = this.state.filters.sort;
            
            this.state.gearItems.sort((a, b) => {
                switch (sort) {
                    case 'weight':
                        return a.weight_g - b.weight_g;
                    case 'category':
                        return a.category.localeCompare(b.category);
                    case 'name':
                    default:
                        return a.name.localeCompare(b.name);
                }
            });
            
            if (this.state.filters.sortDir === 'desc') {
                this.state.gearItems.reverse();
            }
            
            this.renderItems();
        },

        // Debounce helper
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when gear library tab is activated  
    $(document).on('click', '[data-view="gear-library"], .pack-tab[data-view="gear-library"]', function() {
        // Small delay to ensure view is visible
        setTimeout(() => {
            if (!GearLibrary.initialized) {
                console.log('Initializing Gear Library from tab click');
                GearLibrary.init();
                GearLibrary.initialized = true;
            } else {
                console.log('Reloading Gear Library items');
                GearLibrary.loadGearItems();
            }
        }, 100);
    });

    // Also check if gear library is already visible on page load
    $(document).ready(function() {
        // Check if we're on the gear library tab
        if ($('#view-gear-library').hasClass('active') || $('.pack-tab[data-view="gear-library"]').hasClass('active')) {
            console.log('Gear Library tab is active on load');
            setTimeout(() => {
                GearLibrary.init();
                GearLibrary.initialized = true;
            }, 500);
        }
    });

    // Expose to global scope for debugging
    window.GearLibrary = GearLibrary;

})(jQuery);
