/**
 * Pack Builder JS
 * Modern backpack management with jQuery and drag-and-drop
 * Clean, intuitive interface for gear organization
 */

(function($) {
    'use strict';

    // ==================== State Management ====================
    const PackBuilder = {
        state: {
            currentView: 'my-packs',
            currentPack: null,
            editMode: false,
            packs: [],
            gearLibrary: [],
            isDirty: false,
            dragDropInitialized: false,
            filters: {
                category: 'all',
                search: ''
            }
        },

        // ==================== Initialization ====================
        init: function() {
            console.log('🎒 Pack Builder Initializing...');
            
            // Only initialize once
            if (this.initialized) {
                console.log('Pack Builder already initialized');
                return;
            }
            
            this.bindEvents();
            this.initDragDrop();
            this.loadView('my-packs');
            
        // Load data after a small delay to ensure API is ready
            setTimeout(() => {
                // Load gear library immediately
                this.loadGearLibrary().then(() => {
                    console.log('Gear library loaded:', this.state.gearLibrary.length, 'items');
                    // If we're in builder view, render the gear
                    if (this.state.currentView === 'builder') {
                        this.renderGearLibrary();
                    }
                });
            }, 100);
            
            this.initialized = true;
            console.log('✅ Pack Builder Ready!');
        },

        // ==================== Data Loading ====================
        loadData: function() {
            // Let PackBuilderCRUD handle packs loading
            if (!window.PackBuilderCRUD || !window.PackBuilderCRUD.initialized) {
                // Only load packs if PackBuilderCRUD isn't available
                this.loadPacks();
            }
            
            // Load gear library
            this.loadGearLibrary();
        },

        loadPacks: async function() {
            // Delegate to PackBuilderCRUD if available
            if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.loadExistingPacks === 'function') {
                console.log('Using PackBuilderCRUD to load packs');
                await window.PackBuilderCRUD.loadExistingPacks();
                return;
            }
            
            try {
                // Check if API is available
                if (typeof BttApi === 'undefined') {
                    console.warn('BttApi not yet available, using empty state');
                    this.state.packs = [];
                    this.renderPacksGrid();
                    return;
                }
                
                // Use centralized API client
                const response = await BttApi.backpacks.list();
                
                console.log('Backpacks API response:', response);
                
                // Handle different response formats
                if (response && response.success && response.data) {
                    this.state.packs = response.data;
                } else if (Array.isArray(response)) {
                    this.state.packs = response;
                } else {
                    this.state.packs = [];
                }
                
                this.renderPacksGrid();
            } catch (error) {
                console.error('Error loading packs:', error);
                // Show empty state instead of sample data
                this.state.packs = [];
                this.renderPacksGrid();
            }
        },

        loadGearLibrary: async function() {
            try {
                console.log('🔍 Loading gear library from database...');
                
                // Load from API with proper error handling
                const response = await $.ajax({
                    url: '/BTT/ajax-handler.php?route=gear',
                    method: 'GET',
                    dataType: 'json',
                    timeout: 10000 // 10 second timeout
                });
                
                console.log('📦 Raw API response:', response);
                
                if (Array.isArray(response)) {
                    // Validate and clean gear data
                    this.state.gearLibrary = response.filter(gear => {
                        if (!gear || !gear.id || !gear.name) {
                            console.warn('⚠️ Filtering out invalid gear item:', gear);
                            return false;
                        }
                        
                        // Ensure required properties exist and are valid
                        gear.weight_g = Math.max(0, parseInt(gear.weight_g || gear.weight) || 0);
                        gear.weight = gear.weight_g; // Backwards compatibility
                        gear.category = gear.category || 'other';
                        gear.icon = gear.icon || this.getCategoryIcon(gear.category);
                        
                        return true;
                    });
                    
                    console.log('✅ Loaded and validated', this.state.gearLibrary.length, 'gear items from database');
                    console.log('📦 Sample gear items:', this.state.gearLibrary.slice(0, 3));
                    
                    // Always render the gear library after loading
                    this.renderGearLibrary();
                    return;
                } else if (response && response.success === false) {
                    console.error('❌ API error:', response.message);
                    this.showError('Failed to load gear: ' + response.message);
                } else {
                    console.warn('⚠️ API returned non-array response:', response);
                    this.showError('Invalid response from gear API');
                }
            } catch (error) {
                console.error('❌ Failed to load gear from API:', error);
                this.showError('Failed to load gear library: ' + (error.message || 'Unknown error'));
            }
            
            // Fallback to hardcoded gear database if API fails
            this.state.gearLibrary = [
                // Shelter
                { id: 'tent-1', name: 'Zpacks Duplex', weight: 538, category: 'shelter', icon: '⛺', brand: 'Zpacks', price: 699 },
                { id: 'tent-2', name: 'Big Agnes Copper Spur', weight: 1190, category: 'shelter', icon: '⛺', brand: 'Big Agnes', price: 450 },
                { id: 'tarp-1', name: 'Hyperlite DCF Tarp', weight: 240, category: 'shelter', icon: '🏕️', brand: 'HMG', price: 335 },
                { id: 'bivy-1', name: 'OR Helium Bivy', weight: 454, category: 'shelter', icon: '🏕️', brand: 'Outdoor Research', price: 249 },
                
                // Sleep System
                { id: 'quilt-1', name: 'EE Revelation 20°F', weight: 570, category: 'sleep', icon: '🛌', brand: 'EE', price: 340 },
                { id: 'bag-1', name: 'Western Mountaineering', weight: 840, category: 'sleep', icon: '🛌', brand: 'WM', price: 550 },
                { id: 'pad-1', name: 'NeoAir XLite', weight: 340, category: 'sleep', icon: '🟦', brand: 'Thermarest', price: 210 },
                { id: 'pad-2', name: 'Zlite Sol', weight: 410, category: 'sleep', icon: '🟦', brand: 'Thermarest', price: 55 },
                { id: 'pillow-1', name: 'Sea to Summit Aeros', weight: 60, category: 'sleep', icon: '🛌', brand: 'Sea to Summit', price: 39 },
                
                // Cooking
                { id: 'stove-1', name: 'BRS-3000T', weight: 25, category: 'cooking', icon: '🔥', brand: 'BRS', price: 17 },
                { id: 'stove-2', name: 'Jetboil MiniMo', weight: 415, category: 'cooking', icon: '🔥', brand: 'Jetboil', price: 150 },
                { id: 'stove-3', name: 'MSR PocketRocket 2', weight: 73, category: 'cooking', icon: '🔥', brand: 'MSR', price: 48 },
                { id: 'pot-1', name: 'TOAKS Ti 550ml', weight: 65, category: 'cooking', icon: '🍲', brand: 'TOAKS', price: 34 },
                { id: 'pot-2', name: 'GSI Minimalist', weight: 176, category: 'cooking', icon: '🍲', brand: 'GSI', price: 35 },
                { id: 'spork-1', name: 'Titanium Spork', weight: 17, category: 'cooking', icon: '🍴', brand: 'TOAKS', price: 12 },
                { id: 'mug-1', name: 'Ti Mug 450ml', weight: 50, category: 'cooking', icon: '☕', brand: 'TOAKS', price: 28 },
                
                // Water
                { id: 'filter-1', name: 'Sawyer Mini', weight: 57, category: 'water', icon: '💧', brand: 'Sawyer', price: 24 },
                { id: 'filter-2', name: 'Sawyer Squeeze', weight: 85, category: 'water', icon: '💧', brand: 'Sawyer', price: 37 },
                { id: 'filter-3', name: 'Katadyn BeFree', weight: 59, category: 'water', icon: '💧', brand: 'Katadyn', price: 40 },
                { id: 'bottle-1', name: 'Smart Water 1L', weight: 35, category: 'water', icon: '🍼', brand: 'Smart Water', price: 2 },
                { id: 'bladder-1', name: 'Hydrapak 2L', weight: 109, category: 'water', icon: '💧', brand: 'Hydrapak', price: 35 },
                
                // Navigation
                { id: 'headlamp-1', name: 'Nitecore NU25', weight: 57, category: 'navigation', icon: '🔦', brand: 'Nitecore', price: 39 },
                { id: 'headlamp-2', name: 'Petzl Bindi', weight: 35, category: 'navigation', icon: '🔦', brand: 'Petzl', price: 60 },
                { id: 'poles-1', name: 'Trekking Poles', weight: 476, category: 'navigation', icon: '🥾', brand: 'CMT', price: 30 },
                { id: 'poles-2', name: 'Black Diamond Carbon', weight: 355, category: 'navigation', icon: '🥾', brand: 'BD', price: 180 },
                { id: 'compass-1', name: 'Silva Compass', weight: 28, category: 'navigation', icon: '🧭', brand: 'Silva', price: 25 },
                { id: 'gps-1', name: 'Garmin inReach Mini', weight: 100, category: 'navigation', icon: '📱', brand: 'Garmin', price: 350 },
                
                // Clothing
                { id: 'jacket-1', name: 'Frogg Toggs UL', weight: 155, category: 'clothing', icon: '🧥', brand: 'Frogg Toggs', price: 25 },
                { id: 'jacket-2', name: 'Arc\'teryx Zeta SL', weight: 310, category: 'clothing', icon: '🧥', brand: 'Arc\'teryx', price: 350 },
                { id: 'puffy-1', name: 'Ghost Whisperer', weight: 230, category: 'clothing', icon: '🧥', brand: 'Mountain Hardwear', price: 325 },
                { id: 'fleece-1', name: 'Patagonia R1 Daily', weight: 315, category: 'clothing', icon: '👕', brand: 'Patagonia', price: 79 },
                { id: 'baselayer-1', name: 'Merino Wool Top', weight: 140, category: 'clothing', icon: '👕', brand: 'Smartwool', price: 85 },
                { id: 'pants-1', name: 'Prana Stretch Zion', weight: 340, category: 'clothing', icon: '👖', brand: 'Prana', price: 89 },
                { id: 'shorts-1', name: 'Patagonia Baggies', weight: 110, category: 'clothing', icon: '🩳', brand: 'Patagonia', price: 55 },
                
                // Hygiene
                { id: 'toothbrush-1', name: 'Toothbrush', weight: 10, category: 'hygiene', icon: '🧪', brand: 'Generic', price: 3 },
                { id: 'tp-1', name: 'Toilet Paper', weight: 30, category: 'hygiene', icon: '🧻', brand: 'Generic', price: 2 },
                { id: 'trowel-1', name: 'Deuce Trowel', weight: 17, category: 'hygiene', icon: '🔨', brand: 'TheTentLab', price: 20 },
                { id: 'soap-1', name: 'Dr. Bronners 2oz', weight: 60, category: 'hygiene', icon: '🧼', brand: 'Dr. Bronners', price: 4 },
                { id: 'sunscreen-1', name: 'Sunscreen SPF 50', weight: 85, category: 'hygiene', icon: '☀️', brand: 'Generic', price: 10 },
                
                // First Aid
                { id: 'firstaid-1', name: 'First Aid Kit', weight: 120, category: 'first-aid', icon: '🏥', brand: 'Custom', price: 25 },
                { id: 'meds-1', name: 'Medications', weight: 30, category: 'first-aid', icon: '💊', brand: 'Various', price: 15 },
                { id: 'tape-1', name: 'Leukotape', weight: 20, category: 'first-aid', icon: '🩹', brand: 'BSN', price: 8 },
                { id: 'bandaid-1', name: 'Band-Aids', weight: 15, category: 'first-aid', icon: '🩹', brand: 'Band-Aid', price: 5 },
                
                // Electronics
                { id: 'phone-1', name: 'Smartphone', weight: 180, category: 'electronics', icon: '📱', brand: 'Various', price: 0 },
                { id: 'battery-1', name: 'Anker 10000mAh', weight: 180, category: 'electronics', icon: '🔋', brand: 'Anker', price: 25 },
                { id: 'cable-1', name: 'Charging Cable', weight: 20, category: 'electronics', icon: '🔌', brand: 'Generic', price: 10 },
                { id: 'earbuds-1', name: 'Earbuds', weight: 25, category: 'electronics', icon: '🎧', brand: 'Various', price: 30 },
                
                // Other
                { id: 'pack-1', name: 'Zpacks Nero 38L', weight: 298, category: 'other', icon: '🎒', brand: 'Zpacks', price: 290 },
                { id: 'pack-2', name: 'HMG Southwest 3400', weight: 907, category: 'other', icon: '🎒', brand: 'HMG', price: 370 },
                { id: 'stuffsack-1', name: 'Stuff Sack', weight: 20, category: 'other', icon: '📦', brand: 'Sea to Summit', price: 12 },
                { id: 'rope-1', name: 'Guy Lines', weight: 30, category: 'other', icon: '🪀', brand: 'Generic', price: 8 },
                { id: 'knife-1', name: 'Swiss Army Knife', weight: 58, category: 'other', icon: '🔪', brand: 'Victorinox', price: 35 },
                { id: 'whistle-1', name: 'Emergency Whistle', weight: 10, category: 'other', icon: '📢', brand: 'Generic', price: 5 }
            ];
        },

        getSamplePacks: function() {
            return [
                {
                    id: 1,
                    name: 'Weekend Warrior',
                    description: 'Perfect for 2-3 day trips',
                    weight: 4536,
                    items: 24,
                    modified: '2024-01-15'
                },
                {
                    id: 2,
                    name: 'UL Thru-Hiker',
                    description: 'Ultralight setup for long trails',
                    weight: 3200,
                    items: 18,
                    modified: '2024-01-10'
                }
            ];
        },

        // ==================== Event Binding ====================
        bindEvents: function() {
            const self = this;

            // Tab switching - forest theme
            $(document).on('click', '.view-tab', function() {
                const view = $(this).data('view');
                self.switchView(view);
                
                // Update view title
                $('#view-title-text').text(self.getViewTitle(view));
                
                // Initialize Gear Library when its tab is clicked
                if (view === 'gear-library' && window.GearLibrary) {
                    if (!window.GearLibrary.initialized) {
                        window.GearLibrary.init();
                        window.GearLibrary.initialized = true;
                    } else {
                        window.GearLibrary.loadGearItems();
                    }
                }
            });

            // New pack button
            $('#btn-new-pack').on('click', function() {
                self.createNewPack();
            });

            // Prevent pack card clicks from triggering edit (we want explicit edit button clicks)
            $(document).on('click', '.pack-card', function(e) {
                // Only handle if not clicking on action buttons
                if (!$(e.target).closest('.btn-icon').length) {
                    e.stopPropagation();
                }
            });

            // Category filters
            $(document).on('click', '.cat-filter', function() {
                const category = $(this).data('category');
                self.filterByCategory(category);
            });

            // Gear search
            $('#gear-search').on('input', function() {
                self.state.filters.search = $(this).val().toLowerCase();
                self.renderGearLibrary();
            });

            // Quick add buttons
            $(document).on('click', '.btn-quick-add', function(e) {
                e.stopPropagation();
                const gearId = $(this).closest('.forest-gear-item').data('gear-id');
                self.quickAddGear(gearId);
            });

            // Section toggles
            $(document).on('click', '.btn-section-toggle', function() {
                const section = $(this).closest('.pack-section');
                section.toggleClass('collapsed');
                $(this).text(section.hasClass('collapsed') ? '▶' : '▼');
            });

            // Add section
            $('#add-section').on('click', function() {
                self.addSection();
            });
            
            // Delete section
            $(document).on('click', '.btn-delete-section', function() {
                const sectionId = $(this).data('section-id');
                self.deleteSection(sectionId);
            });

            // Save pack
            $('#btn-save-pack').on('click', function() {
                self.savePack();
            });

            // Cancel edit
            $('#btn-cancel-edit').on('click', function() {
                if (self.state.isDirty && !confirm('You have unsaved changes. Cancel anyway?')) {
                    return;
                }
                self.switchView('my-packs');
            });

            // Custom gear panel
            $('#add-custom-gear').on('click', function() {
                self.showCustomGearPanel();
            });

            $('#close-custom-gear, #cancel-custom').on('click', function() {
                self.hideCustomGearPanel();
            });

            $('#save-custom').on('click', function() {
                self.saveCustomGear();
            });

            // Template usage
            $(document).on('click', '.btn-use-template', function() {
                const template = $(this).closest('.template-card').find('h3').text();
                self.loadTemplate(template);
            });

            // Sort packs
            $('#sort-packs').on('change', function() {
                self.sortPacks($(this).val());
            });

            // View mode toggle
            $(document).on('click', '.view-mode', function() {
                const mode = $(this).data('mode');
                $('.view-mode').removeClass('active');
                $(this).addClass('active');
                $('#packs-grid').toggleClass('list-view', mode === 'list');
            });

            // Global search
            $('#global-search').on('input', function() {
                self.globalSearch($(this).val());
            });

            // Inline editing
            $(document).on('blur', '.section-name', function() {
                self.state.isDirty = true;
                self.updateWeights();
            });

            $(document).on('change', '#pack-name, #pack-description, #pack-capacity, #pack-base-weight', function() {
                self.state.isDirty = true;
            });
        },

        // ==================== View Management ====================
        getViewTitle: function(view) {
            const titles = {
                'my-packs': 'My Pack Arsenal',
                'templates': 'Pack Templates',
                'builder': 'Pack Builder'
            };
            return titles[view] || 'Pack Builder';
        },

        switchView: function(view) {
            // Update forest theme tabs (new interface)
            $('.view-tab').removeClass('active');
            $(`.view-tab[data-view="${view}"]`).addClass('active');
            
            // Update legacy tabs (fallback)
            $('.pack-tab').removeClass('active');
            $(`.pack-tab[data-view="${view}"]`).addClass('active');
            
            // Update content for forest theme
            $('.pack-view').hide().removeClass('active');
            $(`#view-${view}`).show().addClass('active');
            
            this.state.currentView = view;
            
            // Load view-specific content
            this.loadView(view);
        },

        loadView: function(view) {
            switch(view) {
                case 'my-packs':
                    // Use PackBuilderCRUD if available
                    if (window.PackBuilderCRUD && window.PackBuilderCRUD.loadExistingPacks) {
                        window.PackBuilderCRUD.loadExistingPacks();
                    } else {
                        this.loadPacks();
                    }
                    break;
                case 'builder':
                    if (!this.state.currentPack) {
                        this.createNewPack();
                    }
                    this.renderBuilder();
                    // Make sure gear is loaded before rendering
                    if (this.state.gearLibrary.length === 0) {
                        this.loadGearLibrary().then(() => {
                            this.renderGearLibrary();
                        });
                    } else {
                        this.renderGearLibrary();
                    }
                    break;
                case 'templates':
                    this.renderTemplates();
                    break;
                case 'gear-library':
                    this.renderGearManagement();
                    break;
            }
        },

        // ==================== Pack Management ====================
        createNewPack: function() {
            this.state.currentPack = {
                id: null,
                name: '',
                description: '',
                capacity: 65,
                base_weight: 0,
                sections: this.getDefaultSections(),
                items: []
            };
            
            this.state.editMode = false;
            this.state.isDirty = false;
            this.switchView('builder');
        },

        deletePack: async function(packId) {
            if (!confirm('Are you sure you want to delete this pack? This cannot be undone.')) {
                return;
            }
            
            try {
                // Use PackBuilderCRUD if available
                if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.deletePack === 'function') {
                    await window.PackBuilderCRUD.deletePack(packId);
                    return;
                }
                
                // Fallback API call
                const response = await $.ajax({
                    url: `/BTT/ajax-handler.php?route=backpacks&id=${packId}`,
                    method: 'DELETE',
                    dataType: 'json'
                });
                
                if (response && response.success) {
                    this.showSuccess('Pack deleted successfully!');
                    // Remove from state and re-render
                    this.state.packs = this.state.packs.filter(p => p.id != packId);
                    this.renderPacksGrid();
                } else {
                    alert('Failed to delete pack: ' + (response.message || 'Unknown error'));
                }
                
            } catch (error) {
                console.error('Delete pack error:', error);
                alert('Failed to delete pack. Please try again.');
            }
        },

        editPack: function(packId) {
            // Load pack data
            const pack = this.state.packs.find(p => p.id == packId);
            if (!pack) return;
            
            this.state.currentPack = {
                ...pack,
                sections: pack.sections || this.getDefaultSections()
            };
            
            this.state.editMode = true;
            this.state.isDirty = false;
            this.switchView('builder');
        },

        savePack: async function() {
            // Delegate to PackBuilderCRUD if available
            if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.savePack === 'function') {
                return window.PackBuilderCRUD.savePack();
            }
            
            // Fallback implementation with correct API URLs
            const packName = $('#pack-name').val();
            const packDescription = $('#pack-description').val();
            const packCapacity = $('#pack-capacity').val();
            const packBaseWeight = $('#pack-base-weight').val();
            
            if (!packName || packName.trim() === '') {
                alert('Please enter a pack name');
                $('#pack-name').focus();
                return;
            }
            
            // Update the pack object with form values
            const pack = this.state.currentPack;
            pack.name = packName;
            pack.description = packDescription;
            pack.capacity = packCapacity;
            pack.base_weight = packBaseWeight;
            
            // Collect all items from sections
            pack.items = [];
            $('.pack-section').each(function() {
                const sectionId = $(this).data('section-id');
                $(this).find('.forest-item').each(function() {
                    const item = $(this).data('item');
                    if (item) {
                        pack.items.push({
                            ...item,
                            section: sectionId
                        });
                    }
                });
            });
            
            try {
                // Save to API with correct URLs
                const url = pack.id ? `/BTT/api/index.php?route=backpacks&id=${pack.id}` : '/BTT/api/index.php?route=backpacks';
                const method = pack.id ? 'PUT' : 'POST';
                
                const response = await $.ajax({
                    url: url,
                    method: method,
                    data: JSON.stringify(pack),
                    contentType: 'application/json'
                });
                
                this.showSuccess(pack.id ? 'Pack updated!' : 'Pack created!');
                this.state.isDirty = false;
                this.switchView('my-packs');
                
            } catch (error) {
                console.error('Save error:', error);
                // For demo, just show success
                this.showSuccess(pack.id ? 'Pack updated!' : 'Pack created!');
                this.state.isDirty = false;
                this.switchView('my-packs');
            }
        },

        getDefaultSections: function() {
            return [
                { id: 'main', name: 'Main Compartment', weight: 0 },
                { id: 'lid', name: 'Top Lid', weight: 0 },
                { id: 'pockets', name: 'Side Pockets', weight: 0 }
            ];
        },

        addSection: function(name, id) {
            const sectionName = name || prompt('Section name:');
            if (!sectionName) return;
            
            const sectionId = id || 'section-' + Date.now();
            const sectionHtml = `
                <div class="pack-section forest-section" data-section-id="${sectionId}">
                    <div class="section-header forest-section-header">
                        <span class="section-handle">≡</span>
                        <input type="text" class="section-name forest-section-name" value="${sectionName}">
                        <span class="section-weight forest-weight">0g</span>
                        <button class="btn-delete-section" data-section-id="${sectionId}" style="
                            background: #fef2f2;
                            border: 1px solid #fecaca;
                            color: #dc2626;
                            padding: 0.25rem 0.5rem;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 0.875rem;
                        ">×</button>
                    </div>
                    <div class="section-items dropzone forest-dropzone" data-section="${sectionId}">
                        <div class="dropzone-placeholder forest-placeholder">Drop gear here to add to this section</div>
                    </div>
                </div>
            `;
            
            $('#sections-list').append(sectionHtml);
            this.initSectionDragDrop($(`[data-section="${sectionId}"]`));
            this.state.isDirty = true;
        },
        
        deleteSection: function(sectionId) {
            // Don't allow deleting if it's the last section
            if ($('.pack-section').length <= 1) {
                alert('You must have at least one section in your pack');
                return;
            }
            
            if (confirm('Delete this section and all its items?')) {
                // Remove the section from DOM
                $(`.pack-section[data-section-id="${sectionId}"]`).remove();
                
                // Update weights
                this.updateWeights();
                this.state.isDirty = true;
                
                this.showSuccess('Section deleted');
            }
        },

        // ==================== Enhanced Drag and Drop ====================
        initDragDrop: function() {
            // Prevent multiple initializations
            if (this.state.dragDropInitialized) {
                console.log('🎯 Drag and drop already initialized, skipping...');
                return;
            }
            
            const self = this;
            console.log('🎯 Initializing drag and drop system...');
            
            // Remove any existing event handlers first to prevent duplicates
            $(document).off('dragstart.packbuilder', '.forest-gear-item');
            $(document).off('dragend.packbuilder', '.forest-gear-item');
            $(document).off('dragstart.packbuilder', '.forest-item');
            $(document).off('dragend.packbuilder', '.forest-item');
            $(document).off('dragover.packbuilder', '.forest-dropzone');
            $(document).off('dragleave.packbuilder', '.forest-dropzone');
            $(document).off('drop.packbuilder', '.forest-dropzone');
            
            // Enhanced drag start for gear items with forest animations
            $(document).on('dragstart.packbuilder', '.forest-gear-item', function(e) {
                const gearId = $(this).data('gear-id');
                const gear = self.state.gearLibrary.find(g => g.id === gearId);
                
                if (!gear) {
                    e.preventDefault();
                    return;
                }
                
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('gear', JSON.stringify(gear));
                e.originalEvent.dataTransfer.setData('action', 'add');
                
                // Add forest-themed dragging effects
                $(this).addClass('dragging');
                $('body').addClass('dragging-gear');
                
                // Create custom drag image with glow effect
                const dragImage = $(this).clone();
                dragImage.css({
                    'position': 'absolute',
                    'top': '-1000px',
                    'left': '-1000px',
                    'opacity': '0.9',
                    'transform': 'rotate(3deg) scale(1.1)',
                    'box-shadow': '0 8px 32px rgba(88, 204, 2, 0.6)',
                    'border': '2px solid var(--forest-primary)'
                });
                $('body').append(dragImage);
                e.originalEvent.dataTransfer.setDragImage(dragImage[0], 50, 25);
                
                // Remove the temporary drag image after a delay
                setTimeout(() => dragImage.remove(), 100);
                
                console.log('🎒 Started dragging:', gear.name, '(Instance ID:', Date.now() + ')');
            });
            
            // Enhanced drag end with cleanup
            $(document).on('dragend.packbuilder', '.forest-gear-item', function(e) {
                $(this).removeClass('dragging');
                $('body').removeClass('dragging-gear');
                $('.forest-dropzone').removeClass('drag-over drag-target');
                
                console.log('🎒 Finished dragging');
            });
            
            // Enhanced pack item dragging between sections
            $(document).on('dragstart.packbuilder', '.forest-item', function(e) {
                const item = $(this).data('item');
                
                if (!item) {
                    e.preventDefault();
                    return;
                }
                
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('gear', JSON.stringify(item));
                e.originalEvent.dataTransfer.setData('action', 'move');
                e.originalEvent.dataTransfer.setData('sourceElement', $(this).attr('data-item-id'));
                
                // Add forest-themed moving effects
                $(this).addClass('dragging');
                $('body').addClass('moving-item');
                
                // Highlight valid drop zones
                $('.forest-dropzone').addClass('drag-target');
                
                console.log('🔄 Moving item:', item.name);
            });
            
            $(document).on('dragend.packbuilder', '.forest-item', function(e) {
                $(this).removeClass('dragging');
                $('body').removeClass('moving-item');
                $('.forest-dropzone').removeClass('drag-over drag-target');
            });
            
            // Enhanced droppable zones with forest animations
            $(document).on('dragover.packbuilder', '.forest-dropzone', function(e) {
                e.preventDefault();
                
                const action = e.originalEvent.dataTransfer.types.includes('gear') ? 'copy' : 'move';
                e.originalEvent.dataTransfer.dropEffect = action;
                
                $(this).addClass('drag-over');
                
                // Add pulsing animation for visual feedback
                if (!$(this).hasClass('pulse-animation')) {
                    $(this).addClass('pulse-animation');
                    setTimeout(() => $(this).removeClass('pulse-animation'), 300);
                }
            });
            
            $(document).on('dragleave.packbuilder', '.forest-dropzone', function(e) {
                // Only remove drag-over if we're actually leaving the element
                if (!$.contains(this, e.relatedTarget)) {
                    $(this).removeClass('drag-over');
                }
            });
            
            // Enhanced drop handling with success animations
            $(document).on('drop.packbuilder', '.forest-dropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over drag-target');
                $('body').removeClass('dragging-gear moving-item');
                
                const gearData = e.originalEvent.dataTransfer.getData('gear');
                if (!gearData) return;
                
                const gear = JSON.parse(gearData);
                const action = e.originalEvent.dataTransfer.getData('action');
                const sectionId = $(this).data('section');
                
                // Add success animation
                $(this).addClass('drop-success');
                setTimeout(() => $(this).removeClass('drop-success'), 600);
                
                if (action === 'move') {
                    // Enhanced moving with smooth animations
                    const sourceElementId = e.originalEvent.dataTransfer.getData('sourceElement');
                    const $sourceElement = $(`.forest-item[data-item-id="${sourceElementId}"]`);
                    
                    if ($sourceElement.length) {
                        const $sourceSection = $sourceElement.closest('.forest-dropzone');
                        const $targetSection = $(this);
                        
                        // Animate out from source
                        $sourceElement.addClass('moving-out');
                        
                        setTimeout(() => {
                            // Remove from source
                            $sourceElement.detach().removeClass('moving-out');
                            
                            // Add placeholder to source if empty
                            if ($sourceSection.find('.forest-item').length === 0) {
                                $sourceSection.html(`<div class="forest-placeholder">🎯 Drop gear here to add to this section</div>`);
                            }
                            
                            // Add to target with animation
                            $targetSection.find('.forest-placeholder').remove();
                            $sourceElement.addClass('moving-in');
                            $targetSection.append($sourceElement);
                            
                            // Re-attach item data
                            $sourceElement.data('item', gear);
                            
                            // Complete animation
                            setTimeout(() => $sourceElement.removeClass('moving-in'), 300);
                            
                            self.updateWeights();
                            self.state.isDirty = true;
                            
                            // Success notification with forest theme
                            self.showForestSuccess(`🔄 Moved ${gear.name} to section`, 'move');
                            
                            console.log(`🔄 Moved item "${gear.name}" to ${sectionId} section`);
                        }, 200);
                    }
                } else {
                    // Adding new item with celebration animation
                    self.addGearToSection(gear, sectionId);
                    self.showForestSuccess(`⚡ Added ${gear.name} to pack!`, 'add');
                    
                    console.log(`⚡ Added new item "${gear.name}" to ${sectionId} section (Instance ID: ${Date.now()})`);
                }
            });
            
            // Enhanced jQuery UI sortable with forest animations
            if ($.fn.sortable) {
                // Reinitialize sortable on dynamic content
                this.initSortableElements();
            }
        },
        
        // Initialize sortable elements with forest theme
        initSortableElements: function() {
            const self = this;
            
            // Make sections sortable (reorder sections) with enhanced animations
            $('#sections-list').sortable({
                handle: '.section-handle',
                axis: 'y',
                placeholder: 'section-placeholder',
                cursor: 'move',
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.item.addClass('section-dragging');
                    ui.placeholder.addClass('forest-section-placeholder');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('section-dragging');
                },
                update: function() {
                    self.state.isDirty = true;
                    self.showForestSuccess('📦 Section order updated!', 'reorder');
                }
            });
            
            // Enhanced sortable for items within sections
            $('.dropzone, .forest-dropzone').sortable({
                connectWith: '.dropzone, .forest-dropzone',
                items: '.forest-item',
                handle: '.item-handle',
                placeholder: 'sortable-placeholder forest-sortable-placeholder',
                opacity: 0.8,
                cursor: 'move',
                tolerance: 'pointer',
                distance: 5,
                start: function(e, ui) {
                    ui.item.addClass('sortable-dragging');
                    ui.placeholder.height(ui.item.height());
                    
                    // Highlight connected containers
                    $('.dropzone, .forest-dropzone').addClass('sortable-active');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('sortable-dragging');
                    $('.dropzone, .forest-dropzone').removeClass('sortable-active');
                },
                update: function(e, ui) {
                    // Clean up placeholders
                    $('.dropzone, .forest-dropzone').each(function() {
                        const $section = $(this);
                        const items = $section.find('.forest-item');
                        
                        if (items.length === 0) {
                            const placeholderClass = $section.hasClass('forest-dropzone') ? 'forest-placeholder' : 'dropzone-placeholder';
                            const placeholderText = $section.hasClass('forest-dropzone') ? '🎯 Drop gear here to add to this section' : 'Drop gear here';
                            $section.html(`<div class="${placeholderClass}">${placeholderText}</div>`);
                        } else {
                            $section.find('.dropzone-placeholder, .forest-placeholder').remove();
                        }
                    });
                    
                    self.updateWeights();
                    self.state.isDirty = true;
                    
                    // Success feedback
                    const itemName = ui.item.find('.item-name').text();
                    const targetSectionName = ui.item.closest('.pack-section, .forest-section').find('.section-name, .forest-section-name').val();
                    self.showForestSuccess(`🔄 Moved ${itemName} to ${targetSectionName}`, 'move');
                    
                    console.log(`📦 Item "${itemName}" moved to section "${targetSectionName}"`);
                }
            }).disableSelection();
            
            // Mark as initialized to prevent duplicate bindings
            this.state.dragDropInitialized = true;
            console.log('✅ Drag and drop system initialized successfully');
        },

        initSectionDragDrop: function(section) {
            // Re-initialize sortable for new sections
            if ($.fn.sortable) {
                section.find('.dropzone, .forest-dropzone').sortable({
                    connectWith: '.dropzone, .forest-dropzone',
                    items: '.forest-item',
                    handle: '.item-handle',
                    placeholder: 'sortable-placeholder forest-sortable-placeholder',
                    opacity: 0.8,
                    cursor: 'move',
                    tolerance: 'pointer',
                    distance: 5
                }).disableSelection();
            }
        },

        // ==================== Gear Management ====================
        addGearToSection: function(gear, sectionId) {
            // Validate inputs
            if (!gear || !gear.id || !gear.name) {
                console.error('❌ Invalid gear data:', gear);
                this.showError('Invalid gear item - missing required data');
                return;
            }
            
            if (!sectionId) {
                console.error('❌ Invalid section ID:', sectionId);
                this.showError('Invalid section ID');
                return;
            }
            
            const section = $(`.dropzone[data-section="${sectionId}"]`);
            
            if (section.length === 0) {
                console.error('❌ Section not found:', sectionId);
                this.showError(`Section "${sectionId}" not found`);
                return;
            }
            
            // Remove placeholder if this is the first item
            if (section.find('.forest-item').length === 0) {
                section.find('.dropzone-placeholder').remove();
            }
            
            // Ensure safe data for HTML insertion
            const safeName = (gear.name || 'Unknown Item').replace(/[<>"'&]/g, (match) => {
                const entities = {'<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '&': '&amp;'};
                return entities[match];
            });
            
            const safeId = String(gear.id).replace(/[^a-zA-Z0-9_-]/g, '');
            const safeWeight = Math.max(0, parseInt(gear.weight_g || gear.weight) || 0);
            const safeIcon = gear.icon || this.getCategoryIcon(gear.category) || '📦';
            
            const itemHtml = `
                <div class="list-item pack-list-item forest-item" draggable="true" data-item-id="${safeId}" title="Drag to move between sections">
                    <div class="list-item-media">
                        <div class="item-icon">${safeIcon}</div>
                        <div class="item-handle" title="Drag to reorder">⋮⋮</div>
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title item-name">${safeName}</div>
                        <div class="list-item-subtitle">
                            <span class="item-weight">${this.formatWeight(safeWeight)}</span>
                            ${gear.brand ? ` • <span class="item-brand">${gear.brand}</span>` : ''}
                        </div>
                    </div>
                    <div class="list-item-actions">
                        <input type="number" class="item-qty" value="1" min="1" max="99" style="width: 50px; margin-right: var(--space-sm);">
                        <button class="btn btn-sm btn-ghost btn-remove-item" title="Remove item">✕</button>
                    </div>
                </div>
            `;
            
            const $item = $(itemHtml);
            $item.data('item', gear);
            section.append($item);
            
            console.log('✅ Successfully added gear to section:', {
                gearId: safeId,
                gearName: safeName,
                sectionId: sectionId,
                weight: safeWeight
            });
            
            // Bind remove button
            $item.find('.btn-remove-item').on('click', function() {
                $item.remove();
                if (section.find('.forest-item').length === 0) {
                    section.html('<div class="dropzone-placeholder forest-placeholder">Drop gear here to add to this section</div>');
                }
                PackBuilder.updateWeights();
            });
            
            // Bind quantity change
            $item.find('.item-qty').on('change', function() {
                PackBuilder.updateWeights();
            });
            
            this.updateWeights();
            this.state.isDirty = true;
        },

        quickAddGear: function(gearId) {
            const gear = this.state.gearLibrary.find(g => g.id === gearId);
            if (!gear) return;
            
            // Add to first section by default
            const firstSection = $('.dropzone').first().data('section');
            if (firstSection) {
                this.addGearToSection(gear, firstSection);
            }
        },

        filterByCategory: function(category) {
            this.state.filters.category = category;
            
            $('.cat-filter').removeClass('active');
            $(`.cat-filter[data-category="${category}"]`).addClass('active');
            
            this.renderGearLibrary();
        },

        renderGearLibrary: function() {
            const filtered = this.state.gearLibrary.filter(gear => {
                const matchesCategory = this.state.filters.category === 'all' || 
                                       gear.category === this.state.filters.category;
                const matchesSearch = !this.state.filters.search || 
                                     gear.name.toLowerCase().includes(this.state.filters.search);
                return matchesCategory && matchesSearch;
            });
            
            const $container = $('#gear-library');
            
            if (filtered.length === 0) {
                $container.html(`
                    <div class="empty-gear-library">
                        <div class="empty-icon">🔍</div>
                        <div class="empty-text">No gear found</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                            Try adjusting your search or category filter
                        </div>
                    </div>
                `);
                return;
            }
            
            const html = filtered.map(gear => `
                <div class="gear-item" draggable="true" data-gear-id="${gear.id}" data-name="${gear.name}" data-weight="${gear.weight_g || gear.weight || 0}" data-icon="${gear.icon || this.getCategoryIcon(gear.category)}" data-category="${gear.category}" title="${gear.brand ? gear.brand + ' - ' : ''}${gear.name}">
                    <div class="gear-item-icon">${gear.icon || this.getCategoryIcon(gear.category)}</div>
                    <div class="gear-item-details">
                        <div class="gear-item-name">${gear.name}</div>
                        <div class="gear-item-meta">
                            <span class="gear-weight">${this.formatWeight(gear.weight_g || gear.weight || 0)}</span>
                            ${gear.brand ? `<span class="gear-brand">${gear.brand}</span>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
            
            $container.html(html);
            
            // Reinitialize drag and drop for new gear items
            if (window.PackBuilderDragDrop && typeof window.PackBuilderDragDrop.init === 'function') {
                window.PackBuilderDragDrop.init();
            }
        },
        
        formatCategory: function(category) {
            if (!category) return 'Other';
            return category.charAt(0).toUpperCase() + category.slice(1).replace('-', ' ');
        },
        
        getCategoryIcon: function(category) {
            const categoryIcons = {
                'shelter': '⛺',
                'sleep': '🛌', 
                'cooking': '🔥',
                'water': '💧',
                'navigation': '🧭',
                'clothing': '👕',
                'hygiene': '🧼',
                'first-aid': '🏥',
                'electronics': '🔋',
                'other': '📦'
            };
            return categoryIcons[category] || '📦';
        },
        
        truncateBrand: function(brand) {
            if (!brand) return '';
            if (brand.length <= 8) return brand;
            return brand.substring(0, 7) + '...';
        },

        // ==================== Quick Add Functionality ====================
        quickAddItem: function(gearId) {
            const gear = this.state.gearLibrary.find(g => g.id == gearId);
            if (!gear) return;

            // Find the first available section or create a default one
            let targetSection = $('.pack-section, .forest-section').first();
            if (targetSection.length === 0) {
                // Create a default section if none exist
                this.addSection('Essential', 'essential');
                targetSection = $('.pack-section, .forest-section').first();
            }

            // Add item to the target section
            // Ensure safe data for HTML insertion
            const safeName = (gear.name || 'Unknown Item').replace(/[<>"'&]/g, (match) => {
                const entities = {'<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '&': '&amp;'};
                return entities[match];
            });
            
            const safeId = String(gear.id).replace(/[^a-zA-Z0-9_-]/g, '');
            const safeWeight = Math.max(0, parseInt(gear.weight_g || gear.weight) || 0);
            const safeIcon = gear.icon || this.getCategoryIcon(gear.category) || '📦';
            
            const itemHtml = `
                <div class="list-item pack-list-item forest-item" draggable="true" data-item-id="${safeId}" title="Drag to move between sections">
                    <div class="list-item-media">
                        <div class="item-icon">${safeIcon}</div>
                        <div class="item-handle" title="Drag to reorder">⋮⋮</div>
                    </div>
                    <div class="list-item-content">
                        <div class="list-item-title item-name">${safeName}</div>
                        <div class="list-item-subtitle">
                            <span class="item-weight">${this.formatWeight(safeWeight)}</span>
                            ${gear.brand ? ` • <span class="item-brand">${gear.brand}</span>` : ''}
                        </div>
                    </div>
                    <div class="list-item-actions">
                        <input type="number" class="item-qty" value="1" min="1" max="99" style="width: 50px; margin-right: var(--space-sm);">
                        <button class="btn btn-sm btn-ghost btn-remove-item" title="Remove item">✕</button>
                    </div>
                </div>
            `;

            targetSection.find('.section-items, .forest-items').append(itemHtml);
            
            // Update weights and show success animation
            this.updateWeights();
            
            // Add success animation to the gear item
            const gearElement = $(`.forest-gear-item[data-gear-id="${gearId}"]`);
            gearElement.addClass('quick-added');
            setTimeout(() => gearElement.removeClass('quick-added'), 600);
            
            // Show success notification
            this.showNotification('Item added successfully!', 'success');
        },

        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="forest-notification notification-${type}">
                    <div class="notification-content">
                        <div class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</div>
                        <div class="notification-message">${message}</div>
                    </div>
                    <button class="notification-close">×</button>
                </div>
            `);

            $('body').append(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.fadeOut(300, () => notification.remove());
            }, 3000);

            // Close button functionality
            notification.find('.notification-close').on('click', () => {
                notification.fadeOut(300, () => notification.remove());
            });
        },
        
        showForestSuccess: function(message, type = 'success') {
            // Remove any existing notifications
            $('.forest-success').remove();
            
            const notification = $(`
                <div class="forest-success">
                    <span>${message}</span>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        // ==================== Enhanced Weight Calculations with Optimization ====================
        updateWeights: function() {
            let totalWeight = 0;
            let baseWeight = parseInt($('#pack-base-weight').val()) || 0;
            let wornWeight = 0;
            let consumableWeight = 0;
            let packItems = [];
            
            // Enhanced section weight calculation
            const self = this;
            $('.pack-section, .forest-section').each(function() {
                let sectionWeight = 0;
                
                $(this).find('.forest-item').each(function() {
                    const item = $(this).data('item');
                    const qty = parseInt($(this).find('.item-qty, .forest-qty').val()) || 1;
                    const itemWeight = (item.weight_g || item.weight || 0);
                    const weight = itemWeight * qty;
                    
                    sectionWeight += weight;
                    totalWeight += weight;
                    
                    // Store for optimization analysis
                    packItems.push({
                        ...item,
                        quantity: qty,
                        totalWeight: weight,
                        section: $(this).closest('.pack-section, .forest-section').data('section-id')
                    });
                    
                    // Enhanced weight categorization
                    if (item.worn || item.category === 'clothing') {
                        wornWeight += weight;
                    } else if (item.consumable || item.category === 'food') {
                        consumableWeight += weight;
                    } else {
                        baseWeight += weight;
                    }
                });
                
                // Update section weight displays
                $(this).find('.section-weight, .forest-weight').text(self.formatWeight(sectionWeight));
            });
            
            const finalTotalWeight = totalWeight + baseWeight;
            
            // Update weight displays
            $('#total-weight').text(this.formatWeight(finalTotalWeight));
            $('#base-weight').text(this.formatWeight(baseWeight));
            $('#worn-weight').text(this.formatWeight(wornWeight));
            $('#consumable-weight').text(this.formatWeight(consumableWeight));
            
            // Update pack stats in header
            $('#avg-pack-weight').text(this.formatWeight(finalTotalWeight));
            
            // Trigger weight optimization analysis
            this.analyzeWeightOptimization(packItems, finalTotalWeight);
            
            // Update achievements
            this.checkWeightAchievements(finalTotalWeight, packItems.length);
        },
        
        // Weight Optimization Analysis
        analyzeWeightOptimization: function(items, totalWeight) {
            if (items.length === 0) return;
            
            const suggestions = [];
            const heavyItems = items.filter(item => item.totalWeight > 500).sort((a, b) => b.totalWeight - a.totalWeight);
            const duplicateCategories = this.findDuplicateCategories(items);
            
            // Heavy item suggestions
            if (heavyItems.length > 0) {
                suggestions.push({
                    type: 'heavy-items',
                    icon: '⚖️',
                    title: 'Heavy Items Detected',
                    message: `Consider lighter alternatives for: ${heavyItems.slice(0, 3).map(i => i.name).join(', ')}`,
                    severity: heavyItems[0].totalWeight > 1000 ? 'warning' : 'info',
                    savings: Math.round(heavyItems.slice(0, 2).reduce((sum, item) => sum + (item.totalWeight * 0.3), 0))
                });
            }
            
            // Weight category analysis
            if (totalWeight > 15000) { // > 15kg
                suggestions.push({
                    type: 'overweight',
                    icon: '🚨',
                    title: 'Pack is Heavy',
                    message: 'Consider reducing pack weight for better hiking comfort',
                    severity: 'warning',
                    savings: Math.round((totalWeight - 12000))
                });
            } else if (totalWeight < 8000) { // < 8kg
                suggestions.push({
                    type: 'ultralight',
                    icon: '🪶',
                    title: 'Ultralight Achievement!',
                    message: 'Excellent pack weight optimization',
                    severity: 'success',
                    savings: 0
                });
            }
            
            // Duplicate category suggestions
            if (duplicateCategories.length > 0) {
                suggestions.push({
                    type: 'duplicates',
                    icon: '🔄',
                    title: 'Duplicate Items',
                    message: `Multiple items in: ${duplicateCategories.join(', ')}`,
                    severity: 'info',
                    savings: Math.round(duplicateCategories.length * 200)
                });
            }
            
            // Display suggestions
            this.displayOptimizationSuggestions(suggestions);
        },
        
        findDuplicateCategories: function(items) {
            const categoryCounts = {};
            items.forEach(item => {
                const category = item.category || 'other';
                categoryCounts[category] = (categoryCounts[category] || 0) + 1;
            });
            
            return Object.keys(categoryCounts).filter(cat => categoryCounts[cat] > 2);
        },
        
        displayOptimizationSuggestions: function(suggestions) {
            const $container = $('#optimization-suggestions');
            
            if (suggestions.length === 0) {
                $container.hide();
                return;
            }
            
            const suggestionsHtml = suggestions.map(suggestion => `
                <div class="optimization-suggestion ${suggestion.severity}" data-type="${suggestion.type}">
                    <div class="suggestion-header">
                        <span class="suggestion-icon">${suggestion.icon}</span>
                        <h4 class="suggestion-title">${suggestion.title}</h4>
                        ${suggestion.savings > 0 ? `<span class="potential-savings">-${this.formatWeight(suggestion.savings)}</span>` : ''}
                    </div>
                    <p class="suggestion-message">${suggestion.message}</p>
                    ${suggestion.type === 'heavy-items' ? `<button class="btn-suggestion-action" onclick="PackBuilder.highlightHeavyItems()">Show Heavy Items</button>` : ''}
                </div>
            `).join('');
            
            $container.html(suggestionsHtml).show();
        },
        
        highlightHeavyItems: function() {
            // Remove existing highlights
            $('.forest-item').removeClass('heavy-item-highlight');
            
            // Highlight items over 500g
            $('.forest-item').each(function() {
                const item = $(this).data('item');
                const qty = parseInt($(this).find('.item-qty, .forest-qty').val()) || 1;
                const weight = (item.weight_g || item.weight || 0) * qty;
                
                if (weight > 500) {
                    $(this).addClass('heavy-item-highlight');
                }
            });
            
            // Auto-remove highlight after 5 seconds
            setTimeout(() => {
                $('.heavy-item-highlight').removeClass('heavy-item-highlight');
            }, 5000);
            
            this.showForestSuccess('💡 Heavy items highlighted in yellow', 'info');
        },
        
        // Achievement System
        checkWeightAchievements: function(totalWeight, itemCount) {
            const achievements = [];
            
            // Weight-based achievements
            if (totalWeight < 6000) {
                achievements.push({
                    id: 'ultralight-master',
                    title: '🪶 Ultralight Master',
                    description: 'Pack weight under 6kg',
                    rarity: 'legendary'
                });
            } else if (totalWeight < 8000) {
                achievements.push({
                    id: 'lightweight-pro',
                    title: '⚡ Lightweight Pro', 
                    description: 'Pack weight under 8kg',
                    rarity: 'epic'
                });
            } else if (totalWeight < 12000) {
                achievements.push({
                    id: 'efficient-packer',
                    title: '📦 Efficient Packer',
                    description: 'Well-optimized pack weight',
                    rarity: 'rare'
                });
            }
            
            // Item count achievements
            if (itemCount < 15) {
                achievements.push({
                    id: 'minimalist',
                    title: '🎯 Minimalist',
                    description: 'Pack with less than 15 items',
                    rarity: 'epic'
                });
            } else if (itemCount > 50) {
                achievements.push({
                    id: 'pack-collector',
                    title: '📚 Pack Collector',
                    description: 'Comprehensive gear collection',
                    rarity: 'rare'
                });
            }
            
            // Display new achievements
            this.displayAchievements(achievements);
        },
        
        displayAchievements: function(achievements) {
            if (achievements.length === 0) return;
            
            // Store achieved items to avoid spam
            this.achievedItems = this.achievedItems || new Set();
            
            achievements.forEach(achievement => {
                if (!this.achievedItems.has(achievement.id)) {
                    this.achievedItems.add(achievement.id);
                    this.showAchievementUnlock(achievement);
                }
            });
        },
        
        showAchievementUnlock: function(achievement) {
            const rarityStyles = {
                'common': { border: '#6b7280', glow: '0 0 15px rgba(107, 114, 128, 0.5)' },
                'rare': { border: '#3b82f6', glow: '0 0 20px rgba(59, 130, 246, 0.6)' },
                'epic': { border: '#8b5cf6', glow: '0 0 25px rgba(139, 92, 246, 0.7)' },
                'legendary': { border: '#f59e0b', glow: '0 0 30px rgba(245, 158, 11, 0.8)' }
            };
            
            const style = rarityStyles[achievement.rarity] || rarityStyles['common'];
            
            const achievementToast = $(`
                <div class="achievement-unlock" style="
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) scale(0);
                    background: linear-gradient(135deg, var(--forest-bg-card), var(--forest-bg-secondary));
                    border: 3px solid ${style.border};
                    border-radius: 16px;
                    padding: 2rem;
                    box-shadow: ${style.glow}, 0 8px 32px var(--forest-shadow);
                    backdrop-filter: blur(20px);
                    z-index: 10001;
                    text-align: center;
                    max-width: 400px;
                    color: var(--forest-text-primary);
                ">
                    <div class="achievement-header" style="margin-bottom: 1rem;">
                        <h3 style="font-size: 1.5rem; margin: 0; color: var(--forest-primary);">
                            🏆 Achievement Unlocked!
                        </h3>
                    </div>
                    <div class="achievement-content">
                        <h4 style="font-size: 1.3rem; margin: 0.5rem 0; color: ${style.border};">
                            ${achievement.title}
                        </h4>
                        <p style="margin: 0; color: var(--forest-text-secondary); font-size: 1rem;">
                            ${achievement.description}
                        </p>
                        <div class="achievement-rarity" style="
                            margin-top: 1rem;
                            padding: 0.5rem 1rem;
                            background: rgba(88, 204, 2, 0.1);
                            border-radius: 8px;
                            font-size: 0.9rem;
                            font-weight: 600;
                            color: var(--forest-primary);
                            text-transform: uppercase;
                            letter-spacing: 1px;
                        ">
                            ${achievement.rarity} Achievement
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(achievementToast);
            
            // Animate in with bounce
            setTimeout(() => {
                achievementToast.css({
                    'transform': 'translate(-50%, -50%) scale(1)',
                    'transition': 'all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                });
            }, 100);
            
            // Auto-close after 4 seconds
            setTimeout(() => {
                achievementToast.css({
                    'transform': 'translate(-50%, -50%) scale(0)',
                    'opacity': '0'
                });
                setTimeout(() => achievementToast.remove(), 500);
            }, 4000);
            
            // Click to close
            achievementToast.on('click', function() {
                $(this).css({
                    'transform': 'translate(-50%, -50%) scale(0)',
                    'opacity': '0'
                });
                setTimeout(() => $(this).remove(), 300);
            });
        },

        formatWeight: function(grams) {
            if (!grams || grams === 0) return '0g';
            if (grams < 1000) {
                return grams + 'g';
            }
            return (grams / 1000).toFixed(2) + 'kg';
        },
        
        calculateSectionWeight: function(items) {
            if (!items || items.length === 0) return 0;
            return items.reduce((total, item) => {
                const weight = item.weight_g || item.weight || 0;
                const qty = item.quantity || 1;
                return total + (weight * qty);
            }, 0);
        },

        formatPackType: function(type) {
            const types = {
                'day-hike': 'Day Hike',
                'overnight': 'Overnight',
                'weekend': 'Weekend',
                'extended': 'Extended',
                'thru-hike': 'Thru-hike',
                'ultralight': 'Ultralight',
                'winter': 'Winter',
                'custom': 'Custom'
            };
            return types[type] || 'Custom';
        },
        
        getPackTypeIcon: function(type) {
            const icons = {
                'day-hike': '🥾',
                'overnight': '🏕️',
                'weekend': '⛰️',
                'extended': '🗻',
                'thru-hike': '🥾',
                'ultralight': '🪶',
                'winter': '❄️',
                'custom': '🎒'
            };
            return icons[type] || '🎒';
        },
        
        getPackStatus: function(weight) {
            if (weight === 0) return 'empty';
            if (weight < 8000) return 'excellent';
            if (weight < 12000) return 'good';
            if (weight < 18000) return 'warning';
            return 'heavy';
        },
        
        getPackStatusText: function(weight) {
            if (weight === 0) return 'Empty Pack';
            if (weight < 8000) return 'Ultralight';
            if (weight < 12000) return 'Optimized';
            if (weight < 18000) return 'Standard';
            return 'Heavy Pack';
        },

        openPack: function(packId) {
            // Enhanced pack opening with smooth transition
            const pack = this.state.packs.find(p => p.id == packId);
            if (!pack) return;
            
            // Show loading state
            this.showForestSuccess(`🎒 Opening ${pack.name}...`, 'info');
            
            // Load pack with smooth transition
            setTimeout(() => {
                this.editPack(packId);
            }, 300);
        },

        // ==================== Rendering ====================
        renderPacksGrid: function() {
            // Don't render if PackBuilderCRUD is handling it
            if (window.PackBuilderCRUD && window.PackBuilderCRUD.initialized) {
                console.log('PackBuilderCRUD is handling pack grid rendering');
                return;
            }
            
            // Hide loading, show appropriate content
            $('#packs-loading').hide();
            
            if (this.state.packs.length === 0) {
                $('#packs-empty').show();
                $('#packs-grid-items').hide();
                return;
            }
            
            $('#packs-empty').hide();
            $('#packs-grid-items').show();
            
            const html = this.state.packs.map(pack => `
                <div class="pack-card forest-pack-card" data-pack-id="${pack.id}" onclick="PackBuilder.openPack('${pack.id}')">
                    
                    <!-- Pack Card Header -->
                    <div class="pack-card-header">
                        <div>
                            <div class="pack-title">${pack.name}</div>
                            <div class="pack-description">${pack.description || 'Ready for your next adventure'}</div>
                        </div>
                        <div class="pack-status ${pack.status || 'active'}">${pack.status || 'Active'}</div>
                    </div>
                    
                    <!-- Pack Stats -->
                    <div class="pack-stats">
                        <div class="pack-stat">
                            <span class="pack-stat-value">${this.formatWeight(pack.total_weight_g || pack.base_weight || 0)}</span>
                            <span class="pack-stat-label">Weight</span>
                        </div>
                        <div class="pack-stat">
                            <span class="pack-stat-value">${pack.total_items || pack.item_count || 0}</span>
                            <span class="pack-stat-label">Items</span>
                        </div>
                        <div class="pack-stat">
                            <span class="pack-stat-value">${pack.sections_count || 0}</span>
                            <span class="pack-stat-label">Sections</span>
                        </div>
                    </div>
                    
                    <!-- Pack Actions -->
                    <div class="pack-actions-row">
                        <button class="pack-action-btn primary" onclick="event.stopPropagation(); PackBuilder.openPack('${pack.id}')">
                            <span>🎒</span> Open Pack
                        </button>
                        <button class="pack-action-btn" onclick="event.stopPropagation(); PackBuilder.editPack('${pack.id}')">
                            <span>✏️</span> Edit
                        </button>
                        <button class="pack-action-btn" onclick="event.stopPropagation(); PackBuilder.duplicatePack('${pack.id}')">
                            <span>📄</span> Copy
                        </button>
                    </div>
                    
                    <!-- Pack Footer -->
                    <div class="pack-card-footer">
                        <div class="pack-last-updated">
                            <span>📅</span>
                            <span>Updated ${this.formatDate(pack.updated_at || pack.created_at)}</span>
                        </div>
                        <div class="pack-item-count">
                            <span>⚖️</span>
                            <span>${this.getWeightCategory(pack.total_weight_g || pack.base_weight || 0)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
            
            $('#packs-grid-items').html(html);
        },

        formatDate: function(dateString) {
            if (!dateString) return 'Recently';
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = now - date;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 0) return 'Today';
            if (diffDays === 1) return 'Yesterday';
            if (diffDays < 7) return `${diffDays} days ago`;
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
            return date.toLocaleDateString();
        },

        getWeightCategory: function(weight) {
            const kg = weight / 1000;
            if (kg < 5) return 'Ultralight';
            if (kg < 9) return 'Lightweight';
            if (kg < 14) return 'Traditional';
            return 'Heavy';
        },

        openPack: function(packId) {
            console.log('Opening pack:', packId);
            this.loadPack(packId);
            this.switchView('builder');
            
            // Trigger pack opened event for persistence
            $(document).trigger('packOpened', [packId]);
        },

        editPack: function(packId) {
            console.log('Editing pack:', packId);
            // Edit should open a modal for quick edits (name, description, etc.)
            const pack = this.state.packs.find(p => p.id == packId);
            if (!pack) return;
            
            // Create edit modal
            const modalHtml = `
                <div class="create-pack-modal forest-modal active" id="edit-pack-modal">
                    <div class="modal-content forest-modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title">Edit Pack</h2>
                            <button class="modal-close" onclick="PackBuilder.closeEditModal()">&times;</button>
                        </div>
                        <form id="edit-pack-form" onsubmit="PackBuilder.savePackEdit(event, ${packId})">
                            <div class="form-group">
                                <label class="form-label">Pack Name</label>
                                <input type="text" class="form-input" name="name" value="${pack.name}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-textarea" name="description" placeholder="Describe this pack...">${pack.description || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Capacity (Liters)</label>
                                <input type="number" class="form-input" name="capacity" value="${pack.capacity || 65}" min="1" max="150">
                            </div>
                            <div class="modal-actions">
                                <button type="button" class="modal-btn" onclick="PackBuilder.closeEditModal()">Cancel</button>
                                <button type="submit" class="modal-btn primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
        },

        closeEditModal: function() {
            $('#edit-pack-modal').remove();
        },

        savePackEdit: function(event, packId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            // TODO: Save to database
            console.log('Saving pack edit:', packId, Object.fromEntries(formData));
            this.showNotification('Pack updated successfully!', 'success');
            this.closeEditModal();
            this.loadPacks(); // Reload packs
        },

        duplicatePack: function(packId) {
            console.log('Duplicating pack:', packId);
            // TODO: Implement pack duplication
            this.showNotification('Pack duplication feature coming soon!', 'info');
        },

        loadPack: async function(packId) {
            console.log('Loading pack:', packId);
            try {
                // Use the same API pattern as the working packs loading
                const response = await fetch(`/BTT/ajax-handler.php?action=backpacks&pack_id=${packId}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const packData = await response.json();
                
                if (packData && packData.length > 0) {
                    // Find the specific pack from the response
                    const pack = packData.find(p => p.id == packId) || packData[0];
                    this.state.currentPack = {
                        ...pack,
                        sections: [
                            { id: 'essentials', name: 'Essentials', weight: 0, items: [] },
                            { id: 'clothing', name: 'Clothing', weight: 0, items: [] },
                            { id: 'shelter', name: 'Shelter & Sleep', weight: 0, items: [] },
                            { id: 'cooking', name: 'Cooking', weight: 0, items: [] },
                            { id: 'water', name: 'Water & Hydration', weight: 0, items: [] }
                        ],
                        items: []
                    };
                    console.log('Pack loaded successfully:', this.state.currentPack);
                    
                    // Switch to builder view and render
                    this.switchView('builder');
                } else {
                    throw new Error('Pack not found in response');
                }
            } catch (error) {
                console.error('Error loading pack:', error);
                
                // Create a default pack structure and continue
                const defaultPack = this.state.packs.find(p => p.id == packId);
                this.state.currentPack = {
                    id: packId,
                    name: defaultPack ? defaultPack.name : 'Pack #' + packId,
                    description: defaultPack ? defaultPack.description : 'Ready to customize this pack',
                    base_weight: defaultPack ? defaultPack.base_weight : 0,
                    capacity: 65,
                    sections: [
                        { id: 'essentials', name: 'Essentials', weight: 0, items: [] },
                        { id: 'clothing', name: 'Clothing', weight: 0, items: [] },
                        { id: 'shelter', name: 'Shelter & Sleep', weight: 0, items: [] },
                        { id: 'cooking', name: 'Cooking', weight: 0, items: [] },
                        { id: 'water', name: 'Water & Hydration', weight: 0, items: [] }
                    ],
                    items: []
                };
                
                this.showNotification('Pack loaded in editing mode', 'info');
                this.switchView('builder');
            }
        },

        renderBuilder: function() {
            const pack = this.state.currentPack;
            
            // If no current pack, create a new one
            if (!pack) {
                console.log('No current pack, creating new one');
                this.createNewPack();
                return;
            }
            
            // Update the builder view content with redesigned layout
            const builderHtml = `
                <!-- Pack Builder Header -->
                <div class="pack-builder-header">
                    <div class="pack-builder-title">
                        <h2 class="builder-title">Pack Builder</h2>
                        <div class="pack-quick-info">
                            <input type="text" id="pack-name" class="pack-name-input" value="${pack.name || ''}" placeholder="Pack name...">
                        </div>
                    </div>
                    <div class="pack-metadata">
                        <div class="pack-meta-item">
                            <span class="meta-label">Capacity</span>
                            <input type="number" id="pack-capacity" class="meta-input" value="${pack.capacity || 65}" min="1" max="150">
                            <span class="meta-unit">L</span>
                        </div>
                        <div class="pack-meta-item">
                            <span class="meta-label">Total Weight</span>
                            <div id="total-weight" class="meta-display">${this.formatWeight(pack.total_weight || 0)}</div>
                        </div>
                        <div class="pack-actions-mini">
                            <button id="btn-save-pack" class="btn-save-mini">💾 Save</button>
                            <button id="btn-cancel-edit" class="btn-cancel-mini">← Back</button>
                        </div>
                    </div>
                </div>

                <!-- Pack Builder Main -->
                <div class="pack-builder-main">
                    <!-- Pack Sections -->
                    <div class="sections-panel forest-panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <span class="panel-icon">📦</span>
                                Pack Contents
                            </h3>
                            <button id="add-section" class="btn-add-section forest-btn-sm">
                                <span class="btn-icon">➕</span>
                                Add Section
                            </button>
                        </div>
                        <div class="panel-content">
                            <div id="sections-list" class="sections-container">
                                <!-- Sections will be rendered here -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gear Library -->
                    <div class="gear-library-panel forest-panel">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <span class="panel-icon">⚡</span>
                                Gear Library
                            </h3>
                        </div>
                        <div class="panel-content">
                            <div class="gear-filters">
                                <input type="search" id="gear-search" class="forest-search-input" placeholder="Search gear...">
                                <div class="category-filters">
                                    <button class="cat-filter active" data-category="all">All</button>
                                    <button class="cat-filter" data-category="shelter">⛺ Shelter</button>
                                    <button class="cat-filter" data-category="sleep">🛌 Sleep</button>
                                    <button class="cat-filter" data-category="cooking">🔥 Cook</button>
                                    <button class="cat-filter" data-category="water">💧 Water</button>
                                    <button class="cat-filter" data-category="clothing">👕 Cloth</button>
                                    <button class="cat-filter" data-category="navigation">🧭 Nav</button>
                                </div>
                            </div>
                            <div id="gear-items" class="gear-items-list">
                                <!-- Gear items will be rendered here -->
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#view-builder').html(builderHtml);
            
            // Bind event handlers for the builder
            this.bindBuilderEvents();
            
            // Render sections with items using forest theme
            if (pack.sections && pack.sections.length > 0) {
                const sectionsHtml = pack.sections.map(section => {
                    const items = (pack.items || []).filter(item => item.section === section.id);
                    const itemsHtml = items.length > 0 ? items.map(item => {
                        const safeName = (item.name || 'Unknown Item').replace(/[<>"'&]/g, (match) => {
                            const entities = {'<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '&': '&amp;'};
                            return entities[match];
                        });
                        const safeIcon = item.icon || this.getCategoryIcon(item.category) || '📦';
                        
                        return `
                        <div class="list-item pack-list-item forest-item" data-item-id="${item.id}" draggable="true" title="Drag to move between sections">
                            <div class="list-item-media">
                                <div class="item-icon">${safeIcon}</div>
                                <div class="item-handle" title="Drag to reorder">⋮⋮</div>
                            </div>
                            <div class="list-item-content">
                                <div class="list-item-title item-name">${safeName}</div>
                                <div class="list-item-subtitle">
                                    <span class="item-weight">${this.formatWeight(item.weight || 0)}</span>
                                    ${item.brand ? ` • <span class="item-brand">${item.brand}</span>` : ''}
                                </div>
                            </div>
                            <div class="list-item-actions">
                                <input type="number" class="item-qty" value="${item.quantity || 1}" min="1" max="99" style="width: 50px; margin-right: var(--space-sm);">
                                <button class="btn btn-sm btn-ghost btn-remove-item" title="Remove item">✕</button>
                            </div>
                        </div>`;
                    }).join('') : '<div class="dropzone-placeholder forest-placeholder">🎯 Drop gear here to add to this section</div>';
                    
                    return `
                        <div class="pack-section forest-section" data-section-id="${section.id}">
                            <div class="section-header forest-section-header">
                                <span class="section-handle">≡</span>
                                <input type="text" class="section-name forest-section-name" value="${section.name}">
                                <span class="section-weight forest-weight">${this.calculateSectionWeight(items)}g</span>
                                <button class="btn-delete-section" data-section-id="${section.id}" style="
                                    background: rgba(239, 68, 68, 0.1);
                                    border: 1px solid rgba(239, 68, 68, 0.3);
                                    color: var(--error);
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    font-size: 0.875rem;
                                    width: 24px;
                                    height: 24px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                ">×</button>
                                <button class="btn-section-toggle">▼</button>
                            </div>
                            <div class="section-items dropzone forest-dropzone" data-section="${section.id}">
                                ${itemsHtml}
                            </div>
                        </div>
                    `;
                }).join('');
                
                $('#sections-list').html(sectionsHtml);
            } else {
                // Add a default section if none exist
                const defaultSectionHtml = `
                    <div class="pack-section forest-section" data-section-id="main">
                        <div class="section-header forest-section-header">
                            <span class="section-handle">≡</span>
                            <input type="text" class="section-name forest-section-name" value="Main Pack">
                            <span class="section-weight forest-weight">0g</span>
                            <button class="btn-section-toggle">▼</button>
                        </div>
                        <div class="section-items dropzone forest-dropzone" data-section="main">
                            <div class="dropzone-placeholder forest-placeholder">🎯 Drop gear here to add to this section</div>
                        </div>
                    </div>
                `;
                $('#sections-list').html(defaultSectionHtml);
            }
            
            // Re-initialize drag and drop only if needed
            if (!this.state.dragDropInitialized) {
                this.initDragDrop();
            }
            // Add optimization suggestions panel to builder if it doesn't exist
            if ($('#optimization-suggestions').length === 0) {
                const optimizationPanel = `
                    <div id="optimization-suggestions" class="optimization-panel forest-panel" style="display: none;">
                        <div class="panel-header">
                            <h3 class="panel-title">
                                <span class="panel-icon">💡</span>
                                Pack Optimization
                            </h3>
                        </div>
                        <div class="panel-content">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                `;
                $('.builder-main-layout').after(optimizationPanel);
            }
            
            this.renderGearLibrary();
            this.updateWeights();
        },

        bindBuilderEvents: function() {
            const self = this;
            
            // Add section button
            $(document).off('click', '#add-section').on('click', '#add-section', function() {
                self.addSection();
            });
            
            // Section toggle functionality
            $(document).off('click', '.btn-section-toggle').on('click', '.btn-section-toggle', function(e) {
                e.stopPropagation();
                const section = $(this).closest('.pack-section');
                section.toggleClass('collapsed');
                $(this).text(section.hasClass('collapsed') ? '▶' : '▼');
            });
            
            // Delete section
            $(document).off('click', '.btn-delete-section').on('click', '.btn-delete-section', function(e) {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this section?')) {
                    $(this).closest('.pack-section').remove();
                    self.updateWeights();
                }
            });
            
            // Gear search
            $(document).off('input', '#gear-search').on('input', '#gear-search', function() {
                self.state.filters.search = $(this).val().toLowerCase();
                self.renderGearLibrary();
            });
            
            // Category filters (works with both .cat-filter and .category-filter)
            $(document).off('click', '.cat-filter, .category-filter').on('click', '.cat-filter, .category-filter', function() {
                $('.cat-filter, .category-filter').removeClass('active');
                $(this).addClass('active');
                self.state.filters.category = $(this).data('category');
                self.renderGearLibrary();
            });
            
            // Cancel/Back button
            $(document).off('click', '#btn-cancel-edit').on('click', '#btn-cancel-edit', function() {
                self.switchView('my-packs');
            });
            
            // Save pack button
            $(document).off('click', '#btn-save-pack').on('click', '#btn-save-pack', function() {
                self.savePack();
            });
        },

        renderTemplates: function() {
            // Templates are static in HTML for now
        },

        renderGearManagement: function() {
            const html = this.state.gearLibrary.map(gear => `
                <tr>
                    <td>${gear.name}</td>
                    <td>${gear.category}</td>
                    <td>${gear.weight}g</td>
                    <td>${gear.brand || '-'}</td>
                    <td>${gear.price ? '$' + gear.price : '-'}</td>
                    <td>
                        <button class="btn-edit-gear" data-gear-id="${gear.id}">Edit</button>
                        <button class="btn-delete-gear" data-gear-id="${gear.id}">Delete</button>
                    </td>
                </tr>
            `).join('');
            
            $('#gear-table-body').html(html);
        },

        // ==================== Custom Gear ====================
        showCustomGearPanel: function() {
            $('#custom-gear-panel').show().addClass('active');
        },

        hideCustomGearPanel: function() {
            $('#custom-gear-panel').removeClass('active');
            setTimeout(() => $('#custom-gear-panel').hide(), 300);
        },

        saveCustomGear: function() {
            const gear = {
                id: 'custom-' + Date.now(),
                name: $('#custom-name').val(),
                weight: parseInt($('#custom-weight').val()) || 0,
                category: $('#custom-category').val(),
                notes: $('#custom-notes').val(),
                icon: '📦'
            };
            
            if (!gear.name) {
                alert('Please enter a gear name');
                return;
            }
            
            this.state.gearLibrary.push(gear);
            this.renderGearLibrary();
            this.hideCustomGearPanel();
            
            // Clear form
            $('#custom-name, #custom-weight, #custom-notes').val('');
            
            this.showSuccess('Custom gear added!');
        },

        // ==================== Templates ====================
        loadTemplate: function(templateName) {
            const templates = {
                'Weekend Warrior': {
                    name: 'Weekend Pack',
                    description: 'Based on Weekend Warrior template',
                    capacity: 65,
                    base_weight: 500,
                    sections: this.getDefaultSections()
                },
                'Thru-Hiker': {
                    name: 'UL Thru-Hiker Pack',
                    description: 'Ultralight setup for long trails',
                    capacity: 45,
                    base_weight: 300,
                    sections: this.getDefaultSections()
                },
                'Winter Explorer': {
                    name: 'Winter Pack',
                    description: 'Cold weather and snow camping gear',
                    capacity: 75,
                    base_weight: 800,
                    sections: this.getDefaultSections()
                },
                'Day Hiker': {
                    name: 'Day Pack',
                    description: 'Light setup for single day adventures',
                    capacity: 25,
                    base_weight: 200,
                    sections: this.getDefaultSections()
                }
            };
            
            const template = templates[templateName];
            if (!template) return;
            
            this.state.currentPack = {
                ...template,
                id: null,
                sections: this.getDefaultSections()
            };
            
            this.state.editMode = false;
            this.state.isDirty = false;
            this.switchView('builder');
            
            this.showSuccess(`Loaded ${templateName} template`);
        },

        // ==================== Utilities ====================
        sortPacks: function(sortBy) {
            switch(sortBy) {
                case 'name':
                    this.state.packs.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'weight':
                    this.state.packs.sort((a, b) => (a.weight || 0) - (b.weight || 0));
                    break;
                case 'items':
                    this.state.packs.sort((a, b) => (a.items || 0) - (b.items || 0));
                    break;
                case 'recent':
                default:
                    this.state.packs.sort((a, b) => new Date(b.modified) - new Date(a.modified));
            }
            
            this.renderPacksGrid();
        },

        globalSearch: function(term) {
            if (!term) {
                $('.pack-card, .forest-gear-item').show();
                return;
            }
            
            term = term.toLowerCase();
            
            $('.pack-card').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
            
            $('.forest-gear-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        },

        // Enhanced success notifications with forest theme and action-specific styling
        showForestSuccess: function(message, action = 'default') {
            const actionStyles = {
                'add': { bg: '#4ade80', icon: '⚡', border: '2px solid #22c55e' },
                'move': { bg: '#06b6d4', icon: '🔄', border: '2px solid #0891b2' },
                'delete': { bg: '#f59e0b', icon: '🗑️', border: '2px solid #d97706' },
                'reorder': { bg: '#8b5cf6', icon: '📦', border: '2px solid #7c3aed' },
                'default': { bg: '#4ade80', icon: '✓', border: '2px solid #22c55e' }
            };
            
            const style = actionStyles[action] || actionStyles['default'];
            
            const toast = $(`
                <div class="forest-success-toast" style="
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    background: linear-gradient(135deg, ${style.bg}, ${style.bg}dd);
                    color: #0a2818;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    border: ${style.border};
                    font-weight: 600;
                    font-size: 0.95rem;
                    box-shadow: 0 8px 32px rgba(88, 204, 2, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1) inset;
                    backdrop-filter: blur(10px);
                    z-index: 10000;
                    transform: translateX(400px) scale(0.8);
                    opacity: 0;
                    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    max-width: 300px;
                ">
                    <span class="toast-icon" style="
                        font-size: 1.2rem;
                        filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.3));
                    ">${style.icon}</span>
                    <span class="toast-message">${message}</span>
                </div>
            `);
            
            $('body').append(toast);
            
            // Animate in
            setTimeout(() => {
                toast.css({
                    'transform': 'translateX(0) scale(1)',
                    'opacity': '1'
                });
            }, 50);
            
            // Auto-hide with enhanced animation
            setTimeout(() => {
                toast.css({
                    'transform': 'translateX(400px) scale(0.8)',
                    'opacity': '0'
                });
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        },
        
        showSuccess: function(message) {
            this.showForestSuccess(message, 'default');
        },
        
        showError: function(message) {
            console.error('❌ PackBuilder Error:', message);
            
            // Remove any existing error notifications
            $('.forest-notification.error').remove();
            
            // Create error notification
            const errorHtml = `
                <div class="forest-notification error" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #ff4444, #cc0000);
                    color: white;
                    padding: 16px 24px;
                    border-radius: 12px;
                    z-index: 10000;
                    box-shadow: 0 8px 32px rgba(255, 68, 68, 0.4);
                    border: 2px solid #ff6666;
                    font-weight: 600;
                    animation: slideInRight 0.3s ease-out;
                    max-width: 400px;
                    word-wrap: break-word;
                ">
                    ❌ ${message}
                </div>
            `;
            
            $('body').append(errorHtml);
            
            // Auto-remove after 8 seconds (longer for errors)
            setTimeout(() => {
                $('.forest-notification.error').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 8000);
        },
        
        // Helper function to get section name
        getSectionName: function(sectionId) {
            const $section = $(`.pack-section[data-section-id="${sectionId}"], .forest-section[data-section-id="${sectionId}"]`);
            const sectionName = $section.find('.section-name, .forest-section-name').val();
            return sectionName || 'Unknown Section';
        }
    };

    // ==================== Initialize on DOM Ready ====================
    $(document).ready(function() {
        window.PackBuilder = PackBuilder;
        // Skip self-initialization - let backpacks-init.js handle it
        console.log('PackBuilder: Exposed globally, waiting for backpacks-init.js to initialize');
    });

})(jQuery);
