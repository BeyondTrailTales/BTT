/**
 * Pack Manager Module
 * Clean, modular pack management functionality
 * Uses unified BTT_API client
 */

window.PackManager = (function() {
    'use strict';

    // Module state
    const state = {
        packs: [],
        currentPack: null,
        loading: false,
        viewMode: 'grid', // grid or list
        packedItemsPanel: {
            isOpen: false,
            items: [],
            loading: false,
            filter: 'all',
            searchTerm: '',
            currentPackId: null,
            currentPackName: '',
            associatedTrips: []
        }
    };

    // DOM elements cache
    const elements = {
        packsGrid: null,
        loadingIndicator: null,
        emptyState: null
    };

    /**
     * Initialize the pack manager
     */
    function init() {
        console.log('[PackManager] Initializing...');
        
        // Cache DOM elements
        elements.packsGrid = document.getElementById('packs-grid-items');
        elements.loadingIndicator = document.getElementById('packs-loading');
        elements.emptyState = document.getElementById('packs-empty');

        // Load packs on init
        loadPacks();

        // Bind global events
        bindEvents();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Create pack button
        document.querySelectorAll('#createNewPackBtn, #create-first-pack').forEach(btn => {
            btn?.addEventListener('click', createPack);
        });

        // View mode toggle
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn?.addEventListener('click', (e) => {
                const view = e.target.dataset.view;
                setViewMode(view);
            });
        });

        // Search
        const searchInputs = document.querySelectorAll('#pack-search-hero, #pack-search-main');
        searchInputs.forEach(input => {
            input?.addEventListener('input', debounce((e) => {
                filterPacks(e.target.value);
            }, 300));
        });

        // Sort
        document.getElementById('sort-hero')?.addEventListener('change', (e) => {
            sortPacks(e.target.value);
        });

        // Initialize packed items panel
        initPackedItemsPanel();
    }

    /**
     * Load all packs
     */
    async function loadPacks() {
        if (state.loading) return;

        state.loading = true;
        showLoading(true);

        try {
            const packs = await BTT_API.backpacks.list();
            state.packs = Array.isArray(packs) ? packs : [];
            renderPacks();
        } catch (error) {
            console.error('[PackManager] Failed to load packs:', error);
            showError('Failed to load packs. Please try refreshing the page.');
        } finally {
            state.loading = false;
            showLoading(false);
        }
    }

    /**
     * Render packs to DOM
     */
    function renderPacks(packs = state.packs) {
        console.log('[PackManager] Rendering', packs.length, 'packs');

        // Clear grid
        if (elements.packsGrid) {
            elements.packsGrid.innerHTML = '';
        }

        // Show/hide empty state
        if (!packs || packs.length === 0) {
            showEmptyState(true);
            return;
        }

        showEmptyState(false);

        // Render each pack
        packs.forEach(pack => {
            const packElement = createPackElement(pack);
            elements.packsGrid?.appendChild(packElement);
        });

        // Apply view mode
        applyViewMode();
    }

    /**
     * Create pack DOM element
     */
    function createPackElement(pack) {
        const div = document.createElement('div');
        div.className = 'card pack-card';
        div.dataset.packId = pack.id;

        const totalWeight = pack.total_weight_g || 0;
        const itemCount = pack.total_items || 0;
        const tripCount = pack.trip_count || 0;

        div.innerHTML = `
            <div class="card-header">
                <h3 class="card-title">
                    <i class="icon">🎒</i>
                    ${escapeHtml(pack.name)}
                </h3>
                <div class="card-actions">
                    <button class="btn-icon" onclick="PackManager.viewPackItems(${pack.id})" title="View items">
                        <i>📦</i>
                    </button>
                    <button class="btn-icon" onclick="PackManager.editPack(${pack.id})" title="Edit pack">
                        <i>✏️</i>
                    </button>
                    <button class="btn-icon" onclick="PackManager.duplicatePack(${pack.id})" title="Duplicate pack">
                        <i>📋</i>
                    </button>
                    <button class="btn-icon btn-danger" onclick="PackManager.deletePack(${pack.id})" title="Delete pack">
                        <i>🗑️</i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                ${pack.description ? `<p class="pack-description">${escapeHtml(pack.description)}</p>` : ''}
                <div class="pack-stats">
                    <div class="stat">
                        <span class="stat-icon">⚖️</span>
                        <span class="stat-value">${formatWeight(totalWeight)}</span>
                        <span class="stat-label">Total Weight</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">📦</span>
                        <span class="stat-value">${itemCount}</span>
                        <span class="stat-label">Items</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">🏔️</span>
                        <span class="stat-value">${tripCount}</span>
                        <span class="stat-label">Trips</span>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary btn-block" onclick="PackManager.openPackBuilder(${pack.id})">
                    Open in Builder
                </button>
            </div>
        `;

        return div;
    }

    /**
     * Create new pack
     */
    async function createPack() {
        // For now, redirect to pack builder
        // Later we can add a modal for quick creation
        window.location.href = '/BTT/pack-builder.php';
    }

    /**
     * Edit pack
     */
    function editPack(packId) {
        window.location.href = `/BTT/pack-builder.php?id=${packId}`;
    }

    /**
     * Open pack in builder
     */
    function openPackBuilder(packId) {
        window.location.href = `/BTT/pack-builder.php?id=${packId}`;
    }

    /**
     * Delete pack
     */
    async function deletePack(packId) {
        const pack = state.packs.find(p => p.id === packId);
        if (!pack) return;

        if (!confirm(`Are you sure you want to delete "${pack.name}"?`)) {
            return;
        }

        try {
            await BTT_API.backpacks.delete(packId);
            
            // Remove from state
            state.packs = state.packs.filter(p => p.id !== packId);
            
            // Re-render
            renderPacks();
            
            showSuccess('Pack deleted successfully');
        } catch (error) {
            console.error('[PackManager] Failed to delete pack:', error);
            showError('Failed to delete pack: ' + error.message);
        }
    }

    /**
     * Duplicate pack
     */
    async function duplicatePack(packId) {
        const pack = state.packs.find(p => p.id === packId);
        if (!pack) return;

        try {
            const newPack = await BTT_API.backpacks.duplicate(packId);
            
            // Add to state
            state.packs.unshift(newPack);
            
            // Re-render
            renderPacks();
            
            showSuccess(`Created copy of "${pack.name}"`);
        } catch (error) {
            console.error('[PackManager] Failed to duplicate pack:', error);
            showError('Failed to duplicate pack: ' + error.message);
        }
    }

    /**
     * Filter packs by search term
     */
    function filterPacks(searchTerm) {
        if (!searchTerm) {
            renderPacks();
            return;
        }

        const filtered = state.packs.filter(pack => {
            return pack.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                   (pack.description && pack.description.toLowerCase().includes(searchTerm.toLowerCase()));
        });

        renderPacks(filtered);
    }

    /**
     * Sort packs
     */
    function sortPacks(sortBy) {
        let sorted = [...state.packs];

        switch(sortBy) {
            case 'name':
                sorted.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case 'weight':
                sorted.sort((a, b) => (b.total_weight_g || 0) - (a.total_weight_g || 0));
                break;
            case 'recent':
            default:
                sorted.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
                break;
        }

        renderPacks(sorted);
    }

    /**
     * UI Helper Functions
     */
    function showLoading(show) {
        if (elements.loadingIndicator) {
            elements.loadingIndicator.style.display = show ? 'block' : 'none';
        }
    }

    function showEmptyState(show) {
        if (elements.emptyState) {
            elements.emptyState.style.display = show ? 'block' : 'none';
        }
        if (elements.packsGrid) {
            elements.packsGrid.style.display = show ? 'none' : 'grid';
        }
    }

    function setViewMode(mode) {
        state.viewMode = mode;
        applyViewMode();
        
        // Update toggle buttons
        document.querySelectorAll('.view-toggle-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === mode);
        });
    }

    function applyViewMode() {
        if (elements.packsGrid) {
            elements.packsGrid.className = state.viewMode === 'list' ? 
                'packs-list' : 'dashboard packs-grid';
        }
    }

    function showSuccess(message) {
        // Use toast notification if available
        if (window.showToast) {
            window.showToast(message, 'success');
        } else {
            console.log('[Success]', message);
        }
    }

    function showError(message) {
        // Use toast notification if available
        if (window.showToast) {
            window.showToast(message, 'error');
        } else {
            console.error('[Error]', message);
        }
    }

    /**
     * Utility functions
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatWeight(grams) {
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }

    function debounce(func, wait) {
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

    /**
     * View specific pack items
     */
    async function viewPackItems(packId) {
        const pack = state.packs.find(p => p.id === packId);
        if (!pack) return;

        state.packedItemsPanel.currentPackId = packId;
        state.packedItemsPanel.currentPackName = pack.name;
        
        openPackedItemsPanel();
        await loadPackItemsAndTrips(packId);
    }

    /**
     * Toggle packed items panel (for all items view)
     */
    function togglePackedItemsPanel() {
        const panel = document.getElementById('packed-items-panel');
        if (!panel) return;

        state.packedItemsPanel.isOpen = !state.packedItemsPanel.isOpen;
        
        if (state.packedItemsPanel.isOpen) {
            // Reset to show all packs
            state.packedItemsPanel.currentPackId = null;
            state.packedItemsPanel.currentPackName = '';
            state.packedItemsPanel.associatedTrips = [];
            
            panel.classList.add('open');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
            updatePanelHeader();
            loadPackedItems();
        } else {
            panel.classList.remove('open');
            document.body.style.overflow = ''; // Restore body scroll
        }
    }

    /**
     * Open packed items panel
     */
    function openPackedItemsPanel() {
        const panel = document.getElementById('packed-items-panel');
        if (!panel) return;

        state.packedItemsPanel.isOpen = true;
        panel.classList.add('open');
        document.body.style.overflow = 'hidden';
        updatePanelHeader();
    }

    /**
     * Load all packed items from all packs/trips
     */
    async function loadPackedItems() {
        if (state.packedItemsPanel.loading) return;

        state.packedItemsPanel.loading = true;
        showPackedItemsLoading(true);

        try {
            // Get all packs with their items
            const packsWithItems = await BTT_API.backpacks.listWithItems();
            
            // Process items
            const allItems = [];
            packsWithItems.forEach(pack => {
                if (pack.items && pack.items.length > 0) {
                    pack.items.forEach(item => {
                        allItems.push({
                            ...item,
                            packId: pack.id,
                            packName: pack.name
                        });
                    });
                }
            });

            state.packedItemsPanel.items = allItems;
            renderPackedItems();
            updatePackedItemsSummary();
            
        } catch (error) {
            console.error('[PackManager] Failed to load packed items:', error);
            showPackedItemsError('Failed to load items');
        } finally {
            state.packedItemsPanel.loading = false;
            showPackedItemsLoading(false);
        }
    }

    /**
     * Render packed items in the panel
     */
    function renderPackedItems() {
        const itemsList = document.getElementById('items-list');
        if (!itemsList) return;

        // Clear and render
        itemsList.innerHTML = '';

        // If showing a specific pack with trips
        if (state.packedItemsPanel.currentPackId && state.packedItemsPanel.associatedTrips.length > 0) {
            renderPackWithTrips();
            return;
        }

        // Otherwise show standard view
        renderStandardPackView();
    }

    /**
     * Render pack with associated trips
     */
    function renderPackWithTrips() {
        const itemsList = document.getElementById('items-list');
        const trips = state.packedItemsPanel.associatedTrips;

        // Show pack sections first
        const packSectionsDiv = document.createElement('div');
        packSectionsDiv.className = 'pack-sections-view';
        packSectionsDiv.innerHTML = '<h4 style="margin: 0 0 1rem 0; color: var(--text-secondary);">Pack Contents by Section</h4>';
        
        // Group items by section
        const itemsBySection = {};
        state.packedItemsPanel.items.forEach(item => {
            const sectionName = item.section_name || item.section || 'Main';
            if (!itemsBySection[sectionName]) {
                itemsBySection[sectionName] = [];
            }
            itemsBySection[sectionName].push(item);
        });

        Object.entries(itemsBySection).forEach(([section, items]) => {
            const sectionDiv = createSectionElement(section, items);
            packSectionsDiv.appendChild(sectionDiv);
        });

        itemsList.appendChild(packSectionsDiv);

        // Show associated trips
        if (trips.length > 0) {
            const tripsDiv = document.createElement('div');
            tripsDiv.className = 'associated-trips';
            tripsDiv.innerHTML = `
                <h4 style="margin: 1.5rem 0 1rem 0; color: var(--text-secondary);">
                    Associated Trips (${trips.length})
                </h4>
            `;

            trips.forEach(trip => {
                const tripElement = createTripElement(trip);
                tripsDiv.appendChild(tripElement);
            });

            itemsList.appendChild(tripsDiv);
        }

        itemsList.style.display = 'block';
        showPackedItemsEmpty(false);
    }

    /**
     * Render standard pack view (all packs or filtered)
     */
    function renderStandardPackView() {
        const itemsList = document.getElementById('items-list');
        
        // Filter items
        let filteredItems = state.packedItemsPanel.items;
        
        // Apply category filter
        if (state.packedItemsPanel.filter !== 'all') {
            filteredItems = filteredItems.filter(item => 
                (item.gear_category || item.category) === state.packedItemsPanel.filter
            );
        }

        // Apply search filter
        if (state.packedItemsPanel.searchTerm) {
            const searchLower = state.packedItemsPanel.searchTerm.toLowerCase();
            filteredItems = filteredItems.filter(item =>
                item.name.toLowerCase().includes(searchLower) ||
                (item.brand && item.brand.toLowerCase().includes(searchLower))
            );
        }

        // Group by pack
        const itemsByPack = {};
        filteredItems.forEach(item => {
            if (!itemsByPack[item.packId]) {
                itemsByPack[item.packId] = {
                    packName: item.packName,
                    items: []
                };
            }
            itemsByPack[item.packId].items.push(item);
        });

        if (Object.keys(itemsByPack).length === 0) {
            showPackedItemsEmpty(true);
            itemsList.style.display = 'none';
            return;
        }

        showPackedItemsEmpty(false);
        itemsList.style.display = 'block';

        // Render each pack group
        Object.entries(itemsByPack).forEach(([packId, packData]) => {
            const packGroup = createPackGroupElement(packId, packData);
            itemsList.appendChild(packGroup);
        });
    }

    /**
     * Create pack group element
     */
    function createPackGroupElement(packId, packData) {
        const packWeight = packData.items.reduce((sum, item) => 
            sum + ((item.weight_g || 0) * (item.quantity || 1)), 0
        );

        const div = document.createElement('div');
        div.className = 'pack-group';
        
        div.innerHTML = `
            <div class="pack-group-header">
                <span>${escapeHtml(packData.packName)}</span>
                <span class="pack-weight">${formatWeight(packWeight)}</span>
            </div>
        `;

        packData.items.forEach(item => {
            const itemCard = createItemCardElement(item);
            div.appendChild(itemCard);
        });

        return div;
    }

    /**
     * Create item card element
     */
    function createItemCardElement(item) {
        const div = document.createElement('div');
        div.className = 'item-card';
        
        const itemWeight = (item.weight_g || 0) * (item.quantity || 1);
        const category = (item.gear_category || item.category || 'other').toLowerCase();
        
        div.innerHTML = `
            <div class="item-info">
                <span class="item-category">${category}</span>
                <span class="item-name">${escapeHtml(item.name)}${item.quantity > 1 ? ` (${item.quantity}x)` : ''}</span>
            </div>
            <div class="item-weight">${formatWeight(itemWeight)}</div>
        `;

        return div;
    }

    /**
     * Create section element
     */
    function createSectionElement(sectionName, items) {
        const div = document.createElement('div');
        div.className = 'section-group';
        
        const sectionWeight = items.reduce((sum, item) => 
            sum + ((item.weight_g || 0) * (item.quantity || 1)), 0
        );
        
        div.innerHTML = `
            <div class="section-header">
                <span>${escapeHtml(sectionName)}</span>
                <span class="section-stats">${items.length} items • ${formatWeight(sectionWeight)}</span>
            </div>
        `;

        const itemsList = document.createElement('div');
        itemsList.className = 'section-items';
        
        items.forEach(item => {
            const itemCard = createItemCardElement(item);
            itemsList.appendChild(itemCard);
        });

        div.appendChild(itemsList);
        return div;
    }

    /**
     * Create trip element with packing checklist
     */
    function createTripElement(trip) {
        const div = document.createElement('div');
        div.className = 'trip-card';
        
        const hasPackingData = trip.packingData && trip.packingData.items;
        const progress = hasPackingData ? trip.packingData.summary : { packed: 0, total: 0, percent: 0 };
        
        div.innerHTML = `
            <div class="trip-header">
                <div class="trip-info">
                    <h5 class="trip-name">${escapeHtml(trip.name)}</h5>
                    <div class="trip-dates">${formatTripDates(trip.start_date, trip.end_date)}</div>
                </div>
                <div class="trip-progress">
                    <div class="progress-text">${progress.packed}/${progress.total} packed</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress.percent}%"></div>
                    </div>
                </div>
            </div>
        `;

        if (hasPackingData) {
            const checklistDiv = document.createElement('div');
            checklistDiv.className = 'trip-checklist';
            checklistDiv.style.marginTop = '1rem';
            
            // Show items with checkboxes
            trip.packingData.items.forEach(item => {
                if (item.gear_id || item.type === 'gear') {
                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'checklist-item';
                    itemDiv.innerHTML = `
                        <label class="checkbox-label">
                            <input type="checkbox" 
                                ${item.is_packed ? 'checked' : ''} 
                                onchange="PackManager.toggleTripItem(${trip.id}, ${item.gear_id || item.id}, this.checked)">
                            <span class="item-name">${escapeHtml(item.name)}</span>
                            <span class="item-qty">${item.quantity > 1 ? `x${item.quantity}` : ''}</span>
                        </label>
                    `;
                    checklistDiv.appendChild(itemDiv);
                }
            });
            
            div.appendChild(checklistDiv);
        } else {
            div.innerHTML += '<div class="no-packing-data">Loading packing list...</div>';
        }

        return div;
    }

    /**
     * Update packed items summary
     */
    function updatePackedItemsSummary() {
        const totalItems = state.packedItemsPanel.items.length;
        const totalWeight = state.packedItemsPanel.items.reduce((sum, item) => 
            sum + ((item.weight_g || 0) * (item.quantity || 1)), 0
        );

        document.getElementById('total-items').textContent = totalItems;
        document.getElementById('total-weight').textContent = formatWeight(totalWeight);
    }

    /**
     * Format trip dates
     */
    function formatTripDates(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        
        if (start.toDateString() === end.toDateString()) {
            return start.toLocaleDateString('en-US', options);
        } else {
            return `${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${end.toLocaleDateString('en-US', options)}`;
        }
    }

    /**
     * Packed items panel UI helpers
     */
    function showPackedItemsLoading(show) {
        const loading = document.getElementById('items-loading');
        const list = document.getElementById('items-list');
        
        if (loading) loading.style.display = show ? 'flex' : 'none';
        if (list && show) list.style.display = 'none';
    }

    function showPackedItemsEmpty(show) {
        const empty = document.getElementById('items-empty');
        if (empty) empty.style.display = show ? 'block' : 'none';
    }

    function showPackedItemsError(message) {
        const list = document.getElementById('items-list');
        if (list) {
            list.innerHTML = `<div class="error-message">${message}</div>`;
            list.style.display = 'block';
        }
    }

    /**
     * Load pack items and associated trips
     */
    async function loadPackItemsAndTrips(packId) {
        if (state.packedItemsPanel.loading) return;

        state.packedItemsPanel.loading = true;
        showPackedItemsLoading(true);

        try {
            // Get pack details with items
            const pack = await BTT_API.backpacks.get(packId);
            
            // Get all trips to find ones using this backpack
            const trips = await BTT_API.trips.list();
            const associatedTrips = trips.filter(trip => trip.backpack_id === packId);
            
            // Process items from pack sections
            const allItems = [];
            if (pack.sections) {
                pack.sections.forEach(section => {
                    section.items.forEach(item => {
                        allItems.push({
                            ...item,
                            packId: pack.id,
                            packName: pack.name,
                            section: section.id,
                            section_name: section.name
                        });
                    });
                });
            }

            state.packedItemsPanel.items = allItems;
            state.packedItemsPanel.associatedTrips = associatedTrips;
            
            renderPackedItems();
            updatePackedItemsSummary();
            
            // If there are associated trips, load their packing status
            if (associatedTrips.length > 0) {
                await loadTripsPackingStatus(associatedTrips);
            }
            
        } catch (error) {
            console.error('[PackManager] Failed to load pack items:', error);
            showPackedItemsError('Failed to load pack items');
        } finally {
            state.packedItemsPanel.loading = false;
            showPackedItemsLoading(false);
        }
    }

    /**
     * Load packing status for trips
     */
    async function loadTripsPackingStatus(trips) {
        // For each trip, get its packing list status
        for (const trip of trips) {
            try {
                const packingData = await BTT_API.request(`trips/${trip.id}/packing-list`);
                trip.packingData = packingData;
            } catch (error) {
                console.error(`Failed to load packing data for trip ${trip.id}:`, error);
                trip.packingData = null;
            }
        }
        
        // Re-render to show trip packing status
        renderPackedItems();
    }

    /**
     * Update panel header
     */
    function updatePanelHeader() {
        const headerTitle = document.querySelector('#packed-items-panel .panel-header h3');
        if (!headerTitle) return;

        if (state.packedItemsPanel.currentPackId && state.packedItemsPanel.currentPackName) {
            headerTitle.textContent = `${state.packedItemsPanel.currentPackName} - Items & Trips`;
        } else {
            headerTitle.textContent = 'All Packed Items';
        }
    }

    /**
     * Toggle packed item in trip
     */
    async function toggleTripItem(tripId, itemId, isPacked) {
        try {
            await BTT_API.request(`trips/${tripId}/packing-list`, {
                method: 'PUT',
                params: { 
                    sub_action: 'gear',
                    item_id: itemId 
                },
                data: { is_packed: isPacked }
            });

            // Update local state
            const trip = state.packedItemsPanel.associatedTrips.find(t => t.id === tripId);
            if (trip && trip.packingData && trip.packingData.items) {
                const item = trip.packingData.items.find(i => 
                    (i.gear_id && i.gear_id == itemId) || (i.id === `gear-${itemId}`)
                );
                if (item) {
                    item.is_packed = isPacked;
                    
                    // Update summary
                    const packed = trip.packingData.items.filter(i => i.is_packed).length;
                    trip.packingData.summary.packed = packed;
                    trip.packingData.summary.percent = Math.floor((packed / trip.packingData.summary.total) * 100);
                }
            }

            // Re-render
            renderPackedItems();
            showSuccess(isPacked ? 'Item marked as packed' : 'Item marked as unpacked');
            
        } catch (error) {
            console.error('[PackManager] Failed to update trip item:', error);
            showError('Failed to update packing status');
        }
    }

    /**
     * Initialize packed items panel events
     */
    function initPackedItemsPanel() {
        // Search
        const searchInput = document.getElementById('items-search');
        searchInput?.addEventListener('input', debounce((e) => {
            state.packedItemsPanel.searchTerm = e.target.value;
            renderPackedItems();
        }, 300));

        // Filter
        const filterSelect = document.getElementById('items-filter');
        filterSelect?.addEventListener('change', (e) => {
            state.packedItemsPanel.filter = e.target.value;
            renderPackedItems();
        });

        // Close on backdrop click
        const panel = document.getElementById('packed-items-panel');
        panel?.addEventListener('click', (e) => {
            if (e.target === panel) {
                togglePackedItemsPanel();
            }
        });
    }

    // Public API
    return {
        init,
        loadPacks,
        createPack,
        editPack,
        openPackBuilder,
        deletePack,
        duplicatePack,
        filterPacks,
        sortPacks,
        togglePackedItemsPanel,
        viewPackItems,
        toggleTripItem
    };

})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManager.init);
} else {
    PackManager.init();
}