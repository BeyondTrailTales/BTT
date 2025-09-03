/**
 * Gear Search Module for BeyondTrailTales
 * Handles gear library search and filtering
 */

(function() {
    'use strict';
    
    // Check if jQuery is available
    if (typeof jQuery === 'undefined') {
        console.warn('Gear search: jQuery not loaded, deferring initialization');
        return;
    }
    
    const GearSearch = {
        searchInput: null,
        filterSelects: {},
        gearItems: [],
        filteredItems: [],
        
        init: function() {
            this.searchInput = jQuery('#global-search, #gear-search');
            this.filterSelects = {
                category: jQuery('#filter-category'),
                weight: jQuery('#filter-weight'),
                brand: jQuery('#filter-brand')
            };
            
            this.bindEvents();
            this.loadGearItems();
            
            console.log('✅ Gear search initialized');
        },
        
        bindEvents: function() {
            const self = this;
            
            // Search input
            this.searchInput.on('input', jQuery.debounce ? jQuery.debounce(300, function() {
                self.performSearch();
            }) : function() {
                self.performSearch();
            });
            
            // Filter selects
            Object.values(this.filterSelects).forEach(select => {
                select.on('change', function() {
                    self.applyFilters();
                });
            });
            
            // Clear filters button
            jQuery('.clear-filters-btn').on('click', function() {
                self.clearFilters();
            });
        },
        
        loadGearItems: function() {
            // In a real app, this would load from API
            // For now, parse from DOM elements
            const self = this;
            this.gearItems = [];
            
            jQuery('.gear-item, .pack-item').each(function() {
                const item = jQuery(this);
                self.gearItems.push({
                    element: item,
                    name: item.find('.gear-name, .item-name').text().toLowerCase(),
                    category: item.data('category') || '',
                    weight: parseFloat(item.data('weight')) || 0,
                    brand: item.data('brand') || '',
                    visible: true
                });
            });
            
            this.filteredItems = [...this.gearItems];
        },
        
        performSearch: function() {
            const searchTerm = this.searchInput.val().toLowerCase();
            
            if (!searchTerm) {
                this.showAllItems();
                return;
            }
            
            this.gearItems.forEach(item => {
                const matches = item.name.includes(searchTerm) ||
                               item.category.toLowerCase().includes(searchTerm) ||
                               item.brand.toLowerCase().includes(searchTerm);
                
                item.visible = matches;
                if (matches) {
                    item.element.show();
                } else {
                    item.element.hide();
                }
            });
            
            this.updateResultsCount();
        },
        
        applyFilters: function() {
            const filters = {
                category: this.filterSelects.category.val(),
                weight: this.filterSelects.weight.val(),
                brand: this.filterSelects.brand.val()
            };
            
            this.gearItems.forEach(item => {
                let matches = true;
                
                if (filters.category && filters.category !== 'all') {
                    matches = matches && item.category === filters.category;
                }
                
                if (filters.weight && filters.weight !== 'all') {
                    const range = filters.weight.split('-');
                    if (range.length === 2) {
                        const min = parseFloat(range[0]);
                        const max = parseFloat(range[1]);
                        matches = matches && item.weight >= min && item.weight <= max;
                    }
                }
                
                if (filters.brand && filters.brand !== 'all') {
                    matches = matches && item.brand === filters.brand;
                }
                
                item.visible = matches;
                if (matches) {
                    item.element.show();
                } else {
                    item.element.hide();
                }
            });
            
            this.updateResultsCount();
            this.showActiveFilters(filters);
        },
        
        clearFilters: function() {
            this.searchInput.val('');
            Object.values(this.filterSelects).forEach(select => {
                select.val('all');
            });
            
            this.showAllItems();
            jQuery('.active-filters').empty();
        },
        
        showAllItems: function() {
            this.gearItems.forEach(item => {
                item.visible = true;
                item.element.show();
            });
            this.updateResultsCount();
        },
        
        updateResultsCount: function() {
            const visibleCount = this.gearItems.filter(item => item.visible).length;
            const totalCount = this.gearItems.length;
            
            jQuery('.results-count').html(
                `Showing <strong>${visibleCount}</strong> of ${totalCount} items`
            );
            
            // Show no results message if needed
            if (visibleCount === 0 && jQuery('.no-results').length === 0) {
                const noResults = jQuery(`
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <div class="no-results-title">No items found</div>
                        <div class="no-results-text">Try adjusting your search or filters</div>
                    </div>
                `);
                jQuery('.gear-grid, .packs-grid').append(noResults);
            } else if (visibleCount > 0) {
                jQuery('.no-results').remove();
            }
        },
        
        showActiveFilters: function(filters) {
            const container = jQuery('.active-filters');
            container.empty();
            
            Object.entries(filters).forEach(([key, value]) => {
                if (value && value !== 'all') {
                    const tag = jQuery(`
                        <span class="filter-tag">
                            ${key}: ${value}
                            <span class="filter-tag-remove" data-filter="${key}">×</span>
                        </span>
                    `);
                    
                    tag.find('.filter-tag-remove').on('click', () => {
                        this.filterSelects[key].val('all');
                        this.applyFilters();
                    });
                    
                    container.append(tag);
                }
            });
        }
    };
    
    // Initialize when DOM is ready
    jQuery(document).ready(function() {
        GearSearch.init();
    });
    
    // Expose to global scope if needed
    window.GearSearch = GearSearch;
})();
