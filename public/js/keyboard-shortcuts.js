/**
 * Keyboard Shortcuts System
 * Global keyboard navigation and shortcuts for improved UX
 */

class KeyboardShortcuts {
    constructor() {
        this.shortcuts = new Map();
        this.activeContext = 'global';
        this.enabled = true;
        this.modifierKeys = {
            ctrl: false,
            alt: false,
            shift: false,
            meta: false
        };
        
        // Default shortcuts configuration
        this.defaultShortcuts = {
            global: {
                '?': { handler: () => this.showHelp(), description: 'Show keyboard shortcuts help' },
                '/': { handler: () => this.focusSearch(), description: 'Focus search input' },
                'Escape': { handler: () => this.escape(), description: 'Close dialogs/Cancel action' },
                'g h': { handler: () => this.navigate('/'), description: 'Go to Home' },
                'g t': { handler: () => this.navigate('/trips'), description: 'Go to Trips' },
                'g p': { handler: () => this.navigate('/backpacks'), description: 'Go to Packs' },
                'g g': { handler: () => this.navigate('/gear'), description: 'Go to Gear Library' },
                'n': { handler: () => this.createNew(), description: 'Create new (context-aware)' },
                'ctrl+s': { handler: (e) => this.save(e), description: 'Save current item' },
                'ctrl+z': { handler: () => this.undo(), description: 'Undo last action' },
                'ctrl+shift+z': { handler: () => this.redo(), description: 'Redo last action' },
                'ctrl+k': { handler: () => this.showCommandPalette(), description: 'Show command palette' },
                'Tab': { handler: (e) => this.handleTab(e), description: 'Navigate forward', preventInInput: false },
                'Shift+Tab': { handler: (e) => this.handleShiftTab(e), description: 'Navigate backward', preventInInput: false },
                'Enter': { handler: (e) => this.handleEnter(e), description: 'Select/Activate', preventInInput: false },
                'ArrowUp': { handler: (e) => this.handleArrowUp(e), description: 'Move up' },
                'ArrowDown': { handler: (e) => this.handleArrowDown(e), description: 'Move down' },
                'ArrowLeft': { handler: (e) => this.handleArrowLeft(e), description: 'Move left/Previous' },
                'ArrowRight': { handler: (e) => this.handleArrowRight(e), description: 'Move right/Next' }
            },
            packBuilder: {
                'a': { handler: () => this.addGearItem(), description: 'Add gear item' },
                'd': { handler: () => this.deleteSelected(), description: 'Delete selected item' },
                'e': { handler: () => this.editSelected(), description: 'Edit selected item' },
                'f': { handler: () => this.filterGear(), description: 'Filter gear items' },
                'w': { handler: () => this.toggleWeightView(), description: 'Toggle weight view' },
                'ctrl+c': { handler: () => this.copyItem(), description: 'Copy item' },
                'ctrl+v': { handler: () => this.pasteItem(), description: 'Paste item' },
                'ctrl+d': { handler: () => this.duplicateItem(), description: 'Duplicate item' }
            },
            trips: {
                't': { handler: () => this.createTrip(), description: 'Create new trip' },
                'e': { handler: () => this.editTrip(), description: 'Edit selected trip' },
                'v': { handler: () => this.viewTrip(), description: 'View trip details' },
                's': { handler: () => this.toggleSort(), description: 'Change sort order' },
                'f': { handler: () => this.toggleFavorite(), description: 'Toggle favorite' }
            },
            gear: {
                'c': { handler: () => this.toggleCategories(), description: 'Toggle category filter' },
                'v': { handler: () => this.toggleView(), description: 'Toggle view mode' },
                'h': { handler: () => this.toggleHidden(), description: 'Show/hide hidden items' },
                'Space': { handler: (e) => this.quickAdd(e), description: 'Quick add to pack' }
            }
        };
        
        this.init();
    }
    
    /**
     * Initialize the keyboard shortcuts system
     */
    init() {
        console.log('Initializing Keyboard Shortcuts System...');
        
        // Load saved preferences
        this.loadPreferences();
        
        // Register default shortcuts
        this.registerDefaults();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Create help modal
        this.createHelpModal();
        
        // Create command palette
        this.createCommandPalette();
        
        // Detect current context
        this.detectContext();
        
        console.log('✅ Keyboard Shortcuts initialized');
    }
    
