/**
 * Trips Loading Enhancement
 * Adds loading states and skeleton screens to the trips page
 */

(function() {
    'use strict';
    
    // Cache DOM elements
    const tripGrid = document.getElementById('trip-grid');
    const emptyState = document.getElementById('trips-empty');
    const searchInput = document.getElementById('trip-search');
    const sortSelect = document.getElementById('sort-trips');
    const newTripBtn = document.getElementById('btn-new-trip');
    
    // Store original loadTrips function if it exists
    const originalLoadTrips = window.loadTrips;
    
    /**
     * Enhanced loadTrips with skeleton loading
     */
    window.loadTrips = async function() {
        try {
            // Show skeleton loader immediately
            if (tripGrid) {
                tripGrid.setAttribute('aria-busy', 'true');
                showSkeleton(tripGrid, 'card', 6);
            }
            
            // If original loadTrips exists, call it
            if (originalLoadTrips) {
                const result = await originalLoadTrips.apply(this, arguments);
                
                // Hide skeleton after data loads
                setTimeout(() => {
                    hideSkeleton(tripGrid);
                    tripGrid.setAttribute('aria-busy', 'false');
                }, 300);
                
                return result;
            } else {
                // Fallback: fetch trips directly
                const response = await fetch('/api/routes/trips.php?action=list', {
                    headers: {
                        'X-CSRF-Token': window.BTT?.csrfToken || ''
                    }
                });
                
                if (!response.ok) throw new Error('Failed to load trips');
                
                const data = await response.json();
                
                // Hide skeleton and render trips
                setTimeout(() => {
                    renderTrips(data.trips || []);
                    tripGrid.setAttribute('aria-busy', 'false');
                }, 300);
                
                return data;
            }
        } catch (error) {
            console.error('Error loading trips:', error);
            
            // Hide skeleton and show error state
            hideSkeleton(tripGrid);
            tripGrid.setAttribute('aria-busy', 'false');
            tripGrid.innerHTML = `
                <div class="error-state">
                    <p>⚠️ Failed to load trips</p>
                    <button class="btn btn-secondary" onclick="loadTrips()">Try Again</button>
                </div>
            `;
        }
    };
    
    /**
     * Render trips with animation
     */
    function renderTrips(trips) {
        if (!trips || trips.length === 0) {
            // Show empty state
            if (tripGrid) tripGrid.style.display = 'none';
            if (emptyState) emptyState.hidden = false;
            return;
        }
        
        // Hide empty state
        if (emptyState) emptyState.hidden = true;
        if (tripGrid) tripGrid.style.display = '';
        
        // Generate trip cards HTML
        const tripsHTML = trips.map(trip => generateTripCard(trip)).join('');
        
        // Fade in new content
        if (window.loadingManager) {
            window.loadingManager.fadeTransition(tripGrid, () => {
                tripGrid.innerHTML = tripsHTML;
            });
        } else {
            tripGrid.innerHTML = tripsHTML;
        }
    }
    
    /**
     * Generate trip card HTML using new card system
     */
    function generateTripCard(trip) {
        // Use PHP-rendered cards if available via AJAX
        // Otherwise fallback to JS template
        
        // Prepare badges
        const badges = [];
        if (trip.favorite == 1) {
            badges.push('⭐ Favorite');
        }
        if (trip.completed == 1) {
            badges.push('✅ Completed');
        }
        
        // Date formatting
        const startDate = trip.start_date ? new Date(trip.start_date).toLocaleDateString() : '';
        const endDate = trip.end_date ? new Date(trip.end_date).toLocaleDateString() : '';
        const dateRange = endDate && endDate !== startDate ? `${startDate} - ${endDate}` : startDate;
        
        // Meta items
        const metaItems = [];
        if (trip.location) metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">📍</span><span>${trip.location}</span></div>`);
        if (dateRange) metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">📅</span><span>${dateRange}</span></div>`);
        if (trip.distance) metaItems.push(`<div class="card__meta-item"><span class="card__meta-icon">📏</span><span>${trip.distance} ${trip.distance_unit || 'miles'}</span></div>`);
        
        const photoUrl = trip.photo || '/assets/images/default-trip.jpg';
        const altText = trip.photo_alt_text || trip.title || 'Trip photo';
        
        return `
            <article class="card trip-card card--animate-in" data-trip-id="${trip.id}">
                <div class="card__image">
                    <img src="${photoUrl}" alt="${altText}" loading="lazy">
                    ${badges.length > 0 ? `
                        <div class="card__badges">
                            ${badges.map(badge => {
                                const type = badge.includes('Favorite') ? 'card__badge--warning' : 
                                           badge.includes('Completed') ? 'card__badge--success' : '';
                                return `<span class="card__badge ${type}">${badge}</span>`;
                            }).join('')}
                        </div>
                    ` : ''}
                </div>
                <div class="card__content">
                    <h3 class="card__title">${trip.title || 'Untitled Trip'}</h3>
                    ${trip.trail_name ? `<div class="card__subtitle">${trip.trail_name}</div>` : ''}
                    ${metaItems.length > 0 ? `
                        <div class="card__meta">
                            ${metaItems.join('')}
                        </div>
                    ` : ''}
                    ${trip.description ? `<p class="card__description">${trip.description}</p>` : ''}
                </div>
                <div class="card__actions">
                    <button class="card__action card__action--secondary" onclick="editTrip(${trip.id})">
                        <span>✏️</span> Edit
                    </button>
                    <button class="card__action card__action--primary" onclick="viewTrip(${trip.id})">
                        <span>👁️</span> View
                    </button>
                </div>
            </article>
        `;
    }
    
    /**
     * Handle create trip button with loading state
     */
    if (newTripBtn) {
        newTripBtn.addEventListener('click', async function() {
            setButtonLoading(this, 'Creating...');
            
            // Switch to editor tab
            const editorTab = document.getElementById('tab-trip-editor');
            if (editorTab) {
                editorTab.click();
            }
            
            // Reset form for new trip
            const form = document.getElementById('trip-form');
            if (form) {
                form.reset();
                document.getElementById('trip-id').value = '';
            }
            
            // Remove loading state
            setTimeout(() => {
                removeButtonLoading(this);
            }, 500);
        });
    }
    
    /**
     * Handle save trip with loading state
     */
    const saveTripBtn = document.getElementById('btn-save-trip');
    if (saveTripBtn) {
        const originalSaveHandler = saveTripBtn.onclick;
        
        saveTripBtn.onclick = async function(e) {
            e.preventDefault();
            
            // Add loading state
            setButtonLoading(this, 'Saving...');
            
            try {
                // Call original handler if exists
                if (originalSaveHandler) {
                    await originalSaveHandler.call(this, e);
                }
                
                // Show success
                removeButtonLoading(this, '✅ Saved!');
                
                // Reload trips list
                setTimeout(() => {
                    loadTrips();
                }, 1000);
            } catch (error) {
                console.error('Save error:', error);
                removeButtonLoading(this);
                
                // Show error toast if available
                if (window.showToast) {
                    window.showToast('Failed to save trip', 'error');
                }
            }
        };
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
            if (tripGrid) {
                tripGrid.setAttribute('aria-busy', 'true');
            }
            
            searchTimeout = setTimeout(async () => {
                if (query.length === 0) {
                    // Load all trips
                    await loadTrips();
                } else {
                    // Show skeleton while searching
                    showSkeleton(tripGrid, 'card', 3);
                    
                    // Perform search (simulate API call)
                    setTimeout(() => {
                        // Filter trips client-side for now
                        const allCards = document.querySelectorAll('.trip-card');
                        const matchingCards = [];
                        
                        allCards.forEach(card => {
                            const title = card.querySelector('.pack-card-title')?.textContent || '';
                            const location = card.querySelector('.pack-card-meta')?.textContent || '';
                            
                            if (title.toLowerCase().includes(query.toLowerCase()) || 
                                location.toLowerCase().includes(query.toLowerCase())) {
                                matchingCards.push(card.outerHTML);
                            }
                        });
                        
                        // Show results
                        hideSkeleton(tripGrid, matchingCards.length > 0 ? 
                            matchingCards.join('') : 
                            '<div class="no-results">No trips found matching your search.</div>');
                        
                        tripGrid.setAttribute('aria-busy', 'false');
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
            showSkeleton(tripGrid, 'card', 6);
            
            // Simulate sorting delay
            setTimeout(() => {
                // Get all cards
                const cards = Array.from(document.querySelectorAll('.trip-card'));
                const sortValue = this.value;
                
                // Sort cards
                cards.sort((a, b) => {
                    if (sortValue === 'name') {
                        const aTitle = a.querySelector('.pack-card-title')?.textContent || '';
                        const bTitle = b.querySelector('.pack-card-title')?.textContent || '';
                        return aTitle.localeCompare(bTitle);
                    } else if (sortValue === 'date') {
                        // Sort by start date (would need actual date data)
                        return 0;
                    }
                    // Default: recent (reverse order)
                    return b.dataset.tripId - a.dataset.tripId;
                });
                
                // Re-render sorted cards
                hideSkeleton(tripGrid, cards.map(card => card.outerHTML).join(''));
            }, 300);
        });
    }
    
    /**
     * Initialize on page load
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Load trips on page load
        if (tripGrid && typeof loadTrips === 'function') {
            loadTrips();
        }
    });
})();
