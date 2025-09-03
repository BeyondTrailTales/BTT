/**
 * Enhanced Trips JavaScript
 * Smart backpack integration, keyboard shortcuts, and amazing UX
 */

(function() {
  'use strict';
  
  // State management
  const state = {
    trips: [],
    backpacks: [],
    currentTrip: null,
    currentTripId: null,
    attachedPack: null,
    viewMode: 'list',
    filter: 'all',
    searchQuery: '',
    totalStats: {
      trips: 0,
      distance: 0,
      days: 0
    }
  };
  
  // Elements
  const elements = {};
  
  // Initialize
  document.addEventListener('DOMContentLoaded', init);
  
  async function init() {
    cacheElements();
    detectViewMode();
    
    if (state.viewMode === 'list') {
      await initListView();
    } else {
      await initDetailView();
    }
    
    bindEvents();
    setupKeyboardShortcuts();
  }
  
  function cacheElements() {
    // List view elements
    elements.tripGrid = document.getElementById('trip-grid');
    elements.searchInput = document.getElementById('trip-search');
    elements.btnNewTrip = document.getElementById('btn-new-trip');
    elements.filterChips = document.querySelectorAll('.filter-chip');
    elements.sortSelect = document.getElementById('sort-trips');
    elements.emptyState = document.getElementById('trips-empty');
    elements.emptyCreate = document.getElementById('empty-create');
    
    // Stats
    elements.totalTrips = document.getElementById('total-trips');
    elements.totalDistance = document.getElementById('total-distance');
    elements.totalDays = document.getElementById('total-days');
    
    // Detail view elements
    elements.heroImage = document.getElementById('hero-image');
    elements.tripTitle = document.getElementById('trip-title');
    elements.tripLocation = document.getElementById('trip-location');
    elements.tripDates = document.getElementById('trip-dates');
    
    // Quick actions
    elements.btnStartWizard = document.getElementById('btn-start-wizard');
    elements.btnAttachPack = document.getElementById('btn-attach-pack');
    elements.btnTimeline = document.getElementById('btn-timeline');
    elements.btnShare = document.getElementById('btn-share');
    
    // Dashboard cards
    elements.scoreCircle = document.querySelector('.score-circle');
    elements.readinessItems = document.querySelectorAll('.readiness-item');
    elements.metricDistance = document.getElementById('metric-distance');
    elements.metricElevation = document.getElementById('metric-elevation');
    elements.metricDays = document.getElementById('metric-days');
    elements.metricWater = document.getElementById('metric-water');
    
    // Backpack section
    elements.noPackState = document.getElementById('no-pack-state');
    elements.packAttachedState = document.getElementById('pack-attached-state');
    elements.packSuggestions = document.getElementById('pack-suggestions');
    elements.btnBrowsePacks = document.getElementById('btn-browse-packs');
    elements.btnCreatePack = document.getElementById('btn-create-pack');
    elements.attachedPackName = document.getElementById('attached-pack-name');
    elements.baseWeight = document.getElementById('base-weight');
    elements.consumablesWeight = document.getElementById('consumables-weight');
    elements.waterWeight = document.getElementById('water-weight');
    elements.totalWeight = document.getElementById('total-weight');
    
    // Modal
    elements.packSelectorModal = document.getElementById('pack-selector-modal');
    elements.packSelectorGrid = document.getElementById('pack-selector-grid');
    elements.modalPackSearch = document.getElementById('modal-pack-search');
    
    // Badges
    elements.badgesGrid = document.getElementById('badges-grid');
    
    // Toast
    elements.shortcutsToast = document.getElementById('shortcuts-toast');
  }
  
  function detectViewMode() {
    const container = document.getElementById('main-content');
    state.viewMode = container.dataset.view || 'list';
    
    // Get trip ID from URL if in detail view
    if (state.viewMode === 'detail') {
      const params = new URLSearchParams(window.location.search);
      state.currentTripId = params.get('id');
    }
  }
  
  // ==================== LIST VIEW ====================
  
  async function initListView() {
    showSkeletonCards();
    await loadTrips();
    await loadBackpacks();
    updateTotalStats();
    renderTripCards();
  }
  
  async function loadTrips() {
    try {
      const response = await fetch('api/trips');
      const data = await response.json();
      state.trips = Array.isArray(data) ? data : [];
    } catch (error) {
      console.error('Failed to load trips:', error);
      state.trips = [];
    }
  }
  
  async function loadBackpacks() {
    try {
      const response = await fetch('api/backpacks');
      const data = await response.json();
      state.backpacks = Array.isArray(data) ? data : [];
    } catch (error) {
      console.error('Failed to load backpacks:', error);
      state.backpacks = [];
    }
  }
  
  function updateTotalStats() {
    let totalDistance = 0;
    let totalDays = 0;
    
    state.trips.forEach(trip => {
      if (trip.distance) totalDistance += parseFloat(trip.distance);
      if (trip.start_date && trip.end_date) {
        const start = new Date(trip.start_date);
        const end = new Date(trip.end_date);
        const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        totalDays += days;
      }
    });
    
    state.totalStats = {
      trips: state.trips.length,
      distance: totalDistance,
      days: totalDays
    };
    
    if (elements.totalTrips) {
      elements.totalTrips.textContent = `${state.totalStats.trips} trips`;
    }
    if (elements.totalDistance) {
      elements.totalDistance.textContent = `${Math.round(state.totalStats.distance)} km`;
    }
    if (elements.totalDays) {
      elements.totalDays.textContent = `${state.totalStats.days} days`;
    }
  }
  
  function showSkeletonCards() {
    if (!elements.tripGrid) return;
    
    const skeletonHTML = Array(3).fill('').map(() => `
      <div class="skeleton-card">
        <div class="skeleton-image"></div>
        <div class="skeleton-content">
          <div class="skeleton-line skeleton-title"></div>
          <div class="skeleton-line skeleton-text"></div>
          <div class="skeleton-line skeleton-text short"></div>
        </div>
      </div>
    `).join('');
    
    elements.tripGrid.innerHTML = skeletonHTML;
  }
  
  function renderTripCards() {
    if (!elements.tripGrid) return;
    
    let filteredTrips = filterTrips();
    filteredTrips = sortTrips(filteredTrips);
    
    if (filteredTrips.length === 0) {
      elements.tripGrid.innerHTML = '';
      if (elements.emptyState) {
        elements.emptyState.hidden = false;
      }
      return;
    }
    
    if (elements.emptyState) {
      elements.emptyState.hidden = true;
    }
    
    const cardsHTML = filteredTrips.map(trip => renderTripCard(trip)).join('');
    elements.tripGrid.innerHTML = cardsHTML;
    
    // Add animation
    const cards = elements.tripGrid.querySelectorAll('.trip-card-enhanced');
    cards.forEach((card, index) => {
      setTimeout(() => {
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, index * 50);
    });
  }
  
  function renderTripCard(trip) {
    const photoUrl = trip.default_image_url || 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=400&h=300&fit=crop';
    const title = escapeHtml(trip.title || 'Untitled Trip');
    const location = escapeHtml(trip.location || 'Location TBD');
    
    // Calculate stats
    let duration = '-';
    let dateRange = '';
    if (trip.start_date) {
      const start = new Date(trip.start_date);
      const end = trip.end_date ? new Date(trip.end_date) : start;
      const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
      duration = days === 1 ? 'Day hike' : `${days} days`;
      
      const startStr = start.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const endStr = end.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      dateRange = start.getTime() === end.getTime() ? startStr : `${startStr} - ${endStr}`;
    }
    
    const distance = trip.distance ? `${trip.distance} ${trip.distance_unit || 'km'}` : '-';
    const elevation = trip.elevation_gain ? `${trip.elevation_gain}m` : '-';
    const readiness = trip.readiness_score || 0;
    
    // Get attached pack info
    const packInfo = getPackInfo(trip.backpack_id);
    const packWeight = packInfo ? `${packInfo.totalWeight}kg` : '-';
    
    // Badges
    const badges = [];
    if (trip.completed) badges.push('COMPLETED');
    if (trip.favorite) badges.push('FAVORITE');
    if (trip.trip_type) badges.push(trip.trip_type.replace('_', ' ').toUpperCase());
    
    return `
      <article class="trip-card-enhanced" data-id="${trip.id}" style="opacity: 0; transform: translateY(20px);">
        <div class="trip-card-image-container">
          <img src="${photoUrl}" alt="${title}" class="trip-card-image" loading="lazy" />
          <div class="trip-card-badges">
            ${badges.map(badge => `<span class="trip-badge">${badge}</span>`).join('')}
          </div>
          <div class="trip-card-progress">
            <div class="progress-fill" style="width: ${readiness}%"></div>
          </div>
        </div>
        <div class="trip-card-content">
          <div class="trip-card-header">
            <h3 class="trip-card-title">${title}</h3>
            <div class="trip-card-meta">
              <span>📍 ${location}</span>
              ${dateRange ? `<span>📅 ${dateRange}</span>` : ''}
            </div>
          </div>
          <div class="trip-card-stats">
            <div class="trip-stat-mini">
              <span class="stat-icon">⏱️</span>
              <span class="stat-value">${duration}</span>
              <span class="stat-label">Duration</span>
            </div>
            <div class="trip-stat-mini">
              <span class="stat-icon">🥾</span>
              <span class="stat-value">${distance}</span>
              <span class="stat-label">Distance</span>
            </div>
            <div class="trip-stat-mini">
              <span class="stat-icon">📈</span>
              <span class="stat-value">${elevation}</span>
              <span class="stat-label">Elevation</span>
            </div>
            <div class="trip-stat-mini">
              <span class="stat-icon">🎒</span>
              <span class="stat-value">${packWeight}</span>
              <span class="stat-label">Pack</span>
            </div>
          </div>
          <div class="trip-card-actions">
            <button onclick="viewTrip(${trip.id})">View</button>
            <button onclick="startWizard(${trip.id})">Plan</button>
            <button onclick="shareTrip(${trip.id})">Share</button>
          </div>
        </div>
      </article>
    `;
  }
  
  function filterTrips() {
    let filtered = [...state.trips];
    
    // Apply filter
    if (state.filter === 'upcoming') {
      filtered = filtered.filter(t => !t.completed && new Date(t.start_date) > new Date());
    } else if (state.filter === 'completed') {
      filtered = filtered.filter(t => t.completed);
    } else if (state.filter === 'favorites') {
      filtered = filtered.filter(t => t.favorite);
    }
    
    // Apply search
    if (state.searchQuery) {
      const query = state.searchQuery.toLowerCase();
      filtered = filtered.filter(t => 
        (t.title || '').toLowerCase().includes(query) ||
        (t.location || '').toLowerCase().includes(query)
      );
    }
    
    return filtered;
  }
  
  function sortTrips(trips) {
    const sortBy = elements.sortSelect ? elements.sortSelect.value : 'recent';
    
    return trips.sort((a, b) => {
      switch (sortBy) {
        case 'name':
          return (a.title || '').localeCompare(b.title || '');
        case 'date':
          return new Date(a.start_date || 0) - new Date(b.start_date || 0);
        case 'distance':
          return (b.distance || 0) - (a.distance || 0);
        default: // recent
          return new Date(b.created_at || 0) - new Date(a.created_at || 0);
      }
    });
  }
  
  // ==================== DETAIL VIEW ====================
  
  async function initDetailView() {
    if (!state.currentTripId) {
      window.location.href = 'trips-enhanced.php';
      return;
    }
    
    await loadTrip();
    await loadBackpacks();
    renderTripOverview();
    loadPackSuggestions();
    updateReadinessScore();
    loadBadges();
  }
  
  async function loadTrip() {
    try {
      const response = await fetch(`api/trips?id=${state.currentTripId}`);
      const data = await response.json();
      state.currentTrip = Array.isArray(data) ? data[0] : data;
    } catch (error) {
      console.error('Failed to load trip:', error);
      showToast('Failed to load trip details', 'error');
    }
  }
  
  function renderTripOverview() {
    if (!state.currentTrip) return;
    
    const trip = state.currentTrip;
    
    // Hero section
    if (elements.heroImage) {
      elements.heroImage.src = trip.default_image_url || 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=1920&h=600&fit=crop';
      elements.heroImage.alt = trip.title || 'Trip photo';
    }
    
    if (elements.tripTitle) {
      elements.tripTitle.textContent = trip.title || 'Untitled Trip';
    }
    
    if (elements.tripLocation) {
      const locationText = elements.tripLocation.querySelector('.text');
      if (locationText) {
        locationText.textContent = trip.location || 'Location TBD';
      }
    }
    
    if (elements.tripDates) {
      const datesText = elements.tripDates.querySelector('.text');
      if (datesText && trip.start_date) {
        const start = new Date(trip.start_date);
        const end = trip.end_date ? new Date(trip.end_date) : start;
        const startStr = start.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        const endStr = end.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        datesText.textContent = start.getTime() === end.getTime() ? startStr : `${startStr} - ${endStr}`;
      }
    }
    
    // Metrics
    if (elements.metricDistance) {
      elements.metricDistance.textContent = trip.distance || '0';
    }
    
    if (elements.metricElevation) {
      elements.metricElevation.textContent = trip.elevation_gain || '0';
    }
    
    if (elements.metricDays) {
      let days = 0;
      if (trip.start_date && trip.end_date) {
        const start = new Date(trip.start_date);
        const end = new Date(trip.end_date);
        days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
      }
      elements.metricDays.textContent = days;
    }
    
    if (elements.metricWater) {
      elements.metricWater.textContent = trip.water_stops || '0';
    }
    
    // Backpack
    if (trip.backpack_id) {
      showAttachedPack(trip.backpack_id);
    }
  }
  
  function loadPackSuggestions() {
    if (!state.currentTrip || !elements.packSuggestions) return;
    
    const tripType = state.currentTrip.trip_type || 'weekend';
    const suggestions = getSuggestedPacks(tripType);
    
    const suggestionsHTML = suggestions.map(pack => `
      <div class="pack-suggestion" onclick="attachPack(${pack.id})">
        <div class="pack-suggestion-icon">🎒</div>
        <div class="pack-suggestion-name">${escapeHtml(pack.name)}</div>
        <div class="pack-suggestion-weight">${calculatePackWeight(pack)} kg</div>
      </div>
    `).join('');
    
    elements.packSuggestions.innerHTML = suggestionsHTML;
  }
  
  function getSuggestedPacks(tripType) {
    // Smart suggestion algorithm
    return state.backpacks
      .filter(pack => {
        // Filter by trip type if pack has tags
        if (pack.tags) {
          const tags = pack.tags.toLowerCase();
          if (tripType === 'day_hike' && tags.includes('day')) return true;
          if (tripType === 'overnight' && tags.includes('overnight')) return true;
          if (tripType === 'weekend' && tags.includes('weekend')) return true;
          if (tripType === 'thru_hike' && tags.includes('thru')) return true;
        }
        return true;
      })
      .sort((a, b) => {
        // Sort by best fit (weight, last used, etc.)
        const weightA = calculatePackWeight(a);
        const weightB = calculatePackWeight(b);
        return weightA - weightB;
      })
      .slice(0, 3); // Top 3 suggestions
  }
  
  function calculatePackWeight(pack) {
    const baseWeight = pack.base_weight || 0;
    const consumables = pack.consumables_weight || 0;
    const water = pack.water_weight || 0;
    return Math.round((baseWeight + consumables + water) * 10) / 10;
  }
  
  function showAttachedPack(packId) {
    const pack = state.backpacks.find(p => p.id == packId);
    if (!pack) return;
    
    state.attachedPack = pack;
    
    if (elements.noPackState) elements.noPackState.hidden = true;
    if (elements.packAttachedState) elements.packAttachedState.hidden = false;
    
    if (elements.attachedPackName) {
      elements.attachedPackName.textContent = pack.name;
    }
    
    const baseWeight = pack.base_weight || 0;
    const consumables = pack.consumables_weight || 0;
    const water = pack.water_weight || 0;
    const total = baseWeight + consumables + water;
    
    if (elements.baseWeight) elements.baseWeight.textContent = `${baseWeight} kg`;
    if (elements.consumablesWeight) elements.consumablesWeight.textContent = `${consumables} kg`;
    if (elements.waterWeight) elements.waterWeight.textContent = `${water} kg`;
    if (elements.totalWeight) elements.totalWeight.textContent = `${Math.round(total * 10) / 10} kg`;
    
    // Update weight badge
    const weightBadge = document.querySelector('.pack-weight-badge .weight-value');
    if (weightBadge) {
      weightBadge.textContent = Math.round(total * 10) / 10;
    }
    
    // Update readiness
    updateReadinessItem('pack', true);
  }
  
  function updateReadinessScore() {
    let score = 0;
    const items = {
      dates: state.currentTrip && state.currentTrip.start_date,
      pack: state.attachedPack !== null,
      route: state.currentTrip && state.currentTrip.distance > 0,
      checklist: false // TODO: Check actual checklist
    };
    
    Object.values(items).forEach(complete => {
      if (complete) score += 25;
    });
    
    // Update circle
    if (elements.scoreCircle) {
      const circle = elements.scoreCircle.querySelector('.score-fill');
      const value = elements.scoreCircle.querySelector('.score-value');
      
      if (circle) {
        const circumference = 283; // 2 * PI * 45
        const offset = circumference - (score / 100 * circumference);
        circle.style.strokeDashoffset = offset;
      }
      
      if (value) {
        value.textContent = score;
      }
      
      elements.scoreCircle.dataset.score = score;
    }
    
    // Update breakdown
    updateReadinessItem('dates', items.dates);
    updateReadinessItem('pack', items.pack);
    updateReadinessItem('route', items.route);
    updateReadinessItem('checklist', items.checklist);
  }
  
  function updateReadinessItem(type, complete) {
    const itemMap = {
      dates: 'Dates Set',
      pack: 'Pack Attached',
      route: 'Route Planned',
      checklist: 'Checklist Ready'
    };
    
    const item = Array.from(elements.readinessItems || []).find(el => 
      el.querySelector('.label')?.textContent === itemMap[type]
    );
    
    if (item) {
      if (complete) {
        item.classList.remove('incomplete');
        item.querySelector('.icon').textContent = '✓';
      } else {
        item.classList.add('incomplete');
        item.querySelector('.icon').textContent = '○';
      }
    }
  }
  
  function loadBadges() {
    if (!elements.badgesGrid) return;
    
    const badges = [
      { id: 'planning', icon: '📝', name: 'Planning', earned: true },
      { id: 'first_trip', icon: '🌟', name: 'First Trip', earned: state.trips.length === 1 },
      { id: 'completed', icon: '✅', name: 'Completed', earned: state.currentTrip?.completed },
      { id: 'favorite', icon: '⭐', name: 'Favorite', earned: state.currentTrip?.favorite },
      { id: 'ultralight', icon: '🪶', name: 'Ultralight', earned: state.attachedPack && calculatePackWeight(state.attachedPack) < 5 },
      { id: 'challenging', icon: '🏔️', name: 'Challenging', earned: state.currentTrip?.difficulty === 'hard' || state.currentTrip?.difficulty === 'expert' }
    ];
    
    const badgesHTML = badges.map(badge => `
      <div class="badge ${badge.earned ? 'earned' : ''}" data-badge="${badge.id}">
        <span class="badge-icon">${badge.icon}</span>
        <span class="badge-name">${badge.name}</span>
      </div>
    `).join('');
    
    elements.badgesGrid.innerHTML = badgesHTML;
  }
  
  // ==================== EVENTS ====================
  
  function bindEvents() {
    // Search
    if (elements.searchInput) {
      elements.searchInput.addEventListener('input', debounce(() => {
        state.searchQuery = elements.searchInput.value;
        renderTripCards();
      }, 300));
    }
    
    // Filters
    elements.filterChips?.forEach(chip => {
      chip.addEventListener('click', () => {
        elements.filterChips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        state.filter = chip.dataset.filter;
        renderTripCards();
      });
    });
    
    // Sort
    if (elements.sortSelect) {
      elements.sortSelect.addEventListener('change', () => {
        renderTripCards();
      });
    }
    
    // New trip
    if (elements.btnNewTrip) {
      elements.btnNewTrip.addEventListener('click', createNewTrip);
    }
    
    if (elements.emptyCreate) {
      elements.emptyCreate.addEventListener('click', createNewTrip);
    }
    
    // Quick actions
    if (elements.btnStartWizard) {
      elements.btnStartWizard.addEventListener('click', () => startWizard(state.currentTripId));
    }
    
    if (elements.btnAttachPack) {
      elements.btnAttachPack.addEventListener('click', openPackSelector);
    }
    
    if (elements.btnTimeline) {
      elements.btnTimeline.addEventListener('click', () => openTimeline(state.currentTripId));
    }
    
    if (elements.btnShare) {
      elements.btnShare.addEventListener('click', () => shareTrip(state.currentTripId));
    }
    
    // Backpack actions
    if (elements.btnBrowsePacks) {
      elements.btnBrowsePacks.addEventListener('click', openPackSelector);
    }
    
    if (elements.btnCreatePack) {
      elements.btnCreatePack.addEventListener('click', createPackForTrip);
    }
    
    // Modal
    const modalBackdrop = document.querySelector('.modal-backdrop');
    const modalClose = document.querySelector('.btn-close');
    
    modalBackdrop?.addEventListener('click', closePackSelector);
    modalClose?.addEventListener('click', closePackSelector);
    
    if (elements.modalPackSearch) {
      elements.modalPackSearch.addEventListener('input', debounce(filterPackSelector, 300));
    }
    
    // Pack filter chips
    document.querySelectorAll('.pack-filter-chips .chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.pack-filter-chips .chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        filterPackSelector();
      });
    });
  }
  
  // ==================== ACTIONS ====================
  
  function createNewTrip() {
    // Redirect to trip editor in create mode
    window.location.href = 'trips.php#new';
  }
  
  window.viewTrip = function(id) {
    window.location.href = `trips-enhanced.php?id=${id}`;
  };
  
  window.startWizard = function(id) {
    showToast('Opening trip planning wizard...', 'info');
    // TODO: Implement wizard
  };
  
  window.shareTrip = function(id) {
    const trip = state.trips.find(t => t.id == id) || state.currentTrip;
    if (!trip) return;
    
    const url = `${window.location.origin}/trips-enhanced.php?id=${id}`;
    
    if (navigator.share) {
      navigator.share({
        title: trip.title,
        text: `Check out my trip: ${trip.title}`,
        url: url
      });
    } else {
      navigator.clipboard.writeText(url);
      showToast('Trip link copied to clipboard!', 'success');
    }
  };
  
  function openTimeline(id) {
    showToast('Opening trip timeline...', 'info');
    // TODO: Implement timeline
  }
  
  function openPackSelector() {
    if (!elements.packSelectorModal) return;
    
    elements.packSelectorModal.hidden = false;
    elements.packSelectorModal.setAttribute('aria-hidden', 'false');
    
    renderPackSelector();
  }
  
  function closePackSelector() {
    if (!elements.packSelectorModal) return;
    
    elements.packSelectorModal.hidden = true;
    elements.packSelectorModal.setAttribute('aria-hidden', 'true');
  }
  
  function renderPackSelector() {
    if (!elements.packSelectorGrid) return;
    
    const packsHTML = state.backpacks.map(pack => {
      const weight = calculatePackWeight(pack);
      const itemCount = pack.items_count || 0;
      
      return `
        <div class="pack-selector-card" onclick="selectPack(${pack.id})">
          <div class="pack-selector-icon">🎒</div>
          <div class="pack-selector-name">${escapeHtml(pack.name)}</div>
          <div class="pack-selector-stats">
            <div class="pack-selector-stat">
              <span class="pack-selector-stat-value">${weight}</span>
              <span class="pack-selector-stat-label">kg</span>
            </div>
            <div class="pack-selector-stat">
              <span class="pack-selector-stat-value">${itemCount}</span>
              <span class="pack-selector-stat-label">items</span>
            </div>
          </div>
        </div>
      `;
    }).join('');
    
    elements.packSelectorGrid.innerHTML = packsHTML;
  }
  
  function filterPackSelector() {
    // TODO: Implement filtering
  }
  
  window.selectPack = function(packId) {
    attachPack(packId);
    closePackSelector();
  };
  
  window.attachPack = async function(packId) {
    if (!state.currentTripId) return;
    
    try {
      const response = await fetch(`api/trips/${state.currentTripId}/attach-pack`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ backpack_id: packId })
      });
      
      if (response.ok) {
        showAttachedPack(packId);
        showToast('Backpack attached successfully!', 'success');
        updateReadinessScore();
      }
    } catch (error) {
      console.error('Failed to attach pack:', error);
      showToast('Failed to attach backpack', 'error');
    }
  };
  
  function createPackForTrip() {
    showToast('Creating optimized pack for this trip...', 'info');
    // TODO: Implement pack creation with trip-specific template
  }
  
  function getPackInfo(packId) {
    if (!packId) return null;
    const pack = state.backpacks.find(p => p.id == packId);
    if (!pack) return null;
    
    return {
      name: pack.name,
      totalWeight: calculatePackWeight(pack)
    };
  }
  
  // ==================== KEYBOARD SHORTCUTS ====================
  
  function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Ignore if typing in input
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
      
      switch(e.key.toLowerCase()) {
        case 'w':
          if (state.viewMode === 'detail' && elements.btnStartWizard) {
            elements.btnStartWizard.click();
            showShortcutToast('Opening wizard...');
          }
          break;
        case 'a':
          if (state.viewMode === 'detail' && elements.btnAttachPack) {
            elements.btnAttachPack.click();
            showShortcutToast('Attaching pack...');
          }
          break;
        case 't':
          if (state.viewMode === 'detail' && elements.btnTimeline) {
            elements.btnTimeline.click();
            showShortcutToast('Opening timeline...');
          }
          break;
        case 's':
          if (state.viewMode === 'detail' && elements.btnShare) {
            elements.btnShare.click();
            showShortcutToast('Sharing trip...');
          }
          break;
        case 'n':
          if (state.viewMode === 'list' && elements.btnNewTrip) {
            elements.btnNewTrip.click();
            showShortcutToast('Creating new trip...');
          }
          break;
        case '/':
          e.preventDefault();
          if (elements.searchInput) {
            elements.searchInput.focus();
            showShortcutToast('Search mode');
          }
          break;
        case 'escape':
          if (elements.packSelectorModal && !elements.packSelectorModal.hidden) {
            closePackSelector();
          }
          break;
      }
    });
  }
  
  function showShortcutToast(message) {
    if (!elements.shortcutsToast) return;
    
    const toastText = elements.shortcutsToast.querySelector('.toast-text');
    if (toastText) {
      toastText.textContent = message;
    }
    
    elements.shortcutsToast.hidden = false;
    
    setTimeout(() => {
      elements.shortcutsToast.hidden = true;
    }, 2000);
  }
  
  // ==================== UTILITIES ====================
  
  function showToast(message, type = 'info') {
    // Use existing toast or create one
    console.log(`[${type.toUpperCase()}] ${message}`);
    // TODO: Implement proper toast notifications
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
  
})();
