/**
 * Backpack Builder Debug Script
 * Run this in the browser console to check what's working
 */

console.log('🎒 Backpack Builder Debug Starting...');

// Check if we're on the right page
if (document.body.dataset.page !== 'backpacks') {
    console.error('❌ Not on backpacks page. Current page:', document.body.dataset.page);
} else {
    console.log('✅ On backpacks page');
}

// Check jQuery
if (typeof jQuery !== 'undefined') {
    console.log('✅ jQuery loaded:', jQuery.fn.jquery);
} else {
    console.error('❌ jQuery not loaded');
}

// Check core components
const components = {
    'PackBuilder': window.PackBuilder,
    'PackBuilderCRUD': window.PackBuilderCRUD,
    'PackBuilderDragDrop': window.PackBuilderDragDrop,
    'SectionManager': window.SectionManager,
    'BttApi': window.BttApi
};

console.log('📦 Components Status:');
Object.entries(components).forEach(([name, component]) => {
    if (component) {
        console.log(`✅ ${name}:`, component);
    } else {
        console.error(`❌ ${name}: Missing`);
    }
});

// Check DOM elements
const elements = {
    'Gear Library': '#gear-library',
    'Pack Sections': '#pack-sections',
    'Pack Builder Container': '.pack-builder-container',
    'Gear Search': '#gear-search',
    'Add Section Button': '#add-section',
    'Save Pack Button': '#btn-save-pack-top'
};

console.log('🎯 DOM Elements Status:');
Object.entries(elements).forEach(([name, selector]) => {
    const element = document.querySelector(selector);
    if (element) {
        console.log(`✅ ${name}:`, element);
    } else {
        console.error(`❌ ${name}: Not found (${selector})`);
    }
});

// Check current view
const activeView = document.querySelector('.pack-view.active');
if (activeView) {
    console.log('👁️ Active View:', activeView.dataset.view);
} else {
    console.error('❌ No active view found');
}

// Test functions
console.log('🧪 Available Functions:');
if (window.PackBuilder) {
    const functions = ['init', 'loadGearLibrary', 'loadPacks', 'renderGearLibrary', 'addGearToPack'];
    functions.forEach(fn => {
        if (typeof window.PackBuilder[fn] === 'function') {
            console.log(`✅ PackBuilder.${fn}`);
        } else {
            console.error(`❌ PackBuilder.${fn}: Missing`);
        }
    });
}

if (window.PackBuilderCRUD) {
    const functions = ['init', 'savePack', 'loadPackForEdit', 'deletePack'];
    functions.forEach(fn => {
        if (typeof window.PackBuilderCRUD[fn] === 'function') {
            console.log(`✅ PackBuilderCRUD.${fn}`);
        } else {
            console.error(`❌ PackBuilderCRUD.${fn}: Missing`);
        }
    });
}

// Manual initialization test
console.log('🚀 Manual Initialization Test:');
console.log('Run these commands to manually initialize:');
console.log('window.PackBuilder.init()');
console.log('window.PackBuilder.loadGearLibrary()');
console.log('window.PackBuilderCRUD.init()');

console.log('🎒 Debug Complete! Check above for any ❌ errors');

// Auto-fix attempt
setTimeout(() => {
    console.log('🔧 Attempting auto-fix...');
    
    if (window.PackBuilder && typeof window.PackBuilder.init === 'function') {
        try {
            window.PackBuilder.init();
            console.log('✅ PackBuilder initialized');
        } catch (e) {
            console.error('❌ PackBuilder init failed:', e);
        }
    }
    
    if (window.PackBuilderCRUD && typeof window.PackBuilderCRUD.init === 'function') {
        try {
            window.PackBuilderCRUD.init();
            console.log('✅ PackBuilderCRUD initialized');
        } catch (e) {
            console.error('❌ PackBuilderCRUD init failed:', e);
        }
    }
    
    if (window.PackBuilder && typeof window.PackBuilder.loadGearLibrary === 'function') {
        try {
            window.PackBuilder.loadGearLibrary();
            console.log('✅ Gear library loaded');
        } catch (e) {
            console.error('❌ Gear library load failed:', e);
        }
    }
}, 2000);