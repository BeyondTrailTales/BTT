// Save Animation Module
(function() {
  'use strict';
  
  // Create save animation elements
  function createSaveOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'save-status-overlay';
    overlay.innerHTML = `
      <div class="save-animation">
        <div class="save-icon">
          <div class="save-icon-circle"></div>
          <svg class="save-progress" viewBox="0 0 80 80">
            <circle class="save-progress-circle" cx="40" cy="40" r="38"></circle>
          </svg>
          <div class="save-icon-center">💾</div>
        </div>
        <div class="save-status-text">Saving...</div>
        <div class="save-status-details"></div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }
  
  // Create small save indicator
  function createSaveIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'save-indicator';
    indicator.innerHTML = `
      <div class="save-indicator-spinner"></div>
      <span>Saving...</span>
    `;
    document.body.appendChild(indicator);
    return indicator;
  }
  
  // Create auto-save status
  function createAutoSaveStatus() {
    const status = document.createElement('div');
    status.className = 'auto-save-status';
    status.innerHTML = `
      <span>✓</span>
      <span>All changes saved</span>
    `;
    document.body.appendChild(status);
    return status;
  }
  
  // Initialize elements
  let saveOverlay = null;
  let saveIndicator = null;
  let autoSaveStatus = null;
  
  // Save Animation API
  window.SaveAnimation = {
    // Show full-screen save animation
    showSaving: function(message = 'Saving your trip...') {
      if (!saveOverlay) saveOverlay = createSaveOverlay();
      
      const overlay = saveOverlay;
      const textEl = overlay.querySelector('.save-status-text');
      const detailsEl = overlay.querySelector('.save-status-details');
      const iconEl = overlay.querySelector('.save-icon-center');
      
      // Reset classes
      overlay.className = 'save-status-overlay saving';
      
      // Update content
      textEl.textContent = message;
      detailsEl.textContent = '';
      iconEl.textContent = '💾';
      
      // Show with animation
      requestAnimationFrame(() => {
        overlay.classList.add('show');
      });
    },
    
    // Show success state
    showSuccess: function(message = 'Saved successfully!', details = '') {
      if (!saveOverlay) saveOverlay = createSaveOverlay();
      
      const overlay = saveOverlay;
      const textEl = overlay.querySelector('.save-status-text');
      const detailsEl = overlay.querySelector('.save-status-details');
      const iconEl = overlay.querySelector('.save-icon-center');
      
      // Update to success state
      overlay.className = 'save-status-overlay success show';
      textEl.textContent = message;
      detailsEl.textContent = details;
      iconEl.textContent = '✅';
      
      // Auto-hide after delay
      setTimeout(() => {
        overlay.classList.remove('show');
      }, 2000);
    },
    
    // Show error state
    showError: function(message = 'Save failed', details = '') {
      if (!saveOverlay) saveOverlay = createSaveOverlay();
      
      const overlay = saveOverlay;
      const textEl = overlay.querySelector('.save-status-text');
      const detailsEl = overlay.querySelector('.save-status-details');
      const iconEl = overlay.querySelector('.save-icon-center');
      
      // Update to error state
      overlay.className = 'save-status-overlay error show';
      textEl.textContent = message;
      detailsEl.textContent = details;
      iconEl.textContent = '❌';
      
      // Auto-hide after longer delay
      setTimeout(() => {
        overlay.classList.remove('show');
      }, 4000);
    },
    
    // Hide overlay immediately
    hide: function() {
      if (saveOverlay) {
        saveOverlay.classList.remove('show');
      }
    },
    
    // Show small save indicator
    showIndicator: function(message = 'Saving...') {
      if (!saveIndicator) saveIndicator = createSaveIndicator();
      
      const textEl = saveIndicator.querySelector('span');
      textEl.textContent = message;
      
      saveIndicator.className = 'save-indicator';
      requestAnimationFrame(() => {
        saveIndicator.classList.add('show');
      });
    },
    
    // Update indicator to success
    indicatorSuccess: function(message = 'Saved') {
      if (!saveIndicator) return;
      
      saveIndicator.innerHTML = `
        <span>✅</span>
        <span>${message}</span>
      `;
      
      setTimeout(() => {
        saveIndicator.classList.remove('show');
      }, 2000);
    },
    
    // Update indicator to error
    indicatorError: function(message = 'Save failed') {
      if (!saveIndicator) return;
      
      saveIndicator.className = 'save-indicator error show';
      saveIndicator.innerHTML = `
        <span>❌</span>
        <span>${message}</span>
      `;
      
      setTimeout(() => {
        saveIndicator.classList.remove('show');
      }, 3000);
    },
    
    // Hide indicator
    hideIndicator: function() {
      if (saveIndicator) {
        saveIndicator.classList.remove('show');
      }
    },
    
    // Show auto-save status
    showAutoSave: function() {
      if (!autoSaveStatus) autoSaveStatus = createAutoSaveStatus();
      
      autoSaveStatus.className = 'auto-save-status saved show';
      
      setTimeout(() => {
        autoSaveStatus.classList.remove('show');
      }, 3000);
    },
    
    // Add loading state to button
    setButtonLoading: function(button, isLoading = true) {
      if (!button) return;
      
      if (isLoading) {
        button.classList.add('saving');
        button.disabled = true;
        const originalText = button.textContent;
        button.setAttribute('data-original-text', originalText);
        button.innerHTML = '<span>💾 Saving...</span>';
      } else {
        button.classList.remove('saving');
        button.disabled = false;
        const originalText = button.getAttribute('data-original-text');
        if (originalText) {
          button.textContent = originalText;
        }
      }
    },
    
    // Helper to wrap save operations with animation
    wrapSave: async function(saveFunction, options = {}) {
      const {
        button = null,
        successMessage = 'Saved successfully!',
        errorMessage = 'Save failed',
        showFullScreen = true,
        showIndicator = !showFullScreen
      } = options;
      
      try {
        // Start animations
        if (button) this.setButtonLoading(button, true);
        if (showFullScreen) {
          this.showSaving();
        } else if (showIndicator) {
          this.showIndicator();
        }
        
        // Execute save
        const result = await saveFunction();
        
        // Show success
        if (showFullScreen) {
          const details = result?.title ? `"${result.title}" has been saved` : '';
          this.showSuccess(successMessage, details);
        } else if (showIndicator) {
          this.indicatorSuccess();
        }
        
        return result;
        
      } catch (error) {
        // Show error
        const errorDetails = error.message || 'Please try again';
        if (showFullScreen) {
          this.showError(errorMessage, errorDetails);
        } else if (showIndicator) {
          this.indicatorError();
        }
        
        throw error;
        
      } finally {
        // Cleanup
        if (button) {
          setTimeout(() => {
            this.setButtonLoading(button, false);
          }, 500);
        }
      }
    }
  };
  
  // Expose to global scope
  window.SaveAnimation = window.SaveAnimation || {};
  
})();