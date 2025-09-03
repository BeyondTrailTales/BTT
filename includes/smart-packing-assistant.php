<?php
/**
 * Smart Packing Assistant Component
 * Provides UI for AI-powered packing suggestions and optimization
 */
?>

<!-- Smart Packing Assistant Panel -->
<div id="smart-packing-assistant" class="smart-assistant-panel">
    <div class="assistant-header">
        <h3 class="assistant-title">
            <span class="assistant-icon">🤖</span>
            Smart Packing Assistant
        </h3>
        <button class="btn-minimize" onclick="toggleSmartAssistant()" aria-label="Toggle assistant">
            <span class="minimize-icon">_</span>
        </button>
    </div>
    
    <div class="assistant-body">
        <!-- Efficiency Score -->
        <div class="efficiency-score-card">
            <h4>Packing Efficiency Score</h4>
            <div class="score-display">
                <div class="score-circle" id="efficiency-score">
                    <span class="score-value">--</span>
                    <span class="score-grade">-</span>
                </div>
                <div class="score-details">
                    <div class="weight-info">
                        <span class="label">Total Weight:</span>
                        <span class="value" id="total-weight">0 kg</span>
                    </div>
                    <div class="distribution-info">
                        <span class="label">Distribution:</span>
                        <span class="value" id="distribution-status">Checking...</span>
                    </div>
                </div>
            </div>
            <div class="score-improvements" id="improvements-list"></div>
        </div>
        
        <!-- Missing Items Alert -->
        <div class="missing-items-card" id="missing-items-card" style="display: none;">
            <h4>
                <span class="alert-icon">⚠️</span>
                Missing Essential Items
            </h4>
            <div class="missing-items-list" id="missing-items-list"></div>
        </div>
        
        <!-- AI Suggestions -->
        <div class="suggestions-card">
            <h4>
                <span class="suggestion-icon">💡</span>
                AI Suggestions
            </h4>
            <div class="suggestions-tabs">
                <button class="tab-btn active" onclick="switchSuggestionTab('essential')">
                    Essential Items
                </button>
                <button class="tab-btn" onclick="switchSuggestionTab('weather')">
                    Weather-Based
                </button>
                <button class="tab-btn" onclick="switchSuggestionTab('tips')">
                    Packing Tips
                </button>
            </div>
            <div class="suggestions-content">
                <div id="essential-suggestions" class="suggestion-panel active">
                    <p class="loading-message">Analyzing your packing list...</p>
                </div>
                <div id="weather-suggestions" class="suggestion-panel">
                    <p class="loading-message">Check weather conditions for suggestions</p>
                </div>
                <div id="tips-suggestions" class="suggestion-panel">
                    <p class="loading-message">Loading packing tips...</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="btn btn-primary" onclick="optimizePackingList()">
                <span class="btn-icon">⚡</span>
                Optimize Weight Distribution
            </button>
            <button class="btn btn-secondary" onclick="autoCategorizeitems()">
                <span class="btn-icon">🏷️</span>
                Auto-Categorize Items
            </button>
            <button class="btn btn-secondary" onclick="checkWeatherGear()">
                <span class="btn-icon">🌦️</span>
                Check Weather Gear
            </button>
        </div>
    </div>
</div>

<!-- Optimization Modal -->
<div id="optimization-modal" class="modal" aria-hidden="true">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 class="modal-title">Weight Distribution Optimization</h2>
            <button class="modal-close" onclick="closeOptimizationModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="optimization-preview">
                <div class="current-distribution">
                    <h3>Current Distribution</h3>
                    <div id="current-sections"></div>
                </div>
                <div class="optimized-distribution">
                    <h3>Optimized Distribution</h3>
                    <div id="optimized-sections"></div>
                </div>
            </div>
            <div class="optimization-changes" id="optimization-changes"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="applyOptimization()">
                Apply Optimization
            </button>
            <button class="btn btn-secondary" onclick="closeOptimizationModal()">
                Cancel
            </button>
        </div>
    </div>
</div>

