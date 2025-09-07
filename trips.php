<?php
// Load bootstrap first
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Include card components
if (file_exists(__DIR__ . '/includes/components/trip-card.php')) {
    require_once __DIR__ . '/includes/components/trip-card.php';
} else if (file_exists(__DIR__ . '/public/includes/components/trip-card.php')) {
    require_once __DIR__ . '/public/includes/components/trip-card.php';
}

// Page metadata
$pageId = 'trips';
$pageTitle = 'Adventures';
$pageDescription = 'Plan, track, and remember your backpacking adventures on the trails';

// Add page-specific scripts (keeping essential functionality)
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/save-animation.js'; // Save animation module
$pageScripts[] = 'js/trips-tabs.js'; // Tab navigation functionality
$pageScripts[] = 'js/trips.js'; // Main trips functionality
$pageScripts[] = 'js/date-range-picker.js'; // Date range picker
$pageScripts[] = 'js/trip-packing.js'; // Trip packing list functionality

// Add page-specific styles
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/unified-page-headers.css'; // Unified header styles
$pageStyles[] = 'css/trip-form-improved.css'; // Improved form styles
$pageStyles[] = 'css/trips-list-view-and-dropdowns.css'; // List view and dropdown fixes
$pageStyles[] = 'css/simple-photo-upload.css'; // Simple photo upload controls
$pageStyles[] = 'css/photo-edit-overlay.css'; // Click-to-edit photo overlay
$pageStyles[] = 'css/trip-packing.css'; // Trip packing list styles
$pageStyles[] = 'css/save-animation.css'; // Save animation styles
$pageStyles[] = 'css/trips-form-ui-improvements.css?v=' . time(); // Enhanced form UI
$pageStyles[] = 'css/date-picker-enhanced.css?v=' . time(); // Enhanced date picker styling
$pageStyles[] = 'css/components/trip-card-duolingo-forest.css?v=' . time(); // Modern trip card styling
$pageStyles[] = 'css/components/trip-card-animations.css?v=' . time(); // Trip card animations

// Add page class for styling
$bodyClasses = $bodyClasses ?? [];
$bodyClasses[] = 'adventures-page';

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Adventures Page Header -->
<div class="page-hero">
  <div class="hero-content">
    <div class="hero-main-row">
      <div class="hero-header">
        <div class="hero-title-section">
          <h1 class="hero-title">
            <span class="hero-title-icon">🗺️</span>
            Your Adventures
          </h1>
          <p class="hero-subtitle">Plan and track your epic trail experiences</p>
        </div>
      </div>
      
      <div class="hero-controls">
        <div class="hero-search">
          <input type="search" class="search-input" placeholder="Search adventures..." id="trip-search-hero" aria-label="Search trips">
        </div>
        
        <select class="sort-select" id="sort-hero" aria-label="Sort trips">
          <option value="recent">Recent</option>
          <option value="name">Name</option>
          <option value="date">Date</option>
        </select>
        
        <div class="view-toggle">
          <button class="view-toggle-btn active" data-view="grid" aria-label="Grid view">⊞</button>
          <button class="view-toggle-btn" data-view="list" aria-label="List view">☰</button>
        </div>
        
        <button id="btn-new-trip" class="btn btn-adventure">
          <span class="btn-icon">✨</span>
          <span>New Adventure</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Adventures Layout Container -->
