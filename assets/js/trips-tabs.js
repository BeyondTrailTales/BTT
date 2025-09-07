/**
 * Trip Form Tab Navigation
 * Handles tab switching in the trip editor form
 */

(function() {
  'use strict';
  
  // Wait for DOM ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeTripTabs();
  });
  
  function initializeTripTabs() {
    // Get all tab buttons and panels
    const tabButtons = document.querySelectorAll('[role="tab"]');
    const tabPanels = document.querySelectorAll('[role="tabpanel"]');
    
    if (!tabButtons.length || !tabPanels.length) {
      return; // No tabs on this page
    }
    
    // Add click handlers to all tab buttons
    tabButtons.forEach(button => {
      button.addEventListener('click', handleTabClick);
      
      // Add keyboard navigation
      button.addEventListener('keydown', handleTabKeydown);
    });
    
    // Handle tab click
    function handleTabClick(e) {
      const button = e.currentTarget;
      const targetPanelId = button.getAttribute('aria-controls');
      
      if (!targetPanelId) return;
      
      // Update button states
      tabButtons.forEach(btn => {
        btn.setAttribute('aria-selected', 'false');
        btn.parentElement.classList.remove('active');
      });
      
      button.setAttribute('aria-selected', 'true');
      button.parentElement.classList.add('active');
      
      // Update panel visibility
      tabPanels.forEach(panel => {
        if (panel.id === targetPanelId) {
          panel.removeAttribute('hidden');
          panel.style.display = 'block';
          
          // Set focus on first focusable element in panel
          const firstInput = panel.querySelector('input:not([type="hidden"]), select, textarea');
          if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
          }
        } else {
          panel.setAttribute('hidden', '');
          panel.style.display = 'none';
        }
      });
      
      // Update URL hash without scrolling
      const tabName = targetPanelId.replace('tab-panel-', '');
      if (window.history.replaceState) {
        window.history.replaceState(null, null, '#tab-' + tabName);
      }
      
      // Trigger custom event for other scripts
      document.dispatchEvent(new CustomEvent('tripTabChanged', {
        detail: { tab: tabName, button: button, panel: document.getElementById(targetPanelId) }
      }));
    }
    
    // Handle keyboard navigation
    function handleTabKeydown(e) {
      const currentButton = e.currentTarget;
      const allButtons = Array.from(tabButtons);
      const currentIndex = allButtons.indexOf(currentButton);
      let nextIndex = null;
      
      switch(e.key) {
        case 'ArrowRight':
        case 'ArrowDown':
          e.preventDefault();
          nextIndex = (currentIndex + 1) % allButtons.length;
          break;
          
        case 'ArrowLeft':
        case 'ArrowUp':
          e.preventDefault();
          nextIndex = currentIndex - 1;
          if (nextIndex < 0) nextIndex = allButtons.length - 1;
          break;
          
        case 'Home':
          e.preventDefault();
          nextIndex = 0;
          break;
          
        case 'End':
          e.preventDefault();
          nextIndex = allButtons.length - 1;
          break;
          
        default:
          return; // Let other keys pass through
      }
      
      if (nextIndex !== null) {
        allButtons[nextIndex].focus();
        allButtons[nextIndex].click();
      }
    }
    
    // Check URL hash on load to open specific tab
    const hash = window.location.hash;
    if (hash && hash.startsWith('#tab-')) {
      const tabName = hash.replace('#tab-', '');
      const targetButton = document.getElementById('tab-btn-' + tabName);
      if (targetButton) {
        targetButton.click();
      }
    }
    
    // Make sure the active tab panel is visible
    const activeButton = document.querySelector('[role="tab"][aria-selected="true"]');
    if (activeButton) {
      activeButton.click();
    }
  }
  
  // Expose function globally for trips.js integration
  window.TripTabs = {
    switchToTab: function(tabName) {
      const button = document.getElementById('tab-btn-' + tabName);
      if (button) {
        button.click();
      }
    },
    
    getCurrentTab: function() {
      const activeButton = document.querySelector('[role="tab"][aria-selected="true"]');
      if (activeButton) {
        const panelId = activeButton.getAttribute('aria-controls');
        return panelId ? panelId.replace('tab-panel-', '') : 'basics';
      }
      return 'basics';
    }
  };
})();