    /**
     * Register default shortcuts
     */
    registerDefaults() {
        Object.entries(this.defaultShortcuts).forEach(([context, shortcuts]) => {
            Object.entries(shortcuts).forEach(([key, config]) => {
                this.register(key, config.handler, context, config);
            });
        });
    }
    
    /**
     * Register a keyboard shortcut
     */
    register(shortcut, handler, context = 'global', options = {}) {
        const normalizedShortcut = this.normalizeShortcut(shortcut);
        
        if (!this.shortcuts.has(context)) {
            this.shortcuts.set(context, new Map());
        }
        
        this.shortcuts.get(context).set(normalizedShortcut, {
            handler,
            description: options.description || '',
            preventInInput: options.preventInInput !== false,
            preventDefault: options.preventDefault !== false,
            ...options
        });
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Keydown handler
        document.addEventListener('keydown', (e) => this.handleKeyDown(e), true);
        
        // Keyup handler for modifier keys
        document.addEventListener('keyup', (e) => this.handleKeyUp(e), true);
        
        // Context change on navigation
        window.addEventListener('popstate', () => this.detectContext());
        
        // Visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Reset modifier keys when tab loses focus
                this.resetModifiers();
            }
        });
    }
    
    /**
     * Handle keydown event
     */
    handleKeyDown(event) {
        if (!this.enabled) return;
        
        // Update modifier keys
        this.updateModifiers(event);
        
        // Build shortcut string
        const shortcut = this.buildShortcutString(event);
        
        // Check if we should handle this event
        if (!this.shouldHandle(event)) return;
        
        // Try to find and execute handler
        const handled = this.executeShortcut(shortcut, event);
        
        if (handled && this.shouldPreventDefault(shortcut)) {
            event.preventDefault();
            event.stopPropagation();
        }
    }
    
    /**
     * Handle keyup event
     */
    handleKeyUp(event) {
        this.updateModifiers(event);
    }
    
    /**
     * Build shortcut string from event
     */
    buildShortcutString(event) {
        const parts = [];
        
        if (event.ctrlKey || event.metaKey) parts.push('ctrl');
        if (event.altKey) parts.push('alt');
        if (event.shiftKey) parts.push('shift');
        
        // Get the key
        let key = event.key.toLowerCase();
        
        // Normalize special keys
        if (key === ' ') key = 'Space';
        if (key === 'arrowup') key = 'ArrowUp';
        if (key === 'arrowdown') key = 'ArrowDown';
        if (key === 'arrowleft') key = 'ArrowLeft';
        if (key === 'arrowright') key = 'ArrowRight';
        
        parts.push(key);
        
        return parts.join('+');
    }
    
    /**
     * Execute shortcut handler
     */
    executeShortcut(shortcut, event) {
        // Check current context first
        const contextShortcuts = this.shortcuts.get(this.activeContext);
        if (contextShortcuts && contextShortcuts.has(shortcut)) {
            const config = contextShortcuts.get(shortcut);
            config.handler(event);
            return true;
        }
        
        // Check global shortcuts
        const globalShortcuts = this.shortcuts.get('global');
        if (globalShortcuts && globalShortcuts.has(shortcut)) {
            const config = globalShortcuts.get(shortcut);
            config.handler(event);
            return true;
        }
        
        // Check for sequential shortcuts (e.g., 'g h')
        return this.checkSequentialShortcut(shortcut);
    }
    
    /**
     * Check for sequential shortcuts
     */
    checkSequentialShortcut(key) {
        // Implementation for sequential shortcuts like 'g h'
        // This would need a state machine to track key sequences
        return false;
    }
    
    /**
     * Should handle this keyboard event?
     */
    shouldHandle(event) {
        // Don't handle if in an input field (unless allowed)
        const target = event.target;
        const tagName = target.tagName.toLowerCase();
        const isInput = ['input', 'textarea', 'select'].includes(tagName) || 
                        target.contentEditable === 'true';
        
        if (isInput) {
            const shortcut = this.buildShortcutString(event);
            const config = this.getShortcutConfig(shortcut);
            return config && !config.preventInInput;
        }
        
        return true;
    }
    
    /**
     * Should prevent default for this shortcut?
     */
    shouldPreventDefault(shortcut) {
        const config = this.getShortcutConfig(shortcut);
        return config && config.preventDefault;
    }
    
    /**
     * Get shortcut configuration
     */
    getShortcutConfig(shortcut) {
        const contextShortcuts = this.shortcuts.get(this.activeContext);
        if (contextShortcuts && contextShortcuts.has(shortcut)) {
            return contextShortcuts.get(shortcut);
        }
        
        const globalShortcuts = this.shortcuts.get('global');
        if (globalShortcuts && globalShortcuts.has(shortcut)) {
            return globalShortcuts.get(shortcut);
        }
        
        return null;
    }
    
    /**
     * Update modifier keys state
     */
    updateModifiers(event) {
        this.modifierKeys.ctrl = event.ctrlKey || event.metaKey;
        this.modifierKeys.alt = event.altKey;
        this.modifierKeys.shift = event.shiftKey;
        this.modifierKeys.meta = event.metaKey;
    }
    
    /**
     * Reset modifier keys
     */
    resetModifiers() {
        this.modifierKeys = {
            ctrl: false,
            alt: false,
            shift: false,
            meta: false
        };
    }
    
    /**
     * Normalize shortcut string
     */
    normalizeShortcut(shortcut) {
        return shortcut.toLowerCase().replace(/\s+/g, '+');
    }
    
    /**
     * Detect current context based on page/state
     */
    detectContext() {
        const path = window.location.pathname;
        
        if (path.includes('/backpacks')) {
            this.activeContext = 'packBuilder';
        } else if (path.includes('/trips')) {
            this.activeContext = 'trips';
        } else if (path.includes('/gear')) {
            this.activeContext = 'gear';
        } else {
            this.activeContext = 'global';
        }
        
        console.log('Context changed to:', this.activeContext);
    }
    
    /**
     * Show keyboard shortcuts help
     */
    showHelp() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.style.display = 'flex';
            this.updateHelpContent();
        }
    }
    
    /**
     * Create help modal
     */
    createHelpModal() {
        if (document.getElementById('keyboard-shortcuts-modal')) return;
        
        const modal = document.createElement('div');
        modal.id = 'keyboard-shortcuts-modal';
        modal.className = 'keyboard-shortcuts-modal';
        modal.innerHTML = `
            <div class="shortcuts-modal-content">
                <div class="shortcuts-modal-header">
                    <h2>Keyboard Shortcuts</h2>
                    <button class="shortcuts-modal-close" aria-label="Close">×</button>
                </div>
                <div class="shortcuts-modal-body">
                    <div class="shortcuts-search">
                        <input type="search" placeholder="Search shortcuts..." id="shortcuts-search">
                    </div>
                    <div id="shortcuts-list" class="shortcuts-list"></div>
                </div>
                <div class="shortcuts-modal-footer">
                    <span>Press <kbd>?</kbd> to toggle this help</span>
                    <span>Press <kbd>Esc</kbd> to close</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Setup modal events
        const closeBtn = modal.querySelector('.shortcuts-modal-close');
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Search functionality
        const searchInput = modal.querySelector('#shortcuts-search');
        searchInput.addEventListener('input', (e) => {
            this.filterShortcuts(e.target.value);
        });
        
        this.injectModalStyles();
    }
    
    /**
     * Update help modal content
     */
    updateHelpContent() {
        const listContainer = document.getElementById('shortcuts-list');
        if (!listContainer) return;
        
        let html = '';
        
        // Add context-specific shortcuts
        if (this.activeContext !== 'global') {
            const contextShortcuts = this.shortcuts.get(this.activeContext);
            if (contextShortcuts) {
                html += `<div class="shortcuts-section">
                    <h3>${this.formatContext(this.activeContext)}</h3>
                    <div class="shortcuts-items">`;
                
                contextShortcuts.forEach((config, key) => {
                    html += this.renderShortcutItem(key, config);
                });
                
                html += '</div></div>';
            }
        }
        
        // Add global shortcuts
        const globalShortcuts = this.shortcuts.get('global');
        if (globalShortcuts) {
            html += `<div class="shortcuts-section">
                <h3>Global</h3>
                <div class="shortcuts-items">`;
            
            globalShortcuts.forEach((config, key) => {
                html += this.renderShortcutItem(key, config);
            });
            
            html += '</div></div>';
        }
        
        listContainer.innerHTML = html;
    }
    
    /**
     * Render shortcut item
     */
    renderShortcutItem(key, config) {
        const keys = key.split('+').map(k => `<kbd>${this.formatKey(k)}</kbd>`).join(' + ');
        return `
            <div class="shortcut-item" data-keys="${key}">
                <span class="shortcut-keys">${keys}</span>
                <span class="shortcut-description">${config.description}</span>
            </div>
        `;
    }
    
    /**
     * Format key for display
     */
    formatKey(key) {
        const keyMap = {
            'ctrl': '⌘/Ctrl',
            'alt': 'Alt',
            'shift': '⇧ Shift',
            'arrowup': '↑',
            'arrowdown': '↓',
            'arrowleft': '←',
            'arrowright': '→',
            'space': 'Space',
            'escape': 'Esc',
            'enter': '↵ Enter',
            'tab': '⇥ Tab'
        };
        
        return keyMap[key.toLowerCase()] || key.toUpperCase();
    }
    
    /**
     * Format context name
     */
    formatContext(context) {
        const contextMap = {
            'packBuilder': 'Pack Builder',
            'trips': 'Trips',
            'gear': 'Gear Library',
            'global': 'Global'
        };
        
        return contextMap[context] || context;
    }
    
    /**
     * Filter shortcuts in help modal
     */
    filterShortcuts(query) {
        const items = document.querySelectorAll('.shortcut-item');
        const searchTerm = query.toLowerCase();
        
        items.forEach(item => {
            const keys = item.dataset.keys.toLowerCase();
            const description = item.querySelector('.shortcut-description').textContent.toLowerCase();
            
            if (keys.includes(searchTerm) || description.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    /**
     * Create command palette
     */
    createCommandPalette() {
        if (document.getElementById('command-palette')) return;
        
        const palette = document.createElement('div');
        palette.id = 'command-palette';
        palette.className = 'command-palette';
        palette.innerHTML = `
            <div class="command-palette-content">
                <input type="search" 
                       id="command-input" 
                       class="command-input" 
                       placeholder="Type a command or search..."
                       autocomplete="off">
                <div id="command-results" class="command-results"></div>
            </div>
        `;
        
        document.body.appendChild(palette);
        
        // Setup command palette events
        const input = palette.querySelector('#command-input');
        
        input.addEventListener('input', (e) => {
            this.searchCommands(e.target.value);
        });
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideCommandPalette();
            } else if (e.key === 'Enter') {
                this.executeSelectedCommand();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectNextCommand();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectPreviousCommand();
            }
        });
    }
    
    /**
     * Show command palette
     */
    showCommandPalette() {
        const palette = document.getElementById('command-palette');
        if (palette) {
            palette.style.display = 'flex';
            const input = palette.querySelector('#command-input');
            input.value = '';
            input.focus();
            this.searchCommands('');
        }
    }
    
    /**
     * Hide command palette
     */
    hideCommandPalette() {
        const palette = document.getElementById('command-palette');
        if (palette) {
            palette.style.display = 'none';
        }
    }
    
    /**
     * Default shortcut handlers
     */
    focusSearch() {
        const searchInput = document.querySelector('input[type="search"], #global-search, #gear-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    escape() {
        // Close modals
        document.querySelectorAll('.modal, [role="dialog"]').forEach(modal => {
            if (modal.style.display !== 'none') {
                modal.style.display = 'none';
            }
        });
        
        // Close command palette
        this.hideCommandPalette();
        
        // Close help modal
        const helpModal = document.getElementById('keyboard-shortcuts-modal');
        if (helpModal && helpModal.style.display !== 'none') {
            helpModal.style.display = 'none';
        }
        
        // Clear selection
        window.getSelection().removeAllRanges();
    }
    
    navigate(path) {
        window.location.href = path;
    }
    
    createNew() {
        // Context-aware creation
        if (this.activeContext === 'packBuilder') {
            if (window.PackBuilder && window.PackBuilder.createNewPack) {
                window.PackBuilder.createNewPack();
            }
        } else if (this.activeContext === 'trips') {
            this.createTrip();
        } else if (this.activeContext === 'gear') {
            this.createGearItem();
        }
    }
    
    save(event) {
        event.preventDefault();
        
        // Find save button and click it
        const saveBtn = document.querySelector('#btn-save-pack, #btn-save-trip, [type="submit"]');
        if (saveBtn) {
            saveBtn.click();
        }
    }
    
    undo() {
        if (window.toastSystem && window.toastSystem.undoStack.length > 0) {
            const lastUndo = window.toastSystem.undoStack.pop();
            if (lastUndo && lastUndo.callback) {
                lastUndo.callback();
            }
        }
    }
    
    /**
     * Inject modal styles
     */
    injectModalStyles() {
        if (document.getElementById('keyboard-shortcuts-styles')) return;
        
        const styles = `
            .keyboard-shortcuts-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 10001;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(4px);
            }
            
            .shortcuts-modal-content {
                background: white;
                border-radius: 1rem;
                max-width: 800px;
                width: 90%;
                max-height: 80vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            
            .shortcuts-modal-header {
                padding: 1.5rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .shortcuts-modal-header h2 {
                margin: 0;
                font-size: 1.5rem;
                color: #1f2937;
            }
            
            .shortcuts-modal-close {
                background: none;
                border: none;
                font-size: 2rem;
                color: #6b7280;
                cursor: pointer;
                width: 2rem;
                height: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.375rem;
                transition: all 0.2s;
            }
            
            .shortcuts-modal-close:hover {
                background: #f3f4f6;
                color: #1f2937;
            }
            
            .shortcuts-modal-body {
                flex: 1;
                overflow-y: auto;
                padding: 1.5rem;
            }
            
            .shortcuts-search {
                margin-bottom: 1.5rem;
            }
            
            .shortcuts-search input {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                font-size: 0.875rem;
            }
            
            .shortcuts-list {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .shortcuts-section h3 {
                margin: 0 0 0.75rem 0;
                font-size: 1rem;
                color: #6b7280;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            
            .shortcuts-items {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .shortcut-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                border-radius: 0.375rem;
                transition: background 0.2s;
            }
            
            .shortcut-item:hover {
                background: #f9fafb;
            }
            
            .shortcut-keys {
                display: flex;
                gap: 0.25rem;
                align-items: center;
            }
            
            kbd {
                padding: 0.25rem 0.5rem;
                background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
                border: 1px solid #d1d5db;
                border-radius: 0.25rem;
                font-family: monospace;
                font-size: 0.75rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }
            
            .shortcut-description {
                color: #4b5563;
                font-size: 0.875rem;
            }
            
            .shortcuts-modal-footer {
                padding: 1rem 1.5rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                font-size: 0.875rem;
                color: #6b7280;
            }
            
            /* Command Palette Styles */
            .command-palette {
                display: none;
                position: fixed;
                top: 20%;
                left: 50%;
                transform: translateX(-50%);
                width: 90%;
                max-width: 600px;
                z-index: 10002;
                align-items: flex-start;
                justify-content: center;
            }
            
            .command-palette-content {
                background: white;
                border-radius: 0.75rem;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                overflow: hidden;
            }
            
            .command-input {
                width: 100%;
                padding: 1rem;
                border: none;
                font-size: 1rem;
                outline: none;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .command-results {
                max-height: 400px;
                overflow-y: auto;
            }
            
            .command-item {
                padding: 0.75rem 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                transition: background 0.1s;
            }
            
            .command-item:hover,
            .command-item.selected {
                background: #f3f4f6;
            }
            
            .command-item-icon {
                width: 1.5rem;
                height: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #6b7280;
            }
            
            .command-item-text {
                flex: 1;
            }
            
            .command-item-title {
                font-size: 0.875rem;
                color: #1f2937;
                font-weight: 500;
            }
            
            .command-item-subtitle {
                font-size: 0.75rem;
                color: #6b7280;
            }
            
            .command-item-shortcut {
                display: flex;
                gap: 0.25rem;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'keyboard-shortcuts-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
    
    /**
     * Save preferences
     */
    savePreferences() {
        localStorage.setItem('keyboard-shortcuts-enabled', this.enabled);
    }
    
    /**
     * Load preferences
     */
    loadPreferences() {
        const enabled = localStorage.getItem('keyboard-shortcuts-enabled');
        if (enabled !== null) {
            this.enabled = enabled === 'true';
        }
    }
    
    /**
     * Enable shortcuts
     */
    enable() {
        this.enabled = true;
        this.savePreferences();
    }
    
    /**
     * Disable shortcuts
     */
    disable() {
        this.enabled = false;
        this.savePreferences();
    }
}

// Initialize keyboard shortcuts
window.keyboardShortcuts = new KeyboardShortcuts();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KeyboardShortcuts;
}
