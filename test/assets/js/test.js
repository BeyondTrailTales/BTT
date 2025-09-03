/**
 * Test Suite JavaScript Utilities
 * Common functions for all test pages
 */

// Test Suite Namespace
const BTTTest = {
    // API Base URL
    apiUrl: '/BTT/api',
    testApiUrl: '/BTT/test/api',
    
    // Storage keys
    storageKeys: {
        theme: 'btt-test-theme',
        recentTests: 'btt-recent-tests',
        apiHistory: 'btt-api-history'
    },
    
    // Initialization
    init() {
        this.setupAccessibility();
        this.setupKeyboardNav();
        this.loadTheme();
        console.log('BTT Test Suite initialized');
    },
    
    // Accessibility Setup
    setupAccessibility() {
        // Add ARIA live region for announcements
        if (!document.getElementById('test-announcer')) {
            const announcer = document.createElement('div');
            announcer.id = 'test-announcer';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'visually-hidden';
            document.body.appendChild(announcer);
        }
    },
    
    // Keyboard Navigation
    setupKeyboardNav() {
        document.addEventListener('keydown', (e) => {
            // Alt + T: Go to test dashboard
            if (e.altKey && e.key === 't') {
                window.location.href = '/BTT/test/';
            }
            // Alt + A: Run accessibility audit
            if (e.altKey && e.key === 'a') {
                this.runAccessibilityAudit();
            }
        });
    },
    
    // Theme Management
    loadTheme() {
        const savedTheme = localStorage.getItem(this.storageKeys.theme);
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    },
    
    toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'forest' ? 'forest-dark' : 'forest';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem(this.storageKeys.theme, newTheme);
        this.announce(`Theme changed to ${newTheme}`);
    },
    
    // Accessibility Announcements
    announce(message) {
        const announcer = document.getElementById('test-announcer');
        if (announcer) {
            announcer.textContent = message;
            setTimeout(() => announcer.textContent = '', 1000);
        }
    },
    
    // Run Accessibility Audit with Axe
    async runAccessibilityAudit() {
        if (typeof axe === 'undefined') {
            this.showAlert('Axe accessibility tool is not loaded', 'error');
            return;
        }
        
        this.showAlert('Running accessibility audit...', 'info');
        
        try {
            const results = await axe.run();
            this.displayAuditResults(results);
        } catch (error) {
            this.showAlert('Failed to run accessibility audit: ' + error.message, 'error');
        }
    },
    
    // Display Accessibility Audit Results
    displayAuditResults(results) {
        const violations = results.violations;
        const passes = results.passes;
        
        let message = `Accessibility Audit Complete:\n`;
        message += `✅ ${passes.length} rules passed\n`;
        
        if (violations.length > 0) {
            message += `❌ ${violations.length} violations found:\n`;
            violations.forEach(violation => {
                message += `- ${violation.description} (${violation.nodes.length} instances)\n`;
            });
            
            // Log detailed results to console
            console.group('Accessibility Violations');
            violations.forEach(violation => {
                console.error(violation);
            });
            console.groupEnd();
            
            this.showAlert(message, 'warning');
        } else {
            message += '🎉 No violations found!';
            this.showAlert(message, 'success');
        }
        
        // Save results
        this.saveAuditResults(results);
    },
    
    // Save Audit Results
    saveAuditResults(results) {
        const timestamp = new Date().toISOString();
        const report = {
            timestamp,
            url: window.location.href,
            violations: results.violations.length,
            passes: results.passes.length,
            details: results
        };
        
        // Send to server
        fetch('/BTT/test/runner/save-audit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(report)
        }).catch(error => {
            console.error('Failed to save audit results:', error);
        });
    },
    
    // Show Alert
    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        
        // Add to page
        const container = document.querySelector('.test-main .container') || document.body;
        container.insertBefore(alert, container.firstChild);
        
        // Announce for screen readers
        this.announce(message);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    },
    
    // API Request Helper
    async apiRequest(endpoint, options = {}) {
        const url = `${this.apiUrl}/${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        try {
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();
            
            // Save to history
            this.saveApiHistory(endpoint, options, response, data);
            
            return data;
        } catch (error) {
            this.showAlert(`API Error: ${error.message}`, 'error');
            throw error;
        }
    },
    
    // Save API History
    saveApiHistory(endpoint, options, response, data) {
        const history = JSON.parse(localStorage.getItem(this.storageKeys.apiHistory) || '[]');
        history.unshift({
            timestamp: new Date().toISOString(),
            endpoint,
            method: options.method || 'GET',
            status: response.status,
            success: response.ok,
            data: data
        });
        
        // Keep only last 50 requests
        if (history.length > 50) {
            history.pop();
        }
        
        localStorage.setItem(this.storageKeys.apiHistory, JSON.stringify(history));
    },
    
    // Format JSON for Display
    formatJSON(obj) {
        return JSON.stringify(obj, null, 2);
    },
    
    // Copy to Clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showAlert('Copied to clipboard!', 'success');
        } catch (error) {
            this.showAlert('Failed to copy to clipboard', 'error');
        }
    },
    
    // Seed Test Data
    async seedTestData() {
        if (!confirm('This will add test data to your database. Continue?')) {
            return;
        }
        
        this.showAlert('Seeding test data...', 'info');
        
        try {
            const response = await fetch('/BTT/test/seed.php');
            const result = await response.text();
            this.showAlert('Test data seeded successfully!', 'success');
            console.log(result);
        } catch (error) {
            this.showAlert('Failed to seed test data: ' + error.message, 'error');
        }
    },
    
    // Clear Test Data
    async clearTestData() {
        if (!confirm('This will clear all test data. Are you sure?')) {
            return;
        }
        
        this.showAlert('Clearing test data...', 'info');
        
        try {
            const response = await fetch('/BTT/test/cleanup.php');
            const result = await response.text();
            this.showAlert('Test data cleared successfully!', 'success');
            console.log(result);
        } catch (error) {
            this.showAlert('Failed to clear test data: ' + error.message, 'error');
        }
    },
    
    // Run All Tests
    async runAllTests() {
        this.showAlert('Running all tests...', 'info');
        
        try {
            const response = await fetch('/BTT/test/runner/run.php?suite=all');
            const results = await response.json();
            
            const passed = results.tests.filter(t => t.passed).length;
            const failed = results.tests.filter(t => !t.passed).length;
            
            if (failed === 0) {
                this.showAlert(`All ${passed} tests passed!`, 'success');
            } else {
                this.showAlert(`${passed} passed, ${failed} failed`, 'warning');
            }
            
            // Display detailed results
            console.table(results.tests);
        } catch (error) {
            this.showAlert('Failed to run tests: ' + error.message, 'error');
        }
    },
    
    // Performance Timer
    startTimer(name) {
        performance.mark(`${name}-start`);
    },
    
    endTimer(name) {
        performance.mark(`${name}-end`);
        performance.measure(name, `${name}-start`, `${name}-end`);
        const measure = performance.getEntriesByName(name)[0];
        return measure ? measure.duration : 0;
    },
    
    // Format File Size
    formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    BTTTest.init();
});

// Export for use in other scripts
window.BTTTest = BTTTest;
