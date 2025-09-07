<?php
/**
 * BeyondTrailTales Asset Loader
 * Manages CSS and JS loading with proper cascade order and versioning
 */

class AssetLoader {
    private static $instance = null;
    private $cssQueue = [];
    private $jsQueue = [];
    private $inlineStyles = [];
    private $inlineScripts = [];
    
    // CSS loading priority levels
    const CSS_PRIORITY_RESET = 100;
    const CSS_PRIORITY_VARIABLES = 200;
    const CSS_PRIORITY_BASE = 300;
    const CSS_PRIORITY_LAYOUT = 400;
    const CSS_PRIORITY_COMPONENTS = 500;
    const CSS_PRIORITY_UTILITIES = 600;
    const CSS_PRIORITY_PAGE = 700;
    const CSS_PRIORITY_OVERRIDES = 800;
    const CSS_PRIORITY_LEGACY = 900; // For temporary legacy support
    
    // Core CSS files that should always load
    private $coreCssFiles = [
        // Level 1: Reset and Variables
        ['file' => 'css/core/reset.css', 'priority' => self::CSS_PRIORITY_RESET],
        ['file' => 'css/core/variables.css', 'priority' => self::CSS_PRIORITY_VARIABLES],
        
        // Level 2: Base Styles
        ['file' => 'css/core/typography.css', 'priority' => self::CSS_PRIORITY_BASE],
        ['file' => 'css/core/base.css', 'priority' => self::CSS_PRIORITY_BASE],
        
        // Level 3: Layout
        ['file' => 'css/layouts/container.css', 'priority' => self::CSS_PRIORITY_LAYOUT],
        ['file' => 'css/layouts/grid.css', 'priority' => self::CSS_PRIORITY_LAYOUT],
        ['file' => 'css/layouts/spacing.css', 'priority' => self::CSS_PRIORITY_LAYOUT],
        
        // Level 4: Components
        ['file' => 'css/components/navigation.css', 'priority' => self::CSS_PRIORITY_COMPONENTS],
        ['file' => 'css/components/buttons.css', 'priority' => self::CSS_PRIORITY_COMPONENTS],
        ['file' => 'css/components/forms.css', 'priority' => self::CSS_PRIORITY_COMPONENTS],
        ['file' => 'css/components/cards.css', 'priority' => self::CSS_PRIORITY_COMPONENTS],
        
        // Level 5: Utilities
        ['file' => 'css/utilities/helpers.css', 'priority' => self::CSS_PRIORITY_UTILITIES],
        ['file' => 'css/utilities/animations.css', 'priority' => self::CSS_PRIORITY_UTILITIES],
    ];
    
