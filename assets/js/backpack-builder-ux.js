/**
 * Backpack Builder UX Enhancement
 * Makes the backpack builder easier to use with better organization
 */

const BackpackBuilderUX = {
  currentPack: null,
  gearItems: [],
  packItems: [],
  categories: [
    { id: 'shelter', name: 'Shelter', icon: '⛺', color: '#4ade80' },
    { id: 'sleep', name: 'Sleep System', icon: '🛏️', color: '#60a5fa' },
    { id: 'clothing', name: 'Clothing', icon: '👕', color: '#f472b6' },
    { id: 'cooking', name: 'Cook System', icon: '🍳', color: '#fb923c' },
    { id: 'food', name: 'Food & Water', icon: '💧', color: '#06b6d4' },
    { id: 'navigation', name: 'Navigation', icon: '🧭', color: '#a855f7' },
    { id: 'safety', name: 'Health & Safety', icon: '🏥', color: '#ef4444' },
    { id: 'tools', name: 'Tools & Repair', icon: '🔧', color: '#84cc16' },
    { id: 'electronics', name: 'Electronics', icon: '📱', color: '#6366f1' },
    { id: 'personal', name: 'Personal Care', icon: '🧴', color: '#ec4899' },
    { id: 'misc', name: 'Miscellaneous', icon: '📦', color: '#94a3b8' }
  ],
  filters: {
    search: '',
    category: 'all',
    onlyFavorites: false,
    weightRange: { min: 0, max: 5000 },
    hideAdded: false
  },
  viewMode: 'grid', // 'grid' or 'list'
  sortBy: 'category', // 'name', 'weight', 'category'
  
  init() {
    this.loadPackData();
    this.loadGearLibrary();
    this.renderBuilder();
    this.bindEvents();
    this.initDragDrop();
    this.loadUserPreferences();
  },
  
  renderBuilder() {
    const container = $('#backpack-builder-container');
    if (!container.length) return;
    
    container.html(`
      <div class="builder-ux">
        <!-- Header with Pack Info -->
        <div class="builder-header">
          <div class="pack-title-section">
            <input type="text" id="pack-name" class="pack-name-input" 
                   placeholder="Name your pack..." 
                   value="${this.currentPack?.name || 'New Pack'}">
            <button class="btn-icon" onclick="BackpackBuilderUX.savePack()" title="Save Pack">
              💾
            </button>
          </div>
          
          <div class="pack-quick-stats">
            <div class="stat-badge base-weight">
              <span class="stat-icon">⚖️</span>
              <span class="stat-value" id="base-weight">0g</span>
              <span class="stat-label">Base</span>
            </div>
            <div class="stat-badge worn-weight">
              <span class="stat-icon">👕</span>
              <span class="stat-value" id="worn-weight">0g</span>
              <span class="stat-label">Worn</span>
            </div>
            <div class="stat-badge consumable-weight">
              <span class="stat-icon">🍎</span>
              <span class="stat-value" id="consumable-weight">0g</span>
              <span class="stat-label">Consumable</span>
            </div>
            <div class="stat-badge total-weight">
              <span class="stat-icon">📊</span>
              <span class="stat-value" id="total-weight">0g</span>
              <span class="stat-label">Total</span>
            </div>
            <div class="stat-badge item-count">
              <span class="stat-icon">📦</span>
              <span class="stat-value" id="item-count">0</span>
              <span class="stat-label">Items</span>
            </div>
          </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="builder-content">
          <!-- Left Panel: Gear Library -->
          <div class="gear-library-panel">
            <div class="panel-header">
              <h3>Gear Library</h3>
              <div class="view-toggles">
                <button class="view-btn ${this.viewMode === 'grid' ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.setViewMode('grid')" title="Grid View">
                  ⊞
                </button>
                <button class="view-btn ${this.viewMode === 'list' ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.setViewMode('list')" title="List View">
                  ☰
                </button>
              </div>
            </div>
            
            <!-- Search and Filters -->
            <div class="library-controls">
              <div class="search-box">
                <input type="text" id="gear-search" placeholder="Search gear..." 
                       value="${this.filters.search}">
                <span class="search-icon">🔍</span>
              </div>
              
              <div class="filter-row">
                <select id="category-filter" class="filter-select">
                  <option value="all">All Categories</option>
                  ${this.categories.map(cat => 
                    `<option value="${cat.id}">${cat.icon} ${cat.name}</option>`
                  ).join('')}
                </select>
                
                <button class="filter-btn ${this.filters.onlyFavorites ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.toggleFavorites()">
                  ⭐ Favorites
                </button>
                
                <button class="filter-btn ${this.filters.hideAdded ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.toggleHideAdded()">
                  👁️ Hide Added
                </button>
              </div>
              
              <div class="sort-row">
                <label>Sort:</label>
                <button class="sort-btn ${this.sortBy === 'name' ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.setSortBy('name')">Name</button>
                <button class="sort-btn ${this.sortBy === 'weight' ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.setSortBy('weight')">Weight</button>
                <button class="sort-btn ${this.sortBy === 'category' ? 'active' : ''}" 
                        onclick="BackpackBuilderUX.setSortBy('category')">Category</button>
              </div>
            </div>
            
            <!-- Gear Items -->
            <div class="gear-items-container" id="gear-items">
              ${this.renderGearItems()}
            </div>
            
            <!-- Quick Actions -->
            <div class="library-actions">
              <button class="btn btn-secondary" onclick="BackpackBuilderUX.addNewGear()">
                + Add New Gear
              </button>
              <button class="btn btn-secondary" onclick="BackpackBuilderUX.importGear()">
                📥 Import
              </button>
            </div>
          </div>
          
          <!-- Right Panel: Current Pack -->
          <div class="current-pack-panel">
            <div class="panel-header">
              <h3>Current Pack</h3>
              <div class="pack-actions">
                <button class="action-btn" onclick="BackpackBuilderUX.clearPack()" 
                        title="Clear Pack">🗑️</button>
                <button class="action-btn" onclick="BackpackBuilderUX.duplicatePack()" 
                        title="Duplicate">📋</button>
                <button class="action-btn" onclick="BackpackBuilderUX.exportPack()" 
                        title="Export">📤</button>
                <button class="action-btn" onclick="BackpackBuilderUX.printPack()" 
                        title="Print">🖨️</button>
              </div>
            </div>
            
            <!-- Category Tabs -->
            <div class="category-tabs">
              <button class="cat-tab active" data-category="all" 
                      onclick="BackpackBuilderUX.filterPackByCategory('all')">
                All Items
              </button>
              ${this.categories.map(cat => 
                `<button class="cat-tab" data-category="${cat.id}" 
                         onclick="BackpackBuilderUX.filterPackByCategory('${cat.id}')"
                         style="--cat-color: ${cat.color}">
                  ${cat.icon} <span class="tab-count" id="count-${cat.id}">0</span>
                </button>`
              ).join('')}
            </div>
            
            <!-- Pack Items -->
            <div class="pack-items-container" id="pack-items">
              ${this.renderPackItems()}
            </div>
            
            <!-- Pack Notes -->
            <div class="pack-notes-section">
              <label for="pack-notes">Pack Notes:</label>
              <textarea id="pack-notes" placeholder="Add notes about this pack..."
                        rows="3">${this.currentPack?.notes || ''}</textarea>
            </div>
            
            <!-- Weight Distribution Chart -->
            <div class="weight-chart-section">
              <h4>Weight Distribution</h4>
              <div id="weight-chart" class="weight-chart">
                ${this.renderWeightChart()}
              </div>
            </div>
          </div>
        </div>
        
        <!-- Floating Action Button -->
        <div class="fab-container">
          <button class="fab main-fab" onclick="BackpackBuilderUX.toggleQuickAdd()">
            +
          </button>
          <div class="fab-menu" id="fab-menu" style="display: none;">
            <button class="fab-option" onclick="BackpackBuilderUX.quickAddEssentials()">
              ⚡ Add Essentials
            </button>
            <button class="fab-option" onclick="BackpackBuilderUX.addFromTemplate()">
              📋 From Template
            </button>
            <button class="fab-option" onclick="BackpackBuilderUX.suggestItems()">
              💡 Suggestions
            </button>
          </div>
        </div>
      </div>
    `);
    
    this.updateWeightDisplay();
    this.updateCategoryCounts();
  },
  
  renderGearItems() {
    const filteredItems = this.getFilteredGearItems();
    
    if (filteredItems.length === 0) {
      return '<div class="empty-message">No gear items found. Try adjusting filters or add new gear.</div>';
    }
    
    if (this.viewMode === 'grid') {
      return `
        <div class="gear-grid">
          ${filteredItems.map(item => this.renderGearCard(item)).join('')}
        </div>
      `;
    } else {
      return `
        <div class="gear-list">
          ${filteredItems.map(item => this.renderGearRow(item)).join('')}
        </div>
      `;
    }
  },
  
  renderGearCard(item) {
    const isInPack = this.isItemInPack(item.id);
    const category = this.categories.find(c => c.id === item.category) || this.categories[10];
    
    return `
      <div class="gear-card ${isInPack ? 'in-pack' : ''}" 
           data-gear-id="${item.id}"
           draggable="true"
           ondragstart="BackpackBuilderUX.handleDragStart(event, ${item.id})">
        <div class="card-header" style="background: linear-gradient(135deg, ${category.color}22, ${category.color}11)">
          <span class="category-icon">${category.icon}</span>
          ${item.favorite ? '<span class="favorite-badge">⭐</span>' : ''}
          ${isInPack ? '<span class="added-badge">✓</span>' : ''}
        </div>
        <div class="card-body">
          <h4 class="gear-name">${item.name}</h4>
          <div class="gear-weight">${this.formatWeight(item.weight_grams)}</div>
          ${item.brand ? `<div class="gear-brand">${item.brand}</div>` : ''}
        </div>
        <div class="card-actions">
          ${isInPack ? 
            `<button class="btn-small btn-danger" onclick="BackpackBuilderUX.removeFromPack(${item.id})">Remove</button>` :
            `<button class="btn-small btn-primary" onclick="BackpackBuilderUX.addToPack(${item.id})">+ Add</button>`
          }
          <button class="btn-icon" onclick="BackpackBuilderUX.editGear(${item.id})" title="Edit">✏️</button>
          <button class="btn-icon" onclick="BackpackBuilderUX.toggleFavorite(${item.id})" title="Favorite">
            ${item.favorite ? '💛' : '🤍'}
          </button>
        </div>
      </div>
    `;
  },
  
  renderGearRow(item) {
    const isInPack = this.isItemInPack(item.id);
    const category = this.categories.find(c => c.id === item.category) || this.categories[10];
    
    return `
      <div class="gear-row ${isInPack ? 'in-pack' : ''}" 
           data-gear-id="${item.id}"
           draggable="true"
           ondragstart="BackpackBuilderUX.handleDragStart(event, ${item.id})">
        <span class="row-category" style="color: ${category.color}">${category.icon}</span>
        <span class="row-name">${item.name}</span>
        <span class="row-brand">${item.brand || '-'}</span>
        <span class="row-weight">${this.formatWeight(item.weight_grams)}</span>
        <span class="row-actions">
          ${isInPack ? 
            `<button class="btn-small btn-danger" onclick="BackpackBuilderUX.removeFromPack(${item.id})">-</button>` :
            `<button class="btn-small btn-primary" onclick="BackpackBuilderUX.addToPack(${item.id})">+</button>`
          }
          ${item.favorite ? '⭐' : ''}
        </span>
      </div>
    `;
  },
  
  renderPackItems() {
    if (this.packItems.length === 0) {
      return `
        <div class="empty-pack-message">
          <div class="empty-icon">🎒</div>
          <p>Your pack is empty</p>
          <p class="hint">Drag items here or click "Add" to start packing</p>
        </div>
      `;
    }
    
    // Group items by category
    const groupedItems = this.groupPackItemsByCategory();
    
    return Object.entries(groupedItems).map(([categoryId, items]) => {
      const category = this.categories.find(c => c.id === categoryId) || this.categories[10];
      const categoryWeight = items.reduce((sum, item) => sum + (item.weight_grams * item.quantity), 0);
      
      return `
        <div class="pack-category-group" data-category="${categoryId}">
          <div class="category-header" style="border-left: 3px solid ${category.color}">
            <span class="category-title">
              ${category.icon} ${category.name}
            </span>
            <span class="category-weight">${this.formatWeight(categoryWeight)}</span>
          </div>
          <div class="category-items">
            ${items.map(item => this.renderPackItem(item)).join('')}
          </div>
        </div>
      `;
    }).join('');
  },
  
  renderPackItem(item) {
    return `
      <div class="pack-item" data-pack-item-id="${item.id}">
        <div class="item-grip" title="Drag to reorder">⋮⋮</div>
        <div class="item-info">
          <span class="item-name">${item.name}</span>
          ${item.brand ? `<span class="item-brand">${item.brand}</span>` : ''}
        </div>
        <div class="item-controls">
          <div class="quantity-control">
            <button class="qty-btn" onclick="BackpackBuilderUX.updateQuantity(${item.id}, -1)">-</button>
            <input type="number" class="qty-input" value="${item.quantity}" min="1" max="99"
                   onchange="BackpackBuilderUX.setQuantity(${item.id}, this.value)">
            <button class="qty-btn" onclick="BackpackBuilderUX.updateQuantity(${item.id}, 1)">+</button>
          </div>
          <div class="weight-display">
            ${this.formatWeight(item.weight_grams * item.quantity)}
          </div>
          <div class="item-options">
            <label class="worn-toggle" title="Worn weight doesn't count toward base weight">
              <input type="checkbox" ${item.worn ? 'checked' : ''} 
                     onchange="BackpackBuilderUX.toggleWorn(${item.id})">
              <span>Worn</span>
            </label>
            <label class="consumable-toggle" title="Consumable weight varies during trip">
              <input type="checkbox" ${item.consumable ? 'checked' : ''} 
                     onchange="BackpackBuilderUX.toggleConsumable(${item.id})">
              <span>Consumable</span>
            </label>
          </div>
          <button class="btn-remove" onclick="BackpackBuilderUX.removeFromPack(${item.id})" title="Remove">
            ×
          </button>
        </div>
      </div>
    `;
  },
  
  renderWeightChart() {
    const categories = this.getCategoryWeights();
    const total = categories.reduce((sum, cat) => sum + cat.weight, 0);
    
    if (total === 0) {
      return '<div class="no-data">Add items to see weight distribution</div>';
    }
    
    return `
      <div class="weight-bars">
        ${categories.filter(cat => cat.weight > 0).map(cat => {
          const percentage = (cat.weight / total * 100).toFixed(1);
          return `
            <div class="weight-bar-row">
              <div class="bar-label">
                ${cat.icon} ${cat.name}
              </div>
              <div class="bar-container">
                <div class="bar-fill" style="width: ${percentage}%; background: ${cat.color}">
                  <span class="bar-value">${this.formatWeight(cat.weight)} (${percentage}%)</span>
                </div>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  },
  
  bindEvents() {
    // Search
    $(document).on('input', '#gear-search', (e) => {
      this.filters.search = e.target.value;
      this.renderGearItems();
    });
    
    // Category filter
    $(document).on('change', '#category-filter', (e) => {
      this.filters.category = e.target.value;
      this.renderGearItems();
    });
    
    // Pack name change
    $(document).on('change', '#pack-name', (e) => {
      if (this.currentPack) {
        this.currentPack.name = e.target.value;
        this.autoSave();
      }
    });
    
    // Pack notes change
    $(document).on('change', '#pack-notes', (e) => {
      if (this.currentPack) {
        this.currentPack.notes = e.target.value;
        this.autoSave();
      }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
          case 's':
            e.preventDefault();
            this.savePack();
            break;
          case 'f':
            e.preventDefault();
            $('#gear-search').focus();
            break;
          case 'n':
            e.preventDefault();
            this.addNewGear();
            break;
        }
      }
    });
    
    // Window resize
    $(window).on('resize', () => {
      this.adjustLayout();
    });
  },
  
  initDragDrop() {
    // Pack items container as drop zone
    $(document).on('dragover', '.pack-items-container', (e) => {
      e.preventDefault();
      $('.pack-items-container').addClass('drag-over');
    });
    
    $(document).on('dragleave', '.pack-items-container', (e) => {
      $('.pack-items-container').removeClass('drag-over');
    });
    
    $(document).on('drop', '.pack-items-container', (e) => {
      e.preventDefault();
      $('.pack-items-container').removeClass('drag-over');
      
      const gearId = e.originalEvent.dataTransfer.getData('gearId');
      if (gearId) {
        this.addToPack(parseInt(gearId));
      }
    });
  },
  
  handleDragStart(event, gearId) {
    event.dataTransfer.setData('gearId', gearId);
    event.dataTransfer.effectAllowed = 'copy';
  },
  
  getFilteredGearItems() {
    let items = [...this.gearItems];
    
    // Search filter
    if (this.filters.search) {
      const search = this.filters.search.toLowerCase();
      items = items.filter(item => 
        item.name.toLowerCase().includes(search) ||
        (item.brand && item.brand.toLowerCase().includes(search)) ||
        (item.description && item.description.toLowerCase().includes(search))
      );
    }
    
    // Category filter
    if (this.filters.category !== 'all') {
      items = items.filter(item => item.category === this.filters.category);
    }
    
    // Favorites filter
    if (this.filters.onlyFavorites) {
      items = items.filter(item => item.favorite);
    }
    
    // Hide added filter
    if (this.filters.hideAdded) {
      items = items.filter(item => !this.isItemInPack(item.id));
    }
    
    // Sort
    items.sort((a, b) => {
      switch(this.sortBy) {
        case 'name':
          return a.name.localeCompare(b.name);
        case 'weight':
          return a.weight_grams - b.weight_grams;
        case 'category':
          return (a.category || '').localeCompare(b.category || '');
        default:
          return 0;
      }
    });
    
    return items;
  },
  
  groupPackItemsByCategory() {
    const grouped = {};
    
    this.packItems.forEach(item => {
      const category = item.category || 'misc';
      if (!grouped[category]) {
        grouped[category] = [];
      }
      grouped[category].push(item);
    });
    
    // Sort categories by predefined order
    const sortedGrouped = {};
    this.categories.forEach(cat => {
      if (grouped[cat.id]) {
        sortedGrouped[cat.id] = grouped[cat.id];
      }
    });
    
    return sortedGrouped;
  },
  
  getCategoryWeights() {
    const weights = {};
    
    this.packItems.forEach(item => {
      if (!item.worn) { // Only count non-worn items
        const category = item.category || 'misc';
        if (!weights[category]) {
          weights[category] = 0;
        }
        weights[category] += item.weight_grams * item.quantity;
      }
    });
    
    return this.categories.map(cat => ({
      ...cat,
      weight: weights[cat.id] || 0
    }));
  },
  
  addToPack(gearId) {
    const gear = this.gearItems.find(g => g.id === gearId);
    if (!gear) return;
    
    // Check if already in pack
    const existingItem = this.packItems.find(p => p.id === gearId);
    if (existingItem) {
      existingItem.quantity++;
      UXUtils.toast(`Increased ${gear.name} quantity`, 'success');
    } else {
      this.packItems.push({
        ...gear,
        quantity: 1,
        worn: gear.category === 'clothing',
        consumable: gear.category === 'food'
      });
      UXUtils.toast(`Added ${gear.name} to pack`, 'success');
    }
    
    this.renderBuilder();
    this.autoSave();
  },
  
  removeFromPack(gearId) {
    const index = this.packItems.findIndex(p => p.id === gearId);
    if (index > -1) {
      const item = this.packItems[index];
      this.packItems.splice(index, 1);
      UXUtils.toast(`Removed ${item.name} from pack`, 'info');
      this.renderBuilder();
      this.autoSave();
    }
  },
  
  updateQuantity(gearId, delta) {
    const item = this.packItems.find(p => p.id === gearId);
    if (item) {
      item.quantity = Math.max(1, Math.min(99, item.quantity + delta));
      this.updateWeightDisplay();
      this.autoSave();
    }
  },
  
  setQuantity(gearId, value) {
    const item = this.packItems.find(p => p.id === gearId);
    if (item) {
      item.quantity = Math.max(1, Math.min(99, parseInt(value) || 1));
      this.updateWeightDisplay();
      this.autoSave();
    }
  },
  
  toggleWorn(gearId) {
    const item = this.packItems.find(p => p.id === gearId);
    if (item) {
      item.worn = !item.worn;
      this.updateWeightDisplay();
      this.autoSave();
    }
  },
  
  toggleConsumable(gearId) {
    const item = this.packItems.find(p => p.id === gearId);
    if (item) {
      item.consumable = !item.consumable;
      this.updateWeightDisplay();
      this.autoSave();
    }
  },
  
  updateWeightDisplay() {
    let baseWeight = 0;
    let wornWeight = 0;
    let consumableWeight = 0;
    let totalItems = 0;
    
    this.packItems.forEach(item => {
      const itemWeight = item.weight_grams * item.quantity;
      totalItems += item.quantity;
      
      if (item.worn) {
        wornWeight += itemWeight;
      } else if (item.consumable) {
        consumableWeight += itemWeight;
      } else {
        baseWeight += itemWeight;
      }
    });
    
    const totalWeight = baseWeight + wornWeight + consumableWeight;
    
    $('#base-weight').text(this.formatWeight(baseWeight));
    $('#worn-weight').text(this.formatWeight(wornWeight));
    $('#consumable-weight').text(this.formatWeight(consumableWeight));
    $('#total-weight').text(this.formatWeight(totalWeight));
    $('#item-count').text(totalItems);
    
    // Update weight chart
    $('#weight-chart').html(this.renderWeightChart());
  },
  
  updateCategoryCounts() {
    const counts = {};
    
    this.packItems.forEach(item => {
      const category = item.category || 'misc';
      counts[category] = (counts[category] || 0) + item.quantity;
    });
    
    this.categories.forEach(cat => {
      $(`#count-${cat.id}`).text(counts[cat.id] || 0);
    });
  },
  
  formatWeight(grams) {
    if (grams < 1000) {
      return `${grams}g`;
    } else {
      return `${(grams / 1000).toFixed(2)}kg`;
    }
  },
  
  isItemInPack(gearId) {
    return this.packItems.some(p => p.id === gearId);
  },
  
  setViewMode(mode) {
    this.viewMode = mode;
    this.saveUserPreference('viewMode', mode);
    $('#gear-items').html(this.renderGearItems());
  },
  
  setSortBy(sortBy) {
    this.sortBy = sortBy;
    this.saveUserPreference('sortBy', sortBy);
    $('#gear-items').html(this.renderGearItems());
  },
  
  toggleFavorites() {
    this.filters.onlyFavorites = !this.filters.onlyFavorites;
    $('#gear-items').html(this.renderGearItems());
  },
  
  toggleHideAdded() {
    this.filters.hideAdded = !this.filters.hideAdded;
    $('#gear-items').html(this.renderGearItems());
  },
  
  filterPackByCategory(category) {
    $('.cat-tab').removeClass('active');
    $(`.cat-tab[data-category="${category}"]`).addClass('active');
    
    if (category === 'all') {
      $('.pack-category-group').show();
    } else {
      $('.pack-category-group').hide();
      $(`.pack-category-group[data-category="${category}"]`).show();
    }
  },
  
  toggleQuickAdd() {
    $('#fab-menu').toggle();
  },
  
  quickAddEssentials() {
    // Add common essential items
    const essentials = ['tent', 'sleeping bag', 'stove', 'first aid', 'water filter'];
    let added = 0;
    
    essentials.forEach(keyword => {
      const item = this.gearItems.find(g => 
        g.name.toLowerCase().includes(keyword) && !this.isItemInPack(g.id)
      );
      if (item) {
        this.addToPack(item.id);
        added++;
      }
    });
    
    UXUtils.toast(`Added ${added} essential items`, 'success');
    $('#fab-menu').hide();
  },
  
  clearPack() {
    if (confirm('Clear all items from pack?')) {
      this.packItems = [];
      this.renderBuilder();
      this.autoSave();
      UXUtils.toast('Pack cleared', 'info');
    }
  },
  
  savePack() {
    UXUtils.showLoader('Saving pack...');
    
    const packData = {
      name: $('#pack-name').val(),
      notes: $('#pack-notes').val(),
      items: this.packItems,
      base_weight_g: this.calculateBaseWeight(),
      total_weight_g: this.calculateTotalWeight()
    };
    
    // Save via API
    $.ajax({
      url: '/BTT/api/index.php?route=backpacks',
      method: this.currentPack?.id ? 'PUT' : 'POST',
      data: JSON.stringify(packData),
      contentType: 'application/json',
      success: (response) => {
        UXUtils.hideLoader();
        if (response.success) {
          this.currentPack = response.data;
          UXUtils.toast('Pack saved successfully!', 'success');
        } else {
          UXUtils.toast('Failed to save pack', 'error');
        }
      },
      error: () => {
        UXUtils.hideLoader();
        UXUtils.toast('Error saving pack', 'error');
      }
    });
  },
  
  autoSave() {
    clearTimeout(this.autoSaveTimer);
    this.autoSaveTimer = setTimeout(() => {
      this.savePackLocal();
    }, 2000);
  },
  
  savePackLocal() {
    const packData = {
      name: $('#pack-name').val(),
      notes: $('#pack-notes').val(),
      items: this.packItems,
      lastModified: new Date().toISOString()
    };
    
    localStorage.setItem('currentPack', JSON.stringify(packData));
  },
  
  loadPackData() {
    // Check URL for pack ID
    const urlParams = new URLSearchParams(window.location.search);
    const packId = urlParams.get('id');
    
    if (packId) {
      this.loadPack(packId);
    } else {
      // Load from localStorage
      const savedPack = localStorage.getItem('currentPack');
      if (savedPack) {
        try {
          const packData = JSON.parse(savedPack);
          this.packItems = packData.items || [];
        } catch (e) {
          console.error('Failed to load saved pack:', e);
        }
      }
    }
  },
  
  loadPack(id) {
    $.get(`/BTT/api/index.php?route=backpacks&id=${id}`, (response) => {
      if (response.data) {
        this.currentPack = response.data;
        this.packItems = response.data.items || [];
        this.renderBuilder();
      }
    });
  },
  
  loadGearLibrary() {
    $.get('/BTT/api/index.php?route=gear', (response) => {
      if (response.data) {
        this.gearItems = response.data;
        $('#gear-items').html(this.renderGearItems());
      }
    });
  },
  
  calculateBaseWeight() {
    return this.packItems
      .filter(item => !item.worn && !item.consumable)
      .reduce((sum, item) => sum + (item.weight_grams * item.quantity), 0);
  },
  
  calculateTotalWeight() {
    return this.packItems
      .reduce((sum, item) => sum + (item.weight_grams * item.quantity), 0);
  },
  
  exportPack() {
    const packData = {
      name: $('#pack-name').val(),
      notes: $('#pack-notes').val(),
      items: this.packItems,
      exported: new Date().toISOString()
    };
    
    const json = JSON.stringify(packData, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${packData.name || 'pack'}-${Date.now()}.json`;
    a.click();
    URL.revokeObjectURL(url);
    
    UXUtils.toast('Pack exported', 'success');
  },
  
  printPack() {
    window.print();
  },
  
  saveUserPreference(key, value) {
    const prefs = JSON.parse(localStorage.getItem('builderPrefs') || '{}');
    prefs[key] = value;
    localStorage.setItem('builderPrefs', JSON.stringify(prefs));
  },
  
  loadUserPreferences() {
    const prefs = JSON.parse(localStorage.getItem('builderPrefs') || '{}');
    if (prefs.viewMode) this.viewMode = prefs.viewMode;
    if (prefs.sortBy) this.sortBy = prefs.sortBy;
  },
  
  adjustLayout() {
    // Responsive adjustments
    if (window.innerWidth < 1024) {
      $('.builder-content').addClass('stacked');
    } else {
      $('.builder-content').removeClass('stacked');
    }
  }
};

// Initialize on document ready
$(document).ready(() => {
  if ($('#backpack-builder-container').length) {
    BackpackBuilderUX.init();
  }
});
