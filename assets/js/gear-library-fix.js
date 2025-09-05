/**
 * Gear Library Loading Fix
 * Ensures gear items display in the pack builder
 */

$(document).ready(function() {
    console.log('🔧 Gear Library Fix: Starting...');
    
    // Wait for page to fully load and check if gear container exists
    setTimeout(function() {
        // Only load gear if we have a container (Builder tab is active)
        if ($('#gear-items-container, .gear-items-container, #gear-library').length > 0) {
            loadAndDisplayGear();
            initGearFilters();
            initAddCustomGear();
        } else {
            console.log('⏳ Gear library not active - waiting for Builder tab');
        }
    }, 1000);
    
    // Make loadAndDisplayGear available globally
    window.loadAndDisplayGear = function loadAndDisplayGear() {
        console.log('📦 Loading gear for library display...');
        
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('✅ Gear API Response:', response);
                
                if (Array.isArray(response) && response.length > 0) {
                    renderGearInLibrary(response);
                } else if (response && response.success === false) {
                    console.error('❌ Gear API Error:', response.message);
                    showGearError('Authentication failed: ' + response.message);
                } else {
                    console.warn('⚠️ No gear found or invalid response');
                    showEmptyGearMessage();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Gear API Request Failed:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    statusCode: xhr.status
                });
                
                if (xhr.status === 401) {
                    showGearError('Please log in to view your gear');
                } else {
                    showGearError('Failed to load gear: ' + error);
                }
            }
        });
    };
    
    function renderGearInLibrary(gearList) {
        console.log('🎨 Rendering', gearList.length, 'gear items...');
        
        const $gearContainer = $('#gear-items-container, .gear-items-container, #gear-library');
        
        if ($gearContainer.length === 0) {
            console.log('❌ Gear container not found - Builder tab may not be active');
            return;
        }
        
        // Clear existing content
        $gearContainer.empty();
        
        if (gearList.length === 0) {
            $gearContainer.html(`
                <div class="no-gear-message">
                    <p>📦 No gear found</p>
                    <p><a href="gear.php">Add some gear first</a></p>
                </div>
            `);
            return;
        }
        
        // Group gear by category for better organization
        const gearByCategory = {};
        gearList.forEach(gear => {
            const category = gear.category || 'other';
            if (!gearByCategory[category]) {
                gearByCategory[category] = [];
            }
            gearByCategory[category].push(gear);
        });
        
        // Render each category
        Object.keys(gearByCategory).forEach(category => {
            const categoryGear = gearByCategory[category];
            const categoryName = category.charAt(0).toUpperCase() + category.slice(1);
            
            $gearContainer.append(`
                <div class="gear-category-section">
                    <h4 class="gear-category-title">${categoryName}</h4>
                    <div class="gear-category-items" data-category="${category}">
                        ${categoryGear.map(gear => createGearItemHtml(gear)).join('')}
                    </div>
                </div>
            `);
        });
        
        // Make items draggable and setup drop zones
        setTimeout(() => {
            initGearDragDrop();
            initDropZones();
            initPackItemDragDrop();
        }, 100);
        
        console.log('✅ Successfully rendered gear library with', gearList.length, 'items');
    }
    
    function createGearItemHtml(gear) {
        const icon = gear.icon || getCategoryIcon(gear.category);
        const weight = gear.weight_g || gear.weight || 0;
        
        return `
            <div class="gear-item" 
                 draggable="true"
                 data-gear-id="${gear.id}"
                 data-name="${gear.name}"
                 data-weight="${weight}"
                 data-category="${gear.category || 'other'}"
                 data-icon="${icon}">
                <div class="gear-item-content">
                    <div class="gear-item-icon">${icon}</div>
                    <div class="gear-item-details">
                        <div class="gear-item-name">${gear.name}</div>
                        <div class="gear-item-weight">${formatWeight(weight)}</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    function getCategoryIcon(category) {
        const icons = {
            shelter: '🏠',
            sleep: '🛏️',
            clothing: '👕',
            cooking: '🍳',
            water: '💧',
            food: '🍞',
            navigation: '🧭',
            safety: '🚨',
            tools: '🔧',
            electronics: '📱',
            personal: '🧴',
            other: '📦'
        };
        return icons[category] || '📦';
    }
    
    function formatWeight(grams) {
        if (grams >= 1000) {
            return (grams / 1000).toFixed(1) + 'kg';
        }
        return grams + 'g';
    }
    
    function initGearDragDrop() {
        console.log('🔧 Setting up drag for', $('.gear-item').length, 'gear items');
        
        $('.gear-item').off('dragstart').on('dragstart', function(e) {
            console.log('🎯 Drag started for item:', $(this).data('name'));
            
            const gearData = {
                id: $(this).data('gear-id'),
                name: $(this).data('name'),
                weight_g: parseInt($(this).data('weight')) || 0,
                category: $(this).data('category'),
                icon: $(this).data('icon')
            };
            
            console.log('📦 Drag data:', gearData);
            
            e.originalEvent.dataTransfer.setData('application/json', JSON.stringify(gearData));
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
            
            $(this).addClass('dragging');
            $('.gear-drop-zone').addClass('drag-active');
            
            console.log('🎒 Dragging gear:', gearData.name);
        });
        
        $('.gear-item').off('dragend').on('dragend', function(e) {
            $(this).removeClass('dragging');
            $('.gear-drop-zone').removeClass('drag-active drag-over');
        });
    }
    
    function showGearError(message) {
        const $gearContainer = $('#gear-items-container, .gear-items-container');
        $gearContainer.html(`
            <div class="gear-error">
                <p>❌ ${message}</p>
                <button onclick="location.reload()">Retry</button>
            </div>
        `);
    }
    
    function showEmptyGearMessage() {
        const $gearContainer = $('#gear-items-container, .gear-items-container');
        $gearContainer.html(`
            <div class="no-gear-message">
                <p>📦 No gear in your library</p>
                <p><a href="gear.php" class="btn btn-primary">Add Gear</a></p>
            </div>
        `);
    }
    
    function initDropZones() {
        // Handle drop zones for sections
        $('.gear-drop-zone').off('dragover').on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'copy';
            $(this).addClass('drag-over');
        });
        
        $('.gear-drop-zone').off('dragleave').on('dragleave', function(e) {
            if (!$(this).is(e.relatedTarget) && !$.contains(this, e.relatedTarget)) {
                $(this).removeClass('drag-over');
            }
        });
        
        $('.gear-drop-zone').off('drop').on('drop', function(e) {
            e.preventDefault();
            const $dropZone = $(this);
            const targetSection = $dropZone.data('section') || $dropZone.closest('.pack-section').data('section');
            
            $dropZone.removeClass('drag-over drag-active');
            
            try {
                const jsonData = e.originalEvent.dataTransfer.getData('application/json');
                if (!jsonData || jsonData.trim() === '') {
                    console.warn('No drag data found, ignoring drop');
                    return;
                }
                
                const gearData = JSON.parse(jsonData);
                const action = e.originalEvent.dataTransfer.getData('text/action') || 'add';
                
                if (action === 'move') {
                    // Moving between sections
                    const sourceSection = e.originalEvent.dataTransfer.getData('text/source-section');
                    const itemId = e.originalEvent.dataTransfer.getData('text/item-id');
                    moveItemBetweenSections(itemId, sourceSection, targetSection);
                } else {
                    // Adding from gear library
                    addGearToSection(gearData, targetSection);
                }
            } catch (error) {
                console.error('Drop error:', error);
                console.warn('Failed to parse drag data, ignoring drop');
            }
        });
    }
    
    function addGearToSection(gearData, targetSection) {
        console.log('➕ Adding gear to section:', gearData.name, '→', targetSection);
        
        const $targetSection = $(`.pack-section[data-section="${targetSection}"]`);
        const $dropZone = $targetSection.find('.gear-drop-zone');
        
        // Check for duplicates - prevent adding same gear twice
        const existingItem = $dropZone.find(`.pack-item[data-gear-id="${gearData.id}"]`);
        
        if (existingItem.length > 0) {
            // Increase quantity instead of duplicating
            const $quantitySpan = existingItem.find('.pack-item-quantity');
            const currentQty = parseInt($quantitySpan.data('quantity')) || 1;
            const newQty = currentQty + 1;
            
            $quantitySpan.data('quantity', newQty).text(`×${newQty}`);
            existingItem.data('quantity', newQty);
            
            updateSectionWeight(targetSection);
            showToast(`📈 Increased ${gearData.name} quantity to ${newQty}`, 'info');
            console.log('📈 Increased quantity instead of duplicating');
            return;
        }
        
        // Create new pack item
        const itemId = 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const packItemHtml = createPackItemHtml(gearData, itemId);
        
        // Remove empty message
        $dropZone.find('.drop-hint, .forest-placeholder').remove();
        
        // Add item to section
        $dropZone.append(packItemHtml);
        
        // Update weights
        updateSectionWeight(targetSection);
        
        // Show success feedback
        showToast(`✅ Added ${gearData.name} to ${targetSection}`, 'success');
        
        // Initialize drag and drop for the new item
        initPackItemDragDrop();
        
        // Auto-save the item to the database
        autoSavePackItem(gearData, targetSection);
        
        // Trigger pack modified event for persistence
        $(document).trigger('packModified', [{ modified: true }]);
    }
    
    function createPackItemHtml(gearData, itemId) {
        const weight = gearData.weight_g || gearData.weight || 0;
        const icon = gearData.icon || getCategoryIcon(gearData.category);
        
        return `
            <div class="pack-item" draggable="true"
                 data-item-id="${itemId}"
                 data-gear-id="${gearData.id}"
                 data-name="${gearData.name}"
                 data-weight="${weight}"
                 data-quantity="1"
                 data-category="${gearData.category}"
                 data-icon="${icon}">
                <div class="pack-item-content">
                    <div class="pack-item-icon">${icon}</div>
                    <div class="pack-item-details">
                        <div class="pack-item-name">${gearData.name}</div>
                        <div class="pack-item-meta">
                            <span class="pack-item-weight">${formatWeight(weight)}</span>
                            <span class="pack-item-quantity" data-quantity="1">×1</span>
                        </div>
                    </div>
                    <div class="pack-item-actions">
                        <button class="btn-quantity-down" title="Decrease quantity">-</button>
                        <button class="btn-quantity-up" title="Increase quantity">+</button>
                        <button class="btn-remove-item" title="Remove item">×</button>
                    </div>
                </div>
            </div>
        `;
    }
    
    function initPackItemDragDrop() {
        // Make pack items draggable between sections
        $('.pack-item').off('dragstart').on('dragstart', function(e) {
            const $item = $(this);
            const itemData = {
                id: $item.data('gear-id'),
                name: $item.data('name'),
                weight_g: parseInt($item.data('weight')) || 0,
                quantity: parseInt($item.data('quantity')) || 1,
                category: $item.data('category'),
                icon: $item.data('icon')
            };
            
            const sourceSection = $item.closest('.pack-section').data('section');
            const itemId = $item.data('item-id');
            
            e.originalEvent.dataTransfer.setData('application/json', JSON.stringify(itemData));
            e.originalEvent.dataTransfer.setData('text/action', 'move');
            e.originalEvent.dataTransfer.setData('text/source-section', sourceSection);
            e.originalEvent.dataTransfer.setData('text/item-id', itemId);
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            
            $item.addClass('dragging');
            $('.gear-drop-zone').addClass('drag-active');
            
            console.log('🔄 Moving pack item:', itemData.name, 'from', sourceSection);
        });
        
        $('.pack-item').off('dragend').on('dragend', function(e) {
            $(this).removeClass('dragging');
            $('.gear-drop-zone').removeClass('drag-active drag-over');
        });
        
        // Quantity controls
        $('.btn-quantity-up').off('click').on('click', function(e) {
            e.preventDefault();
            const $item = $(this).closest('.pack-item');
            const $quantitySpan = $item.find('.pack-item-quantity');
            const currentQty = parseInt($quantitySpan.data('quantity')) || 1;
            const newQty = Math.min(currentQty + 1, 99);
            
            $quantitySpan.data('quantity', newQty).text(`×${newQty}`);
            $item.data('quantity', newQty);
            
            const section = $item.closest('.pack-section').data('section');
            updateSectionWeight(section);
            
            // Auto-save quantity change
            autoSavePackContents();
        });
        
        $('.btn-quantity-down').off('click').on('click', function(e) {
            e.preventDefault();
            const $item = $(this).closest('.pack-item');
            const $quantitySpan = $item.find('.pack-item-quantity');
            const currentQty = parseInt($quantitySpan.data('quantity')) || 1;
            
            if (currentQty > 1) {
                const newQty = currentQty - 1;
                $quantitySpan.data('quantity', newQty).text(`×${newQty}`);
                $item.data('quantity', newQty);
                
                const section = $item.closest('.pack-section').data('section');
                updateSectionWeight(section);
                
                // Auto-save quantity change
                autoSavePackContents();
            }
        });
        
        // Remove items
        $('.btn-remove-item').off('click').on('click', function(e) {
            e.preventDefault();
            const $item = $(this).closest('.pack-item');
            const itemName = $item.data('name');
            const section = $item.closest('.pack-section').data('section');
            
            $item.remove();
            updateSectionWeight(section);
            showToast(`🗑️ Removed ${itemName}`, 'info');
            
            // Auto-save after item removal
            autoSavePackContents();
        });
    }
    
    function moveItemBetweenSections(itemId, sourceSection, targetSection) {
        if (sourceSection === targetSection) {
            console.log('Item already in target section');
            return;
        }
        
        const $item = $(`.pack-item[data-item-id="${itemId}"]`);
        if ($item.length === 0) {
            console.error('Source item not found:', itemId);
            return;
        }
        
        const itemName = $item.data('name');
        console.log('🔄 Moving item:', itemName, sourceSection, '→', targetSection);
        
        // Remove from source section
        $item.detach();
        
        // Add to target section
        const $targetSection = $(`.pack-section[data-section="${targetSection}"]`);
        const $dropZone = $targetSection.find('.gear-drop-zone');
        
        // Remove empty messages
        $dropZone.find('.drop-hint, .forest-placeholder').remove();
        
        // Add to target
        $dropZone.append($item);
        
        // Update weights for both sections
        updateSectionWeight(sourceSection);
        updateSectionWeight(targetSection);
        
        // Show feedback
        showToast(`🔄 Moved ${itemName} to ${targetSection}`, 'info');
        
        // Auto-save after moving item
        autoSavePackContents();
        
        // Trigger pack modified event for persistence
        $(document).trigger('packModified', [{ modified: true }]);
    }
    
    function updateSectionWeight(sectionId) {
        const $section = $(`.pack-section[data-section="${sectionId}"]`);
        let totalWeight = 0;
        let totalItems = 0;
        
        $section.find('.pack-item').each(function() {
            const weight = parseInt($(this).data('weight')) || 0;
            const quantity = parseInt($(this).data('quantity')) || 1;
            totalWeight += weight * quantity;
            totalItems += quantity;
        });
        
        // Update section display
        $section.find('.section-weight').text(formatWeight(totalWeight));
        $section.find('.section-item-count').text(totalItems + ' items');
        
        // Update total pack weight
        updateTotalPackWeight();
    }
    
    function updateTotalPackWeight() {
        let totalWeight = 0;
        let totalItems = 0;
        
        $('.pack-section').each(function() {
            $(this).find('.pack-item').each(function() {
                const weight = parseInt($(this).data('weight')) || 0;
                const quantity = parseInt($(this).data('quantity')) || 1;
                totalWeight += weight * quantity;
                totalItems += quantity;
            });
        });
        
        $('#quick-total-weight, .pack-total-weight').text(formatWeight(totalWeight));
        $('#quick-total-items, .pack-total-items').text(totalItems);
    }
    
    function showToast(message, type = 'info') {
        const toast = $(`<div class="pack-toast pack-toast-${type}">${message}</div>`);
        $('body').append(toast);
        
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Initialize gear library filters
    function initGearFilters() {
        console.log('🔍 Initializing gear filters...');
        
        // Search filter
        $('#gear-search').off('input').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            filterGearBySearch(searchTerm);
        });
        
        // Category filters
        $('.category-filter').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Update active state
            $('.category-filter').removeClass('active');
            $(this).addClass('active');
            
            const category = $(this).data('category');
            filterGearByCategory(category);
        });
    }
    
    function filterGearBySearch(searchTerm) {
        $('.gear-category-section').each(function() {
            let hasVisibleItems = false;
            
            $(this).find('.gear-item').each(function() {
                const itemName = $(this).data('name').toLowerCase();
                const itemCategory = $(this).data('category').toLowerCase();
                
                if (searchTerm === '' || itemName.includes(searchTerm) || itemCategory.includes(searchTerm)) {
                    $(this).show();
                    hasVisibleItems = true;
                } else {
                    $(this).hide();
                }
            });
            
            // Hide/show category section based on visible items
            if (hasVisibleItems) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
    
    function filterGearByCategory(category) {
        if (category === 'all') {
            $('.gear-category-section').show();
            $('.gear-item').show();
        } else {
            $('.gear-category-section').each(function() {
                const sectionCategory = $(this).find('.gear-category-items').data('category');
                
                if (sectionCategory === category) {
                    $(this).show();
                    $(this).find('.gear-item').show();
                } else {
                    $(this).hide();
                }
            });
        }
    }
    
    // Initialize add custom gear functionality
    function initAddCustomGear() {
        console.log('➕ Initializing add custom gear...');
        
        $('#btn-add-custom-gear').off('click').on('click', function(e) {
            e.preventDefault();
            showAddCustomGearModal();
        });
    }
    
    function showAddCustomGearModal() {
        const modalHtml = `
            <div class="modal-backdrop" id="custom-gear-modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Add Custom Gear</h3>
                            <button class="btn-close" onclick="$('#custom-gear-modal').remove()">×</button>
                        </div>
                        <div class="modal-body">
                            <form id="custom-gear-form">
                                <div class="form-group">
                                    <label for="custom-name">Item Name *</label>
                                    <input type="text" id="custom-name" required placeholder="e.g., My Custom Tent">
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="custom-weight">Weight (grams)</label>
                                        <input type="number" id="custom-weight" min="0" placeholder="0">
                                    </div>
                                    <div class="form-group">
                                        <label for="custom-category">Category</label>
                                        <select id="custom-category">
                                            <option value="other">Other</option>
                                            <option value="shelter">Shelter</option>
                                            <option value="sleep">Sleep System</option>
                                            <option value="cooking">Cooking</option>
                                            <option value="water">Water</option>
                                            <option value="clothing">Clothing</option>
                                            <option value="navigation">Navigation</option>
                                            <option value="safety">Safety</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="custom-brand">Brand</label>
                                        <input type="text" id="custom-brand" placeholder="Brand name">
                                    </div>
                                    <div class="form-group">
                                        <label for="custom-price">Price ($)</label>
                                        <input type="number" id="custom-price" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="custom-notes">Notes</label>
                                    <textarea id="custom-notes" placeholder="Optional notes about this item"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="$('#custom-gear-modal').remove()">Cancel</button>
                            <button class="btn btn-primary" onclick="saveCustomGear()">Add Gear</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#custom-name').focus();
    }
    
    // Make saveCustomGear available globally
    window.saveCustomGear = function() {
        const formData = {
            name: $('#custom-name').val().trim(),
            weight_g: parseInt($('#custom-weight').val()) || 0,
            category: $('#custom-category').val(),
            brand: $('#custom-brand').val().trim(),
            price: parseFloat($('#custom-price').val()) || 0,
            notes: $('#custom-notes').val().trim()
        };
        
        if (!formData.name) {
            showToast('Please enter an item name', 'error');
            return;
        }
        
        // Save to database
        $.ajax({
            url: '/BTT/ajax-handler.php?route=gear',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showToast(`✅ ${formData.name} added to gear library and database`, 'success');
                    $('#custom-gear-modal').remove();
                    
                    // Reload gear library
                    setTimeout(() => {
                        loadAndDisplayGear();
                    }, 500);
                } else {
                    showToast(`❌ Failed to add gear: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                showToast(`❌ Database error: Could not add custom gear`, 'error');
            }
        });
    };
    
    // Create new pack automatically when user starts adding items
    function createNewPackAndSaveItem(gearData, targetSection) {
        const packName = $('#pack-name-input').val() || 'New Pack - ' + new Date().toLocaleDateString();
        const packData = {
            name: packName,
            description: 'Auto-created pack',
            capacity_l: 65,
            weight_empty_g: 0,
            type: 'custom'
        };
        
        console.log('🆕 Creating new pack:', packData);
        
        $.ajax({
            url: '/BTT/ajax-handler.php?route=backpacks',
            method: 'POST',
            data: JSON.stringify(packData),
            contentType: 'application/json',
            success: function(response) {
                if (response.success && response.data) {
                    const newPackId = response.data.id;
                    console.log('✅ Pack created with ID:', newPackId);
                    
                    // Update pack name display
                    $('#pack-name-display').text(packData.name);
                    
                    // Set current pack ID in multiple places for compatibility
                    if (window.PackBuilderCRUD && window.PackBuilderCRUD.state) {
                        window.PackBuilderCRUD.state.currentPackId = newPackId;
                    }
                    localStorage.setItem('btt_current_pack_id', newPackId);
                    
                    // Now save the item to the newly created pack
                    setTimeout(() => {
                        autoSavePackItem(gearData, targetSection);
                    }, 100);
                    
                    showToast(`🎒 Created new pack "${packData.name}"`, 'success');
                } else {
                    console.error('❌ Failed to create pack:', response.message);
                    showToast(`❌ Failed to create pack: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Pack creation error:', error);
                showToast(`❌ Failed to create pack: ${error}`, 'error');
            }
        });
    }
    
    // Auto-save functions for real-time pack updates
    function autoSavePackItem(gearData, targetSection) {
        let currentPackId = getCurrentPackId();
        if (!currentPackId) {
            console.log('No current pack ID, creating a new pack first...');
            createNewPackAndSaveItem(gearData, targetSection);
            return;
        }
        
        console.log('💾 Auto-saving item to pack:', gearData.name, '→', targetSection);
        
        // Save item to backpack_gear table
        $.ajax({
            url: '/BTT/ajax-handler.php?route=backpack-gear',
            method: 'POST',
            data: {
                backpack_id: currentPackId,
                custom_name: gearData.name,
                custom_weight: gearData.weight_g || 0,
                custom_category: gearData.category || 'other',
                quantity: 1,
                section: targetSection,
                gear_id: gearData.id, // Reference to original gear
                notes: ''
            },
            success: function(response) {
                if (response.success) {
                    console.log('✅ Item saved to database');
                    showToast(`💾 Saved ${gearData.name} to database`, 'success');
                    updatePackListCounts();
                } else {
                    console.error('❌ Failed to save item:', response.message);
                    showToast(`❌ Failed to save ${gearData.name}: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Auto-save error:', error);
                showToast(`❌ Database error: Could not save ${gearData.name}`, 'error');
            }
        });
    }
    
    function autoSavePackContents() {
        const currentPackId = getCurrentPackId();
        if (!currentPackId) {
            console.warn('No current pack ID for auto-save');
            return;
        }
        
        console.log('💾 Auto-saving complete pack contents...');
        
        // Collect all pack items and sections
        const packData = collectCurrentPackData();
        
        // Save complete pack state
        $.ajax({
            url: '/BTT/ajax-handler.php?route=backpacks',
            method: 'PUT',
            data: {
                id: currentPackId,
                ...packData
            },
            success: function(response) {
                if (response.success) {
                    console.log('✅ Pack auto-saved successfully');
                    showToast(`💾 Pack saved automatically`, 'success');
                    updatePackListCounts();
                } else {
                    console.error('❌ Failed to auto-save pack:', response.message);
                    showToast(`❌ Failed to save pack: ${response.message}`, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Pack auto-save error:', error);
                showToast(`❌ Database error: Could not save pack`, 'error');
            }
        });
    }
    
    function collectCurrentPackData() {
        const sections = [];
        
        $('.pack-section').each(function() {
            const sectionId = $(this).data('section');
            const sectionName = $(this).find('.section-name').val() || sectionId;
            const items = [];
            
            $(this).find('.pack-item').each(function() {
                const $item = $(this);
                items.push({
                    gear_id: $item.data('gear-id'),
                    name: $item.data('name'),
                    weight_g: parseInt($item.data('weight')) || 0,
                    quantity: parseInt($item.data('quantity')) || 1,
                    category: $item.data('category') || 'other',
                    section: sectionId
                });
            });
            
            if (items.length > 0 || ['main', 'worn', 'consumables'].includes(sectionId)) {
                sections.push({
                    id: sectionId,
                    name: sectionName,
                    items: items
                });
            }
        });
        
        return {
            name: $('#pack-name-input').val() || $('#pack-name-display').text() || 'Backpack',
            description: $('#pack-description').val() || '',
            capacity_l: parseFloat($('#pack-capacity').val()) || 65,
            weight_empty_g: parseFloat($('#pack-base-weight').val()) || 0,
            type: $('#pack-type').val() || 'custom',
            sections: sections
        };
    }
    
    function getCurrentPackId() {
        // Try multiple ways to get current pack ID
        if (window.PackBuilderCRUD && window.PackBuilderCRUD.state && window.PackBuilderCRUD.state.currentPackId) {
            return window.PackBuilderCRUD.state.currentPackId;
        }
        
        if (window.PackBuilder && window.PackBuilder.state && window.PackBuilder.state.currentPack && window.PackBuilder.state.currentPack.id) {
            return window.PackBuilder.state.currentPack.id;
        }
        
        // Try localStorage
        const savedPackId = localStorage.getItem('btt_current_pack_id');
        if (savedPackId) {
            return savedPackId;
        }
        
        console.warn('Could not determine current pack ID');
        return null;
    }
    
    function updatePackListCounts() {
        // Update the pack list with new item counts
        const currentPackId = getCurrentPackId();
        if (!currentPackId) return;
        
        // Count total items and weight
        let totalItems = 0;
        let totalWeight = 0;
        
        $('.pack-item').each(function() {
            const quantity = parseInt($(this).data('quantity')) || 1;
            const weight = parseInt($(this).data('weight')) || 0;
            totalItems += quantity;
            totalWeight += weight * quantity;
        });
        
        // Update the pack card in the list view
        const $packCard = $(`.pack-card[data-pack-id="${currentPackId}"], .forest-pack-card[data-pack-id="${currentPackId}"]`);
        if ($packCard.length) {
            $packCard.find('.pack-items-count, .items-count').text(totalItems);
            $packCard.find('.pack-weight, .total-weight').text(formatWeight(totalWeight));
        }
        
        // Update quick stats in builder
        $('#quick-total-items').text(totalItems);
        $('#quick-total-weight').text(formatWeight(totalWeight));
        
        console.log(`📊 Updated pack counts: ${totalItems} items, ${formatWeight(totalWeight)}`);
    }
});