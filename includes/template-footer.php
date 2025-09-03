            </div><!-- .container -->
        </main><!-- #main-content -->
        
        <!-- Footer -->
        <footer class="site-footer" role="contentinfo">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-brand">
                        <span class="footer-logo">🌲</span>
                        <h3>BeyondTrailTales</h3>
                        <p>Your adventure companion for trip planning and backpack management</p>
                    </div>
                    
                    <div class="footer-links">
                        <div class="footer-column">
                            <h4>Quick Links</h4>
                            <ul>
                                <li><a href="<?php echo route_url(); ?>">Dashboard</a></li>
                                <li><a href="<?php echo route_url('trips'); ?>">My Trips</a></li>
                                <li><a href="<?php echo route_url('backpacks'); ?>">My Backpacks</a></li>
                            </ul>
                        </div>
                        
                        <div class="footer-column">
                            <h4>Resources</h4>
                            <ul>
                                <li><a href="<?php echo route_url('public/ui-ux-guide.php'); ?>">Design Guide</a></li>
                                <?php if (BTT_DEBUG): ?>
                                <li><a href="<?php echo route_url('test'); ?>">Test Suite</a></li>
                                <?php endif; ?>
                                <li><a href="<?php echo route_url('api'); ?>">API Documentation</a></li>
                            </ul>
                        </div>
                        
                        <div class="footer-column">
                            <h4>About</h4>
                            <ul>
                                <li>Version <?php echo BTT_APP_VERSION; ?></li>
                                <li>WCAG 2.1 AA Compliant</li>
                                <li>Forest Theme Design</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p>&copy; <?php echo date('Y'); ?> BeyondTrailTales. All rights reserved.</p>
                    <p class="footer-credits">Built with 💚 for adventurers</p>
                </div>
            </div>
        </footer>
    </div><!-- .main-wrapper -->
    
    <!-- Toast Container for Notifications -->
    <div class="toast-container" role="region" aria-live="polite" aria-label="Notifications"></div>
    
    <!-- Core JavaScript -->
    <script>
        // Set global configuration
        window.BTT = {
            baseUrl: "<?php echo BASE_URL; ?>",
            apiUrl: "<?php echo BTT_API_URL; ?>",
            assetsUrl: "<?php echo BTT_ASSETS_URL; ?>",
            publicUrl: "<?php echo BTT_PUBLIC_URL; ?>",
            userId: "<?php echo $_SESSION['user_id'] ?? 'guest'; ?>",
            userName: "<?php echo $_SESSION['user_name'] ?? 'Adventurer'; ?>",
            csrfToken: "<?php echo csrf_token(); ?>",
            debug: <?php echo BTT_DEBUG ? 'true' : 'false'; ?>
        };
        
        // Mobile menu toggle
        function toggleMobileMenu(button) {
            const menu = document.getElementById('mobile-nav-menu');
            const isOpen = menu.classList.contains('active');
            
            if (isOpen) {
                menu.classList.remove('active');
                menu.setAttribute('aria-hidden', 'true');
                button.setAttribute('aria-expanded', 'false');
            } else {
                menu.classList.add('active');
                menu.setAttribute('aria-hidden', 'false');
                button.setAttribute('aria-expanded', 'true');
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('mobile-nav-menu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (menu && toggle && menu.classList.contains('active')) {
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    menu.classList.remove('active');
                    menu.setAttribute('aria-hidden', 'true');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const menu = document.getElementById('mobile-nav-menu');
                const toggle = document.querySelector('.mobile-menu-toggle');
                
                if (menu && toggle && menu.classList.contains('active')) {
                    menu.classList.remove('active');
                    menu.setAttribute('aria-hidden', 'true');
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.focus();
                }
            }
        });
        
        // User dropdown and logout handlers are now in navigation.js
    </script>
    
    <!-- Load jQuery first (required by many components) -->
    <script src="<?php echo BTT_VENDOR_URL; ?>/jquery-3.7.1.min.js"></script>
    
    <!-- Load Sortable.js for drag-and-drop functionality -->
    <script src="<?php echo BTT_VENDOR_URL; ?>/sortable.min.js"></script>
    
    <!-- Load BTT utilities and API client before everything else -->
    <script src="<?php echo asset_url('js/btt-utils.js'); ?>"></script>
    <script src="<?php echo asset_url('js/api.js'); ?>"></script>
    
    <!-- Load utilities and app scripts -->
    <script src="<?php echo asset_url('js/app.js'); ?>"></script>
    <!-- Navigation handlers (dropdowns, logout, etc.) -->
    <script src="<?php echo asset_url('js/navigation.js'); ?>"></script>
    <script src="<?php echo asset_url('js/gamification.js'); ?>"></script>
    <!-- Loading Animations and Transitions -->
    <script src="<?php echo asset_url('js/loading-transitions.js'); ?>"></script>
    <!-- Toast Notifications System -->
    <script src="<?php echo asset_url('js/toast-notifications.js'); ?>"></script>
    
    <!-- Keyboard Shortcuts System -->
    <script src="<?php echo asset_url('js/keyboard-shortcuts.js'); ?>" defer></script>
    
    <!-- Performance Optimizer -->
    <script src="<?php echo asset_url('js/performance-optimizer.js'); ?>" defer></script>
    
    <!-- Accessibility Enhancements -->
    <script src="<?php echo asset_url('js/accessibility-enhancements.js'); ?>" defer></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
        <script src="<?php echo asset_url($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Footer Styles */
        .site-footer {
            background: var(--forest-canopy, #1a3d2e);
            border-top: 1px solid var(--glass-border, rgba(255,255,255,0.1));
            padding: 3rem 0 1.5rem;
            margin-top: auto;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            gap: 3rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .footer-brand {
            flex: 0 0 300px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .footer-logo {
            font-size: 2rem;
            line-height: 1;
        }
        
        .footer-brand h3 {
            color: var(--forest-mint, #52ffb8);
            font-size: 1.5rem;
            margin: 0;
        }
        
        .footer-brand p {
            color: var(--text-secondary, rgba(255,255,255,0.7));
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .footer-links {
            flex: 1;
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            min-width: 0;
        }
        
        .footer-column {
            flex: 1;
            min-width: 150px;
        }
        
        .footer-column h4 {
            color: var(--forest-leaf, #8fff6d);
            font-size: 1rem;
            margin: 0 0 1rem 0;
            font-weight: 600;
        }
        
        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-column li {
            margin-bottom: 0.5rem;
        }
        
        .footer-column a {
            color: var(--text-secondary, rgba(255,255,255,0.7));
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-block;
        }
        
        .footer-column a:hover {
            color: var(--forest-mint, #52ffb8);
            transform: translateX(2px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--glass-border, rgba(255,255,255,0.1));
            color: var(--text-muted, rgba(255,255,255,0.5));
            font-size: 0.875rem;
        }
        
        .footer-bottom p {
            margin: 0.5rem 0;
        }
        
        .footer-credits {
            margin-top: 0.5rem;
            opacity: 0.8;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 80px;
            right: var(--space-4);
            z-index: var(--z-notification, 1000);
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
            pointer-events: none;
        }
        
        .toast {
            pointer-events: auto;
            min-width: 300px;
            max-width: 400px;
        }
        
        /* Responsive Footer */
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                gap: 2rem;
            }
            
            .footer-brand {
                flex: 1 1 auto;
                text-align: center;
                margin-bottom: 1rem;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }
            
            .footer-column {
                margin-bottom: 1rem;
            }
        }
    </style>
</body>
</html>
