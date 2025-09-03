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
$pageTitle = 'Plan Your Adventure';
$pageDescription = 'Plan, track, and remember your backpacking adventures on the trails';

// Use trip-specific styles with pack-builder base for shared components
$pageStyles = $pageStyles ?? [];
$pageStyles[] = 'css/pack-builder.css';         // Base layout and components
$pageStyles[] = 'css/pack-builder-enhanced.css'; // Enhanced pack styles
$pageStyles[] = 'css/trip-builder.css';         // Trip-specific overrides
$pageStyles[] = 'css/trip-builder-enhanced.css'; // Trip-specific enhancements
$pageStyles[] = 'css/trip-builder-compat.css';  // Browser compatibility fixes
$pageStyles[] = 'css/form-inputs.css';          // Form input styles

// Add page-specific scripts
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/trips-loading.js';  // Loading states enhancement
$pageScripts[] = 'js/form-validation.js'; // Form validation system
$pageScripts[] = 'js/trips-form-validation.js'; // Trips-specific validation
$pageScripts[] = 'js/trips.js'; // Main trips functionality

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Main Trips Builder Container (mirrors Pack Builder layout) -->
<div class="pack-builder-container" id="main-content">
  <!-- Action Bar with Tabs -->
  <div class="pack-action-bar" role="navigation" aria-label="Trip views">
  <div class="pack-tabs" role="tablist" aria-label="Trip views">
    <button id="tab-my-trips" class="pack-tab active" role="tab" aria-selected="true" aria-controls="panel-my-trips">🗺️ My Adventures</button>
    <button id="tab-trip-editor" class="pack-tab" role="tab" aria-selected="false" aria-controls="panel-trip-editor">✍️ Plan New Trip</button>
  </div>

    <div class="pack-actions">
      <div class="search-bar" role="search">
        <span class="search-icon" aria-hidden="true">🔍</span>
        <input id="trip-search" type="search" placeholder="Search trips by name or location" aria-label="Search trips" />
      </div>
      <button id="btn-new-trip" class="btn-action" aria-label="Start planning a new adventure">
        <i class="icon">➕</i> Start Adventure
      </button>
    </div>
  </div>

  <!-- Content Views -->
  <div class="pack-content">
    <!-- My Trips View -->
    <section id="panel-my-trips" class="pack-view active" role="tabpanel" aria-labelledby="tab-my-trips">
      <div class="packs-header">
        <h2 class="view-title">Your Trail Adventures</h2>
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
        <div class="packs-empty-text">No adventures yet</div>
        <div class="packs-empty-subtext">Ready to hit the trails? Start planning your first adventure!</div>
        <button id="empty-create" class="btn-action"><i>➕</i> Plan First Trip</button>
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

            <form id="trip-form" data-validate>
              <input type="hidden" id="trip-id" name="id" />

              <!-- Basics -->
              <section id="tab-panel-basics" class="pack-section" role="tabpanel" aria-labelledby="tab-btn-basics">
                <div class="section-header">
                  <h4 class="section-title">Basics</h4>
                </div>
                <div class="section-items">
                  <div class="form-row">
                    <div class="form-group">
                      <label for="title" class="form-label">
                        Trip Name <span class="required">*</span>
                      </label>
                      <input id="title" 
                             name="title" 
                             class="form-control" 
                             type="text" 
                             placeholder="e.g., PCT Section Hike"
                             required 
                             minlength="3" 
                             maxlength="100" />
                      <small class="form-text">Give your trip a memorable name</small>
                    </div>
                    <div class="form-group">
                      <label for="location" class="form-label">Location / Trailhead</label>
                      <div class="input-group">
                        <span class="input-group-text">📍</span>
                        <input id="location" 
                               name="location" 
                               class="form-control" 
                               type="text" 
                               placeholder="Trail or park name" 
                               maxlength="255" />
                      </div>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="start_date" class="form-label">Start Date</label>
                      <input id="start_date" 
                             name="start_date" 
                             class="form-control" 
                             type="date" />
                    </div>
                    <div class="form-group">
                      <label for="end_date" class="form-label">End Date</label>
                      <input id="end_date" 
                             name="end_date" 
                             class="form-control" 
                             type="date" 
                             data-validate="match:#start_date" />
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
                    <label for="description" class="form-label">Notes</label>
                    <textarea id="description" 
                              name="description" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Goals, highlights, gear notes..."
                              maxlength="1000"></textarea>
                    <small class="form-text">Add any notes about this trip</small>
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
                    <label for="photo_alt_text" class="form-label">Photo Description</label>
                    <input id="photo_alt_text" 
                           name="photo_alt_text" 
                           class="form-control" 
                           type="text" 
                           placeholder="Describe the photo for screen readers" 
                           maxlength="255" />
                    <small class="form-text">Required when uploading a photo (ADA compliance)</small>
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
                      <label for="distance" class="form-label">Distance</label>
                      <input id="distance" 
                             name="distance" 
                             class="form-control" 
                             type="number" 
                             step="0.1" 
                             min="0" 
                             max="5000"
                             placeholder="0.0" />
                    </div>
                    <div class="form-group">
                      <label for="distance_unit" class="form-label">Unit</label>
                      <select id="distance_unit" name="distance_unit" class="form-control">
                        <option value="miles">miles</option>
                        <option value="km">km</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="elevation_gain" class="form-label">Elevation Gain (ft)</label>
                      <input id="elevation_gain" 
                             name="elevation_gain" 
                             class="form-control" 
                             type="number" 
                             step="100" 
                             min="0" 
                             max="50000" />
                    </div>
                    <div class="form-group">
                      <label for="difficulty" class="form-label">Difficulty</label>
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
                    <label for="permit_info" class="form-label">Permit/Reservation Info</label>
                    <textarea id="permit_info" 
                              name="permit_info" 
                              class="form-control" 
                              rows="2" 
                              placeholder="How to obtain permits, lottery dates, etc."
                              maxlength="500"></textarea>
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
                    <label for="water_sources" class="form-label">Water Sources</label>
                    <textarea id="water_sources" 
                              name="water_sources" 
                              class="form-control" 
                              rows="2"
                              placeholder="Describe water availability along the trail"
                              maxlength="500"></textarea>
                  </div>
                  <div class="form-group">
                    <label for="trail_conditions" class="form-label">Trail Conditions</label>
                    <textarea id="trail_conditions" 
                              name="trail_conditions" 
                              class="form-control" 
                              rows="2"
                              placeholder="Current trail conditions, hazards, etc."
                              maxlength="500"></textarea>
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
