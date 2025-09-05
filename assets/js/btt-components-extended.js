/**
 * BeyondTrailTales Extended Components
 * Dark mode, onboarding, and additional features
 * Version: 2.0.0
 */

(function(window, document) {
    'use strict';

    window.BTT = window.BTT || {};

    /**
     * Dark Mode Manager
     * Handles theme switching and persistence
     */
    BTT.DarkMode = {
        currentTheme: 'light',
        
        init() {
            // Check for saved theme preference or default to 'light'
            this.currentTheme = localStorage.getItem('btt-theme') || 'light';
            
            // Check for system preference
            if (!localStorage.getItem('btt-theme')) {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    this.currentTheme = 'dark';
                }
            }
            
            // Apply theme
            this.applyTheme(this.currentTheme);
            
            // Add toggle controls
            this.initToggle();
            
            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                    if (!localStorage.getItem('btt-theme-override')) {
                        this.setTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        },
        
        initToggle() {
            // Create toggle if it doesn't exist
            if (!document.querySelector('.dark-mode-toggle')) {
                const toggle = document.createElement('div');
                toggle.className = 'dark-mode-toggle';
                toggle.innerHTML = `
                    <label class="dark-mode-switch">
                        <span class="dark-mode-icon-sun">☀️</span>
                        <input type="checkbox" id="dark-mode-toggle" ${this.currentTheme === 'dark' ? 'checked' : ''}>
                        <div class="dark-mode-switch-track">
                            <div class="dark-mode-switch-thumb"></div>
                        </div>
                        <span class="dark-mode-icon-moon">🌙</span>
                    </label>
                `;
                document.body.appendChild(toggle);
            }
            
            // Add event listener
            const checkbox = document.getElementById('dark-mode-toggle');
            if (checkbox) {
                checkbox.addEventListener('change', (e) => {
                    this.setTheme(e.target.checked ? 'dark' : 'light');
                    localStorage.setItem('btt-theme-override', 'true');
                });
            }
        },
        
        setTheme(theme) {
            this.currentTheme = theme;
            this.applyTheme(theme);
            localStorage.setItem('btt-theme', theme);
            
            // Dispatch custom event
            document.dispatchEvent(new CustomEvent('theme:change', { detail: { theme } }));
            
            // Show toast notification
            BTT.Toast?.show(`Switched to ${theme} mode`, 'info', 2000);
        },
        
        applyTheme(theme) {
            // Add no-transition class to prevent flash
            document.documentElement.classList.add('no-transition');
            
            // Set theme
            document.documentElement.setAttribute('data-theme', theme);
            
            // Update checkbox if exists
            const checkbox = document.getElementById('dark-mode-toggle');
            if (checkbox) {
                checkbox.checked = theme === 'dark';
            }
            
            // Remove no-transition after a frame
            requestAnimationFrame(() => {
                document.documentElement.classList.remove('no-transition');
            });
        },
        
        toggle() {
            this.setTheme(this.currentTheme === 'light' ? 'dark' : 'light');
        }
    };

    /**
     * Onboarding System
     * Interactive tutorial for new users
     */
    BTT.Onboarding = {
        steps: [],
        currentStep: 0,
        overlay: null,
        
        init() {
            // Check if user has completed onboarding
            if (!localStorage.getItem('btt-onboarding-complete')) {
                // Auto-start for new users
                const isNewUser = !localStorage.getItem('btt-user-id');
                if (isNewUser) {
                    setTimeout(() => this.start(), 1000);
                }
            }
        },
        
        configure(steps) {
            this.steps = steps;
        },
        
        start() {
            if (this.steps.length === 0) {
                console.warn('No onboarding steps configured');
                return;
            }
            
            this.currentStep = 0;
            this.createOverlay();
            this.showStep(0);
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('onboarding:start'));
        },
        
        createOverlay() {
            this.overlay = document.createElement('div');
            this.overlay.className = 'onboarding-overlay';
            this.overlay.innerHTML = `
                <div class="onboarding-container">
                    <div class="onboarding-progress">
                        <div class="onboarding-progress-bar" style="width: 0%"></div>
                    </div>
                    <div class="onboarding-content">
                        <button class="onboarding-skip">
                            <i class="fas fa-times mr-2"></i>
                            Skip Tour
                        </button>
                        <div class="onboarding-spotlight"></div>
                        <div class="onboarding-tooltip">
                            <div class="onboarding-tooltip-arrow"></div>
                            <div class="onboarding-tooltip-content">
                                <h3 class="onboarding-title"></h3>
                                <p class="onboarding-description"></p>
                                <div class="onboarding-actions">
                                    <button class="btn btn-ghost btn-sm onboarding-prev">Previous</button>
                                    <span class="onboarding-counter"></span>
                                    <button class="btn btn-primary btn-sm onboarding-next">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(this.overlay);
            
            // Add event listeners
            this.overlay.querySelector('.onboarding-skip').addEventListener('click', () => this.complete());
            this.overlay.querySelector('.onboarding-prev').addEventListener('click', () => this.previousStep());
            this.overlay.querySelector('.onboarding-next').addEventListener('click', () => this.nextStep());
            
            // Add CSS if not exists
            if (!document.getElementById('onboarding-styles')) {
                const styles = document.createElement('style');
                styles.id = 'onboarding-styles';
                styles.textContent = `
                    .onboarding-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(135deg, rgba(10, 40, 24, 0.85), rgba(129, 199, 132, 0.15));
                        backdrop-filter: blur(4px);
                        z-index: 10000;
                        animation: fadeIn 0.3s ease;
                    }
                    
                    .onboarding-progress {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        height: 8px;
                        background: rgba(255, 255, 255, 0.1);
                        z-index: 10001;
                    }
                    
                    .onboarding-progress-bar {
                        height: 100%;
                        background: linear-gradient(90deg, #6B9B7E, #8FB299);
                        transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: none;
                    }
                    
                    .onboarding-skip {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: linear-gradient(135deg, rgba(245, 101, 101, 0.95), rgba(239, 68, 68, 0.95));
                        border: 3px solid rgba(255, 255, 255, 1);
                        color: white;
                        padding: 14px 28px;
                        border-radius: 50px;
                        cursor: pointer;
                        z-index: 10002;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        font-weight: 700;
                        font-size: 16px;
                        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4), 0 0 0 3px rgba(239, 68, 68, 0.1);
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    
                    .onboarding-skip:hover {
                        background: linear-gradient(135deg, rgba(220, 38, 38, 1), rgba(185, 28, 28, 1));
                        transform: translateY(-3px) scale(1.05);
                        box-shadow: 0 12px 30px rgba(239, 68, 68, 0.5), 0 0 0 5px rgba(239, 68, 68, 0.15);
                    }
                    
                    .onboarding-spotlight {
                        position: absolute;
                        border: 2px solid #6B9B7E;
                        border-radius: 12px;
                        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
                        pointer-events: none;
                        animation: pulse 2s infinite;
                    }
                    
                    @keyframes pulse {
                        0%, 100% { border-color: #6B9B7E; opacity: 0.8; }
                        50% { border-color: #8FB299; opacity: 1; }
                    }
                    
                    .onboarding-tooltip {
                        position: absolute;
                        background: linear-gradient(135deg, #FFFFFF, #F8FFF8);
                        border: 4px solid #58CC02;
                        border-radius: 24px;
                        padding: 36px;
                        max-width: 460px;
                        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3),
                                    0 0 0 2px rgba(88, 204, 2, 0.15),
                                    0 10px 30px rgba(88, 204, 2, 0.2),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.9);
                        animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                        z-index: 10003;
                    }
                    
                    @keyframes bounceIn {
                        0% { transform: scale(0.3); opacity: 0; }
                        50% { transform: scale(1.05); }
                        70% { transform: scale(0.9); }
                        100% { transform: scale(1); opacity: 1; }
                    }
                    
                    [data-theme="dark"] .onboarding-tooltip {
                        background: linear-gradient(135deg, #2A3F2A, #1F3A1F);
                        color: #FFFFFF;
                        border-color: #8FE537;
                        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5),
                                    0 0 0 2px rgba(143, 229, 55, 0.2),
                                    0 10px 30px rgba(143, 229, 55, 0.25),
                                    inset 0 1px 0 rgba(255, 255, 255, 0.1);
                    }
                    
                    [data-theme="dark"] .onboarding-title {
                        color: #FFFFFF;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                    }
                    
                    [data-theme="dark"] .onboarding-description {
                        color: #E0E0E0;
                        opacity: 1;
                    }
                    
                    [data-theme="dark"] .onboarding-counter {
                        color: #8FE537;
                        background: rgba(143, 229, 55, 0.15);
                        border-color: #8FE537;
                    }
                    
                    .onboarding-tooltip::before {
                        content: '🌲';
                        position: absolute;
                        top: -20px;
                        right: 30px;
                        font-size: 40px;
                        animation: sway 3s ease-in-out infinite;
                    }
                    
                    @keyframes sway {
                        0%, 100% { transform: rotate(-5deg); }
                        50% { transform: rotate(5deg); }
                    }
                    
                    .onboarding-tooltip-arrow {
                        position: absolute;
                        width: 0;
                        height: 0;
                        border-style: solid;
                        border-width: 15px;
                        border-color: transparent;
                    }
                    
                    .onboarding-tooltip-arrow.top {
                        bottom: 100%;
                        left: 50%;
                        transform: translateX(-50%);
                        border-bottom-color: #58CC02;
                    }
                    
                    .onboarding-tooltip-arrow.bottom {
                        top: 100%;
                        left: 50%;
                        transform: translateX(-50%);
                        border-top-color: #58CC02;
                    }
                    
                    .onboarding-title {
                        margin: 0 0 16px 0;
                        font-size: 32px;
                        font-weight: 800;
                        color: #0A2818;
                        letter-spacing: -0.5px;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
                        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                    }
                    
                    .onboarding-title::before {
                        content: '✨';
                        font-size: 28px;
                    }
                    
                    .onboarding-description {
                        margin: 0 0 24px 0;
                        line-height: 1.8;
                        color: #0A2818;
                        font-size: 18px;
                        font-weight: 500;
                        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
                        opacity: 0.9;
                    }
                    
                    .onboarding-actions {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 16px;
                    }
                    
                    .onboarding-counter {
                        font-size: 14px;
                        font-weight: 700;
                        color: #58CC02;
                        background: rgba(88, 204, 2, 0.1);
                        padding: 6px 14px;
                        border-radius: 20px;
                        border: 2px solid #58CC02;
                    }
                    
                    .onboarding-prev, .onboarding-next {
                        padding: 12px 24px !important;
                        border-radius: 50px !important;
                        font-weight: 700 !important;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    }
                    
                    .onboarding-next {
                        background: linear-gradient(135deg, #58CC02, #8FE537) !important;
                        border: 3px solid transparent !important;
                        color: white !important;
                        box-shadow: 0 4px 15px rgba(88, 204, 2, 0.4) !important;
                    }
                    
                    .onboarding-next:hover {
                        transform: translateY(-2px) scale(1.05) !important;
                        box-shadow: 0 6px 20px rgba(88, 204, 2, 0.5) !important;
                    }
                    
                    .onboarding-prev {
                        background: transparent !important;
                        border: 3px solid #58CC02 !important;
                        color: #58CC02 !important;
                    }
                    
                    .onboarding-prev:hover {
                        background: rgba(88, 204, 2, 0.1) !important;
                    }
                    
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes slideUp {
                        from { transform: translateY(20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(styles);
            }
        },
        
        showStep(index) {
            if (index >= this.steps.length || index < 0) return;
            
            const step = this.steps[index];
            this.currentStep = index;
            
            // Update progress
            const progress = ((index + 1) / this.steps.length) * 100;
            this.overlay.querySelector('.onboarding-progress-bar').style.width = `${progress}%`;
            
            // Update content
            this.overlay.querySelector('.onboarding-title').textContent = step.title;
            this.overlay.querySelector('.onboarding-description').textContent = step.description;
            this.overlay.querySelector('.onboarding-counter').textContent = `${index + 1} of ${this.steps.length}`;
            
            // Update buttons
            this.overlay.querySelector('.onboarding-prev').style.display = index === 0 ? 'none' : 'inline-block';
            const nextBtn = this.overlay.querySelector('.onboarding-next');
            nextBtn.textContent = index === this.steps.length - 1 ? 'Finish' : 'Next';
            
            // Position spotlight and tooltip
            if (step.element) {
                const element = document.querySelector(step.element);
                if (element) {
                    this.highlightElement(element, step.position || 'bottom');
                }
            } else {
                // Center tooltip if no element
                this.centerTooltip();
            }
            
            // Execute callback if provided
            if (step.onShow) {
                step.onShow();
            }
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('onboarding:step', { detail: { step: index } }));
        },
        
        highlightElement(element, position) {
            const rect = element.getBoundingClientRect();
            const spotlight = this.overlay.querySelector('.onboarding-spotlight');
            const tooltip = this.overlay.querySelector('.onboarding-tooltip');
            
            // Show spotlight
            spotlight.style.display = 'block';
            
            // Account for scroll position
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
            
            // Position spotlight
            spotlight.style.left = `${rect.left + scrollLeft - 4}px`;
            spotlight.style.top = `${rect.top + scrollTop - 4}px`;
            spotlight.style.width = `${rect.width + 8}px`;
            spotlight.style.height = `${rect.height + 8}px`;
            spotlight.style.position = 'absolute';
            
            // Clear any transform on tooltip
            tooltip.style.transform = 'none';
            
            // Position tooltip
            setTimeout(() => {
                const tooltipRect = tooltip.getBoundingClientRect();
                let tooltipX = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                let tooltipY;
                
                if (position === 'top') {
                    tooltipY = rect.top - tooltipRect.height - 20;
                } else {
                    tooltipY = rect.bottom + 20;
                }
                
                // Keep tooltip in viewport
                tooltipX = Math.max(20, Math.min(tooltipX, window.innerWidth - tooltipRect.width - 20));
                tooltipY = Math.max(20, Math.min(tooltipY, window.innerHeight - tooltipRect.height - 20));
                
                tooltip.style.position = 'fixed';
                tooltip.style.left = `${tooltipX}px`;
                tooltip.style.top = `${tooltipY}px`;
                
                // Update arrow
                const arrow = tooltip.querySelector('.onboarding-tooltip-arrow');
                arrow.className = `onboarding-tooltip-arrow ${position}`;
            }, 50);
        },
        
        centerTooltip() {
            const tooltip = this.overlay.querySelector('.onboarding-tooltip');
            const spotlight = this.overlay.querySelector('.onboarding-spotlight');
            
            // Hide spotlight
            spotlight.style.display = 'none';
            
            // Center tooltip
            tooltip.style.left = '50%';
            tooltip.style.top = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
        },
        
        nextStep() {
            if (this.currentStep === this.steps.length - 1) {
                this.complete();
            } else {
                this.showStep(this.currentStep + 1);
            }
        },
        
        previousStep() {
            this.showStep(this.currentStep - 1);
        },
        
        complete() {
            // Mark as complete
            localStorage.setItem('btt-onboarding-complete', 'true');
            
            // Remove overlay
            this.overlay.remove();
            this.overlay = null;
            
            // Show success message
            BTT.Toast?.show('Welcome to BeyondTrailTales! 🎉', 'success', 3000);
            
            // Dispatch event
            document.dispatchEvent(new CustomEvent('onboarding:complete'));
        },
        
        reset() {
            localStorage.removeItem('btt-onboarding-complete');
            this.currentStep = 0;
        }
    };

    /**
     * Notification Center
     * Manages in-app notifications
     */
    BTT.NotificationCenter = {
        notifications: [],
        unreadCount: 0,
        container: null,
        
        init() {
            this.createBellIcon();
            this.loadNotifications();
        },
        
        createBellIcon() {
            // Add bell icon to navigation if exists
            const nav = document.querySelector('.nav-menu');
            if (nav && !document.querySelector('.notification-bell')) {
                const bellItem = document.createElement('li');
                bellItem.className = 'nav-item';
                bellItem.innerHTML = `
                    <button class="notification-bell nav-link" aria-label="Notifications">
                        <i class="fas fa-bell nav-icon"></i>
                        <span class="notification-badge" style="display: none;">0</span>
                    </button>
                    <div class="notification-dropdown" style="display: none;">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button class="notification-clear">Clear All</button>
                        </div>
                        <div class="notification-list"></div>
                        <div class="notification-footer">
                            <a href="#" class="notification-view-all">View All Notifications</a>
                        </div>
                    </div>
                `;
                
                nav.appendChild(bellItem);
                
                // Add event listeners
                const bell = bellItem.querySelector('.notification-bell');
                const dropdown = bellItem.querySelector('.notification-dropdown');
                const clearBtn = bellItem.querySelector('.notification-clear');
                
                bell.addEventListener('click', () => this.toggleDropdown());
                clearBtn.addEventListener('click', () => this.clearAll());
                
                // Close on outside click
                document.addEventListener('click', (e) => {
                    if (!bellItem.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });
                
                this.container = bellItem;
            }
        },
        
        loadNotifications() {
            // Load from localStorage or API
            const stored = localStorage.getItem('btt-notifications');
            if (stored) {
                this.notifications = JSON.parse(stored);
                this.updateDisplay();
            }
        },
        
        add(notification) {
            // Add timestamp if not provided
            if (!notification.timestamp) {
                notification.timestamp = Date.now();
            }
            
            // Add unique ID
            notification.id = `notif-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            
            // Add to beginning of array
            this.notifications.unshift(notification);
            
            // Limit to 20 notifications
            if (this.notifications.length > 20) {
                this.notifications = this.notifications.slice(0, 20);
            }
            
            // Increment unread if not read
            if (!notification.read) {
                this.unreadCount++;
            }
            
            // Save and update
            this.save();
            this.updateDisplay();
            
            // Show toast for new notification
            BTT.Toast?.show(notification.title, notification.type || 'info', 3000);
            
            return notification;
        },
        
        markAsRead(id) {
            const notification = this.notifications.find(n => n.id === id);
            if (notification && !notification.read) {
                notification.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.save();
                this.updateDisplay();
            }
        },
        
        markAllAsRead() {
            this.notifications.forEach(n => n.read = true);
            this.unreadCount = 0;
            this.save();
            this.updateDisplay();
        },
        
        remove(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index > -1) {
                const notification = this.notifications[index];
                if (!notification.read) {
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                this.notifications.splice(index, 1);
                this.save();
                this.updateDisplay();
            }
        },
        
        clearAll() {
            if (confirm('Clear all notifications?')) {
                this.notifications = [];
                this.unreadCount = 0;
                this.save();
                this.updateDisplay();
                BTT.Toast?.show('All notifications cleared', 'success');
            }
        },
        
        toggleDropdown() {
            if (!this.container) return;
            
            const dropdown = this.container.querySelector('.notification-dropdown');
            const isVisible = dropdown.style.display !== 'none';
            
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Mark visible notifications as read after delay
                setTimeout(() => {
                    this.markAllAsRead();
                }, 2000);
            }
        },
        
        updateDisplay() {
            if (!this.container) return;
            
            // Update badge
            const badge = this.container.querySelector('.notification-badge');
            if (badge) {
                badge.textContent = this.unreadCount;
                badge.style.display = this.unreadCount > 0 ? 'inline-block' : 'none';
            }
            
            // Update list
            const list = this.container.querySelector('.notification-list');
            if (list) {
                if (this.notifications.length === 0) {
                    list.innerHTML = '<div class="notification-empty">No notifications</div>';
                } else {
                    list.innerHTML = this.notifications.slice(0, 5).map(n => `
                        <div class="notification-item ${n.read ? 'read' : 'unread'}" data-id="${n.id}">
                            <div class="notification-icon notification-${n.type || 'info'}">
                                <i class="fas fa-${this.getIcon(n.type)}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">${n.title}</div>
                                <div class="notification-message">${n.message || ''}</div>
                                <div class="notification-time">${this.formatTime(n.timestamp)}</div>
                            </div>
                            <button class="notification-close" data-id="${n.id}">×</button>
                        </div>
                    `).join('');
                    
                    // Add event listeners
                    list.querySelectorAll('.notification-close').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            this.remove(btn.dataset.id);
                        });
                    });
                    
                    list.querySelectorAll('.notification-item').forEach(item => {
                        item.addEventListener('click', () => {
                            this.markAsRead(item.dataset.id);
                            if (item.dataset.url) {
                                window.location.href = item.dataset.url;
                            }
                        });
                    });
                }
            }
        },
        
        getIcon(type) {
            const icons = {
                success: 'check-circle',
                warning: 'exclamation-triangle',
                error: 'times-circle',
                info: 'info-circle',
                message: 'envelope',
                achievement: 'trophy'
            };
            return icons[type] || 'bell';
        },
        
        formatTime(timestamp) {
            const now = Date.now();
            const diff = now - timestamp;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            if (days < 7) return `${days}d ago`;
            
            return new Date(timestamp).toLocaleDateString();
        },
        
        save() {
            localStorage.setItem('btt-notifications', JSON.stringify(this.notifications));
        }
    };

    /**
     * Initialize extended components
     */
    BTT.initExtended = function() {
        // Initialize dark mode
        BTT.DarkMode.init();
        
        // Initialize onboarding
        BTT.Onboarding.init();
        
        // Initialize notification center
        BTT.NotificationCenter.init();
        
        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('btt:extended:ready'));
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', BTT.initExtended);
    } else {
        BTT.initExtended();
    }

})(window, document);
