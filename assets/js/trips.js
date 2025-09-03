document.addEventListener('DOMContentLoaded', function() {
  'use strict';
  
  console.log('Trips.js: Starting initialization');
  
  // Wait for jQuery and then initialize
  if (typeof $ === 'undefined') {
    console.error('jQuery not loaded!');
    return;
  }
  
  // Use jQuery's ready to ensure everything is loaded
  $(document).ready(function() {
    console.log('Trips.js: jQuery ready, initializing...');
    
    // Set up API references
    window.BTTApi = window.BTTApi || window.BttApi || window.API;
    
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
    currentId: null
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
    els.tabMyTrips = document.getElementById('tab-my-trips');
    els.tabEditor  = document.getElementById('tab-trip-editor');
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
    els.photoPreview = document.getElementById('photo-preview');
    els.previewImage = document.getElementById('preview-image');
    els.removePhotoBtn = document.getElementById('remove-photo');
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
    els.panelBasics = document.getElementById('tab-panel-basics');
    els.panelTrail = document.getElementById('tab-panel-trail');
    els.panelLogistics = document.getElementById('tab-panel-logistics');
    els.panelConditions = document.getElementById('tab-panel-conditions');
    els.panelNotes = document.getElementById('tab-panel-notes');

    // Insights
    els.insightImg = document.getElementById('insight-image');
    els.insightDuration = document.getElementById('insight-duration');
    els.insightDistance = document.getElementById('insight-distance');
    els.insightElevation = document.getElementById('insight-elevation');
    els.insightTags = document.getElementById('insight-tags');
    els.editorTitle = document.getElementById('trip-editor-title');
  }

  function bindEvents(){
    // Top tabs
    els.tabMyTrips.addEventListener('click', () => switchTopView('my-trips'));
    els.tabEditor.addEventListener('click', () => switchTopView('editor'));
    
    // Keyboard navigation for top tabs
    [els.tabMyTrips, els.tabEditor].forEach(tab => {
      tab.addEventListener('keydown', handleTopTabKeydown);
    });

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
    
    // Keyboard navigation for inner tabs
    const innerTabs = [els.tabBtnBasics, els.tabBtnTrail, els.tabBtnLogistics, els.tabBtnConditions, els.tabBtnNotes];
    innerTabs.forEach(tab => {
      tab.addEventListener('keydown', handleInnerTabKeydown);
    });

    // Form
    els.form.addEventListener('submit', onSave);
    els.btnCancel.addEventListener('click', () => switchTopView('my-trips'));
    els.btnDelete.addEventListener('click', onDelete);

    // Form changes -> insights
    [els.title, els.location, els.start, els.end, els.distance, els.unit, els.elevation, els.tripType,
     els.favorite, els.completed].forEach(el => {
      if (!el) return; el.addEventListener('input', updateInsights);
    });
    
    // Photo upload handling
    if (els.photoInput) {
      els.photoInput.addEventListener('change', handlePhotoSelect);
    }
    if (els.removePhotoBtn) {
      els.removePhotoBtn.addEventListener('click', handlePhotoRemove);
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
        
        setTimeout(() => { button.disabled = false; }, 300);
        
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
      const data = await BTTApi.get('trips');
      state.trips = Array.isArray(data) ? data : (data ? [data] : []);
      state.filtered = [...state.trips];
    } catch (e) {
      setGridError('Failed to load trips');
    }
  }

  async function loadBackpacks(){
    try {
      const data = await BTTApi.get('backpacks');
      state.backpacks = Array.isArray(data) ? data : (data ? [data] : []);
      // Fill select
      if (els.backpack){
        const opts = ['<option value="">No backpack selected</option>'].concat(
          state.backpacks.map(b => `<option value="${b.id}">${BTTUtils.escapeHtml(b.name)}</option>`) );
        els.backpack.innerHTML = opts.join('');
      }
    } catch(e) { /* ignore */ }
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
    if (!state.filtered.length){
      els.tripGrid.innerHTML = '';
      return;
    }
    const html = state.filtered.map(renderTripCard).join('');
    els.tripGrid.innerHTML = html;
    // Events are now handled by delegation in bindEvents()
  }

  function updateEmptyState(){
    els.tripsEmpty.hidden = state.trips.length !== 0;
  }

  function renderTripCard(trip){
    const photoUrl = trip.photo_path ? `${window.BTT.baseUrl}/${trip.photo_path}` : (trip.default_image_url || 'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=400&h=300&fit=crop');
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

  function switchTopView(view, {focus=true}={}){
    state.activeView = view;
    const isEditor = view === 'editor';
    els.panelMyTrips.classList.toggle('active', !isEditor);
    els.panelEditor.classList.toggle('active', isEditor);

    els.tabMyTrips.classList.toggle('active', !isEditor);
    els.tabEditor .classList.toggle('active', isEditor);
    els.tabMyTrips.setAttribute('aria-selected', (!isEditor).toString());
    els.tabEditor .setAttribute('aria-selected', (isEditor).toString());

    if (focus){
      (isEditor ? els.tabEditor : els.tabMyTrips).focus();
    }
  }

  function switchFormTab(tab){
    state.formTab = tab;
    const mapping = {
      basics: [els.tabBtnBasics, els.panelBasics],
      trail: [els.tabBtnTrail, els.panelTrail],
      logistics: [els.tabBtnLogistics, els.panelLogistics],
      conditions: [els.tabBtnConditions, els.panelConditions],
      notes: [els.tabBtnNotes, els.panelNotes]
    };
    // Reset
    [els.tabBtnBasics, els.tabBtnTrail, els.tabBtnLogistics, els.tabBtnConditions, els.tabBtnNotes].forEach(b => b.classList.remove('active'));
    [els.panelBasics, els.panelTrail, els.panelLogistics, els.panelConditions, els.panelNotes].forEach(p => p.hidden = true);
    // Activate
    const [btn, panel] = mapping[tab];
    btn.classList.add('active');
    panel.hidden = false;
    btn.setAttribute('aria-selected', 'true');
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
      setReadOnly(false);
      return;
    }
    // edit or view
    loadTripById(id).then(trip => {
      populateForm(trip);
      els.editorTitle.textContent = mode === 'view' ? 'Trip Summary' : 'Edit Trip';
      switchFormTab('basics');
      updateInsights();
      setReadOnly(mode === 'view');
    }).catch(() => {
      BTTUtils.showToast('Failed to load trip', 'error');
      switchTopView('my-trips');
    });
  }

  async function loadTripById(id){
    const data = await BTTApi.get('trips', { id });
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
    els.description.value = t.description || '';
    els.photoAltText.value = t.photo_alt_text || '';
    
    // Show existing photo if available
    if (t.photo_path) {
      const photoUrl = `${window.BTT.baseUrl}/${t.photo_path}`;
      els.previewImage.src = photoUrl;
      els.photoPreview.style.display = 'block';
      state.existingPhoto = t.photo_path;
    } else {
      els.photoPreview.style.display = 'none';
      state.existingPhoto = null;
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
    e.preventDefault();
    
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
    const files = {};
    if (photoFile) {
      files.photo = photoFile;
    }
    
    // Get form data as object for non-file fields
    const body = serializeForm();
    
    // Remember current tab
    const currentTab = state.formTab;
    
    // Disable form during save
    setFormBusy(true);
    
    try {
      let newTripId = null;
      if (id){
        await BTTApi.put('trips', id, body, files);
        announceStatus('Trip updated successfully');
        BTTUtils.showToast('✅ Trip saved successfully!', 'success');
        // Update the current trip in state
        const tripIndex = state.trips.findIndex(t => t.id === parseInt(id));
        if (tripIndex !== -1) {
          state.trips[tripIndex] = {...state.trips[tripIndex], ...body};
        }
      } else {
        const result = await BTTApi.post('trips', body, files);
        newTripId = result.id || result;
        announceStatus('Trip created successfully');
        BTTUtils.showToast('✅ Trip created successfully!', 'success');
        // Update the trip ID in the form so subsequent saves are updates
        document.getElementById('trip-id').value = newTripId;
        state.currentId = newTripId;
        state.mode = 'edit';
        els.editorTitle.textContent = 'Edit Trip';
      }
      
      // Reload trips in background to keep list updated
      await loadTrips();
      filterTrips();
      
      // Stay on the current editor view and tab
      // The form is already populated with the saved data
      // Just update the insights panel
      updateInsights();
      
      // Flash a visual indicator on the save button
      els.btnSave.classList.add('btn-success');
      els.btnSave.innerHTML = '✅ Saved!';
      setTimeout(() => {
        els.btnSave.classList.remove('btn-success');
        els.btnSave.innerHTML = '💾 Save Trip';
      }, 2000);
      
    } catch(err){
      showFormErrors(['Failed to save trip. Please try again.']);
      announceStatus('Error saving trip');
      BTTUtils.showToast('❌ Failed to save trip', 'error');
    } finally {
      setFormBusy(false);
    }
  }

  async function onDelete(){
    const id = document.getElementById('trip-id').value;
    if (!id) return;
    if (!confirm('Are you sure you want to delete this trip? This cannot be undone.')) return;
    try {
      await BTTApi.delete('trips', id);
      BTTUtils.showToast('Trip deleted successfully', 'success');
      await loadTrips();
      filterTrips();
      switchTopView('my-trips');
    } catch(err){
      // handled
    }
  }

  async function handleDeleteTrip(id){
    try {
      await BTTApi.delete('trips', id);
      BTTUtils.showToast('Trip deleted', 'success');
      await loadTrips();
      filterTrips();
    } catch(err){ 
      BTTUtils.showToast('Failed to delete trip', 'error');
    }
  }

  function resetForm(){ 
    els.form.reset(); 
    document.getElementById('trip-id').value = ''; 
    els.photoPreview.style.display = 'none';
    state.existingPhoto = null;
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
    // Image - show preview if available
    if (els.previewImage && els.previewImage.src && els.photoPreview.style.display !== 'none') {
      const alt = els.photoAltText.value || 'Trip photo';
      els.insightImg.innerHTML = `<img src="${els.previewImage.src}" alt="${BTTUtils.escapeHtml(alt)}" style="width:100%;height:160px;object-fit:cover;border-radius:.5rem;border:1px solid rgba(74,222,128,.2)"/>`;
    } else {
      els.insightImg.innerHTML = '';
    }

    // Duration
    let durationText = '-';
    if (els.start.value){
      const s = new Date(els.start.value);
      const e = els.end.value ? new Date(els.end.value) : s;
      const days = Math.floor((e - s)/(1000*60*60*24)) + 1;
      durationText = days === 1 ? 'Day hike' : `${days} days`;
    }
    els.insightDuration.textContent = durationText;

    // Distance / Elevation
    const dist = els.distance.value ? `${els.distance.value} ${els.unit.value}` : '-';
    els.insightDistance.textContent = dist;
    els.insightElevation.textContent = els.elevation.value ? `${Number(els.elevation.value).toLocaleString()} ft` : '-';

    // Tags
    const chips = [];
    if (els.difficulty.value) chips.push(`#${els.difficulty.value}`);
    if (els.tripType.value) chips.push(`#${els.tripType.value.replace('_','-')}`);
    if (els.backpack.value){
      const bp = state.backpacks.find(b => String(b.id) === String(els.backpack.value));
      if (bp) chips.push(`🎒 ${bp.name}`);
    }
    els.insightTags.textContent = chips.join('  ');
  }

  function handlePhotoSelect(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!validTypes.includes(file.type)) {
      BTTUtils.showToast('Please select a JPG, JPEG, or PNG image', 'error');
      els.photoInput.value = '';
      return;
    }
    
    // Validate file size (4MB max)
    const maxSize = 4 * 1024 * 1024; // 4MB
    if (file.size > maxSize) {
      BTTUtils.showToast('Image must be less than 4MB', 'error');
      els.photoInput.value = '';
      return;
    }
    
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
      els.previewImage.src = e.target.result;
      els.photoPreview.style.display = 'block';
      updateInsights();
    };
    reader.readAsDataURL(file);
  }
  
  function handlePhotoRemove(e) {
    e.preventDefault();
    els.photoInput.value = '';
    els.previewImage.src = '';
    els.photoPreview.style.display = 'none';
    state.existingPhoto = null;
    updateInsights();
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
    
    // If photo uploaded, alt text is required
    if ((els.photoInput.files.length > 0 || state.existingPhoto) && !els.photoAltText.value.trim()) {
      errors.push('Alt text is required when uploading a photo (for accessibility)');
      els.photoAltText.setAttribute('aria-invalid', 'true');
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
    const tabs = [els.tabBtnBasics, els.tabBtnTrail, els.tabBtnLogistics, els.tabBtnConditions, els.tabBtnNotes];
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
