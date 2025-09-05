/**
 * BeyondTrailTales Navigation JavaScript
 * Handles navigation dropdowns and authentication
 */

(function() {
    'use strict';

    // Navigation controller object
    window.BTTNav = {
        // Initialize navigation handlers
        init: function() {
            this.initUserDropdown();
            this.initLogoutHandler();
            this.initMobileMenu();
        },

        // Initialize user dropdown menu with modern compatibility
        initUserDropdown: function() {
            // Support both legacy and modern selectors
            const userButton = document.querySelector('.dropdown-trigger') || 
                              document.querySelector('.modern-dropdown-trigger') ||
                              document.querySelector('[data-dropdown="trigger"]');
            const userMenu = document.querySelector('.dropdown-menu') || 
                            document.querySelector('.modern-dropdown-menu') ||
                            document.querySelector('[data-dropdown="menu"]');
            
            if (!userButton || !userMenu) return;
            
            // Apply modern classes for enhanced styling
            if (window.BTTCompat) {
                window.BTTCompat.applyModernClasses(userButton, 'dropdown-trigger');
                window.BTTCompat.applyModernClasses(userMenu, 'dropdown-menu');
            }
            
            // Toggle dropdown on button click
            userButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleDropdown(userButton, userMenu);
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!userButton.contains(e.target) && !userMenu.contains(e.target)) {
                    this.closeDropdown(userButton, userMenu);
                }
            });
            
            // Handle escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && userButton.getAttribute('aria-expanded') === 'true') {
                    this.closeDropdown(userButton, userMenu);
                    userButton.focus();
                }
            });
        },

        // Toggle dropdown state with modern animations
        toggleDropdown: function(button, menu) {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', !isExpanded);
            menu.hidden = isExpanded;
            
            // Modern dropdown classes with fallbacks
            if (!isExpanded) {
                menu.classList.add('active', 'modern-dropdown-active', 'dropdown-show');
                menu.style.display = 'block';
                
                // Modern animation timing
                requestAnimationFrame(() => {
                    menu.classList.add('dropdown-visible');
                });
            } else {
                menu.classList.remove('active', 'modern-dropdown-active', 'dropdown-show', 'dropdown-visible');
                menu.classList.add('dropdown-hide');
                
                // Clean up after animation
                setTimeout(() => {
                    menu.style.display = 'none';
                    menu.classList.remove('dropdown-hide');
                }, 200);
            }
        },

        // Close dropdown
        closeDropdown: function(button, menu) {
            button.setAttribute('aria-expanded', 'false');
            menu.hidden = true;
            menu.classList.remove('active');
        },

        // Initialize logout form handler
        initLogoutHandler: function() {
            const logoutForm = document.querySelector('.logout-form');
            if (!logoutForm) return;
            
            // Get the logout button within the form
            const logoutBtn = logoutForm.querySelector('.logout-btn');
            
            // Handle form submission
            logoutForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                console.log('Logout initiated');
                
                // Add loading state
                logoutForm.classList.add('loading');
                if (logoutBtn) {
                    logoutBtn.disabled = true;
                    logoutBtn.textContent = 'Logging out...';
                }
                
                try {
                    // Get CSRF token
                    const csrfToken = logoutForm.querySelector('[name="csrf_token"]')?.value || '';
                    console.log('CSRF Token found:', csrfToken ? 'Yes (' + csrfToken.substring(0, 10) + '...)' : 'No');
                    
                    // Debug: Check if we have the BTT global object
                    if (!window.BTT) {
                        console.error('BTT global object not found!');
                        // Fallback to direct logout
                        window.location.href = logoutForm.action;
                        return;
                    }
                    
                    // Prepare the request
                    const response = await fetch(logoutForm.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({
                            csrf_token: csrfToken
                        }),
                        credentials: 'same-origin'
                    });
                    
                    console.log('Logout response status:', response.status);
                    
                    // Parse response
                    const data = await response.json();
                    console.log('Logout response data:', data);
                    
                    if (data.success) {
                        // Show success message
                        if (window.BTTUtils && window.BTTUtils.showToast) {
                            window.BTTUtils.showToast('Logout successful! Redirecting...', 'success');
                        }
                        
                        // Use redirect URL from response if available, default to homepage
                        const redirectUrl = data.data?.redirect || data.redirect || window.BTT.publicUrl + '/';
                        
                        // Redirect to homepage after short delay
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 500);
                    } else {
                        throw new Error(data.message || data.error || 'Logout failed');
                    }
                    
                } catch (error) {
                    console.error('Logout error:', error);
                    
                    // Show error message
                    if (window.BTTUtils && window.BTTUtils.showToast) {
                        window.BTTUtils.showToast('Using fallback logout method...', 'warning');
                    }
                    
                    // Reset button state
                    logoutForm.classList.remove('loading');
                    if (logoutBtn) {
                        logoutBtn.disabled = false;
                        logoutBtn.textContent = 'Logout';
                    }
                    
                    // Fallback: Use GET request to logout
                    // This will work even if CSRF validation fails
                    setTimeout(() => {
                        console.log('Attempting fallback logout via GET...');
                        window.location.href = logoutForm.action;
                    }, 1000);
                }
            });
            
            // Alternative: Handle click on logout button directly
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    // Let the form submission handler take care of it
                    // This ensures the form's submit event is triggered
                });
            }
        },

        // Initialize mobile menu
        initMobileMenu: function() {
            const mobileToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-nav-menu');
            
            if (!mobileToggle || !mobileMenu) return;
            
            mobileToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
                mobileToggle.setAttribute('aria-expanded', !isExpanded);
                mobileMenu.setAttribute('aria-hidden', isExpanded);
                mobileMenu.classList.toggle('active');
            });
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (window.BTTNav && typeof window.BTTNav.init === 'function') {
                window.BTTNav.init();
            }
        });
    } else {
        if (window.BTTNav && typeof window.BTTNav.init === 'function') {
            window.BTTNav.init();
        }
    }

    // Re-initialize if content is dynamically loaded
    document.addEventListener('navigation:reinit', () => {
        if (window.BTTNav && typeof window.BTTNav.init === 'function') {
            window.BTTNav.init();
        }
    });

})();
