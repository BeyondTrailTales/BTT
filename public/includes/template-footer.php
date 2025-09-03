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
    </script>
    
    <!-- Load utilities and app scripts -->
    <script src="<?php echo asset_url('js/app.js'); ?>"></script>
    <script src="<?php echo asset_url('js/gamification.js'); ?>"></script>
    <!-- Loading Animations and Transitions -->
    <script src="<?php echo asset_url('js/loading-transitions.js'); ?>"></script>
    <!-- Toast Notifications System -->
    <script src="<?php echo asset_url('js/toast-notifications.js'); ?>"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
        <script src="<?php echo asset_url($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Footer Styles */
        .site-footer {
            background: var(--forest-canopy);
            border-top: 1px solid var(--glass-border);
            padding: var(--space-8) 0 var(--space-4);
            margin-top: auto;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: var(--space-8);
            margin-bottom: var(--space-6);
        }
        
        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }
        
        .footer-logo {
            font-size: 2rem;
        }
        
        .footer-brand h3 {
            color: var(--forest-mint);
            font-size: var(--text-xl);
            margin: 0;
        }
        
        .footer-brand p {
            color: var(--text-secondary);
            font-size: var(--text-sm);
        }
        
        .footer-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--space-6);
        }
        
        .footer-column h4 {
            color: var(--forest-leaf);
            font-size: var(--text-base);
            margin-bottom: var(--space-3);
            font-weight: var(--font-semibold);
        }
        
        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-column li {
            margin-bottom: var(--space-2);
        }
        
        .footer-column a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: var(--text-sm);
            transition: var(--transition-all);
        }
        
        .footer-column a:hover {
            color: var(--forest-mint);
            transform: translateX(2px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: var(--space-4);
            border-top: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: var(--text-sm);
        }
        
        .footer-credits {
            margin-top: var(--space-2);
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
                grid-template-columns: 1fr;
                gap: var(--space-6);
            }
            
            .footer-links {
                grid-template-columns: 1fr;
                gap: var(--space-4);
            }
            
            .footer-brand {
                text-align: center;
            }
        }
    </style>
</body>
</html>
