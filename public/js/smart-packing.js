/**
 * Smart Packing Assistant Module
 * Provides AI-powered packing suggestions, automatic categorization,
 * weight optimization, and missing items detection
 */

const SmartPacking = (() => {
    
    // Gear categories and their optimal backpack sections
    const ITEM_CATEGORIES = {
        // Top Lid - Quick access items
        'hygiene': { section: 'lid', priority: 1 },
        'electronics': { section: 'lid', priority: 2 },
        'tools': { section: 'lid', priority: 2 },
        'first-aid': { section: 'lid', priority: 1 },
        'snacks': { section: 'lid', priority: 1 },
        
        // Main Body - Core gear
        'clothing': { section: 'main', priority: 3 },
        'sleep-system': { section: 'main', priority: 4 },
        'food': { section: 'main', priority: 3 },
        'insulation': { section: 'main', priority: 3 },
        
        // Front Pocket - Navigation/safety
        'navigation': { section: 'front', priority: 1 },
        'safety': { section: 'front', priority: 1 },
        'documents': { section: 'front', priority: 2 },
        'emergency': { section: 'front', priority: 1 },
        
        // Side Pockets - Water/cooking
        'water': { section: 'side', priority: 1 },
        'cooking': { section: 'side', priority: 2 },
        'fuel': { section: 'side', priority: 2 },
        
        // Bottom - Heavy items/shelter
        'shelter': { section: 'bottom', priority: 5 },
        'tent': { section: 'bottom', priority: 5 },
        'sleeping-bag': { section: 'bottom', priority: 4 },
        'bear-canister': { section: 'bottom', priority: 5 }
    };
    
    // Essential items database by trip type
    const ESSENTIAL_ITEMS = {
        'day-hike': [
            { name: 'Water bottle', category: 'water', weight: 0.5 },
            { name: 'First aid kit', category: 'first-aid', weight: 0.2 },
            { name: 'Map and compass', category: 'navigation', weight: 0.1 },
            { name: 'Sun protection', category: 'hygiene', weight: 0.1 },
            { name: 'Snacks', category: 'snacks', weight: 0.3 },
            { name: 'Rain jacket', category: 'clothing', weight: 0.3 },
            { name: 'Headlamp', category: 'electronics', weight: 0.1 }
        ],
        'weekend-backpacking': [
            { name: 'Tent', category: 'shelter', weight: 2.0 },
            { name: 'Sleeping bag', category: 'sleep-system', weight: 1.5 },
            { name: 'Sleeping pad', category: 'sleep-system', weight: 0.5 },
            { name: 'Cooking stove', category: 'cooking', weight: 0.3 },
            { name: 'Water filter', category: 'water', weight: 0.2 },
            { name: 'Food (2 days)', category: 'food', weight: 1.5 },
            { name: 'Bear canister', category: 'bear-canister', weight: 1.0 },
            { name: 'First aid kit', category: 'first-aid', weight: 0.3 },
            { name: 'Navigation tools', category: 'navigation', weight: 0.2 },
            { name: 'Extra clothing', category: 'clothing', weight: 1.0 }
        ],
        'thru-hike': [
            { name: 'Ultralight tent', category: 'shelter', weight: 1.0 },
            { name: 'Down sleeping bag', category: 'sleep-system', weight: 0.8 },
            { name: 'Ultralight pad', category: 'sleep-system', weight: 0.3 },
            { name: 'Canister stove', category: 'cooking', weight: 0.2 },
            { name: 'Water filter', category: 'water', weight: 0.15 },
            { name: 'Food resupply', category: 'food', weight: 2.0 },
            { name: 'Complete first aid', category: 'first-aid', weight: 0.4 },
            { name: 'GPS device', category: 'electronics', weight: 0.2 },
            { name: 'Power bank', category: 'electronics', weight: 0.3 },
            { name: 'Repair kit', category: 'tools', weight: 0.2 }
        ]
    };
    
    // Weather-based gear suggestions
    const WEATHER_GEAR = {
        'cold': [
            { name: 'Insulated jacket', category: 'insulation', weight: 0.5 },
            { name: 'Thermal layers', category: 'clothing', weight: 0.3 },
            { name: 'Warm gloves', category: 'clothing', weight: 0.1 },
            { name: 'Beanie', category: 'clothing', weight: 0.05 }
        ],
        'rain': [
            { name: 'Rain jacket', category: 'clothing', weight: 0.3 },
            { name: 'Rain pants', category: 'clothing', weight: 0.25 },
            { name: 'Pack cover', category: 'other', weight: 0.1 },
            { name: 'Dry bags', category: 'other', weight: 0.05 }
        ],
        'hot': [
            { name: 'Sun hat', category: 'clothing', weight: 0.05 },
            { name: 'Sunscreen', category: 'hygiene', weight: 0.1 },
            { name: 'Extra water', category: 'water', weight: 1.0 },
            { name: 'Electrolytes', category: 'food', weight: 0.05 }
        ]
    };
    
    /**
     * Automatically categorize an item based on its name
     */
    function categorizeItem(itemName) {
        const lowerName = itemName.toLowerCase();
        
        // Check for specific keywords
        const keywords = {
            'tent': 'shelter',
            'sleeping bag': 'sleep-system',
            'pad': 'sleep-system',
            'stove': 'cooking',
            'pot': 'cooking',
            'water': 'water',
            'filter': 'water',
            'bottle': 'water',
            'first aid': 'first-aid',
            'bandage': 'first-aid',
            'medicine': 'first-aid',
            'map': 'navigation',
            'compass': 'navigation',
            'gps': 'electronics',
            'phone': 'electronics',
            'battery': 'electronics',
            'charger': 'electronics',
            'headlamp': 'electronics',
            'flashlight': 'electronics',
            'knife': 'tools',
            'multi-tool': 'tools',
            'repair': 'tools',
            'jacket': 'clothing',
            'shirt': 'clothing',
            'pants': 'clothing',
            'socks': 'clothing',
            'underwear': 'clothing',
            'food': 'food',
            'meal': 'food',
            'snack': 'snacks',
            'bar': 'snacks',
            'toothbrush': 'hygiene',
            'soap': 'hygiene',
            'toilet': 'hygiene',
            'sunscreen': 'hygiene'
        };
        
        for (const [keyword, category] of Object.entries(keywords)) {
            if (lowerName.includes(keyword)) {
                return category;
            }
        }
        
        return 'other'; // Default category
    }
    
    /**
     * Get the optimal section for an item
     */
    function getOptimalSection(item) {
        const category = item.category || categorizeItem(item.name);
        const categoryInfo = ITEM_CATEGORIES[category];
        
        if (categoryInfo) {
            return categoryInfo.section;
        }
        
        // Default placement based on weight
        if (item.weight > 2.0) return 'bottom';
        if (item.weight < 0.2) return 'lid';
        return 'main';
    }
    
    /**
     * Generate AI-powered packing suggestions based on trip details
     */
    async function generatePackingSuggestions(tripDetails) {
        const suggestions = {
            essential: [],
            weather: [],
            optional: [],
            optimizations: []
        };
        
        // Get essential items for trip type
        const tripType = tripDetails.type || 'day-hike';
        const essentials = ESSENTIAL_ITEMS[tripType] || ESSENTIAL_ITEMS['day-hike'];
        
        // Check which essentials are missing
        const currentItems = tripDetails.items || [];
        const currentItemNames = currentItems.map(item => item.name.toLowerCase());
        
        essentials.forEach(essential => {
            const found = currentItemNames.some(name => 
                name.includes(essential.name.toLowerCase()) ||
                essential.name.toLowerCase().includes(name)
            );
            
            if (!found) {
                suggestions.essential.push({
                    ...essential,
                    reason: `Essential for ${tripType.replace('-', ' ')}`,
                    priority: 'high'
                });
            }
        });
        
        // Add weather-based suggestions
        if (tripDetails.weather) {
            const weatherGear = WEATHER_GEAR[tripDetails.weather] || [];
            weatherGear.forEach(gear => {
                const found = currentItemNames.some(name => 
                    name.includes(gear.name.toLowerCase())
                );
                
                if (!found) {
                    suggestions.weather.push({
                        ...gear,
                        reason: `Recommended for ${tripDetails.weather} weather`,
                        priority: 'medium'
                    });
                }
            });
        }
        
        // Add optimization suggestions
        const analysis = analyzePackingEfficiency(currentItems);
        if (analysis.score < 80) {
            suggestions.optimizations = analysis.improvements;
        }
        
        return suggestions;
    }
    
    /**
     * Optimize weight distribution across backpack sections
     */
    function optimizeWeightDistribution(items) {
        const sections = {
            'lid': { items: [], maxWeight: 1.0, currentWeight: 0 },
            'main': { items: [], maxWeight: 6.0, currentWeight: 0 },
            'front': { items: [], maxWeight: 1.5, currentWeight: 0 },
            'side': { items: [], maxWeight: 2.0, currentWeight: 0 },
            'bottom': { items: [], maxWeight: 4.0, currentWeight: 0 }
        };
        
        // Categorize and sort items by priority and weight
        const categorizedItems = items.map(item => ({
            ...item,
            category: item.category || categorizeItem(item.name),
            optimalSection: getOptimalSection(item)
        }));
        
        // Sort items: heavy items first for bottom, light items for top
        categorizedItems.sort((a, b) => {
            const catA = ITEM_CATEGORIES[a.category] || { priority: 3 };
            const catB = ITEM_CATEGORIES[b.category] || { priority: 3 };
            
            // First by priority
            if (catA.priority !== catB.priority) {
                return catA.priority - catB.priority;
            }
            
            // Then by weight (heavy first for bottom section)
            if (a.optimalSection === 'bottom' && b.optimalSection === 'bottom') {
                return b.weight - a.weight;
            }
            
            return a.weight - b.weight;
        });
        
        // Distribute items to sections
        categorizedItems.forEach(item => {
            const targetSection = sections[item.optimalSection];
            
            // Check if section has capacity
            if (targetSection.currentWeight + item.weight <= targetSection.maxWeight) {
                targetSection.items.push(item);
                targetSection.currentWeight += item.weight;
            } else {
                // Find alternative section with capacity
                const alternativeSections = ['main', 'front', 'side', 'lid', 'bottom']
                    .filter(s => s !== item.optimalSection);
                
                for (const altSection of alternativeSections) {
                    if (sections[altSection].currentWeight + item.weight <= sections[altSection].maxWeight) {
                        sections[altSection].items.push({
                            ...item,
                            relocated: true,
                            originalSection: item.optimalSection
                        });
                        sections[altSection].currentWeight += item.weight;
                        break;
                    }
                }
            }
        });
        
        return sections;
    }
    
    /**
     * Detect missing essential items
     */
    function detectMissingItems(currentItems, tripType, weather) {
        const missing = [];
        const essentials = ESSENTIAL_ITEMS[tripType] || ESSENTIAL_ITEMS['day-hike'];
        const weatherGear = WEATHER_GEAR[weather] || [];
        
        const allRequired = [...essentials, ...weatherGear];
        const currentItemNames = currentItems.map(item => item.name.toLowerCase());
        
        allRequired.forEach(required => {
            const found = currentItemNames.some(name => {
                const requiredWords = required.name.toLowerCase().split(' ');
                return requiredWords.some(word => name.includes(word));
            });
            
            if (!found) {
                missing.push({
                    ...required,
                    importance: essentials.includes(required) ? 'essential' : 'recommended'
                });
            }
        });
        
        return missing;
    }
    
    /**
     * Calculate packing efficiency score
     */
    function analyzePackingEfficiency(items) {
        let score = 100;
        const improvements = [];
        
        // Check total weight
        const totalWeight = items.reduce((sum, item) => sum + (item.weight || 0), 0);
        if (totalWeight > 15) {
            score -= 20;
            improvements.push({
                type: 'weight',
                message: 'Total pack weight exceeds recommended 15kg',
                suggestion: 'Consider lighter alternatives for heavy items'
            });
        }
        
        // Check weight distribution
        const optimized = optimizeWeightDistribution(items);
        const bottomWeight = optimized.bottom.currentWeight;
        const topWeight = optimized.lid.currentWeight + optimized.front.currentWeight;
        
        if (bottomWeight < totalWeight * 0.3) {
            score -= 15;
            improvements.push({
                type: 'distribution',
                message: 'Heavy items should be at the bottom',
                suggestion: 'Move heavier items to the bottom section'
            });
        }
        
        if (topWeight > totalWeight * 0.2) {
            score -= 10;
            improvements.push({
                type: 'distribution',
                message: 'Top sections are overloaded',
                suggestion: 'Move non-essential items from top to main compartment'
            });
        }
        
        // Check for duplicate items
        const itemCounts = {};
        items.forEach(item => {
            const key = item.name.toLowerCase();
            itemCounts[key] = (itemCounts[key] || 0) + 1;
        });
        
        const duplicates = Object.entries(itemCounts)
            .filter(([name, count]) => count > 1 && !name.includes('sock') && !name.includes('shirt'));
        
        if (duplicates.length > 0) {
            score -= 5 * duplicates.length;
            improvements.push({
                type: 'duplicates',
                message: `Found ${duplicates.length} duplicate items`,
                suggestion: 'Review and remove unnecessary duplicates'
            });
        }
        
        // Check organization
        const uncategorized = items.filter(item => !item.category || item.category === 'other');
        if (uncategorized.length > items.length * 0.2) {
            score -= 10;
            improvements.push({
                type: 'organization',
                message: 'Many items are uncategorized',
                suggestion: 'Categorize items for better organization'
            });
        }
        
        // Calculate final grade
        let grade = 'A';
        if (score < 90) grade = 'B';
        if (score < 80) grade = 'C';
        if (score < 70) grade = 'D';
        if (score < 60) grade = 'F';
        
        return {
            score: Math.max(0, score),
            grade,
            improvements,
            totalWeight,
            optimizedDistribution: optimized
        };
    }
    
    /**
     * Generate packing tips based on trip type
     */
    function getPackingTips(tripType) {
        const tips = {
            'day-hike': [
                'Keep total weight under 10% of body weight',
                'Pack water and snacks in easily accessible pockets',
                'Bring layers for changing weather conditions',
                'Don\'t forget sun protection and first aid'
            ],
            'weekend-backpacking': [
                'Aim for base weight under 10kg',
                'Place sleeping bag at bottom of pack',
                'Keep camp shoes accessible for river crossings',
                'Pack food in bear-proof containers',
                'Organize by day to minimize unpacking'
            ],
            'thru-hike': [
                'Ultra-light is key - every gram counts',
                'Plan resupply points to minimize carried food',
                'Invest in multi-use items',
                'Keep electronics in waterproof bags',
                'Mail seasonal gear ahead to avoid carrying'
            ]
        };
        
        return tips[tripType] || tips['day-hike'];
    }
    
    // Public API
    return {
        categorizeItem,
        getOptimalSection,
        generatePackingSuggestions,
        optimizeWeightDistribution,
        detectMissingItems,
        analyzePackingEfficiency,
        getPackingTips,
        ITEM_CATEGORIES,
        ESSENTIAL_ITEMS,
        WEATHER_GEAR
    };
})();

// Make it globally available
window.SmartPacking = SmartPacking;
