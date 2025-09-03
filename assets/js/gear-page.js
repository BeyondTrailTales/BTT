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
            filters: {
                search: '',
                category: '',
                tags: []
            },
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
         * Initialize the gear manager
         */
        init: function() {
            console.log('GearManager.init() called');
            this.bindEvents();
            this.restoreUserPreferences();
            this.loadGear();
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

            // Search with debounce
            let searchTimeout;
            $('#gear-search').on('input', function() {
                clearTimeout(searchTimeout);
                const value = $(this).val();
                searchTimeout = setTimeout(function() {
                    self.state.filters.search = value;
                    self.filterAndRender();
                }, self.config.debounceDelay);
            });

            // Category filter
            $('#category-filter').on('change', function() {
                self.state.filters.category = $(this).val();
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
                } else {
                    self.state.sort = { field: 'name', direction: 'asc' };
                }
                self.filterAndRender();
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
        },

        /**
         * Load gear from API
         */
        loadGear: function() {
            const self = this;
            console.log('Loading gear from:', self.config.apiUrl + '/?route=gear');
            self.setLoading(true);

            $.ajax({
                url: self.config.apiUrl + '/?route=gear',
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-Token': self.config.csrfToken
                },
                success: function(response) {
                    console.log('API Response:', response);
                    if (response && response.success && response.data) {
                        self.state.items = response.data.items || [];
                        console.log('Loaded', self.state.items.length, 'items');
                        self.filterAndRender();
                    } else {
                        console.error('Invalid response format:', response);
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
            let filtered = [...this.state.items];

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
                filtered = filtered.filter(item => 
                    item.category === this.state.filters.category
                );
            }

            // Apply sorting
            filtered.sort((a, b) => {
                let compareValue = 0;
                const field = this.state.sort.field;
                
                if (field === 'weight_g') {
                    compareValue = (a[field] || 0) - (b[field] || 0);
                } else if (field === 'created_at') {
                    compareValue = new Date(b[field] || 0) - new Date(a[field] || 0);
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

            return `
                <div class="gear-card ${isDefault ? 'default-item' : ''}" data-item-id="${item.id}">
                    <div class="gear-card-header">
                        <span class="gear-icon" aria-hidden="true">${categoryIcon}</span>
                        <h3 class="gear-name">${this.escapeHtml(item.name)}</h3>
                        ${isDefault ? '<span class="gear-badge">Default</span>' : ''}
                    </div>
                    <div class="gear-card-body">
                        <div class="gear-meta">
                            <span class="gear-category">${this.formatCategory(item.category)}</span>
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
         * Update result count display
         */
        updateResultCount: function() {
            const count = this.state.filteredItems.length;
            const total = this.state.items.length;
            let message = '';

            if (this.state.filters.search || this.state.filters.category) {
                message = `Showing ${count} of ${total} items`;
            } else {
                message = `${count} item${count !== 1 ? 's' : ''} total`;
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

            // Determine URL and method
            let url = self.config.apiUrl + '/?route=gear';
            let method = 'POST';
            
            if (isEdit) {
                url += '/' + this.state.currentEditItem.id;
                method = 'PUT';
            }

            // Send request
            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(formData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-Token': self.config.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.showSuccess(isEdit ? 'Item updated successfully' : 'Item added successfully');
                        self.loadGear(); // Reload items
                    } else {
                        self.showError(response.message || 'Failed to save item');
                    }
                },
                error: function(xhr) {
                    console.error('Save error:', xhr);
                    const message = xhr.responseJSON?.message || 'Failed to save item';
                    self.showError(message);
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

            $.ajax({
                url: self.config.apiUrl + '/?route=gear/' + this.deleteItemId,
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': self.config.csrfToken
                },
                success: function(response) {
                    if (response.success) {
                        self.closeDeleteModal();
                        self.showSuccess('Item deleted successfully');
                        self.loadGear();
                    } else {
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
            
            $('#gear-search').val('');
            $('#category-filter').val('');
            
            this.filterAndRender();
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
                'cooking': '🍳',
                'clothing': '👕',
                'navigation': '🧭',
                'hygiene': '🧼',
                'first-aid': '🏥',
                'electronics': '📱',
                'water': '💧',
                'food-storage': '🥫',
                'repair': '🔧',
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
         * Show toast notification
         */
        showToast: function(message, type) {
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast toast-${type}" role="alert" aria-live="polite">
                    <span class="toast-icon">${type === 'success' ? '✓' : '⚠️'}</span>
                    <span class="toast-message">${this.escapeHtml(message)}</span>
                    <button class="toast-close" aria-label="Close notification">×</button>
                </div>
            `;
            
            $('#toast-container').append(toastHtml);
            
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
            
            // Animate in
            setTimeout(function() {
                $toast.addClass('toast-show');
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