<style>
/* Smart Packing Assistant Styles */
.smart-assistant-panel {
    position: fixed;
    right: 20px;
    top: 100px;
    width: 350px;
    max-height: 80vh;
    background: var(--card-bg, #ffffff);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: all 0.3s ease;
}

.smart-assistant-panel.minimized {
    height: 50px;
    overflow: hidden;
}

.assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color, #e0e0e0);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.assistant-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1.1rem;
}

.assistant-icon {
    font-size: 1.5rem;
}

.assistant-body {
    padding: 1rem;
    max-height: calc(80vh - 60px);
    overflow-y: auto;
}

/* Efficiency Score Card */
.efficiency-score-card {
    background: var(--glass-bg, rgba(255, 255, 255, 0.9));
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.score-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1rem 0;
}

.score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: conic-gradient(
        from 0deg,
        #10b981 0%,
        #10b981 var(--score-percent, 0%),
        #e5e7eb var(--score-percent, 0%)
    );
    position: relative;
}

.score-circle::before {
    content: '';
    position: absolute;
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
}

.score-value {
    position: relative;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--score-color, #10b981);
}

.score-grade {
    position: relative;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--score-color, #10b981);
}

.score-details {
    flex: 1;
}

.weight-info, .distribution-info {
    display: flex;
    justify-content: space-between;
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.score-improvements {
    margin-top: 1rem;
}

.improvement-item {
    display: flex;
    align-items: start;
    gap: 0.5rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-left: 3px solid #ef4444;
    border-radius: 4px;
    margin: 0.5rem 0;
}

.improvement-icon {
    color: #ef4444;
}

/* Missing Items Card */
.missing-items-card {
    background: rgba(239, 68, 68, 0.05);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.missing-items-list {
    margin-top: 0.5rem;
}

.missing-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    margin: 0.25rem 0;
    background: white;
    border-radius: 4px;
}

.missing-item.essential {
    border-left: 3px solid #ef4444;
}

.missing-item.recommended {
    border-left: 3px solid #f59e0b;
}

.add-item-btn {
    padding: 0.25rem 0.5rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
}

/* Suggestions Card */
.suggestions-card {
    background: var(--glass-bg, rgba(255, 255, 255, 0.9));
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.suggestions-tabs {
    display: flex;
    gap: 0.5rem;
    margin: 1rem 0;
}

.tab-btn {
    flex: 1;
    padding: 0.5rem;
    background: transparent;
    border: 1px solid var(--border-color, #e0e0e0);
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.tab-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.suggestion-panel {
    display: none;
}

.suggestion-panel.active {
    display: block;
}

.suggestion-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    margin: 0.5rem 0;
    background: white;
    border-radius: 6px;
    border: 1px solid var(--border-color, #e0e0e0);
    transition: all 0.2s;
}

.suggestion-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.suggestion-info {
    flex: 1;
}

.suggestion-name {
    font-weight: 600;
    color: #1f2937;
}

.suggestion-reason {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
}

.quick-actions .btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
}

/* Optimization Modal */
.optimization-preview {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin: 2rem 0;
}

.current-distribution, .optimized-distribution {
    padding: 1rem;
    background: var(--glass-bg, rgba(255, 255, 255, 0.9));
    border-radius: 8px;
}

.section-distribution {
    margin: 1rem 0;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    border: 1px solid var(--border-color, #e0e0e0);
}

.section-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.section-weight {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #6b7280;
}

.section-items {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #9ca3af;
}

.optimization-changes {
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 8px;
}

.change-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    margin: 0.25rem 0;
}

.change-arrow {
    color: #3b82f6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .smart-assistant-panel {
        width: calc(100% - 40px);
        right: 20px;
        left: 20px;
    }
    
    .optimization-preview {
        grid-template-columns: 1fr;
    }
}

/* Animations */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.smart-assistant-panel {
    animation: slideIn 0.3s ease;
}

.suggestion-item.new {
    animation: pulse 0.5s ease;
}
</style>

<script>
// Initialize Smart Packing Assistant
let currentOptimization = null;
let packingItems = [];

// Load smart packing script if not already loaded
if (typeof SmartPacking === 'undefined') {
    const script = document.createElement('script');
    script.src = '/BTT/public/js/smart-packing.js';
    document.head.appendChild(script);
}

// Toggle assistant panel
function toggleSmartAssistant() {
    const panel = document.getElementById('smart-packing-assistant');
    panel.classList.toggle('minimized');
}

// Switch suggestion tabs
function switchSuggestionTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.suggestions-tabs .tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update panels
    document.querySelectorAll('.suggestion-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    document.getElementById(`${tab}-suggestions`).classList.add('active');
}

// Analyze packing efficiency
async function analyzePackingEfficiency(items) {
    if (typeof SmartPacking === 'undefined') {
        console.error('SmartPacking module not loaded');
        return;
    }
    
    packingItems = items || [];
    const analysis = SmartPacking.analyzePackingEfficiency(packingItems);
    
    // Update efficiency score display
    const scoreElement = document.querySelector('.score-value');
    const gradeElement = document.querySelector('.score-grade');
    const weightElement = document.getElementById('total-weight');
    const distributionElement = document.getElementById('distribution-status');
    const improvementsElement = document.getElementById('improvements-list');
    
    // Update score circle
    scoreElement.textContent = analysis.score;
    gradeElement.textContent = analysis.grade;
    
    // Set color based on grade
    const scoreCircle = document.querySelector('.score-circle');
    let scoreColor = '#10b981'; // Green for A
    if (analysis.grade === 'B') scoreColor = '#3b82f6'; // Blue
    if (analysis.grade === 'C') scoreColor = '#f59e0b'; // Yellow
    if (analysis.grade === 'D' || analysis.grade === 'F') scoreColor = '#ef4444'; // Red
    
    scoreCircle.style.setProperty('--score-percent', `${analysis.score}%`);
    scoreCircle.style.setProperty('--score-color', scoreColor);
    
    // Update weight and distribution
    weightElement.textContent = `${analysis.totalWeight.toFixed(1)} kg`;
    distributionElement.textContent = analysis.score >= 80 ? 'Good' : 'Needs improvement';
    
    // Display improvements
    if (analysis.improvements.length > 0) {
        improvementsElement.innerHTML = analysis.improvements.map(imp => `
            <div class="improvement-item">
                <span class="improvement-icon">⚠️</span>
                <div>
                    <div class="improvement-message">${BTTUtils.escapeHtml(imp.message)}</div>
                    <div class="improvement-suggestion">${BTTUtils.escapeHtml(imp.suggestion)}</div>
                </div>
            </div>
        `).join('');
    } else {
        improvementsElement.innerHTML = '<p class="success-message">✅ Your packing is well optimized!</p>';
    }
    
    // Check for missing items
    await checkMissingItems();
    
    // Generate suggestions
    await generateSuggestions();
}

// Check for missing essential items
async function checkMissingItems() {
    if (typeof SmartPacking === 'undefined') return;
    
    const tripType = window.currentTripType || 'day-hike';
    const weather = window.currentWeather || null;
    
    const missing = SmartPacking.detectMissingItems(packingItems, tripType, weather);
    
    const missingCard = document.getElementById('missing-items-card');
    const missingList = document.getElementById('missing-items-list');
    
    if (missing.length > 0) {
        missingCard.style.display = 'block';
        missingList.innerHTML = missing.map(item => `
            <div class="missing-item ${item.importance}">
                <div>
                    <div class="item-name">${BTTUtils.escapeHtml(item.name)}</div>
                    <div class="item-weight">${item.weight} kg - ${item.importance}</div>
                </div>
                <button class="add-item-btn" onclick="addSuggestedItem('${BTTUtils.escapeHtml(item.name)}', ${item.weight}, '${item.category}')">
                    Add
                </button>
            </div>
        `).join('');
    } else {
        missingCard.style.display = 'none';
    }
}

// Generate AI suggestions
async function generateSuggestions() {
    if (typeof SmartPacking === 'undefined') return;
    
    const tripDetails = {
        type: window.currentTripType || 'day-hike',
        weather: window.currentWeather || null,
        items: packingItems
    };
    
    const suggestions = await SmartPacking.generatePackingSuggestions(tripDetails);
    
    // Display essential suggestions
    const essentialPanel = document.getElementById('essential-suggestions');
    if (suggestions.essential.length > 0) {
        essentialPanel.innerHTML = suggestions.essential.map(item => `
            <div class="suggestion-item">
                <div class="suggestion-info">
                    <div class="suggestion-name">${BTTUtils.escapeHtml(item.name)}</div>
                    <div class="suggestion-reason">${BTTUtils.escapeHtml(item.reason)}</div>
                </div>
                <button class="add-item-btn" onclick="addSuggestedItem('${BTTUtils.escapeHtml(item.name)}', ${item.weight}, '${item.category}')">
                    Add
                </button>
            </div>
        `).join('');
    } else {
        essentialPanel.innerHTML = '<p class="success-message">✅ All essential items are packed!</p>';
    }
    
    // Display weather suggestions
    const weatherPanel = document.getElementById('weather-suggestions');
    if (suggestions.weather.length > 0) {
        weatherPanel.innerHTML = suggestions.weather.map(item => `
            <div class="suggestion-item">
                <div class="suggestion-info">
                    <div class="suggestion-name">${BTTUtils.escapeHtml(item.name)}</div>
                    <div class="suggestion-reason">${BTTUtils.escapeHtml(item.reason)}</div>
                </div>
                <button class="add-item-btn" onclick="addSuggestedItem('${BTTUtils.escapeHtml(item.name)}', ${item.weight}, '${item.category}')">
                    Add
                </button>
            </div>
        `).join('');
    } else {
        weatherPanel.innerHTML = '<p>Set weather conditions to get weather-specific suggestions.</p>';
    }
    
    // Display packing tips
    const tipsPanel = document.getElementById('tips-suggestions');
    const tips = SmartPacking.getPackingTips(tripDetails.type);
    tipsPanel.innerHTML = '<ul class="packing-tips">' + 
        tips.map(tip => `<li>${BTTUtils.escapeHtml(tip)}</li>`).join('') +
        '</ul>';
}

// Optimize packing list
function optimizePackingList() {
    if (typeof SmartPacking === 'undefined') {
        alert('Smart Packing module not loaded');
        return;
    }
    
    currentOptimization = SmartPacking.optimizeWeightDistribution(packingItems);
    
    // Show optimization modal
    const modal = document.getElementById('optimization-modal');
    BTTUtils.showModal('optimization-modal');
    
    // Display current distribution
    const currentSections = document.getElementById('current-sections');
    currentSections.innerHTML = generateSectionDisplay(groupItemsBySection(packingItems));
    
    // Display optimized distribution
    const optimizedSections = document.getElementById('optimized-sections');
    optimizedSections.innerHTML = generateSectionDisplay(currentOptimization);
    
    // Show changes
    const changes = document.getElementById('optimization-changes');
    const relocatedItems = [];
    
    Object.entries(currentOptimization).forEach(([section, data]) => {
        data.items.forEach(item => {
            if (item.relocated) {
                relocatedItems.push({
                    name: item.name,
                    from: item.originalSection,
                    to: section
                });
            }
        });
    });
    
    if (relocatedItems.length > 0) {
        changes.innerHTML = '<h4>Recommended Changes:</h4>' +
            relocatedItems.map(change => `
                <div class="change-item">
                    <span>${BTTUtils.escapeHtml(change.name)}</span>
                    <span class="change-arrow">→</span>
                    <span>Move from ${change.from} to ${change.to}</span>
                </div>
            `).join('');
    } else {
        changes.innerHTML = '<p class="success-message">✅ Items are already optimally distributed!</p>';
    }
}

// Helper function to group items by section
function groupItemsBySection(items) {
    const sections = {
        lid: { items: [], currentWeight: 0 },
        main: { items: [], currentWeight: 0 },
        front: { items: [], currentWeight: 0 },
        side: { items: [], currentWeight: 0 },
        bottom: { items: [], currentWeight: 0 }
    };
    
    items.forEach(item => {
        const section = item.section || SmartPacking.getOptimalSection(item);
        if (sections[section]) {
            sections[section].items.push(item);
            sections[section].currentWeight += item.weight || 0;
        }
    });
    
    return sections;
}

// Generate section display HTML
function generateSectionDisplay(sections) {
    const sectionNames = {
        lid: 'Top Lid',
        main: 'Main Compartment',
        front: 'Front Pocket',
        side: 'Side Pockets',
        bottom: 'Bottom Section'
    };
    
    return Object.entries(sections).map(([key, data]) => `
        <div class="section-distribution">
            <div class="section-name">${sectionNames[key]}</div>
            <div class="section-weight">
                <span>Weight: ${data.currentWeight.toFixed(1)} kg</span>
                <span>${data.items.length} items</span>
            </div>
            <div class="section-items">
                ${data.items.slice(0, 3).map(item => item.name).join(', ')}
                ${data.items.length > 3 ? ` +${data.items.length - 3} more` : ''}
            </div>
        </div>
    `).join('');
}

// Apply optimization
function applyOptimization() {
    if (!currentOptimization) return;
    
    // Update items with new sections
    Object.entries(currentOptimization).forEach(([section, data]) => {
        data.items.forEach(item => {
            const index = packingItems.findIndex(i => i.name === item.name);
            if (index !== -1) {
                packingItems[index].section = section;
            }
        });
    });
    
    // Trigger update in main application
    if (window.updatePackingList) {
        window.updatePackingList(packingItems);
    }
    
    // Close modal and show success
    closeOptimizationModal();
    BTTUtils.showToast('Weight distribution optimized successfully!', 'success');
    
    // Re-analyze with new distribution
    analyzePackingEfficiency(packingItems);
}

// Close optimization modal
function closeOptimizationModal() {
    BTTUtils.hideModal('optimization-modal');
}

// Auto-categorize items
function autoCategorizeitems() {
    if (typeof SmartPacking === 'undefined') {
        alert('Smart Packing module not loaded');
        return;
    }
    
    let categorized = 0;
    packingItems.forEach(item => {
        if (!item.category || item.category === 'other') {
            const category = SmartPacking.categorizeItem(item.name);
            item.category = category;
            item.section = SmartPacking.getOptimalSection(item);
            categorized++;
        }
    });
    
    if (categorized > 0) {
        // Update the packing list
        if (window.updatePackingList) {
            window.updatePackingList(packingItems);
        }
        
        BTTUtils.showToast(`Categorized ${categorized} items successfully!`, 'success');
        
        // Re-analyze
        analyzePackingEfficiency(packingItems);
    } else {
        BTTUtils.showToast('All items are already categorized!', 'info');
    }
}

// Check weather gear
function checkWeatherGear() {
    const weather = prompt('What weather conditions are expected? (cold/rain/hot)');
    if (weather) {
        window.currentWeather = weather.toLowerCase();
        checkMissingItems();
        generateSuggestions();
        BTTUtils.showToast(`Weather gear checked for ${weather} conditions`, 'info');
    }
}

// Add suggested item to packing list
function addSuggestedItem(name, weight, category) {
    const newItem = {
        name: name,
        weight: weight,
        category: category,
        section: SmartPacking.getOptimalSection({ name, weight, category }),
        quantity: 1,
        packed: false
    };
    
    packingItems.push(newItem);
    
    // Update the main packing list
    if (window.updatePackingList) {
        window.updatePackingList(packingItems);
    }
    
    BTTUtils.showToast(`Added ${name} to your packing list`, 'success');
    
    // Re-analyze
    analyzePackingEfficiency(packingItems);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Wait for SmartPacking to load
    setTimeout(() => {
        if (window.packingItems) {
            analyzePackingEfficiency(window.packingItems);
        }
    }, 1000);
});
</script>
