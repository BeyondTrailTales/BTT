        </div><!-- .container -->
    </main>
    
    <!-- Test Suite Footer -->
    <footer class="test-footer" role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Test Suite Info</h3>
                    <ul class="footer-list">
                        <li>Storage: <strong><?php echo $storageInfo['driver']; ?></strong></li>
                        <li>Tables: <strong><?php echo count($storageInfo['tables']); ?></strong></li>
                        <li>Size: <strong><?php echo number_format($storageInfo['size'] / 1024, 2); ?> KB</strong></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Actions</h3>
                    <ul class="footer-list">
                        <li><a href="/BTT/test/seed.php">Seed Test Data</a></li>
                        <li><a href="/BTT/test/cleanup.php">Clear Test Data</a></li>
                        <li><a href="/BTT/test/docs.php">Documentation</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul class="footer-list">
                        <li><a href="/BTT/public/ui-ux-guide.php">UI/UX Guide</a></li>
                        <li><a href="/BTT/test/runner/">Test Runner</a></li>
                        <li><a href="https://github.com/dequelabs/axe-core" target="_blank" rel="noopener">Axe Accessibility</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>System Status</h3>
                    <div class="status-indicators">
                        <span class="status-item status-success" aria-label="PHP version">
                            PHP <?php echo phpversion(); ?>
                        </span>
                        <span class="status-item status-info" aria-label="Memory usage">
                            Memory: <?php echo round(memory_get_usage() / 1048576, 2); ?> MB
                        </span>
                        <span class="status-item" aria-label="Execution time">
                            Time: <span id="exec-time">0.00</span>s
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> BeyondTrailTales Test Suite. For development use only.</p>
            </div>
        </div>
    </footer>
    
    <!-- Load Axe for accessibility testing (CDN with fallback) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axe-core/4.8.3/axe.min.js" 
            integrity="sha512-Ckj8RpkaBNaJ/OhjNH2JqP3q7xnhXS3xeJRoR5xRFPMVfhnJoyQwUznlp4o5dXqKpfr8PVH5r1nxlnDeP2LtdQ==" 
            crossorigin="anonymous" 
            referrerpolicy="no-referrer"
            onerror="loadAxeFallback()"></script>
    
    <!-- Test Suite Scripts -->
    <script src="/BTT/test/assets/js/test.js"></script>
    
    <!-- Page execution time -->
    <script>
        (function() {
            const startTime = <?php echo microtime(true); ?>;
            const endTime = performance.now() / 1000;
            const execTime = (endTime - startTime).toFixed(3);
            document.getElementById('exec-time').textContent = execTime;
        })();
        
        // Axe fallback loader
        function loadAxeFallback() {
            const script = document.createElement('script');
            script.src = '/BTT/test/assets/js/axe.min.js';
            script.onerror = function() {
                console.warn('Axe accessibility tool could not be loaded');
            };
            document.head.appendChild(script);
        }
        
        // Theme toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'forest' ? 'forest-dark' : 'forest';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('btt-test-theme', newTheme);
        }
        
        // Load saved theme
        (function() {
            const savedTheme = localStorage.getItem('btt-test-theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        })();
        
        // Accessibility: Announce page changes for screen readers
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'visually-hidden';
        document.body.appendChild(announcer);
        
        function announce(message) {
            announcer.textContent = message;
            setTimeout(() => announcer.textContent = '', 1000);
        }
        
        // Log page view
        <?php 
        $logger->info('Test page viewed', [
            'page' => $pageId,
            'storage' => $storageInfo['driver'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        ?>
    </script>
</body>
</html>
