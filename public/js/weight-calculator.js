/**
 * Weight Calculation Engine
 * Centralized state management with animated counters and progress visuals
 */

class WeightCalculator {
    constructor() {
        this.state = {
            items: [],
            categories: {},
            totalWeight: 0,
            baseWeight: 0,
            wornWeight: 0,
            consumableWeight: 0,
            packWeight: 0,
            targetBaseWeight: 4536, // 10 lbs in grams
            units: 'metric' // metric or imperial
        };
        
        this.listeners = new Set();
        this.animationFrames = new Map();
        
        this.init();
    }
    
    init() {
        // Load saved state from localStorage
        this.loadState();
        
        // Initialize UI
        this.initializeUI();
        
        // Setup auto-save
        this.setupAutoSave();
    }
    
    /**
     * Initialize UI components
     */
    initializeUI() {
        // Create weight display elements if they don't exist
        this.createWeightDisplays();
        
        // Create progress visualizations
        this.createProgressVisuals();
        
        // Setup unit toggle
        this.setupUnitToggle();
    }
    
    /**
     * Create weight display elements
     */
    createWeightDisplays() {
        const summaryCard = document.querySelector('.weight-summary-card');
        if (!summaryCard) return;
        
        // Enhance existing displays
        const displays = {
            'total-weight': this.state.totalWeight,
            'base-weight': this.state.baseWeight,
            'worn-weight': this.state.wornWeight,
            'consumable-weight': this.state.consumableWeight
        };
        
        Object.entries(displays).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.dataset.value = value;
                element.textContent = this.formatWeight(value);
                
                // Add click handler for unit toggle
                element.style.cursor = 'pointer';
                element.addEventListener('click', () => this.toggleUnits());
            }
        });
    }
    
    /**
     * Create progress visualizations
     */
    createProgressVisuals() {
        const chartContainer = document.getElementById('weight-chart');
        if (!chartContainer) return;
        
        chartContainer.innerHTML = `
            <div class="weight-progress">
                <div class="progress-header">
                    <span>Base Weight Goal</span>
                    <span class="progress-percentage">0%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" id="weight-progress-fill"></div>
                    </div>
                    <div class="progress-markers">
                        <div class="marker ultralight" style="left: 20%">
                            <span>UL</span>
                        </div>
                        <div class="marker light" style="left: 40%">
                            <span>L</span>
                        </div>
                        <div class="marker moderate" style="left: 60%">
                            <span>M</span>
                        </div>
                    </div>
                </div>
                <div class="weight-categories">
                    <div class="category-breakdown" id="category-breakdown"></div>
                </div>
            </div>
        `;
        
        // Add styles
        this.injectProgressStyles();
    }
    
    /**
     * Setup unit toggle
     */
    setupUnitToggle() {
        // Create unit toggle button
        const summaryCard = document.querySelector('.weight-summary-card h3');
        if (summaryCard && !document.getElementById('unit-toggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.id = 'unit-toggle';
            toggleBtn.className = 'unit-toggle-btn';
            toggleBtn.textContent = this.state.units === 'metric' ? 'g/kg' : 'oz/lb';
            toggleBtn.onclick = () => this.toggleUnits();
            summaryCard.appendChild(toggleBtn);
        }
    }
    
    /**
     * Add item to pack
     */
    addItem(item) {
        // Check if item already exists
        const existingIndex = this.state.items.findIndex(i => i.id === item.id);
        
        if (existingIndex >= 0) {
            // Increment quantity
            this.state.items[existingIndex].quantity = 
                (this.state.items[existingIndex].quantity || 1) + 1;
        } else {
            // Add new item
            this.state.items.push({
                ...item,
                quantity: 1,
                worn: item.worn || false,
                consumable: item.consumable || false
            });
        }
        
        // Recalculate weights
        this.calculate();
        
        // Show feedback
        showSuccess(`Added ${item.name} to pack`, {
            action: {
                label: 'Undo',
                callback: () => this.removeItem(item.id)
            }
        });
        
        // Save state
        this.saveState();
    }
    
    /**
     * Remove item from pack
     */
    removeItem(itemId) {
        const index = this.state.items.findIndex(i => i.id === itemId);
        if (index < 0) return;
        
        const item = this.state.items[index];
        
        if (item.quantity > 1) {
            // Decrement quantity
            this.state.items[index].quantity--;
        } else {
            // Remove item
            this.state.items.splice(index, 1);
        }
        
        // Recalculate
        this.calculate();
        
        // Show feedback
        showInfo(`Removed ${item.name}`, {
            action: {
                label: 'Undo',
                callback: () => this.addItem(item)
            }
        });
        
        // Save state
        this.saveState();
    }
    
    /**
     * Toggle item worn status
     */
    toggleWorn(itemId) {
        const item = this.state.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.worn = !item.worn;
        
        // If worn, can't be consumable
        if (item.worn) {
            item.consumable = false;
        }
        
        this.calculate();
        this.saveState();
    }
    
    /**
     * Toggle item consumable status
     */
    toggleConsumable(itemId) {
        const item = this.state.items.find(i => i.id === itemId);
        if (!item) return;
        
        item.consumable = !item.consumable;
        
        // If consumable, can't be worn
        if (item.consumable) {
            item.worn = false;
        }
        
        this.calculate();
        this.saveState();
    }
    
    /**
     * Calculate all weights
     */
    calculate() {
        // Reset weights
        this.state.totalWeight = 0;
        this.state.baseWeight = 0;
        this.state.wornWeight = 0;
        this.state.consumableWeight = 0;
        this.state.categories = {};
        
        // Calculate weights
        this.state.items.forEach(item => {
            const weight = (item.weight || 0) * (item.quantity || 1);
            
            // Total weight
            this.state.totalWeight += weight;
            
            // Category weight
            if (item.worn) {
                this.state.wornWeight += weight;
            } else if (item.consumable) {
                this.state.consumableWeight += weight;
            } else {
                this.state.baseWeight += weight;
            }
            
            // Category breakdown
            const category = item.category || 'misc';
            if (!this.state.categories[category]) {
                this.state.categories[category] = 0;
            }
            this.state.categories[category] += weight;
        });
        
        // Add pack weight to base weight
        this.state.baseWeight += this.state.packWeight;
        
        // Update displays with animation
        this.updateDisplays();
        
        // Update progress
        this.updateProgress();
        
        // Update category breakdown
        this.updateCategoryBreakdown();
        
        // Notify listeners
        this.notifyListeners();
    }
    
    /**
     * Update weight displays with animation
     */
    updateDisplays() {
        const displays = {
            'total-weight': this.state.totalWeight,
            'base-weight': this.state.baseWeight,
            'worn-weight': this.state.wornWeight,
            'consumable-weight': this.state.consumableWeight
        };
        
        Object.entries(displays).forEach(([id, targetValue]) => {
            const element = document.getElementById(id);
            if (!element) return;
            
            const currentValue = parseFloat(element.dataset.value) || 0;
            
            // Animate counter
            this.animateCounter(element, currentValue, targetValue);
        });
    }
    
    /**
     * Animate counter from current to target value
     */
    animateCounter(element, from, to) {
        // Cancel existing animation
        if (this.animationFrames.has(element)) {
            cancelAnimationFrame(this.animationFrames.get(element));
        }
        
        const duration = 500; // ms
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function
            const eased = 1 - Math.pow(1 - progress, 3);
            
            // Calculate current value
            const currentValue = from + (to - from) * eased;
            
            // Update display
            element.dataset.value = currentValue;
            element.textContent = this.formatWeight(Math.round(currentValue));
            
            // Add pulse effect on change
            if (progress === 0) {
                element.classList.add('weight-updating');
            }
            
            if (progress < 1) {
                // Continue animation
                const frame = requestAnimationFrame(animate);
                this.animationFrames.set(element, frame);
            } else {
                // Animation complete
                element.classList.remove('weight-updating');
                this.animationFrames.delete(element);
            }
        };
        
        // Start animation
        requestAnimationFrame(animate);
    }
    
    /**
     * Update progress visualization
     */
    updateProgress() {
        const progressFill = document.getElementById('weight-progress-fill');
        const percentageDisplay = document.querySelector('.progress-percentage');
        
        if (!progressFill || !percentageDisplay) return;
        
        // Calculate percentage
        const percentage = Math.min(
            (this.state.baseWeight / this.state.targetBaseWeight) * 100,
            100
        );
        
        // Update fill with animation
        progressFill.style.width = percentage + '%';
        
        // Update color based on weight class
        let color = '#10b981'; // green - ultralight
        if (this.state.baseWeight > 9072) { // > 20 lbs
            color = '#ef4444'; // red - heavy
        } else if (this.state.baseWeight > 6804) { // > 15 lbs
            color = '#f59e0b'; // yellow - moderate
        } else if (this.state.baseWeight > 4536) { // > 10 lbs
            color = '#3b82f6'; // blue - light
        }
        
        progressFill.style.background = color;
        
        // Update percentage text
        percentageDisplay.textContent = Math.round(percentage) + '%';
        
        // Add achievement animation if ultralight
        if (this.state.baseWeight < 4536 && this.state.items.length > 0) {
            this.showAchievement('Ultralight Status!');
        }
    }
    
    /**
     * Update category breakdown chart
     */
    updateCategoryBreakdown() {
        const container = document.getElementById('category-breakdown');
        if (!container) return;
        
        const total = this.state.totalWeight || 1;
        
        // Sort categories by weight
        const sortedCategories = Object.entries(this.state.categories)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 5); // Top 5 categories
        
        // Create donut chart
        let html = '<div class="donut-chart">';
        let offset = 0;
        
        sortedCategories.forEach(([category, weight]) => {
            const percentage = (weight / total) * 100;
            const color = this.getCategoryColor(category);
            
            html += `
                <div class="donut-segment" 
                     style="--percentage: ${percentage}; --offset: ${offset}; --color: ${color}">
                </div>
            `;
            
            offset += percentage;
        });
        
        html += '</div>';
        
        // Add legend
        html += '<div class="chart-legend">';
        sortedCategories.forEach(([category, weight]) => {
            const percentage = Math.round((weight / total) * 100);
            const color = this.getCategoryColor(category);
            
            html += `
                <div class="legend-item">
                    <span class="legend-color" style="background: ${color}"></span>
                    <span class="legend-label">${this.formatCategory(category)}</span>
                    <span class="legend-value">${percentage}%</span>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    /**
     * Show achievement animation
     */
    showAchievement(message) {
        // Check if already shown recently
        const lastShown = localStorage.getItem('lastAchievement');
        const now = Date.now();
        
        if (lastShown && now - parseInt(lastShown) < 60000) {
            return; // Don't show within 1 minute
        }
        
        // Show achievement toast
        showSuccess(`🏆 ${message}`, {
            duration: 8000,
            icon: '🎉'
        });
        
        // Save timestamp
        localStorage.setItem('lastAchievement', now.toString());
    }
    
    /**
     * Format weight based on units
     */
    formatWeight(grams) {
        if (this.state.units === 'imperial') {
            const oz = grams * 0.035274;
            if (oz >= 16) {
                const lbs = oz / 16;
                return `${lbs.toFixed(1)}lb`;
            }
            return `${oz.toFixed(1)}oz`;
        }
        
        if (grams >= 1000) {
            return `${(grams / 1000).toFixed(2)}kg`;
        }
        
        return `${Math.round(grams)}g`;
    }
    
    /**
     * Toggle units
     */
    toggleUnits() {
        this.state.units = this.state.units === 'metric' ? 'imperial' : 'metric';
        
        // Update button
        const toggleBtn = document.getElementById('unit-toggle');
        if (toggleBtn) {
            toggleBtn.textContent = this.state.units === 'metric' ? 'g/kg' : 'oz/lb';
        }
        
        // Re-render displays
        this.updateDisplays();
        
        // Save preference
        localStorage.setItem('weightUnits', this.state.units);
        
        showInfo(`Switched to ${this.state.units} units`);
    }
    
    /**
     * Get category color
     */
    getCategoryColor(category) {
        const colors = {
            shelter: '#ef4444',
            sleep: '#f59e0b',
            cooking: '#eab308',
            clothing: '#84cc16',
            hydration: '#06b6d4',
            navigation: '#3b82f6',
            safety: '#8b5cf6',
            hygiene: '#ec4899',
            electronics: '#6366f1',
            tools: '#10b981',
            food: '#f97316',
            misc: '#6b7280'
        };
        
        return colors[category] || colors.misc;
    }
    
    /**
     * Format category name
     */
    formatCategory(category) {
        const names = {
            shelter: 'Shelter',
            sleep: 'Sleep',
            cooking: 'Cooking',
            clothing: 'Clothing',
            hydration: 'Water',
            navigation: 'Nav',
            safety: 'Safety',
            hygiene: 'Hygiene',
            electronics: 'Tech',
            tools: 'Tools',
            food: 'Food',
            misc: 'Other'
        };
        
        return names[category] || category;
    }
    
    /**
     * Subscribe to state changes
     */
    subscribe(callback) {
        this.listeners.add(callback);
        return () => this.listeners.delete(callback);
    }
    
    /**
     * Notify listeners of state change
     */
    notifyListeners() {
        this.listeners.forEach(callback => callback(this.state));
    }
    
    /**
     * Save state to localStorage
     */
    saveState() {
        const stateToSave = {
            items: this.state.items,
            packWeight: this.state.packWeight,
            targetBaseWeight: this.state.targetBaseWeight,
            units: this.state.units
        };
        
        localStorage.setItem('weightCalculatorState', JSON.stringify(stateToSave));
    }
    
    /**
     * Load state from localStorage
     */
    loadState() {
        const saved = localStorage.getItem('weightCalculatorState');
        
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                Object.assign(this.state, parsed);
                
                // Recalculate weights
                this.calculate();
            } catch (e) {
                console.error('Failed to load saved state:', e);
            }
        }
        
        // Load unit preference
        const savedUnits = localStorage.getItem('weightUnits');
        if (savedUnits) {
            this.state.units = savedUnits;
        }
    }
    
    /**
     * Setup auto-save
     */
    setupAutoSave() {
        // Save on unload
        window.addEventListener('beforeunload', () => {
            this.saveState();
        });
        
        // Periodic save every 30 seconds if there are changes
        setInterval(() => {
            if (this.state.items.length > 0) {
                this.saveState();
            }
        }, 30000);
    }
    
    /**
     * Export pack data
     */
    exportData() {
        const data = {
            items: this.state.items,
            summary: {
                totalWeight: this.state.totalWeight,
                baseWeight: this.state.baseWeight,
                wornWeight: this.state.wornWeight,
                consumableWeight: this.state.consumableWeight
            },
            categories: this.state.categories,
            exportDate: new Date().toISOString()
        };
        
        return JSON.stringify(data, null, 2);
    }
    
    /**
     * Import pack data
     */
    importData(jsonString) {
        try {
            const data = JSON.parse(jsonString);
            
            if (data.items) {
                this.state.items = data.items;
                this.calculate();
                this.saveState();
                
                showSuccess('Pack data imported successfully');
                return true;
            }
        } catch (e) {
            showError('Failed to import pack data');
            console.error('Import error:', e);
        }
        
        return false;
    }
    
    /**
     * Clear all items
     */
    clearAll() {
        if (this.state.items.length === 0) return;
        
        const itemCount = this.state.items.length;
        const oldItems = [...this.state.items];
        
        this.state.items = [];
        this.calculate();
        this.saveState();
        
        showWarning(`Cleared ${itemCount} items`, {
            action: {
                label: 'Undo',
                callback: () => {
                    this.state.items = oldItems;
                    this.calculate();
                    this.saveState();
                    showSuccess('Items restored');
                }
            },
            duration: 8000
        });
    }
    
    /**
     * Inject progress styles
     */
    injectProgressStyles() {
        if (document.getElementById('weight-progress-styles')) return;
        
        const styles = `
            .weight-progress {
                padding: 1rem;
            }
            
            .progress-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
                color: var(--text-secondary);
            }
            
            .progress-percentage {
                font-weight: 600;
                color: var(--forest-mint);
            }
            
            .progress-bar-container {
                position: relative;
                margin-bottom: 1.5rem;
            }
            
            .progress-bar-track {
                height: 12px;
                background: rgba(0,0,0,0.1);
                border-radius: 6px;
                overflow: hidden;
            }
            
            .progress-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, var(--forest-mint), var(--forest-leaf));
                border-radius: 6px;
                transition: width 0.5s ease, background 0.3s ease;
            }
            
            .progress-markers {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                margin-top: 0.25rem;
            }
            
            .marker {
                position: absolute;
                font-size: 0.625rem;
                color: var(--text-secondary);
                transform: translateX(-50%);
            }
            
            .weight-updating {
                animation: pulse 0.5s ease;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .donut-chart {
                width: 120px;
                height: 120px;
                margin: 0 auto 1rem;
                position: relative;
            }
            
            .chart-legend {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .legend-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.75rem;
            }
            
            .legend-color {
                width: 12px;
                height: 12px;
                border-radius: 2px;
                flex-shrink: 0;
            }
            
            .legend-label {
                flex: 1;
                color: var(--text-secondary);
            }
            
            .legend-value {
                font-weight: 600;
                color: var(--text-primary);
            }
            
            .unit-toggle-btn {
                margin-left: auto;
                padding: 0.25rem 0.5rem;
                background: var(--glass-bg);
                border: 1px solid var(--glass-border);
                border-radius: 0.375rem;
                color: var(--forest-mint);
                font-size: 0.75rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .unit-toggle-btn:hover {
                background: var(--glass-bg-hover);
                transform: translateY(-1px);
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'weight-progress-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// Initialize weight calculator
window.weightCalculator = new WeightCalculator();

// Global functions
window.updatePackWeight = function() {
    window.weightCalculator.calculate();
};

window.addItemToPack = function(item) {
    window.weightCalculator.addItem(item);
};

window.removeItemFromPack = function(itemId) {
    window.weightCalculator.removeItem(itemId);
};

window.toggleItemWorn = function(itemId) {
    window.weightCalculator.toggleWorn(itemId);
};

window.toggleItemConsumable = function(itemId) {
    window.weightCalculator.toggleConsumable(itemId);
};

window.clearPack = function() {
    window.weightCalculator.clearAll();
};
