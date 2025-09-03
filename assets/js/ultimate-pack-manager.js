/**
 * Ultimate Pack Manager - Flagship Backpack Management System
 * The most advanced backpack organizer for outdoor enthusiasts
 */

const PackManager = (function() {
    'use strict';

    // State Management
    const state = {
        packs: [],
        currentPack: null,
        currentView: 'grid',
        filters: {
            type: 'all',
            weight: 30,
            season: null,
            tags: [],
            search: ''
        },
        gearInventory: [],
        templates: [],
        categories: {
            all: { count: 0, packs: [] },
            favorites: { count: 0, packs: [] },
            recent: { count: 0, packs: [] },
            shared: { count: 0, packs: [] }
        },
        isLoading: false,
        detailPanelOpen: false,
        tourActive: false
    };

    // API Configuration
    const API = {
        base: '/BTT/api/',
        endpoints: {
            packs: 'backpacks',
            gear: 'gear',
            templates: 'templates/backpack',
            gamification: 'routes/gamification.php'
        }
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', init);

    async function init() {
        console.log('🎒 Ultimate Pack Manager initializing...');
        
        // Load initial data
        await Promise.all([
            loadPacks(),
            loadGearInventory(),
            loadTemplates()
        ]);

        // Setup event listeners
        setupEventListeners();
        setupKeyboardShortcuts();
        
        // Initialize UI
        updateHeroStats();
        renderPacks();
        
        // Check for tour
        if (!localStorage.getItem('packManagerTourCompleted')) {
            setTimeout(() => {
                if (state.packs.length === 0) {
                    showWelcomeTour();
                }
            }, 1000);
        }

        // Start animations
        animateHero();
        
        console.log('✅ Pack Manager ready!');
    }

    // Data Loading
    async function loadPacks() {
        try {
            setState({ isLoading: true });
            
            // Simulate API call with mock data for now
            const mockPacks = getMockPacks();
            state.packs = mockPacks;
            
            // Categorize packs
            categorizePacks();
            
            return mockPacks;
        } catch (error) {
            console.error('Error loading packs:', error);
            showToast('Failed to load packs', 'error');
        } finally {
            setState({ isLoading: false });
        }
    }

    async function loadGearInventory() {
        try {
            // Mock gear data
            state.gearInventory = getMockGear();
        } catch (error) {
            console.error('Error loading gear:', error);
        }
    }

    async function loadTemplates() {
        try {
            // Mock templates
            state.templates = getMockTemplates();
        } catch (error) {
            console.error('Error loading templates:', error);
        }
    }

    // UI Rendering
    function renderPacks() {
        const container = document.getElementById('pack-container');
        if (!container) return;

        // Hide all states first
        hideElement('loading-state');
        hideElement('empty-state');
        hideElement('pack-grid');

        if (state.isLoading) {
            showElement('loading-state');
            return;
        }

        const filteredPacks = getFilteredPacks();

        if (filteredPacks.length === 0) {
            if (state.packs.length === 0) {
                showElement('empty-state');
            } else {
                showEmptyFilterState();
            }
            return;
        }

        const grid = document.getElementById('pack-grid');
        if (!grid) return;

        grid.innerHTML = '';
        showElement('pack-grid');

        // Render based on current view
        switch (state.currentView) {
            case 'list':
                renderListView(filteredPacks);
                break;
            case 'kanban':
                renderKanbanView(filteredPacks);
                break;
            default:
                renderGridView(filteredPacks);
        }
    }

    function renderGridView(packs) {
        const grid = document.getElementById('pack-grid');
        grid.className = 'pack-grid';
        
        packs.forEach(pack => {
            const card = createPackCard(pack);
            grid.appendChild(card);
        });

        // Add entrance animation
        animateCards();
    }

    function createPackCard(pack) {
        const card = document.createElement('div');
        card.className = 'pack-card fade-in';
        card.dataset.packId = pack.id;
        
        const isFavorite = pack.favorite ? '⭐' : '';
        const packingProgress = calculatePackingProgress(pack);
        
        card.innerHTML = `
            <div class="pack-card-header">
                <div class="pack-card-title">
                    ${pack.name}
                    ${isFavorite ? '<span class="pack-favorite">⭐</span>' : ''}
                </div>
                <div class="pack-card-meta">
                    <span>${pack.type}</span>
                    <span>•</span>
                    <span>${formatDate(pack.updated_at)}</span>
                </div>
            </div>
            <div class="pack-card-body">
                <div class="pack-stats-grid">
                    <div class="pack-stat">
                        <div class="pack-stat-icon">⚖️</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${formatWeight(pack.total_weight)}</div>
                            <div class="pack-stat-label">Total</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">📦</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${pack.items_count || 0}</div>
                            <div class="pack-stat-label">Items</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">🏕️</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${pack.trips_count || 0}</div>
                            <div class="pack-stat-label">Trips</div>
                        </div>
                    </div>
                    <div class="pack-stat">
                        <div class="pack-stat-icon">📍</div>
                        <div class="pack-stat-info">
                            <div class="pack-stat-value">${pack.sections_count || 8}</div>
                            <div class="pack-stat-label">Sections</div>
                        </div>
                    </div>
                </div>
                <div class="pack-progress">
                    <div class="progress-header">
                        <span>Packing Progress</span>
                        <span>${packingProgress}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${packingProgress}%"></div>
                    </div>
                </div>
            </div>
            <div class="pack-card-footer">
                <div class="pack-tags">
                    ${pack.tags ? pack.tags.map(tag => `<span class="pack-tag">${tag}</span>`).join('') : ''}
                </div>
                <div class="pack-actions">
                    <button class="pack-action-btn" onclick="PackManager.editPack('${pack.id}')" aria-label="Edit">
                        ✏️
                    </button>
                    <button class="pack-action-btn" onclick="PackManager.duplicatePack('${pack.id}')" aria-label="Duplicate">
                        📋
                    </button>
                    <button class="pack-action-btn" onclick="PackManager.exportPack('${pack.id}')" aria-label="Export">
                        📤
                    </button>
                </div>
            </div>
        `;

        // Add click handler for detail view
        card.addEventListener('click', (e) => {
            if (!e.target.closest('.pack-actions')) {
                showPackDetail(pack);
            }
        });

        return card;
    }

    function showPackDetail(pack) {
        state.currentPack = pack;
        state.detailPanelOpen = true;
        
        const panel = document.getElementById('detail-panel');
        const content = document.getElementById('detail-content');
        const title = document.getElementById('detail-title');
        
        if (!panel || !content) return;
        
        title.textContent = pack.name;
        
        content.innerHTML = `
            <div class="detail-section">
                <h3>Overview</h3>
                <div class="detail-stats">
                    <div class="detail-stat">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value">${pack.type}</span>
                    </div>
                    <div class="detail-stat">
                        <span class="detail-label">Created:</span>
                        <span class="detail-value">${formatDate(pack.created_at)}</span>
                    </div>
                    <div class="detail-stat">
                        <span class="detail-label">Last Modified:</span>
                        <span class="detail-value">${formatDate(pack.updated_at)}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h3>Weight Breakdown</h3>
                <canvas id="weight-chart" width="300" height="200"></canvas>
            </div>
            
            <div class="detail-section">
                <h3>Sections (${pack.sections?.length || 0})</h3>
                <div class="sections-list">
                    ${pack.sections ? pack.sections.map(section => `
                        <div class="section-item">
                            <span class="section-color" style="background: ${section.color}"></span>
                            <span class="section-name">${section.name}</span>
                            <span class="section-count">${section.items?.length || 0} items</span>
                        </div>
                    `).join('') : '<p>No sections yet</p>'}
                </div>
            </div>
            
            <div class="detail-actions">
                <button class="btn-primary-large" onclick="PackManager.openBuilder('${pack.id}')">
                    <span>🛠️</span> Open in Builder
                </button>
                <button class="btn-secondary-large" onclick="PackManager.sharePack('${pack.id}')">
                    <span>🔗</span> Share Pack
                </button>
            </div>
        `;
        
        // Show panel
        panel.classList.remove('hidden');
        document.querySelector('.content-wrapper-ultimate').classList.add('with-detail');
        
        // Draw weight chart
        setTimeout(() => drawWeightChart(pack), 100);
    }

    function closeDetail() {
        const panel = document.getElementById('detail-panel');
        panel.classList.add('hidden');
        document.querySelector('.content-wrapper-ultimate').classList.remove('with-detail');
        state.detailPanelOpen = false;
        state.currentPack = null;
    }

    // Filtering
    function getFilteredPacks() {
        let packs = [...state.packs];
        
        // Type filter
        if (state.filters.type !== 'all') {
            packs = packs.filter(p => p.type === state.filters.type);
        }
        
        // Weight filter
        packs = packs.filter(p => (p.total_weight || 0) <= state.filters.weight * 1000);
        
        // Season filter
        if (state.filters.season) {
            packs = packs.filter(p => p.season === state.filters.season);
        }
        
        // Tag filter
        if (state.filters.tags.length > 0) {
            packs = packs.filter(p => 
                p.tags && state.filters.tags.some(tag => p.tags.includes(tag))
            );
        }
        
        // Search filter
        if (state.filters.search) {
            const search = state.filters.search.toLowerCase();
            packs = packs.filter(p => 
                p.name.toLowerCase().includes(search) ||
                (p.description && p.description.toLowerCase().includes(search)) ||
                (p.tags && p.tags.some(t => t.toLowerCase().includes(search)))
            );
        }
        
        return packs;
    }

    // Actions
    function createNew() {
        showPackBuilder();
    }

    function showPackBuilder(packId = null) {
        const modal = document.getElementById('pack-builder-modal');
        const builderLayout = modal.querySelector('.builder-layout');
        
        builderLayout.innerHTML = `
            <div class="builder-container">
                <div class="builder-sidebar">
                    <h3>Pack Details</h3>
                    <form id="pack-form">
                        <div class="form-group">
                            <label>Pack Name</label>
                            <input type="text" id="pack-name" class="form-control" placeholder="My Adventure Pack" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select id="pack-type" class="form-control">
                                <option value="day">Day Hike</option>
                                <option value="weekend">Weekend</option>
                                <option value="multi">Multi-Day</option>
                                <option value="thru">Thru-Hike</option>
                                <option value="ultra">Ultralight</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity (L)</label>
                            <input type="number" id="pack-capacity" class="form-control" value="65" min="10" max="150">
                        </div>
                        <div class="form-group">
                            <label>Base Weight (g)</label>
                            <input type="number" id="pack-weight" class="form-control" value="1500" min="0">
                        </div>
                    </form>
                </div>
                
                <div class="builder-main">
                    <h3>Sections & Items</h3>
                    <div id="sections-builder">
                        <!-- Sections will be added here -->
                    </div>
                    <button class="btn-add-section" onclick="PackManager.addSection()">
                        ➕ Add Section
                    </button>
                </div>
                
                <div class="builder-gear">
                    <h3>Gear Inventory</h3>
                    <div class="gear-search">
                        <input type="search" placeholder="Search gear..." onkeyup="PackManager.searchBuilderGear(this.value)">
                    </div>
                    <div id="builder-gear-list">
                        <!-- Gear items -->
                    </div>
                </div>
            </div>
            
            <div class="builder-footer">
                <button class="btn-secondary" onclick="PackManager.closeBuilder()">Cancel</button>
                <button class="btn-primary" onclick="PackManager.savePackFromBuilder()">Save Pack</button>
            </div>
        `;
        
        // Load sections if editing
        if (packId) {
            loadPackIntoBuilder(packId);
        } else {
            addDefaultSections();
        }
        
        // Load gear inventory
        loadBuilderGear();
        
        modal.classList.remove('hidden');
    }

    function closeBuilder() {
        const modal = document.getElementById('pack-builder-modal');
        modal.classList.add('hidden');
    }

    // Quick Actions
    function quickStart() {
        // Show quick start wizard
        const steps = [
            {
                title: 'Choose Your Adventure',
                content: 'What type of trip are you planning?',
                options: ['Day Hike', 'Weekend Camping', 'Multi-Day Trek', 'Thru-Hike']
            },
            {
                title: 'Select Season',
                content: 'When will you be going?',
                options: ['Spring 🌸', 'Summer ☀️', 'Fall 🍂', 'Winter ❄️']
            },
            {
                title: 'Pack Weight Goal',
                content: 'What\'s your target base weight?',
                options: ['Ultralight (<10 lbs)', 'Lightweight (10-20 lbs)', 'Standard (20-30 lbs)', 'No Limit']
            }
        ];
        
        showWizard(steps, (answers) => {
            createPackFromWizard(answers);
        });
    }

    function showTour() {
        state.tourActive = true;
        
        const tour = [
            {
                element: '.hero-section-ultimate',
                title: 'Welcome to Pack Manager!',
                content: 'The most advanced backpack organizer for outdoor enthusiasts.',
                position: 'bottom'
            },
            {
                element: '.action-bar-ultimate',
                title: 'Command Center',
                content: 'Switch views, search your packs, and create new ones from here.',
                position: 'bottom'
            },
            {
                element: '.filter-bar-ultimate',
                title: 'Smart Filters',
                content: 'Filter by type, weight, season, and more to find the perfect pack.',
                position: 'bottom'
            },
            {
                element: '.sidebar-ultimate',
                title: 'Quick Access',
                content: 'Categories, tags, and quick actions at your fingertips.',
                position: 'right'
            }
        ];
        
        startTour(tour);
    }

    // View Management
    function toggleView(view) {
        state.currentView = view;
        
        // Update button states
        document.querySelectorAll('.btn-action[data-view]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        
        renderPacks();
    }

    // Filtering
    function filter(type, value) {
        if (type === 'type') {
            state.filters.type = value;
            
            // Update chip states
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.filter === value);
            });
        } else if (type === 'season') {
            state.filters.season = state.filters.season === value ? null : value;
            
            // Update season button states
            document.querySelectorAll('.season-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.season === state.filters.season);
            });
        }
        
        renderPacks();
    }

    function filterByWeight(weight) {
        state.filters.weight = parseInt(weight);
        document.getElementById('weight-value').textContent = `< ${weight}kg`;
        renderPacks();
    }

    function filterByTag(tag) {
        const index = state.filters.tags.indexOf(tag);
        if (index > -1) {
            state.filters.tags.splice(index, 1);
        } else {
            state.filters.tags.push(tag);
        }
        
        // Update tag states
        document.querySelectorAll('.tag-item').forEach(item => {
            const isActive = state.filters.tags.includes(item.textContent);
            item.style.background = isActive ? 'var(--forest-mint)' : '';
            item.style.color = isActive ? 'white' : '';
        });
        
        renderPacks();
    }

    function search(query) {
        state.filters.search = query;
        
        // Debounce search
        clearTimeout(state.searchTimeout);
        state.searchTimeout = setTimeout(() => {
            renderPacks();
        }, 300);
    }

    // Category Management
    function showCategory(category) {
        // Update active state
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.toggle('active', item.textContent.includes(category));
        });
        
        // Filter packs based on category
        if (category === 'favorites') {
            state.packs = state.packs.filter(p => p.favorite);
        } else if (category === 'recent') {
            state.packs = state.packs.sort((a, b) => 
                new Date(b.updated_at) - new Date(a.updated_at)
            ).slice(0, 5);
        } else if (category === 'shared') {
            state.packs = state.packs.filter(p => p.shared);
        }
        
        renderPacks();
    }

    // Pack Operations
    async function editPack(packId) {
        showPackBuilder(packId);
    }

    async function duplicatePack(packId) {
        const pack = state.packs.find(p => p.id === packId);
        if (!pack) return;
        
        const newPack = {
            ...pack,
            id: generateId(),
            name: pack.name + ' (Copy)',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        
        state.packs.push(newPack);
        renderPacks();
        showToast('Pack duplicated successfully!', 'success');
    }

    async function exportPack(packId) {
        const pack = state.packs.find(p => p.id === packId);
        if (!pack) return;
        
        const dataStr = JSON.stringify(pack, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(dataBlob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `${pack.name.replace(/\s+/g, '_')}_${Date.now()}.json`;
        link.click();
        
        URL.revokeObjectURL(url);
        showToast('Pack exported!', 'success');
    }

    // UI Helpers
    function updateHeroStats() {
        const totalPacks = state.packs.length;
        const totalWeight = state.packs.reduce((sum, p) => sum + (p.total_weight || 0), 0);
        const totalItems = state.packs.reduce((sum, p) => sum + (p.items_count || 0), 0);
        
        animateCounter('hero-packs', totalPacks);
        animateCounter('hero-weight', Math.round(totalWeight / 1000) + 'kg');
        animateCounter('hero-items', totalItems);
    }

    function animateCounter(elementId, target) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const duration = 1000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (typeof target === 'string') {
                element.textContent = target;
            } else {
                element.textContent = Math.round(current);
            }
        }, 16);
    }

    function animateHero() {
        // Add floating animation to badge
        const badge = document.querySelector('.hero-badge-float');
        if (badge) {
            badge.style.animation = 'bounce 2s ease-in-out infinite';
        }
    }

    function animateCards() {
        const cards = document.querySelectorAll('.pack-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.animation = 'fadeInUp 0.5s ease-out';
                card.style.opacity = '1';
            }, index * 50);
        });
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = `toast ${type} fade-in`;
        
        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
        
        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function showElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.remove('hidden');
    }

    function hideElement(id) {
        const element = document.getElementById(id);
        if (element) element.classList.add('hidden');
    }

    // Event Listeners
    function setupEventListeners() {
        // Window resize
        window.addEventListener('resize', handleResize);
        
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (state.detailPanelOpen) closeDetail();
                if (document.querySelector('.modal-ultimate:not(.hidden)')) closeBuilder();
            }
        });
    }

    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + N for new pack
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                createNew();
            }
            
            // Ctrl/Cmd + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('pack-search')?.focus();
            }
            
            // Number keys for views
            if (!e.ctrlKey && !e.metaKey && !e.altKey) {
                if (e.key === '1') toggleView('grid');
                if (e.key === '2') toggleView('list');
                if (e.key === '3') toggleView('kanban');
            }
        });
    }

    function handleResize() {
        // Adjust layout for mobile
        if (window.innerWidth < 768) {
            state.currentView = 'grid';
            renderPacks();
        }
    }

    // Utility Functions
    function setState(updates) {
        Object.assign(state, updates);
    }

    function generateId() {
        return 'pack_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'Never';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 86400000) return 'Today';
        if (diff < 172800000) return 'Yesterday';
        if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
        
        return date.toLocaleDateString();
    }

    function formatWeight(grams) {
        if (!grams) return '0g';
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }

    function calculatePackingProgress(pack) {
        if (!pack.sections) return 0;
        
        let totalItems = 0;
        let packedItems = 0;
        
        pack.sections.forEach(section => {
            if (section.items) {
                totalItems += section.items.length;
                packedItems += section.items.filter(i => i.packed).length;
            }
        });
        
        return totalItems > 0 ? Math.round((packedItems / totalItems) * 100) : 0;
    }

    function categorizePacks() {
        state.categories.all.count = state.packs.length;
        state.categories.favorites.count = state.packs.filter(p => p.favorite).length;
        state.categories.recent.count = Math.min(5, state.packs.length);
        state.categories.shared.count = state.packs.filter(p => p.shared).length;
        
        // Update UI counts
        updateCategoryCounts();
    }

    function updateCategoryCounts() {
        document.querySelectorAll('.category-count').forEach((element, index) => {
            const categories = ['all', 'favorites', 'recent', 'shared'];
            const category = categories[index];
            if (state.categories[category]) {
                element.textContent = state.categories[category].count;
            }
        });
    }

    // Mock Data Functions
    function getMockPacks() {
        return [
            {
                id: 'pack_1',
                name: 'Weekend Warrior',
                type: 'weekend',
                description: 'Perfect for 2-3 day adventures',
                total_weight: 8500,
                items_count: 42,
                trips_count: 5,
                sections_count: 8,
                favorite: true,
                tags: ['summer', 'lightweight'],
                created_at: '2024-01-15T10:00:00Z',
                updated_at: '2024-01-20T15:30:00Z',
                sections: getDefaultSections()
            },
            {
                id: 'pack_2',
                name: 'Day Hike Essential',
                type: 'day',
                total_weight: 3500,
                items_count: 18,
                trips_count: 12,
                sections_count: 5,
                tags: ['lightweight', 'family'],
                created_at: '2024-01-10T08:00:00Z',
                updated_at: '2024-01-25T12:00:00Z',
                sections: getDefaultSections().slice(0, 5)
            },
            {
                id: 'pack_3',
                name: 'Thru-Hiker Pro',
                type: 'thru',
                total_weight: 15000,
                items_count: 85,
                trips_count: 2,
                sections_count: 10,
                favorite: true,
                shared: true,
                tags: ['ultralight', 'photography'],
                created_at: '2023-12-01T09:00:00Z',
                updated_at: '2024-01-18T14:20:00Z',
                sections: getDefaultSections()
            }
        ];
    }

    function getDefaultSections() {
        return [
            { id: 's1', name: 'Shelter & Sleep', color: '#8B4513', items: [] },
            { id: 's2', name: 'Clothing', color: '#4A5568', items: [] },
            { id: 's3', name: 'Cooking & Food', color: '#D97706', items: [] },
            { id: 's4', name: 'Water & Hydration', color: '#3B82F6', items: [] },
            { id: 's5', name: 'Navigation', color: '#EF4444', items: [] },
            { id: 's6', name: 'First Aid', color: '#EC4899', items: [] },
            { id: 's7', name: 'Tools & Repair', color: '#10B981', items: [] },
            { id: 's8', name: 'Electronics', color: '#6B7280', items: [] }
        ];
    }

    function getMockGear() {
        return [
            { id: 'g1', name: 'Tent', weight: 1500, category: 'shelter' },
            { id: 'g2', name: 'Sleeping Bag', weight: 800, category: 'sleep' },
            { id: 'g3', name: 'Backpack', weight: 2000, category: 'pack' },
            { id: 'g4', name: 'Water Filter', weight: 75, category: 'water' },
            { id: 'g5', name: 'First Aid Kit', weight: 200, category: 'safety' }
        ];
    }

    function getMockTemplates() {
        return [
            {
                id: 't1',
                name: 'Day Hike Essentials',
                description: 'Everything you need for a day on the trail',
                type: 'day',
                sections: getDefaultSections().slice(0, 5)
            },
            {
                id: 't2',
                name: 'Weekend Adventure',
                description: '2-3 day camping setup',
                type: 'weekend',
                sections: getDefaultSections()
            },
            {
                id: 't3',
                name: 'Ultralight Setup',
                description: 'Minimal weight, maximum efficiency',
                type: 'ultra',
                sections: getDefaultSections()
            }
        ];
    }

    // Public API
    return {
        // Core functions
        init,
        createNew,
        quickStart,
        showTour,
        
        // View management
        toggleView,
        search,
        filter,
        filterByWeight,
        filterByTag,
        showCategory,
        
        // Pack operations
        editPack,
        duplicatePack,
        exportPack,
        sharePack: (id) => showToast('Sharing coming soon!', 'info'),
        
        // Builder functions
        openBuilder: showPackBuilder,
        closeBuilder,
        saveDraft: () => showToast('Draft saved!', 'success'),
        addSection: () => showToast('Section added!', 'success'),
        savePackFromBuilder: () => {
            closeBuilder();
            showToast('Pack saved successfully!', 'success');
            loadPacks().then(renderPacks);
        },
        
        // Detail panel
        closeDetail,
        
        // Template functions
        showTemplates: () => showToast('Templates opening...', 'info'),
        showImport: () => showToast('Import dialog opening...', 'info'),
        importFromFile: () => document.getElementById('import-file')?.click(),
        
        // Quick actions
        createFromLastTrip: () => showToast('Creating from last trip...', 'info'),
        duplicateFavorite: () => {
            const favorite = state.packs.find(p => p.favorite);
            if (favorite) duplicatePack(favorite.id);
            else showToast('No favorite pack found', 'error');
        },
        smartPack: () => showToast('AI Smart Pack coming soon!', 'info')
    };
})();

// Auto-initialize if not already done
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', PackManager.init);
} else {
    PackManager.init();
}
