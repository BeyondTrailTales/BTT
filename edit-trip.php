<?php
// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

// Require authentication
require_auth();

// Include Database class
require_once __DIR__ . '/api/classes/Database.php';

// Set page metadata
$pageId = 'edit-trip';
$pageTitle = 'Edit Adventure';
$pageDescription = 'Edit your backpacking adventure details';

// Handle both edit and create modes
$tripId = $_GET['id'] ?? null;
$mode = $_GET['mode'] ?? 'edit';

// If no ID and no create mode, redirect to trips
if (empty($tripId) && $mode !== 'create') {
    header('Location: trips.php');
    exit;
}

// Add page scripts and CSS
$pageScripts = $pageScripts ?? [];
$pageScripts[] = 'js/save-animation.js'; // Save animation module
// $pageScripts[] = 'js/date-range-picker.js'; // Date range picker - causing conflicts, use basic input
$pageScripts[] = 'js/trip-packing.js'; // Trip packing list functionality
$pageScripts[] = 'js/edit-trip.js'; // Standalone trip editor functionality

$pageStyles = $pageStyles ?? [];
$cacheTime = time();
$pageStyles[] = 'css/edit-trip-standalone.css?v=' . $cacheTime; // Standalone editor styles
$pageStyles[] = 'css/save-animation.css?v=' . $cacheTime; // Save animation styles
// $pageStyles[] = 'css/date-picker-enhanced.css?v=' . $cacheTime; // Enhanced date picker styling - not needed
$pageStyles[] = 'css/photo-edit-overlay.css?v=' . $cacheTime; // Photo editing overlay

$bodyClasses = $bodyClasses ?? [];
$bodyClasses[] = 'edit-trip-page';
$bodyClasses[] = 'adventures-editor';

// Get database connection and check user
$db = Database::getInstance();

if (!isset($_SESSION['user_id'])) {
    redirect_to_login();
}
$user_id = $_SESSION['user_id'];

// Load existing trip data or create new trip template
$tripData = null;
if ($mode === 'create') {
    // Create empty trip data template for new trips
    $tripData = [
        'id' => null,
        'title' => '',
        'location' => '',
        'start_date' => '',
        'end_date' => '',
        'distance' => '',
        'distance_unit' => 'miles',
        'elevation_gain' => '',
        'difficulty' => '',
        'trip_type' => '',
        'description' => '',
        'backpack_id' => null,
        'completed' => 0,
        'favorite' => 0,
        'photo_path' => null,
        'photo_alt_text' => '',
        'permit_required' => 0,
        'permit_info' => '',
        'permit_cost' => null,
        'water_sources' => '',
        'camping_type' => '',
        'expected_weather' => '',
        'trail_conditions' => '',
        'emergency_contact' => '',
        'trailhead_parking' => '',
        'parking_cost' => null,
        'pre_trip_notes' => '',
        'post_trip_notes' => '',
        'lessons_learned' => '',
        'cell_coverage' => '',
        'crowd_level' => ''
    ];
    $pageTitle = 'New Adventure';
    $pageDescription = 'Plan your new backpacking adventure';
} else {
    // Load existing trip data
    try {
        $tripData = $db->fetchOne("SELECT * FROM trips WHERE id = ? AND user_id = ?", [$tripId, $user_id]);
        
        if (!$tripData) {
            // Trip not found or doesn't belong to user
            header('Location: trips.php');
            exit;
        }
    } catch (Exception $e) {
        error_log("Trip load error: " . $e->getMessage());
        header('Location: trips.php');
        exit;
    }
}

// Load user's backpacks for selection
$backpacks = [];
try {
    $backpacks = $db->fetchAll("SELECT id, name FROM backpacks WHERE user_id = ? ORDER BY name", [$user_id]);
    error_log("Loaded " . count($backpacks) . " backpacks for user " . $user_id);
} catch (Exception $e) {
    error_log("Backpacks load error: " . $e->getMessage());
}

// Include the unified template header
require_once __DIR__ . '/includes/template-header.php';
?>

