/**
 * Trip Wizard - 5-Step Progressive Disclosure
 * Creates a smooth trip planning experience
 */

const TripWizard = {
  currentStep: 1,
  totalSteps: 5,
  autoSaveTimer: null,
  tripData: {},
  
  steps: {
    1: { title: 'Basics', icon: '📝', required: true },
    2: { title: 'Route', icon: '🗺️', required: false },
    3: { title: 'Gear', icon: '🎒', required: false },
    4: { title: 'Food & Water', icon: '🍎', required: false },
    5: { title: 'Review', icon: '✅', required: true }
  },
  
  init() {
    this.loadSavedData();
    this.renderWizard();
    this.bindEvents();
    this.initAutosave();
  },
  
  renderWizard() {
    const container = $('#trip-wizard-container');
    if (!container.length) return;
    
    // Render progress bar
    const progressHtml = this.renderProgressBar();
    
    // Render step content
    const contentHtml = this.renderStepContent();
    
    // Render navigation
    const navHtml = this.renderNavigation();
    
    container.html(`
      <div class="trip-wizard">
        ${progressHtml}
        <div class="wizard-content">
          ${contentHtml}
        </div>
        ${navHtml}
      </div>
    `);
  },
  
  renderProgressBar() {
    let html = '<div class="wizard-progress">';
    
    for (let i = 1; i <= this.totalSteps; i++) {
      const step = this.steps[i];
      const isActive = i === this.currentStep;
      const isCompleted = this.isStepCompleted(i);
      const classes = `wizard-step ${isActive ? 'active' : ''} ${isCompleted ? 'completed' : ''}`;
      
      html += `
        <div class="${classes}" data-step="${i}">
          <div class="wizard-step-number">
            ${isCompleted && !isActive ? '✓' : step.icon}
          </div>
          <div class="wizard-step-label">${step.title}</div>
        </div>
      `;
    }
    
    // Progress line
    const progress = ((this.currentStep - 1) / (this.totalSteps - 1)) * 100;
    html += `<div class="wizard-progress-line" style="width: ${progress}%"></div>`;
    html += '</div>';
    
    return html;
  },
  
  renderStepContent() {
    let html = '';
    
    for (let i = 1; i <= this.totalSteps; i++) {
      const isActive = i === this.currentStep;
      html += `
        <div class="step-content ${isActive ? 'active' : ''}" data-step="${i}">
          ${this.getStepContent(i)}
        </div>
      `;
    }
    
    return html;
  },
  
  getStepContent(step) {
    switch(step) {
      case 1:
        return this.renderBasicsStep();
      case 2:
        return this.renderRouteStep();
      case 3:
        return this.renderGearStep();
      case 4:
        return this.renderFoodWaterStep();
      case 5:
        return this.renderReviewStep();
      default:
        return '';
    }
  },
  
  renderBasicsStep() {
    return `
      <div class="step-header">
        <h2 class="step-title">Trip Basics</h2>
        <p class="step-description">Let's start with the essentials</p>
      </div>
      
      <div class="wizard-form-grid">
        <div class="essential-fields">
          <div class="form-group">
            <label for="trip-name">Trip Name <span class="required">*</span></label>
            <input type="text" id="trip-name" name="title" 
                   placeholder="e.g., Yosemite Weekend" 
                   value="${this.tripData.title || ''}" required>
            <span class="help-tip" title="Give your trip a memorable name">?</span>
          </div>
          
          <div class="wizard-form-row">
            <div class="form-group">
              <label for="start-date">Start Date <span class="required">*</span></label>
              <input type="date" id="start-date" name="start_date" 
                     value="${this.tripData.start_date || ''}" required>
            </div>
            
            <div class="form-group">
              <label for="end-date">End Date <span class="required">*</span></label>
              <input type="date" id="end-date" name="end_date" 
                     value="${this.tripData.end_date || ''}" required>
            </div>
          </div>
          
          <div class="form-group">
            <label for="party-size">Party Size</label>
            <input type="number" id="party-size" name="party_size" 
                   min="1" max="20" value="${this.tripData.party_size || 1}">
            <span class="help-tip" title="How many people are going?">?</span>
          </div>
        </div>
        
        <button class="show-advanced-btn" onclick="TripWizard.toggleAdvanced(1)">
          <span class="chevron">▼</span> Show Advanced Options
        </button>
        
        <div class="advanced-fields" id="advanced-1" style="display: none;">
          <div class="form-group">
            <label for="trip-location">Location</label>
            <input type="text" id="trip-location" name="location" 
                   placeholder="e.g., Yosemite National Park" 
                   value="${this.tripData.location || ''}">
          </div>
          
          <div class="form-group">
            <label for="trip-description">Description</label>
            <textarea id="trip-description" name="description" rows="3"
                      placeholder="Add notes about your trip...">${this.tripData.description || ''}</textarea>
          </div>
        </div>
        
        <div class="quick-suggestions">
          <h3>Quick Templates:</h3>
          <button class="suggestion-chip" onclick="TripWizard.useTemplate('weekend')">
            Weekend Backpacking
          </button>
          <button class="suggestion-chip" onclick="TripWizard.useTemplate('day')">
            Day Hike
          </button>
          <button class="suggestion-chip" onclick="TripWizard.useTemplate('thru')">
            Thru-Hike Section
          </button>
        </div>
      </div>
    `;
  },
  
  renderRouteStep() {
    return `
      <div class="step-header">
        <h2 class="step-title">Route Planning</h2>
        <p class="step-description">Map out your journey (optional)</p>
      </div>
      
      <div class="wizard-form-grid">
        <div class="wizard-form-row">
          <div class="form-group">
            <label for="total-distance">Total Distance</label>
            <input type="number" id="total-distance" name="distance" 
                   step="0.1" min="0" placeholder="0.0"
                   value="${this.tripData.distance || ''}">
            <select name="distance_unit" style="width: auto; margin-left: 0.5rem;">
              <option value="mi" ${this.tripData.distance_unit === 'mi' ? 'selected' : ''}>miles</option>
              <option value="km" ${this.tripData.distance_unit === 'km' ? 'selected' : ''}>km</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="elevation-gain">Elevation Gain</label>
            <input type="number" id="elevation-gain" name="elevation_gain" 
                   step="100" min="0" placeholder="0"
                   value="${this.tripData.elevation_gain || ''}">
            <span style="margin-left: 0.5rem;">feet</span>
          </div>
        </div>
        
        <div class="form-group">
          <label>Route Sections</label>
          <div id="route-sections">
            ${this.renderRouteSections()}
          </div>
          <button class="btn btn-secondary btn-sm" onclick="TripWizard.addRouteSection()">
            + Add Section
          </button>
        </div>
      </div>
    `;
  },
  
  renderGearStep() {
    return `
      <div class="step-header">
        <h2 class="step-title">Select Your Pack</h2>
        <p class="step-description">Choose or create a backpack for this trip</p>
      </div>
      
      <div class="pack-selection-grid">
        <div class="pack-option" onclick="TripWizard.selectPack('quick')">
          <div class="pack-option-icon">⚡</div>
          <div class="pack-option-name">Quick Pack</div>
          <div class="pack-option-detail">Auto-generate from favorites</div>
        </div>
        
        <div class="pack-option" onclick="TripWizard.selectPack('existing')">
          <div class="pack-option-icon">🎒</div>
          <div class="pack-option-name">Use Existing</div>
          <div class="pack-option-detail">Select from your packs</div>
        </div>
        
        <div class="pack-option" onclick="TripWizard.selectPack('new')">
          <div class="pack-option-icon">✨</div>
          <div class="pack-option-name">Create New</div>
          <div class="pack-option-detail">Build from scratch</div>
        </div>
        
        <div class="pack-option" onclick="TripWizard.selectPack('later')">
          <div class="pack-option-icon">⏰</div>
          <div class="pack-option-name">Pack Later</div>
          <div class="pack-option-detail">Skip for now</div>
        </div>
      </div>
      
      <div id="pack-selection-detail"></div>
    `;
  },
  
  renderFoodWaterStep() {
    return `
      <div class="step-header">
        <h2 class="step-title">Food & Water Planning</h2>
        <p class="step-description">Estimate your consumables (optional)</p>
      </div>
      
      <div class="wizard-form-grid">
        <div class="essential-fields">
          <div class="wizard-form-row">
            <div class="form-group">
              <label for="water-per-day">Water per Day</label>
              <input type="number" id="water-per-day" name="water_per_day" 
                     step="0.5" min="0" placeholder="3.0"
                     value="${this.tripData.water_per_day || '3'}">
              <span style="margin-left: 0.5rem;">liters</span>
            </div>
            
            <div class="form-group">
              <label for="calories-per-day">Calories per Day</label>
              <input type="number" id="calories-per-day" name="calories_per_day" 
                     step="100" min="0" placeholder="2500"
                     value="${this.tripData.calories_per_day || '2500'}">
            </div>
          </div>
          
          <div class="form-group">
            <label>Quick Estimate:</label>
            <div class="quick-suggestions">
              <button class="suggestion-chip" onclick="TripWizard.setFoodWater('light')">
                Light (2000 cal, 2.5L)
              </button>
              <button class="suggestion-chip" onclick="TripWizard.setFoodWater('moderate')">
                Moderate (2500 cal, 3L)
              </button>
              <button class="suggestion-chip" onclick="TripWizard.setFoodWater('heavy')">
                Heavy (3000 cal, 4L)
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  },
  
  renderReviewStep() {
    const days = this.calculateDays();
    
    return `
      <div class="step-header">
        <h2 class="step-title">Review Your Trip</h2>
        <p class="step-description">Everything look good?</p>
      </div>
      
      <div class="review-sections">
        <div class="review-section">
          <h3 class="review-section-title">Trip Details</h3>
          <div class="review-item">
            <span class="review-label">Name:</span>
            <span class="review-value">${this.tripData.title || 'Not set'}</span>
          </div>
          <div class="review-item">
            <span class="review-label">Dates:</span>
            <span class="review-value">
              ${this.tripData.start_date || 'Not set'} to ${this.tripData.end_date || 'Not set'}
              (${days} days)
            </span>
          </div>
          <div class="review-item">
            <span class="review-label">Party Size:</span>
            <span class="review-value">${this.tripData.party_size || 1} people</span>
          </div>
          ${this.tripData.location ? `
          <div class="review-item">
            <span class="review-label">Location:</span>
            <span class="review-value">${this.tripData.location}</span>
          </div>
          ` : ''}
        </div>
        
        ${this.tripData.distance ? `
        <div class="review-section">
          <h3 class="review-section-title">Route</h3>
          <div class="review-item">
            <span class="review-label">Distance:</span>
            <span class="review-value">${this.tripData.distance} ${this.tripData.distance_unit || 'mi'}</span>
          </div>
          ${this.tripData.elevation_gain ? `
          <div class="review-item">
            <span class="review-label">Elevation Gain:</span>
            <span class="review-value">${this.tripData.elevation_gain} ft</span>
          </div>
          ` : ''}
        </div>
        ` : ''}
        
        <div class="review-section">
          <h3 class="review-section-title">Preparation</h3>
          <div class="review-item">
            <span class="review-label">Pack:</span>
            <span class="review-value">${this.tripData.pack_status || 'Not selected'}</span>
          </div>
          <div class="review-item">
            <span class="review-label">Food & Water:</span>
            <span class="review-value">
              ${this.tripData.calories_per_day || 2500} cal/day, 
              ${this.tripData.water_per_day || 3}L/day
            </span>
          </div>
        </div>
      </div>
      
      <div class="review-actions">
        <button class="btn btn-primary btn-lg" onclick="TripWizard.saveTrip()">
          🎉 Create Trip
        </button>
        <button class="btn btn-secondary" onclick="TripWizard.saveAsTemplate()">
          Save as Template
        </button>
      </div>
    `;
  },
  
  renderNavigation() {
    const canGoBack = this.currentStep > 1;
    const canGoNext = this.currentStep < this.totalSteps;
    const isReview = this.currentStep === this.totalSteps;
    
    return `
      <div class="wizard-actions">
        <div class="wizard-actions-left">
          ${canGoBack ? `
            <button class="btn btn-secondary" onclick="TripWizard.previousStep()">
              ← Previous
            </button>
          ` : ''}
        </div>
        
        <div class="wizard-actions-right">
          <div class="autosave-indicator" id="autosave-indicator">
            <span class="autosave-icon">💾</span>
            <span class="autosave-text">Draft saved</span>
          </div>
          
          ${!isReview && canGoNext ? `
            <button class="btn btn-primary" onclick="TripWizard.nextStep()">
              Next →
            </button>
          ` : ''}
        </div>
      </div>
    `;
  },
  
  bindEvents() {
    // Step clicks
    $(document).on('click', '.wizard-step', function() {
      const step = $(this).data('step');
      TripWizard.goToStep(step);
    });
    
    // Form field changes trigger autosave
    $(document).on('change', '.wizard-form-grid input, .wizard-form-grid textarea, .wizard-form-grid select', function() {
      TripWizard.fieldChanged($(this));
    });
    
    // Keyboard navigation
    $(document).on('keydown', function(e) {
      if (e.altKey && e.key === 'ArrowRight') {
        TripWizard.nextStep();
      } else if (e.altKey && e.key === 'ArrowLeft') {
        TripWizard.previousStep();
      }
    });
  },
  
  nextStep() {
    if (this.currentStep < this.totalSteps) {
      // Validate required fields
      if (this.validateStep(this.currentStep)) {
        this.goToStep(this.currentStep + 1);
      }
    }
  },
  
  previousStep() {
    if (this.currentStep > 1) {
      this.goToStep(this.currentStep - 1);
    }
  },
  
  goToStep(step) {
    if (step < 1 || step > this.totalSteps) return;
    
    this.currentStep = parseInt(step);
    this.renderWizard();
    
    // Scroll to top of wizard
    $('.trip-wizard').get(0)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  },
  
  validateStep(step) {
    if (step === 1) {
      // Validate basics
      const title = $('#trip-name').val();
      const startDate = $('#start-date').val();
      const endDate = $('#end-date').val();
      
      if (!title || !startDate || !endDate) {
        UXUtils.toast('Please fill in all required fields', 'error');
        return false;
      }
      
      if (new Date(startDate) > new Date(endDate)) {
        UXUtils.toast('End date must be after start date', 'error');
        return false;
      }
    }
    
    return true;
  },
  
  fieldChanged(field) {
    const name = field.attr('name');
    const value = field.val();
    
    if (name) {
      this.tripData[name] = value;
      this.triggerAutosave();
    }
  },
  
  initAutosave() {
    // Load any existing draft
    const draft = localStorage.getItem('tripWizardDraft');
    if (draft) {
      try {
        this.tripData = JSON.parse(draft);
      } catch (e) {
        console.error('Failed to load draft:', e);
      }
    }
  },
  
  triggerAutosave() {
    clearTimeout(this.autoSaveTimer);
    
    this.autoSaveTimer = setTimeout(() => {
      this.saveDraft();
    }, 1000); // Save after 1 second of inactivity
  },
  
  saveDraft() {
    localStorage.setItem('tripWizardDraft', JSON.stringify(this.tripData));
    this.showAutosaveIndicator();
  },
  
  showAutosaveIndicator() {
    const indicator = $('#autosave-indicator');
    indicator.addClass('saving');
    indicator.find('.autosave-text').text('Saving...');
    
    setTimeout(() => {
      indicator.removeClass('saving').addClass('saved');
      indicator.find('.autosave-text').text('Draft saved');
      
      setTimeout(() => {
        indicator.removeClass('saved');
      }, 2000);
    }, 500);
  },
  
  toggleAdvanced(step) {
    const advanced = $(`#advanced-${step}`);
    const btn = $('.show-advanced-btn');
    
    advanced.slideToggle();
    btn.toggleClass('expanded');
    
    if (btn.hasClass('expanded')) {
      btn.html('<span class="chevron">▲</span> Hide Advanced Options');
    } else {
      btn.html('<span class="chevron">▼</span> Show Advanced Options');
    }
  },
  
  useTemplate(type) {
    switch(type) {
      case 'weekend':
        this.tripData = {
          ...this.tripData,
          party_size: 2,
          distance: 15,
          distance_unit: 'mi',
          elevation_gain: 2000,
          water_per_day: 3,
          calories_per_day: 2500
        };
        UXUtils.toast('Weekend template applied', 'success');
        break;
      case 'day':
        this.tripData = {
          ...this.tripData,
          party_size: 1,
          distance: 8,
          distance_unit: 'mi',
          elevation_gain: 1000,
          water_per_day: 2,
          calories_per_day: 1500
        };
        UXUtils.toast('Day hike template applied', 'success');
        break;
      case 'thru':
        this.tripData = {
          ...this.tripData,
          party_size: 1,
          distance: 50,
          distance_unit: 'mi',
          elevation_gain: 8000,
          water_per_day: 4,
          calories_per_day: 3500
        };
        UXUtils.toast('Thru-hike template applied', 'success');
        break;
    }
    
    this.renderWizard();
    this.triggerAutosave();
  },
  
  addRouteSection() {
    if (!this.tripData.sections) {
      this.tripData.sections = [];
    }
    
    this.tripData.sections.push({
      name: '',
      distance: 0,
      description: ''
    });
    
    this.renderWizard();
  },
  
  renderRouteSections() {
    if (!this.tripData.sections || this.tripData.sections.length === 0) {
      return '<p class="text-muted">No sections added yet</p>';
    }
    
    return this.tripData.sections.map((section, index) => `
      <div class="route-section">
        <input type="text" placeholder="Section name" 
               value="${section.name}" 
               onchange="TripWizard.updateSection(${index}, 'name', this.value)">
        <input type="number" placeholder="Distance" step="0.1" min="0"
               value="${section.distance}" 
               onchange="TripWizard.updateSection(${index}, 'distance', this.value)">
        <button onclick="TripWizard.removeSection(${index})">×</button>
      </div>
    `).join('');
  },
  
  updateSection(index, field, value) {
    if (this.tripData.sections && this.tripData.sections[index]) {
      this.tripData.sections[index][field] = value;
      this.triggerAutosave();
    }
  },
  
  removeSection(index) {
    if (this.tripData.sections) {
      this.tripData.sections.splice(index, 1);
      this.renderWizard();
      this.triggerAutosave();
    }
  },
  
  selectPack(type) {
    $('.pack-option').removeClass('selected');
    $(event.target).closest('.pack-option').addClass('selected');
    
    this.tripData.pack_type = type;
    this.tripData.pack_status = this.getPackStatusText(type);
    
    // Show relevant options based on selection
    const detailContainer = $('#pack-selection-detail');
    
    switch(type) {
      case 'existing':
        this.loadExistingPacks(detailContainer);
        break;
      case 'quick':
        detailContainer.html('<p class="info-message">✨ We\'ll generate a pack from your favorite gear</p>');
        break;
      case 'new':
        detailContainer.html('<p class="info-message">🎒 You\'ll build a new pack after creating the trip</p>');
        break;
      case 'later':
        detailContainer.html('<p class="info-message">⏰ You can add a pack anytime</p>');
        break;
    }
    
    this.triggerAutosave();
  },
  
  getPackStatusText(type) {
    switch(type) {
      case 'existing': return 'Using existing pack';
      case 'quick': return 'Quick pack (auto-generated)';
      case 'new': return 'Will create new pack';
      case 'later': return 'Pack later';
      default: return 'Not selected';
    }
  },
  
  loadExistingPacks(container) {
    container.html('<div class="loading">Loading your packs...</div>');
    
    // Fetch packs via API
    $.get('/BTT/api/index.php?route=backpacks', (data) => {
      if (data.data && data.data.length > 0) {
        let html = '<select id="existing-pack-select" onchange="TripWizard.selectExistingPack(this.value)">';
        html += '<option value="">Select a pack...</option>';
        
        data.data.forEach(pack => {
          html += `<option value="${pack.id}">${pack.name} (${pack.base_weight_g}g)</option>`;
        });
        
        html += '</select>';
        container.html(html);
      } else {
        container.html('<p class="warning-message">No packs found. Create one first!</p>');
      }
    });
  },
  
  selectExistingPack(packId) {
    this.tripData.backpack_id = packId;
    this.triggerAutosave();
  },
  
  setFoodWater(level) {
    switch(level) {
      case 'light':
        $('#calories-per-day').val(2000);
        $('#water-per-day').val(2.5);
        this.tripData.calories_per_day = 2000;
        this.tripData.water_per_day = 2.5;
        break;
      case 'moderate':
        $('#calories-per-day').val(2500);
        $('#water-per-day').val(3);
        this.tripData.calories_per_day = 2500;
        this.tripData.water_per_day = 3;
        break;
      case 'heavy':
        $('#calories-per-day').val(3000);
        $('#water-per-day').val(4);
        this.tripData.calories_per_day = 3000;
        this.tripData.water_per_day = 4;
        break;
    }
    
    this.triggerAutosave();
    UXUtils.toast('Food & water estimate applied', 'success');
  },
  
  calculateDays() {
    if (this.tripData.start_date && this.tripData.end_date) {
      const start = new Date(this.tripData.start_date);
      const end = new Date(this.tripData.end_date);
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      return days > 0 ? days : 1;
    }
    return 0;
  },
  
  isStepCompleted(step) {
    switch(step) {
      case 1:
        return this.tripData.title && this.tripData.start_date && this.tripData.end_date;
      case 2:
        return this.tripData.distance || this.tripData.sections?.length > 0;
      case 3:
        return this.tripData.pack_type;
      case 4:
        return this.tripData.calories_per_day || this.tripData.water_per_day;
      case 5:
        return false; // Review is never "completed" until saved
      default:
        return false;
    }
  },
  
  loadSavedData() {
    // Check if we're editing an existing trip
    const urlParams = new URLSearchParams(window.location.search);
    const tripId = urlParams.get('id');
    
    if (tripId) {
      this.loadTrip(tripId);
    } else if (urlParams.get('copy') === 'last') {
      this.copyLastTrip();
    }
  },
  
  loadTrip(id) {
    $.get(`/BTT/api/index.php?route=trips&id=${id}`, (data) => {
      if (data.data) {
        this.tripData = data.data;
        this.renderWizard();
      }
    });
  },
  
  copyLastTrip() {
    $.get('/BTT/api/index.php?route=trips&limit=1&sort=created_at:desc', (data) => {
      if (data.data && data.data.length > 0) {
        const lastTrip = data.data[0];
        
        // Copy but reset dates and name
        this.tripData = {
          ...lastTrip,
          id: null,
          title: lastTrip.title + ' (Copy)',
          start_date: '',
          end_date: '',
          created_at: null,
          updated_at: null
        };
        
        this.renderWizard();
        UXUtils.toast('Copied from your last trip! Update the dates and name.', 'info');
      }
    });
  },
  
  saveTrip() {
    // Validate all required fields
    if (!this.validateStep(1)) {
      this.goToStep(1);
      return;
    }
    
    // Show loading
    UXUtils.showLoader('Creating your trip...');
    
    // Prepare data for API
    const tripData = {
      ...this.tripData,
      status: 'planned'
    };
    
    // Save via API
    $.ajax({
      url: '/BTT/api/index.php?route=trips',
      method: 'POST',
      data: JSON.stringify(tripData),
      contentType: 'application/json',
      success: (response) => {
        UXUtils.hideLoader();
        
        if (response.success) {
          // Clear draft
          localStorage.removeItem('tripWizardDraft');
          
          // Handle pack creation if needed
          if (this.tripData.pack_type === 'quick') {
            this.createQuickPack(response.data.id);
          } else if (this.tripData.pack_type === 'new') {
            window.location.href = `/BTT/public/backpacks.php?action=new&trip_id=${response.data.id}`;
          } else {
            // Show success and redirect
            UXUtils.toast('Trip created successfully!', 'success');
            setTimeout(() => {
              window.location.href = `/BTT/public/trips.php?id=${response.data.id}`;
            }, 1500);
          }
        } else {
          UXUtils.toast('Failed to create trip', 'error');
        }
      },
      error: () => {
        UXUtils.hideLoader();
        UXUtils.toast('Error creating trip. Please try again.', 'error');
      }
    });
  },
  
  createQuickPack(tripId) {
    // Generate quick pack from favorites
    $.get('/BTT/api/index.php?route=gear&favorite=1', (data) => {
      if (data.data && data.data.length > 0) {
        const packData = {
          name: `Pack for ${this.tripData.title}`,
          trip_id: tripId,
          items: data.data.map(item => ({
            gear_id: item.id,
            quantity: 1,
            worn: item.category === 'clothing'
          }))
        };
        
        $.ajax({
          url: '/BTT/api/index.php?route=backpacks',
          method: 'POST',
          data: JSON.stringify(packData),
          contentType: 'application/json',
          success: () => {
            UXUtils.toast('Trip and quick pack created!', 'success');
            setTimeout(() => {
              window.location.href = `/BTT/public/trips.php?id=${tripId}`;
            }, 1500);
          }
        });
      } else {
        // No favorites, just redirect
        UXUtils.toast('Trip created! Add gear to your pack when ready.', 'success');
        setTimeout(() => {
          window.location.href = `/BTT/public/trips.php?id=${tripId}`;
        }, 1500);
      }
    });
  },
  
  saveAsTemplate() {
    const templateName = prompt('Template name:');
    if (templateName) {
      const templates = JSON.parse(localStorage.getItem('tripTemplates') || '[]');
      templates.push({
        name: templateName,
        data: this.tripData,
        created: new Date().toISOString()
      });
      
      localStorage.setItem('tripTemplates', JSON.stringify(templates));
      UXUtils.toast('Template saved!', 'success');
    }
  }
};

// Initialize on document ready
$(document).ready(() => {
  if ($('#trip-wizard-container').length) {
    TripWizard.init();
  }
});
