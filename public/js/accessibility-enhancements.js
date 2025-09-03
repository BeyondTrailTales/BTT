/**
 * Accessibility Enhancements Module
 * WCAG AA compliance, skip links, landmarks, and screen reader improvements
 */

class AccessibilityEnhancements {
    constructor() {
        this.config = {
            enableSkipLinks: true,
            enableLandmarks: true,
            enableAriaLive: true,
            enableFocusIndicators: true,
            enableHighContrast: false,
            enableReducedMotion: false,
            announceRouteChanges: true,
            keyboardTrapElements: new Set()
        };
        
        this.focusableElements = 'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])';
        this.currentFocus = null;
        this.skipLinks = [];
        this.announcements = [];
        
        this.init();
    }
    
    /**
     * Initialize accessibility enhancements
     */
    init() {
        console.log('Initializing Accessibility Enhancements...');
        
        // Check user preferences
        this.checkUserPreferences();
        
        // Create skip links
        this.createSkipLinks();
        
        // Enhance landmarks
        this.enhanceLandmarks();
        
        // Setup ARIA live regions
        this.setupAriaLiveRegions();
        
        // Enhance focus management
        this.enhanceFocusManagement();
        
        // Setup keyboard navigation helpers
        this.setupKeyboardNavigation();
        
        // Add screen reader helpers
        this.addScreenReaderHelpers();
        
        // Setup high contrast mode
        this.setupHighContrastMode();
        
        // Monitor DOM changes
        this.monitorDOMChanges();
        
        // Add accessibility toolbar
        this.createAccessibilityToolbar();
        
        console.log('✅ Accessibility Enhancements initialized');
    }
    
    /**
     * Check user preferences for accessibility
     */
    checkUserPreferences() {
        // Check for reduced motion preference
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (prefersReducedMotion.matches) {
            this.config.enableReducedMotion = true;
            document.body.classList.add('reduce-motion');
        }
        
        // Check for high contrast preference
        const prefersHighContrast = window.matchMedia('(prefers-contrast: high)');
        if (prefersHighContrast.matches) {
            this.config.enableHighContrast = true;
            document.body.classList.add('high-contrast');
        }
        
        // Check saved preferences
        const savedPrefs = localStorage.getItem('a11y-preferences');
        if (savedPrefs) {
            try {
                const prefs = JSON.parse(savedPrefs);
                Object.assign(this.config, prefs);
                this.applyPreferences();
            } catch (e) {
                console.error('Error loading accessibility preferences:', e);
            }
        }
    }
    
