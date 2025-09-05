/**
 * Trip Packing List Module
 * Manages packing list interactions for trips
 * 
 * @version 1.0.0
 */

(function() {
  'use strict';
  
  // Module state
  const state = {
    currentTripId: null,
    packingData: null,
    activeCategory: 'all',
    pendingUpdates: new Map(),
    updateTimer: null
  };
  
  // API endpoints
  const API_BASE = 'ajax-handler.php';
  
  // Initialize module
  function init() {
    if (!window.TripPacking) {
      window.TripPacking = {
        loadPackingList,
        refreshPackingList,
        setTripId,
        clearData
      };
    }
    
    bindEvents();
    console.log('Trip Packing module initialized');
  }
  
  // Bind event listeners
  function bindEvents() {
    // Category filters
    document.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', handleFilterClick);
    });
    
    // Bulk actions
    const packAll = document.getElementById('btn-pack-all');
    const unpackAll = document.getElementById('btn-unpack-all');
    const resetPacking = document.getElementById('btn-reset-packing');
    
    if (packAll) packAll.addEventListener('click', () => handleBulkAction('pack'));
    if (unpackAll) unpackAll.addEventListener('click', () => handleBulkAction('unpack'));
    if (resetPacking) resetPacking.addEventListener('click', () => handleBulkAction('reset'));
    
    // Custom item form
    const addCustomBtn = document.getElementById('btn-add-custom-item');
    if (addCustomBtn) {
      addCustomBtn.addEventListener('click', handleAddCustomItem);
    }
    
    // Listen for tab changes
    const packingTab = document.getElementById('tab-btn-packing');
    if (packingTab) {
      packingTab.addEventListener('click', () => {
        if (state.currentTripId) {
          loadPackingList(state.currentTripId);
        }
      });
    }
  }
  
  // Set current trip ID
  function setTripId(tripId) {
    state.currentTripId = tripId;
  }
  
  // Load packing list for a trip
  async function loadPackingList(tripId) {
    if (!tripId) return;
    
    state.currentTripId = tripId;
    const container = document.getElementById('packing-items-container');
    const emptyState = document.getElementById('packing-empty-state');
    
    // Show loading state
    if (container) {
      container.innerHTML = `
        <div class="loading-spinner">
          <div class="spinner"></div>
          <p>Loading packing list...</p>
        </div>
      `;
    }
    
    try {
      const response = await fetch(`${API_BASE}?route=trips&id=${tripId}&action=packing-list`);
      if (!response.ok) throw new Error('Failed to load packing list');
      
      const result = await response.json();
      console.log('Packing list API response:', result);
      state.packingData = result.data || result;
      
      // Check if we have a backpack or any items
      if (!state.packingData.backpack_id && (!state.packingData.items || state.packingData.items.length === 0)) {
        // Show empty state
        if (emptyState) emptyState.hidden = false;
        if (container) container.innerHTML = '';
      } else {
        // Hide empty state and render items
        if (emptyState) emptyState.hidden = true;
        renderPackingList();
      }
      
      updateProgress();
      
    } catch (error) {
      console.error('Error loading packing list:', error);
      if (container) {
        container.innerHTML = `
          <div class="error-message">
            <p>Failed to load packing list. Please try again.</p>
          </div>
        `;
      }
    }
  }
  
  // Refresh packing list
  function refreshPackingList() {
    if (state.currentTripId) {
      loadPackingList(state.currentTripId);
    }
  }
  
  // Render packing list items
  function renderPackingList() {
    const container = document.getElementById('packing-items-container');
    if (!container || !state.packingData) return;
    
    const categories = state.packingData.categories || {};
    const activeCategory = state.activeCategory;
    
    let html = '';
    
    // Filter and render categories
    const categoriesToShow = activeCategory === 'all' 
      ? ['main', 'lid', 'pockets', 'external'] 
      : [activeCategory];
    
    categoriesToShow.forEach(category => {
      const items = categories[category] || [];
      if (items.length === 0) return;
      
      html += `
        <div class="packing-category" data-category="${category}">
          <h5 class="category-title">${getCategoryLabel(category)} (${items.length})</h5>
          <div class="category-items">
      `;
      
      items.forEach(item => {
        const itemId = item.type === 'gear' ? `gear-${item.gear_id}` : `custom-${item.custom_id}`;
        const isChecked = item.is_packed ? 'checked' : '';
        const customBadge = item.is_custom ? '<span class="item-badge">Custom</span>' : '';
        
        html += `
          <div class="packing-item ${item.is_packed ? 'packed' : ''}" data-item-id="${itemId}">
            <div class="item-checkbox">
              <input type="checkbox" 
                     id="pack-${itemId}" 
                     class="pack-checkbox" 
                     ${isChecked}
                     data-type="${item.type}"
                     data-id="${item.gear_id || item.custom_id}"
                     aria-label="Pack ${item.name}">
              <label for="pack-${itemId}" class="item-label">
                <span class="item-name">${escapeHtml(item.name)}</span>
                ${item.quantity > 1 ? `<span class="item-qty">×${item.quantity}</span>` : ''}
                ${customBadge}
              </label>
            </div>
            ${item.notes ? `<div class="item-notes">${escapeHtml(item.notes)}</div>` : ''}
            ${item.is_custom ? `
              <button class="btn-delete-custom" 
                      data-id="${item.custom_id}"
                      aria-label="Delete ${item.name}">
                ×
              </button>
            ` : ''}
            <span class="sr-only" id="status-${itemId}">
              ${item.is_packed ? 'Packed' : 'Not packed'}
            </span>
          </div>
        `;
      });
      
      html += `
          </div>
        </div>
      `;
    });
    
    container.innerHTML = html || '<p class="no-items">No items in this category</p>';
    
    // Bind checkbox events
    container.querySelectorAll('.pack-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', handlePackToggle);
    });
    
    // Bind delete buttons
    container.querySelectorAll('.btn-delete-custom').forEach(btn => {
      btn.addEventListener('click', handleDeleteCustom);
    });
  }
  
  // Handle pack/unpack toggle
  function handlePackToggle(event) {
    const checkbox = event.target;
    const type = checkbox.dataset.type;
    const id = checkbox.dataset.id;
    const isPacked = checkbox.checked;
    
    console.log('🎯 Checkbox toggle:', { type, id, isPacked, tripId: state.currentTripId });
    
    // Update UI immediately (optimistic update)
    const itemDiv = checkbox.closest('.packing-item');
    if (itemDiv) {
      itemDiv.classList.toggle('packed', isPacked);
      const statusSpan = itemDiv.querySelector('.sr-only');
      if (statusSpan) {
        statusSpan.textContent = isPacked ? 'Packed' : 'Not packed';
      }
    }
    
    // Queue update
    queueUpdate(type, id, isPacked);
    
    // Update progress immediately
    updateProgressOptimistic();
  }
  
  // Queue updates for batch processing
  function queueUpdate(type, id, isPacked) {
    const key = `${type}-${id}`;
    state.pendingUpdates.set(key, { type, id, isPacked });
    
    // Clear existing timer
    if (state.updateTimer) {
      clearTimeout(state.updateTimer);
    }
    
    // Set new timer to batch updates
    state.updateTimer = setTimeout(() => {
      sendBatchUpdates();
    }, 500); // Wait 500ms before sending
  }
  
  // Send batch updates to server
  async function sendBatchUpdates() {
    if (state.pendingUpdates.size === 0) return;
    
    const updates = Array.from(state.pendingUpdates.values()).map(item => ({
      type: item.type,
      gear_id: item.type === 'gear' ? item.id : undefined,
      id: item.type === 'custom' ? item.id : undefined,
      is_packed: item.isPacked
    }));
    
    console.log('📦 Sending batch updates:', updates);
    console.log('🌐 API URL:', `${API_BASE}?route=trips&id=${state.currentTripId}&action=packing-list&sub_action=bulk`);
    
    state.pendingUpdates.clear();
    
    try {
      const response = await fetch(`${API_BASE}?route=trips&id=${state.currentTripId}&action=packing-list&sub_action=bulk`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ items: updates })
      });
      
      if (!response.ok) throw new Error('Failed to update packing status');
      
      const result = await response.json();
      console.log('📦 Batch update result:', result);
      
      if (result.success) {
        console.log('✅ Batch update successful');
        showToast('Packing status updated', 'success');
      } else {
        console.log('❌ Batch update failed:', result.message);
        throw new Error(result.message || 'Update failed');
      }
      
      // Refresh data to ensure consistency
      await loadPackingList(state.currentTripId);
      
    } catch (error) {
      console.error('❌ Error updating packing status:', error);
      showToast('Failed to save changes. Please try again.', 'error');
      // Reload to revert optimistic updates
      await loadPackingList(state.currentTripId);
    }
  }
  
  // Handle filter clicks
  function handleFilterClick(event) {
    const chip = event.target;
    const category = chip.dataset.category;
    
    // Update active states
    document.querySelectorAll('.filter-chip').forEach(c => {
      c.classList.remove('active');
      c.setAttribute('aria-pressed', 'false');
    });
    chip.classList.add('active');
    chip.setAttribute('aria-pressed', 'true');
    
    state.activeCategory = category;
    renderPackingList();
  }
  
  // Handle bulk actions
  async function handleBulkAction(action) {
    if (!state.packingData || !state.packingData.items) return;
    
    const items = state.activeCategory === 'all' 
      ? state.packingData.items 
      : state.packingData.items.filter(item => item.category === state.activeCategory);
    
    if (items.length === 0) return;
    
    const updates = items.map(item => ({
      type: item.type,
      gear_id: item.gear_id,
      id: item.custom_id,
      is_packed: action === 'pack'
    }));
    
    try {
      const response = await fetch(`${API_BASE}?route=trips&id=${state.currentTripId}&action=packing-list&sub_action=bulk`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ items: updates })
      });
      
      if (!response.ok) throw new Error('Failed to update items');
      
      await loadPackingList(state.currentTripId);
      showToast(`All items ${action === 'pack' ? 'packed' : 'unpacked'}`, 'success');
      
    } catch (error) {
      console.error('Error with bulk action:', error);
      showToast('Failed to update items. Please try again.', 'error');
    }
  }
  
  // Handle adding custom item
  async function handleAddCustomItem() {
    const nameInput = document.getElementById('custom-item-name');
    const categorySelect = document.getElementById('custom-item-category');
    const quantityInput = document.getElementById('custom-item-quantity');
    const notesInput = document.getElementById('custom-item-notes');
    const errorDiv = document.getElementById('custom-item-error');
    
    // Clear previous errors
    if (errorDiv) {
      errorDiv.hidden = true;
      errorDiv.textContent = '';
    }
    
    // Validate
    if (!nameInput.value.trim()) {
      if (errorDiv) {
        errorDiv.textContent = 'Item name is required';
        errorDiv.hidden = false;
      }
      nameInput.focus();
      return;
    }
    
    try {
      const response = await fetch(`${API_BASE}?route=trips&id=${state.currentTripId}&action=packing-list&sub_action=custom`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          item_name: nameInput.value.trim(),
          category: categorySelect.value,
          quantity: parseInt(quantityInput.value) || 1,
          notes: notesInput.value.trim() || null
        })
      });
      
      if (!response.ok) throw new Error('Failed to add custom item');
      
      // Clear form
      nameInput.value = '';
      notesInput.value = '';
      quantityInput.value = '1';
      
      // Reload list
      await loadPackingList(state.currentTripId);
      showToast('Custom item added', 'success');
      
    } catch (error) {
      console.error('Error adding custom item:', error);
      if (errorDiv) {
        errorDiv.textContent = 'Failed to add item. Please try again.';
        errorDiv.hidden = false;
      }
    }
  }
  
  // Handle deleting custom item
  async function handleDeleteCustom(event) {
    const btn = event.target;
    const itemId = btn.dataset.id;
    
    if (!confirm('Delete this custom item?')) return;
    
    try {
      const response = await fetch(`${API_BASE}?route=trips&id=${state.currentTripId}&action=packing-list&sub_action=custom&item_id=${itemId}`, {
        method: 'DELETE'
      });
      
      if (!response.ok) throw new Error('Failed to delete item');
      
      await loadPackingList(state.currentTripId);
      showToast('Item deleted', 'success');
      
    } catch (error) {
      console.error('Error deleting custom item:', error);
      showToast('Failed to delete item. Please try again.', 'error');
    }
  }
  
  // Update progress display
  function updateProgress() {
    if (!state.packingData) return;
    
    const summary = state.packingData.summary || { total: 0, packed: 0, percent: 0 };
    
    const progressText = document.getElementById('packing-progress-text');
    const progressPercent = document.getElementById('packing-progress-percent');
    const progressBar = document.getElementById('packing-progress-bar');
    const progressContainer = document.querySelector('.progress-bar');
    
    if (progressText) {
      progressText.textContent = `${summary.packed} of ${summary.total} items packed`;
    }
    
    if (progressPercent) {
      progressPercent.textContent = `${summary.percent}%`;
    }
    
    if (progressBar) {
      progressBar.style.width = `${summary.percent}%`;
    }
    
    if (progressContainer) {
      progressContainer.setAttribute('aria-valuenow', summary.percent);
      progressContainer.setAttribute('aria-valuemax', summary.total);
    }
  }
  
  // Update progress optimistically (before server response)
  function updateProgressOptimistic() {
    const checkboxes = document.querySelectorAll('.pack-checkbox');
    const total = checkboxes.length;
    const packed = document.querySelectorAll('.pack-checkbox:checked').length;
    const percent = total > 0 ? Math.floor((packed / total) * 100) : 0;
    
    const progressText = document.getElementById('packing-progress-text');
    const progressPercent = document.getElementById('packing-progress-percent');
    const progressBar = document.getElementById('packing-progress-bar');
    
    if (progressText) {
      progressText.textContent = `${packed} of ${total} items packed`;
    }
    
    if (progressPercent) {
      progressPercent.textContent = `${percent}%`;
    }
    
    if (progressBar) {
      progressBar.style.width = `${percent}%`;
    }
  }
  
  // Clear data when switching trips
  function clearData() {
    state.packingData = null;
    state.pendingUpdates.clear();
    if (state.updateTimer) {
      clearTimeout(state.updateTimer);
    }
  }
  
  // Helper functions
  function getCategoryLabel(category) {
    const labels = {
      main: 'Main Compartment',
      lid: 'Top Lid',
      pockets: 'Side Pockets',
      external: 'External'
    };
    return labels[category] || category;
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  function showToast(message, type = 'info') {
    // Use BTTUtils if available, otherwise console log
    if (window.BTTUtils && window.BTTUtils.showToast) {
      window.BTTUtils.showToast(message, type);
    } else {
      console.log(`[${type}] ${message}`);
    }
  }
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
})();
