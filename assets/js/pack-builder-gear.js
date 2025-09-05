/**
 * Pack Builder Gear Integration
 * Loads gear from API and enables drag & drop to pack sections
 * @version 2.0.0
 */

(function($) {
    'use strict';

    const PackBuilderGear = {
        // State
        state: {
            gearItems: [],
            filters: {
                search: '',
                category: 'all'
            },
            loading: false,
            viewMode: 'both' // Show both default and custom gear
        },

        // API endpoint
        api: '/BTT/api/index.php?route=gear',

        // Initialize
        init: function() {
            console.log('🎒 Pack Builder Gear initializing...');
            
            // Load gear items
            this.loadGearItems();
            
            // Bind events
            this.bindEvents();
        },

        // Bind event handlers
        bindEvents: function() {
            const self = this;

            // Search input
            $('#gear-search').on('input', self.debounce(function() {
                self.state.filters.search = $(this).val();
                self.filterAndRenderItems();
            }, 300));

            // Category filter buttons
            $('.cat-filter').on('click', function() {
                $('.cat-filter').removeClass('active');
                $(this).addClass('active');
                self.state.filters.category = $(this).data('category');
                self.filterAndRenderItems();
            });

            // Quick add button
            $(document).on('click', '.btn-quick-add', function(e) {
                e.stopPropagation();
                const gearItem = $(this).closest('.gear-item');
                const item = gearItem.data('item');
                self.quickAddToMainCompartment(item);
            });

            // Make gear items draggable
            $(document).on('dragstart', '.gear-item', function(e) {
                const item = $(this).data('item');
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('gear', JSON.stringify(item));
                $(this).addClass('dragging');
            });

            $(document).on('dragend', '.gear-item', function() {
                $(this).removeClass('dragging');
            });

            // Add custom gear button
            $('#add-custom-gear').on('click', function() {
                // Use the gear library's add modal if available
                if (window.GearLibrary && window.GearLibrary.showAddGearModal) {
                    window.GearLibrary.showAddGearModal();
                } else {
                    alert('Add custom gear functionality coming soon!');
                }
            });
        },

        // Load gear items from API
        loadGearItems: async function() {
            if (this.state.loading) return;
            
            this.state.loading = true;
            this.showLoading();

            try {
                const response = await $.ajax({
                    url: this.api,
                    method: 'GET',
                    data: {
                        view: this.state.viewMode
                    }
                });

                if (response.success) {
                    this.state.gearItems = response.data.items || [];
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

        // Filter and render items
        filterAndRenderItems: function() {
            let items = this.state.gearItems;

            // Apply search filter
            if (this.state.filters.search) {
                const search = this.state.filters.search.toLowerCase();
                items = items.filter(item => {
                    const searchIn = (
                        item.name + ' ' + 
                        item.category + ' ' + 
                        (item.notes || '')
                    ).toLowerCase();
                    return searchIn.includes(search);
                });
            }

            // Apply category filter
            if (this.state.filters.category && this.state.filters.category !== 'all') {
                items = items.filter(item => item.category === this.state.filters.category);
            }

            this.renderFilteredItems(items);
        },

        // Render all items
        renderItems: function() {
            this.filterAndRenderItems();
        },

        // Render filtered items
        renderFilteredItems: function(items) {
            const container = $('#gear-items');
            container.empty();

            if (items.length === 0) {
                container.html('<div class="no-items">No gear items found</div>');
                return;
            }

            items.forEach(item => {
                const gearEl = this.createGearElement(item);
                container.append(gearEl);
            });
        },

        // Create gear element
        createGearElement: function(item) {
            const isDefault = item.is_default;
            const badge = isDefault ? 
                '<span class="item-badge default">Default</span>' : 
                '<span class="item-badge custom">Custom</span>';

            const el = $(`
                <div class="gear-item draggable ${isDefault ? 'default-item' : 'custom-item'}" 
                     draggable="true" 
                     data-gear-id="${item.id}"
                     data-category="${item.category || 'other'}">
                    <div class="gear-icon">${item.icon || this.getCategoryIcon(item.category)}</div>
                    <div class="gear-info">
                        <div class="gear-name">${item.name}</div>
                        <div class="gear-meta">
                            <span class="gear-weight">${this.formatWeight(item.weight_g)}</span>
                            <span class="gear-category gear-category-badge ${item.category || 'other'}">${this.formatCategory(item.category)}</span>
                        </div>
                    </div>
                    <button class="btn-quick-add" title="Quick Add">+</button>
                    ${badge}
                </div>
            `);

            el.data('item', item);
            return el;
        },

        // Get category icon
        getCategoryIcon: function(category) {
            const icons = {
                'shelter': '⛺',
                'sleep': '🛌',
                'cooking': '🔥',
                'clothing': '👕',
                'navigation': '🧭',
                'hygiene': '🧼',
                'first-aid': '🩹',
                'electronics': '📱',
                'water': '💧',
                'food-storage': '🍱',
                'repair': '🔧',
                'other': '📦'
            };
            return icons[category] || '📦';
        },

        // Quick add to main compartment
        quickAddToMainCompartment: function(item) {
            // Find the main compartment dropzone
            const mainDropzone = $('.dropzone[data-section="main"]');
            if (mainDropzone.length) {
                // Create a pack item element
                const packItem = this.createPackItem(item);
                
                // Remove placeholder if exists
                mainDropzone.find('.dropzone-placeholder').remove();
                
                // Add to dropzone
                mainDropzone.append(packItem);
                
                // Update weight calculations
                if (window.PackBuilderCRUD && window.PackBuilderCRUD.updateWeightSummary) {
                    window.PackBuilderCRUD.state.isDirty = true;
                    window.PackBuilderCRUD.updateWeightSummary();
                } else if (window.PackBuilder && window.PackBuilder.updateWeights) {
                    window.PackBuilder.updateWeights();
                }
                
                // Show success feedback
                this.showSuccess(`Added ${item.name} to pack`);
            }
        },

        // Create pack item element
        createPackItem: function(item) {
            const packItem = $(`
                <div class="pack-item" data-item-id="${item.id}" data-weight="${item.weight_g}">
                    <span class="item-handle">≡</span>
                    <span class="item-icon">${item.icon || this.getCategoryIcon(item.category)}</span>
                    <span class="item-name">${item.name}</span>
                    <input type="number" class="item-qty" value="1" min="1" max="99">
                    <span class="item-weight">${this.formatWeight(item.weight_g)}</span>
                    <button class="btn-remove-item" title="Remove">×</button>
                </div>
            `);

            // Store item data
            packItem.data('item', item);

            // Bind remove button
            packItem.find('.btn-remove-item').on('click', function() {
                packItem.fadeOut(200, function() {
                    packItem.remove();
                    // Check if section is empty
                    const section = packItem.closest('.dropzone');
                    if (section.find('.pack-item').length === 0) {
                        section.html('<div class="dropzone-placeholder">Drop gear here</div>');
                    }
                    // Update weights
                    if (window.PackBuilderCRUD && window.PackBuilderCRUD.updateWeightSummary) {
                        window.PackBuilderCRUD.state.isDirty = true;
                        window.PackBuilderCRUD.updateWeightSummary();
                    } else if (window.PackBuilder && window.PackBuilder.updateWeights) {
                        window.PackBuilder.updateWeights();
                    }
                });
            });

            // Bind quantity change
            packItem.find('.item-qty').on('change', function() {
                const qty = parseInt($(this).val()) || 1;
                const totalWeight = item.weight_g * qty;
                packItem.find('.item-weight').text(PackBuilderGear.formatWeight(totalWeight));
                // Update weights
                if (window.PackBuilderCRUD && window.PackBuilderCRUD.updateWeightSummary) {
                    window.PackBuilderCRUD.state.isDirty = true;
                    window.PackBuilderCRUD.updateWeightSummary();
                } else if (window.PackBuilder && window.PackBuilder.updateWeights) {
                    window.PackBuilder.updateWeights();
                }
            });

            return packItem;
        },

        // Format weight
        formatWeight: function(grams) {
            if (grams >= 1000) {
                return `${(grams / 1000).toFixed(2)}kg`;
            }
            return `${grams}g`;
        },

        // Format category
        formatCategory: function(category) {
            const formatted = category.replace(/-/g, ' ');
            return formatted.charAt(0).toUpperCase() + formatted.slice(1);
        },

        // Show loading
        showLoading: function() {
            const container = $('#gear-items');
            if (container.find('.loading-spinner').length === 0) {
                container.html(`
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Loading gear...</p>
                    </div>
                `);
            }
        },

        // Hide loading
        hideLoading: function() {
            $('#gear-items .loading-spinner').remove();
        },

        // Show success message
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

    // Initialize when pack builder view is shown
    $(document).on('click', '[data-view="builder"], .pack-tab[data-view="builder"]', function() {
        // Small delay to ensure view is visible
        setTimeout(() => {
            if (!PackBuilderGear.initialized) {
                console.log('Initializing Pack Builder Gear');
                PackBuilderGear.init();
                PackBuilderGear.initialized = true;
            } else {
                console.log('Refreshing gear items');
                PackBuilderGear.loadGearItems();
            }
        }, 100);
    });

    // Check if builder view is active on load
    $(document).ready(function() {
        if ($('#view-builder').hasClass('active') || $('.pack-tab[data-view="builder"]').hasClass('active')) {
            console.log('Pack Builder is active on load');
            setTimeout(() => {
                PackBuilderGear.init();
                PackBuilderGear.initialized = true;
            }, 500);
        }
    });

    // Expose to global scope
    window.PackBuilderGear = PackBuilderGear;

})(jQuery);