    private function __construct() {
        // Initialize with core CSS files
        foreach ($this->coreCssFiles as $cssFile) {
            $this->addCss($cssFile['file'], $cssFile['priority']);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Add CSS file to loading queue
     * @param string $file Relative path to CSS file (from assets directory)
     * @param int $priority Loading priority (lower loads first)
     * @param array $attributes Additional attributes for the link tag
     */
    public function addCss($file, $priority = self::CSS_PRIORITY_PAGE, $attributes = []) {
        // Check if file exists in new structure first
        $newPath = $this->checkNewCssPath($file);
        if ($newPath) {
            $file = $newPath;
        }
        
        $this->cssQueue[] = [
            'file' => $file,
            'priority' => $priority,
            'attributes' => $attributes,
            'version' => $this->getFileVersion($file)
        ];
    }
    
    /**
     * Add JavaScript file to loading queue
     * @param string $file Relative path to JS file (from assets directory)
     * @param bool $defer Whether to defer script loading
     * @param array $attributes Additional attributes
     */
    public function addJs($file, $defer = true, $attributes = []) {
        $this->jsQueue[] = [
            'file' => $file,
            'defer' => $defer,
            'attributes' => $attributes,
            'version' => $this->getFileVersion($file)
        ];
    }
    
    /**
     * Add inline CSS
     * @param string $css CSS content
     * @param int $priority Priority for ordering
     */
    public function addInlineCss($css, $priority = self::CSS_PRIORITY_PAGE) {
        $this->inlineStyles[] = [
            'content' => $css,
            'priority' => $priority
        ];
    }
    
    /**
     * Add inline JavaScript
     * @param string $js JavaScript content
     */
    public function addInlineJs($js) {
        $this->inlineScripts[] = $js;
    }
    
    /**
     * Render all CSS links
     * @return string HTML output
     */
    public function renderCss() {
        // Sort CSS by priority
        usort($this->cssQueue, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        // Remove duplicates while preserving order
        $seen = [];
        $uniqueCss = [];
        foreach ($this->cssQueue as $css) {
            if (!isset($seen[$css['file']])) {
                $seen[$css['file']] = true;
                $uniqueCss[] = $css;
            }
        }
        
        $output = '';
        
        // Render CSS links
        foreach ($uniqueCss as $css) {
            $attributes = $css['attributes'];
            $attributes['rel'] = 'stylesheet';
            $attributes['href'] = asset_url($css['file']) . '?v=' . $css['version'];
            
            $output .= '<link';
            foreach ($attributes as $key => $value) {
                $output .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
            $output .= ">\n";
        }
        
        // Render inline styles
        if (!empty($this->inlineStyles)) {
            usort($this->inlineStyles, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });
            
            $output .= "<style>\n";
            foreach ($this->inlineStyles as $style) {
                $output .= $style['content'] . "\n";
            }
            $output .= "</style>\n";
        }
        
        return $output;
    }
    
    /**
     * Render all JavaScript
     * @return string HTML output
     */
    public function renderJs() {
        $output = '';
        
        // Render external scripts
        foreach ($this->jsQueue as $js) {
            $attributes = $js['attributes'];
            $attributes['src'] = asset_url($js['file']) . '?v=' . $js['version'];
            if ($js['defer']) {
                $attributes['defer'] = 'defer';
            }
            
            $output .= '<script';
            foreach ($attributes as $key => $value) {
                if ($value === true) {
                    $output .= ' ' . $key;
                } else {
                    $output .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
                }
            }
            $output .= "></script>\n";
        }
        
        // Render inline scripts
        if (!empty($this->inlineScripts)) {
            $output .= "<script>\n";
            foreach ($this->inlineScripts as $script) {
                $output .= $script . "\n";
            }
            $output .= "</script>\n";
        }
        
        return $output;
    }
    
    /**
     * Get file version for cache busting
     * @param string $file File path
     * @return string Version string
     */
    private function getFileVersion($file) {
        if (defined('BTT_DEBUG') && BTT_DEBUG) {
            // In debug mode, use timestamp for aggressive cache busting
            return time();
        }
        
        // In production, use file modification time or app version
        $fullPath = BTT_ROOT . '/assets/' . $file;
        if (file_exists($fullPath)) {
            return filemtime($fullPath);
        }
        
        return defined('BTT_APP_VERSION') ? BTT_APP_VERSION : '1.0.0';
    }
    
    /**
     * Check if file exists in new CSS structure
     * @param string $file Original file path
     * @return string|false New file path if exists, false otherwise
     */
    private function checkNewCssPath($file) {
        // Map old files to new structure
        $mappings = [
            'css/btt-main.css' => 'css/core/base.css',
            'css/navigation.css' => 'css/components/navigation.css',
            'css/buttons.css' => 'css/components/buttons.css',
            'css/forms.css' => 'css/components/forms.css',
            'css/cards.css' => 'css/components/cards.css',
            // Add more mappings as needed
        ];
        
        if (isset($mappings[$file])) {
            $newPath = $mappings[$file];
            if (file_exists(BTT_ROOT . '/assets/' . $newPath)) {
                return $newPath;
            }
        }
        
        return false;
    }
    
    /**
     * Add page-specific CSS
     * @param string $pageName Name of the page (e.g., 'dashboard', 'trips')
     */
    public function addPageCss($pageName) {
        $pageFile = "css/pages/{$pageName}.css";
        if (file_exists(BTT_ROOT . '/assets/' . $pageFile)) {
            $this->addCss($pageFile, self::CSS_PRIORITY_PAGE);
        }
        
        // Check for legacy file as fallback
        $legacyFile = "css/{$pageName}.css";
        if (file_exists(BTT_ROOT . '/assets/' . $legacyFile)) {
            $this->addCss($legacyFile, self::CSS_PRIORITY_LEGACY);
        }
    }
    
    /**
     * Add temporary legacy CSS support during migration
     * @param array $files Array of legacy CSS files
     */
    public function addLegacyCss($files) {
        foreach ($files as $file) {
            $this->addCss($file, self::CSS_PRIORITY_LEGACY);
        }
    }
    
    /**
     * Clear all queued assets (useful for testing)
     */
    public function clear() {
        $this->cssQueue = [];
        $this->jsQueue = [];
        $this->inlineStyles = [];
        $this->inlineScripts = [];
        
        // Re-add core files
        foreach ($this->coreCssFiles as $cssFile) {
            $this->addCss($cssFile['file'], $cssFile['priority']);
        }
    }
}

// Helper function for easy access
function asset_loader() {
    return AssetLoader::getInstance();
}