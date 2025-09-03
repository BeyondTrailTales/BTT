/**
 * Backpacks Loading Enhancement
 * Uses the new card system for backpack display
 */

(function() {
    'use strict';
    
    // Cache DOM elements
    const backpackGrid = document.querySelector('.packs-grid');
    const emptyState = document.querySelector('.packs-empty-state');
    const searchInput = document.getElementById('pack-search');
    const sortSelect = document.querySelector('.sort-control select');
    const newPackBtn = document.querySelector('.btn-action');
    
    // Store original loadBackpacks function if it exists
    const originalLoadBackpacks = window.loadBackpacks;
    
    /**
     * Enhanced loadBackpacks with skeleton loading
     */
    window.loadBackpacks = async function() {
        try {
            // Show skeleton loader immediately
            if (backpackGrid) {
                backpackGrid.setAttribute('aria-busy', 'true');
                showSkeleton(backpackGrid, 'card', 6);
            }
            
            // If original loadBackpacks exists, call it
            if (originalLoadBackpacks) {
                const result = await originalLoadBackpacks.apply(this, arguments);
                
                // Hide skeleton after data loads
                setTimeout(() => {
                    hideSkeleton(backpackGrid);
                    backpackGrid.setAttribute('aria-busy', 'false');
                }, 300);
                
                return result;
            } else {
                // Fallback: fetch backpacks directly
                const response = await fetch('/api/routes/backpacks.php?action=list', {
                    headers: {
                        'X-CSRF-Token': window.BTT?.csrfToken || ''
                    }
                });
                
                if (!response.ok) throw new Error('Failed to load backpacks');
                
                const data = await response.json();
                
                // Hide skeleton and render backpacks
                setTimeout(() => {
                    renderBackpacks(data.backpacks || []);
                    backpackGrid.setAttribute('aria-busy', 'false');
                }, 300);
                
                return data;
            }
        } catch (error) {
            console.error('Error loading backpacks:', error);
            
            // Hide skeleton and show error state
            hideSkeleton(backpackGrid);
            backpackGrid.setAttribute('aria-busy', 'false');
            backpackGrid.innerHTML = `
                <div class="error-state">
                    <p>⚠️ Failed to load backpacks</p>
                    <button class="btn btn-secondary" onclick="loadBackpacks()">Try Again</button>
                </div>
            `;
        }
    };
    
    /**
     * Render backpacks with animation
     */
    function renderBackpacks(backpacks) {
        if (!backpacks || backpacks.length === 0) {
            // Show empty state using new card system
            if (backpackGrid) {
                backpackGrid.innerHTML = `
                    <div class="card card--empty backpack-empty-card">
                        <div class="card__empty-content">
                            <div class="card__empty-icon">🎒</div>
                            <h3 class="card__empty-title">No backpacks yet</h3>
                            <p class="card__empty-subtitle">Create your first pack to start building</p>
                            <button class="card__empty-action" onclick="createNewBackpack()">
                                <span>➕</span> Create First Pack
                            </button>
                        </div>
                    </div>
                `;
            }
            return;
        }
        
        // Generate backpack cards HTML
        const backpacksHTML = backpacks.map(pack => generateBackpackCard(pack)).join('');
        
        // Fade in new content
        if (window.loadingManager) {
            window.loadingManager.fadeTransition(backpackGrid, () => {
                backpackGrid.innerHTML = backpacksHTML;
            });
        } else {
            backpackGrid.innerHTML = backpacksHTML;
        }
    }
    
    /**
     * Generate backpack card HTML using new card system
     */
    function generateBackpackCard(pack) {
        // Prepare badges
        const badges = [];
        if (pack.is_public == 1) {
            badges.push('🌐 Public');
        }
        if (pack.is_wishlist == 1) {
            badges.push('✨ Wishlist');
        }
        
        // Calculate item count
        let itemCount = 0;
        if (pack.items) {
            try {
                const items = typeof pack.items === 'string' ? JSON.parse(pack.items) : pack.items;
                itemCount = items ? items.length : 0;
            } catch (e) {
                itemCount = 0;
            }
        }
        
        // Format weights
        const baseWeight = formatWeight(pack.base_weight || 0);
        const totalWeight = formatWeight(pack.total_weight || 0);
        
        // Meta items
        const metaItems = [];
        metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">📦</span><span>${itemCount} items</span></div>`);
        metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">⚖️</span><span>Base: ${baseWeight}</span></div>`);
        
        if (totalWeight !== baseWeight) {
            metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">🎒</span><span>Total: ${totalWeight}</span></div>`);
        }
        
        if (pack.capacity_liters) {
            metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">📏</span><span>${pack.capacity_liters}L</span></div>`);
        }
        
        const photoUrl = pack.photo || '/assets/images/default-backpack.jpg';
        const altText = pack.photo_alt_text || pack.name || 'Backpack photo';
        
        // Get weight class for visual indicator
        const weightClass = getWeightClass(pack.base_weight || 0);
        
        return `
            <article class="card backpack-card ${weightClass} card--animate-in" data-backpack-id="${pack.id}">
                <div class="card__image">
                    <img src="${photoUrl}" alt="${altText}" loading="lazy">
                    ${badges.length > 0 ? `
                        <div class="card__badges">
                            ${badges.map(badge => {
                                const type = badge.includes('Public') ? 'card__badge--primary' : 
                                           badge.includes('Wishlist') ? 'card__badge--warning' : '';
                                return `<span class="card__badge ${type}">${badge}</span>`;
                            }).join('')}
                        </div>
                    ` : ''}
                </div>
                <div class="card__content">
                    <h3 class="card__title">${pack.name || 'Untitled Pack'}</h3>
                    ${pack.brand ? `<div class="card__subtitle">${pack.brand}</div>` : ''}
                    ${metaItems.length > 0 ? `
                        <div class="card__meta">
                            ${metaItems.join('')}
                        </div>
                    ` : ''}
                    ${pack.description ? `<p class="card__description">${pack.description}</p>` : ''}
                </div>
                <div class="card__actions">
                    <button class="card__action card__action--secondary" onclick="viewBackpack(${pack.id})">
                        <span>👁️</span> View
                    </button>
                    <button class="card__action card__action--primary" onclick="openPackBuilder(${pack.id})">
                        <span>🔧</span> Pack Builder
                    </button>
                </div>
            </article>
        `;
    }
    
    /**
     * Format weight with appropriate unit
     */
    function formatWeight(grams) {
        if (grams === 0 || grams === null) {
            return '0g';
        }
        
        if (grams >= 1000) {
            const kg = (grams / 1000).toFixed(2);
            return kg + 'kg';
        }
        
        // For ounces conversion
        const oz = (grams * 0.035274).toFixed(1);
        
        return `${grams}g (${oz}oz)`;
    }
    
    /**
     * Get weight class for styling
     */
    function getWeightClass(baseWeightGrams) {
        if (baseWeightGrams < 4536) { // < 10 lbs
            return 'weight-ultralight';
        } else if (baseWeightGrams < 6804) { // < 15 lbs
            return 'weight-light';
        } else if (baseWeightGrams < 9072) { // < 20 lbs
            return 'weight-moderate';
        } else {
            return 'weight-heavy';
        }
    }
    
    /**
     * Handle create backpack button with loading state
     */
    if (newPackBtn) {
        newPackBtn.addEventListener('click', async function() {
            setButtonLoading(this, 'Creating...');
            
            // Switch to builder tab if exists
            const builderTab = document.getElementById('tab-builder');
            if (builderTab) {
                builderTab.click();
            }
            
            // Remove loading state
            setTimeout(() => {
                removeButtonLoading(this);
            }, 500);
        });
    }
    
    /**
     * Handle search with debounced loading
     */
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            const query = this.value.trim();
            
            // Show loading state in grid
            if (backpackGrid) {
                backpackGrid.setAttribute('aria-busy', 'true');
            }
            
            searchTimeout = setTimeout(async () => {
                if (query.length === 0) {
                    // Load all backpacks
                    await loadBackpacks();
                } else {
                    // Show skeleton while searching
                    showSkeleton(backpackGrid, 'card', 3);
                    
                    // Perform search (simulate API call)
                    setTimeout(() => {
                        // Filter backpacks client-side for now
                        const allCards = document.querySelectorAll('.backpack-card');
                        const matchingCards = [];
                        
                        allCards.forEach(card => {
                            const title = card.querySelector('.card__title')?.textContent || '';
                            const subtitle = card.querySelector('.card__subtitle')?.textContent || '';
                            
                            if (title.toLowerCase().includes(query.toLowerCase()) || 
                                subtitle.toLowerCase().includes(query.toLowerCase())) {
                                matchingCards.push(card.outerHTML);
                            }
                        });
                        
                        // Show results
                        hideSkeleton(backpackGrid, matchingCards.length > 0 ? 
                            matchingCards.join('') : 
                            '<div class="no-results">No backpacks found matching your search.</div>');
                        
                        backpackGrid.setAttribute('aria-busy', 'false');
                    }, 500);
                }
            }, 300);
        });
    }
    
    /**
     * Handle sort with loading state
     */
    if (sortSelect) {
        sortSelect.addEventListener('change', async function() {
            // Show loading
            showSkeleton(backpackGrid, 'card', 6);
            
            // Simulate sorting delay
            setTimeout(() => {
                // Get all cards
                const cards = Array.from(document.querySelectorAll('.backpack-card'));
                const sortValue = this.value;
                
                // Sort cards
                cards.sort((a, b) => {
                    if (sortValue === 'name') {
                        const aTitle = a.querySelector('.card__title')?.textContent || '';
                        const bTitle = b.querySelector('.card__title')?.textContent || '';
                        return aTitle.localeCompare(bTitle);
                    } else if (sortValue === 'weight') {
                        const aWeight = parseInt(a.dataset.baseWeight) || 0;
                        const bWeight = parseInt(b.dataset.baseWeight) || 0;
                        return aWeight - bWeight;
                    }
                    // Default: recent (reverse order)
                    return b.dataset.backpackId - a.dataset.backpackId;
                });
                
                // Re-render sorted cards
                hideSkeleton(backpackGrid, cards.map(card => card.outerHTML).join(''));
            }, 300);
        });
    }
    
    /**
     * Initialize on page load
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Load backpacks on page load
        if (backpackGrid && typeof loadBackpacks === 'function') {
            loadBackpacks();
        }
    });
    
    // Expose functions globally
    window.openPackBuilder = function(id) {
        // Switch to builder view and load pack
        const builderTab = document.getElementById('tab-builder');
        if (builderTab) {
            builderTab.click();
            // Load pack data into builder
            console.log('Loading pack', id, 'into builder');
        }
    };
    
    window.viewBackpack = function(id) {
        // Navigate to backpack detail view
        console.log('Viewing backpack', id);
    };
    
    window.createNewBackpack = function() {
        // Create new backpack and switch to builder
        const newPackBtn = document.querySelector('.btn-action');
        if (newPackBtn) {
            newPackBtn.click();
        }
    };
})();
