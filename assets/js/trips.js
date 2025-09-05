document.addEventListener('DOMContentLoaded', function() {
  'use strict';
  
  console.log('Trips.js: Starting initialization');
  
  // Wait for jQuery and then initialize
  if (typeof $ === 'undefined') {
    console.error('jQuery not loaded!');
    return;
  }
  
  // Fallback for notification function if enhancements haven't loaded yet
  if (!window.showNotification) {
    window.showNotification = function(message, type = 'info') {
      if (window.BTTUtils && window.BTTUtils.showToast) {
        window.BTTUtils.showToast(message, type);
      } else if (window.DuoNotify && window.DuoNotify.show) {
        window.DuoNotify.show(message, type);
      } else {
        console.log(`[${type.toUpperCase()}] ${message}`);
      }
    };
  }
  
  // Use jQuery's ready to ensure everything is loaded
  $(document).ready(function() {
    console.log('Trips.js: jQuery ready, initializing...');
    
    // Set up API references - Use BttApi which now has file upload support
    window.BTTApi = window.BttApi || window.BTTApi || window.API;
    console.log('BTTApi/BttApi available:', !!window.BTTApi);
    console.log('BTTApi has post method:', !!(window.BTTApi && window.BTTApi.post));
    console.log('BTTApi has put method:', !!(window.BTTApi && window.BTTApi.put));
    console.log('BTTApi object:', window.BTTApi);
    
    // Create minimal BTTUtils if missing
    if (typeof window.BTTUtils === 'undefined') {
      window.BTTUtils = {
        escapeHtml: function(str) {
          if (!str) return '';
          const div = document.createElement('div');
          div.textContent = str;
          return div.innerHTML;
        },
        showToast: function(message, type) {
          console.log('Toast:', type, message);
          // Create simple toast
          const toast = $(`<div class="toast toast-${type}">${message}</div>`);
          $('body').append(toast);
          toast.fadeIn();
          setTimeout(() => toast.fadeOut(() => toast.remove()), 3000);
        }
      };
    }

  // State
  const state = {
    trips: [],
    filtered: [],
    backpacks: [],
    activeView: 'my-trips', // 'my-trips' | 'editor'
    formTab: 'basics',      // basics|trail|logistics|conditions|notes
    mode: 'create',         // create|edit|view
    currentId: null,
    existingPhoto: null,
    viewMode: 'grid'        // grid | list
  };

  // Elements
  const els = {};

  async function init(){
    cacheEls();
    bindEvents();
    await Promise.all([loadTrips(), loadBackpacks()]);
    updateGrid();
    updateEmptyState();
    
    // Check URL hash for direct navigation
    const hash = window.location.hash;
    if (hash === '#new') {
      openEditor({mode:'create'});
    } else if (hash.startsWith('#edit-')) {
      const id = hash.replace('#edit-', '');
      openEditor({id, mode:'edit'});
    } else if (hash.startsWith('#view-')) {
      const id = hash.replace('#view-', '');
      openEditor({id, mode:'view'});
    } else {
      switchTopView('my-trips', {focus:false});
    }
    
    // Handle hash changes
    window.addEventListener('hashchange', handleHashChange);
  }

  function cacheEls(){
    // Note: These elements don't exist in the new theme structure
    // Using the actual elements from the page structure
    els.tabMyTrips = null; // No tabs in new design
    els.tabEditor  = null; // No tabs in new design
    els.panelMyTrips = document.getElementById('panel-my-trips');
    els.panelEditor  = document.getElementById('panel-trip-editor');

    els.tripGrid = document.getElementById('trip-grid');
    els.tripsEmpty = document.getElementById('trips-empty');
    els.btnNew = document.getElementById('btn-new-trip');
    els.btnEmptyCreate = document.getElementById('empty-create');
    els.search = document.getElementById('trip-search');
    els.sort = document.getElementById('sort-trips');

    // Form
    els.form = document.getElementById('trip-form');
    els.formStatus = document.getElementById('form-status');
    els.title = document.getElementById('title');
    els.location = document.getElementById('location');
    els.start = document.getElementById('start_date');
    els.end   = document.getElementById('end_date');
    els.tripType = document.getElementById('trip_type');
    els.backpack = document.getElementById('backpack_id');
    els.favorite = document.getElementById('favorite');
    els.completed = document.getElementById('completed');
    els.description = document.getElementById('description');
    els.photoInput = document.getElementById('photo');
    els.photoAltText = document.getElementById('photo_alt_text');
    els.adventureImageDisplay = document.getElementById('adventure-image-display');
    els.uploadOverlay = document.getElementById('upload-overlay');
    els.removePhotoBtn = document.getElementById('btn-remove-photo');
    els.removePhotoField = document.getElementById('remove_photo');
    
    // View toggle elements
    els.viewButtons = document.querySelectorAll('.view-btn');
    els.gridButton = document.querySelector('.view-btn[data-mode="grid"]');
    els.listButton = document.querySelector('.view-btn[data-mode="list"]');
    els.distance = document.getElementById('distance');
    els.unit = document.getElementById('distance_unit');
    els.elevation = document.getElementById('elevation_gain');
    els.difficulty = document.getElementById('difficulty');
    els.permitReq = document.getElementById('permit_required');
    els.permitCost = document.getElementById('permit_cost');
    els.permitInfo = document.getElementById('permit_info');
    els.trailheadParking = document.getElementById('trailhead_parking');
    els.parkingCost = document.getElementById('parking_cost');
    els.cellCoverage = document.getElementById('cell_coverage');
    els.crowdLevel = document.getElementById('crowd_level');
    els.waterSources = document.getElementById('water_sources');
    els.trailConditions = document.getElementById('trail_conditions');
    els.preTripNotes = document.getElementById('pre_trip_notes');
    els.postTripNotes = document.getElementById('post_trip_notes');
    els.lessons = document.getElementById('lessons_learned');

    els.btnSave   = document.getElementById('btn-save-trip');
    els.btnCancel = document.getElementById('btn-cancel-edit');
    els.btnDelete = document.getElementById('btn-delete-trip');

    // Inner form tabs
    els.tabBtnBasics = document.getElementById('tab-btn-basics');
    els.tabBtnTrail = document.getElementById('tab-btn-trail');
    els.tabBtnLogistics = document.getElementById('tab-btn-logistics');
    els.tabBtnConditions = document.getElementById('tab-btn-conditions');
    els.tabBtnNotes = document.getElementById('tab-btn-notes');
    els.tabBtnPacking = document.getElementById('tab-btn-packing');
    els.panelBasics = document.getElementById('tab-panel-basics');
    els.panelTrail = document.getElementById('tab-panel-trail');
    els.panelLogistics = document.getElementById('tab-panel-logistics');
    els.panelConditions = document.getElementById('tab-panel-conditions');
    els.panelNotes = document.getElementById('tab-panel-notes');
    els.panelPacking = document.getElementById('tab-panel-packing');

    // Insights (updated for new theme structure)
    els.insightDuration = document.getElementById('insight-duration');
    els.insightDistance = document.getElementById('insight-distance');
    els.insightElevation = document.getElementById('insight-elevation');
    els.editorTitle = document.getElementById('trip-editor-title');
  }

  function bindEvents(){
    // Skip top tab events since they don't exist in new design
    // The page is now always showing the trips list view

    // New Trip
    els.btnNew.addEventListener('click', () => openEditor({mode:'create'}));
    if (els.btnEmptyCreate) els.btnEmptyCreate.addEventListener('click', () => openEditor({mode:'create'}));

    // Search + sort
    els.search.addEventListener('input', debounce(filterTrips, 200));
    els.sort.addEventListener('change', () => { sortTrips(); updateGrid(); });
    
    // Keyboard navigation for search
    els.search.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        els.search.value = '';
        filterTrips();
      }
    });

    // Inner tabs
    els.tabBtnBasics.addEventListener('click', () => switchFormTab('basics'));
    els.tabBtnTrail.addEventListener('click', () => switchFormTab('trail'));
    els.tabBtnLogistics.addEventListener('click', () => switchFormTab('logistics'));
    els.tabBtnConditions.addEventListener('click', () => switchFormTab('conditions'));
    els.tabBtnNotes.addEventListener('click', () => switchFormTab('notes'));
    els.tabBtnPacking.addEventListener('click', () => switchFormTab('packing'));
    
    // Keyboard navigation for inner tabs
    const innerTabs = [els.tabBtnBasics, els.tabBtnTrail, els.tabBtnLogistics, els.tabBtnConditions, els.tabBtnNotes, els.tabBtnPacking];
    innerTabs.forEach(tab => {
      tab.addEventListener('keydown', handleInnerTabKeydown);
    });

    // Form
    els.form.addEventListener('submit', onSave);
    els.btnCancel.addEventListener('click', () => switchTopView('my-trips'));
    els.btnDelete.addEventListener('click', onDelete);

    // Form changes -> insights and adventure display
    [els.title, els.location, els.start, els.end, els.distance, els.unit, els.elevation, els.tripType,
     els.favorite, els.completed, els.difficulty].forEach(el => {
      if (!el) return; el.addEventListener('input', () => {
        updateInsights();
        updateAdventureDisplay();
      });
    });
    
    // Special handling for backpack changes - refresh packing list
    if (els.backpack) {
      els.backpack.addEventListener('change', async () => {
        updateInsights();
        updateAdventureDisplay();
        
        // Always refresh packing list when backpack changes (if TripPacking available)
        if (window.TripPacking) {
          const tripId = document.getElementById('trip-id').value;
          const backpackId = els.backpack.value;
          
          if (tripId && backpackId) {
            console.log('Backpack changed to:', backpackId, '- refreshing packing list for trip:', tripId);
            showNotification('🎒 Loading items from selected backpack...', 'info', 2000);
            
            // Always save the backpack association first, then load packing list
            console.log('Saving trip to associate backpack...');
            
            try {
              // Save the trip to ensure backpack association is persisted
              await onSave();
              
              // After saving, get the current trip ID (might be new for create mode)
              const currentTripId = document.getElementById('trip-id').value;
              
              if (currentTripId) {
                console.log('Trip saved, loading packing list for trip:', currentTripId, 'with backpack:', backpackId);
                await window.TripPacking.loadPackingList(currentTripId);
                showNotification('✅ Packing list updated with backpack items!', 'success', 3000);
              }
            } catch (error) {
              console.error('Failed to save trip and load packing list:', error);
              showNotification('⚠️ Could not load packing list. Please save the trip first.', 'warning', 4000);
            }
          } else if (tripId && !backpackId) {
            // No backpack selected - show empty state
            console.log('No backpack selected - showing empty state');
            const emptyState = document.getElementById('packing-empty-state');
            const packingContent = document.getElementById('packing-content');
            if (emptyState && packingContent) {
              emptyState.hidden = false;
              packingContent.style.display = 'none';
            }
            if (window.TripPacking) {
              window.TripPacking.clearData();
            }
          }
        }
      });
    }
    
    // Photo upload handling
    if (els.photoInput) {
      els.photoInput.addEventListener('change', (e) => {
        handlePhotoSelect(e);
        // Remove duplicate call - handlePhotoSelect already calls updateAdventureDisplay
      });
    }
    
    // Remove photo button
    if (els.removePhotoBtn) {
      els.removePhotoBtn.addEventListener('click', (e) => {
        handlePhotoRemove(e);
      });
    }
    
    // Adventure image container click (handled by onclick in HTML)
    // No additional JavaScript needed since we use onclick attribute
    
    // View toggle buttons
    if (els.viewButtons) {
      els.viewButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          const mode = e.target.getAttribute('data-mode');
          switchViewMode(mode);
        });
      });
    }
    
    // Photo alt text changes
    if (els.photoAltText) {
      els.photoAltText.addEventListener('input', updateAdventureDisplay);
    }
    
    // Toggle switches
    const favoriteToggle = document.getElementById('favorite-toggle');
    const completedToggle = document.getElementById('completed-toggle');
    
    if (favoriteToggle) {
      favoriteToggle.addEventListener('change', (e) => {
        document.getElementById('favorite').value = e.target.checked ? '1' : '0';
        updateAdventureDisplay();
      });
    }
    
    if (completedToggle) {
      completedToggle.addEventListener('change', (e) => {
        document.getElementById('completed').value = e.target.checked ? '1' : '0';
        updateAdventureDisplay();
      });
    }
    
    // Event delegation for trip card actions - handles dynamically added elements
    els.tripGrid.addEventListener('click', function(event) {
      // Check if a button was clicked
      const button = event.target.closest('button[data-action]');
      const card = event.target.closest('.trip-card');
      
      if (!card) return;
      
      const id = card.getAttribute('data-id');
      
      if (button) {
        // Handle button clicks
        event.stopPropagation();
        const action = button.getAttribute('data-action');
        
        // Prevent double processing
        if (button.disabled) return;
        button.disabled = true;
        
        setTimeout(() => { button.disabled = false; }, 150);
        
        switch(action) {
          case 'edit':
            openEditor({id, mode:'edit'});
            break;
          case 'view':
            openEditor({id, mode:'view'});
            break;
          case 'delete':
            event.preventDefault(); // Prevent card click
            const title = card.querySelector('h3')?.textContent || 'this trip';
            if (confirm(`Delete ${title}?`)) {
              handleDeleteTrip(id);
            }
            break;
        }
      } else {
        // Card was clicked (not a button) - open editor
        openEditor({id, mode:'edit'});
      }
    });
  }

  async function loadTrips(){
    try {
      console.log('Loading trips via ajax-handler...');
      // Use ajax-handler directly for better performance
      const response = await fetch('ajax-handler.php?route=trips', {
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('API response:', data);
      console.log('API response type:', typeof data, 'isArray:', Array.isArray(data));
      
      // Handle successful vs error responses properly
      if (data && data.success === false) {
        console.error('API returned error:', data.message);
        throw new Error(data.message);
      }
      
      state.trips = Array.isArray(data) ? data : (data ? [data] : []);
      state.filtered = [...state.trips];
      console.log('Trips loaded:', state.trips.length);
      console.log('First trip sample:', state.trips[0]);
    } catch (e) {
      console.error('Failed to load trips:', e);
      setGridError('Failed to load trips: ' + e.message);
    }
  }

  async function loadBackpacks(){
    try {
      console.log('Loading backpacks via ajax-handler...');
      // Use ajax-handler directly for better performance and consistency
      const response = await fetch('ajax-handler.php?route=backpacks', {
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('Backpacks API response:', data);
      
      // Handle successful vs error responses properly
      if (data && data.success === false) {
        console.error('Backpacks API returned error:', data.message);
        throw new Error(data.message);
      }
      
      state.backpacks = Array.isArray(data) ? data : (data ? [data] : []);
      console.log('Backpacks loaded:', state.backpacks.length);
      
      // Fill select
      if (els.backpack){
        const opts = ['<option value="">No backpack selected</option>'].concat(
          state.backpacks.map(b => `<option value="${b.id}">${BTTUtils.escapeHtml(b.name)}</option>`) );
        els.backpack.innerHTML = opts.join('');
        console.log('Backpack dropdown populated with', state.backpacks.length, 'backpacks');
      }
    } catch(e) { 
      console.error('Failed to load backpacks:', e);
      // Still provide empty dropdown
      if (els.backpack){
        els.backpack.innerHTML = '<option value="">No backpack selected</option>';
      }
    }
  }

  function setGridLoading(isLoading){
    els.tripGrid.setAttribute('aria-busy', isLoading ? 'true' : 'false');
  }

  function setGridError(msg){
    els.tripGrid.innerHTML = `<div class="loading-spinner"><p class="alert alert-error">${BTTUtils.escapeHtml(msg)}</p></div>`;
  }

  function filterTrips(){
    const q = (els.search.value || '').toLowerCase();
    state.filtered = state.trips.filter(t => {
      const name = (t.title||'').toLowerCase();
      const loc = (t.location||'').toLowerCase();
      return !q || name.includes(q) || loc.includes(q);
    });
    sortTrips();
    updateGrid();
    updateEmptyState();
  }

  function sortTrips(){
    const mode = els.sort.value;
    const keyRecent = (a,b) => new Date(b.created_at||0) - new Date(a.created_at||0);
    const keyName   = (a,b) => (a.title||'').localeCompare(b.title||'');
    const keyDate   = (a,b) => new Date(a.start_date||0) - new Date(b.start_date||0);
    if (mode === 'name') state.filtered.sort(keyName);
    else if (mode === 'date') state.filtered.sort(keyDate);
    else state.filtered.sort(keyRecent);
  }

  function updateGrid(){
    console.log('updateGrid called, filtered trips:', state.filtered.length);
    if (!state.filtered.length){
      console.log('No trips to display, showing empty state');
      els.tripGrid.innerHTML = '';
      return;
    }
    
    if (state.viewMode === 'list') {
      const html = state.filtered.map(renderTripListItem).join('');
      els.tripGrid.innerHTML = html;
      els.tripGrid.className = 'trips-list';
    } else {
      const html = state.filtered.map(renderTripCard).join('');
      els.tripGrid.innerHTML = html;
      els.tripGrid.className = 'cards-grid cards-grid-3';
    }
    
    console.log('Generated HTML for', state.filtered.length, 'trips in', state.viewMode, 'mode');
    // Events are now handled by delegation in bindEvents()
  }
  
  function switchViewMode(mode) {
    state.viewMode = mode;
    
    // Update button states
    els.viewButtons.forEach(btn => {
      if (btn.getAttribute('data-mode') === mode) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
    
    // Re-render with new view mode
    updateGrid();
  }

  function updateEmptyState(){
    els.tripsEmpty.hidden = state.trips.length !== 0;
  }

  function renderTripCard(trip){
    // Handle photo URL properly - ensure no double prefixing
    let photoUrl;
    if (trip.photo_path) {
      if (trip.photo_path.startsWith('http')) {
        photoUrl = trip.photo_path;
      } else if (trip.photo_path.startsWith('assets/img/')) {
        photoUrl = trip.photo_path; // Already has full path
      } else {
        photoUrl = `assets/img/trips/${trip.photo_path}`; // Add prefix only if needed
      }
    } else {
      photoUrl = trip.default_image_url || 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=400&h=300&fit=crop';
    }
    const alt = BTTUtils.escapeHtml(trip.default_image_alt || trip.photo_alt_text || 'Trip photo');
    const title = BTTUtils.escapeHtml(trip.title || 'Untitled Trip');
    const loc = BTTUtils.escapeHtml(trip.location || '');

    // Duration
    let durationText = '-';
    let dateText = '';
    if (trip.start_date){
      const s = new Date(trip.start_date);
      const e = trip.end_date ? new Date(trip.end_date) : s;
      const days = Math.floor((e - s) / (1000*60*60*24)) + 1;
      durationText = days === 1 ? 'Day hike' : `${days} days`;
      
      // Format dates
      const startMonth = s.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const endMonth = e.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      dateText = s.getTime() === e.getTime() ? startMonth : `${startMonth} - ${endMonth}`;
    }

    const distance = trip.distance ? `${trip.distance} ${trip.distance_unit || 'miles'}` : '-';
    const elevation = trip.elevation_gain ? `${Number(trip.elevation_gain).toLocaleString()} ft` : '-';
    
    // Trip type formatting
    const tripTypeLabels = {
      'day_hike': 'Day Hike',
      'overnight': 'Overnight',
      'weekend': 'Weekend',
      'section_hike': 'Section',
      'thru_hike': 'Thru-Hike'
    };
    const tripTypeLabel = trip.trip_type ? tripTypeLabels[trip.trip_type] || trip.trip_type : '';
    
    // Difficulty badge
    const difficultyBadge = trip.difficulty ? `<span class="trip-difficulty-badge difficulty-${trip.difficulty}">${trip.difficulty}</span>` : '';

    return `
    <article class="trip-card trip-card-clickable" data-id="${trip.id}" tabindex="0" aria-labelledby="trip-${trip.id}-title" role="button" aria-label="Click to edit ${title}">
      <div class="trip-card-header">
        <h3 id="trip-${trip.id}-title">${title}</h3>
        <div class="trip-card-actions">
          <button type="button" data-action="edit" title="Edit" aria-label="Edit ${title}">✏️</button>
          <button type="button" data-action="view" title="View" aria-label="View ${title}">👁️</button>
          <button type="button" data-action="delete" title="Delete" aria-label="Delete ${title}">🗑️</button>
        </div>
      </div>
      
      <div class="trip-card-image">
        <img src="${photoUrl}" alt="${alt}" />
        ${tripTypeLabel ? `<span class="trip-type-badge">${tripTypeLabel}</span>` : ''}
      </div>
      
      ${loc ? `<div class="trip-card-location"><span class="icon">📍</span>${loc}</div>` : ''}
      
      ${dateText ? `<div class="trip-dates"><span class="date-icon">📅</span>${dateText}</div>` : ''}
      
      <div class="trip-card-stats">
        <div class="trip-stat">
          <span class="trip-stat-icon">⏱️</span>
          <div class="trip-stat-content">
            <span class="trip-stat-value">${durationText}</span>
            <span class="trip-stat-label">Duration</span>
          </div>
        </div>
        <div class="trip-stat">
          <span class="trip-stat-icon">🥾</span>
          <div class="trip-stat-content">
            <span class="trip-stat-value">${distance}</span>
            <span class="trip-stat-label">Distance</span>
          </div>
        </div>
        <div class="trip-stat">
          <span class="trip-stat-icon">📈</span>
          <div class="trip-stat-content">
            <span class="trip-stat-value">${elevation}</span>
            <span class="trip-stat-label">Elevation</span>
          </div>
        </div>
      </div>
      
      <div class="trip-card-footer">
        <div class="trip-status">
          <span class="trip-status-item ${trip.completed ? 'status-completed' : 'status-planning'}">
            ${trip.completed ? '✓ Completed' : '📝 Planning'}
          </span>
          ${trip.favorite ? '<span class="trip-status-item status-favorite">⭐ Favorite</span>' : ''}
        </div>
        ${difficultyBadge}
      </div>
    </article>`;
  }
  
  function renderTripListItem(trip) {
    // Handle photo URL properly - ensure no double prefixing
    let photoUrl;
    if (trip.photo_path) {
      if (trip.photo_path.startsWith('http')) {
        photoUrl = trip.photo_path;
      } else if (trip.photo_path.startsWith('assets/img/')) {
        photoUrl = trip.photo_path; // Already has full path
      } else {
        photoUrl = `assets/img/trips/${trip.photo_path}`; // Add prefix only if needed
      }
    } else {
      photoUrl = trip.default_image_url || 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=100&h=100&fit=crop';
    }
    const alt = BTTUtils.escapeHtml(trip.default_image_alt || trip.photo_alt_text || 'Trip photo');
    const title = BTTUtils.escapeHtml(trip.title || 'Untitled Trip');
    const loc = BTTUtils.escapeHtml(trip.location || '');

    // Duration
    let durationText = '-';
    let dateText = '';
    if (trip.start_date){
      const s = new Date(trip.start_date);
      const e = trip.end_date ? new Date(trip.end_date) : s;
      const days = Math.floor((e - s)/(1000*60*60*24)) + 1;
      durationText = days === 1 ? 'Day hike' : `${days} days`;
      dateText = s.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Stats
    const diffText = trip.difficulty ? `${trip.difficulty}` : '';
    const distText = trip.distance ? `${trip.distance} ${trip.distance_unit || 'miles'}` : '';
    const elevText = trip.elevation_gain ? `${Number(trip.elevation_gain).toLocaleString()} ft` : '';

    return `
    <div class="trip-list-item" data-id="${trip.id}" role="button" tabindex="0" aria-label="Trip: ${title}">
      <div class="trip-list-image">
        <img src="${photoUrl}" alt="${alt}" loading="lazy" class="trip-photo-small" />
      </div>
      <div class="trip-list-content">
        <div class="trip-list-main">
          <div class="trip-list-header">
            <h3 class="trip-list-title">${title}</h3>
            <div class="trip-list-badges">
              ${trip.favorite ? '<span class="trip-badge badge-favorite" title="Favorite">⭐</span>' : ''}
              ${trip.completed ? '<span class="trip-badge badge-completed" title="Completed">✅</span>' : ''}
            </div>
          </div>
          ${loc ? `<p class="trip-list-location">📍 ${loc}</p>` : ''}
          <div class="trip-list-meta">
            <span class="trip-list-duration">${durationText}</span>
            ${dateText ? `<span class="trip-list-date">${dateText}</span>` : ''}
            ${diffText ? `<span class="trip-list-difficulty">${diffText}</span>` : ''}
            ${distText ? `<span class="trip-list-distance">${distText}</span>` : ''}
            ${elevText ? `<span class="trip-list-elevation">${elevText}</span>` : ''}
          </div>
        </div>
        <div class="trip-list-actions">
          <button class="trip-action-btn" data-action="edit" title="Edit trip" aria-label="Edit ${title}">✏️</button>
          <button class="trip-action-btn trip-delete-btn" data-action="delete" title="Delete trip" aria-label="Delete ${title}">🗑️</button>
        </div>
      </div>
    </div>`;
  }

  function switchTopView(view, {focus=true}={}){
    state.activeView = view;
    const isEditor = view === 'editor';
    
    // Hide/show panels based on view
    if (els.panelMyTrips) {
      els.panelMyTrips.classList.toggle('active', !isEditor);
      els.panelMyTrips.style.display = !isEditor ? 'block' : 'none';
    }
    if (els.panelEditor) {
      els.panelEditor.classList.toggle('active', isEditor);
      els.panelEditor.style.display = isEditor ? 'block' : 'none';
      els.panelEditor.hidden = !isEditor;
    }
  }

  function switchFormTab(tab){
    state.formTab = tab;
    const mapping = {
      basics: [els.tabBtnBasics, els.panelBasics],
      trail: [els.tabBtnTrail, els.panelTrail],
      logistics: [els.tabBtnLogistics, els.panelLogistics],
      conditions: [els.tabBtnConditions, els.panelConditions],
      notes: [els.tabBtnNotes, els.panelNotes],
      packing: [els.tabBtnPacking, els.panelPacking]
    };
    
    // Reset all progress steps
    document.querySelectorAll('.progress-step').forEach(step => {
      step.classList.remove('active');
      const btn = step.querySelector('.step-btn');
      if (btn) btn.setAttribute('aria-selected', 'false');
    });
    
    // Reset all panels
    [els.panelBasics, els.panelTrail, els.panelLogistics, els.panelConditions, els.panelNotes, els.panelPacking].forEach(p => {
      if (p) p.hidden = true;
    });
    
    // Activate the selected tab
    const [btn, panel] = mapping[tab];
    if (btn && panel) {
      // Find the parent progress step and activate it
      const progressStep = btn.closest('.progress-step');
      if (progressStep) {
        progressStep.classList.add('active');
      }
      
      btn.setAttribute('aria-selected', 'true');
      panel.hidden = false;
      
      // Special handling for packing tab
      if (tab === 'packing') {
        initializePackingTab();
      }
      
      // Update the adventure title display
      updateAdventureDisplay();
    }
  }

  // Initialize packing tab when it's first opened
  async function initializePackingTab() {
    console.log('🎒 Initializing packing tab...');
    
    // Check if TripPacking module is available
    if (!window.TripPacking) {
      console.warn('TripPacking module not available');
      showEmptyPackingState('Packing module not loaded');
      return;
    }
    
    // Get current trip ID and backpack ID
    const tripId = document.getElementById('trip-id').value;
    const backpackId = els.backpack ? els.backpack.value : null;
    
    console.log('🔍 Packing tab state:', {
      tripId: tripId || 'NONE',
      backpackId: backpackId || 'NONE',
      mode: state.mode
    });
    
    // If we have a backpack selected but no trip ID (new trip), save first
    if (!tripId && backpackId) {
      console.log('📝 New trip with backpack - saving first...');
      showNotification('💾 Saving trip to load packing list...', 'info', 2000);
      
      try {
        await onSave();
        const newTripId = document.getElementById('trip-id').value;
        
        if (newTripId) {
          console.log('✅ Trip saved with ID:', newTripId);
          window.TripPacking.setTripId(newTripId);
          await window.TripPacking.loadPackingList(newTripId);
          showNotification('🎒 Packing list loaded!', 'success', 3000);
          return;
        }
      } catch (error) {
        console.error('Failed to save trip for packing:', error);
        showEmptyPackingState('Please save your trip first to see packing list');
        return;
      }
    }
    
    // Set the trip ID in the packing module
    if (tripId) {
      window.TripPacking.setTripId(tripId);
      
      // Load packing list if trip has a backpack
      if (backpackId) {
        console.log('🔄 Loading packing list for existing trip:', tripId, 'with backpack:', backpackId);
        showNotification('🎒 Loading your packing checklist...', 'info', 2000);
        await window.TripPacking.loadPackingList(tripId);
      } else {
        console.log('No backpack selected - showing empty state');
        showEmptyPackingState('Select a backpack in the Basic Info tab to see your packing list');
      }
    } else {
      // New trip - show helpful message
      console.log('New trip - showing empty state with guidance');
      showEmptyPackingState('Save your trip with a backpack selection to create a packing list');
    }
  }
  
  // Helper function to show empty packing state with custom message
  function showEmptyPackingState(message = 'No backpack selected') {
    const emptyState = document.getElementById('packing-empty-state');
    const packingContent = document.getElementById('packing-content');
    
    if (emptyState) {
      emptyState.hidden = false;
      // Update the message if there's a subtext element
      const subtextElement = emptyState.querySelector('.empty-subtext');
      if (subtextElement) {
        subtextElement.textContent = message;
      }
    }
    
    if (packingContent) {
      packingContent.style.display = 'none';
    }
    
    // Clear any existing packing data
    if (window.TripPacking) {
      window.TripPacking.clearData();
    }
  }

  function updateAdventureDisplay() {
    // Update the adventure title overlay with current form values
    const titleElement = document.querySelector('.adventure-name-display');
    const locationElement = document.querySelector('.adventure-location-display');
    
    // Update title
    if (titleElement && els.title && els.title.value) {
      titleElement.textContent = els.title.value;
    } else if (titleElement) {
      titleElement.textContent = state.mode === 'create' ? 'New Adventure' : 'Adventure';
    }
    
    // Update location
    if (locationElement && els.location && els.location.value) {
      locationElement.textContent = els.location.value;
    } else if (locationElement) {
      locationElement.textContent = '';
    }
    
    // Update stat chips
    const durationChip = document.getElementById('duration-chip');
    const distanceChip = document.getElementById('distance-chip');
    const difficultyChip = document.getElementById('difficulty-chip');
    
    // Duration chip
    if (durationChip && els.start && els.start.value) {
      let durationText = 'Duration';
      if (els.start.value) {
        const s = new Date(els.start.value);
        const e = els.end && els.end.value ? new Date(els.end.value) : s;
        const days = Math.floor((e - s) / (1000 * 60 * 60 * 24)) + 1;
        durationText = days === 1 ? '📅 Day hike' : `📅 ${days} days`;
      }
      durationChip.textContent = durationText;
    }
    
    // Distance chip
    if (distanceChip && els.distance && els.distance.value) {
      const unit = els.unit ? els.unit.value : 'miles';
      distanceChip.textContent = `🥾 ${els.distance.value} ${unit}`;
    } else if (distanceChip) {
      distanceChip.textContent = '🥾 Distance';
    }
    
    // Difficulty chip  
    if (difficultyChip && els.difficulty && els.difficulty.value) {
      const difficultyLabels = {
        'easy': 'Easy',
        'moderate': 'Moderate', 
        'hard': 'Hard',
        'expert': 'Expert'
      };
      const label = difficultyLabels[els.difficulty.value] || els.difficulty.value;
      difficultyChip.textContent = `💪 ${label}`;
    } else if (difficultyChip) {
      difficultyChip.textContent = '💪 Difficulty';
    }
    
    // Update adventure image display
    const imageContainer = document.getElementById('adventure-image-display');
    const hasPhoto = (els.photoInput && els.photoInput.files.length > 0) || (state.existingPhoto && els.removePhotoField.value !== '1');
    
    console.log('DEBUG updateAdventureDisplay:', {
      imageContainer: !!imageContainer,
      photoInput: !!els.photoInput,
      hasPhotoInput: !!(els.photoInput && els.photoInput.files.length > 0),
      filesCount: els.photoInput ? els.photoInput.files.length : 0,
      hasExistingPhoto: !!state.existingPhoto,
      removePhotoField: els.removePhotoField ? els.removePhotoField.value : 'N/A',
      hasPhoto: hasPhoto,
      uploadOverlay: !!els.uploadOverlay
    });
    
    if (imageContainer) {
      const placeholderImage = imageContainer.querySelector('.placeholder-image');
      let adventureImage = imageContainer.querySelector('.adventure-image');
      
      if (hasPhoto) {
        // Hide placeholder and show image
        if (placeholderImage) {
          placeholderImage.style.display = 'none';
        }
        
        // Show upload overlay and remove button
        if (els.uploadOverlay) {
          els.uploadOverlay.classList.remove('hidden');
        }
        if (els.removePhotoBtn) {
          els.removePhotoBtn.classList.remove('hidden');
        }
        
        // Create or update adventure image
        if (!adventureImage) {
          adventureImage = document.createElement('img');
          adventureImage.className = 'adventure-image';
          adventureImage.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 1.5rem;';
          imageContainer.appendChild(adventureImage);
        }
        
        // Update image source
        if (els.photoInput && els.photoInput.files.length > 0) {
          // New photo selected
          console.log('DEBUG: Loading new photo file');
          const reader = new FileReader();
          reader.onload = function(e) {
            console.log('DEBUG: FileReader loaded, setting src to:', e.target.result.substring(0, 50) + '...');
            adventureImage.src = e.target.result;
            adventureImage.style.display = 'block'; // Ensure image is visible
          };
          reader.onerror = function() {
            console.error('DEBUG: FileReader error');
            showNotification('Error reading image file', 'error');
          };
          reader.readAsDataURL(els.photoInput.files[0]);
        } else if (state.existingPhoto) {
          // Existing photo from database
          console.log('DEBUG: Using existing photo:', state.existingPhoto);
          adventureImage.src = state.existingPhoto;
          adventureImage.style.display = 'block'; // Ensure image is visible
        }
        
        adventureImage.alt = els.photoAltText ? els.photoAltText.value : 'Adventure photo';
      } else {
        // No photo - show placeholder
        if (placeholderImage) {
          placeholderImage.style.display = 'block';
        }
        
        // Hide upload overlay and remove button
        if (els.uploadOverlay) {
          els.uploadOverlay.classList.add('hidden');
        }
        if (els.removePhotoBtn) {
          els.removePhotoBtn.classList.add('hidden');
        }
        
        if (adventureImage) {
          adventureImage.remove();
        }
      }
    }
  }

  function openEditor({id=null, mode='create'}){
    state.mode = mode;
    state.currentId = id;
    switchTopView('editor');
    if (mode === 'create'){
      els.editorTitle.textContent = 'New Trip';
      resetForm();
      switchFormTab('basics');
      updateInsights();
      updateAdventureDisplay();
      setReadOnly(false);
      return;
    }
    // edit or view
    loadTripById(id).then(trip => {
      populateForm(trip);
      els.editorTitle.textContent = mode === 'view' ? 'Trip Summary' : 'Edit Trip';
      switchFormTab('basics');
      updateInsights();
      updateAdventureDisplay();
      setReadOnly(mode === 'view');
    }).catch((error) => {
      console.error('Failed to load trip:', error);
      showNotification('Failed to load trip', 'error');
      switchTopView('my-trips');
    });
  }

  async function loadTripById(id){
    const response = await fetch(`ajax-handler.php?route=trips&id=${id}`, {
      credentials: 'include'
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data && data.success === false) {
      throw new Error(data.message);
    }
    
    return Array.isArray(data) ? data[0] : data;
  }

  function populateForm(t){
    els.form.reset();
    document.getElementById('trip-id').value = t.id;
    els.title.value = t.title || '';
    els.location.value = t.location || '';
    els.start.value = t.start_date || '';
    els.end.value = t.end_date || '';
    els.tripType.value = t.trip_type || '';
    els.backpack.value = t.backpack_id || '';
    els.favorite.value = t.favorite ? '1' : '0';
    els.completed.value = t.completed ? '1' : '0';
    
    // Update toggle switches
    const favoriteToggle = document.getElementById('favorite-toggle');
    const completedToggle = document.getElementById('completed-toggle');
    
    if (favoriteToggle) {
      favoriteToggle.checked = t.favorite ? true : false;
    }
    if (completedToggle) {
      completedToggle.checked = t.completed ? true : false;
    }
    els.description.value = t.description || '';
    els.photoAltText.value = t.photo_alt_text || '';
    
    // Show existing photo if available
    if (t.photo_path) {
      console.log('DEBUG: Original photo_path from DB:', t.photo_path);
      // Set the existing photo state - avoid double prefixing
      if (t.photo_path.startsWith('http')) {
        state.existingPhoto = t.photo_path;
      } else if (t.photo_path.startsWith('assets/img/')) {
        state.existingPhoto = t.photo_path; // Already has full path
      } else {
        state.existingPhoto = `assets/img/trips/${t.photo_path}`; // Add prefix only if needed
      }
      console.log('DEBUG: Final existingPhoto value:', state.existingPhoto);
      // Clear any file input and reset remove flag
      els.photoInput.value = '';
      els.removePhotoField.value = '0';
    } else {
      state.existingPhoto = null;
      els.removePhotoField.value = '0';
    }
    
    els.distance.value = t.distance || '';
    els.unit.value = t.distance_unit || 'miles';
    els.elevation.value = t.elevation_gain || '';
    els.difficulty.value = t.difficulty || '';
    els.permitReq.value = t.permit_required ? '1' : '0';
    els.permitCost.value = t.permit_cost || '';
    els.permitInfo.value = t.permit_info || '';
    els.trailheadParking.value = t.trailhead_parking || '';
    els.parkingCost.value = t.parking_cost || '';
    els.cellCoverage.value = t.cell_coverage || '';
    els.crowdLevel.value = t.crowd_level || '';
    els.waterSources.value = t.water_sources || '';
    els.trailConditions.value = t.trail_conditions || '';
    els.preTripNotes.value = t.pre_trip_notes || '';
    els.postTripNotes.value = t.post_trip_notes || '';
    els.lessons.value = t.lessons_learned || '';
    
    // Initialize packing module if available
    if (window.TripPacking) {
      window.TripPacking.setTripId(t.id);
      // Clear any previous packing data when loading a different trip
      window.TripPacking.clearData();
    }
  }

  function serializeForm(){
    const data = new FormData(els.form);
    const obj = Object.fromEntries(data.entries());
    // Normalize booleans/numbers expected by API
    // Handle backpack_id specially - empty string or 0 should be null
    if (obj.backpack_id && obj.backpack_id !== '' && obj.backpack_id !== '0') {
      obj.backpack_id = parseInt(obj.backpack_id, 10) || null;
    } else {
      obj.backpack_id = null;
    }
    obj.permit_required = parseInt(obj.permit_required, 10) || 0;
    obj.favorite = parseInt(obj.favorite, 10) || 0;
    obj.completed = parseInt(obj.completed, 10) || 0;
    if (obj.distance) obj.distance = parseFloat(obj.distance) || 0;
    if (obj.elevation_gain) obj.elevation_gain = parseFloat(obj.elevation_gain) || 0;
    if (obj.permit_cost) obj.permit_cost = parseFloat(obj.permit_cost) || 0;
    if (obj.parking_cost) obj.parking_cost = parseFloat(obj.parking_cost) || 0;
    
    // Ensure all fields have defaults for API compatibility
    obj.distance = obj.distance || 0;
    obj.elevation_gain = obj.elevation_gain || 0;
    
    return obj;
  }

  async function onSave(e){
    if (e) e.preventDefault();
    
    // Clear previous errors
    clearFormErrors();
    
    // Validate required fields
    const errors = validateForm();
    if (errors.length > 0) {
      showFormErrors(errors);
      return;
    }
    
    const id = document.getElementById('trip-id').value;
    const formData = new FormData(els.form);
    
    // Handle file upload if a new photo is selected
    const photoFile = els.photoInput.files[0];
    const isRemoving = els.removePhotoField.value === '1';
    const hasPhoto = !isRemoving && photoFile && photoFile.size > 0;
    
    // Get form data as object
    const body = serializeForm();
    
    // IMPORTANT: If we're uploading a new photo, clear the remove_photo flag
    if (hasPhoto && photoFile) {
      body.remove_photo = '0'; // Clear removal flag when uploading
      console.log('🔧 Cleared remove_photo flag for new photo upload');
    }
    
    // Remember current tab
    const currentTab = state.formTab;
    
    // Disable form during save
    setFormBusy(true);
    
    try {
      console.log('Saving trip:', body);
      let newTripId = null;
      let result = null;
      
      // Use the correct API endpoint based on whether we have a photo or are removing one
      if (hasPhoto && photoFile) {
        // Use main API for photo uploads - it has handlePhotoUpload function
        console.log('\n=== PHOTO UPLOAD DEBUG START ===');
        console.log('Using main API for photo upload');
        console.log('photoFile details:', {
          name: photoFile.name,
          size: photoFile.size,
          type: photoFile.type,
          lastModified: photoFile.lastModified
        });
        console.log('Full body data:', body);
        console.log('photo_alt_text in body:', body.photo_alt_text);
        
        // Log FormData that will be sent
        console.log('Creating FormData...');
        const debugFormData = new FormData();
        for (const [key, value] of Object.entries(body)) {
          if (value !== null && value !== undefined) {
            debugFormData.append(key, value);
            console.log(`FormData append: ${key} = ${value}`);
          }
        }
        debugFormData.append('photo', photoFile);
        console.log('FormData append: photo = [File object]');
        
        const files = { photo: photoFile };
        console.log('Calling BTTApi.' + (id ? 'put' : 'post') + '...');
        console.log('BTTApi exists?', !!window.BTTApi);
        console.log('BTT config:', window.BTT);
        
        try {
          result = id ? await BTTApi.put('trips', id, body, files) : await BTTApi.post('trips', body, files);
          console.log('API result received:', result);
          console.log('Result type:', typeof result);
          console.log('Result photo_path:', result?.photo_path);
          console.log('Result photo_alt_text:', result?.photo_alt_text);
          
          // Show detailed success confirmation
          if (result && result.id) {
            const photoStatus = result.photo_path ? `✅ Photo: ${result.photo_path}` : '📷 No photo';
            showNotification(`✅ Trip saved successfully!\n📝 ID: ${result.id}\n📍 Title: ${result.title || 'Untitled'}\n${photoStatus}`, 'success', 5000);
          } else {
            showNotification('✅ Trip saved (no confirmation data returned)', 'success', 3000);
          }
        } catch (apiError) {
          console.error('BTTApi call failed:', apiError);
          console.error('Error details:', {
            message: apiError.message,
            stack: apiError.stack
          });
          
          // Show detailed error message
          let errorMsg = '❌ Photo upload failed!\n';
          if (apiError.message.includes('404')) {
            errorMsg += '🔍 Cause: API endpoint not found\n💡 Check: Main API may not be accessible';
          } else if (apiError.message.includes('413')) {
            errorMsg += '📂 Cause: File too large\n💡 Check: Photo must be under 4MB';
          } else if (apiError.message.includes('401')) {
            errorMsg += '🔐 Cause: Not authenticated\n💡 Check: Try refreshing the page';
          } else {
            errorMsg += `🐛 Error: ${apiError.message}\n💡 Check console for details`;
          }
          showNotification(errorMsg, 'error', 7000);
          throw apiError; // Re-throw to be caught by outer try-catch
        }
        console.log('=== PHOTO UPLOAD DEBUG END ===\n');
      } else if (isRemoving) {
        // Use main API for photo removal - it has deletePhotoFile function
        console.log('\n=== PHOTO REMOVAL DEBUG START ===');
        console.log('Using main API for photo removal');
        console.log('remove_photo flag:', body.remove_photo);
        console.log('BTTApi exists?', !!window.BTTApi);
        
        try {
          result = await BTTApi.put('trips', id, body, {}); // No files for removal
          console.log('Photo removal API result:', result);
          
          // Show detailed success confirmation for removal
          if (result && result.id) {
            const photoStatus = result.photo_path ? '⚠️ Photo still exists!' : '✅ Photo removed';
            showNotification(`📷 Photo removal processed!\n📝 ID: ${result.id}\n📍 Title: ${result.title || 'Updated'}\n${photoStatus}`, 
                           result.photo_path ? 'warning' : 'success', 5000);
          } else {
            showNotification('📷 Photo removal sent (no confirmation)', 'info', 3000);
          }
        } catch (apiError) {
          console.error('Photo removal failed:', apiError);
          const errorMsg = `❌ Photo removal failed!\n🐛 Error: ${apiError.message}\n💡 Check console for details`;
          showNotification(errorMsg, 'error', 7000);
          throw apiError;
        }
        console.log('=== PHOTO REMOVAL DEBUG END ===\n');
      } else {
        // Use ajax-handler for simple data updates (no photo)
        console.log('DEBUG: Using ajax-handler for data-only update');
        const url = `ajax-handler.php?route=trips${id ? '&id=' + id : ''}`;
        
        const ajaxFormData = new FormData();
        if (id) {
          ajaxFormData.append('_method', 'PUT');
        }
        
        // Add all form fields
        for (const [key, value] of Object.entries(body)) {
          if (value !== null && value !== undefined) {
            ajaxFormData.append(key, value);
          }
        }
        
        const response = await fetch(url, {
          method: 'POST',
          body: ajaxFormData,
          credentials: 'include'
        });
        
        console.log('Ajax-handler response status:', response.status);
        const responseText = await response.text();
        console.log('Ajax-handler raw response:', responseText.substring(0, 500));

        try {
          result = JSON.parse(responseText);
        } catch (parseError) {
          console.error('Failed to parse ajax-handler response:', parseError);
          showNotification('❌ Server returned invalid response\n🐛 Response was not valid JSON\n💡 Check console for raw response', 'error', 7000);
          throw new Error('Server returned invalid JSON response');
        }

        console.log('DEBUG: ajax-handler response:', result);
        
        if (!response.ok || (result.success === false)) {
          const errorMsg = `❌ Save failed via Ajax Handler!\n🔍 Status: ${response.status}\n🐛 Error: ${result.message || result.error || 'Unknown error'}\n💡 Check console for details`;
          showNotification(errorMsg, 'error', 7000);
          throw new Error(result.message || result.error || 'Request failed');
        }

        // Show success confirmation for ajax-handler saves
        if (result && result.id) {
          showNotification(`✅ Trip saved via Ajax Handler!\n📝 ID: ${result.id}\n📍 Title: ${result.title || 'Updated'}\n🔄 Method: No photo data`, 'success', 4000);
        } else {
          showNotification('✅ Trip saved successfully via Ajax Handler!', 'success', 3000);
        }
      }
      
      if (id){
        console.log('Updated existing trip:', id, result);
        console.log('DEBUG: hasPhoto:', hasPhoto, 'result:', result);
        console.log('DEBUG: result.photo_path:', result?.photo_path);
        
        announceStatus('Trip updated successfully');
        // Use Duolingo-style notification
        showNotification('✅ Trip saved successfully!', 'success');
        // Update the current trip in state with the full result from API
        const tripIndex = state.trips.findIndex(t => t.id === parseInt(id));
        if (tripIndex !== -1) {
          // Always update with the result from API when we have it
          if (result && result.id) {
            console.log('DEBUG: Updating state.trips[tripIndex] with result:', result);
            state.trips[tripIndex] = result;
            // Update the existing photo state - avoid double prefixing
            if (result.photo_path) {
              console.log('DEBUG: Setting state.existingPhoto to:', result.photo_path);
              if (result.photo_path.startsWith('http')) {
                state.existingPhoto = result.photo_path;
              } else if (result.photo_path.startsWith('assets/img/')) {
                state.existingPhoto = result.photo_path; // Already has full path
              } else {
                state.existingPhoto = `assets/img/trips/${result.photo_path}`; // Add prefix only if needed
              }
            }
          } else {
            console.log('DEBUG: No result with ID, merging body data');
            // No proper result, just merge the body data
            state.trips[tripIndex] = {...state.trips[tripIndex], ...body};
          }
        } else {
          console.log('DEBUG: Trip not found in state.trips!');
        }
      } else {
        console.log('Creating new trip, result:', result);
        // For new trips, result is the complete trip object
        const newTrip = result.id ? result : {id: result, ...body};
        newTripId = newTrip.id;
        announceStatus('Trip created successfully');
        // Use Duolingo-style notification
        showNotification('✅ Trip created successfully!', 'success');
        // Update the trip ID in the form so subsequent saves are updates
        document.getElementById('trip-id').value = newTripId;
        state.currentId = newTripId;
        state.mode = 'edit';
        els.editorTitle.textContent = 'Edit Trip';
        
        // Update the existing photo state if photo was uploaded - avoid double prefixing
        if (newTrip.photo_path) {
          if (newTrip.photo_path.startsWith('http')) {
            state.existingPhoto = newTrip.photo_path;
          } else if (newTrip.photo_path.startsWith('assets/img/')) {
            state.existingPhoto = newTrip.photo_path; // Already has full path
          } else {
            state.existingPhoto = `assets/img/trips/${newTrip.photo_path}`; // Add prefix only if needed
          }
        }
      }
      
      // Reload trips in background to keep list updated
      await loadTrips();
      filterTrips();
      
      // Stay on the current editor view and tab
      // The form is already populated with the saved data
      // Just update the insights panel and adventure display
      updateInsights();
      updateAdventureDisplay();
      
      // If we're on the packing tab and have a backpack, refresh the packing list
      if (state.formTab === 'packing' && window.TripPacking && els.backpack && els.backpack.value) {
        const currentTripId = document.getElementById('trip-id').value;
        if (currentTripId) {
          console.log('Trip saved with backpack - refreshing packing list');
          setTimeout(async () => {
            await window.TripPacking.loadPackingList(currentTripId);
          }, 500); // Small delay to ensure trip is fully saved
        }
      }
      
      // Flash a visual indicator on the save button
      els.btnSave.classList.add('btn-success');
      els.btnSave.innerHTML = '✅ Saved!';
      setTimeout(() => {
        els.btnSave.classList.remove('btn-success');
        els.btnSave.innerHTML = '💾 Save Trip';
      }, 2000);
      
    } catch(err){
      console.error('🚨 SAVE TRIP FAILED - Full Error Details:', err);
      console.error('Error name:', err.name);
      console.error('Error message:', err.message);
      console.error('Error stack:', err.stack);
      
      // Show detailed error message with debugging info
      let errorMsg = `❌ Failed to save trip!\n🐛 Error: ${err.message}`;
      
      if (err.message.includes('fetch')) {
        errorMsg += '\n🌐 Network issue - check connection';
      } else if (err.message.includes('401')) {
        errorMsg += '\n🔐 Authentication failed - try refreshing page';
      } else if (err.message.includes('500')) {
        errorMsg += '\n🖥️ Server error - check server logs';
      } else {
        errorMsg += '\n💡 Check browser console for details';
      }
      
      showFormErrors([errorMsg]);
      announceStatus('Error saving trip: ' + err.message);
      showNotification(errorMsg, 'error', 8000);
    } finally {
      setFormBusy(false);
    }
  }

  async function onDelete(){
    const id = document.getElementById('trip-id').value;
    if (!id) return;
    if (!confirm('Are you sure you want to delete this trip? This cannot be undone.')) return;
    try {
      console.log('=== DELETE DEBUG START ===');
      console.log('Deleting trip ID:', id);
      
      // Use ajax-handler for deletes
      const formData = new FormData();
      formData.append('_method', 'DELETE');
      
      console.log('FormData contents:', Array.from(formData.entries()));
      console.log('Calling ajax-handler for DELETE...');
      
      const response = await fetch(`ajax-handler.php?route=trips&id=${id}`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });
      
      console.log('DELETE response status:', response.status);
      console.log('DELETE response headers:', Array.from(response.headers.entries()));
      
      const responseText = await response.text();
      console.log('DELETE raw response:', responseText);
      
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse DELETE response as JSON:', parseError);
        throw new Error('Server returned invalid JSON response');
      }
      
      console.log('DELETE parsed result:', result);
      console.log('=== DELETE DEBUG END ===');
      
      if (!response.ok || (result.success === false)) {
        const errorMsg = `❌ Delete failed!\n🔍 Status: ${response.status}\n🐛 Error: ${result.message || 'Unknown error'}\n💡 Trip may not exist or permission denied`;
        showNotification(errorMsg, 'error', 6000);
        throw new Error(result.message || 'Failed to delete trip');
      }
      
      // Show success confirmation with details
      showNotification(`✅ Trip deleted successfully!\n📝 ID: ${id}\n🗑️ Removed from database\n🔄 Refreshing trip list...`, 'success', 4000);
      await loadTrips();
      filterTrips();
      switchTopView('my-trips');
    } catch(err){
      console.error('Delete error:', err);
      showNotification(err.message || 'Failed to delete trip', 'error');
    }
  }

  async function handleDeleteTrip(id){
    try {
      // Use ajax-handler for deletes
      const formData = new FormData();
      formData.append('_method', 'DELETE');
      
      const response = await fetch(`ajax-handler.php?route=trips&id=${id}`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });
      
      const result = await response.json();
      
      if (!response.ok || (result.success === false)) {
        throw new Error(result.message || 'Failed to delete trip');
      }
      
      showNotification('Trip deleted', 'success');
      await loadTrips();
      filterTrips();
    } catch(err){ 
      console.error('Delete error:', err);
      showNotification(err.message || 'Failed to delete trip', 'error');
    }
  }

  function resetForm(){ 
    els.form.reset(); 
    document.getElementById('trip-id').value = ''; 
    state.existingPhoto = null;
    // Clear photo input and alt text
    if (els.photoInput) els.photoInput.value = '';
    if (els.photoAltText) els.photoAltText.value = '';
    if (els.removePhotoField) els.removePhotoField.value = '0';
    
    // Clear packing data for new trip
    if (window.TripPacking) {
      window.TripPacking.clearData();
    }
  }

  function setReadOnly(isRead){
    Array.from(els.form.elements).forEach(el => {
      if (el.id === 'btn-save-trip' || el.id === 'btn-delete-trip' || el.id === 'btn-cancel-edit') return;
      if (isRead){ el.setAttribute('disabled','disabled'); el.setAttribute('aria-readonly','true'); }
      else { el.removeAttribute('disabled'); el.removeAttribute('aria-readonly'); }
    });
    els.btnSave.style.display = isRead ? 'none' : '';
  }

  function updateInsights(){
    // Duration
    let durationText = '-';
    if (els.start && els.start.value){
      const s = new Date(els.start.value);
      const e = els.end && els.end.value ? new Date(els.end.value) : s;
      const days = Math.floor((e - s)/(1000*60*60*24)) + 1;
      durationText = days === 1 ? 'Day hike' : `${days} days`;
    }
    if (els.insightDuration) {
      els.insightDuration.textContent = durationText;
    }

    // Distance / Elevation
    const dist = els.distance && els.distance.value ? `${els.distance.value} ${els.unit ? els.unit.value : 'miles'}` : '-';
    if (els.insightDistance) {
      els.insightDistance.textContent = dist;
    }
    
    const elevationText = els.elevation && els.elevation.value ? `${Number(els.elevation.value).toLocaleString()} ft` : '-';
    if (els.insightElevation) {
      els.insightElevation.textContent = elevationText;
    }
  }

  function handlePhotoSelect(e) {
    console.log('Simple photo select');
    const file = e.target.files[0];
    if (!file) return;
    
    // Clear remove flag
    if (els.removePhotoField) {
      els.removePhotoField.value = '0';
    }
    
    // Validate file
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!validTypes.includes(file.type)) {
      showNotification('Please select a JPG, JPEG, or PNG image', 'error');
      els.photoInput.value = '';
      return;
    }

    const maxSize = 4 * 1024 * 1024; // 4MB
    if (file.size > maxSize) {
      showNotification('Image must be smaller than 4MB', 'error');
      els.photoInput.value = '';
      return;
    }

    // Show simple preview
    const reader = new FileReader();
    reader.onload = function(e) {
      const container = document.getElementById('adventure-image-display');
      if (container) {
        container.innerHTML = `<img src="${e.target.result}" alt="Photo preview" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;" />`;
        document.getElementById('btn-remove-photo').style.display = 'block';
      }
    };
    reader.readAsDataURL(file);
    showNotification('📷 Photo ready - click Save to upload', 'success');
  }
  
  function handlePhotoRemove(e) {
    e.preventDefault();
    
    // Clear file input
    els.photoInput.value = '';
    
    // Mark for removal
    els.removePhotoField.value = '1';
    
    // Reset display
    const container = document.getElementById('adventure-image-display');
    if (container) {
      container.innerHTML = `
        <div class="placeholder-image">
          <div class="placeholder-icon">🏔️</div>
          <p class="placeholder-text">No photo uploaded</p>
        </div>
      `;
    }
    
    // Hide remove button
    document.getElementById('btn-remove-photo').style.display = 'none';
    
    showNotification('📷 Photo will be removed when you save', 'warning');
  }
  
  function debounce(fn, ms){
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(null,args), ms); };
  }
  
  // Validation functions
  function validateForm() {
    const errors = [];
    
    // Title is required
    if (!els.title.value.trim()) {
      errors.push('Trip title is required');
      els.title.setAttribute('aria-invalid', 'true');
      els.title.setAttribute('aria-describedby', 'title-error');
    } else {
      els.title.removeAttribute('aria-invalid');
      els.title.removeAttribute('aria-describedby');
    }
    
    // Auto-generate alt text if photo is uploaded but no alt text
    if ((els.photoInput.files.length > 0 || state.existingPhoto) && !els.photoAltText.value.trim()) {
      if (els.title && els.title.value) {
        els.photoAltText.value = `Photo from ${els.title.value}`;
      } else {
        els.photoAltText.value = 'Adventure photo';
      }
    } else {
      els.photoAltText.removeAttribute('aria-invalid');
    }
    
    // Date validation
    if (els.start.value && els.end.value) {
      const startDate = new Date(els.start.value);
      const endDate = new Date(els.end.value);
      if (endDate < startDate) {
        errors.push('End date cannot be before start date');
        els.end.setAttribute('aria-invalid', 'true');
      } else {
        els.end.removeAttribute('aria-invalid');
      }
    }
    
    return errors;
  }
  
  function showFormErrors(errors) {
    const errorContainer = document.getElementById('form-errors');
    if (!errorContainer) return;
    
    errorContainer.innerHTML = `
      <div class="alert alert-error">
        <strong>Please fix the following errors:</strong>
        <ul>
          ${errors.map(err => `<li>${BTTUtils.escapeHtml(err)}</li>`).join('')}
        </ul>
      </div>
    `;
    errorContainer.hidden = false;
    errorContainer.focus();
  }
  
  function clearFormErrors() {
    const errorContainer = document.getElementById('form-errors');
    if (errorContainer) {
      errorContainer.innerHTML = '';
      errorContainer.hidden = true;
    }
    
    // Clear aria-invalid attributes
    els.form.querySelectorAll('[aria-invalid]').forEach(el => {
      el.removeAttribute('aria-invalid');
      el.removeAttribute('aria-describedby');
    });
  }
  
  function announceStatus(message) {
    const statusEl = document.getElementById('form-status');
    if (statusEl) {
      statusEl.textContent = message;
      // Clear after announcement
      setTimeout(() => { statusEl.textContent = ''; }, 3000);
    }
  }
  
  function setFormBusy(busy) {
    els.form.setAttribute('aria-busy', busy ? 'true' : 'false');
    els.btnSave.disabled = busy;
    els.btnDelete.disabled = busy;
    if (busy) {
      els.btnSave.innerHTML = '⏳ Saving...';
    } else {
      els.btnSave.innerHTML = '💾 Save Trip';
    }
  }
  
  // Keyboard navigation handlers
  function handleTopTabKeydown(e) {
    const tabs = [els.tabMyTrips, els.tabEditor];
    const currentIndex = tabs.indexOf(e.target);
    let newIndex = currentIndex;
    
    switch(e.key) {
      case 'ArrowLeft':
        newIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
        break;
      case 'ArrowRight':
        newIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
        break;
      case 'Home':
        newIndex = 0;
        break;
      case 'End':
        newIndex = tabs.length - 1;
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        tabs[currentIndex].click();
        return;
      default:
        return;
    }
    
    e.preventDefault();
    tabs[newIndex].focus();
  }
  
  function handleInnerTabKeydown(e) {
    const tabs = [els.tabBtnBasics, els.tabBtnTrail, els.tabBtnLogistics, els.tabBtnConditions, els.tabBtnNotes, els.tabBtnPacking];
    const currentIndex = tabs.indexOf(e.target);
    let newIndex = currentIndex;
    
    switch(e.key) {
      case 'ArrowLeft':
        newIndex = currentIndex > 0 ? currentIndex - 1 : tabs.length - 1;
        break;
      case 'ArrowRight':
        newIndex = currentIndex < tabs.length - 1 ? currentIndex + 1 : 0;
        break;
      case 'Home':
        newIndex = 0;
        break;
      case 'End':
        newIndex = tabs.length - 1;
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        tabs[currentIndex].click();
        return;
      default:
        return;
    }
    
    e.preventDefault();
    tabs[newIndex].focus();
  }
  
  // Handle hash changes for navigation
  function handleHashChange() {
    const hash = window.location.hash;
    if (hash === '#new') {
      openEditor({mode:'create'});
    } else if (hash.startsWith('#edit-')) {
      const id = hash.replace('#edit-', '');
      openEditor({id, mode:'edit'});
    } else if (hash.startsWith('#view-')) {
      const id = hash.replace('#view-', '');
      openEditor({id, mode:'view'});
    } else if (hash === '' || hash === '#') {
      switchTopView('my-trips');
    }
  }
  
    // Initialize the app  
    console.log('Trips.js: Initializing app now!');
    init();
  }); // End jQuery ready
}); // End DOMContentLoaded