<div class="adventures-layout" id="main-content">
  <!-- Adventure Controls -->
  <!-- Hidden controls for JavaScript compatibility -->
  <div class="adventures-controls" style="display: none;">
    <input id="trip-search" type="search" aria-hidden="true" />
    <select id="sort-trips" aria-hidden="true">
      <option value="recent">Recent</option>
      <option value="name">Name</option>
      <option value="date">Date</option>
    </select>
    <div class="view-toggle">
      <button class="view-btn active" data-mode="grid" aria-hidden="true">Grid</button>
      <button class="view-btn" data-mode="list" aria-hidden="true">List</button>
    </div>
  </div>



  <!-- Adventures Content -->
  <div class="adventures-content">
    <!-- My Adventures Grid -->
    <section id="panel-my-trips" class="adventures-section active" role="main">

      <div id="trip-grid" class="cards-grid cards-grid-3" aria-live="polite" aria-busy="false">
        <div class="card loading-card">
          <div class="card-content">
            <div class="loading-spinner">
              <div class="spinner"></div>
              <p>Loading your adventures...</p>
            </div>
          </div>
        </div>
      </div>

      <div id="trips-empty" class="empty-state" hidden>
        <div class="empty-state-icon">🏔️</div>
        <h3 class="empty-state-title">No adventures yet</h3>
        <p class="empty-state-description">Ready to hit the trails? Start planning your first adventure!</p>
        <button id="empty-create" class="btn btn-adventure">➕ Plan First Adventure</button>
      </div>
    </section>

    <!-- Adventure Editor -->
    <section id="panel-trip-editor" class="adventures-editor" role="dialog" aria-labelledby="trip-editor-title" hidden>
      <div class="editor-layout">
        <!-- Adventure Visual Header -->
        <div class="adventure-visual-header">
          <div class="adventure-image-section">
            <!-- Clickable photo display area -->
            <div class="adventure-image-container" id="adventure-image-display" role="button" tabindex="0" title="Click to add or change photo" style="cursor: pointer;">
              <div class="placeholder-image">
                <div class="placeholder-icon">🏔️</div>
                <p class="placeholder-text">Click to add photo</p>
              </div>
            </div>
            
            <!-- Photo upload overlay with click-to-edit -->
            <div class="adventure-image-overlay">
              <div class="adventure-title-overlay">
                <h1 id="trip-editor-title" class="adventure-name-display">New Adventure</h1>
                <p class="adventure-location-display"></p>
              </div>
              <div class="adventure-stats-overlay">
                <div class="stat-chip" id="duration-chip">📅 Duration</div>
                <div class="stat-chip" id="distance-chip">🥾 Distance</div>
                <div class="stat-chip" id="difficulty-chip">💪 Difficulty</div>
              </div>
              
              <!-- Click-to-edit photo button -->
              <div class="photo-edit-overlay" id="photo-edit-overlay" title="Click to change photo">
                <button type="button" class="photo-edit-btn" id="photo-edit-btn">
                  <span class="photo-edit-icon">📸</span>
                  <span class="photo-edit-text">Change Photo</span>
                </button>
                
                <!-- Remove photo button -->
                <button type="button" class="btn btn-danger btn-sm" id="btn-remove-photo" style="display: none;">🗑️ Remove</button>
              </div>
            </div>
            
            <!-- Hidden file input -->
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/jpg,image/png" class="hidden-file-input" style="display: none;" />
          </div>
        </div>
        
        <!-- Adventure Form Content -->
        <div class="editor-content">
          <div class="card card-adventure">

            <!-- Progress Navigation -->
            <div class="adventure-progress-nav" role="tablist" aria-label="Adventure planning steps">
              <div class="progress-step active" data-step="1">
                <button id="tab-btn-basics" class="step-btn" role="tab" aria-selected="true" aria-controls="tab-panel-basics">
                  <span class="step-icon">✏️</span>
                  <span class="step-label">Basic Info</span>
                </button>
              </div>
              <div class="progress-step" data-step="2">
                <button id="tab-btn-trail" class="step-btn" role="tab" aria-selected="false" aria-controls="tab-panel-trail">
                  <span class="step-icon">🗺️</span>
                  <span class="step-label">Trail Details</span>
                </button>
              </div>
              <div class="progress-step" data-step="3">
                <button id="tab-btn-logistics" class="step-btn" role="tab" aria-selected="false" aria-controls="tab-panel-logistics">
                  <span class="step-icon">🎫</span>
                  <span class="step-label">Logistics</span>
                </button>
              </div>
              <div class="progress-step" data-step="4">
                <button id="tab-btn-conditions" class="step-btn" role="tab" aria-selected="false" aria-controls="tab-panel-conditions">
                  <span class="step-icon">🌤️</span>
                  <span class="step-label">Conditions</span>
                </button>
              </div>
              <div class="progress-step" data-step="5">
                <button id="tab-btn-notes" class="step-btn" role="tab" aria-selected="false" aria-controls="tab-panel-notes">
                  <span class="step-icon">📝</span>
                  <span class="step-label">Notes</span>
                </button>
              </div>
              <div class="progress-step" data-step="6">
                <button id="tab-btn-packing" class="step-btn" role="tab" aria-selected="false" aria-controls="tab-panel-packing">
                  <span class="step-icon">🎒</span>
                  <span class="step-label">Packing List</span>
                </button>
              </div>
            </div>

            <div class="card-content">
              <form id="trip-form" class="form-layout" data-validate>
                <input type="hidden" id="trip-id" name="id" />

                <!-- Basics Tab -->
                <section id="tab-panel-basics" class="form-section" role="tabpanel" aria-labelledby="tab-btn-basics">
                  <div class="form-group full-width">
                    <label for="title" class="form-label required">
                      Adventure Name
                    </label>
                    <input id="title" 
                           name="title" 
                           class="form-input" 
                           type="text" 
                           placeholder="e.g., PCT Section Hike"
                           required 
                           minlength="3" 
                           maxlength="100" />
                    <small class="form-help">Give your adventure a memorable name</small>
                  </div>

                  <div class="form-group full-width">
                    <label for="location" class="form-label">Location / Trailhead</label>
                    <input id="location" 
                           name="location" 
                           class="form-input" 
                           type="text" 
                           placeholder="📍 Trail or park name" 
                           maxlength="255" />
                  </div>

                  <div class="form-group full-width">
                    <label for="date_range" class="form-label">Trip Dates</label>
                    <div class="date-range-input">
                      <input id="date_range" 
                             name="date_range" 
                             class="form-input" 
                             type="text" 
                             placeholder="Select trip dates" 
                             readonly />
                      <div id="date-picker-popup" class="date-picker-popup">
                        <div class="date-picker-header">
                          <div class="date-picker-info">
                            <span id="selection-status">Select check-in date</span>
                          </div>
                          <div class="date-picker-actions">
                            <button type="button" class="btn btn-secondary btn-sm" id="clear-dates">Clear</button>
                          </div>
                        </div>
                        <div class="single-calendar-container">
                          <div id="main-calendar" class="calendar"></div>
                        </div>
                      </div>
                    </div>
                    <!-- Hidden fields for database compatibility -->
                    <input type="hidden" id="start_date" name="start_date" />
                    <input type="hidden" id="end_date" name="end_date" />
                    <small class="form-help">Click to select start and end dates for your adventure</small>
                  </div>

                  <!-- Trip Metadata in compact layout -->
                  <div class="trip-metadata-section">
                    <div class="metadata-row">
                      <div class="form-group">
                        <label for="trip_type" class="form-label">Trip Type</label>
                        <select id="trip_type" name="trip_type" class="form-select">
                          <option value="">Select type...</option>
                          <option value="day_hike">Day Hike</option>
                          <option value="overnight">Overnight</option>
                          <option value="weekend">Weekend</option>
                          <option value="section_hike">Section Hike</option>
                          <option value="thru_hike">Thru-Hike</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="backpack_id" class="form-label">Backpack</label>
                        <select id="backpack_id" name="backpack_id" class="form-select">
                          <option value="">No backpack selected</option>
                        </select>
                      </div>
                    </div>
                    
                    <!-- Toggle switches for boolean values -->
                    <div class="metadata-toggles">
                      <div class="toggle-group">
                        <input type="hidden" id="favorite" name="favorite" value="0">
                        <label class="toggle-switch" for="favorite-toggle">
                          <input type="checkbox" id="favorite-toggle">
                          <span class="toggle-slider"></span>
                          <span class="toggle-label">⭐ Favorite Adventure</span>
                        </label>
                      </div>
                      <div class="toggle-group">
                        <input type="hidden" id="completed" name="completed" value="0">
                        <label class="toggle-switch" for="completed-toggle">
                          <input type="checkbox" id="completed-toggle">
                          <span class="toggle-slider"></span>
                          <span class="toggle-label">✅ Completed</span>
                        </label>
                      </div>
                    </div>
                  </div>

                  <div class="form-group full-width">
                    <label for="description" class="form-label">Adventure Notes</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-textarea" 
                              rows="3" 
                              placeholder="Goals, highlights, gear notes..."
                              maxlength="1000"></textarea>
                    <small class="form-help">Add any notes about this adventure</small>
                  </div>

                  <!-- Photo Alt Text (for accessibility) -->
                  <div class="form-group full-width">
                    <label for="photo_alt_text" class="form-label">📸 Photo Description</label>
                    <input id="photo_alt_text" name="photo_alt_text" type="text" placeholder="Describe the photo for accessibility (auto-generated if empty)" class="form-input" />
                    <small class="form-help">Brief description of your photo for screen readers</small>
                  </div>

                  <!-- Photo removal flag -->
                  <input id="remove_photo" name="remove_photo" type="hidden" value="0" />
                </section>

              <!-- Trail Info -->
              <section id="tab-panel-trail" class="form-section" role="tabpanel" aria-labelledby="tab-btn-trail" hidden>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="distance" class="form-label">📏 Distance</label>
                      <input id="distance" 
                             name="distance" 
                             class="form-input" 
                             type="number" 
                             step="0.1" 
                             min="0" 
                             max="5000"
                             placeholder="0.0" />
                    </div>
                    <div class="form-group">
                      <label for="distance_unit" class="form-label">📐 Unit</label>
                      <select id="distance_unit" name="distance_unit" class="form-select">
                        <option value="miles">miles</option>
                        <option value="km">km</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="elevation_gain" class="form-label">⛰️ Elevation Gain (ft)</label>
                      <input id="elevation_gain" 
                             name="elevation_gain" 
                             class="form-input" 
                             type="number" 
                             step="100" 
                             min="0" 
                             max="50000" />
                    </div>
                    <div class="form-group">
                      <label for="difficulty" class="form-label">💪 Difficulty</label>
                      <select id="difficulty" name="difficulty" class="form-select">
                        <option value="">Select...</option>
                        <option value="easy">Easy</option>
                        <option value="moderate">Moderate</option>
                        <option value="hard">Hard</option>
                        <option value="expert">Expert</option>
                      </select>
                    </div>
                  </div>
              </section>

              <!-- Logistics Tab -->
              <section id="tab-panel-logistics" class="form-section" role="tabpanel" aria-labelledby="tab-btn-logistics" hidden>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="permit_required" class="form-label">🎫 Permit Required</label>
                      <select id="permit_required" name="permit_required" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="permit_cost" class="form-label">💰 Permit Cost ($)</label>
                      <input id="permit_cost" name="permit_cost" class="form-input" type="number" step="0.01" min="0" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="permit_info" class="form-label">Permit/Reservation Info</label>
                    <textarea id="permit_info" 
                              name="permit_info" 
                              class="form-textarea" 
                              rows="2" 
                              placeholder="How to obtain permits, lottery dates, etc."
                              maxlength="500"></textarea>
                  </div>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="trailhead_parking" class="form-label">🚗 Parking Info</label>
                      <input id="trailhead_parking" name="trailhead_parking" class="form-input" type="text" />
                    </div>
                    <div class="form-group">
                      <label for="parking_cost" class="form-label">🅿️ Parking Cost ($)</label>
                      <input id="parking_cost" name="parking_cost" class="form-input" type="number" step="0.01" min="0" />
                    </div>
                  </div>
              </section>

              <!-- Conditions -->
              <section id="tab-panel-conditions" class="form-section" role="tabpanel" aria-labelledby="tab-btn-conditions" hidden>
                  <div class="form-group">
                    <label for="water_sources" class="form-label">💧 Water Sources</label>
                    <textarea id="water_sources" 
                              name="water_sources" 
                              class="form-textarea" 
                              rows="2"
                              placeholder="Describe water availability along the trail"
                              maxlength="500"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="trail_conditions" class="form-label">🥾 Trail Conditions</label>
                    <textarea id="trail_conditions" 
                              name="trail_conditions" 
                              class="form-textarea" 
                              rows="2"
                              placeholder="Current trail conditions, hazards, etc."
                              maxlength="500"></textarea>
                  </div>
                  <div class="form-grid">
                    <div class="form-group">
                      <label for="cell_coverage" class="form-label">📶 Cell Coverage</label>
                      <select id="cell_coverage" name="cell_coverage" class="form-select">
                        <option value="">Select...</option>
                        <option value="none">None</option>
                        <option value="poor">Poor</option>
                        <option value="spotty">Spotty</option>
                        <option value="good">Good</option>
                        <option value="excellent">Excellent</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="crowd_level" class="form-label">👥 Crowd Level</label>
                      <select id="crowd_level" name="crowd_level" class="form-select">
                        <option value="">Select...</option>
                        <option value="empty">Empty</option>
                        <option value="light">Light</option>
                        <option value="moderate">Moderate</option>
                        <option value="busy">Busy</option>
                        <option value="packed">Packed</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="form-group">
                    <label for="camping_type" class="form-label">🏕️ Camping Type</label>
                    <select id="camping_type" name="camping_type" class="form-select">
                      <option value="">Select camping type...</option>
                      <option value="dispersed">Dispersed Camping</option>
                      <option value="designated">Designated Sites</option>
                      <option value="frontcountry">Frontcountry Campground</option>
                      <option value="backcountry">Backcountry Sites</option>
                      <option value="shelter">Trail Shelter</option>
                      <option value="none">No Camping</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label for="expected_weather" class="form-label">🌤️ Expected Weather</label>
                    <textarea id="expected_weather" 
                              name="expected_weather" 
                              class="form-textarea" 
                              rows="2" 
                              placeholder="Temperature range, precipitation, wind conditions..."
                              maxlength="500"></textarea>
                  </div>
                  
                  <div class="form-group">
                    <label for="emergency_contact" class="form-label">🚨 Emergency Contact</label>
                    <input id="emergency_contact" 
                           name="emergency_contact" 
                           type="text" 
                           class="form-input" 
                           placeholder="Name & phone number for emergency situations"
                           maxlength="255" />
                  </div>
              </section>

              <!-- Notes -->
              <section id="tab-panel-notes" class="form-section" role="tabpanel" aria-labelledby="tab-btn-notes" hidden>
                  <div class="form-group">
                    <label for="pre_trip_notes" class="form-label">📝 Pre-Trip Notes</label>
                    <textarea id="pre_trip_notes" name="pre_trip_notes" class="form-textarea" rows="3" placeholder="Planning notes, gear prep, permits..."></textarea>
                  </div>
                  <div class="form-group">
                    <label for="post_trip_notes" class="form-label">📖 Post-Trip Notes</label>
                    <textarea id="post_trip_notes" name="post_trip_notes" class="form-textarea" rows="3" placeholder="Trip highlights, experiences, memories..."></textarea>
                  </div>
                  <div class="form-group">
                    <label for="lessons_learned" class="form-label">🎓 Lessons Learned</label>
                    <textarea id="lessons_learned" name="lessons_learned" class="form-textarea" rows="3" placeholder="What would you do differently next time?"></textarea>
                  </div>
              </section>

              <!-- Packing List -->
              <section id="tab-panel-packing" class="form-section" role="tabpanel" aria-labelledby="tab-btn-packing" hidden>
                <!-- Packing Progress -->
                <div class="packing-progress">
                  <div class="progress-stats">
                    <span class="progress-text" id="packing-progress-text">0 of 0 items packed</span>
                    <span class="progress-percent" id="packing-progress-percent">0%</span>
                  </div>
                  <div class="progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-fill" id="packing-progress-bar" style="width: 0%"></div>
                  </div>
                </div>

                <!-- Empty State -->
                <div class="packing-empty" id="packing-empty-state" hidden>
                  <div class="empty-icon">🎒</div>
                  <div class="empty-text">No backpack selected</div>
                  <div class="empty-subtext">Select a backpack in the Basic Info tab to manage your packing list</div>
                </div>

                <!-- Packing Content (loaded dynamically) -->
                <div id="packing-content" class="packing-content">
                  <!-- Category Filters -->
                  <div class="packing-filters">
                    <button class="filter-chip active" data-category="all" aria-pressed="true">All Items</button>
                    <button class="filter-chip" data-category="main" aria-pressed="false">Main Compartment</button>
                    <button class="filter-chip" data-category="lid" aria-pressed="false">Top Lid</button>
                    <button class="filter-chip" data-category="pockets" aria-pressed="false">Side Pockets</button>
                    <button class="filter-chip" data-category="external" aria-pressed="false">External</button>
                  </div>

                  <!-- Bulk Actions -->
                  <div class="packing-bulk-actions">
                    <button id="btn-pack-all" class="btn btn-secondary btn-sm">✅ Pack All</button>
                    <button id="btn-unpack-all" class="btn btn-secondary btn-sm">☑️ Unpack All</button>
                    <button id="btn-reset-packing" class="btn btn-secondary btn-sm">🔄 Reset</button>
                  </div>

                  <!-- Packing Items Container -->
                  <div class="packing-items">
                    <div id="packing-items-container"></div>
                  </div>

                  <!-- Custom Item Form -->
                  <div class="custom-item-form">
                    <h5>➕ Add Custom Item</h5>
                    <div class="form-row">
                      <div class="form-group">
                        <label for="custom-item-name" class="form-label">Item Name</label>
                        <input id="custom-item-name" type="text" class="form-input" placeholder="e.g., Camp pillow" maxlength="100" />
                      </div>
                      <div class="form-group">
                        <label for="custom-item-category" class="form-label">Category</label>
                        <select id="custom-item-category" class="form-select">
                          <option value="main">Main Compartment</option>
                          <option value="lid">Top Lid</option>
                          <option value="pockets">Side Pockets</option>
                          <option value="external">External</option>
                        </select>
                      </div>
                      <div class="form-group">
                        <label for="custom-item-quantity" class="form-label">Qty</label>
                        <input id="custom-item-quantity" type="number" class="form-input" value="1" min="1" max="99" />
                      </div>
                    </div>
                    <div class="form-group">
                      <label for="custom-item-notes" class="form-label">Notes (optional)</label>
                      <input id="custom-item-notes" type="text" class="form-input" placeholder="Special notes..." maxlength="255" />
                    </div>
                    <div class="form-actions">
                      <button id="btn-add-custom-item" type="button" class="btn btn-adventure">➕ Add Item</button>
                    </div>
                    <div id="custom-item-error" class="form-error" hidden></div>
                  </div>
                </div>
              </section>

              <!-- Form Actions -->
              <div class="form-actions">
                <button type="submit" id="btn-save-trip" class="btn btn-adventure btn-lg">💾 Save Adventure</button>
                <button type="button" id="btn-cancel-edit" class="btn btn-secondary">← Back to Adventures</button>
                <button type="button" id="btn-delete-trip" class="btn btn-danger">🗑️ Delete Adventure</button>
                <div id="form-errors" class="form-errors" role="alert" aria-live="assertive" hidden></div>
                <span id="form-status" class="sr-only" role="status" aria-live="polite"></span>
              </div>
            </form>
          </div>
        </div>
      
        <!-- Adventure Planning Sidebar -->
        <div class="adventure-sidebar">
          <div class="planning-card">
            <h3 class="planning-title">🎯 Planning Checklist</h3>
            <div class="checklist-items">
              <div class="checklist-item" data-requirement="name">
                <div class="check-icon">✓</div>
                <span class="check-label">Adventure name</span>
              </div>
              <div class="checklist-item" data-requirement="dates">
                <div class="check-icon">✓</div>
                <span class="check-label">Trip dates</span>
              </div>
              <div class="checklist-item" data-requirement="location">
                <div class="check-icon">✓</div>
                <span class="check-label">Location & trailhead</span>
              </div>
              <div class="checklist-item" data-requirement="photo">
                <div class="check-icon">✓</div>
                <span class="check-label">Adventure photo</span>
              </div>
              <div class="checklist-item" data-requirement="distance">
                <div class="check-icon">✓</div>
                <span class="check-label">Distance & difficulty</span>
              </div>
            </div>
          </div>
          
          <div class="quick-stats-card">
            <h3 class="stats-title">📈 Trip Stats</h3>
            <div class="stats-grid">
              <div class="stat-item">
                <div class="stat-value" id="insight-duration">-</div>
                <div class="stat-label">Duration</div>
              </div>
              <div class="stat-item">
                <div class="stat-value" id="insight-distance">-</div>
                <div class="stat-label">Distance</div>
              </div>
              <div class="stat-item">
                <div class="stat-value" id="insight-elevation">-</div>
                <div class="stat-label">Elevation</div>
              </div>
            </div>
          </div>
          
          <div class="backpack-selector-card">
            <h3 class="backpack-title">🎒 Choose Your Pack</h3>
            <div class="backpack-preview" id="selected-backpack">
              <div class="no-pack-selected">
                <div class="pack-icon">🎒</div>
                <p>No pack selected yet</p>
                <p class="pack-hint">Link a backpack to this adventure</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</div>

