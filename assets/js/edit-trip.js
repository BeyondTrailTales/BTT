/**
 * Standalone Edit Trip JavaScript
 * Handles form submission, validation, and UI interactions
 */

window.EditTrip = {
  config: {
    tripId: null,
    apiUrl: null,
    csrfToken: null,
    tripData: null
  },

  elements: {
    form: null,
    saveBtn: null,
    photoInput: null,
    photoDisplay: null,
    tabBtns: null,
    tabPanels: null,
    toggles: null
  },

  currentTab: 'basics',

  init() {
    console.log('Initializing EditTrip...');
    
    // Set config from global
    if (window.TripEditConfig) {
      Object.assign(this.config, window.TripEditConfig);
      console.log('Loaded config:', this.config);
      console.log('Available backpacks:', this.config.backpacks);
    }

    // Get DOM elements
    this.cacheElements();
    
    // Bind events
    this.bindEvents();
    
    // Initialize components
    this.initTabs();
    this.initToggles();
    this.initPhotoUpload();
    this.updateChecklist();
    
    // Initialize backpack state
    this.handleBackpackChange();
    
    console.log('EditTrip initialized successfully');
  },

  cacheElements() {
    this.elements.form = document.getElementById('trip-form');
    this.elements.saveBtn = document.getElementById('btn-save-trip');
    this.elements.photoInput = document.getElementById('photo');
    this.elements.photoDisplay = document.getElementById('adventure-image-display');
    this.elements.tabBtns = document.querySelectorAll('.step-btn');
    this.elements.tabPanels = document.querySelectorAll('.form-section');
    this.elements.toggles = document.querySelectorAll('.toggle-switch input[type="checkbox"]');
    
    console.log('Cached elements:');
    console.log('Form:', !!this.elements.form);
    console.log('Save button:', !!this.elements.saveBtn);
    console.log('Tab buttons found:', this.elements.tabBtns.length);
    console.log('Tab panels found:', this.elements.tabPanels.length);
  },

  bindEvents() {
    // Save button
    if (this.elements.saveBtn) {
      this.elements.saveBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.saveTrip();
      });
    }

    // Form submission
    if (this.elements.form) {
      this.elements.form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveTrip();
      });
    }

    // Form field changes for checklist updates
    if (this.elements.form) {
      this.elements.form.addEventListener('input', () => {
        this.updateChecklist();
      });
      this.elements.form.addEventListener('change', () => {
        this.updateChecklist();
      });
    }

    // Backpack selection changes
    const backpackSelect = document.getElementById('backpack_id');
    if (backpackSelect) {
      backpackSelect.addEventListener('change', () => {
        this.handleBackpackChange();
      });
    }

    // Photo upload
    if (this.elements.photoInput) {
      this.elements.photoInput.addEventListener('change', (e) => {
        this.handlePhotoUpload(e);
      });
    }

    // Photo display click
    if (this.elements.photoDisplay) {
      this.elements.photoDisplay.addEventListener('click', () => {
        this.elements.photoInput?.click();
      });
    }

    // Photo edit button
    const photoEditBtn = document.getElementById('photo-edit-btn');
    if (photoEditBtn) {
      photoEditBtn.addEventListener('click', () => {
        this.elements.photoInput?.click();
      });
    }

    // Remove photo button
    const removePhotoBtn = document.getElementById('btn-remove-photo');
    if (removePhotoBtn) {
      removePhotoBtn.addEventListener('click', () => {
        this.removePhoto();
      });
    }
  },

  initTabs() {
    console.log('Initializing tabs, found buttons:', this.elements.tabBtns.length);
    
    this.elements.tabBtns.forEach((btn, index) => {
      console.log(`Tab button ${index}:`, btn.getAttribute('aria-controls'));
      
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const ariaControls = btn.getAttribute('aria-controls');
        if (!ariaControls) {
          console.error('Tab button missing aria-controls attribute:', btn);
          return;
        }
        
        const tabId = ariaControls.replace('tab-panel-', '');
        console.log('Switching to tab:', tabId);
        this.switchTab(tabId);
      });
    });
    
    // Set initial tab to basics
    this.switchTab('basics');
  },

  initToggles() {
    this.elements.toggles.forEach((toggle) => {
      toggle.addEventListener('change', (e) => {
        const hiddenInput = document.getElementById(toggle.id.replace('-toggle', ''));
        if (hiddenInput) {
          hiddenInput.value = e.target.checked ? '1' : '0';
        }
      });
    });
  },

  initPhotoUpload() {
    // Photo overlay interactions
    const photoOverlay = document.getElementById('photo-edit-overlay');
    const photoContainer = this.elements.photoDisplay;
    
    if (photoContainer && photoOverlay) {
      photoContainer.addEventListener('mouseenter', () => {
        photoOverlay.style.opacity = '1';
      });
      
      photoContainer.addEventListener('mouseleave', () => {
        photoOverlay.style.opacity = '0';
      });
    }
  },

  switchTab(tabId) {
    console.log('Switching to tab:', tabId);
    
    // Update current tab
    this.currentTab = tabId;
    
    // Update tab buttons - remove active class from all steps
    document.querySelectorAll('.progress-step').forEach((step) => {
      step.classList.remove('active');
    });
    
    // Update tab buttons
    this.elements.tabBtns.forEach((btn) => {
      const step = btn.closest('.progress-step');
      const isActive = btn.getAttribute('aria-controls') === `tab-panel-${tabId}`;
      
      btn.setAttribute('aria-selected', isActive);
      if (isActive) {
        step.classList.add('active');
      }
    });
    
    // Update tab panels - hide all first
    document.querySelectorAll('.form-section').forEach((panel) => {
      panel.classList.remove('active');
      panel.hidden = true;
    });
    
    // Show active panel
    const activePanel = document.getElementById(`tab-panel-${tabId}`);
    if (activePanel) {
      activePanel.classList.add('active');
      activePanel.hidden = false;
    }
    
    // Update checklist after tab switch
    this.updateChecklist();
    
    // Load packing list when switching to packing tab
    if (tabId === 'packing') {
      const backpackSelect = document.getElementById('backpack_id');
      if (backpackSelect && backpackSelect.value && window.TripPacking && this.config.tripId) {
        console.log('Loading packing list for tab switch to packing');
        window.TripPacking.loadPackingList(this.config.tripId);
      } else {
        // Show empty state if no backpack selected
        const packingContent = document.getElementById('packing-content');
        const packingEmpty = document.getElementById('packing-empty-state');
        if (packingContent && packingEmpty && !backpackSelect?.value) {
          packingContent.style.display = 'none';
          packingEmpty.hidden = false;
        }
      }
    }
  },

  handlePhotoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file
    if (!file.type.startsWith('image/')) {
      this.showMessage('Please select a valid image file.', 'error');
      return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB limit
      this.showMessage('Image file too large. Please select an image under 10MB.', 'error');
      return;
    }

    // Preview image
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.alt = 'Adventure photo preview';
      img.className = 'adventure-photo';
      
      const container = this.elements.photoDisplay;
      container.innerHTML = '';
      container.appendChild(img);
      
      // Show remove button
      const removeBtn = document.getElementById('btn-remove-photo');
      if (removeBtn) {
        removeBtn.style.display = 'block';
      }
      
      this.updateChecklist();
    };
    
    reader.readAsDataURL(file);
  },

  removePhoto() {
    if (confirm('Are you sure you want to remove this photo?')) {
      // Clear photo input
      if (this.elements.photoInput) {
        this.elements.photoInput.value = '';
      }
      
      // Set remove flag
      const removeFlag = document.getElementById('remove_photo');
      if (removeFlag) {
        removeFlag.value = '1';
      }
      
      // Reset display to placeholder
      const container = this.elements.photoDisplay;
      container.innerHTML = `
        <div class="placeholder-image">
          <div class="placeholder-icon">🏔️</div>
          <p class="placeholder-text">Click to add photo</p>
        </div>
      `;
      
      // Hide remove button
      const removeBtn = document.getElementById('btn-remove-photo');
      if (removeBtn) {
        removeBtn.style.display = 'none';
      }
      
      this.updateChecklist();
      this.showMessage('Photo removed', 'success');
    }
  },

  updateChecklist() {
    const requirements = {
      name: document.getElementById('title')?.value?.trim(),
      dates: document.getElementById('start_date')?.value && document.getElementById('end_date')?.value,
      location: document.getElementById('location')?.value?.trim(),
      photo: this.elements.photoInput?.files?.length > 0 || (this.config.tripData?.photo_path && document.getElementById('remove_photo')?.value !== '1'),
      distance: document.getElementById('distance')?.value && document.getElementById('difficulty')?.value
    };

    Object.keys(requirements).forEach(req => {
      const item = document.querySelector(`[data-requirement="${req}"]`);
      if (item) {
        const icon = item.querySelector('.check-icon');
        const isComplete = requirements[req];
        
        icon.textContent = isComplete ? '✓' : '○';
        icon.style.background = isComplete ? 'rgba(76, 175, 80, 0.2)' : 'rgba(255, 255, 255, 0.1)';
        icon.style.borderColor = isComplete ? '#4CAF50' : 'rgba(255, 255, 255, 0.3)';
        icon.style.color = isComplete ? '#4CAF50' : 'rgba(255, 255, 255, 0.5)';
      }
    });
  },

  handleBackpackChange() {
    const backpackSelect = document.getElementById('backpack_id');
    const selectedBackpack = document.getElementById('selected-backpack');
    
    console.log('handleBackpackChange called');
    console.log('Backpack select element:', backpackSelect);
    console.log('Available options:', backpackSelect?.options?.length);
    
    if (!backpackSelect || !selectedBackpack) {
      console.log('Missing elements - backpackSelect or selectedBackpack');
      return;
    }
    
    const backpackId = backpackSelect.value;
    const backpackName = backpackSelect.options[backpackSelect.selectedIndex]?.text;
    
    console.log('Selected backpack ID:', backpackId);
    console.log('Selected backpack name:', backpackName);
    
    // Update sidebar preview
    if (backpackId && backpackName !== 'No backpack selected') {
      selectedBackpack.innerHTML = `
        <div class="pack-selected">
          <div class="pack-icon">🎒</div>
          <p class="pack-name">${backpackName}</p>
          <p class="pack-hint">Selected backpack</p>
        </div>
      `;
      
      // Show packing content if we're on packing tab
      const packingContent = document.getElementById('packing-content');
      const packingEmpty = document.getElementById('packing-empty-state');
      if (packingContent && packingEmpty) {
        packingContent.style.display = 'block';
        packingEmpty.hidden = true;
      }
      
      // Load packing list if TripPacking is available and we're on packing tab
      if (window.TripPacking && this.currentTab === 'packing' && this.config.tripId) {
        window.TripPacking.loadPackingList(this.config.tripId);
      }
    } else {
      selectedBackpack.innerHTML = `
        <div class="no-pack-selected">
          <div class="pack-icon">🎒</div>
          <p>No pack selected yet</p>
          <p class="pack-hint">Link a backpack to this adventure</p>
        </div>
      `;
      
      // Hide packing content and show empty state
      const packingContent = document.getElementById('packing-content');
      const packingEmpty = document.getElementById('packing-empty-state');
      if (packingContent && packingEmpty) {
        packingContent.style.display = 'none';
        packingEmpty.hidden = false;
      }
    }
  },

  saveTrip() {
    console.log('Saving trip...');
    
    if (!this.validateForm()) {
      return;
    }

    // Show saving state
    this.elements.saveBtn.disabled = true;
    this.elements.saveBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Saving...</span>';

    // Prepare form data
    const formData = new FormData(this.elements.form);
    
    // Use different actions for create vs edit
    if (this.config.mode === 'create' && !this.config.tripId) {
      formData.append('action', 'create_trip');
    } else {
      formData.append('action', 'update_trip');
    }
    
    formData.append('csrf_token', this.config.csrfToken);

    // Add photo if selected
    if (this.elements.photoInput?.files?.length > 0) {
      formData.append('photo', this.elements.photoInput.files[0]);
    }

    // Submit via AJAX
    fetch(this.config.apiUrl, {
      method: 'POST',
      body: formData
    })
    .then(response => {
      // Check if response is ok
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      // Get the response text first to debug
      return response.text();
    })
    .then(text => {
      console.log('Raw response:', text);
      
      try {
        const data = JSON.parse(text);
        
        if (data.success) {
          const isCreate = this.config.mode === 'create' && !this.config.tripId;
          const message = isCreate ? 'Adventure created successfully! ✅' : 'Adventure updated successfully! ✅';
          this.showMessage(message, 'success');
          
          // For new trips, update the config and page to switch to edit mode
          if (isCreate && data.trip && data.trip.id) {
            this.config.tripId = data.trip.id;
            this.config.mode = 'edit';
            
            // Update the URL to reflect the new trip ID
            const newUrl = `edit-trip.php?id=${data.trip.id}`;
            window.history.replaceState({}, '', newUrl);
            
            // Update page title
            document.title = document.title.replace('New Adventure', 'Edit Adventure');
          }
          
          // Update trip data in config for next save
          if (data.trip) {
            this.config.tripData = data.trip;
          }
          
          // Update page title overlay if trip name changed
          const titleDisplay = document.querySelector('.adventure-name-display');
          if (titleDisplay && document.getElementById('title')?.value) {
            titleDisplay.textContent = document.getElementById('title').value;
          }
          
          // Update location display if changed
          const locationDisplay = document.querySelector('.adventure-location-display');
          const locationInput = document.getElementById('location');
          if (locationDisplay && locationInput?.value) {
            locationDisplay.textContent = locationInput.value;
            locationDisplay.style.display = 'block';
          } else if (locationDisplay && !locationInput?.value) {
            locationDisplay.style.display = 'none';
          }
          
          // Update stats chips
          this.updateStatsChips();
          
          // Stay on the current page - no redirect
          // User can continue editing or use the back button if needed
        } else {
          throw new Error(data.error || 'Failed to save trip');
        }
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Response text:', text);
        throw new Error('Server returned invalid response. Check console for details.');
      }
    })
    .catch(error => {
      console.error('Save error:', error);
      this.showMessage(error.message || 'Failed to save adventure. Please try again.', 'error');
    })
    .finally(() => {
      // Reset save button
      this.elements.saveBtn.disabled = false;
      this.elements.saveBtn.innerHTML = '<span class="btn-icon">💾</span><span class="btn-text">Save Changes</span>';
    });
  },

  validateForm() {
    let isValid = true;
    const errors = [];

    // Validate required fields
    const title = document.getElementById('title')?.value?.trim();
    if (!title || title.length < 3) {
      errors.push('Adventure name must be at least 3 characters long');
      isValid = false;
    }

    if (errors.length > 0) {
      this.showMessage(errors.join('<br>'), 'error');
    }

    return isValid;
  },

  updateStatsChips() {
    // Update duration chip
    const durationChip = document.getElementById('duration-chip');
    const startDate = document.getElementById('start_date')?.value;
    const endDate = document.getElementById('end_date')?.value;
    
    if (durationChip && startDate && endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      durationChip.textContent = `📅 ${days} day${days > 1 ? 's' : ''}`;
    }
    
    // Update distance chip
    const distanceChip = document.getElementById('distance-chip');
    const distance = document.getElementById('distance')?.value;
    const distanceUnit = document.getElementById('distance_unit')?.value || 'miles';
    
    if (distanceChip && distance) {
      distanceChip.textContent = `🥾 ${distance} ${distanceUnit}`;
    }
    
    // Update difficulty chip
    const difficultyChip = document.getElementById('difficulty-chip');
    const difficulty = document.getElementById('difficulty')?.value;
    
    if (difficultyChip && difficulty) {
      const difficultyText = difficulty.charAt(0).toUpperCase() + difficulty.slice(1);
      difficultyChip.textContent = `💪 ${difficultyText}`;
    }
  },

  showMessage(message, type = 'info') {
    const container = document.getElementById('form-messages');
    if (!container) return;

    const messageEl = document.createElement('div');
    messageEl.className = `form-message ${type}`;
    messageEl.innerHTML = `
      <span class="message-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
      <span class="message-text">${message}</span>
    `;

    container.appendChild(messageEl);

    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (messageEl.parentNode) {
        messageEl.parentNode.removeChild(messageEl);
      }
    }, 5000);

    // Manual close on click
    messageEl.addEventListener('click', () => {
      if (messageEl.parentNode) {
        messageEl.parentNode.removeChild(messageEl);
      }
    });
  }
};