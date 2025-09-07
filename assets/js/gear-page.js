/**
 * Gear Page Management System
 * Handles all gear inventory CRUD operations with filtering and accessibility
 * 
 * @package BeyondTrailTales
 * @version 1.0.0
 */

(function($, window, document) {
    'use strict';

    /**
     * Gear Manager - Main controller for gear page
     */
    window.GearManager = {
        // Configuration
        config: {
            apiUrl: window.GearConfig?.apiUrl || '/api',
            csrfToken: window.GearConfig?.csrfToken || '',
            debounceDelay: 300
        },

        // Application state
        state: {
            items: [],
            filteredItems: [],
            customItems: [],
            defaultItems: [],
            filters: {
                search: '',
                category: '',
                tags: []
            },
            currentTab: 'all',
            viewMode: 'grid',
            displayDensity: 'comfortable',
            sort: {
                field: 'name',
                direction: 'asc'
            },
            weightUnit: 'grams',
            isLoading: false,
            currentEditItem: null
        },

        // Weight conversion constants
        WEIGHT_CONVERSIONS: {
            grams: 1,
            ounces: 28.3495,
            pounds: 453.592
        },

        /**
         * Initialize the gear manager with modern compatibility
         */
        init: function() {
            console.log('GearManager.init() called');
            
            // Initialize compatibility layer if available
            if (window.BTTCompat) {
                window.BTTCompat.initializeElement(document.body);
            }
            
            // Connect to state manager if available
            if (window.BTTState) {
                this.initStateConnections();
            }
            
            this.bindEvents();
            this.restoreUserPreferences();
            this.loadGear();
        },

        /**
         * Initialize connections to centralized state manager
         */
        initStateConnections: function() {
            // Subscribe to gear data changes
            this.unsubscribeGearData = window.BTTState.subscribe('data.gear', (newGear, oldGear) => {
                if (newGear && Array.isArray(newGear)) {
                    this.state.items = newGear;
                    this.state.customItems = newGear.filter(item => !item.is_default);
                    this.state.defaultItems = newGear.filter(item => item.is_default);
                    this.updateTabCounts();
                    this.filterAndRender();
                }
            });
            
            // Subscribe to gear filters
            this.unsubscribeFilters = window.BTTState.subscribe('filters.gear', (newFilters) => {
                if (newFilters) {
                    this.state.filters = { ...this.state.filters, ...newFilters };
                    this.filterAndRender();
                }
            });
            
            // Subscribe to loading state
            this.unsubscribeLoading = window.BTTState.subscribe('ui.loading', (loading) => {
                this.state.isLoading = loading;
                this.renderItems();
            });
        },

        /**
         * Bind all event listeners
         */
        bindEvents: function() {
            const self = this;

            // Add gear button
            $('#btn-add-gear').on('click', function() {
                self.showAddModal();
            });

            // Search with debounce - use the main search bar
            let searchTimeout;
            $('#gear-search-main').on('input', function() {
                clearTimeout(searchTimeout);
                const value = $(this).val();
                searchTimeout = setTimeout(function() {
                    self.state.filters.search = value;
                    self.filterAndRender();
                }, self.config.debounceDelay);
            });

            // Tab switching
            $('.gear-tab').on('click', function() {
                const tab = $(this).data('tab');
                
                // Remove active class from all tabs
                $('.gear-tab').removeClass('active').attr('aria-selected', 'false');
                // Add active class to clicked tab
                $(this).addClass('active').attr('aria-selected', 'true');
                
                // Update current tab
                self.state.currentTab = tab;
                
                // Filter and render based on new tab
                self.filterAndRender();
            });

            // Filter chip buttons
            $('.filter-chip').on('click', function() {
                const category = $(this).data('category');
                
                // Remove active class from all chips
                $('.filter-chip').removeClass('active');
                // Add active class to clicked chip
                $(this).addClass('active');
                
                // Set filter based on category
                if (category === 'all') {
                    self.state.filters.category = '';
                } else {
                    self.state.filters.category = category;
                }
                
                self.filterAndRender();
            });

            // Sort
            $('#sort-gear').on('change', function() {
                const value = $(this).val();
                if (value === 'weight-asc') {
                    self.state.sort = { field: 'weight_g', direction: 'asc' };
                } else if (value === 'weight-desc') {
                    self.state.sort = { field: 'weight_g', direction: 'desc' };
                } else if (value === 'category') {
                    self.state.sort = { field: 'category', direction: 'asc' };
                } else if (value === 'recent') {
                    self.state.sort = { field: 'created_at', direction: 'desc' };
                } else if (value === 'essential') {
                    self.state.sort = { field: 'essential', direction: 'desc' };
                } else {
                    self.state.sort = { field: 'name', direction: 'asc' };
                }
                self.filterAndRender();
            });

            // View mode buttons
            $('.view-mode-btn').on('click', function() {
                const viewMode = $(this).data('view');
                
                // Remove active class from all view buttons
                $('.view-mode-btn').removeClass('active');
                // Add active class to clicked button
                $(this).addClass('active');
                
                // Update view mode
                self.state.viewMode = viewMode;
                localStorage.setItem('gear-view-mode', viewMode);
                
                self.applyViewMode();
            });

            // Density selector
            $('#density-select').on('change', function() {
                self.state.displayDensity = $(this).val();
                localStorage.setItem('gear-display-density', self.state.displayDensity);
                self.applyDisplayDensity();
            });

            // Weight unit toggle
            $('#weight-unit').on('change', function() {
                self.state.weightUnit = $(this).val();
                localStorage.setItem('gear-weight-unit', self.state.weightUnit);
                self.renderItems();
            });

            // Form submission
            $('#gear-form').on('submit', function(e) {
                e.preventDefault();
                if (self.validateForm()) {
                    self.saveItem();
                }
            });

            // Modal keyboard events
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    if ($('#gear-modal').is(':visible')) {
                        self.closeModal();
                    } else if ($('#delete-modal').is(':visible')) {
                        self.closeDeleteModal();
                    }
                }
            });

            // Focus trap for modals
            this.setupFocusTrap();
            
            // Keyboard navigation for tabs
            $('.gear-tab').on('keydown', function(e) {
                if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                    e.preventDefault();
                    const tabs = $('.gear-tab');
                    const currentIndex = tabs.index(this);
                    let nextIndex;
                    
                    if (e.key === 'ArrowRight') {
                        nextIndex = (currentIndex + 1) % tabs.length;
                    } else {
                        nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
                    }
                    
                    tabs.eq(nextIndex).focus().click();
                } else if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        /**
         * Setup focus trap for modal accessibility
         */
        setupFocusTrap: function() {
            const self = this;
            
            $('#gear-modal, #delete-modal').on('keydown', function(e) {
                if (e.key === 'Tab') {
                    const focusableElements = $(this).find(
                        'button:visible, input:visible, select:visible, textarea:visible, [tabindex]:not([tabindex="-1"]):visible'
                    );
                    
                    const firstElement = focusableElements.first()[0];
                    const lastElement = focusableElements.last()[0];
                    
                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            });
        },

        /**
         * Restore user preferences from localStorage
         */
        restoreUserPreferences: function() {
            const savedUnit = localStorage.getItem('gear-weight-unit');
            if (savedUnit) {
                this.state.weightUnit = savedUnit;
                $('#weight-unit').val(savedUnit);
            }
            
            const savedViewMode = localStorage.getItem('gear-view-mode');
            if (savedViewMode) {
                this.state.viewMode = savedViewMode;
                $('.view-mode-btn').removeClass('active');
                $(`.view-mode-btn[data-view="${savedViewMode}"]`).addClass('active');
            }
            
            const savedDensity = localStorage.getItem('gear-display-density');
            if (savedDensity) {
                this.state.displayDensity = savedDensity;
                $('#density-select').val(savedDensity);
            }
        },

        /**
         * Load gear from API
         */
        loadGear: function() {
            const self = this;
            console.log('Loading gear from:', self.config.apiUrl + '/?route=gear');
            self.setLoading(true);

            $.ajax({
                url: self.config.apiUrl + '?route=gear',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Gear API Response:', response);
                    // AJAX handler returns the items directly from the gear endpoint
                    if (Array.isArray(response)) {
                        self.state.items = response;
                        
                        // Separate custom and default items
                        self.state.customItems = response.filter(item => !item.is_default);
                        self.state.defaultItems = response.filter(item => item.is_default);
                        
                        console.log('Loaded', self.state.items.length, 'total items');
                        console.log('Custom:', self.state.customItems.length, 'Default:', self.state.defaultItems.length);
                        
                        self.updateTabCounts();
                        self.filterAndRender();
                    } else if (response && response.success && response.data && response.data.items) {
                        self.state.items = response.data.items;
                        
                        // Separate custom and default items
                        self.state.customItems = response.data.items.filter(item => !item.is_default);
                        self.state.defaultItems = response.data.items.filter(item => item.is_default);
                        
                        console.log('Loaded', self.state.items.length, 'total items');
                        console.log('Custom:', self.state.customItems.length, 'Default:', self.state.defaultItems.length);
                        
                        self.updateTabCounts();
                        self.filterAndRender();
                    } else {
                        console.error('Invalid gear response format:', response);
                        self.showError('Failed to load gear items');
                        self.setLoading(false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Load gear error:', status, error, xhr);
                    self.showError('Failed to load gear. Please try again.');
                    self.setLoading(false);
                },
                complete: function() {
                    console.log('Load complete');
                    self.setLoading(false);
                }
            });
        },

        /**
         * Filter items and render
         */
        filterAndRender: function() {
            // Start with items based on current tab
            let filtered = [];
            
            if (this.state.currentTab === 'custom') {
                filtered = [...this.state.customItems];
            } else if (this.state.currentTab === 'default') {
                filtered = [...this.state.defaultItems];
            } else {
                // 'all' tab shows everything
                filtered = [...this.state.items];
            }

            // Apply search filter
            if (this.state.filters.search) {
                const search = this.state.filters.search.toLowerCase();
                filtered = filtered.filter(item => {
                    const searchableText = [
                        item.name,
                        item.category,
                        item.notes || '',
                        ...(item.tags || [])
                    ].join(' ').toLowerCase();
                    return searchableText.includes(search);
                });
            }

            // Apply category filter
            if (this.state.filters.category) {
                const category = this.state.filters.category;
                
                if (category === 'favorites') {
                    // Filter for favorited items (if we have a favorite field)
                    filtered = filtered.filter(item => item.is_favorite);
                } else if (category === 'ultralight') {
                    // Filter for ultralight items (under 100g typically)
                    filtered = filtered.filter(item => (item.weight_g || 0) <= 100);
                } else {
                    // Normal category filter
                    filtered = filtered.filter(item => 
                        item.category === category
                    );
                }
            }

            // Apply sorting
            filtered.sort((a, b) => {
                let compareValue = 0;
                const field = this.state.sort.field;
                
                if (field === 'weight_g') {
                    compareValue = (a[field] || 0) - (b[field] || 0);
                } else if (field === 'created_at') {
                    compareValue = new Date(b[field] || 0) - new Date(a[field] || 0);
                } else if (field === 'essential') {
                    // Define essential categories
                    const essentialCategories = ['shelter', 'sleep', 'water', 'first-aid', 'navigation'];
                    const aEssential = essentialCategories.includes(a.category);
                    const bEssential = essentialCategories.includes(b.category);
                    
                    if (aEssential && !bEssential) return -1;
                    if (!aEssential && bEssential) return 1;
                    
                    // If both essential or both non-essential, sort by category then name
                    compareValue = (a.category || '').localeCompare(b.category || '');
                    if (compareValue === 0) {
                        compareValue = (a.name || '').localeCompare(b.name || '');
                    }
                } else {
                    compareValue = (a[field] || '').toString()
                        .localeCompare((b[field] || '').toString());
                }
                
                return this.state.sort.direction === 'desc' ? -compareValue : compareValue;
            });

            this.state.filteredItems = filtered;
            this.renderItems();
            this.updateResultCount();
        },

        /**
         * Render gear items
         */
        renderItems: function() {
            const $container = $('#gear-grid');
            const $loading = $('#gear-loading');
            const $empty = $('#gear-empty');
            const $noResults = $('#gear-no-results');
            const $itemsContainer = $('#gear-items');

            console.log('Rendering items:', this.state.filteredItems.length, 'Loading:', this.state.isLoading);

            // Hide all states first
            $loading.hide();
            $empty.hide();
            $noResults.hide();
            $container.hide();

            if (this.state.isLoading) {
                $loading.show();
                return;
            }

            if (this.state.items.length === 0) {
                $empty.show();
                return;
            }

            if (this.state.filteredItems.length === 0) {
                $noResults.show();
                return;
            }

            // Build items HTML
            const itemsHtml = this.state.filteredItems.map(item => 
                this.renderItemCard(item)
            ).join('');

            // If container doesn't exist, create it
            if ($container.length === 0) {
                $itemsContainer.append('<div class="gear-grid" id="gear-grid"></div>');
            }
            
            $('#gear-grid').html(itemsHtml).show();

            // Apply current view mode and density
            this.applyViewMode();
            
            // Bind item-specific events
            this.bindItemEvents();
        },

        /**
         * Render single item card
         */
        renderItemCard: function(item) {
            const weight = this.convertWeight(item.weight_g || 0, 'grams', this.state.weightUnit);
            const weightDisplay = this.formatWeight(weight, this.state.weightUnit);
            const tags = (item.tags || []).map(tag => 
                `<span class="gear-tag">${this.escapeHtml(tag)}</span>`
            ).join('');
            
            const categoryIcon = this.getCategoryIcon(item.category);
            const isDefault = item.is_default || false;
            const isCustom = item.is_custom || false;

            return `
                <div class="gear-card ${isDefault ? 'default-item' : ''} ${isCustom ? 'custom-item' : ''}" data-item-id="${item.id}" data-category="${item.category || 'other'}">
                    <div class="gear-card-header">
                        <span class="gear-icon" aria-hidden="true">${categoryIcon}</span>
                        <h3 class="gear-name">${this.escapeHtml(item.name)}</h3>
                        ${isDefault ? '<span class="gear-badge default-badge">📚 Default</span>' : ''}
                        ${isCustom ? '<span class="gear-badge custom-badge">🎒 My Gear</span>' : ''}
                    </div>
                    <div class="gear-card-body">
                        <div class="gear-meta">
                            <span class="gear-category gear-category-badge ${item.category || 'other'}">${this.formatCategory(item.category)}</span>
                            <span class="gear-weight">${weightDisplay}</span>
                        </div>
                        ${tags ? `<div class="gear-tags">${tags}</div>` : ''}
                        ${item.notes ? `
                            <div class="gear-notes">
                                <p class="gear-notes-text" data-full-text="${this.escapeHtml(item.notes)}">
                                    ${this.truncateText(item.notes, 100)}
                                </p>
                                ${item.notes.length > 100 ? `
                                    <button class="btn-text btn-expand-notes" aria-label="Expand notes">
                                        Show more
                                    </button>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                    <div class="gear-card-actions">
                        ${!isDefault ? `
                            <button class="btn-icon btn-edit" 
                                    onclick="GearManager.editItem('${item.id}')"
                                    aria-label="Edit ${this.escapeHtml(item.name)}">
                                <span aria-hidden="true">✏️</span>
                            </button>
                            <button class="btn-icon btn-delete" 
                                    onclick="GearManager.confirmDeleteItem('${item.id}')"
                                    aria-label="Delete ${this.escapeHtml(item.name)}">
                                <span aria-hidden="true">🗑️</span>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        },

        /**
         * Bind events for item cards
         */
        bindItemEvents: function() {
            // Expand/collapse notes
            $('.btn-expand-notes').off('click').on('click', function() {
                const $btn = $(this);
                const $notesText = $btn.siblings('.gear-notes-text');
                const fullText = $notesText.data('full-text');
                
                if ($btn.text().trim() === 'Show more') {
                    $notesText.text(fullText);
                    $btn.text('Show less');
                } else {
                    $notesText.text(GearManager.truncateText(fullText, 100));
                    $btn.text('Show more');
                }
            });
        },

        /**
         * Update tab count badges
         */
        updateTabCounts: function() {
            $('#all-count').text(this.state.items.length);
            $('#custom-count').text(this.state.customItems.length);
            $('#default-count').text(this.state.defaultItems.length);
        },

        /**
         * Update result count display
         */
        updateResultCount: function() {
            const count = this.state.filteredItems.length;
            let total = this.state.items.length;
            
            // Adjust total based on current tab
            if (this.state.currentTab === 'custom') {
                total = this.state.customItems.length;
            } else if (this.state.currentTab === 'default') {
                total = this.state.defaultItems.length;
            }
            
            let message = '';
            
            if (this.state.filters.search || this.state.filters.category) {
                message = `Showing ${count} of ${total} items`;
            } else {
                const tabName = this.state.currentTab === 'custom' ? 'custom' : 
                               this.state.currentTab === 'default' ? 'default' : '';
                const tabSuffix = tabName ? ` ${tabName}` : '';
                message = `${count}${tabSuffix} item${count !== 1 ? 's' : ''} total`;
            }

            $('#result-count').text(message);
        },

        /**
         * Show add modal
         */
        showAddModal: function() {
            this.state.currentEditItem = null;
            $('#modal-title').text('Add Gear Item');
            $('#submit-text').text('Add Item');
            $('#gear-form')[0].reset();
            $('.form-error').text('').hide();
            this.openModal();
        },

        /**
         * Edit item
         */
        editItem: function(itemId) {
            const item = this.state.items.find(i => i.id == itemId);
            if (!item) return;

            this.state.currentEditItem = item;
            $('#modal-title').text('Edit Gear Item');
            $('#submit-text').text('Save Changes');

            // Populate form
            $('#gear-id').val(item.id);
            $('#gear-name').val(item.name);
            $('#gear-category').val(item.category);
            
            // Convert weight to selected unit for display
            const currentUnit = $('#gear-weight-unit').val();
            const weight = this.convertWeight(item.weight_g || 0, 'grams', currentUnit);
            $('#gear-weight').val(weight.toFixed(2));
            
            $('#gear-tags').val((item.tags || []).join(', '));
            $('#gear-notes').val(item.notes || '');

            $('.form-error').text('').hide();
            this.openModal();
        },

        /**
         * Open modal with accessibility
         */
        openModal: function() {
            const $modal = $('#gear-modal');
            this.lastFocusedElement = document.activeElement;
            
            $modal.show();
            $modal.attr('aria-hidden', 'false');
            
            // Focus first input
            setTimeout(() => {
                $('#gear-name').focus();
            }, 100);

            // Prevent body scroll
            $('body').css('overflow', 'hidden');
        },

        /**
         * Close modal
         */
        closeModal: function() {
            const $modal = $('#gear-modal');
            $modal.hide();
            $modal.attr('aria-hidden', 'true');
            
            // Restore focus
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }

            // Restore body scroll
            $('body').css('overflow', '');
        },

        /**
         * Validate form
         */
        validateForm: function() {
            let isValid = true;
            $('.form-error').text('').hide();

            // Validate name
            const name = $('#gear-name').val().trim();
            if (!name) {
                $('#name-error').text('Name is required').show();
                isValid = false;
            }

            // Validate category
            const category = $('#gear-category').val();
            if (!category) {
                $('#category-error').text('Please select a category').show();
                isValid = false;
            }

            // Validate weight
            const weight = parseFloat($('#gear-weight').val());
            if (isNaN(weight) || weight < 0) {
                $('#weight-error').text('Please enter a valid weight').show();
                isValid = false;
            }

            return isValid;
        },

        /**
         * Save item (create or update)
         */
        saveItem: function() {
            const self = this;
            const isEdit = !!this.state.currentEditItem;
            
            // Show save animation
            if (window.SaveAnimation) {
                window.SaveAnimation.showSaving('Saving gear item...');
                const saveBtn = $('#gear-modal .btn-primary');
                if (saveBtn.length) {
                    window.SaveAnimation.setButtonLoading(saveBtn[0], true);
                }
            }
            
            // Collect form data
            const weightUnit = $('#gear-weight-unit').val();
            const weightValue = parseFloat($('#gear-weight').val());
            const weightGrams = this.convertWeight(weightValue, weightUnit, 'grams');
            
            const formData = {
                name: $('#gear-name').val().trim(),
                category: $('#gear-category').val(),
                weight_g: weightGrams,
                tags: $('#gear-tags').val().split(',').map(t => t.trim()).filter(t => t),
                notes: $('#gear-notes').val().trim()
            };

            // Determine URL and method for AJAX handler
            let url = self.config.apiUrl + '?route=gear';
            let method = 'POST';
            
            if (isEdit) {
                url += '&id=' + this.state.currentEditItem.id;
                method = 'PUT';
            }

            // Send request
            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(formData),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        // Show success animation
                        if (window.SaveAnimation) {
                            const gearName = formData.name || 'Gear item';
                            const message = isEdit ? 'Gear updated!' : 'Gear added!';
                            const details = `"${gearName}" saved to database (${weightGrams}g)`;
                            window.SaveAnimation.showSuccess(message, details);
                        }
                        
                        self.closeModal();
                        self.showSuccess(isEdit ? 'Item updated successfully' : 'Item added successfully');
                        self.loadGear(); // Reload items
                    } else {
                        // Show error animation
                        if (window.SaveAnimation) {
                            window.SaveAnimation.showError('Failed to save gear', response.message || 'Unknown error');
                        }
                        self.showError(response.message || 'Failed to save item');
                    }
                },
                error: function(xhr) {
                    console.error('Save error:', xhr);
                    const message = xhr.responseJSON?.message || 'Failed to save item';
                    
                    // Show error animation
                    if (window.SaveAnimation) {
                        let errorDetails = message;
                        if (xhr.status === 401) {
                            errorDetails = 'Session expired - please refresh the page';
                        } else if (xhr.status === 500) {
                            errorDetails = 'Server error - please try again';
                        }
                        window.SaveAnimation.showError('Failed to save gear', errorDetails);
                    }
                    
                    self.showError(message);
                },
                complete: function() {
                    // Reset button state
                    if (window.SaveAnimation) {
                        const saveBtn = $('#gear-modal .btn-primary');
                        if (saveBtn.length) {
                            window.SaveAnimation.setButtonLoading(saveBtn[0], false);
                        }
                    }
                }
            });
        },

        /**
         * Confirm delete item
         */
        confirmDeleteItem: function(itemId) {
            const item = this.state.items.find(i => i.id == itemId);
            if (!item) return;

            this.deleteItemId = itemId;
            $('#delete-item-name').text(item.name);
            
            const $modal = $('#delete-modal');
            this.lastFocusedElement = document.activeElement;
            
            $modal.show();
            $modal.attr('aria-hidden', 'false');
            
            // Focus cancel button (safe default)
            setTimeout(() => {
                $modal.find('.btn-secondary').focus();
            }, 100);

            $('body').css('overflow', 'hidden');
        },

        /**
         * Close delete modal
         */
        closeDeleteModal: function() {
            const $modal = $('#delete-modal');
            $modal.hide();
            $modal.attr('aria-hidden', 'true');
            
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }

            $('body').css('overflow', '');
            this.deleteItemId = null;
        },

        /**
         * Confirm delete action
         */
        confirmDelete: function() {
            const self = this;
            if (!this.deleteItemId) return;

            // Show delete animation
            if (window.SaveAnimation) {
                window.SaveAnimation.showSaving('Deleting gear item...');
            }

            $.ajax({
                url: self.config.apiUrl + '?route=gear&id=' + this.deleteItemId,
                method: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        // Show success animation
                        if (window.SaveAnimation) {
                            window.SaveAnimation.showSuccess('Gear deleted!', 'Item removed from database');
                        }
                        
                        self.closeDeleteModal();
                        self.showSuccess('Item deleted successfully');
                        self.loadGear();
                    } else {
                        // Show error animation
                        if (window.SaveAnimation) {
                            window.SaveAnimation.showError('Failed to delete gear', response.message || 'Unknown error');
                        }
                        self.showError(response.message || 'Failed to delete item');
                    }
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    self.showError('Failed to delete item');
                }
            });
        },

        /**
         * Clear all filters
         */
        clearFilters: function() {
            this.state.filters = {
                search: '',
                category: '',
                tags: []
            };
            
            $('#gear-search-main').val('');
            $('.filter-chip').removeClass('active');
            $('.filter-chip[data-category="all"]').addClass('active');
            
            this.filterAndRender();
        },

        /**
         * Switch to a specific tab
         */
        switchToTab: function(tabName) {
            $('.gear-tab').removeClass('active').attr('aria-selected', 'false');
            $(`.gear-tab[data-tab="${tabName}"]`).addClass('active').attr('aria-selected', 'true');
            this.state.currentTab = tabName;
            this.filterAndRender();
        },

        /**
         * Apply view mode to gear grid
         */
        applyViewMode: function() {
            const $grid = $('#gear-grid');
            
            // Remove all view mode classes
            $grid.removeClass('list-view compact ultra-compact');
            
            if (this.state.viewMode === 'list') {
                $grid.addClass('list-view');
            } else {
                // Apply density for grid views
                this.applyDisplayDensity();
            }
        },

        /**
         * Apply display density to gear grid
         */
        applyDisplayDensity: function() {
            const $grid = $('#gear-grid');
            
            // Remove density classes
            $grid.removeClass('compact ultra-compact');
            
            // Only apply density to grid views (not list view)
            if (this.state.viewMode !== 'list') {
                if (this.state.displayDensity === 'compact') {
                    $grid.addClass('compact');
                } else if (this.state.displayDensity === 'ultra-compact') {
                    $grid.addClass('ultra-compact');
                }
            }
        },

        /**
         * Convert weight between units
         */
        convertWeight: function(value, fromUnit, toUnit) {
            if (fromUnit === toUnit) return value;
            
            const grams = value * this.WEIGHT_CONVERSIONS[fromUnit];
            return grams / this.WEIGHT_CONVERSIONS[toUnit];
        },

        /**
         * Format weight display
         */
        formatWeight: function(value, unit) {
            let formatted = value.toFixed(unit === 'pounds' ? 2 : 1);
            
            // Remove unnecessary decimals
            formatted = parseFloat(formatted).toString();
            
            switch(unit) {
                case 'ounces':
                    return formatted + ' oz';
                case 'pounds':
                    return formatted + ' lbs';
                case 'grams':
                default:
                    return formatted + ' g';
            }
        },

        /**
         * Format category display
         */
        formatCategory: function(category) {
            if (!category) return '';
            
            return category
                .replace(/-/g, ' ')
                .replace(/\b\w/g, l => l.toUpperCase());
        },

        /**
         * Get category icon
         */
        getCategoryIcon: function(category) {
            const icons = {
                'shelter': '⛺',
                'sleep': '🛌',
                'cooking': '🔥',
                'water': '💧',
                'clothing': '👕',
                'footwear': '🥾',
                'rain-gear': '🌧️',
                'navigation': '🧭',
                'first-aid': '🏥',
                'emergency': '🚨',
                'electronics': '📱',
                'tools': '🔧',
                'repair': '🛠️',
                'hygiene': '🧼',
                'food-storage': '🥫',
                'other': '📦'
            };
            
            return icons[category] || '📦';
        },

        /**
         * Truncate text
         */
        truncateText: function(text, length) {
            if (!text || text.length <= length) return text;
            return text.substring(0, length) + '...';
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return (text || '').toString().replace(/[&<>"']/g, m => map[m]);
        },

        /**
         * Set loading state
         */
        setLoading: function(isLoading) {
            this.state.isLoading = isLoading;
            if (isLoading) {
                $('#gear-loading').show();
                $('#gear-grid, #gear-empty, #gear-no-results').hide();
            } else {
                $('#gear-loading').hide();
            }
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            this.showToast(message, 'success');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            this.showToast(message, 'error');
        },

        /**
         * Show toast notification with modern compatibility
         */
        showToast: function(message, type) {
            // Use unified toast system if available
            if (window.BTTUtils && window.BTTUtils.showToast) {
                window.BTTUtils.showToast(message, type);
                return;
            }
            
            // Use compatibility layer if available
            if (window.BTTCompat && window.BTTCompat.showToast) {
                window.BTTCompat.showToast(message, type);
                return;
            }
            
            // Fallback to legacy implementation
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast modern-toast toast-${type}" role="alert" aria-live="polite">
                    <span class="toast-icon">${type === 'success' ? '✓' : '⚠️'}</span>
                    <span class="toast-message">${this.escapeHtml(message)}</span>
                    <button class="toast-close" aria-label="Close notification">×</button>
                </div>
            `;
            
            const container = $('#toast-container, #modern-toast-container').first();
            if (container.length === 0) {
                $('body').append('<div id="toast-container" class="toast-container modern-toast-container" aria-live="polite"></div>');
                container = $('#toast-container');
            }
            
            container.append(toastHtml);
            
            const $toast = $('#' + toastId);
            
            // Bind close button
            $toast.find('.toast-close').on('click', function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Animate in with modern classes
            setTimeout(function() {
                $toast.addClass('toast-show modern-toast-show');
            }, 10);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        console.log('Document ready, initializing GearManager...');
        try {
            GearManager.init();
        } catch (error) {
            console.error('Error initializing GearManager:', error);
        }
    });

})(jQuery, window, document);
