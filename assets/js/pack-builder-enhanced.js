/**
 * Enhanced Pack Builder JS
 * Adds delete functionality and improved UI interactions
 */

(function($) {
    'use strict';

    // Extend the existing PackBuilder object
    if (typeof window.PackBuilder === 'undefined') {
        window.PackBuilder = {};
    }

    const PackBuilderEnhancements = {
        
        // -------------------- Shared constants --------------------
        GEAR_API: '/BTT/api/index.php?route=gear',
        
        categoryIcons: {
            shelter: '⛺',
            sleep: '🛌',
            cooking: '🍲',
            clothing: '🧥',
            navigation: '🧭',
            hygiene: '🧼',
            'first-aid': '🏥',
            first_aid: '🏥',
            electronics: '🔋',
            water: '💧',
            food: '🍎',
            tools: '🛠️',
            other: '📦'
        },
        
        // -------------------- Gear API integration --------------------
        async loadGearLibrary() {
            try {
                const resp = await $.ajax({
                    url: this.GEAR_API, // server applies user preference for mode
                    method: 'GET'
                });
                const items = (resp && resp.data && resp.data.items) ? resp.data.items : [];
                // Preserve original API items (for management table: brand, price, source)
                this.state.gearApiItems = items;
                this.state.gearStats = (resp && resp.data && resp.data.stats) ? resp.data.stats : {};
                
                // Map to builder-friendly structure
                this.state.gearLibrary = items.map(i => ({
                    id: i.id,
                    name: i.name,
                    weight: i.weight_g || 0,
                    category: i.category || 'other',
                    icon: i.icon || this.categoryIcons[i.category] || '📦',
                    brand: i.brand,
                    price: i.price
                }));
                
                // Render current view pieces
                if (this.state.currentView === 'gear-library') {
                    this.renderGearManagement();
                }
                // Always refresh the small builder panel if visible
                if ($('#gear-items').length) {
                    this.renderGearLibrary();
                }
            } catch (e) {
                console.error('Failed to load gear from API', e);
                // Fallback to original behaviour
                if (typeof this.originalLoadGearLibrary === 'function') {
                    return this.originalLoadGearLibrary();
                }
            }
        },
        
        async setGearViewMode(mode) {
            try {
                await $.ajax({
                    url: this.GEAR_API + '/preferences',
                    method: 'PATCH',
                    contentType: 'application/json',
                    data: JSON.stringify({ view_mode: mode })
                });
                // Reload with new mode
                await this.loadGearLibrary();
                this.showSuccess('Gear view updated');
            } catch (e) {
                console.error('Failed to save gear preference', e);
                this.showError('Could not save gear view');
            }
        },
        
        // Override builder-side gear list rendering
        renderGearLibrary: function() {
            const self = this; // Store reference to 'this' for use in map function
            const filtered = (this.state.gearLibrary || []).filter(gear => {
                const matchesCategory = this.state.filters.category === 'all' || 
                                       gear.category === this.state.filters.category;
                const matchesSearch = !this.state.filters.search || 
                                     gear.name.toLowerCase().includes(this.state.filters.search);
                return matchesCategory && matchesSearch;
            });
            const html = filtered.map(gear => `
                <div class="gear-item draggable" draggable="true" data-gear-id="${gear.id}">
                    <div class="gear-icon">${gear.icon || '📦'}</div>
                    <div class="gear-info">
                        <div class="gear-name">${self.escapeHtml(gear.name || '')}</div>
                        <div class="gear-meta">
                            <span class="gear-weight">${gear.weight || 0}g</span>
                            <span class="gear-category">${self.escapeHtml(gear.category || '')}</span>
                        </div>
                    </div>
                    <button class="btn-quick-add" title="Quick Add">+</button>
                </div>
            `).join('');
            $('#gear-items').html(html || '<p style="padding: 1rem; text-align: center;">No gear found</p>');
        },
        
        // Override quickAddGear to work with new IDs
        quickAddGear: function(gearId) {
            const gear = this.state.gearLibrary.find(g => g.id === gearId);
            if (!gear) return;
            
            // Add to first section by default
            const firstSection = $('.dropzone').first().data('section');
            if (firstSection) {
                this.addGearToSection(gear, firstSection);
            }
        },
        
        // Override the drag-start handler to work with new gear structure
        initDragDropOverrides: function() {
            const self = this;
            
            // Override the dragstart handler for gear library items
            $(document).off('dragstart', '.gear-item').on('dragstart', '.gear-item', function(e) {
                const gearId = $(this).data('gear-id');
                const gear = self.state.gearLibrary.find(g => g.id === gearId);
                if (gear) {
                    e.originalEvent.dataTransfer.effectAllowed = 'copy';
                    e.originalEvent.dataTransfer.setData('gear', JSON.stringify(gear));
                    e.originalEvent.dataTransfer.setData('action', 'add');
                    $(this).addClass('dragging');
                }
            });
            
            // Add dragstart handler for pack items to enable moving between sections
            $(document).off('dragstart', '.pack-item').on('dragstart', '.pack-item', function(e) {
                const $item = $(this);
                const itemData = $item.data('item');
                if (itemData) {
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                    e.originalEvent.dataTransfer.setData('gear', JSON.stringify(itemData));
                    e.originalEvent.dataTransfer.setData('action', 'move');
                    e.originalEvent.dataTransfer.setData('sourceElement', $item.attr('data-item-id'));
                    $item.addClass('dragging');
                    
                    // Store reference to the element being dragged
                    self.draggedPackItem = $item;
                }
            });
            
            // Handle dragend for pack items
            $(document).off('dragend', '.pack-item').on('dragend', '.pack-item', function() {
                $(this).removeClass('dragging');
                self.draggedPackItem = null;
            });
            
            // Override the drop handler to handle both add and move actions
            $(document).off('drop', '.dropzone').on('drop', '.dropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                const gearData = e.originalEvent.dataTransfer.getData('gear');
                const action = e.originalEvent.dataTransfer.getData('action');
                
                if (!gearData) return;
                
                const gear = JSON.parse(gearData);
                const targetSection = $(this);
                const sectionId = targetSection.data('section');
                
                if (action === 'move' && self.draggedPackItem) {
                    // Moving an existing item between sections
                    const $oldSection = self.draggedPackItem.parent();
                    
                    // Only proceed if actually moving to a different section
                    if ($oldSection[0] !== targetSection[0]) {
                        // Remove placeholder if needed in target section
                        if (targetSection.find('.pack-item').length === 0) {
                            targetSection.find('.dropzone-placeholder').remove();
                        }
                        
                        // Move the item to the new section
                        self.draggedPackItem.appendTo(targetSection);
                        
                        // Add placeholder back to old section if it's now empty
                        if ($oldSection.find('.pack-item').length === 0 && !$oldSection.find('.dropzone-placeholder').length) {
                            $oldSection.html('<div class="dropzone-placeholder">Drop gear here</div>');
                        }
                        
                        // Update weights and mark as dirty
                        self.updateWeights();
                        self.state.isDirty = true;
                    }
                    
                } else {
                    // Adding a new item from the gear library
                    self.addGearToSection(gear, sectionId);
                }
            });
        },
        
        // Management table
        renderGearManagement: function() {
            const self = this; // Store reference to 'this' for use in map function
            const items = this.state.gearApiItems || [];
            const tbodyHtml = items.map(item => {
                const isEditable = !!item.editable; // user gear only
                const price = item.price != null ? `$${item.price}` : '-';
                const weight = item.weight_g != null ? `${item.weight_g}g` : '-';
                const brand = item.brand || '-';
                const sourceBadge = item.source === 'user' ? '<span class="badge badge-user">Mine</span>' : '<span class="badge badge-default">Default</span>';
                const actions = isEditable
                    ? `<button class="btn-edit-gear" data-gear-id="${item.id}">Edit</button>
                       <button class="btn-delete-gear" data-gear-id="${item.id}">Delete</button>
                       <button class="btn-add-to-pack" data-gear-id="${item.id}">Add</button>`
                    : `<button class="btn-view-gear" data-gear-id="${item.id}">View</button>
                       <button class="btn-add-to-pack" data-gear-id="${item.id}">Add</button>`;
                return `
                    <tr>
                        <td>${self.escapeHtml(item.name)} ${sourceBadge}</td>
                        <td>${self.escapeHtml(item.category || 'other')}</td>
                        <td>${weight}</td>
                        <td>${self.escapeHtml(brand)}</td>
                        <td>${price}</td>
                        <td class="gear-actions">${actions}</td>
                    </tr>
                `;
            }).join('');
            $('#gear-table-body').html(tbodyHtml);
            
            // Inject view-mode controls if not present
            if (!$('.gear-view-modes').length) {
                const controls = `
                    <div class="gear-view-modes" role="group" aria-label="Gear view mode">
                        <button class="gear-view-toggle" data-mode="all">All</button>
                        <button class="gear-view-toggle" data-mode="custom_only">Mine</button>
                        <button class="gear-view-toggle" data-mode="default_only">Default</button>
                    </div>`;
                $(controls).insertAfter('.library-actions');
            }
            
            // Mark active mode
            const mode = (this.state.gearStats && this.state.gearStats.view_mode) || 'all';
            $('.gear-view-toggle').removeClass('active');
            $(`.gear-view-toggle[data-mode="${mode}"]`).addClass('active');
        },
        
        // Actions
        bindGearEventsOnceBound: false,
        ensureGearEventBindings: function() {
            if (this.bindGearEventsOnceBound) return;
            const self = this;
            // View mode toggles
            $(document).on('click', '.gear-view-toggle', function() {
                const mode = $(this).data('mode');
                self.setGearViewMode(mode);
            });
            // Add new gear
            $(document).on('click', '.btn-add-gear', function() {
                self.state.editingGearId = null;
                $('#custom-name').val('');
                $('#custom-weight').val('');
                $('#custom-category').val('other');
                $('#custom-notes').val('');
                self.showCustomGearPanel();
            });
            // Edit gear
            $(document).on('click', '.btn-edit-gear', async function() {
                const id = $(this).data('gear-id');
                try {
                    const resp = await $.ajax({ url: self.GEAR_API + '&id=' + id, method: 'GET' });
                    const g = resp && resp.data ? resp.data : null;
                    if (!g) return;
                    self.state.editingGearId = g.id; // user_*
                    $('#custom-name').val(g.name || '');
                    $('#custom-weight').val(g.weight_g || '');
                    $('#custom-category').val(g.category || 'other');
                    $('#custom-notes').val(g.description || '');
                    self.showCustomGearPanel();
                } catch (e) {
                    console.error('Failed to load gear item', e);
                    self.showError('Unable to load gear for edit');
                }
            });
            // Delete gear
            $(document).on('click', '.btn-delete-gear', async function() {
                const id = $(this).data('gear-id');
                if (!confirm('Delete this gear item?')) return;
                try {
                    await $.ajax({ url: self.GEAR_API + '&id=' + id, method: 'DELETE' });
                    await self.loadGearLibrary();
                    self.showSuccess('Gear deleted');
                } catch (e) {
                    console.error('Delete failed', e);
                    self.showError('Failed to delete gear');
                }
            });
            // Add to pack from management table
            $(document).on('click', '.btn-add-to-pack', function() {
                const id = $(this).data('gear-id');
                const gear = (self.state.gearLibrary || []).find(x => x.id === id);
                if (!gear) return;
                const firstSection = $('.dropzone').first().data('section') || 'main';
                self.addGearToSection(gear, firstSection);
                self.showSuccess('Added to pack');
            });
            // Export CSV
            $(document).on('click', '.btn-export-gear', function() {
                self.exportUserGearCSV();
            });
            // Import CSV
            $(document).on('click', '.btn-import-gear', function() {
                self.importUserGearCSV();
            });
            
            // Override save to hit API
            const originalSave = this.saveCustomGear;
            this.saveCustomGear = async function() {
                const payload = {
                    name: $('#custom-name').val(),
                    weight_g: parseInt($('#custom-weight').val() || '0', 10),
                    category: $('#custom-category').val(),
                    description: $('#custom-notes').val() || null
                };
                if (!payload.name) {
                    alert('Please enter a gear name');
                    return;
                }
                try {
                    if (self.state.editingGearId && String(self.state.editingGearId).startsWith('user_')) {
                        await $.ajax({
                            url: self.GEAR_API + '&id=' + self.state.editingGearId,
                            method: 'PUT',
                            contentType: 'application/json',
                            data: JSON.stringify(payload)
                        });
                        self.showSuccess('Gear updated!');
                    } else {
                        await $.ajax({
                            url: self.GEAR_API,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload)
                        });
                        self.showSuccess('Gear created!');
                    }
                    self.hideCustomGearPanel();
                    await self.loadGearLibrary();
                } catch (e) {
                    console.error('Save gear failed', e);
                    self.showError('Failed to save gear');
                }
            };
            this.bindGearEventsOnceBound = true;
        },
        
        exportUserGearCSV: function() {
            const userItems = (this.state.gearApiItems || []).filter(i => i.source === 'user');
            const header = ['name','category','weight_g','brand','price','description'];
            const rows = userItems.map(i => [i.name, i.category, i.weight_g || '', i.brand || '', i.price || '', (i.description||'').replace(/\n/g,' ') ]);
            const csv = [header].concat(rows).map(r => r.map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'gear_user_export.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },
        
        importUserGearCSV: function() {
            const self = this;
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.csv,text/csv';
            input.addEventListener('change', async function() {
                const file = this.files[0];
                if (!file) return;
                const text = await file.text();
                const lines = text.split(/\r?\n/).filter(Boolean);
                if (lines.length <= 1) return;
                const headers = lines[0].split(',').map(h => h.replace(/(^\"|\"$)/g,''));
                const nameIdx = headers.indexOf('name');
                const catIdx = headers.indexOf('category');
                const weightIdx = headers.indexOf('weight_g');
                const brandIdx = headers.indexOf('brand');
                const priceIdx = headers.indexOf('price');
                const descIdx = headers.indexOf('description');
                let created = 0;
                for (let i = 1; i < lines.length; i++) {
                    const cols = lines[i].match(/(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|[^,]+)/g);
                    if (!cols) continue;
                    const val = idx => {
                        if (idx < 0 || idx >= cols.length) return null;
                        const raw = cols[idx].replace(/(^\"|\"$)/g,'').replace(/\"\"/g,'"');
                        return raw === '' ? null : raw;
                    };
                    const payload = {
                        name: val(nameIdx),
                        category: val(catIdx) || 'other',
                        weight_g: parseInt(val(weightIdx) || '0', 10) || 0,
                        brand: val(brandIdx),
                        price: val(priceIdx) ? parseFloat(val(priceIdx)) : null,
                        description: val(descIdx)
                    };
                    if (!payload.name) continue;
                    try {
                        await $.ajax({
                            url: self.GEAR_API,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload)
                        });
                        created++;
                    } catch (e) {
                        console.warn('Failed to import row', i, e);
                    }
                }
                await self.loadGearLibrary();
                self.showSuccess(`Imported ${created} gear item(s)`);
            });
            input.click();
        },
        
        // Delete functionality
        deletePack: function(packId) {
            const pack = this.state.packs.find(p => p.id == packId);
            if (!pack) return;
            
            // Show confirmation modal
            this.showDeleteModal(pack);
        },
        
        showDeleteModal: function(pack) {
            const modalHtml = `
                <div class="delete-modal" id="delete-modal">
                    <div class="delete-modal-content">
                        <div class="delete-modal-header">
                            <span class="delete-modal-icon">⚠️</span>
                            <h3>Delete Backpack?</h3>
                        </div>
                        <div class="delete-modal-body">
                            <p>Are you sure you want to delete this backpack?</p>
                            <div class="delete-modal-pack-name">${pack.name}</div>
                            <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">
                                This action cannot be undone. All items and configurations in this pack will be permanently removed.
                            </p>
                        </div>
                        <div class="delete-modal-actions">
                            <button class="btn-cancel-delete" onclick="PackBuilder.hideDeleteModal()">Cancel</button>
                            <button class="btn-confirm-delete" onclick="PackBuilder.confirmDelete(${pack.id})">Delete Pack</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove any existing modal
            $('#delete-modal').remove();
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Show modal with animation
            setTimeout(() => {
                $('#delete-modal').addClass('active');
            }, 10);
            
            // Handle escape key
            $(document).on('keyup.deleteModal', function(e) {
                if (e.key === 'Escape') {
                    PackBuilder.hideDeleteModal();
                }
            });
            
            // Handle click outside
            $('#delete-modal').on('click', function(e) {
                if (e.target === this) {
                    PackBuilder.hideDeleteModal();
                }
            });
        },
        
        hideDeleteModal: function() {
            $('#delete-modal').removeClass('active');
            setTimeout(() => {
                $('#delete-modal').remove();
            }, 300);
            $(document).off('keyup.deleteModal');
        },
        
        confirmDelete: async function(packId, force = false) {
            try {
                // Call API to delete pack
                const response = await $.ajax({
                    url: '/BTT/api/index.php?route=backpacks&id=' + packId + (force ? '&force=true' : ''),
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.success) {
                    // Remove from state
                    this.state.packs = this.state.packs.filter(p => p.id != packId);
                    
                    // Re-render packs grid
                    this.renderEnhancedPacksGrid();
                    
                    // Hide modal
                    this.hideDeleteModal();
                    
                    // Show success message
                    this.showSuccess('Backpack deleted successfully');
                }
            } catch (error) {
                console.error('Error deleting pack:', error);
                // If conflict due to trips using this backpack, offer force delete
                if (error && error.status === 409) {
                    const pack = (this.state.packs || []).find(p => p.id == packId);
                    const message = (error.responseJSON && (error.responseJSON.error || error.responseJSON.message)) || 'This pack is referenced by one or more trips.';
                    this.showForceDeleteModal(pack, message);
                    return;
                }
                this.showError('Failed to delete backpack. Please try again.');
            }
        },
        
        showForceDeleteModal: function(pack, message) {
            const name = pack ? this.escapeHtml(pack.name) : 'this backpack';
            const modalHtml = `
                <div class="delete-modal" id="delete-modal">
                    <div class="delete-modal-content">
                        <div class="delete-modal-header">
                            <span class="delete-modal-icon">⚠️</span>
                            <h3>Delete Anyway?</h3>
                        </div>
                        <div class="delete-modal-body">
                            <p>${this.escapeHtml(message)}</p>
                            <p style="margin-top: 0.75rem; font-size: 0.9rem; opacity: 0.85;">
                                Proceeding will unlink all trips that reference <strong>${name}</strong> and permanently remove it.
                            </p>
                        </div>
                        <div class="delete-modal-actions">
                            <button class="btn-cancel-delete" onclick="PackBuilder.hideDeleteModal()">Cancel</button>
                            <button class="btn-confirm-delete" onclick="PackBuilder.confirmDelete(${pack ? pack.id : 'null'}, true)">Delete Anyway</button>
                        </div>
                    </div>
                </div>
            `;

            // Replace or add modal
            const $existing = $('#delete-modal');
            if ($existing.length) {
                $existing.remove();
            }
            $('body').append(modalHtml);
            setTimeout(() => { $('#delete-modal').addClass('active'); }, 10);

            // Escape and outside click handlers
            $(document).off('keyup.deleteModal').on('keyup.deleteModal', function(e) {
                if (e.key === 'Escape') {
                    PackBuilder.hideDeleteModal();
                }
            });
            $('#delete-modal').off('click').on('click', function(e) {
                if (e.target === this) {
                    PackBuilder.hideDeleteModal();
                }
            });
        },
        
        duplicatePack: async function(packId) {
            const pack = this.state.packs.find(p => p.id == packId);
            if (!pack) return;
            
            try {
                const response = await $.ajax({
                    url: '/BTT/api/index.php?route=backpacks&action=duplicate&id=' + packId,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        name: pack.name + ' (Copy)'
                    })
                });
                
                if (response.success && response.data) {
                    // Add to state
                    this.state.packs.push(response.data);
                    
                    // Re-render
                    this.renderEnhancedPacksGrid();
                    
                    // Show success
                    this.showSuccess('Backpack duplicated successfully');
                }
            } catch (error) {
                console.error('Error duplicating pack:', error);
                this.showError('Failed to duplicate backpack');
            }
        },
        
        // Enhanced rendering with better UI
        renderEnhancedPacksGrid: function() {
            console.log('🎒 Rendering enhanced packs grid with', this.state.packs.length, 'packs');
            if (this.state.packs.length === 0) {
                $('#packs-grid').html(`
                    <div class="packs-empty-state">
                        <div class="packs-empty-icon">🎒</div>
                        <div class="packs-empty-text">No backpacks yet</div>
                        <div class="packs-empty-subtext">Start your adventure by creating your first pack</div>
                        <button class="btn-action" onclick="PackBuilder.createNewPack()">
                            <i>➕</i> Create Your First Pack
                        </button>
                    </div>
                `);
                return;
            }
            
            const html = this.state.packs.map(pack => {
                // Calculate total weight
                let totalWeight = pack.weight_empty_g || 0;
                let itemCount = 0;
                
                if (pack.sections) {
                    pack.sections.forEach(section => {
                        if (section.items) {
                            section.items.forEach(item => {
                                totalWeight += (item.weight_g || 0) * (item.quantity || 1);
                                itemCount += item.quantity || 1;
                            });
                        }
                    });
                }
                
                // Format dates
                const lastModified = pack.updated_at ? new Date(pack.updated_at).toLocaleDateString() : 'Never';
                
                return `
                    <div class="pack-card" data-pack-id="${pack.id}">
                        <div class="pack-card-header">
                            <h3>${this.escapeHtml(pack.name)}</h3>
                            <div class="pack-card-actions">
                                <button class="btn-edit-pack" data-pack-id="${pack.id}" title="Edit Pack">
                                    ✏️
                                </button>
                                <button class="btn-duplicate-pack" data-pack-id="${pack.id}" title="Duplicate Pack">
                                    📋
                                </button>
                                <button class="btn-delete-pack" data-pack-id="${pack.id}" title="Delete Pack">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        <div class="pack-card-description">
                            ${this.escapeHtml(pack.description || 'No description')}
                        </div>
                        <div class="pack-card-stats">
                            <div class="pack-stat">
                                <span class="pack-stat-value">${this.formatWeight(totalWeight)}</span>
                                <span class="pack-stat-label">Total</span>
                            </div>
                            <div class="pack-stat">
                                <span class="pack-stat-value">${itemCount}</span>
                                <span class="pack-stat-label">Items</span>
                            </div>
                            <div class="pack-stat">
                                <span class="pack-stat-value">${pack.capacity_liters || 65}L</span>
                                <span class="pack-stat-label">Capacity</span>
                            </div>
                        </div>
                        <div class="pack-card-footer">
                            <span class="pack-last-modified">${lastModified}</span>
                        </div>
                    </div>
                `;
            }).join('');
            
            $('#packs-grid').html(html);
            
            // Remove direct click handler, use button clicks instead
            $('.pack-card').off('click').on('click', function(e) {
                // Only trigger edit if clicking on the card itself, not buttons
                if (!$(e.target).closest('.pack-card-actions').length) {
                    const packId = $(this).data('pack-id');
                    PackBuilder.editPack(packId);
                }
            });
        },
        
        // Utility functions
        escapeHtml: function(text) {
            // Handle non-string values
            if (text === null || text === undefined) {
                return '';
            }
            
            // Convert to string if not already
            text = String(text);
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },
        
        showSuccess: function(message) {
            const toast = $(`
                <div class="toast-success" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: rgba(74, 222, 128, 0.9);
                    color: var(--forest-deep, #0a2818);
                    padding: 1rem 1.5rem;
                    border-radius: 0.5rem;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    z-index: 1001;
                    animation: slideInRight 0.3s ease;
                ">
                    <strong>✓</strong> ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        showError: function(message) {
            const toast = $(`
                <div class="toast-error" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: rgba(239, 68, 68, 0.9);
                    color: #fff;
                    padding: 1rem 1.5rem;
                    border-radius: 0.5rem;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
                    z-index: 1001;
                    animation: slideInRight 0.3s ease;
                ">
                    <strong>✗</strong> ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        }
    };
    
    // Merge enhancements with existing PackBuilder
    Object.assign(window.PackBuilder, PackBuilderEnhancements);
    
    // Override the original renderPacksGrid with enhanced version
    if (window.PackBuilder.renderPacksGrid) {
        window.PackBuilder.originalRenderPacksGrid = window.PackBuilder.renderPacksGrid;
        window.PackBuilder.renderPacksGrid = window.PackBuilder.renderEnhancedPacksGrid;
    }
    
    // Add CSS animation keyframes if not already present
    if (!document.getElementById('pack-builder-animations')) {
        const style = document.createElement('style');
        style.id = 'pack-builder-animations';
        style.innerHTML = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Ensure our enhancements are merged after PackBuilder is attached on DOMReady
    $(document).ready(function() {
        if (window.PackBuilder) {
            if (!window.PackBuilder.originalLoadGearLibrary) {
                window.PackBuilder.originalLoadGearLibrary = window.PackBuilder.loadGearLibrary;
            }
            if (!window.PackBuilder.originalQuickAddGear) {
                window.PackBuilder.originalQuickAddGear = window.PackBuilder.quickAddGear;
            }
            
            // Override renderPacksGrid before merging
            if (!window.PackBuilder.originalRenderPacksGrid) {
                window.PackBuilder.originalRenderPacksGrid = window.PackBuilder.renderPacksGrid;
            }
            
            Object.assign(window.PackBuilder, PackBuilderEnhancements);
            window.PackBuilder.renderPacksGrid = window.PackBuilder.renderEnhancedPacksGrid;
            
            window.PackBuilder.ensureGearEventBindings();
            window.PackBuilder.initDragDropOverrides();
            
            // Re-render packs with enhanced UI if they're already loaded
            if (window.PackBuilder.state && window.PackBuilder.state.packs && window.PackBuilder.state.packs.length > 0) {
                window.PackBuilder.renderEnhancedPacksGrid();
            }
            
            // Refresh gear from API to replace any sample data
            window.PackBuilder.loadGearLibrary();
        }
        
        // Add gear table sorting functionality
        initGearTableSorting();
    });
    
    // Gear table sorting functionality
    function initGearTableSorting() {
        let sortOrder = {}; // Track sort order for each column
        
        $(document).on('click', '#gear-sortable-table th.sortable', function() {
            const column = $(this).data('sort');
            const tbody = $('#gear-table-body');
            const rows = tbody.find('tr').toArray();
            
            // Toggle sort order
            sortOrder[column] = sortOrder[column] === 'asc' ? 'desc' : 'asc';
            
            // Update visual indicator
            $('#gear-sortable-table th.sortable span.sort-icon').text('⇅');
            $(this).find('.sort-icon').text(sortOrder[column] === 'asc' ? '↑' : '↓');
            
            // Sort rows
            rows.sort((a, b) => {
                let aVal, bVal;
                
                switch(column) {
                    case 'name':
                        aVal = $(a).find('td:eq(0)').text().toLowerCase();
                        bVal = $(b).find('td:eq(0)').text().toLowerCase();
                        break;
                    case 'category':
                        aVal = $(a).find('td:eq(1)').text().toLowerCase();
                        bVal = $(b).find('td:eq(1)').text().toLowerCase();
                        break;
                    case 'weight':
                        aVal = parseInt($(a).find('td:eq(2)').text()) || 0;
                        bVal = parseInt($(b).find('td:eq(2)').text()) || 0;
                        break;
                    case 'brand':
                        aVal = $(a).find('td:eq(3)').text().toLowerCase();
                        bVal = $(b).find('td:eq(3)').text().toLowerCase();
                        break;
                    case 'price':
                        aVal = parseFloat($(a).find('td:eq(4)').text().replace('$', '')) || 0;
                        bVal = parseFloat($(b).find('td:eq(4)').text().replace('$', '')) || 0;
                        break;
                    default:
                        return 0;
                }
                
                if (column === 'weight' || column === 'price') {
                    return sortOrder[column] === 'asc' ? aVal - bVal : bVal - aVal;
                } else {
                    if (aVal < bVal) return sortOrder[column] === 'asc' ? -1 : 1;
                    if (aVal > bVal) return sortOrder[column] === 'asc' ? 1 : -1;
                    return 0;
                }
            });
            
            // Re-append sorted rows
            tbody.empty();
            rows.forEach(row => tbody.append(row));
        });
    }

})(jQuery);