<style>
/* Force 2-column layout with sidebar beside form */
@media (min-width: 1024px) {
  .editor-layout {
    display: grid !important;
    grid-template-columns: 1fr 320px !important;
    grid-template-areas: 
      "header header"
      "content sidebar" !important;
    gap: 2rem !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
  }
  
  .adventure-visual-header {
    grid-area: header !important;
    width: 100% !important;
  }
  
  .editor-content {
    grid-area: content !important;
    min-width: 0 !important;
  }
  
  .adventure-sidebar {
    display: flex !important;
    grid-area: sidebar !important;
    width: 320px !important;
    flex-direction: column !important;
    gap: 1rem !important;
    background: rgba(255, 255, 255, 0.05) !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    height: fit-content !important;
  }
  
  /* Make sure planning cards stack properly in sidebar */
  .planning-card,
  .quick-stats-card,
  .backpack-selector-card {
    background: rgba(255, 255, 255, 0.08) !important;
    border-radius: 8px !important;
    padding: 1rem !important;
    margin-bottom: 1rem !important;
  }
}
</style>

<!-- Trips JavaScript is loaded via $pageScripts in the footer -->
<script>
// Connect hero controls to existing functionality
document.addEventListener('DOMContentLoaded', function() {
  // Wait for the hidden controls to be available
  const connectControls = function() {
      
      // Connect hero search to hidden search input
      const heroSearch = document.getElementById('trip-search-hero');
      const hiddenSearch = document.getElementById('trip-search');
      if (heroSearch && hiddenSearch) {
        heroSearch.addEventListener('input', function() {
          hiddenSearch.value = this.value;
          hiddenSearch.dispatchEvent(new Event('input', { bubbles: true }));
        });
      }
      
      // Connect hero sort to hidden sort select
      const heroSort = document.getElementById('sort-hero');
      const hiddenSort = document.getElementById('sort-trips');
      if (heroSort && hiddenSort) {
        heroSort.addEventListener('change', function() {
          hiddenSort.value = this.value;
          hiddenSort.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      
      // Connect view toggle buttons
      document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const view = this.dataset.view;
          // Find corresponding view button in the existing UI
          document.querySelectorAll('.view-btn').forEach(viewBtn => {
            if (viewBtn.dataset.mode === view) {
              viewBtn.click();
            }
          });
          // Update active state
          document.querySelectorAll('.view-toggle-btn').forEach(toggleBtn => {
            toggleBtn.classList.remove('active');
          });
          this.classList.add('active');
        });
      });
  };
  
  // Run immediately since controls are in the same file
  connectControls();
});

</script>

<?php 
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>
