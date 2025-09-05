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
$pageScripts[] = 'js/trips.js'; // Main trips functionality
$pageScripts[] = 'js/date-range-picker.js'; // Date range picker
$pageScripts[] = 'js/trip-packing.js'; // Trip packing list functionality

// Add page-specific styles
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/trip-form-improved.css'; // Improved form styles
$pageStyles[] = 'css/trips-list-view-and-dropdowns.css'; // List view and dropdown fixes
$pageStyles[] = 'css/simple-photo-upload.css'; // Simple photo upload controls
$pageStyles[] = 'css/trip-packing.css'; // Trip packing list styles

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Adventures Page Header -->
<div class="adventures-hero">
  <div class="hero-content">
    <div class="hero-text">
      <h1 class="hero-title">🗺️ Your Adventures</h1>
      <p class="hero-subtitle">Plan, track, and relive your epic trail experiences</p>
    </div>
    <div class="hero-actions">
      <button id="btn-new-trip" class="btn btn-adventure btn-hero">
        <span class="btn-icon">✨</span>
        <span>Start New Adventure</span>
      </button>
    </div>
  </div>
</div>

<!-- Adventures Layout Container -->
<div class="adventures-layout" id="main-content">
  <!-- Adventure Controls -->
  <div class="adventures-controls">
    <div class="search-control">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input id="trip-search" type="search" class="search-input" placeholder="Search your adventures..." aria-label="Search trips" />
      </div>
    </div>
    <div class="view-controls">
      <div class="sort-control">
        <select id="sort-trips" class="sort-select">
          <option value="recent">🕰️ Recently Created</option>
          <option value="name">📝 Name</option>
          <option value="date">📅 Start Date</option>
        </select>
      </div>
      <div class="view-toggle">
        <button class="view-btn active" data-mode="grid" title="Grid View">⬜</button>
        <button class="view-btn" data-mode="list" title="List View">☰</button>
      </div>
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
            <!-- Simple photo display area -->
            <div class="adventure-image-container" id="adventure-image-display">
              <div class="placeholder-image">
                <div class="placeholder-icon">🏔️</div>
                <p class="placeholder-text">No photo uploaded</p>
              </div>
            </div>
            
            <!-- Simple photo upload controls -->
            <div class="photo-upload-controls">
              <input id="photo" name="photo" type="file" accept="image/jpeg,image/jpg,image/png" class="form-input-file" />
              <input id="photo_alt_text" name="photo_alt_text" type="text" placeholder="Photo description (required for accessibility)" class="form-input" style="margin-top: 8px;" />
              <button type="button" class="btn btn-danger btn-sm" id="btn-remove-photo" style="margin-top: 8px; display: none;">Remove Photo</button>
            </div>
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
            </div>
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
                          <button type="button" class="btn btn-secondary btn-sm" id="clear-dates">Clear Dates</button>
                          <button type="button" class="btn btn-adventure btn-sm" id="apply-dates">Apply</button>
                        </div>
                        <div class="date-picker-calendars">
                          <div id="start-calendar" class="calendar"></div>
                          <div id="end-calendar" class="calendar"></div>
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

<!-- Trips JavaScript is loaded via $pageScripts in the footer -->
<script>
// This small script ensures trips.js runs after all dependencies are loaded
window.addEventListener('load', function() {
  console.log('Trips page fully loaded');
});

</script>

<?php 
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>