    /**
     * Create skip links for navigation
     */
    createSkipLinks() {
        if (!this.config.enableSkipLinks) return;
        
        // Remove existing skip links container
        const existing = document.getElementById('skip-links');
        if (existing) existing.remove();
        
        // Define skip link targets
        const skipTargets = [
            { text: 'Skip to main content', target: '#main-content, main, [role="main"]' },
            { text: 'Skip to navigation', target: '#main-nav, nav, [role="navigation"]' },
            { text: 'Skip to search', target: '#search, [type="search"], .search-bar input' },
            { text: 'Skip to footer', target: '#footer, footer, [role="contentinfo"]' }
        ];
        
        // Create skip links container
        const container = document.createElement('div');
        container.id = 'skip-links';
        container.className = 'skip-links';
        container.setAttribute('role', 'navigation');
        container.setAttribute('aria-label', 'Skip links');
        
        // Create skip links
        skipTargets.forEach(({ text, target }) => {
            const targetElement = document.querySelector(target);
            if (targetElement) {
                const link = document.createElement('a');
                link.href = '#';
                link.className = 'skip-link';
                link.textContent = text;
                
                // Ensure target has an ID
                if (!targetElement.id) {
                    targetElement.id = `skip-target-${Math.random().toString(36).substr(2, 9)}`;
                }
                
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    targetElement.focus();
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    // Announce to screen reader
                    this.announce(`Navigated to ${text.replace('Skip to ', '')}`);
                });
                
                container.appendChild(link);
                this.skipLinks.push(link);
            }
        });
        
        // Insert at the beginning of body
        document.body.insertBefore(container, document.body.firstChild);
        
        // Add skip link styles
        this.injectSkipLinkStyles();
    }
    
    /**
     * Enhance landmarks with proper ARIA roles and labels
     */
    enhanceLandmarks() {
        if (!this.config.enableLandmarks) return;
        
        // Main navigation
        const mainNav = document.querySelector('.unified-nav, .main-nav, nav:not([role])');
        if (mainNav && !mainNav.hasAttribute('role')) {
            mainNav.setAttribute('role', 'navigation');
            mainNav.setAttribute('aria-label', 'Main navigation');
        }
        
        // Main content
        const mainContent = document.querySelector('#main-content, main, .main-wrapper');
        if (mainContent && !mainContent.hasAttribute('role')) {
            mainContent.setAttribute('role', 'main');
            mainContent.setAttribute('aria-label', 'Main content');
        }
        
        // Header
        const header = document.querySelector('header, .site-header');
        if (header && !header.hasAttribute('role')) {
            header.setAttribute('role', 'banner');
        }
        
        // Footer
        const footer = document.querySelector('footer, .site-footer');
        if (footer && !footer.hasAttribute('role')) {
            footer.setAttribute('role', 'contentinfo');
        }
        
        // Search
        const searchForm = document.querySelector('form[role="search"], .search-bar');
        if (searchForm && !searchForm.hasAttribute('role')) {
            searchForm.setAttribute('role', 'search');
            searchForm.setAttribute('aria-label', 'Site search');
        }
        
        // Aside/Sidebar
        const sidebars = document.querySelectorAll('aside, .sidebar');
        sidebars.forEach(sidebar => {
            if (!sidebar.hasAttribute('role')) {
                sidebar.setAttribute('role', 'complementary');
                sidebar.setAttribute('aria-label', 'Sidebar');
            }
        });
        
        // Forms
        const forms = document.querySelectorAll('form:not([role])');
        forms.forEach(form => {
            if (!form.hasAttribute('aria-label') && !form.hasAttribute('aria-labelledby')) {
                const heading = form.querySelector('h1, h2, h3, h4, h5, h6');
                if (heading) {
                    const headingId = heading.id || `form-heading-${Math.random().toString(36).substr(2, 9)}`;
                    heading.id = headingId;
                    form.setAttribute('aria-labelledby', headingId);
                }
            }
        });
    }
    
    /**
     * Setup ARIA live regions for dynamic content
     */
    setupAriaLiveRegions() {
        if (!this.config.enableAriaLive) return;
        
        // Create announcement region
        let announcer = document.getElementById('aria-announcer');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'aria-announcer';
            announcer.className = 'sr-only';
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
        
        // Create alert region
        let alerter = document.getElementById('aria-alerter');
        if (!alerter) {
            alerter = document.createElement('div');
            alerter.id = 'aria-alerter';
            alerter.className = 'sr-only';
            alerter.setAttribute('role', 'alert');
            alerter.setAttribute('aria-live', 'assertive');
            alerter.setAttribute('aria-atomic', 'true');
            document.body.appendChild(alerter);
        }
        
        // Monitor loading states
        this.monitorLoadingStates();
    }
    
    /**
     * Enhance focus management
     */
    enhanceFocusManagement() {
        if (!this.config.enableFocusIndicators) return;
        
        // Track focus source (keyboard vs mouse)
        let usingKeyboard = false;
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                usingKeyboard = true;
                document.body.classList.add('using-keyboard');
            }
        });
        
        document.addEventListener('mousedown', () => {
            usingKeyboard = false;
            document.body.classList.remove('using-keyboard');
        });
        
        // Enhance focus visibility
        document.addEventListener('focusin', (e) => {
            if (usingKeyboard) {
                e.target.classList.add('keyboard-focus');
            }
            this.currentFocus = e.target;
        });
        
        document.addEventListener('focusout', (e) => {
            e.target.classList.remove('keyboard-focus');
        });
        
        // Add focus styles
        this.injectFocusStyles();
    }
    
    /**
     * Setup keyboard navigation helpers
     */
    setupKeyboardNavigation() {
        // Handle arrow key navigation in menus
        document.addEventListener('keydown', (e) => {
            const target = e.target;
            
            // Menu navigation
            if (target.matches('[role="menu"] [role="menuitem"]')) {
                this.handleMenuKeyboard(e, target);
            }
            
            // Tab panel navigation
            if (target.matches('[role="tab"]')) {
                this.handleTabKeyboard(e, target);
            }
            
            // Grid navigation
            if (target.matches('[role="grid"] [role="gridcell"]')) {
                this.handleGridKeyboard(e, target);
            }
        });
        
        // Escape key to close modals/dropdowns
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.handleEscape(e);
            }
        });
    }
    
    /**
     * Handle menu keyboard navigation
     */
    handleMenuKeyboard(e, target) {
        const menu = target.closest('[role="menu"]');
        const items = Array.from(menu.querySelectorAll('[role="menuitem"]:not([disabled])'));
        const currentIndex = items.indexOf(target);
        
        let nextIndex;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                nextIndex = currentIndex - 1;
                if (nextIndex < 0) nextIndex = items.length - 1;
                items[nextIndex].focus();
                break;
                
            case 'Home':
                e.preventDefault();
                items[0].focus();
                break;
                
            case 'End':
                e.preventDefault();
                items[items.length - 1].focus();
                break;
        }
    }
    
    /**
     * Handle tab panel keyboard navigation
     */
    handleTabKeyboard(e, target) {
        const tablist = target.closest('[role="tablist"]');
        const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));
        const currentIndex = tabs.indexOf(target);
        
        let nextIndex;
        
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                nextIndex = currentIndex - 1;
                if (nextIndex < 0) nextIndex = tabs.length - 1;
                tabs[nextIndex].click();
                tabs[nextIndex].focus();
                break;
                
            case 'ArrowRight':
                e.preventDefault();
                nextIndex = (currentIndex + 1) % tabs.length;
                tabs[nextIndex].click();
                tabs[nextIndex].focus();
                break;
                
            case 'Home':
                e.preventDefault();
                tabs[0].click();
                tabs[0].focus();
                break;
                
            case 'End':
                e.preventDefault();
                tabs[tabs.length - 1].click();
                tabs[tabs.length - 1].focus();
                break;
        }
    }
    
    /**
     * Handle grid keyboard navigation
     */
    handleGridKeyboard(e, target) {
        const grid = target.closest('[role="grid"]');
        const cells = Array.from(grid.querySelectorAll('[role="gridcell"]'));
        const currentIndex = cells.indexOf(target);
        const columns = parseInt(grid.dataset.columns) || 3;
        
        let nextIndex;
        
        switch (e.key) {
            case 'ArrowRight':
                e.preventDefault();
                nextIndex = Math.min(currentIndex + 1, cells.length - 1);
                cells[nextIndex].focus();
                break;
                
            case 'ArrowLeft':
                e.preventDefault();
                nextIndex = Math.max(currentIndex - 1, 0);
                cells[nextIndex].focus();
                break;
                
            case 'ArrowDown':
                e.preventDefault();
                nextIndex = Math.min(currentIndex + columns, cells.length - 1);
                cells[nextIndex].focus();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                nextIndex = Math.max(currentIndex - columns, 0);
                cells[nextIndex].focus();
                break;
        }
    }
    
    /**
     * Handle escape key
     */
    handleEscape(e) {
        // Close dropdowns
        const openDropdown = document.querySelector('.dropdown.open, [aria-expanded="true"]');
        if (openDropdown) {
            openDropdown.classList.remove('open');
            openDropdown.setAttribute('aria-expanded', 'false');
            
            // Return focus to trigger
            const trigger = openDropdown.querySelector('[aria-haspopup]') || 
                          document.querySelector(`[aria-controls="${openDropdown.id}"]`);
            if (trigger) trigger.focus();
        }
        
        // Close modals
        const openModal = document.querySelector('.modal.open, [role="dialog"]:not([hidden])');
        if (openModal) {
            openModal.classList.remove('open');
            openModal.hidden = true;
            
            // Return focus to trigger
            if (this.modalTrigger) {
                this.modalTrigger.focus();
                this.modalTrigger = null;
            }
        }
    }
    
    /**
     * Add screen reader helpers
     */
    addScreenReaderHelpers() {
        // Add descriptive text to icon-only buttons
        document.querySelectorAll('button, a').forEach(element => {
            const hasText = element.textContent.trim().replace(/[^\w\s]/g, '').length > 0;
            const hasAriaLabel = element.hasAttribute('aria-label');
            const hasAriaLabelledby = element.hasAttribute('aria-labelledby');
            
            if (!hasText && !hasAriaLabel && !hasAriaLabelledby) {
                // Try to determine purpose from class or ID
                const purposeMap = {
                    'close': 'Close',
                    'menu': 'Menu',
                    'search': 'Search',
                    'edit': 'Edit',
                    'delete': 'Delete',
                    'save': 'Save',
                    'cancel': 'Cancel',
                    'add': 'Add',
                    'remove': 'Remove',
                    'settings': 'Settings',
                    'profile': 'Profile',
                    'logout': 'Logout',
                    'help': 'Help'
                };
                
                for (const [key, label] of Object.entries(purposeMap)) {
                    if (element.className.includes(key) || element.id.includes(key)) {
                        element.setAttribute('aria-label', label);
                        break;
                    }
                }
            }
        });
        
        // Add table headers scope
        document.querySelectorAll('th').forEach(th => {
            if (!th.hasAttribute('scope')) {
                // Determine if row or column header
                const isRowHeader = th.parentElement.querySelector('td');
                th.setAttribute('scope', isRowHeader ? 'row' : 'col');
            }
        });
        
        // Add form field descriptions
        document.querySelectorAll('input, select, textarea').forEach(field => {
            if (!field.hasAttribute('aria-describedby')) {
                // Look for help text
                const helpText = field.parentElement.querySelector('.form-text, .help-text, small');
                if (helpText) {
                    const helpId = helpText.id || `help-${Math.random().toString(36).substr(2, 9)}`;
                    helpText.id = helpId;
                    field.setAttribute('aria-describedby', helpId);
                }
            }
        });
    }
    
    /**
     * Setup high contrast mode
     */
    setupHighContrastMode() {
        // Check if user has high contrast preference saved
        if (this.config.enableHighContrast) {
            document.body.classList.add('high-contrast');
        }
        
        // Listen for changes
        const highContrastMedia = window.matchMedia('(prefers-contrast: high)');
        highContrastMedia.addEventListener('change', (e) => {
            if (e.matches) {
                this.enableHighContrast();
            } else {
                this.disableHighContrast();
            }
        });
    }
    
    /**
     * Enable high contrast mode
     */
    enableHighContrast() {
        document.body.classList.add('high-contrast');
        this.config.enableHighContrast = true;
        this.savePreferences();
        this.announce('High contrast mode enabled');
    }
    
    /**
     * Disable high contrast mode
     */
    disableHighContrast() {
        document.body.classList.remove('high-contrast');
        this.config.enableHighContrast = false;
        this.savePreferences();
        this.announce('High contrast mode disabled');
    }
    
    /**
     * Monitor loading states for announcements
     */
    monitorLoadingStates() {
        // Monitor AJAX requests
        if (window.XMLHttpRequest) {
            const originalOpen = XMLHttpRequest.prototype.open;
            const self = this;
            
            XMLHttpRequest.prototype.open = function() {
                this.addEventListener('loadstart', () => {
                    self.announce('Loading content...');
                });
                
                this.addEventListener('loadend', () => {
                    self.announce('Content loaded');
                });
                
                originalOpen.apply(this, arguments);
            };
        }
        
        // Monitor route changes
        if (this.config.announceRouteChanges) {
            window.addEventListener('popstate', () => {
                this.announceRouteChange();
            });
        }
    }
    
    /**
     * Monitor DOM changes for accessibility updates
     */
    monitorDOMChanges() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            // Enhance new content
                            this.enhanceElement(node);
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    /**
     * Enhance a single element for accessibility
     */
    enhanceElement(element) {
        // Add to focusable elements if needed
        if (element.matches(this.focusableElements)) {
            this.ensureFocusable(element);
        }
        
        // Enhance form fields
        if (element.matches('input, select, textarea')) {
            this.enhanceFormField(element);
        }
        
        // Enhance buttons
        if (element.matches('button, [role="button"]')) {
            this.enhanceButton(element);
        }
        
        // Enhance images
        if (element.matches('img')) {
            this.enhanceImage(element);
        }
    }
    
    /**
     * Ensure element is properly focusable
     */
    ensureFocusable(element) {
        // Add tabindex if needed
        if (!element.hasAttribute('tabindex') && 
            !element.matches('a[href], button, input, select, textarea')) {
            element.setAttribute('tabindex', '0');
        }
    }
    
    /**
     * Enhance form field accessibility
     */
    enhanceFormField(field) {
        // Ensure label association
        if (!field.hasAttribute('aria-label') && !field.hasAttribute('aria-labelledby')) {
            const label = document.querySelector(`label[for="${field.id}"]`);
            if (!label && field.parentElement.matches('label')) {
                // Implicit label - add aria-label
                const labelText = field.parentElement.textContent.replace(field.value, '').trim();
                if (labelText) {
                    field.setAttribute('aria-label', labelText);
                }
            }
        }
        
        // Add required indicator
        if (field.hasAttribute('required') && !field.hasAttribute('aria-required')) {
            field.setAttribute('aria-required', 'true');
        }
        
        // Add invalid state
        if (field.validity && !field.validity.valid) {
            field.setAttribute('aria-invalid', 'true');
        }
    }
    
    /**
     * Enhance button accessibility
     */
    enhanceButton(button) {
        // Ensure button has accessible name
        const hasText = button.textContent.trim().length > 0;
        const hasAriaLabel = button.hasAttribute('aria-label');
        
        if (!hasText && !hasAriaLabel) {
            // Try to determine purpose
            const title = button.getAttribute('title');
            if (title) {
                button.setAttribute('aria-label', title);
            }
        }
        
        // Add role if needed
        if (!button.matches('button') && !button.hasAttribute('role')) {
            button.setAttribute('role', 'button');
        }
    }
    
    /**
     * Enhance image accessibility
     */
    enhanceImage(img) {
        // Check for alt text
        if (!img.hasAttribute('alt')) {
            // Decorative image
            img.setAttribute('alt', '');
            img.setAttribute('role', 'presentation');
        } else if (img.alt === '') {
            // Explicitly decorative
            img.setAttribute('role', 'presentation');
        }
    }
    
    /**
     * Create accessibility toolbar
     */
    createAccessibilityToolbar() {
        // Check if toolbar already exists
        if (document.getElementById('a11y-toolbar')) return;
        
        const toolbar = document.createElement('div');
        toolbar.id = 'a11y-toolbar';
        toolbar.className = 'a11y-toolbar';
        toolbar.setAttribute('role', 'toolbar');
        toolbar.setAttribute('aria-label', 'Accessibility tools');
        
        // Create toolbar content
        toolbar.innerHTML = `
            <button class="a11y-toolbar-toggle" aria-label="Toggle accessibility toolbar" aria-expanded="false">
                <span class="icon">♿</span>
            </button>
            <div class="a11y-toolbar-panel" hidden>
                <h3>Accessibility Options</h3>
                <div class="a11y-options">
                    <button class="a11y-option" data-action="high-contrast">
                        <span class="icon">🔲</span>
                        <span>High Contrast</span>
                    </button>
                    <button class="a11y-option" data-action="large-text">
                        <span class="icon">🔤</span>
                        <span>Large Text</span>
                    </button>
                    <button class="a11y-option" data-action="reduce-motion">
                        <span class="icon">⏸️</span>
                        <span>Reduce Motion</span>
                    </button>
                    <button class="a11y-option" data-action="focus-highlight">
                        <span class="icon">🎯</span>
                        <span>Focus Highlight</span>
                    </button>
                    <button class="a11y-option" data-action="reading-guide">
                        <span class="icon">📖</span>
                        <span>Reading Guide</span>
                    </button>
                    <button class="a11y-option" data-action="reset">
                        <span class="icon">🔄</span>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(toolbar);
        
        // Setup toolbar events
        this.setupToolbarEvents(toolbar);
        
        // Add toolbar styles
        this.injectToolbarStyles();
    }
    
    /**
     * Setup toolbar events
     */
    setupToolbarEvents(toolbar) {
        const toggle = toolbar.querySelector('.a11y-toolbar-toggle');
        const panel = toolbar.querySelector('.a11y-toolbar-panel');
        
        // Toggle panel
        toggle.addEventListener('click', () => {
            const isOpen = panel.hidden === false;
            panel.hidden = isOpen;
            toggle.setAttribute('aria-expanded', !isOpen);
        });
        
        // Handle option clicks
        toolbar.addEventListener('click', (e) => {
            const option = e.target.closest('.a11y-option');
            if (!option) return;
            
            const action = option.dataset.action;
            
            switch (action) {
                case 'high-contrast':
                    this.toggleHighContrast();
                    break;
                case 'large-text':
                    this.toggleLargeText();
                    break;
                case 'reduce-motion':
                    this.toggleReducedMotion();
                    break;
                case 'focus-highlight':
                    this.toggleFocusHighlight();
                    break;
                case 'reading-guide':
                    this.toggleReadingGuide();
                    break;
                case 'reset':
                    this.resetAccessibility();
                    break;
            }
            
            // Update button state
            if (action !== 'reset') {
                option.classList.toggle('active');
            }
        });
    }
    
    /**
     * Toggle high contrast
     */
    toggleHighContrast() {
        document.body.classList.toggle('high-contrast');
        this.config.enableHighContrast = document.body.classList.contains('high-contrast');
        this.savePreferences();
        this.announce(this.config.enableHighContrast ? 'High contrast enabled' : 'High contrast disabled');
    }
    
    /**
     * Toggle large text
     */
    toggleLargeText() {
        document.body.classList.toggle('large-text');
        this.announce(document.body.classList.contains('large-text') ? 'Large text enabled' : 'Large text disabled');
    }
    
    /**
     * Toggle reduced motion
     */
    toggleReducedMotion() {
        document.body.classList.toggle('reduce-motion');
        this.config.enableReducedMotion = document.body.classList.contains('reduce-motion');
        this.savePreferences();
        this.announce(this.config.enableReducedMotion ? 'Reduced motion enabled' : 'Reduced motion disabled');
    }
    
    /**
     * Toggle focus highlight
     */
    toggleFocusHighlight() {
        document.body.classList.toggle('focus-highlight');
        this.announce(document.body.classList.contains('focus-highlight') ? 'Focus highlight enabled' : 'Focus highlight disabled');
    }
    
    /**
     * Toggle reading guide
     */
    toggleReadingGuide() {
        const hasGuide = document.getElementById('reading-guide');
        
        if (hasGuide) {
            hasGuide.remove();
            this.announce('Reading guide disabled');
        } else {
            const guide = document.createElement('div');
            guide.id = 'reading-guide';
            guide.className = 'reading-guide';
            document.body.appendChild(guide);
            
            // Follow mouse
            document.addEventListener('mousemove', (e) => {
                guide.style.top = `${e.clientY - 40}px`;
            });
            
            this.announce('Reading guide enabled');
        }
    }
    
    /**
     * Reset accessibility settings
     */
    resetAccessibility() {
        document.body.classList.remove('high-contrast', 'large-text', 'reduce-motion', 'focus-highlight');
        
        const guide = document.getElementById('reading-guide');
        if (guide) guide.remove();
        
        // Reset toolbar buttons
        document.querySelectorAll('.a11y-option.active').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Reset config
        this.config = {
            enableSkipLinks: true,
            enableLandmarks: true,
            enableAriaLive: true,
            enableFocusIndicators: true,
            enableHighContrast: false,
            enableReducedMotion: false,
            announceRouteChanges: true
        };
        
        this.savePreferences();
        this.announce('Accessibility settings reset');
    }
    
    /**
     * Announce to screen readers
     */
    announce(message, priority = 'polite') {
        const announcer = document.getElementById(priority === 'assertive' ? 'aria-alerter' : 'aria-announcer');
        if (announcer) {
            announcer.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }
    
    /**
     * Announce route change
     */
    announceRouteChange() {
        const pageTitle = document.title.split(' - ')[0];
        this.announce(`Navigated to ${pageTitle}`);
    }
    
    /**
     * Save preferences
     */
    savePreferences() {
        const prefs = {
            enableHighContrast: this.config.enableHighContrast,
            enableReducedMotion: this.config.enableReducedMotion
        };
        localStorage.setItem('a11y-preferences', JSON.stringify(prefs));
    }
    
    /**
     * Apply saved preferences
     */
    applyPreferences() {
        if (this.config.enableHighContrast) {
            document.body.classList.add('high-contrast');
        }
        
        if (this.config.enableReducedMotion) {
            document.body.classList.add('reduce-motion');
        }
    }
    
    /**
     * Inject skip link styles
     */
    injectSkipLinkStyles() {
        this.injectStyles('skip-links', `
            .skip-links {
                position: absolute;
                top: -40px;
                left: 0;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                z-index: 100000;
            }
            
            .skip-link {
                position: absolute;
                left: -10000px;
                top: auto;
                width: 1px;
                height: 1px;
                overflow: hidden;
                background: #000;
                color: #fff;
                padding: 8px 16px;
                text-decoration: none;
                border-radius: 4px;
            }
            
            .skip-link:focus {
                position: absolute;
                left: 10px;
                top: 10px;
                width: auto;
                height: auto;
                z-index: 100000;
            }
        `);
    }
    
    /**
     * Inject focus styles
     */
    injectFocusStyles() {
        this.injectStyles('focus-styles', `
            .using-keyboard :focus {
                outline: 3px solid #4A90E2 !important;
                outline-offset: 2px !important;
            }
            
            .keyboard-focus {
                outline: 3px solid #4A90E2 !important;
                outline-offset: 2px !important;
                box-shadow: 0 0 0 6px rgba(74, 144, 226, 0.2) !important;
            }
            
            .focus-highlight *:focus {
                outline: 4px solid #FFD700 !important;
                outline-offset: 4px !important;
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.5) !important;
            }
            
            .high-contrast {
                filter: contrast(1.5);
            }
            
            .high-contrast * {
                border-color: currentColor !important;
            }
            
            .large-text {
                font-size: 125%;
            }
            
            .large-text * {
                line-height: 1.5 !important;
            }
            
            .reduce-motion *,
            .reduce-motion *::before,
            .reduce-motion *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            
            .reading-guide {
                position: fixed;
                left: 0;
                right: 0;
                height: 80px;
                background: rgba(255, 255, 0, 0.1);
                border-top: 2px solid #FFD700;
                border-bottom: 2px solid #FFD700;
                pointer-events: none;
                z-index: 10000;
                transition: top 0.1s ease;
            }
            
            .sr-only {
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            }
        `);
    }
    
    /**
     * Inject toolbar styles
     */
    injectToolbarStyles() {
        this.injectStyles('toolbar-styles', `
            .a11y-toolbar {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 10000;
            }
            
            .a11y-toolbar-toggle {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: #4A90E2;
                color: white;
                border: none;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
            }
            
            .a11y-toolbar-toggle:hover {
                background: #357ABD;
                transform: scale(1.1);
            }
            
            .a11y-toolbar-panel {
                position: absolute;
                bottom: 60px;
                right: 0;
                background: white;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                min-width: 200px;
            }
            
            .a11y-toolbar-panel h3 {
                margin: 0 0 15px 0;
                font-size: 16px;
                color: #333;
            }
            
            .a11y-options {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .a11y-option {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                background: white;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .a11y-option:hover {
                background: #f0f0f0;
                border-color: #4A90E2;
            }
            
            .a11y-option.active {
                background: #E3F2FD;
                border-color: #4A90E2;
                color: #1565C0;
            }
            
            .a11y-option .icon {
                font-size: 20px;
            }
        `);
    }
    
    /**
     * Inject styles helper
     */
    injectStyles(id, styles) {
        if (document.getElementById(`a11y-${id}`)) return;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = `a11y-${id}`;
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
}

// Initialize accessibility enhancements
window.accessibilityEnhancements = new AccessibilityEnhancements();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AccessibilityEnhancements;
}
