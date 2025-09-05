(function($) {
    'use strict';

    class MyPacksManager {
        constructor() {
            this.packs = [];
            this.filteredPacks = [];
            this.currentFilter = 'all';
            this.init();
        }

        init() {
            this.loadUserPacks();
            this.bindEvents();
        }

        bindEvents() {
            // Search functionality
            $('#search-packs').on('input', (e) => {
                this.searchPacks(e.target.value);
            });

            // Filter buttons
            $('.filter-btn').on('click', (e) => {
                $('.filter-btn').removeClass('active');
                $(e.target).addClass('active');
                this.filterPacks($(e.target).data('filter'));
            });

            // Pack card actions
            $(document).on('click', '.open-pack-btn', (e) => {
                e.preventDefault();
                const packId = $(e.target).data('pack-id');
                window.location.href = `backpacks.php?id=${packId}`;
            });

            $(document).on('click', '.btn-edit-pack', (e) => {
                e.stopPropagation();
                const packId = $(e.target).closest('.pack-action-btn').data('pack-id');
                window.location.href = `backpacks.php?id=${packId}`;
            });

            $(document).on('click', '.btn-duplicate-pack', (e) => {
                e.stopPropagation();
                const packId = $(e.target).closest('.pack-action-btn').data('pack-id');
                this.duplicatePack(packId);
            });

            $(document).on('click', '.btn-delete-pack', (e) => {
                e.stopPropagation();
                const packId = $(e.target).closest('.pack-action-btn').data('pack-id');
                const packName = $(e.target).closest('.pack-card').find('.pack-name').text();
                if (confirm(`Are you sure you want to delete "${packName}"?`)) {
                    this.deletePack(packId);
                }
            });
        }

        loadUserPacks() {
            $.ajax({
                url: BTT.apiUrl + '/?route=backpacks&include=items',
                method: 'GET',
                headers: {
                    'X-CSRF-Token': BTT.csrfToken
                },
                success: (response) => {
                    console.log('Loaded packs:', response);
                    if (response.success && response.data) {
                        this.packs = response.data;
                        this.filteredPacks = this.packs;
                        this.renderPacks();
                    } else if (response.success === false && response.data) {
                        // Handle case where success is false but data exists
                        this.packs = response.data;
                        this.filteredPacks = this.packs;
                        this.renderPacks();
                    } else {
                        this.showEmptyState();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Failed to load packs:', error);
                    this.showError('Failed to load your packs. Please try again.');
                }
            });
        }

        renderPacks() {
            const container = $('#user-packs-list');
            container.empty();

            if (this.filteredPacks.length === 0) {
                this.showEmptyState();
                return;
            }

            $('#empty-state').hide();

            this.filteredPacks.forEach(pack => {
                const packCard = this.createPackCard(pack);
                container.append(packCard);
            });
        }

        createPackCard(pack) {
            // Use database totals or calculate from items
            let totalItems = pack.total_items || 0;
            let totalWeight = pack.total_weight_g || 0;

            // If we have items array, we can show a preview
            let itemsPreview = '';
            if (pack.items && Array.isArray(pack.items) && pack.items.length > 0) {
                const previewItems = pack.items.slice(0, 3); // Show first 3 items
                itemsPreview = `
                    <div class="pack-items-preview">
                        <h4 class="items-label">Items:</h4>
                        <ul class="items-list">
                            ${previewItems.map(item => `
                                <li>
                                    <span class="item-name">${item.name || 'Unnamed Item'}</span>
                                    <span class="item-details">
                                        ${item.quantity > 1 ? `x${item.quantity}` : ''}
                                        ${item.weight_g ? `(${item.weight_g}g)` : ''}
                                    </span>
                                </li>
                            `).join('')}
                            ${pack.items.length > 3 ? `<li class="more-items">... and ${pack.items.length - 3} more</li>` : ''}
                        </ul>
                    </div>
                `;
            }

            // Format weight display - show grams and pounds
            const weightInKg = (totalWeight / 1000).toFixed(2);
            const weightInLb = (totalWeight / 453.592).toFixed(2);

            return $(`
                <div class="pack-card" data-pack-id="${pack.id}">
                    <div class="pack-header">
                        <h3 class="pack-name">${pack.name || 'Untitled Pack'}</h3>
                        <div class="pack-actions">
                            <button class="pack-action-btn btn-edit-pack" data-pack-id="${pack.id}" title="Edit">
                                ✏️
                            </button>
                            <button class="pack-action-btn btn-duplicate-pack" data-pack-id="${pack.id}" title="Duplicate">
                                📋
                            </button>
                            <button class="pack-action-btn btn-delete-pack" data-pack-id="${pack.id}" title="Delete">
                                🗑️
                            </button>
                        </div>
                    </div>
                    <p class="pack-description">${pack.description || 'No description'}</p>
                    <div class="pack-stats">
                        <div class="pack-stat">
                            <span class="stat-label">Weight</span>
                            <span class="stat-value" title="${weightInLb} lbs">${weightInKg}kg</span>
                        </div>
                        <div class="pack-stat">
                            <span class="stat-label">Items</span>
                            <span class="stat-value">${totalItems}</span>
                        </div>
                    </div>
                    ${itemsPreview}
                    <button class="open-pack-btn" data-pack-id="${pack.id}">
                        Open Pack
                    </button>
                </div>
            `);
        }

        searchPacks(query) {
            if (!query) {
                this.filteredPacks = this.applyFilter(this.packs);
            } else {
                const lowerQuery = query.toLowerCase();
                this.filteredPacks = this.applyFilter(this.packs).filter(pack => 
                    pack.name.toLowerCase().includes(lowerQuery) ||
                    (pack.description && pack.description.toLowerCase().includes(lowerQuery))
                );
            }
            this.renderPacks();
        }

        filterPacks(filter) {
            this.currentFilter = filter;
            this.filteredPacks = this.applyFilter(this.packs);
            const searchQuery = $('#search-packs').val();
            if (searchQuery) {
                this.searchPacks(searchQuery);
            } else {
                this.renderPacks();
            }
        }

        applyFilter(packs) {
            switch(this.currentFilter) {
                case 'recent':
                    // Sort by created_at or updated_at, most recent first
                    return [...packs].sort((a, b) => {
                        const dateA = new Date(a.updated_at || a.created_at);
                        const dateB = new Date(b.updated_at || b.created_at);
                        return dateB - dateA;
                    }).slice(0, 6);
                
                case 'lightweight':
                    // Filter packs under 10kg base weight
                    return packs.filter(pack => {
                        const weight = pack.base_weight_g || pack.total_weight_g || 0;
                        return weight < 10000;
                    });
                
                case 'heavy':
                    // Filter packs over 10kg base weight
                    return packs.filter(pack => {
                        const weight = pack.base_weight_g || pack.total_weight_g || 0;
                        return weight >= 10000;
                    });
                
                default:
                    return packs;
            }
        }

        duplicatePack(packId) {
            const pack = this.packs.find(p => p.id == packId);
            if (!pack) return;

            const duplicateData = {
                ...pack,
                name: pack.name + ' (Copy)',
                id: undefined // Let the backend generate a new ID
            };

            $.ajax({
                url: BTT.apiUrl + '/?route=backpacks',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': BTT.csrfToken
                },
                data: JSON.stringify(duplicateData),
                success: (response) => {
                    if (response.success) {
                        this.showToast('Pack duplicated successfully!', 'success');
                        this.loadUserPacks();
                    } else {
                        this.showToast('Failed to duplicate pack', 'error');
                    }
                },
                error: () => {
                    this.showToast('Failed to duplicate pack', 'error');
                }
            });
        }

        deletePack(packId) {
            $.ajax({
                url: BTT.apiUrl + '/?route=backpacks&id=' + packId,
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': BTT.csrfToken
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('Pack deleted successfully', 'success');
                        this.loadUserPacks();
                    } else {
                        this.showToast('Failed to delete pack', 'error');
                    }
                },
                error: () => {
                    this.showToast('Failed to delete pack', 'error');
                }
            });
        }

        showEmptyState() {
            $('#user-packs-list').empty();
            $('#empty-state').show();
        }

        showError(message) {
            $('#user-packs-list').html(`
                <div class="error-message">
                    <p>${message}</p>
                </div>
            `);
        }

        showToast(message, type = 'info') {
            const toast = $(`
                <div class="toast ${type}">
                    <span>${message}</span>
                </div>
            `);
            
            $('#toast-container').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        new MyPacksManager();
    });

})(jQuery);
