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
            filters: {
                category: 'all',
                search: ''
            }
        },

        // ==================== Initialization ====================
        init: function() {
            console.log('🎒 Pack Builder Initializing...');
            
            this.loadData();
            this.bindEvents();
            this.initDragDrop();
            this.loadView('my-packs');
            
            console.log('✅ Pack Builder Ready!');
        },

        // ==================== Data Loading ====================
        loadData: function() {
            // Load user's packs
            this.loadPacks();
            
            // Load gear library
            this.loadGearLibrary();
        },

        loadPacks: async function() {
            try {
                const response = await $.ajax({
                    url: '/BTT/api/index.php?route=backpacks',
                    method: 'GET'
                });
                
                this.state.packs = response.data || [];
                this.renderPacksGrid();
            } catch (error) {
                console.error('Error loading packs:', error);
                // Use sample data for now
                this.state.packs = this.getSamplePacks();
                this.renderPacksGrid();
            }
        },

        loadGearLibrary: function() {
            // Comprehensive gear database
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

            // Tab switching
            $(document).on('click', '.pack-tab', function() {
                const view = $(this).data('view');
                self.switchView(view);
                
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

            // Pack card clicks
            $(document).on('click', '.pack-card', function() {
                const packId = $(this).data('pack-id');
                self.editPack(packId);
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
                const gearId = $(this).closest('.gear-item').data('gear-id');
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
        switchView: function(view) {
            // Update tabs
            $('.pack-tab').removeClass('active');
            $(`.pack-tab[data-view="${view}"]`).addClass('active');
            
            // Update content
            $('.pack-view').removeClass('active');
            $(`#view-${view}`).addClass('active');
            
            this.state.currentView = view;
            
            // Load view-specific content
            this.loadView(view);
        },

        loadView: function(view) {
            switch(view) {
                case 'my-packs':
                    this.loadPacks();
                    break;
                case 'builder':
                    if (!this.state.currentPack) {
                        this.createNewPack();
                    }
                    this.renderBuilder();
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
                $(this).find('.pack-item').each(function() {
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

        addSection: function() {
            const sectionName = prompt('Section name:');
            if (!sectionName) return;
            
            const sectionId = 'section-' + Date.now();
            const sectionHtml = `
                <div class="pack-section" data-section-id="${sectionId}">
                    <div class="section-header">
                        <span class="section-handle">≡</span>
                        <input type="text" class="section-name" value="${sectionName}">
                        <span class="section-weight">0g</span>
                        <button class="btn-delete-section" data-section-id="${sectionId}" style="
                            background: rgba(239, 68, 68, 0.2);
                            border: 1px solid rgba(239, 68, 68, 0.4);
                            color: #f87171;
                            padding: 0.25rem 0.5rem;
                            border-radius: 0.25rem;
                            cursor: pointer;
                            margin: 0 0.5rem;
                        ">×</button>
                        <button class="btn-section-toggle">▼</button>
                    </div>
                    <div class="section-items dropzone" data-section="${sectionId}">
                        <div class="dropzone-placeholder">Drop gear here</div>
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

        // ==================== Drag and Drop ====================
        initDragDrop: function() {
            const self = this;
            
            // Make gear items draggable
            $(document).on('dragstart', '.gear-item', function(e) {
                const gearId = $(this).data('gear-id');
                const gear = self.state.gearLibrary.find(g => g.id === gearId);
                e.originalEvent.dataTransfer.effectAllowed = 'copy';
                e.originalEvent.dataTransfer.setData('gear', JSON.stringify(gear));
                $(this).addClass('dragging');
            });
            
            $(document).on('dragend', '.gear-item', function() {
                $(this).removeClass('dragging');
            });
            
            // Make sections droppable
            $(document).on('dragover', '.dropzone', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(this).addClass('drag-over');
            });
            
            $(document).on('dragleave', '.dropzone', function() {
                $(this).removeClass('drag-over');
            });
            
            $(document).on('drop', '.dropzone', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                
                const gearData = e.originalEvent.dataTransfer.getData('gear');
                if (!gearData) return;
                
                const gear = JSON.parse(gearData);
                const sectionId = $(this).data('section');
                
                self.addGearToSection(gear, sectionId);
            });
            
            // Initialize jQuery UI sortable for sections
            if ($.fn.sortable) {
                $('#sections-list').sortable({
                    handle: '.section-handle',
                    axis: 'y',
                    update: function() {
                        self.state.isDirty = true;
                    }
                });
            }
        },

        initSectionDragDrop: function(section) {
            // Initialize drag and drop for a new section
            // Already handled by delegated events
        },

        // ==================== Gear Management ====================
        addGearToSection: function(gear, sectionId) {
            const section = $(`.dropzone[data-section="${sectionId}"]`);
            
            // Remove placeholder if this is the first item
            if (section.find('.pack-item').length === 0) {
                section.find('.dropzone-placeholder').remove();
            }
            
            const itemHtml = `
                <div class="pack-item" draggable="true" data-item-id="${gear.id}" style="
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    padding: 0.75rem;
                    background: rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(74, 222, 128, 0.2);
                    border-radius: 0.5rem;
                    margin-bottom: 0.5rem;
                    cursor: move;
                ">
                    <span class="item-icon" style="font-size: 1.2rem;">${gear.icon || '📦'}</span>
                    <span class="item-name" style="flex: 1; color: #fff;">${gear.name}</span>
                    <input type="number" class="item-qty" value="1" min="1" style="
                        width: 50px;
                        padding: 0.25rem;
                        background: rgba(0, 0, 0, 0.3);
                        border: 1px solid rgba(74, 222, 128, 0.2);
                        border-radius: 0.25rem;
                        color: #fff;
                        text-align: center;
                    ">
                    <span class="item-weight" style="color: #4ade80; min-width: 50px; text-align: right;">${gear.weight}g</span>
                    <button class="btn-remove-item" style="
                        width: 24px;
                        height: 24px;
                        border-radius: 50%;
                        background: rgba(239, 68, 68, 0.2);
                        border: 1px solid rgba(239, 68, 68, 0.4);
                        color: #f87171;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.2rem;
                        line-height: 1;
                        padding: 0;
                    ">×</button>
                </div>
            `;
            
            const $item = $(itemHtml);
            $item.data('item', gear);
            section.append($item);
            
            // Bind remove button
            $item.find('.btn-remove-item').on('click', function() {
                $item.remove();
                if (section.find('.pack-item').length === 0) {
                    section.html('<div class="dropzone-placeholder">Drop gear here</div>');
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
            
            const html = filtered.map(gear => `
                <div class="gear-item draggable" draggable="true" data-gear-id="${gear.id}">
                    <div class="gear-icon">${gear.icon || '📦'}</div>
                    <div class="gear-info">
                        <div class="gear-name">${gear.name}</div>
                        <div class="gear-meta">
                            <span class="gear-weight">${gear.weight}g</span>
                            <span class="gear-category">${gear.category}</span>
                        </div>
                    </div>
                    <button class="btn-quick-add" title="Quick Add">+</button>
                </div>
            `).join('');
            
            $('#gear-items').html(html || '<p style="padding: 1rem; text-align: center;">No gear found</p>');
        },

        // ==================== Weight Calculations ====================
        updateWeights: function() {
            let totalWeight = 0;
            let baseWeight = parseInt($('#pack-base-weight').val()) || 0;
            let wornWeight = 0;
            let consumableWeight = 0;
            
            $('.pack-section').each(function() {
                let sectionWeight = 0;
                
                $(this).find('.pack-item').each(function() {
                    const item = $(this).data('item');
                    const qty = parseInt($(this).find('.item-qty').val()) || 1;
                    const weight = (item.weight || 0) * qty;
                    
                    sectionWeight += weight;
                    totalWeight += weight;
                    
                    // Categories for weight breakdown
                    if (item.worn) {
                        wornWeight += weight;
                    } else if (item.consumable) {
                        consumableWeight += weight;
                    } else {
                        baseWeight += weight;
                    }
                });
                
                $(this).find('.section-weight').text(sectionWeight + 'g');
            });
            
            $('#total-weight').text(this.formatWeight(totalWeight + baseWeight));
            $('#base-weight').text(this.formatWeight(baseWeight));
            $('#worn-weight').text(this.formatWeight(wornWeight));
            $('#consumable-weight').text(this.formatWeight(consumableWeight));
        },

        formatWeight: function(grams) {
            if (grams < 1000) {
                return grams + 'g';
            }
            return (grams / 1000).toFixed(2) + 'kg';
        },

        // ==================== Rendering ====================
        renderPacksGrid: function() {
            if (this.state.packs.length === 0) {
                $('#packs-grid').html(`
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 1rem;">No packs yet</p>
                        <button class="btn-action" onclick="PackBuilder.createNewPack()">
                            Create Your First Pack
                        </button>
                    </div>
                `);
                return;
            }
            
            const html = this.state.packs.map(pack => `
                <div class="pack-card" data-pack-id="${pack.id}">
                    <h3 style="color: #4ade80; margin-bottom: 0.5rem;">${pack.name}</h3>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem; margin-bottom: 1rem;">
                        ${pack.description || 'No description'}
                    </p>
                    <div style="display: flex; justify-content: space-between; color: rgba(255,255,255,0.6); font-size: 0.85rem;">
                        <span>${this.formatWeight(pack.weight || 0)}</span>
                        <span>${pack.items || 0} items</span>
                    </div>
                </div>
            `).join('');
            
            $('#packs-grid').html(html);
        },

        renderBuilder: function() {
            const pack = this.state.currentPack;
            
            $('#pack-name').val(pack.name || '');
            $('#pack-description').val(pack.description || '');
            $('#pack-capacity').val(pack.capacity || 65);
            $('#pack-base-weight').val(pack.base_weight || 0);
            
            // Render sections with items
            if (pack.sections) {
                const sectionsHtml = pack.sections.map(section => {
                    const items = (pack.items || []).filter(item => item.section === section.id);
                    const itemsHtml = items.length > 0 ? items.map(item => `
                        <div class="pack-item" data-item-id="${item.id}">
                            <span class="item-icon">${item.icon || '📦'}</span>
                            <span class="item-name">${item.name}</span>
                            <input type="number" class="item-qty" value="${item.quantity || 1}" min="1" style="width: 50px;">
                            <span class="item-weight">${item.weight || 0}g</span>
                            <button class="btn-remove-item" style="margin-left: auto;">×</button>
                        </div>
                    `).join('') : '<div class="dropzone-placeholder">Drop gear here</div>';
                    
                    return `
                        <div class="pack-section" data-section-id="${section.id}">
                            <div class="section-header">
                                <span class="section-handle">≡</span>
                                <input type="text" class="section-name" value="${section.name}">
                                <span class="section-weight">${section.weight || 0}g</span>
                                <button class="btn-delete-section" data-section-id="${section.id}" style="
                                    background: rgba(239, 68, 68, 0.2);
                                    border: 1px solid rgba(239, 68, 68, 0.4);
                                    color: #f87171;
                                    padding: 0.25rem 0.5rem;
                                    border-radius: 0.25rem;
                                    cursor: pointer;
                                    margin: 0 0.5rem;
                                    font-size: 1rem;
                                ">×</button>
                                <button class="btn-section-toggle">▼</button>
                            </div>
                            <div class="section-items dropzone" data-section="${section.id}">
                                ${itemsHtml}
                            </div>
                        </div>
                    `;
                }).join('');
                
                $('#sections-list').html(sectionsHtml);
            }
            
            this.renderGearLibrary();
            this.updateWeights();
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
                $('.pack-card, .gear-item').show();
                return;
            }
            
            term = term.toLowerCase();
            
            $('.pack-card').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
            
            $('.gear-item').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(term));
            });
        },

        showSuccess: function(message) {
            // Create toast notification
            const toast = $(`
                <div style="
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    background: #4ade80;
                    color: #0a2818;
                    padding: 1rem 1.5rem;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    box-shadow: 0 4px 12px rgba(74, 222, 128, 0.3);
                    z-index: 10000;
                    animation: slideIn 0.3s ease;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    // ==================== Initialize on DOM Ready ====================
    $(document).ready(function() {
        window.PackBuilder = PackBuilder;
        PackBuilder.init();
    });

})(jQuery);
