/**
 * Ultimate Pack Manager - Optimized Version
 * Enhanced UX for packing process with performance improvements
 */

const PackManager = (function() {
    'use strict';

    // ==================== STATE MANAGEMENT ====================
    const state = {
        packs: [],
        currentPack: null,
        builderPack: null,
        currentStep: 1,
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
            all: { count: 0 },
            favorites: { count: 0 },
            recent: { count: 0 },
            shared: { count: 0 }
        },
        isLoading: false,
        detailPanelOpen: false,
        builderOpen: false,
        draggedItem: null,
        initialized: false,
        currentGearFilter: 'all',
        searchTerm: ''
    };

    // Performance: Use requestAnimationFrame for smooth animations
    const raf = window.requestAnimationFrame || (cb => setTimeout(cb, 16));
    
    // Debounce function for search and filters
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

    // ==================== INITIALIZATION ====================
    function init() {
        // Prevent multiple initializations
        if (state.initialized) return;
        state.initialized = true;
        
        console.log('🎒 Pack Manager Starting...');
        
        // Fast initial render with loading state
        showLoadingState();
        
        // Load data asynchronously
        raf(() => {
            loadInitialData().then(() => {
                setupEventListeners();
                setupDragAndDrop();
                setupKeyboardShortcuts();
                hideLoadingState();
                renderPacks();
                updateHeroStats();
                console.log('✅ Pack Manager Ready!');
            });
        });
    }

    // Immediate initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    async function loadInitialData() {
        try {
            // Load packs from localStorage or use mock data
            const savedPacks = localStorage.getItem('btt_packs');
            if (savedPacks) {
                state.packs = JSON.parse(savedPacks);
            } else {
                state.packs = getMockPacks();
                savePacks();
            }
            
            // Load gear inventory
            const savedGear = localStorage.getItem('btt_gear');
            if (savedGear) {
                state.gearInventory = JSON.parse(savedGear);
            } else {
                state.gearInventory = getMockGear();
                saveGear();
            }
            
            // Load templates
            state.templates = getMockTemplates();
            
            categorizePacks();
        } catch (error) {
            console.error('Error loading data:', error);
            showToast('Error loading data. Using defaults.', 'error');
        }
    }

    function showLoadingState() {
        const container = document.getElementById('pack-container');
        if (container) {
            container.innerHTML = `
                <div class="loading-state">
                    <div class="loading-animation">
                        <div class="backpack-loader">
                            <div class="pack-body"></div>
                            <div class="pack-pocket"></div>
                            <div class="pack-straps"></div>
                        </div>
                    </div>
                    <p class="loading-text">Loading your adventure gear...</p>
                </div>
            `;
        }
    }

    function hideLoadingState() {
        // Smooth transition out of loading state
        const loadingEl = document.querySelector('.loading-state');
        if (loadingEl) {
            loadingEl.style.opacity = '0';
            setTimeout(() => {
                loadingEl.style.display = 'none';
            }, 300);
        }
    }

    // ==================== ENHANCED PACK BUILDER ====================
    function createNew() {
        // Initialize new pack with smart defaults
        state.builderPack = {
            id: generateId(),
            name: '',
            type: 'weekend',
            description: '',
            capacity_l: 65,
            weight_empty_g: 1500,
            sections: getSmartSections(), // Smart sections based on type
            tags: [],
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };
        
        state.currentStep = 1;
        openBuilder();
    }

    function openBuilder(packId = null) {
        if (packId) {
            const pack = state.packs.find(p => p.id === packId);
            if (pack) {
                state.builderPack = JSON.parse(JSON.stringify(pack));
            }
        }
        
        state.builderOpen = true;
        state.currentStep = 1;
        
        const modal = document.getElementById('pack-builder-modal');
        if (modal) {
            modal.classList.remove('hidden');
            // Smooth entrance animation
            raf(() => {
                modal.classList.add('show');
            });
        }
        
        renderBuilderStep();
        updateBuilderProgress();
        updateBuilderStats();
        
        // Focus first input for better UX
        setTimeout(() => {
            const firstInput = document.querySelector('#pack-name');
            if (firstInput) firstInput.focus();
        }, 100);
    }

    function closeBuilder() {
        const hasChanges = state.builderPack && (state.builderPack.name || 
            (state.builderPack.sections && state.builderPack.sections.some(s => s.items && s.items.length > 0)));
        
        if (!hasChanges || confirm('You have unsaved changes. Are you sure you want to close?')) {
            const modal = document.getElementById('pack-builder-modal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    state.builderOpen = false;
                    state.builderPack = null;
                    state.currentStep = 1;
                }, 300);
            }
        }
    }

    // Enhanced rendering with better UX
    function renderBuilderStep() {
        const container = document.getElementById('builder-layout');
        if (!container) return;
        
        let content = '';
        
        switch (state.currentStep) {
            case 1:
                content = renderEnhancedDetailsStep();
                break;
            case 2:
                content = renderEnhancedItemsStep(); // Combined sections + items for better UX
                break;
            case 3:
                content = renderEnhancedReviewStep();
                break;
        }
        
        // Smooth transition
        container.style.opacity = '0';
        setTimeout(() => {
            container.innerHTML = `<div class="builder-step-content">${content}</div>`;
            container.style.opacity = '1';
            setupStepEventListeners();
        }, 150);
    }

    function renderEnhancedDetailsStep() {
        const pack = state.builderPack;
        
        return `
            <div class="enhanced-details">
                <div class="quick-setup-bar">
                    <h3>Quick Setup Templates</h3>
                    <div class="template-cards">
                        <div class="template-card" onclick="PackManager.applyTemplate('day')">
                            <span class="template-icon">☀️</span>
                            <span class="template-name">Day Hike</span>
                            <span class="template-info">5-10kg</span>
                        </div>
                        <div class="template-card" onclick="PackManager.applyTemplate('weekend')">
                            <span class="template-icon">🏕️</span>
                            <span class="template-name">Weekend</span>
                            <span class="template-info">10-15kg</span>
                        </div>
                        <div class="template-card" onclick="PackManager.applyTemplate('multi')">
                            <span class="template-icon">🏔️</span>
                            <span class="template-name">Multi-Day</span>
                            <span class="template-info">15-20kg</span>
                        </div>
                        <div class="template-card" onclick="PackManager.applyTemplate('ultra')">
                            <span class="template-icon">🪶</span>
                            <span class="template-name">Ultralight</span>
                            <span class="template-info"><10kg</span>
                        </div>
                    </div>
                </div>

                <div class="details-main-form">
                    <div class="form-section">
                        <div class="form-group large">
                            <label class="form-label required">What should we call this pack?</label>
                            <input type="text" 
                                   id="pack-name" 
                                   class="form-control form-control-large" 
                                   value="${pack.name || ''}" 
                                   placeholder="e.g., Weekend Warrior, Summer Adventures"
                                   onchange="PackManager.updatePackDetail('name', this.value)"
                                   onkeyup="PackManager.validatePackName(this.value)">
                            <div class="form-hint">Give your pack a memorable name</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Trip Type</label>
                                <select id="pack-type" 
                                        class="form-control"
                                        onchange="PackManager.updatePackType(this.value)">
                                    <option value="day" ${pack.type === 'day' ? 'selected' : ''}>Day Hike</option>
                                    <option value="weekend" ${pack.type === 'weekend' ? 'selected' : ''}>Weekend Trip</option>
                                    <option value="multi" ${pack.type === 'multi' ? 'selected' : ''}>Multi-Day</option>
                                    <option value="thru" ${pack.type === 'thru' ? 'selected' : ''}>Thru-Hike</option>
                                    <option value="ultra" ${pack.type === 'ultra' ? 'selected' : ''}>Ultralight</option>
                                    <option value="custom" ${pack.type === 'custom' ? 'selected' : ''}>Custom</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Season</label>
                                <div class="season-selector-large">
                                    <button class="season-btn-large ${pack.season === 'spring' ? 'active' : ''}" 
                                            onclick="PackManager.updatePackDetail('season', 'spring')">
                                        <span>🌸</span>
                                        <small>Spring</small>
                                    </button>
                                    <button class="season-btn-large ${pack.season === 'summer' ? 'active' : ''}" 
                                            onclick="PackManager.updatePackDetail('season', 'summer')">
                                        <span>☀️</span>
                                        <small>Summer</small>
                                    </button>
                                    <button class="season-btn-large ${pack.season === 'fall' ? 'active' : ''}" 
                                            onclick="PackManager.updatePackDetail('season', 'fall')">
                                        <span>🍂</span>
                                        <small>Fall</small>
                                    </button>
                                    <button class="season-btn-large ${pack.season === 'winter' ? 'active' : ''}" 
                                            onclick="PackManager.updatePackDetail('season', 'winter')">
                                        <span>❄️</span>
                                        <small>Winter</small>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pack Size & Weight</label>
                            <div class="slider-group">
                                <div class="slider-item">
                                    <div class="slider-header">
                                        <span>Capacity</span>
                                        <span class="slider-value">${pack.capacity_l}L</span>
                                    </div>
                                    <input type="range" 
                                           class="form-slider" 
                                           min="20" max="120" 
                                           value="${pack.capacity_l}"
                                           oninput="PackManager.updateSlider('capacity_l', this.value)">
                                </div>
                                <div class="slider-item">
                                    <div class="slider-header">
                                        <span>Empty Weight</span>
                                        <span class="slider-value">${formatWeight(pack.weight_empty_g)}</span>
                                    </div>
                                    <input type="range" 
                                           class="form-slider" 
                                           min="500" max="3000" step="100"
                                           value="${pack.weight_empty_g}"
                                           oninput="PackManager.updateSlider('weight_empty_g', this.value)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderEnhancedItemsStep() {
        const pack = state.builderPack;
        const sections = pack.sections || [];
        
        return `
            <div class="enhanced-items-manager">
                <!-- Left: Gear Library -->
                <div class="gear-library">
                    <div class="library-header">
                        <h3>Gear Library</h3>
                        <button class="btn-add-custom" onclick="PackManager.showAddGearModal()">
                            + Add Custom
                        </button>
                    </div>
                    
                    <div class="gear-search">
                        <input type="search" 
                               placeholder="Search gear..." 
                               class="search-input"
                               onkeyup="PackManager.searchGear(this.value)">
                    </div>
                    
                    <div class="gear-filter-tabs">
                        <button class="filter-tab ${state.currentGearFilter === 'all' ? 'active' : ''}" 
                                onclick="PackManager.filterGearByCategory('all')">All</button>
                        <button class="filter-tab ${state.currentGearFilter === 'essentials' ? 'active' : ''}" 
                                onclick="PackManager.filterGearByCategory('essentials')">Essentials</button>
                        <button class="filter-tab ${state.currentGearFilter === 'shelter' ? 'active' : ''}" 
                                onclick="PackManager.filterGearByCategory('shelter')">Shelter</button>
                        <button class="filter-tab ${state.currentGearFilter === 'clothing' ? 'active' : ''}" 
                                onclick="PackManager.filterGearByCategory('clothing')">Clothing</button>
                        <button class="filter-tab ${state.currentGearFilter === 'cooking' ? 'active' : ''}" 
                                onclick="PackManager.filterGearByCategory('cooking')">Cooking</button>
                    </div>
                    
                    <div class="gear-grid" id="gear-grid">
                        ${renderGearGrid()}
                    </div>
                </div>

                <!-- Center: Pack Organization -->
                <div class="pack-organizer">
                    <div class="organizer-header">
                        <h3>Pack Contents</h3>
                        <div class="pack-quick-stats">
                            <span class="stat-pill">
                                <span class="stat-icon">⚖️</span>
                                <span id="quick-weight">${calculateQuickWeight(pack)}</span>
                            </span>
                            <span class="stat-pill">
                                <span class="stat-icon">📦</span>
                                <span id="quick-items">${calculateQuickItems(pack)} items</span>
                            </span>
                        </div>
                    </div>

                    <div class="sections-container">
                        ${sections.map(section => renderEnhancedSection(section)).join('')}
                        
                        <button class="btn-add-section-inline" onclick="PackManager.addSection()">
                            <span class="plus-icon">+</span>
                            <span>Add New Section</span>
                        </button>
                    </div>
                </div>

                <!-- Right: Quick Tools -->
                <div class="quick-tools">
                    <div class="tool-section">
                        <h4>Essential Items</h4>
                        <div class="essential-items">
                            ${renderEssentialItems()}
                        </div>
                    </div>
                    
                    <div class="tool-section">
                        <h4>Weight Distribution</h4>
                        <div class="weight-chart">
                            ${renderWeightChart(pack)}
                        </div>
                    </div>
                    
                    <div class="tool-section">
                        <h4>Packing Tips</h4>
                        <div class="tips-box">
                            <div class="tip">💡 Heavy items go near your back</div>
                            <div class="tip">💡 Frequently used items on top</div>
                            <div class="tip">💡 Balance weight left to right</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderGearGrid() {
        const filteredGear = getFilteredGear();
        
        if (filteredGear.length === 0) {
            return '<div class="empty-gear">No gear found</div>';
        }
        
        return filteredGear.map(gear => `
            <div class="gear-card" 
                 draggable="true" 
                 data-gear-id="${gear.id}"
                 ondragstart="PackManager.handleDragStart(event, 'gear', '${gear.id}')"
                 onclick="PackManager.quickAddGear('${gear.id}')">
                <div class="gear-icon">${gear.icon || '🎒'}</div>
                <div class="gear-name">${gear.name}</div>
                <div class="gear-weight">${formatWeight(gear.weight_g)}</div>
            </div>
        `).join('');
    }

    function renderEnhancedSection(section) {
        const sectionWeight = calculateSectionWeight(section);
        const itemCount = section.items ? section.items.length : 0;
        
        return `
            <div class="pack-section-enhanced" data-section-id="${section.id}">
                <div class="section-header-enhanced">
                    <div class="section-color-dot" style="background: ${section.color}"></div>
                    <input type="text" 
                           class="section-name-inline" 
                           value="${section.name}" 
                           placeholder="Section name..."
                           onchange="PackManager.updateSectionName('${section.id}', this.value)">
                    <div class="section-stats">
                        <span class="section-weight">${formatWeight(sectionWeight)}</span>
                        <span class="section-count">${itemCount} items</span>
                    </div>
                    <div class="section-actions">
                        <button class="btn-icon-small" onclick="PackManager.toggleSectionCollapse('${section.id}')">
                            <span>▼</span>
                        </button>
                        <button class="btn-icon-small" onclick="PackManager.deleteSection('${section.id}')">
                            <span>×</span>
                        </button>
                    </div>
                </div>
                
                <div class="section-items-area" 
                     ondrop="PackManager.handleDrop(event, '${section.id}')"
                     ondragover="PackManager.handleDragOver(event)"
                     ondragleave="PackManager.handleDragLeave(event)">
                    ${section.items && section.items.length > 0 ? 
                        section.items.map(item => renderPackItem(item, section.id)).join('') :
                        '<div class="drop-zone-hint">Drop items here or click gear to add</div>'
                    }
                </div>
            </div>
        `;
    }

    function renderPackItem(item, sectionId) {
        return `
            <div class="pack-item-enhanced" data-item-id="${item.id}">
                <input type="checkbox" 
                       class="item-checkbox" 
                       ${item.packed ? 'checked' : ''}
                       onchange="PackManager.toggleItemPacked('${sectionId}', '${item.id}')">
                <span class="item-name">${item.name}</span>
                <span class="item-weight">${formatWeight(item.weight_g)}</span>
                <button class="btn-remove-item" onclick="PackManager.removeItemFromSection('${sectionId}', '${item.id}')">×</button>
            </div>
        `;
    }

    function renderEssentialItems() {
        const essentials = [
            { name: 'First Aid Kit', icon: '🏥', weight: 200 },
            { name: 'Water (2L)', icon: '💧', weight: 2000 },
            { name: 'Map & Compass', icon: '🗺️', weight: 150 },
            { name: 'Headlamp', icon: '🔦', weight: 75 },
            { name: 'Emergency Whistle', icon: '📯', weight: 20 },
            { name: 'Knife/Multi-tool', icon: '🔪', weight: 100 }
        ];
        
        return essentials.map(item => `
            <button class="essential-item-btn" 
                    onclick="PackManager.addEssentialItem('${item.name}', ${item.weight})">
                <span class="essential-icon">${item.icon}</span>
                <span class="essential-name">${item.name}</span>
            </button>
        `).join('');
    }

    function renderWeightChart(pack) {
        const sections = pack.sections || [];
        const total = sections.reduce((sum, s) => sum + calculateSectionWeight(s), 0) + (pack.weight_empty_g || 0);
        
        if (total === 0) return '<div class="no-weight">No items yet</div>';
        
        return sections.map(section => {
            const weight = calculateSectionWeight(section);
            const percentage = (weight / total * 100).toFixed(1);
            
            return `
                <div class="weight-bar-item">
                    <div class="weight-bar-label">
                        <span>${section.name}</span>
                        <span>${percentage}%</span>
                    </div>
                    <div class="weight-bar-track">
                        <div class="weight-bar-fill" 
                             style="width: ${percentage}%; background: ${section.color}"></div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderEnhancedReviewStep() {
        const pack = state.builderPack;
        const stats = calculatePackStats(pack);
        
        return `
            <div class="enhanced-review">
                <div class="review-header">
                    <h2>✨ Your Pack is Ready!</h2>
                    <p>Review your configuration and make any final adjustments</p>
                </div>

                <div class="review-grid">
                    <div class="review-card">
                        <h3>📊 Pack Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-big">${stats.totalWeight}</span>
                                <span class="stat-label">Total Weight</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-big">${stats.totalItems}</span>
                                <span class="stat-label">Total Items</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-big">${stats.sections}</span>
                                <span class="stat-label">Sections</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-big">${calculatePackingProgress(pack)}%</span>
                                <span class="stat-label">Packed</span>
                            </div>
                        </div>
                    </div>

                    <div class="review-card">
                        <h3>🎒 Pack Details</h3>
                        <div class="detail-list">
                            <div class="detail-item">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value">${pack.name || 'Unnamed Pack'}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Type:</span>
                                <span class="detail-value">${pack.type}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Capacity:</span>
                                <span class="detail-value">${pack.capacity_l}L</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Season:</span>
                                <span class="detail-value">${pack.season || 'All Seasons'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="review-card full-width">
                        <h3>📦 Packing List</h3>
                        <div class="packing-list">
                            ${pack.sections.map(section => `
                                <div class="packing-section">
                                    <div class="packing-section-header">
                                        <span class="color-indicator" style="background: ${section.color}"></span>
                                        <span class="section-title">${section.name}</span>
                                        <span class="section-meta">${section.items?.length || 0} items • ${formatWeight(calculateSectionWeight(section))}</span>
                                    </div>
                                    ${section.items && section.items.length > 0 ? `
                                        <div class="packing-items">
                                            ${section.items.map(item => `
                                                <div class="packing-item">
                                                    <span class="check-status">${item.packed ? '✅' : '⬜'}</span>
                                                    <span class="item-text">${item.name}</span>
                                                    <span class="item-weight-small">${formatWeight(item.weight_g)}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : '<div class="no-items">No items in this section</div>'}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                <div class="save-options">
                    <label class="save-option">
                        <input type="checkbox" id="mark-favorite">
                        <span>⭐ Mark as favorite</span>
                    </label>
                    <label class="save-option">
                        <input type="checkbox" id="create-template">
                        <span>📋 Save as template for future use</span>
                    </label>
                </div>
            </div>
        `;
    }

    // ==================== IMPROVED INTERACTIONS ====================
    
    // Quick add gear by clicking (no drag needed)
    function quickAddGear(gearId) {
        if (!state.builderPack || !state.builderPack.sections.length) {
            showToast('Please add a section first', 'warning');
            return;
        }
        
        const gear = state.gearInventory.find(g => g.id === gearId);
        if (!gear) return;
        
        // Add to first section or last used section
        const targetSection = state.builderPack.sections[0];
        
        // Check if already exists
        if (targetSection.items && targetSection.items.find(i => i.gear_id === gearId)) {
            showToast('Item already in pack', 'info');
            return;
        }
        
        const item = {
            id: generateId(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g,
            category: gear.category,
            packed: false
        };
        
        targetSection.items = targetSection.items || [];
        targetSection.items.push(item);
        
        // Animate addition
        showQuickAddAnimation(gear.name);
        renderBuilderStep();
        updateBuilderStats();
    }

    function showQuickAddAnimation(itemName) {
        const toast = document.createElement('div');
        toast.className = 'quick-add-toast';
        toast.textContent = `+ ${itemName}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 1000);
    }

    function addEssentialItem(name, weight) {
        if (!state.builderPack || !state.builderPack.sections.length) {
            // Auto-create a section if none exist
            const section = {
                id: generateId(),
                name: 'Essentials',
                color: '#ef4444',
                items: []
            };
            state.builderPack.sections = [section];
        }
        
        const item = {
            id: generateId(),
            name: name,
            weight_g: weight,
            category: 'essentials',
            packed: false
        };
        
        state.builderPack.sections[0].items = state.builderPack.sections[0].items || [];
        state.builderPack.sections[0].items.push(item);
        
        renderBuilderStep();
        updateBuilderStats();
        showToast(`Added ${name}`, 'success');
    }

    // Search with debouncing for performance
    const searchGear = debounce(function(query) {
        state.searchTerm = query.toLowerCase();
        document.getElementById('gear-grid').innerHTML = renderGearGrid();
    }, 300);

    function getFilteredGear() {
        let filtered = state.gearInventory;
        
        // Apply category filter
        if (state.currentGearFilter !== 'all') {
            filtered = filtered.filter(g => g.category === state.currentGearFilter);
        }
        
        // Apply search filter
        if (state.searchTerm) {
            filtered = filtered.filter(g => 
                g.name.toLowerCase().includes(state.searchTerm) ||
                (g.category && g.category.toLowerCase().includes(state.searchTerm))
            );
        }
        
        return filtered;
    }

    function filterGearByCategory(category) {
        state.currentGearFilter = category;
        
        // Update UI
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Re-render gear grid
        document.getElementById('gear-grid').innerHTML = renderGearGrid();
    }

    // ==================== PERFORMANCE OPTIMIZATIONS ====================
    
    function updateBuilderStats() {
        if (!state.builderPack) return;
        
        raf(() => {
            const stats = calculatePackStats(state.builderPack);
            
            // Update all stat displays
            const elements = {
                weight: document.getElementById('builder-total-weight'),
                items: document.getElementById('builder-total-items'),
                quickWeight: document.getElementById('quick-weight'),
                quickItems: document.getElementById('quick-items')
            };
            
            if (elements.weight) elements.weight.textContent = stats.totalWeight;
            if (elements.items) elements.items.textContent = stats.totalItems;
            if (elements.quickWeight) elements.quickWeight.textContent = stats.totalWeight;
            if (elements.quickItems) elements.quickItems.textContent = stats.totalItems + ' items';
        });
    }

    function nextStep() {
        if (state.currentStep < 3) {
            // Validate current step
            if (state.currentStep === 1 && !state.builderPack.name) {
                const input = document.getElementById('pack-name');
                if (input) {
                    input.classList.add('error');
                    input.focus();
                }
                showToast('Please enter a pack name', 'error');
                return;
            }
            
            state.currentStep++;
            renderBuilderStep();
            updateBuilderProgress();
        }
    }

    function previousStep() {
        if (state.currentStep > 1) {
            state.currentStep--;
            renderBuilderStep();
            updateBuilderProgress();
        }
    }

    function updateBuilderProgress() {
        const steps = [
            { num: 1, label: 'Details' },
            { num: 2, label: 'Pack Items' },
            { num: 3, label: 'Review & Save' }
        ];
        
        // Update progress bar
        const progressBar = document.querySelector('.builder-progress-bar');
        if (progressBar) {
            progressBar.innerHTML = steps.map(step => `
                <div class="progress-step ${step.num === state.currentStep ? 'active' : ''} ${step.num < state.currentStep ? 'completed' : ''}">
                    <span class="step-number">${step.num}</span>
                    <span class="step-label">${step.label}</span>
                </div>
            `).join('');
        }
        
        // Update buttons
        const prevBtn = document.getElementById('btn-prev');
        const nextBtn = document.getElementById('btn-next');
        const saveBtn = document.getElementById('btn-save');
        
        if (prevBtn) prevBtn.disabled = state.currentStep === 1;
        
        if (state.currentStep === 3) {
            if (nextBtn) nextBtn.classList.add('hidden');
            if (saveBtn) saveBtn.classList.remove('hidden');
        } else {
            if (nextBtn) nextBtn.classList.remove('hidden');
            if (saveBtn) saveBtn.classList.add('hidden');
        }
    }

    // ==================== KEYBOARD SHORTCUTS ====================
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (!state.builderOpen) return;
            
            // Escape to close
            if (e.key === 'Escape') {
                closeBuilder();
            }
            
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (state.currentStep === 3) {
                    savePackFromBuilder();
                }
            }
            
            // Arrow keys for navigation (when not in input)
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                if (e.key === 'ArrowRight' && state.currentStep < 3) {
                    nextStep();
                } else if (e.key === 'ArrowLeft' && state.currentStep > 1) {
                    previousStep();
                }
            }
        });
    }

    // ==================== SMART DEFAULTS ====================
    function getSmartSections() {
        // Return smart default sections based on pack type
        return [
            { id: generateId(), name: 'Essentials', color: '#ef4444', items: [] },
            { id: generateId(), name: 'Shelter & Sleep', color: '#8B4513', items: [] },
            { id: generateId(), name: 'Clothing', color: '#4A5568', items: [] },
            { id: generateId(), name: 'Food & Water', color: '#3B82F6', items: [] },
            { id: generateId(), name: 'Tools & Misc', color: '#10B981', items: [] }
        ];
    }

    function applyTemplate(type) {
        state.builderPack.type = type;
        
        // Apply smart defaults based on type
        const templates = {
            day: { capacity_l: 30, weight_empty_g: 800 },
            weekend: { capacity_l: 50, weight_empty_g: 1200 },
            multi: { capacity_l: 65, weight_empty_g: 1500 },
            ultra: { capacity_l: 40, weight_empty_g: 600 }
        };
        
        if (templates[type]) {
            Object.assign(state.builderPack, templates[type]);
        }
        
        // Update UI
        document.querySelectorAll('.template-card').forEach(card => {
            card.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
        
        renderBuilderStep();
        showToast(`Applied ${type} template`, 'success');
    }

    function updatePackType(type) {
        state.builderPack.type = type;
        
        // Suggest capacity based on type
        const suggestions = {
            day: 30,
            weekend: 50,
            multi: 65,
            thru: 55,
            ultra: 40,
            custom: 65
        };
        
        if (suggestions[type]) {
            state.builderPack.capacity_l = suggestions[type];
            renderBuilderStep();
        }
    }

    function updateSlider(field, value) {
        state.builderPack[field] = parseInt(value);
        
        // Update display
        const displayEl = event.target.parentElement.querySelector('.slider-value');
        if (displayEl) {
            displayEl.textContent = field === 'capacity_l' ? `${value}L` : formatWeight(value);
        }
        
        updateBuilderStats();
    }

    function validatePackName(value) {
        const input = document.getElementById('pack-name');
        if (input) {
            if (value.length > 0) {
                input.classList.remove('error');
                input.classList.add('valid');
            } else {
                input.classList.remove('valid');
            }
        }
    }

    // ==================== CALCULATE FUNCTIONS ====================
    function calculateQuickWeight(pack) {
        const total = (pack.weight_empty_g || 0) + 
            (pack.sections || []).reduce((sum, s) => 
                sum + (s.items || []).reduce((iSum, i) => iSum + (i.weight_g || 0), 0), 0);
        return formatWeight(total);
    }

    function calculateQuickItems(pack) {
        return (pack.sections || []).reduce((sum, s) => sum + (s.items?.length || 0), 0);
    }

    function calculatePackStats(pack) {
        let totalWeight = pack.weight_empty_g || 0;
        let totalItems = 0;
        let packedItems = 0;
        
        if (pack.sections) {
            pack.sections.forEach(section => {
                if (section.items) {
                    totalItems += section.items.length;
                    section.items.forEach(item => {
                        totalWeight += item.weight_g || 0;
                        if (item.packed) packedItems++;
                    });
                }
            });
        }
        
        return {
            totalWeight: formatWeight(totalWeight),
            totalItems: totalItems,
            packedItems: packedItems,
            sections: pack.sections ? pack.sections.length : 0
        };
    }

    function calculateSectionWeight(section) {
        if (!section.items) return 0;
        return section.items.reduce((sum, item) => sum + (item.weight_g || 0), 0);
    }

    function calculatePackingProgress(pack) {
        let totalItems = 0;
        let packedItems = 0;
        
        if (pack.sections) {
            pack.sections.forEach(section => {
                if (section.items) {
                    totalItems += section.items.length;
                    packedItems += section.items.filter(i => i.packed).length;
                }
            });
        }
        
        return totalItems > 0 ? Math.round((packedItems / totalItems) * 100) : 0;
    }

    // ==================== UTILITY FUNCTIONS ====================
    function formatWeight(grams) {
        if (!grams) return '0g';
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }

    function generateId() {
        return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function getRandomColor() {
        const colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#6b7280'];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast ${type} slide-in`;
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        toast.innerHTML = `
            <span class="toast-icon">${icons[type]}</span>
            <span class="toast-message">${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('slide-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
        document.body.appendChild(container);
        return container;
    }

    // ==================== ENHANCED DRAG AND DROP ====================
    function handleDragStart(event, type, id) {
        state.draggedItem = { type, id };
        event.dataTransfer.effectAllowed = 'copy';
        event.currentTarget.classList.add('dragging');
    }

    function handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        event.currentTarget.classList.add('drag-over');
    }

    function handleDragLeave(event) {
        event.currentTarget.classList.remove('drag-over');
    }

    function handleDrop(event, sectionId) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');
        
        if (!state.draggedItem || !state.builderPack) return;
        
        const section = state.builderPack.sections.find(s => s.id === sectionId);
        if (!section) return;
        
        const gear = state.gearInventory.find(g => g.id === state.draggedItem.id);
        if (!gear) return;
        
        section.items = section.items || [];
        if (section.items.find(i => i.gear_id === gear.id)) {
            showToast('Item already in this section', 'warning');
            return;
        }
        
        const item = {
            id: generateId(),
            gear_id: gear.id,
            name: gear.name,
            weight_g: gear.weight_g,
            category: gear.category,
            packed: false
        };
        
        section.items.push(item);
        renderBuilderStep();
        updateBuilderStats();
        showToast(`Added ${gear.name} to ${section.name}`, 'success');
        
        // Clean up
        document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
        state.draggedItem = null;
    }

    // ==================== MOCK DATA ====================
    function getMockPacks() {
        return [
            {
                id: 'pack_demo_1',
                name: 'Weekend Warrior',
                type: 'weekend',
                description: 'Perfect for 2-3 day adventures',
                capacity_l: 65,
                weight_empty_g: 1800,
                season: 'summer',
                tags: ['lightweight', 'summer'],
                favorite: true,
                sections: [
                    {
                        id: 's1',
                        name: 'Shelter & Sleep',
                        color: '#8B4513',
                        items: [
                            { id: 'i1', name: 'Tent', weight_g: 1500, category: 'shelter', packed: true },
                            { id: 'i2', name: 'Sleeping Bag', weight_g: 800, category: 'sleep', packed: true },
                            { id: 'i3', name: 'Sleeping Pad', weight_g: 400, category: 'sleep', packed: false }
                        ]
                    }
                ],
                created_at: '2024-01-15T10:00:00Z',
                updated_at: '2024-01-20T15:30:00Z'
            }
        ];
    }

    function getMockGear() {
        return [
            // Essentials
            { id: 'g1', name: 'First Aid Kit', weight_g: 200, category: 'essentials', icon: '🏥' },
            { id: 'g2', name: 'Map & Compass', weight_g: 150, category: 'essentials', icon: '🗺️' },
            { id: 'g3', name: 'Headlamp', weight_g: 75, category: 'essentials', icon: '🔦' },
            { id: 'g4', name: 'Knife', weight_g: 100, category: 'essentials', icon: '🔪' },
            { id: 'g5', name: 'Emergency Whistle', weight_g: 20, category: 'essentials', icon: '📯' },
            
            // Shelter
            { id: 'g6', name: 'Ultralight Tent', weight_g: 1200, category: 'shelter', icon: '⛺' },
            { id: 'g7', name: 'Tarp', weight_g: 400, category: 'shelter', icon: '🏕️' },
            { id: 'g8', name: 'Sleeping Bag', weight_g: 650, category: 'shelter', icon: '🛏️' },
            { id: 'g9', name: 'Sleeping Pad', weight_g: 350, category: 'shelter', icon: '🟦' },
            { id: 'g10', name: 'Pillow', weight_g: 50, category: 'shelter', icon: '🟩' },
            
            // Cooking
            { id: 'g11', name: 'Stove', weight_g: 75, category: 'cooking', icon: '🔥' },
            { id: 'g12', name: 'Pot', weight_g: 100, category: 'cooking', icon: '🍲' },
            { id: 'g13', name: 'Spork', weight_g: 20, category: 'cooking', icon: '🥄' },
            { id: 'g14', name: 'Water Filter', weight_g: 60, category: 'cooking', icon: '💧' },
            { id: 'g15', name: 'Water Bottle', weight_g: 100, category: 'cooking', icon: '🍶' },
            
            // Clothing
            { id: 'g16', name: 'Rain Jacket', weight_g: 250, category: 'clothing', icon: '🧥' },
            { id: 'g17', name: 'Puffy Jacket', weight_g: 300, category: 'clothing', icon: '🧤' },
            { id: 'g18', name: 'Base Layer', weight_g: 150, category: 'clothing', icon: '👕' },
            { id: 'g19', name: 'Hiking Pants', weight_g: 200, category: 'clothing', icon: '👖' },
            { id: 'g20', name: 'Hat', weight_g: 50, category: 'clothing', icon: '🧢' }
        ];
    }

    function getMockTemplates() {
        return [
            {
                id: 't1',
                name: 'Day Hike Essentials',
                description: 'Everything you need for a day on the trail',
                type: 'day',
                sections: getSmartSections().slice(0, 3)
            }
        ];
    }

    // ==================== OTHER FUNCTIONS ====================
    function savePackFromBuilder() {
        const pack = state.builderPack;
        
        if (!pack.name) {
            showToast('Please enter a pack name', 'error');
            return;
        }
        
        // Check for save options
        const favoriteCheck = document.getElementById('mark-favorite');
        if (favoriteCheck && favoriteCheck.checked) {
            pack.favorite = true;
        }
        
        // Update timestamps
        pack.updated_at = new Date().toISOString();
        
        // Save pack
        const existingIndex = state.packs.findIndex(p => p.id === pack.id);
        if (existingIndex >= 0) {
            state.packs[existingIndex] = pack;
            showToast('Pack updated successfully!', 'success');
        } else {
            state.packs.push(pack);
            showToast('Pack created successfully!', 'success');
        }
        
        // Save to localStorage
        savePacks();
        
        // Close builder
        closeBuilder();
        
        // Refresh view
        renderPacks();
        updateHeroStats();
    }

    function savePacks() {
        localStorage.setItem('btt_packs', JSON.stringify(state.packs));
    }

    function saveGear() {
        localStorage.setItem('btt_gear', JSON.stringify(state.gearInventory));
    }

    function renderPacks() {
        const container = document.getElementById('pack-container');
        if (!container) return;
        
        const grid = document.getElementById('pack-grid') || createPackGrid();
        
        if (state.packs.length === 0) {
            container.innerHTML = `
                <div class="empty-state-ultimate">
                    <h2 class="empty-title">Start Your Packing Journey</h2>
                    <p class="empty-description">Create your first backpack and organize your gear like a pro</p>
                    <button class="btn-primary-large" onclick="PackManager.createNew()">
                        <span>🎒</span> Create First Pack
                    </button>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = state.packs.map(pack => createPackCard(pack)).join('');
        container.innerHTML = '';
        container.appendChild(grid);
    }

    function createPackGrid() {
        const grid = document.createElement('div');
        grid.id = 'pack-grid';
        grid.className = 'pack-grid';
        return grid;
    }

    function createPackCard(pack) {
        const stats = calculatePackStats(pack);
        const progress = calculatePackingProgress(pack);
        
        return `
            <div class="pack-card fade-in" data-pack-id="${pack.id}">
                <div class="pack-card-header">
                    <div class="pack-card-title">
                        ${pack.name}
                        ${pack.favorite ? '<span class="pack-favorite">⭐</span>' : ''}
                    </div>
                    <div class="pack-card-meta">
                        <span>${pack.type}</span> • <span>${formatDate(pack.updated_at)}</span>
                    </div>
                </div>
                <div class="pack-card-body">
                    <div class="pack-stats-grid">
                        <div class="pack-stat">
                            <span class="pack-stat-icon">⚖️</span>
                            <span class="pack-stat-value">${stats.totalWeight}</span>
                        </div>
                        <div class="pack-stat">
                            <span class="pack-stat-icon">📦</span>
                            <span class="pack-stat-value">${stats.totalItems} items</span>
                        </div>
                    </div>
                    <div class="pack-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progress}%"></div>
                        </div>
                        <span class="progress-text">${progress}% packed</span>
                    </div>
                </div>
                <div class="pack-card-footer">
                    <button class="btn-card-action" onclick="PackManager.editPack('${pack.id}')">Edit</button>
                    <button class="btn-card-action" onclick="PackManager.duplicatePack('${pack.id}')">Duplicate</button>
                </div>
            </div>
        `;
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

    function updateHeroStats() {
        const totalPacks = state.packs.length;
        let totalWeight = 0;
        let totalItems = 0;
        
        state.packs.forEach(pack => {
            const stats = calculatePackStats(pack);
            totalItems += stats.totalItems;
            const weight = parseInt(stats.totalWeight) || 0;
            totalWeight += weight;
        });
        
        const elements = {
            packs: document.getElementById('hero-packs'),
            weight: document.getElementById('hero-weight'),
            items: document.getElementById('hero-items')
        };
        
        if (elements.packs) elements.packs.textContent = totalPacks;
        if (elements.weight) elements.weight.textContent = formatWeight(totalWeight);
        if (elements.items) elements.items.textContent = totalItems;
    }

    function categorizePacks() {
        state.categories.all.count = state.packs.length;
        state.categories.favorites.count = state.packs.filter(p => p.favorite).length;
        state.categories.recent.count = Math.min(5, state.packs.length);
        state.categories.shared.count = state.packs.filter(p => p.shared).length;
    }

    function setupEventListeners() {
        // Modal overlay click to close
        const overlay = document.querySelector('.modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeBuilder);
        }
    }

    function setupDragAndDrop() {
        // Set up global drag and drop listeners
        document.addEventListener('dragend', () => {
            document.querySelectorAll('.dragging').forEach(el => el.classList.remove('dragging'));
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        });
    }

    // ==================== PUBLIC API ====================
    return {
        // Core
        init,
        createNew,
        openBuilder,
        closeBuilder,
        
        // Navigation
        nextStep,
        previousStep,
        savePackFromBuilder,
        
        // Pack details
        updatePackDetail: (field, value) => {
            if (state.builderPack) {
                state.builderPack[field] = value;
                updateBuilderStats();
            }
        },
        
        updatePackType,
        updateSlider,
        validatePackName,
        applyTemplate,
        
        // Sections
        addSection: () => {
            if (state.builderPack) {
                const section = {
                    id: generateId(),
                    name: '',
                    color: getRandomColor(),
                    items: []
                };
                state.builderPack.sections.push(section);
                renderBuilderStep();
            }
        },
        
        updateSectionName: (sectionId, name) => {
            if (state.builderPack) {
                const section = state.builderPack.sections.find(s => s.id === sectionId);
                if (section) section.name = name;
            }
        },
        
        deleteSection: (sectionId) => {
            if (state.builderPack && confirm('Delete this section?')) {
                state.builderPack.sections = state.builderPack.sections.filter(s => s.id !== sectionId);
                renderBuilderStep();
                updateBuilderStats();
            }
        },
        
        // Items
        quickAddGear,
        addEssentialItem,
        searchGear,
        filterGearByCategory,
        
        removeItemFromSection: (sectionId, itemId) => {
            if (state.builderPack) {
                const section = state.builderPack.sections.find(s => s.id === sectionId);
                if (section && section.items) {
                    section.items = section.items.filter(i => i.id !== itemId);
                    renderBuilderStep();
                    updateBuilderStats();
                }
            }
        },
        
        toggleItemPacked: (sectionId, itemId) => {
            if (state.builderPack) {
                const section = state.builderPack.sections.find(s => s.id === sectionId);
                if (section && section.items) {
                    const item = section.items.find(i => i.id === itemId);
                    if (item) {
                        item.packed = !item.packed;
                        updateBuilderStats();
                    }
                }
            }
        },
        
        // Drag and drop
        handleDragStart,
        handleDragOver,
        handleDragLeave,
        handleDrop,
        
        // Pack management
        editPack: (id) => openBuilder(id),
        
        duplicatePack: (id) => {
            const pack = state.packs.find(p => p.id === id);
            if (pack) {
                const newPack = JSON.parse(JSON.stringify(pack));
                newPack.id = generateId();
                newPack.name = pack.name + ' (Copy)';
                newPack.created_at = new Date().toISOString();
                state.packs.push(newPack);
                savePacks();
                renderPacks();
                showToast('Pack duplicated!', 'success');
            }
        },
        
        // Placeholder functions
        saveDraft: () => showToast('Draft saved!', 'success'),
        previewPack: () => showToast('Preview coming soon!', 'info'),
        quickStart: () => createNew(),
        showTour: () => showToast('Tour coming soon!', 'info'),
        toggleView: () => {},
        search: () => {},
        filter: () => {},
        filterByWeight: () => {},
        filterByTag: () => {},
        showCategory: () => {},
        showTemplates: () => showToast('Templates coming soon!', 'info'),
        showImport: () => showToast('Import coming soon!', 'info'),
        closeDetail: () => {},
        createFromLastTrip: () => createNew(),
        duplicateFavorite: () => createNew(),
        smartPack: () => showToast('AI Smart Pack coming soon!', 'info'),
        showAddGearModal: () => showToast('Custom gear modal coming soon!', 'info'),
        toggleSectionCollapse: () => {},
        exportPack: () => showToast('Export coming soon!', 'info')
    };
})();

// Auto-initialize with performance optimization
if (typeof PackManager !== 'undefined') {
    console.log('PackManager already initialized');
}
