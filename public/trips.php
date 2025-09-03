<?php
// Load bootstrap first
require_once dirname(__DIR__) . '/app/bootstrap.php';

// Include card components
require_once __DIR__ . '/includes/components/trip-card.php';

// Page metadata
$pageId = 'trips';
$pageTitle = 'Trips';
$pageDescription = 'Plan, track, and remember your backpacking adventures';

// Use trip-specific styles with pack-builder base for shared components
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/pack-builder.css';         // Base layout and components
$pageStyles[] = 'css/pack-builder-enhanced.css'; // Enhanced pack styles
$pageStyles[] = 'css/trip-builder.css';         // Trip-specific overrides
$pageStyles[] = 'css/trip-builder-enhanced.css'; // Trip-specific enhancements
$pageStyles[] = 'css/trip-builder-compat.css';  // Browser compatibility fixes

// Add page-specific scripts
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/trips-loading.js';  // Loading states enhancement

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Main Trips Builder Container (mirrors Pack Builder layout) -->
<div class="pack-builder-container" id="main-content">
  <!-- Action Bar with Tabs -->
  <div class="pack-action-bar" role="navigation" aria-label="Trip views">
    <div class="pack-tabs" role="tablist" aria-label="Trip views">
      <button id="tab-my-trips" class="pack-tab active" role="tab" aria-selected="true" aria-controls="panel-my-trips">🗺️ My Trips</button>
      <button id="tab-trip-editor" class="pack-tab" role="tab" aria-selected="false" aria-controls="panel-trip-editor">✍️ Trip Editor</button>
    </div>

    <div class="pack-actions">
      <div class="search-bar" role="search">
        <span class="search-icon" aria-hidden="true">🔍</span>
        <input id="trip-search" type="search" placeholder="Search trips by name or location" aria-label="Search trips" />
      </div>
      <button id="btn-new-trip" class="btn-action" aria-label="Create a new trip">
        <i class="icon">➕</i> New Trip
      </button>
    </div>
  </div>

  <!-- Content Views -->
  <div class="pack-content">
    <!-- My Trips View -->
    <section id="panel-my-trips" class="pack-view active" role="tabpanel" aria-labelledby="tab-my-trips">
      <div class="packs-header">
        <h2 class="view-title">My Trips</h2>
        <div class="view-controls">
          <div class="sort-control">
            <label for="sort-trips">Sort by:</label>
            <select id="sort-trips">
              <option value="recent">Recently Created</option>
              <option value="name">Name</option>
              <option value="date">Start Date</option>
            </select>
          </div>
          <div class="view-mode-toggle" aria-hidden="true">
            <button class="view-mode active" data-mode="grid" title="Grid View"><i>⊞</i></button>
            <button class="view-mode" data-mode="list" title="List View"><i>☰</i></button>
          </div>
        </div>
      </div>

      <div id="trip-grid" class="packs-grid" aria-live="polite" aria-busy="false">
        <div class="loading-spinner">
          <div class="spinner"></div>
          <p>Loading your trips...</p>
        </div>
      </div>

      <div id="trips-empty" class="packs-empty-state" hidden>
        <div class="packs-empty-icon">🏔️</div>
        <div class="packs-empty-text">No trips yet</div>
        <div class="packs-empty-subtext">Click the New Trip button to plan your first adventure.</div>
        <button id="empty-create" class="btn-action"><i>➕</i> Create Trip</button>
      </div>
    </section>

    <!-- Trip Editor View -->
    <section id="panel-trip-editor" class="pack-view" role="tabpanel" aria-labelledby="tab-trip-editor" tabindex="-1">
      <div class="builder-layout">
        <!-- Left: Trip Form -->
        <div class="builder-left">
          <div class="pack-info-card">
            <h3 id="trip-editor-title">Trip Editor</h3>

            <!-- Inner form tabs -->
            <div class="pack-tabs" role="tablist" aria-label="Trip form sections">
              <button id="tab-btn-basics" class="pack-tab active" role="tab" aria-selected="true" aria-controls="tab-panel-basics">Basics</button>
              <button id="tab-btn-trail" class="pack-tab" role="tab" aria-selected="false" aria-controls="tab-panel-trail">Trail Info</button>
              <button id="tab-btn-logistics" class="pack-tab" role="tab" aria-selected="false" aria-controls="tab-panel-logistics">Logistics</button>
              <button id="tab-btn-conditions" class="pack-tab" role="tab" aria-selected="false" aria-controls="tab-panel-conditions">Conditions</button>
              <button id="tab-btn-notes" class="pack-tab" role="tab" aria-selected="false" aria-controls="tab-panel-notes">Notes</button>
            </div>

            <form id="trip-form" novalidate>
              <input type="hidden" id="trip-id" name="id" />

              <!-- Basics -->
              <section id="tab-panel-basics" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-basics">
                <div class="section-header">
                  <h4 class="section-title">Basics</h4>
                </div>
                <div class="section-items">
                  <div class="form-row">
                    <div class="form-group">
                      <label for="title">Trip Name *</label>
                      <input id="title" name="title" class="form-control" type="text" required />
                    </div>
                    <div class="form-group">
                      <label for="location">Location / Trailhead</label>
                      <input id="location" name="location" class="form-control" type="text" />
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="start_date">Start Date</label>
                      <input id="start_date" name="start_date" class="form-control" type="date" />
                    </div>
                    <div class="form-group">
                      <label for="end_date">End Date</label>
                      <input id="end_date" name="end_date" class="form-control" type="date" />
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="trip_type">Trip Type</label>
                      <select id="trip_type" name="trip_type" class="form-control">
                        <option value="">Select type...</option>
                        <option value="day_hike">Day Hike</option>
                        <option value="overnight">Overnight</option>
                        <option value="weekend">Weekend</option>
                        <option value="section_hike">Section Hike</option>
                        <option value="thru_hike">Thru-Hike</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="backpack_id">Backpack</label>
                      <select id="backpack_id" name="backpack_id" class="form-control">
                        <option value="">No backpack selected</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="favorite">Favorite</label>
                      <select id="favorite" name="favorite" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="completed">Completed</label>
                      <select id="completed" name="completed" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="description">Notes</label>
                    <textarea id="description" name="description" class="form-control" rows="2" placeholder="Goals, highlights, gear notes..."></textarea>
                  </div>

                  <div class="form-group">
                    <label for="photo">Trip Photo</label>
                    <input id="photo" name="photo" class="form-control" type="file" accept="image/jpeg,image/jpg,image/png" />
                    <small class="form-text text-muted">Max 4MB. JPG, JPEG, or PNG.</small>
                    <div id="photo-preview" style="margin-top: 10px; display: none;">
                      <img id="preview-image" style="max-width: 100%; height: 200px; object-fit: cover; border-radius: 0.5rem;" alt="Preview" />
                      <button type="button" id="remove-photo" class="btn-secondary" style="margin-top: 10px;">Remove Photo</button>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="photo_alt_text">Photo Description (Required for accessibility)</label>
                    <input id="photo_alt_text" name="photo_alt_text" class="form-control" type="text" placeholder="Describe the photo for screen readers" maxlength="255" />
                    <small class="form-text text-muted">Required when uploading a photo (ADA compliance)</small>
                  </div>
                </div>
              </section>

              <!-- Trail Info -->
              <section id="tab-panel-trail" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-trail" hidden>
                <div class="section-header">
                  <h4 class="section-title">Trail Info</h4>
                </div>
                <div class="section-items">
                  <div class="form-row">
                    <div class="form-group">
                      <label for="distance">Distance</label>
                      <input id="distance" name="distance" class="form-control" type="number" step="0.1" min="0" placeholder="0.0" />
                    </div>
                    <div class="form-group">
                      <label for="distance_unit">Unit</label>
                      <select id="distance_unit" name="distance_unit" class="form-control">
                        <option value="miles">miles</option>
                        <option value="km">km</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="elevation_gain">Elevation Gain (ft)</label>
                      <input id="elevation_gain" name="elevation_gain" class="form-control" type="number" step="100" min="0" />
                    </div>
                    <div class="form-group">
                      <label for="difficulty">Difficulty</label>
                      <select id="difficulty" name="difficulty" class="form-control">
                        <option value="">Select...</option>
                        <option value="easy">Easy</option>
                        <option value="moderate">Moderate</option>
                        <option value="hard">Hard</option>
                        <option value="expert">Expert</option>
                      </select>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Logistics -->
              <section id="tab-panel-logistics" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-logistics" hidden>
                <div class="section-header">
                  <h4 class="section-title">Logistics</h4>
                </div>
                <div class="section-items">
                  <div class="form-row">
                    <div class="form-group">
                      <label for="permit_required">Permit Required</label>
                      <select id="permit_required" name="permit_required" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="permit_cost">Permit Cost ($)</label>
                      <input id="permit_cost" name="permit_cost" class="form-control" type="number" step="0.01" min="0" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="permit_info">Permit/Reservation Info</label>
                    <textarea id="permit_info" name="permit_info" class="form-control" rows="2" placeholder="How to obtain permits, lottery dates, etc."></textarea>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="trailhead_parking">Parking Info</label>
                      <input id="trailhead_parking" name="trailhead_parking" class="form-control" type="text" />
                    </div>
                    <div class="form-group">
                      <label for="parking_cost">Parking Cost ($)</label>
                      <input id="parking_cost" name="parking_cost" class="form-control" type="number" step="0.01" min="0" />
                    </div>
                  </div>
                </div>
              </section>

              <!-- Conditions -->
              <section id="tab-panel-conditions" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-conditions" hidden>
                <div class="section-header">
                  <h4 class="section-title">Conditions</h4>
                </div>
                <div class="section-items">
                  <div class="form-group">
                    <label for="water_sources">Water Sources</label>
                    <textarea id="water_sources" name="water_sources" class="form-control" rows="2"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="trail_conditions">Trail Conditions</label>
                    <textarea id="trail_conditions" name="trail_conditions" class="form-control" rows="2"></textarea>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="cell_coverage">Cell Coverage</label>
                      <select id="cell_coverage" name="cell_coverage" class="form-control">
                        <option value="">Select...</option>
                        <option value="none">None</option>
                        <option value="poor">Poor</option>
                        <option value="spotty">Spotty</option>
                        <option value="good">Good</option>
                        <option value="excellent">Excellent</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="crowd_level">Crowd Level</label>
                      <select id="crowd_level" name="crowd_level" class="form-control">
                        <option value="">Select...</option>
                        <option value="empty">Empty</option>
                        <option value="light">Light</option>
                        <option value="moderate">Moderate</option>
                        <option value="busy">Busy</option>
                        <option value="packed">Packed</option>
                      </select>
                    </div>
                  </div>
                </div>
              </section>

              <!-- Notes -->
              <section id="tab-panel-notes" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-notes" hidden>
                <div class="section-header">
                  <h4 class="section-title">Notes</h4>
                </div>
                <div class="section-items">
                  <div class="form-group">
                    <label for="pre_trip_notes">Pre-Trip Notes</label>
                    <textarea id="pre_trip_notes" name="pre_trip_notes" class="form-control" rows="3"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="post_trip_notes">Post-Trip Notes</label>
                    <textarea id="post_trip_notes" name="post_trip_notes" class="form-control" rows="3"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="lessons_learned">Lessons Learned</label>
                    <textarea id="lessons_learned" name="lessons_learned" class="form-control" rows="3"></textarea>
                  </div>
                </div>
              </section>

              <!-- Editor actions -->
              <div class="builder-actions">
                <button type="submit" id="btn-save-trip" class="btn-primary">💾 Save Trip</button>
                <button type="button" id="btn-cancel-edit" class="btn-secondary">← Back to My Trips</button>
                <button type="button" id="btn-delete-trip" class="btn-secondary" style="border-color: rgba(239,68,68,.4); color: #f87171;">🗑️ Delete</button>
                <div id="form-errors" class="form-errors" role="alert" aria-live="assertive" hidden></div>
                <span id="form-status" class="sr-only" role="status" aria-live="polite"></span>
              </div>
            </form>
          </div>
        </div>

        <!-- Right: Insights / Summary -->
        <div class="builder-right">
          <div class="weight-summary-card">
            <h3>Trip Insights</h3>
            <div id="insight-image" style="margin-bottom: .75rem;"></div>
            <div class="weight-stats">
              <div class="weight-stat">
                <span class="stat-label">Duration</span>
                <span class="stat-value" id="insight-duration">-</span>
              </div>
              <div class="weight-stat">
                <span class="stat-label">Distance</span>
                <span class="stat-value" id="insight-distance">-</span>
              </div>
              <div class="weight-stat">
                <span class="stat-label">Elevation</span>
                <span class="stat-value" id="insight-elevation">-</span>
              </div>
            </div>
            <div id="insight-tags" style="margin-top:.5rem; color: rgba(255,255,255,.7);"></div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<script>
// Wait for the global BTT and utilities to be available
document.addEventListener('DOMContentLoaded', function() {
  'use strict';
  
  // Check if required globals are available
  if (typeof window.BTTApi === 'undefined' || typeof window.BTTUtils === 'undefined') {
    console.error('BTT API utilities not loaded');
    return;
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
    
    // Disable form during save
    setFormBusy(true);
    
    try {
      if (id){
        await BTTApi.put('trips', id, body, files);
        announceStatus('Trip updated successfully');
        BTTUtils.showToast('Trip updated successfully', 'success');
      } else {
        await BTTApi.post('trips', body, files);
        announceStatus('Trip created successfully');
        BTTUtils.showToast('Trip created successfully', 'success');
      }
      await loadTrips();
      filterTrips();
      switchTopView('my-trips');
      
      // Focus on the saved card
      setTimeout(() => {
        const savedCard = document.querySelector(`.pack-card[data-id="${id || state.trips[0]?.id}"]`);
        if (savedCard) savedCard.focus();
      }, 100);
    } catch(err){
      showFormErrors(['Failed to save trip. Please try again.']);
      announceStatus('Error saving trip');
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
  init();
}); // End DOMContentLoaded
</script>

<?php 
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>

