/**
 * BeyondTrailTales - Duolingo Forest Navigation JavaScript
 * 
 * Handles all navigation interactions, gamification animations,
 * and responsive behavior for the new theme system
 */

(function() {
    'use strict';
    
    // Navigation state
    let navState = {
        mobileMenuOpen: false,
        quickActionsOpen: false,
        userDropdownOpen: false,
        scrolled: false
    };
    
    // Initialize navigation when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeNavigation);
    } else {
        initializeNavigation();
    }
    
    function initializeNavigation() {
        console.log('🌲 Initializing Duolingo Forest Navigation...');
        
        // Setup mobile menu
        setupMobileMenu();
        
        // Setup dropdowns
        setupDropdowns();
        
        // Setup scroll effects
        setupScrollEffects();
        
        // Setup gamification animations
        setupGamificationAnimations();
        
        // Setup keyboard navigation
        setupKeyboardNavigation();
        
        // Setup section highlighting
        setupSectionHighlighting();
        
        // Apply entrance animations
        applyEntranceAnimations();
        
        console.log('✅ Navigation initialized successfully');
    }
    
    // Mobile Menu Functionality
    function setupMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-nav-toggle');
        const mobileMenu = document.querySelector('.mobile-nav-menu');
        
        if (!mobileToggle || !mobileMenu) return;
        
        mobileToggle.addEventListener('click', function() {
            toggleMobileMenu();
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (navState.mobileMenuOpen && 
                !mobileToggle.contains(e.target) && 
                !mobileMenu.contains(e.target)) {
                closeMobileMenu();
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && navState.mobileMenuOpen) {
                closeMobileMenu();
            }
        });
        
        // Close mobile menu when clicking on navigation links
        const mobileNavLinks = mobileMenu.querySelectorAll('.mobile-nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Add a slight delay to allow for page transition
                setTimeout(() => closeMobileMenu(), 100);
            });
        });
    }
    
    function toggleMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-nav-toggle');
        const mobileMenu = document.querySelector('.mobile-nav-menu');
        
        if (navState.mobileMenuOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    }
    
    function openMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-nav-toggle');
        const mobileMenu = document.querySelector('.mobile-nav-menu');
        
        navState.mobileMenuOpen = true;
        mobileMenu.classList.add('active');
        mobileToggle.setAttribute('aria-expanded', 'true');
        mobileMenu.setAttribute('aria-hidden', 'false');
        
        // Animate mobile menu links
        const links = mobileMenu.querySelectorAll('.mobile-nav-link');
        links.forEach((link, index) => {
            link.style.animationDelay = `${index * 50}ms`;
            link.classList.add('animate-slide-in-right');
        });
        
        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        const mobileToggle = document.querySelector('.mobile-nav-toggle');
        const mobileMenu = document.querySelector('.mobile-nav-menu');
        
        navState.mobileMenuOpen = false;
        mobileMenu.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
        mobileMenu.setAttribute('aria-hidden', 'true');
        
        // Re-enable body scrolling
        document.body.style.overflow = '';
        
        // Remove animation classes
        const links = mobileMenu.querySelectorAll('.mobile-nav-link');
        links.forEach(link => {
            link.classList.remove('animate-slide-in-right');
        });
    }
    
    // Dropdown Functionality
    function setupDropdowns() {
        // Quick Actions Dropdown
        const quickActionsButton = document.querySelector('.quick-actions-trigger');
        const quickActionsMenu = document.querySelector('.quick-actions-menu');
        
        if (quickActionsButton && quickActionsMenu) {
            quickActionsButton.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleQuickActions();
            });
        }
        
        // User Dropdown
        const userDropdownButton = document.querySelector('.dropdown-trigger');
        const userDropdownMenu = document.querySelector('.dropdown-menu');
        
        if (userDropdownButton && userDropdownMenu) {
            userDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleUserDropdown();
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (navState.quickActionsOpen) {
                closeQuickActions();
            }
            if (navState.userDropdownOpen) {
                closeUserDropdown();
            }
        });
        
        // Close dropdowns on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (navState.quickActionsOpen) closeQuickActions();
                if (navState.userDropdownOpen) closeUserDropdown();
            }
        });
    }
    
    function toggleQuickActions() {
        if (navState.quickActionsOpen) {
            closeQuickActions();
        } else {
            openQuickActions();
        }
    }
    
    function openQuickActions() {
        const button = document.querySelector('.quick-actions-trigger');
        const dropdown = document.querySelector('.nav-quick-actions');
        
        navState.quickActionsOpen = true;
        dropdown.classList.add('active');
        button.setAttribute('aria-expanded', 'true');
        
        // Close user dropdown if open
        if (navState.userDropdownOpen) {
            closeUserDropdown();
        }
        
        // Animate quick action items
        const items = dropdown.querySelectorAll('.quick-action-item');
        items.forEach((item, index) => {
            item.style.animationDelay = `${index * 50}ms`;
            item.classList.add('animate-scale-in');
        });
    }
    
    function closeQuickActions() {
        const button = document.querySelector('.quick-actions-trigger');
        const dropdown = document.querySelector('.nav-quick-actions');
        
        if (!button || !dropdown) return;
        
        navState.quickActionsOpen = false;
        dropdown.classList.remove('active');
        button.setAttribute('aria-expanded', 'false');
        
        // Remove animation classes
        const items = dropdown.querySelectorAll('.quick-action-item');
        items.forEach(item => {
            item.classList.remove('animate-scale-in');
        });
    }
    
    function toggleUserDropdown() {
        if (navState.userDropdownOpen) {
            closeUserDropdown();
        } else {
            openUserDropdown();
        }
    }
    
    function openUserDropdown() {
        const button = document.querySelector('.dropdown-trigger');
        const dropdown = document.querySelector('.dropdown');
        
        if (!button || !dropdown) return;
        
        navState.userDropdownOpen = true;
        dropdown.classList.add('active');
        button.setAttribute('aria-expanded', 'true');
        
        // Close quick actions if open
        if (navState.quickActionsOpen) {
            closeQuickActions();
        }
    }
    
    function closeUserDropdown() {
        const button = document.querySelector('.dropdown-trigger');
        const dropdown = document.querySelector('.dropdown');
        
        if (!button || !dropdown) return;
        
        navState.userDropdownOpen = false;
        dropdown.classList.remove('active');
        button.setAttribute('aria-expanded', 'false');
    }
    
    // Scroll Effects
    function setupScrollEffects() {
        const nav = document.querySelector('.forest-nav');
        if (!nav) return;
        
        let ticking = false;
        
        function updateScrollState() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const shouldBeScrolled = scrollTop > 10;
            
            if (shouldBeScrolled !== navState.scrolled) {
                navState.scrolled = shouldBeScrolled;
                nav.classList.toggle('scrolled', shouldBeScrolled);
            }
            
            ticking = false;
        }
        
        function handleScroll() {
            if (!ticking) {
                requestAnimationFrame(updateScrollState);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', handleScroll, { passive: true });
    }
    
    // Gamification Animations
    function setupGamificationAnimations() {
        // XP Bar Animation
        const xpFill = document.querySelector('.user-xp-fill');
        if (xpFill) {
            // Animate XP bar on load
            const targetWidth = xpFill.style.width || '0%';
            xpFill.style.width = '0%';
            
            setTimeout(() => {
                xpFill.style.transition = 'width 1.5s cubic-bezier(0.4, 0, 0.2, 1)';
                xpFill.style.width = targetWidth;
            }, 500);
        }
        
        // Level Badge Hover Effect
        const levelDisplay = document.querySelector('.user-level-display');
        if (levelDisplay) {
            levelDisplay.addEventListener('mouseenter', function() {
                this.classList.add('animate-duo-bounce');
            });
            
            levelDisplay.addEventListener('animationend', function() {
                this.classList.remove('animate-duo-bounce');
            });
        }
        
        // Nav Section Icon Animations
        const navLinks = document.querySelectorAll('.nav-section-link');
        navLinks.forEach(link => {
            const icon = link.querySelector('.nav-section-icon');
            if (icon) {
                link.addEventListener('mouseenter', function() {
                    icon.classList.add('animate-icon-bounce');
                });
                
                link.addEventListener('mouseleave', function() {
                    icon.classList.remove('animate-icon-bounce');
                });
            }
        });
    }
    
    // Keyboard Navigation
    function setupKeyboardNavigation() {
        const navLinks = document.querySelectorAll('.nav-section-link, .mobile-nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
        
        // Dropdown keyboard navigation
        const dropdownItems = document.querySelectorAll('.dropdown-item, .quick-action-item');
        
        dropdownItems.forEach(item => {
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                } else if (e.key === 'Escape') {
                    closeAllDropdowns();
                }
            });
        });
    }
    
    function closeAllDropdowns() {
        if (navState.quickActionsOpen) closeQuickActions();
        if (navState.userDropdownOpen) closeUserDropdown();
        if (navState.mobileMenuOpen) closeMobileMenu();
    }
    
    // Section Highlighting
    function setupSectionHighlighting() {
        const currentPage = document.body.dataset.page;
        if (!currentPage) return;
        
        // Map page IDs to sections
        const pageToSection = {
            'dashboard': 'dashboard',
            'trips': 'adventures',
            'backpacks': 'backpacks',
            'gear': 'gear'
        };
        
        const section = pageToSection[currentPage];
        if (section) {
            // Add section theme class to body
            document.body.classList.add(`theme-${section}`);
            
            // Highlight current section in navigation
            const navLinks = document.querySelectorAll(`[data-section="${section}"]`);
            navLinks.forEach(link => {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            });
        }
    }
    
    // Entrance Animations
    function applyEntranceAnimations() {
        // Animate navigation elements on load
        const navElements = document.querySelectorAll('.nav-section-item');
        navElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 100}ms`;
            element.classList.add('animate-fade-in-up');
        });
        
        // Animate user section
        const userSection = document.querySelector('.nav-user-gamified');
        if (userSection) {
            userSection.style.animationDelay = '400ms';
            userSection.classList.add('animate-fade-in-up');
        }
        
        // Animate brand
        const brand = document.querySelector('.nav-brand');
        if (brand) {
            brand.classList.add('animate-fade-in-up');
        }
    }
    
    // XP Gain Animation (can be called from other scripts)
    window.BTTNav = {
        showXPGain: function(amount) {
            const levelDisplay = document.querySelector('.user-level-display');
            if (!levelDisplay) return;
            
            // Create XP gain notification
            const xpGain = document.createElement('div');
            xpGain.className = 'xp-gain-notification';
            xpGain.textContent = `+${amount} XP`;
            xpGain.setAttribute('data-xp', amount);
            
            levelDisplay.appendChild(xpGain);
            
            // Animate XP gain
            xpGain.classList.add('animate-xp-gain');
            
            // Remove after animation
            setTimeout(() => {
                if (xpGain.parentNode) {
                    xpGain.parentNode.removeChild(xpGain);
                }
            }, 2000);
        },
        
        levelUp: function(newLevel) {
            const levelNumber = document.querySelector('.user-level-number');
            if (!levelNumber) return;
            
            // Update level number
            levelNumber.textContent = `Level ${newLevel}`;
            
            // Play level up animation
            levelNumber.classList.add('animate-level-up');
            
            // Show celebration particles
            showCelebrationParticles();
            
            setTimeout(() => {
                levelNumber.classList.remove('animate-level-up');
            }, 1000);
        },
        
        updateXP: function(currentXP, totalXP) {
            const xpFill = document.querySelector('.user-xp-fill');
            if (!xpFill) return;
            
            const percentage = (currentXP / totalXP) * 100;
            
            xpFill.style.transition = 'width 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
            xpFill.style.width = `${percentage}%`;
        }
    };
    
    function showCelebrationParticles() {
        const container = document.createElement('div');
        container.className = 'celebration-particles';
        document.body.appendChild(container);
        
        // Create particles
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 2 + 's';
            container.appendChild(particle);
        }
        
        // Remove particles after animation
        setTimeout(() => {
            if (container.parentNode) {
                container.parentNode.removeChild(container);
            }
        }, 4000);
    }
    
    // Handle resize events
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Close mobile menu if screen becomes large
            if (window.innerWidth > 768 && navState.mobileMenuOpen) {
                closeMobileMenu();
            }
        }, 250);
    });
    
    // Global click handler for closing dropdowns
    document.addEventListener('click', function(e) {
        // Don't close if clicking inside a dropdown
        if (e.target.closest('.nav-quick-actions') || 
            e.target.closest('.dropdown') || 
            e.target.closest('.mobile-nav-menu')) {
            return;
        }
        
        closeAllDropdowns();
    });
    
})();