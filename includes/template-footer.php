<?php
// Get the uxRefreshEnabled flag from session/cookie
$uxRefreshEnabled = $_SESSION['ux_refresh'] ?? $_COOKIE['ux_refresh'] ?? true;
?>
            </div><!-- .container -->
        </main><!-- #main-content -->
        
        <!-- Footer -->
        <footer class="site-footer" role="contentinfo">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-brand">
                        <span class="footer-logo">🌲</span>
                        <h3>BeyondTrailTales</h3>
                        <p>Your trail companion for planning adventures and managing gear</p>
                    </div>
                    
                    <div class="footer-links">
                        <div class="footer-column">
                            <h4>Quick Links</h4>
                            <ul>
                                <li><a href="<?php echo route_url(); ?>">Trailhead</a></li>
                                <li><a href="<?php echo route_url('trips'); ?>">Plan Trip</a></li>
                                <li><a href="<?php echo route_url('backpacks'); ?>">Pack & Gear</a></li>
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
    
    <?php if ($uxRefreshEnabled): ?>
    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav" role="navigation" aria-label="Mobile navigation">
        <a href="<?php echo route_url('dashboard'); ?>" class="mobile-nav-item <?php echo active_class('dashboard'); ?>">
            <span class="mobile-nav-icon" aria-hidden="true">🏔️</span>
            <span class="mobile-nav-label">Trailhead</span>
        </a>
        <a href="<?php echo route_url('trips'); ?>" class="mobile-nav-item <?php echo active_class('trips'); ?>">
            <span class="mobile-nav-icon" aria-hidden="true">🗺️</span>
            <span class="mobile-nav-label">Trips</span>
        </a>
        <button class="mobile-nav-item nav-quick-add" aria-label="Quick actions menu" onclick="UX.quickAdd.toggle()">
            <span class="mobile-nav-icon" aria-hidden="true">➕</span>
        </button>
        <a href="<?php echo route_url('backpacks'); ?>" class="mobile-nav-item <?php echo active_class('backpacks'); ?>">
            <span class="mobile-nav-icon" aria-hidden="true">🎒</span>
            <span class="mobile-nav-label">Packs</span>
        </a>
        <a href="<?php echo route_url('gear'); ?>" class="mobile-nav-item <?php echo active_class('gear'); ?>">
            <span class="mobile-nav-icon" aria-hidden="true">📦</span>
            <span class="mobile-nav-label">Gear</span>
        </a>
    </nav>
    <?php endif; ?>
    
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
    <script src="<?php echo asset_url('js/btt-utils.js'); ?>?v=<?php echo time(); ?>"></script>
    <script src="<?php echo asset_url('js/api.js'); ?>?v=<?php echo time(); ?>"></script>
    
    <!-- Load utilities and app scripts -->
    <script src="<?php echo asset_url('js/app.js'); ?>?v=<?php echo time(); ?>"></script>
    <!-- UX Refresh UI Utilities (toasts, accordions, steppers) -->
    <?php if ($uxRefreshEnabled): ?>
    <script src="<?php echo asset_url('js/ux-ui.js'); ?>"></script>
    <?php endif; ?>
    <!-- Navigation handlers now handled by duolingo-forest-nav.js in header -->
    <!-- Duolingo-style notifications and popups -->
    <script src="<?php echo asset_url('js/duo-notifications.js'); ?>"></script>
    <script src="<?php echo asset_url('js/gamification.js'); ?>"></script>
    <!-- Achievement System -->
    <script src="<?php echo asset_url('js/confetti.js'); ?>"></script>
    <script src="<?php echo asset_url('js/achievement-manager.js'); ?>"></script>
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
    
    <!-- Modern Unified JavaScript - Global Application -->
    <script src="<?php echo asset_url('js/btt-state-manager.js'); ?>"></script>
    <script src="<?php echo asset_url('js/btt-compatibility-layer.js'); ?>"></script>
    
    <!-- Page-specific scripts with aggressive cache busting -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
        <script src="<?php echo asset_url($script); ?>?v=<?php echo time(); ?>&bust=<?php echo md5(time() . $script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
</body>
</html>