<!-- Edit Adventure Page -->
<div class="edit-trip-container">
  
  <!-- Page Header with Back Button -->
  <div class="page-header">
    <div class="header-content">
      <div class="header-nav">
        <a href="trips.php" class="btn-back">
          <span class="back-icon">←</span>
          <span class="back-text">Back to Adventures</span>
        </a>
      </div>
      
      <div class="header-title">
        <h1 class="edit-title">
          <span class="title-icon"><?php echo $mode === 'create' ? '✨' : '✏️'; ?></span>
          <?php echo $mode === 'create' ? 'New Adventure' : 'Edit Adventure'; ?>
        </h1>
        <p class="edit-subtitle"><?php echo $mode === 'create' ? 'Plan your new backpacking adventure' : 'Update your adventure details and planning information'; ?></p>
      </div>
      
      <div class="header-actions">
        <button type="button" id="btn-save-trip" class="btn btn-primary btn-save">
          <span class="btn-icon">💾</span>
          <span class="btn-text">Save Changes</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Two-Column Layout -->
  <div class="edit-layout">
    
    <!-- Main Form Column -->
    <div class="form-column">
      
      <!-- Adventure Visual Header -->
      <div class="adventure-visual">
        <div class="adventure-image-section">
          <!-- Clickable photo display area -->
          <div class="adventure-image-container" id="adventure-image-display" role="button" tabindex="0" title="Click to add or change photo" style="cursor: pointer;">
            <?php if (!empty($tripData['photo_path'])): ?>
              <img src="<?php echo htmlspecialchars($tripData['photo_path']); ?>" 
                   alt="<?php echo htmlspecialchars($tripData['photo_alt_text'] ?? $tripData['title']); ?>" 
                   class="adventure-photo"
                   onerror="console.error('Failed to load image:', this.src); this.style.border='2px solid red';">
            <?php else: ?>
              <div class="placeholder-image">
                <div class="placeholder-icon">🏔️</div>
                <p class="placeholder-text">Click to add photo</p>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Photo upload overlay -->
          <div class="adventure-image-overlay">
            <div class="adventure-title-overlay">
              <h2 class="adventure-name-display"><?php echo htmlspecialchars($tripData['title'] ?: ($mode === 'create' ? 'New Adventure' : 'Untitled')); ?></h2>
              <?php if (!empty($tripData['location'])): ?>
                <p class="adventure-location-display"><?php echo htmlspecialchars($tripData['location']); ?></p>
              <?php else: ?>
                <p class="adventure-location-display" style="display: none;"></p>
              <?php endif; ?>
            </div>
            <div class="adventure-stats-overlay">
              <div class="stat-chip" id="duration-chip">📅 <?php 
                if ($tripData['start_date'] && $tripData['end_date']) {
                  $start = new DateTime($tripData['start_date']);
                  $end = new DateTime($tripData['end_date']);
                  $days = $start->diff($end)->days + 1;
                  echo $days . ' day' . ($days > 1 ? 's' : '');
                } else {
                  echo 'Duration';
                }
              ?></div>
              <?php if (!empty($tripData['distance'])): ?>
                <div class="stat-chip" id="distance-chip">🥾 <?php echo $tripData['distance'] . ' ' . ($tripData['distance_unit'] ?? 'miles'); ?></div>
              <?php endif; ?>
              <?php if (!empty($tripData['difficulty'])): ?>
                <div class="stat-chip" id="difficulty-chip">💪 <?php echo ucfirst($tripData['difficulty']); ?></div>
              <?php endif; ?>
            </div>
            
            <!-- Photo edit button -->
            <div class="photo-edit-overlay" id="photo-edit-overlay">
              <button type="button" class="photo-edit-btn" id="photo-edit-btn">
                <span class="photo-edit-icon">📸</span>
                <span class="photo-edit-text">Change Photo</span>
              </button>
              
              <?php if (!empty($tripData['photo_path'])): ?>
                <button type="button" class="btn btn-danger btn-sm" id="btn-remove-photo">🗑️ Remove</button>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Hidden file input -->
          <input id="photo" name="photo" type="file" accept="image/jpeg,image/jpg,image/png" class="hidden-file-input" style="display: none;" />
        </div>
      </div>

      <!-- Adventure Form with Tabs -->
      <div class="adventure-form-card">
        
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

        <div class="form-content">
          <form id="trip-form" class="trip-form" data-validate>
            <input type="hidden" id="trip-id" name="id" value="<?php echo $tripData['id'] ?? ''; ?>" />

            <!-- Basics Tab -->
            <section id="tab-panel-basics" class="form-section active" role="tabpanel" aria-labelledby="tab-btn-basics">
              <div class="form-group full-width">
                <label for="title" class="form-label required">Adventure Name</label>
                <input id="title" name="title" class="form-input" type="text" 
                       value="<?php echo htmlspecialchars($tripData['title']); ?>"
                       placeholder="e.g., PCT Section Hike" required minlength="3" maxlength="100" />
                <small class="form-help">Give your adventure a memorable name</small>
              </div>

              <div class="form-group full-width">
                <label for="location" class="form-label">Location / Trailhead</label>
                <input id="location" name="location" class="form-input" type="text" 
                       value="<?php echo htmlspecialchars($tripData['location'] ?? ''); ?>"
                       placeholder="📍 Trail or park name" maxlength="255" />
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label for="start_date" class="form-label">Start Date</label>
                  <input id="start_date" name="start_date" class="form-input" type="date" 
                         value="<?php echo $tripData['start_date']; ?>" />
                </div>
                <div class="form-group">
                  <label for="end_date" class="form-label">End Date</label>
                  <input id="end_date" name="end_date" class="form-input" type="date" 
                         value="<?php echo $tripData['end_date']; ?>" />
                </div>
              </div>

              <!-- Trip Metadata -->
              <div class="trip-metadata-section">
                <div class="metadata-row">
                  <div class="form-group">
                    <label for="trip_type" class="form-label">Trip Type</label>
                    <select id="trip_type" name="trip_type" class="form-select">
                      <option value="">Select type...</option>
                      <option value="day_hike" <?php echo ($tripData['trip_type'] === 'day_hike') ? 'selected' : ''; ?>>Day Hike</option>
                      <option value="overnight" <?php echo ($tripData['trip_type'] === 'overnight') ? 'selected' : ''; ?>>Overnight</option>
                      <option value="weekend" <?php echo ($tripData['trip_type'] === 'weekend') ? 'selected' : ''; ?>>Weekend</option>
                      <option value="section_hike" <?php echo ($tripData['trip_type'] === 'section_hike') ? 'selected' : ''; ?>>Section Hike</option>
                      <option value="thru_hike" <?php echo ($tripData['trip_type'] === 'thru_hike') ? 'selected' : ''; ?>>Thru-Hike</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="backpack_id" class="form-label">Backpack</label>
                    <select id="backpack_id" name="backpack_id" class="form-select">
                      <option value="">No backpack selected</option>
                      <?php foreach ($backpacks as $backpack): ?>
                        <option value="<?php echo $backpack['id']; ?>" 
                                <?php echo ($tripData['backpack_id'] == $backpack['id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($backpack['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                
                <!-- Toggle switches -->
                <div class="metadata-toggles">
                  <div class="toggle-group">
                    <input type="hidden" id="favorite" name="favorite" value="<?php echo $tripData['favorite'] ? '1' : '0'; ?>">
                    <label class="toggle-switch" for="favorite-toggle">
                      <input type="checkbox" id="favorite-toggle" <?php echo $tripData['favorite'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                      <span class="toggle-label">⭐ Favorite Adventure</span>
                    </label>
                  </div>
                  <div class="toggle-group">
                    <input type="hidden" id="completed" name="completed" value="<?php echo $tripData['completed'] ? '1' : '0'; ?>">
                    <label class="toggle-switch" for="completed-toggle">
                      <input type="checkbox" id="completed-toggle" <?php echo $tripData['completed'] ? 'checked' : ''; ?>>
                      <span class="toggle-slider"></span>
                      <span class="toggle-label">✅ Completed</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="form-group full-width">
                <label for="description" class="form-label">Adventure Notes</label>
                <textarea id="description" name="description" class="form-textarea" rows="3" 
                          placeholder="Goals, highlights, gear notes..." maxlength="1000"><?php echo htmlspecialchars($tripData['description'] ?? ''); ?></textarea>
                <small class="form-help">Add any notes about this adventure</small>
              </div>

              <div class="form-group full-width">
                <label for="photo_alt_text" class="form-label">📸 Photo Description</label>
                <input id="photo_alt_text" name="photo_alt_text" type="text" 
                       value="<?php echo htmlspecialchars($tripData['photo_alt_text'] ?? ''); ?>"
                       placeholder="Describe the photo for accessibility" class="form-input" />
                <small class="form-help">Brief description of your photo for screen readers</small>
              </div>

              <input id="remove_photo" name="remove_photo" type="hidden" value="0" />
            </section>

            <!-- Additional form sections for Trail Details, Logistics, Conditions, Notes, and Packing would go here -->
            <!-- For now, I'll include the basic structure and we can expand later -->
            
            <!-- Trail Details Tab -->
            <section id="tab-panel-trail" class="form-section" role="tabpanel" aria-labelledby="tab-btn-trail" hidden>
              <div class="form-grid">
                <div class="form-group">
                  <label for="distance" class="form-label">📏 Distance</label>
                  <input id="distance" name="distance" class="form-input" type="number" 
                         value="<?php echo $tripData['distance'] ?? ''; ?>"
                         step="0.1" min="0" max="5000" placeholder="0.0" />
                </div>
                <div class="form-group">
                  <label for="distance_unit" class="form-label">📐 Unit</label>
                  <select id="distance_unit" name="distance_unit" class="form-select">
                    <option value="miles" <?php echo ($tripData['distance_unit'] === 'miles') ? 'selected' : ''; ?>>miles</option>
                    <option value="km" <?php echo ($tripData['distance_unit'] === 'km') ? 'selected' : ''; ?>>km</option>
                  </select>
                </div>
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label for="elevation_gain" class="form-label">⛰️ Elevation Gain (ft)</label>
                  <input id="elevation_gain" name="elevation_gain" class="form-input" type="number" 
                         value="<?php echo $tripData['elevation_gain'] ?? ''; ?>"
                         step="100" min="0" max="50000" />
                </div>
                <div class="form-group">
                  <label for="difficulty" class="form-label">💪 Difficulty</label>
                  <select id="difficulty" name="difficulty" class="form-select">
                    <option value="">Select...</option>
                    <option value="easy" <?php echo ($tripData['difficulty'] === 'easy') ? 'selected' : ''; ?>>Easy</option>
                    <option value="moderate" <?php echo ($tripData['difficulty'] === 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                    <option value="hard" <?php echo ($tripData['difficulty'] === 'hard') ? 'selected' : ''; ?>>Hard</option>
                    <option value="expert" <?php echo ($tripData['difficulty'] === 'expert') ? 'selected' : ''; ?>>Expert</option>
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
                    <option value="0" <?php echo ($tripData['permit_required'] == 0) ? 'selected' : ''; ?>>No</option>
                    <option value="1" <?php echo ($tripData['permit_required'] == 1) ? 'selected' : ''; ?>>Yes</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="permit_cost" class="form-label">💰 Permit Cost ($)</label>
                  <input id="permit_cost" name="permit_cost" class="form-input" type="number" 
                         value="<?php echo htmlspecialchars($tripData['permit_cost'] ?? ''); ?>"
                         step="0.01" min="0" />
                </div>
              </div>
              <div class="form-group">
                <label for="permit_info" class="form-label">Permit/Reservation Info</label>
                <textarea id="permit_info" name="permit_info" class="form-textarea" rows="2" 
                          placeholder="How to obtain permits, lottery dates, etc." maxlength="500"><?php echo htmlspecialchars($tripData['permit_info'] ?? ''); ?></textarea>
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label for="trailhead_parking" class="form-label">🚗 Parking Info</label>
                  <input id="trailhead_parking" name="trailhead_parking" class="form-input" type="text" 
                         value="<?php echo htmlspecialchars($tripData['trailhead_parking'] ?? ''); ?>" />
                </div>
                <div class="form-group">
                  <label for="parking_cost" class="form-label">🅿️ Parking Cost ($)</label>
                  <input id="parking_cost" name="parking_cost" class="form-input" type="number" 
                         value="<?php echo htmlspecialchars($tripData['parking_cost'] ?? ''); ?>"
                         step="0.01" min="0" />
                </div>
              </div>
            </section>

            <!-- Conditions Tab -->
            <section id="tab-panel-conditions" class="form-section" role="tabpanel" aria-labelledby="tab-btn-conditions" hidden>
              <div class="form-group">
                <label for="water_sources" class="form-label">💧 Water Sources</label>
                <textarea id="water_sources" name="water_sources" class="form-textarea" rows="2"
                          placeholder="Describe water availability along the trail" maxlength="500"><?php echo htmlspecialchars($tripData['water_sources'] ?? ''); ?></textarea>
              </div>
              <div class="form-group">
                <label for="trail_conditions" class="form-label">🥾 Trail Conditions</label>
                <textarea id="trail_conditions" name="trail_conditions" class="form-textarea" rows="2"
                          placeholder="Current trail conditions, hazards, etc." maxlength="500"><?php echo htmlspecialchars($tripData['trail_conditions'] ?? ''); ?></textarea>
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label for="cell_coverage" class="form-label">📶 Cell Coverage</label>
                  <select id="cell_coverage" name="cell_coverage" class="form-select">
                    <option value="">Select...</option>
                    <option value="none" <?php echo ($tripData['cell_coverage'] === 'none') ? 'selected' : ''; ?>>None</option>
                    <option value="poor" <?php echo ($tripData['cell_coverage'] === 'poor') ? 'selected' : ''; ?>>Poor</option>
                    <option value="spotty" <?php echo ($tripData['cell_coverage'] === 'spotty') ? 'selected' : ''; ?>>Spotty</option>
                    <option value="good" <?php echo ($tripData['cell_coverage'] === 'good') ? 'selected' : ''; ?>>Good</option>
                    <option value="excellent" <?php echo ($tripData['cell_coverage'] === 'excellent') ? 'selected' : ''; ?>>Excellent</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="crowd_level" class="form-label">👥 Crowd Level</label>
                  <select id="crowd_level" name="crowd_level" class="form-select">
                    <option value="">Select...</option>
                    <option value="empty" <?php echo ($tripData['crowd_level'] === 'empty') ? 'selected' : ''; ?>>Empty</option>
                    <option value="light" <?php echo ($tripData['crowd_level'] === 'light') ? 'selected' : ''; ?>>Light</option>
                    <option value="moderate" <?php echo ($tripData['crowd_level'] === 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                    <option value="busy" <?php echo ($tripData['crowd_level'] === 'busy') ? 'selected' : ''; ?>>Busy</option>
                    <option value="packed" <?php echo ($tripData['crowd_level'] === 'packed') ? 'selected' : ''; ?>>Packed</option>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label for="camping_type" class="form-label">🏕️ Camping Type</label>
                <select id="camping_type" name="camping_type" class="form-select">
                  <option value="">Select camping type...</option>
                  <option value="dispersed" <?php echo ($tripData['camping_type'] === 'dispersed') ? 'selected' : ''; ?>>Dispersed Camping</option>
                  <option value="designated" <?php echo ($tripData['camping_type'] === 'designated') ? 'selected' : ''; ?>>Designated Sites</option>
                  <option value="frontcountry" <?php echo ($tripData['camping_type'] === 'frontcountry') ? 'selected' : ''; ?>>Frontcountry Campground</option>
                  <option value="backcountry" <?php echo ($tripData['camping_type'] === 'backcountry') ? 'selected' : ''; ?>>Backcountry Sites</option>
                  <option value="shelter" <?php echo ($tripData['camping_type'] === 'shelter') ? 'selected' : ''; ?>>Trail Shelter</option>
                  <option value="none" <?php echo ($tripData['camping_type'] === 'none') ? 'selected' : ''; ?>>No Camping</option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="expected_weather" class="form-label">🌤️ Expected Weather</label>
                <textarea id="expected_weather" name="expected_weather" class="form-textarea" rows="2" 
                          placeholder="Temperature range, precipitation, wind conditions..." maxlength="500"><?php echo htmlspecialchars($tripData['expected_weather'] ?? ''); ?></textarea>
              </div>
              
              <div class="form-group">
                <label for="emergency_contact" class="form-label">🚨 Emergency Contact</label>
                <input id="emergency_contact" name="emergency_contact" type="text" class="form-input" 
                       value="<?php echo htmlspecialchars($tripData['emergency_contact'] ?? ''); ?>"
                       placeholder="Name & phone number for emergency situations" maxlength="255" />
              </div>
            </section>

            <!-- Notes Tab -->
            <section id="tab-panel-notes" class="form-section" role="tabpanel" aria-labelledby="tab-btn-notes" hidden>
              <div class="form-group">
                <label for="pre_trip_notes" class="form-label">📝 Pre-Trip Notes</label>
                <textarea id="pre_trip_notes" name="pre_trip_notes" class="form-textarea" rows="3" 
                          placeholder="Planning notes, gear prep, permits..."><?php echo htmlspecialchars($tripData['pre_trip_notes'] ?? ''); ?></textarea>
              </div>
              <div class="form-group">
                <label for="post_trip_notes" class="form-label">📖 Post-Trip Notes</label>
                <textarea id="post_trip_notes" name="post_trip_notes" class="form-textarea" rows="3" 
                          placeholder="Trip highlights, experiences, memories..."><?php echo htmlspecialchars($tripData['post_trip_notes'] ?? ''); ?></textarea>
              </div>
              <div class="form-group">
                <label for="lessons_learned" class="form-label">🎓 Lessons Learned</label>
                <textarea id="lessons_learned" name="lessons_learned" class="form-textarea" rows="3" 
                          placeholder="What would you do differently next time?"><?php echo htmlspecialchars($tripData['lessons_learned'] ?? ''); ?></textarea>
              </div>
            </section>

            <!-- Packing List Tab -->
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
            
          </form>
        </div>
      </div>
    </div>

    <!-- Sidebar Column -->
    <div class="sidebar-column">
      
      <!-- Planning Checklist -->
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
      
      <!-- Trip Stats -->
      <div class="quick-stats-card">
        <h3 class="stats-title">📈 Trip Stats</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-value" id="insight-duration"><?php 
              if ($tripData['start_date'] && $tripData['end_date']) {
                $start = new DateTime($tripData['start_date']);
                $end = new DateTime($tripData['end_date']);
                $days = $start->diff($end)->days + 1;
                echo $days . ' day' . ($days > 1 ? 's' : '');
              } else {
                echo '-';
              }
            ?></div>
            <div class="stat-label">Duration</div>
          </div>
          <div class="stat-item">
            <div class="stat-value" id="insight-distance"><?php 
              echo $tripData['distance'] ? $tripData['distance'] . ' ' . ($tripData['distance_unit'] ?? 'mi') : '-';
            ?></div>
            <div class="stat-label">Distance</div>
          </div>
          <div class="stat-item">
            <div class="stat-value" id="insight-elevation"><?php 
              echo $tripData['elevation_gain'] ? number_format($tripData['elevation_gain']) . ' ft' : '-';
            ?></div>
            <div class="stat-label">Elevation</div>
          </div>
        </div>
      </div>
      
      <!-- Backpack Selector -->
      <div class="backpack-selector-card">
        <h3 class="backpack-title">🎒 Choose Your Pack</h3>
        <div class="backpack-preview" id="selected-backpack">
          <?php if ($tripData['backpack_id'] && !empty($backpacks)): ?>
            <?php 
              $selectedBackpack = null;
              foreach ($backpacks as $bp) {
                if ($bp['id'] == $tripData['backpack_id']) {
                  $selectedBackpack = $bp;
                  break;
                }
              }
            ?>
            <?php if ($selectedBackpack): ?>
              <div class="pack-selected">
                <div class="pack-icon">🎒</div>
                <p class="pack-name"><?php echo htmlspecialchars($selectedBackpack['name']); ?></p>
                <p class="pack-hint">Selected backpack</p>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="no-pack-selected">
              <div class="pack-icon">🎒</div>
              <p>No pack selected yet</p>
              <p class="pack-hint">Link a backpack to this adventure</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Form Status and Messages -->
<div id="form-messages" class="form-messages" role="alert" aria-live="assertive"></div>

<!-- Initialize Edit Trip -->
<script>
window.TripEditConfig = {
  tripId: <?php echo json_encode($tripId); ?>,
  mode: '<?php echo $mode; ?>',
  apiUrl: '<?php echo route_url(""); ?>/ajax-handler.php',
  csrfToken: '<?php echo csrf_token(); ?>',
  tripData: <?php echo json_encode($tripData); ?>,
  backpacks: <?php echo json_encode($backpacks); ?>
};

document.addEventListener('DOMContentLoaded', function() {
  if (window.EditTrip) {
    EditTrip.init();
  }
});
</script>

<?php
// Include the unified template footer
require_once __DIR__ . '/includes/template-footer.php';
?>