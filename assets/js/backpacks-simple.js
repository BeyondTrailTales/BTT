/**
 * Simplified Backpacks Loader
 * Quick fix to get backpacks working without complex dependencies
 */

(function($) {
    'use strict';
    
    // Only run on backpacks page
    if (!window.location.pathname.includes('backpack')) return;
    
    console.log('Simple Backpacks Loader: Starting');
    
    // Wait for jQuery
    $(document).ready(function() {
        console.log('Simple Backpacks Loader: jQuery ready');
        
        // Initialize immediately
        loadBackpacks();
        
        // Bind events
        $('#btn-new-pack').on('click', createNewBackpack);
        $('#global-search').on('input', filterBackpacks);
        $('#sort-packs').on('change', sortBackpacks);
        
        // Handle tab switching
        $('.pack-tab').on('click', function() {
            const view = $(this).data('view');
            showView(view);
        });
    });
    
    function loadBackpacks() {
        const $grid = $('#packs-grid');
        
        console.log('Loading backpacks...');
        $grid.html('<div class="loading-spinner"><div class="spinner"></div><p>Loading backpacks...</p></div>');
        
        // Use jQuery AJAX directly
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'GET',
            data: { route: 'backpacks' },
            dataType: 'json',
            success: function(response) {
                console.log('Backpacks loaded:', response);
                
                // Handle wrapped response
                const backpacks = response.data || response;
                const backpacksArray = Array.isArray(backpacks) ? backpacks : [];
                
                if (backpacksArray.length === 0) {
                    $grid.html('<div class="packs-empty-state"><div class="packs-empty-icon">🎒</div><div class="packs-empty-text">No backpacks yet</div><div class="packs-empty-subtext">Click the New Pack button to create your first backpack.</div></div>');
                } else {
                    renderBackpacks(backpacksArray);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load backpacks:', error);
                $grid.html('<div class="alert alert-error">Failed to load backpacks. Please refresh the page.</div>');
            }
        });
    }
    
    function renderBackpacks(backpacks) {
        const $grid = $('#packs-grid');
        
        const html = backpacks.map(function(pack) {
            const photoUrl = pack.image_url || 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=300&fit=crop';
            const weight = pack.total_weight_g ? (pack.total_weight_g / 1000).toFixed(1) + 'kg' : '0kg';
            const items = pack.total_items || 0;
            
            return `
                <article class="pack-card" data-id="${pack.id}">
                    <div class="pack-card-header">
                        <h3>${escapeHtml(pack.name || 'Unnamed Pack')}</h3>
                        <div class="pack-card-actions">
                            <button type="button" data-action="edit" title="Edit">✏️</button>
                            <button type="button" data-action="duplicate" title="Duplicate">📋</button>
                            <button type="button" data-action="delete" title="Delete">🗑️</button>
                        </div>
                    </div>
                    
                    <div class="pack-card-image">
                        <img src="${photoUrl}" alt="${escapeHtml(pack.image_alt || 'Backpack photo')}" />
                        <span class="pack-weight-badge">${weight}</span>
                    </div>
                    
                    <div class="pack-card-content">
                        ${pack.description ? `<p class="pack-description">${escapeHtml(pack.description)}</p>` : ''}
                        
                        <div class="pack-stats">
                            <div class="pack-stat">
                                <span class="pack-stat-value">${items}</span>
                                <span class="pack-stat-label">Items</span>
                            </div>
                            <div class="pack-stat">
                                <span class="pack-stat-value">${weight}</span>
                                <span class="pack-stat-label">Total Weight</span>
                            </div>
                            <div class="pack-stat">
                                <span class="pack-stat-value">${pack.capacity_l || 65}L</span>
                                <span class="pack-stat-label">Capacity</span>
                            </div>
                        </div>
                        
                        <div class="pack-card-footer">
                            <span class="pack-type-badge">${pack.type || 'custom'}</span>
                            ${pack.trip_count > 0 ? `<span class="pack-trips-badge">${pack.trip_count} trips</span>` : ''}
                        </div>
                    </div>
                </article>
            `;
        }).join('');
        
        $grid.html(html);
        
        // Bind card events
        $('.pack-card button[data-action]').on('click', handlePackAction);
        $('.pack-card').on('click', function(e) {
            if (!$(e.target).is('button')) {
                const id = $(this).data('id');
                openPackBuilder(id);
            }
        });
    }
    
    function handlePackAction(e) {
        e.stopPropagation();
        const $button = $(this);
        const $card = $button.closest('.pack-card');
        const id = $card.data('id');
        const action = $button.data('action');
        
        switch(action) {
            case 'edit':
                openPackBuilder(id);
                break;
            case 'duplicate':
                duplicateBackpack(id);
                break;
            case 'delete':
                if (confirm('Delete this backpack?')) {
                    deleteBackpack(id);
                }
                break;
        }
    }
    
    function createNewBackpack() {
        showView('builder');
        $('#pack-name').val('');
        $('#pack-description').val('');
        $('#pack-capacity').val('65');
        $('#pack-base-weight').val('0');
        $('.dropzone').html('<div class="dropzone-placeholder">Drop gear here</div>');
    }
    
    function openPackBuilder(id) {
        showView('builder');
        
        // Load backpack data
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'GET',
            data: { route: 'backpacks', id: id },
            dataType: 'json',
            success: function(response) {
                const pack = response.data || response;
                populatePackForm(Array.isArray(pack) ? pack[0] : pack);
            }
        });
    }
    
    function duplicateBackpack(id) {
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'POST',
            data: JSON.stringify({}),
            contentType: 'application/json',
            dataType: 'json',
            headers: { 'X-Action': 'duplicate', 'X-ID': id },
            success: function() {
                showToast('Backpack duplicated', 'success');
                loadBackpacks();
            },
            error: function() {
                showToast('Failed to duplicate backpack', 'error');
            }
        });
    }
    
    function deleteBackpack(id) {
        $.ajax({
            url: '/BTT/api/index.php?route=backpacks&id=' + id,
            method: 'DELETE',
            dataType: 'json',
            success: function() {
                showToast('Backpack deleted', 'success');
                loadBackpacks();
            },
            error: function() {
                showToast('Failed to delete backpack', 'error');
            }
        });
    }
    
    function populatePackForm(pack) {
        $('#pack-name').val(pack.name || '');
        $('#pack-description').val(pack.description || '');
        $('#pack-capacity').val(pack.capacity_l || 65);
        $('#pack-base-weight').val(pack.weight_empty_g || 0);
        
        // Load sections and items if available
        if (pack.sections && pack.sections.length > 0) {
            pack.sections.forEach(function(section) {
                const $section = $(`.dropzone[data-section="${section.id}"]`);
                if ($section.length && section.items && section.items.length > 0) {
                    $section.html('');
                    section.items.forEach(function(item) {
                        $section.append(`
                            <div class="pack-item" data-item-id="${item.id}">
                                <span class="item-handle">≡</span>
                                <span class="item-icon">${item.icon || '📦'}</span>
                                <span class="item-name">${escapeHtml(item.name)}</span>
                                <input type="number" class="item-qty" value="${item.quantity}" min="1" max="99">
                                <span class="item-weight">${item.weight}g</span>
                                <button class="btn-remove-item" title="Remove">×</button>
                            </div>
                        `);
                    });
                }
            });
        }
    }
    
    function filterBackpacks() {
        const query = $('#global-search').val().toLowerCase();
        $('.pack-card').each(function() {
            const $card = $(this);
            const name = $card.find('h3').text().toLowerCase();
            const description = $card.find('.pack-description').text().toLowerCase();
            const matches = name.includes(query) || description.includes(query);
            $card.toggle(matches);
        });
    }
    
    function sortBackpacks() {
        // Implement sorting logic
        loadBackpacks(); // For now, just reload
    }
    
    function showView(view) {
        // Hide all views
        $('.pack-view').removeClass('active');
        $('.pack-tab').removeClass('active');
        
        // Show selected view
        $(`#view-${view}`).addClass('active');
        $(`.pack-tab[data-view="${view}"]`).addClass('active');
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function showToast(message, type) {
        // Simple toast notification
        const $toast = $(`<div class="toast toast-${type}">${message}</div>`);
        $('body').append($toast);
        $toast.fadeIn();
        setTimeout(function() {
            $toast.fadeOut(function() {
                $toast.remove();
            });
        }, 3000);
    }
    
    // Handle save button
    $('#btn-save-pack').on('click', function() {
        const packData = {
            name: $('#pack-name').val() || 'New Pack',
            description: $('#pack-description').val(),
            capacity_l: parseInt($('#pack-capacity').val()) || 65,
            weight_empty_g: parseInt($('#pack-base-weight').val()) || 0,
            sections: []
        };
        
        // Collect items from sections
        $('.pack-section').each(function() {
            const $section = $(this);
            const sectionId = $section.data('section-id');
            const items = [];
            
            $section.find('.pack-item').each(function() {
                const $item = $(this);
                items.push({
                    id: $item.data('item-id'),
                    name: $item.find('.item-name').text(),
                    quantity: parseInt($item.find('.item-qty').val()) || 1,
                    weight: parseInt($item.find('.item-weight').text()) || 0
                });
            });
            
            if (items.length > 0) {
                packData.sections.push({
                    id: sectionId,
                    name: $section.find('.section-name').val(),
                    items: items
                });
            }
        });
        
        // If sections is still a plain array, convert to JSON string for the API
        packData.sections = JSON.stringify(packData.sections);
        
        $.ajax({
            url: '/BTT/api/index.php?route=backpacks',
            method: 'POST',
            data: JSON.stringify(packData),
            contentType: 'application/json',
            dataType: 'json',
            success: function() {
                showToast('Backpack saved successfully', 'success');
                showView('my-packs');
                loadBackpacks();
            },
            error: function() {
                showToast('Failed to save backpack', 'error');
            }
        });
    });
    
    // Handle cancel button
    $('#btn-cancel-edit').on('click', function() {
        showView('my-packs');
    });
    
})(jQuery);
