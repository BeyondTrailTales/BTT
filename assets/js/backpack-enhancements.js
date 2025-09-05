/**
 * Backpack Enhancements - Forest Theme & Trail Features
 * Advanced functionality for backpacking enthusiasts
 * @version 2.0.0
 */

(function() {
    'use strict';

    // Enhanced Backpack Manager
    window.BackpackEnhancements = {
        
        // Initialize enhanced features
        init: function() {
            console.log('🏔️ Initializing Backpack Enhancements...');
            this.setupSearchSuggestions();
            this.setupQuickActions();
            this.setupPackFiltering();
            this.setupWeightTracking();
            this.setupGamification();
            this.bindEvents();
        },

        // Advanced search with suggestions
        setupSearchSuggestions: function() {
            const searchInput = document.getElementById('global-search');
            const suggestionsDiv = document.getElementById('search-suggestions');
            
            if (!searchInput || !suggestionsDiv) return;

            let searchTimeout;
            
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                if (query.length < 2) {
                    suggestionsDiv.style.display = 'none';
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    this.fetchSearchSuggestions(query, suggestionsDiv);
                }, 300);
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                    suggestionsDiv.style.display = 'none';
                }
            });
        },

        // Fetch trail-focused search suggestions
        fetchSearchSuggestions: async function(query, container) {
            try {
                // Mock suggestions for demo - replace with actual API call
                const suggestions = this.generateTrailSuggestions(query);
                this.displaySuggestions(suggestions, container);
            } catch (error) {
                console.error('Search suggestions error:', error);
            }
        },

        // Generate trail-focused suggestions
        generateTrailSuggestions: function(query) {
            const trailTerms = [
                { text: 'Ultralight tent', category: 'shelter', icon: '🏕️' },
                { text: 'Base weight under 4kg', category: 'filter', icon: '⚖️' },
                { text: 'Three-season sleeping bag', category: 'sleep', icon: '🛌' },
                { text: 'Water filter system', category: 'water', icon: '💧' },
                { text: 'Thru-hiking setup', category: 'template', icon: '🏔️' },
                { text: 'Titanium cookware', category: 'cooking', icon: '🍳' },
                { text: 'Rain gear lightweight', category: 'clothing', icon: '🌧️' }
            ];

            return trailTerms.filter(term => 
                term.text.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 5);
        },

        // Display search suggestions with forest theme
        displaySuggestions: function(suggestions, container) {
            if (!suggestions.length) {
                container.style.display = 'none';
                return;
            }

            const html = suggestions.map(suggestion => `
                <div class="suggestion-item" data-category="${suggestion.category}">
                    <span class="suggestion-icon">${suggestion.icon}</span>
                    <span class="suggestion-text">${suggestion.text}</span>
                    <span class="suggestion-category">${suggestion.category}</span>
                </div>
            `).join('');

            container.innerHTML = html;
            container.style.display = 'block';

            // Bind suggestion clicks
            container.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => {
                    const text = item.querySelector('.suggestion-text').textContent;
                    document.getElementById('global-search').value = text;
                    container.style.display = 'none';
                    this.executeSearch(text);
                });
            });
        },

        // Setup quick action buttons
        setupQuickActions: function() {
            const quickActions = document.getElementById('quick-actions');
            if (!quickActions) return;

            // Duplicate pack functionality
            const duplicateBtn = document.getElementById('duplicate-pack');
            if (duplicateBtn) {
                duplicateBtn.addEventListener('click', this.duplicateBestPack.bind(this));
            }

            // LighterPack import
            const importBtn = document.getElementById('import-lighterpack');
            if (importBtn) {
                importBtn.addEventListener('click', this.showImportModal.bind(this));
            }

            // Weight analyzer
            const analyzerBtn = document.getElementById('weight-analyzer');
            if (analyzerBtn) {
                analyzerBtn.addEventListener('click', this.showWeightAnalysis.bind(this));
            }
        },

        // Advanced pack filtering
        setupPackFiltering: function() {
            const filterSelect = document.getElementById('filter-packs');
            if (!filterSelect) return;

            filterSelect.addEventListener('change', (e) => {
                this.filterPacks(e.target.value);
            });

            // Add custom filter chips
            this.addFilterChips();
        },

        // Add filter chips for common backpacking categories
        addFilterChips: function() {
            const packsGrid = document.getElementById('packs-grid');
            if (!packsGrid) return;

            const filterChipsHtml = `
                <div class="filter-chips-container">
                    <div class="filter-chips">
                        <button class="filter-chip active" data-filter="all">
                            <span class="chip-icon">🌟</span>
                            <span>All Packs</span>
                        </button>
                        <button class="filter-chip" data-filter="ultralight">
                            <span class="chip-icon">🪶</span>
                            <span>Ultralight</span>
                        </button>
                        <button class="filter-chip" data-filter="weekend">
                            <span class="chip-icon">🏕️</span>
                            <span>Weekend</span>
                        </button>
                        <button class="filter-chip" data-filter="thru-hike">
                            <span class="chip-icon">🏔️</span>
                            <span>Thru-Hike</span>
                        </button>
                        <button class="filter-chip" data-filter="winter">
                            <span class="chip-icon">❄️</span>
                            <span>Winter</span>
                        </button>
                    </div>
                </div>
            `;

            packsGrid.insertAdjacentHTML('beforebegin', filterChipsHtml);

            // Bind filter chip events
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Update active state
                    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    chip.classList.add('active');
                    
                    // Apply filter
                    this.filterPacks(chip.dataset.filter);
                });
            });
        },

        // Real-time weight tracking
        setupWeightTracking: function() {
            const weightTracker = document.getElementById('total-base-weight');
            if (!weightTracker) return;

            // Update weight display periodically
            setInterval(() => {
                this.updateWeightDisplay();
            }, 2000);
        },

        // Update weight display with trail-focused metrics
        updateWeightDisplay: function() {
            // Mock weight data - replace with actual calculation
            const totalWeight = this.calculateTotalBaseWeight();
            const weightElement = document.getElementById('total-base-weight');
            
            if (weightElement) {
                weightElement.textContent = totalWeight;
                
                // Add weight category styling
                this.styleWeightByCategory(weightElement, totalWeight);
            }

            // Update pack stats if visible
            this.updatePackStats();
        },

        // Calculate total base weight across all packs
        calculateTotalBaseWeight: function() {
            // Mock calculation - replace with actual pack data
            const mockWeight = Math.floor(Math.random() * 8000) + 2000; // 2-10kg range
            return this.formatWeight(mockWeight);
        },

        // Format weight with appropriate units
        formatWeight: function(grams) {
            if (grams < 1000) {
                return `${grams}g`;
            } else {
                return `${(grams / 1000).toFixed(1)}kg`;
            }
        },

        // Style weight display by ultralight categories
        styleWeightByCategory: function(element, weightStr) {
            const weight = parseFloat(weightStr);
            const unit = weightStr.includes('kg') ? 'kg' : 'g';
            const weightInGrams = unit === 'kg' ? weight * 1000 : weight;

            // Remove existing weight classes
            element.classList.remove('weight-ultralight', 'weight-light', 'weight-traditional', 'weight-heavy');

            // Ultralight categories for backpacking
            if (weightInGrams < 2300) {
                element.classList.add('weight-ultralight');
                element.title = 'Ultralight Base Weight (<2.3kg)';
            } else if (weightInGrams < 4500) {
                element.classList.add('weight-light');
                element.title = 'Lightweight Base Weight (2.3-4.5kg)';
            } else if (weightInGrams < 9000) {
                element.classList.add('weight-traditional');
                element.title = 'Traditional Base Weight (4.5-9kg)';
            } else {
                element.classList.add('weight-heavy');
                element.title = 'Heavy Base Weight (>9kg)';
            }
        },

        // Gamification elements
        setupGamification: function() {
            this.updateAchievements();
            this.updateStreakDisplay();
            this.showPackingTips();
        },

        // Update pack achievements
        updateAchievements: function() {
            // Mock achievements - replace with actual data
            const achievements = [
                { name: 'Ultralight Master', icon: '🪶', unlocked: true },
                { name: 'Thru-Hike Ready', icon: '🏔️', unlocked: false },
                { name: 'Winter Warrior', icon: '❄️', unlocked: false }
            ];

            this.displayAchievements(achievements);
        },

        // Display achievements in the UI
        displayAchievements: function(achievements) {
            const container = document.querySelector('.hero-badges');
            if (!container) return;

            achievements.forEach(achievement => {
                if (achievement.unlocked) {
                    const badge = document.createElement('div');
                    badge.className = 'achievement-badge';
                    badge.innerHTML = `
                        <span class="achievement-icon">${achievement.icon}</span>
                        <span class="achievement-name">${achievement.name}</span>
                    `;
                    badge.title = `Achievement: ${achievement.name}`;
                    
                    // Add subtle animation
                    badge.style.animation = 'achievement-glow 2s ease-in-out infinite alternate';
                    
                    container.appendChild(badge);
                }
            });
        },

        // Update streak display
        updateStreakDisplay: function() {
            // Mock streak data - replace with actual tracking
            const streak = this.getCurrentStreak();
            this.displayStreak(streak);
        },

        // Get current packing streak
        getCurrentStreak: function() {
            // Mock streak calculation
            return {
                days: Math.floor(Math.random() * 30) + 1,
                longest: Math.floor(Math.random() * 100) + 10,
                lastPack: new Date().toISOString().split('T')[0]
            };
        },

        // Display streak information
        displayStreak: function(streak) {
            const container = document.querySelector('.hero-badges');
            if (!container) return;

            const streakBadge = document.createElement('div');
            streakBadge.className = 'streak-badge';
            streakBadge.innerHTML = `
                <span class="streak-icon">🔥</span>
                <span class="streak-text">${streak.days} day streak</span>
            `;
            streakBadge.title = `Pack building streak: ${streak.days} days (Record: ${streak.longest})`;
            
            container.appendChild(streakBadge);
        },

        // Show packing tips
        showPackingTips: function() {
            // Randomly show helpful packing tips
            const tips = [
                "💡 Tip: Weigh your gear to identify weight savings opportunities",
                "🏔️ Pro tip: Test your pack setup before hitting the trail",
                "⚖️ Remember: Base weight excludes food, water, and fuel",
                "🎒 Pack heavy items close to your back for better balance",
                "🌧️ Always pack a rain plan, even in good weather"
            ];

            // Show tip 20% of the time
            if (Math.random() < 0.2) {
                const randomTip = tips[Math.floor(Math.random() * tips.length)];
                this.showNotification(randomTip, 'info');
            }
        },

        // Bind all enhancement events
        bindEvents: function() {
            // Enhanced pack card interactions
            document.addEventListener('click', (e) => {
                if (e.target.closest('.pack-card')) {
                    this.handlePackCardClick(e.target.closest('.pack-card'));
                }
            });

            // Keyboard shortcuts for power users
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case 'k':
                            e.preventDefault();
                            document.getElementById('global-search')?.focus();
                            break;
                        case 'n':
                            e.preventDefault();
                            document.getElementById('btn-new-pack')?.click();
                            break;
                    }
                }
            });
        },

        // Enhanced pack card interactions
        handlePackCardClick: function(packCard) {
            const packId = packCard.dataset.id;
            if (!packId) return;

            // Add loading state
            packCard.classList.add('loading');

            // Navigate to pack builder with loading feedback
            setTimeout(() => {
                this.openPackBuilder(packId);
            }, 200);
        },

        // Quick actions implementations
        duplicateBestPack: function() {
            this.showNotification('Finding your lightest pack...', 'info');
            
            // Mock implementation - replace with actual logic
            setTimeout(() => {
                this.showNotification('Pack duplicated successfully! 🎒', 'success');
            }, 1000);
        },

        showImportModal: function() {
            // Create import modal
            const modalHtml = `
                <div class="modal forest-modal" id="import-modal">
                    <div class="modal-backdrop"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Import from LighterPack</h2>
                            <button class="modal-close">×</button>
                        </div>
                        <div class="modal-body">
                            <p>Paste your LighterPack URL or CSV export:</p>
                            <input type="url" placeholder="https://lighterpack.com/r/..." class="form-control">
                            <div class="import-options">
                                <label>
                                    <input type="checkbox" checked> Import item weights
                                </label>
                                <label>
                                    <input type="checkbox" checked> Create pack sections
                                </label>
                                <label>
                                    <input type="checkbox"> Mark worn items
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-secondary">Cancel</button>
                            <button class="btn-primary">Import Pack</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.getElementById('import-modal').style.display = 'block';
        },

        showWeightAnalysis: function() {
            this.showNotification('Analyzing your pack weights...', 'info');
            
            // Mock analysis - replace with actual data analysis
            setTimeout(() => {
                const analysis = this.generateWeightAnalysis();
                this.displayWeightAnalysisModal(analysis);
            }, 1500);
        },

        // Generate weight analysis insights
        generateWeightAnalysis: function() {
            return {
                totalPacks: 5,
                averageWeight: '3.2kg',
                lightestPack: '2.1kg',
                heaviestPack: '4.8kg',
                recommendations: [
                    'Consider switching to a lighter tent to save 300g',
                    'Your sleep system is optimized for weight',
                    'Cook system could be reduced by choosing alcohol stove'
                ],
                categories: {
                    shelter: { weight: '1.2kg', percentage: 38 },
                    sleep: { weight: '0.9kg', percentage: 28 },
                    cooking: { weight: '0.6kg', percentage: 19 },
                    other: { weight: '0.5kg', percentage: 15 }
                }
            };
        },

        // Utility functions
        filterPacks: function(filter) {
            console.log(`Filtering packs by: ${filter}`);
            // Implementation would filter the pack grid
        },

        executeSearch: function(query) {
            console.log(`Executing search: ${query}`);
            // Implementation would perform search
        },

        openPackBuilder: function(packId) {
            console.log(`Opening pack builder for: ${packId}`);
            // Implementation would navigate to builder
        },

        updatePackStats: function() {
            // Update pack count and average weight displays
            const totalPacksEl = document.getElementById('total-packs-count');
            const avgWeightEl = document.getElementById('avg-base-weight');

            if (totalPacksEl) totalPacksEl.textContent = '5'; // Mock data
            if (avgWeightEl) avgWeightEl.textContent = '3.2kg'; // Mock data
        },

        showNotification: function(message, type = 'info') {
            // Create forest-themed notification
            const notification = document.createElement('div');
            notification.className = `notification forest-notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-icon">${this.getNotificationIcon(type)}</span>
                    <span class="notification-message">${message}</span>
                </div>
                <button class="notification-close">×</button>
            `;

            document.body.appendChild(notification);

            // Auto remove after 4 seconds
            setTimeout(() => {
                notification.remove();
            }, 4000);

            // Manual close
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
        },

        getNotificationIcon: function(type) {
            const icons = {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                error: '❌'
            };
            return icons[type] || icons.info;
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            BackpackEnhancements.init();
        });
    } else {
        BackpackEnhancements.init();
    }

    // Make available globally
    window.BackpackEnhancements = BackpackEnhancements;

})();