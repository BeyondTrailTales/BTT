<?php
/**
 * CSS Migration Analysis Tool
 * Analyzes current CSS files and provides migration recommendations
 * 
 * Usage: php css-migration-analyzer.php [--fix] [--output=report.html]
 */

class CSSMigrationAnalyzer {
    private $cssDir = __DIR__ . '/assets/css/';
    private $issues = [];
    private $stats = [
        'total_files' => 0,
        'total_lines' => 0,
        'important_count' => 0,
        'id_selectors' => 0,
        'deep_nesting' => 0,
        'inline_styles' => 0,
        'duplicate_rules' => 0,
        'file_sizes' => [],
        'migration_status' => []
    ];
    
    private $fileCategories = [
        'core' => ['variables.css', 'reset.css', 'typography.css', 'base.css'],
        'layouts' => ['container.css', 'grid.css', 'spacing.css'],
        'components' => ['buttons', 'cards', 'forms', 'navigation', 'modals'],
        'utilities' => ['helpers.css', 'spacing.css', 'responsive.css'],
        'pages' => ['dashboard', 'trips', 'gear', 'backpacks', 'pack-builder'],
        'legacy' => ['btt-main.css', 'duo', 'forest', 'fix', 'clean']
    ];
    
    private $migrationMap = [];
    
    public function __construct() {
        $this->buildMigrationMap();
    }
    
    private function buildMigrationMap() {
        // Map old files to new structure
        $this->migrationMap = [
            // Core files
            'btt-tokens.css' => 'core/variables.css',
            'btt-reset.css' => 'core/reset.css',
            'btt-base.css' => 'core/base.css',
            'btt-main.css' => 'SPLIT:core/base.css,components/*',
            
            // Component files
            'buttons-premium.css' => 'components/buttons-v2.css',
            'btt-buttons.css' => 'components/buttons-v2.css',
            'components.buttons.css' => 'components/buttons-v2.css',
            
            'cards.css' => 'components/cards-v2.css',
            'btt-cards.css' => 'components/cards-v2.css',
            'components.cards.css' => 'components/cards-v2.css',
            
            // Page files
            'dashboard-clean.css' => 'pages/dashboard-v2.css',
            'dashboard-unified-modern.css' => 'pages/dashboard-v2.css',
            'trips-enhanced.css' => 'pages/trips-v2.css',
            'gear-forest-complete.css' => 'pages/gear-v2.css',
            'backpacks-clean.css' => 'pages/backpacks-v2.css',
            'pack-builder-clean.css' => 'pages/pack-builder-v2.css',
            
            // Files to remove (duplicates/obsolete)
            'dashboard-immediate-fix.css' => 'REMOVE',
            'dashboard-container-fix.css' => 'REMOVE',
            'button-resize-fix.css' => 'REMOVE',
            'nav-scrollbar-fix.css' => 'REMOVE',
            'gear-grid-fix.css' => 'REMOVE',
            'pack-builder-fixes.css' => 'REMOVE'
        ];
    }
    
    public function analyze() {
        echo "CSS Migration Analysis Tool\n";
        echo "==========================\n\n";
        
        $this->scanDirectory($this->cssDir);
        $this->analyzeImportantUsage();
        $this->analyzeSelectors();
        $this->analyzeDuplicates();
        $this->checkMigrationStatus();
        
        return $this->generateReport();
    }
    
    private function scanDirectory($dir, $prefix = '') {
        $files = scandir($dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $fullPath = $dir . '/' . $file;
            $relativePath = $prefix . $file;
            
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $relativePath . '/');
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                $this->analyzeFile($fullPath, $relativePath);
            }
        }
    }
    
    private function analyzeFile($filePath, $relativePath) {
        $this->stats['total_files']++;
        
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        $this->stats['total_lines'] += count($lines);
        $this->stats['file_sizes'][$relativePath] = filesize($filePath);
        
        // Check for issues
        $fileIssues = [];
        
        // Count !important
        $importantCount = preg_match_all('/!important/i', $content, $matches);
        if ($importantCount > 0) {
            $this->stats['important_count'] += $importantCount;
            $fileIssues[] = [
                'type' => 'important',
                'count' => $importantCount,
                'severity' => $importantCount > 50 ? 'high' : ($importantCount > 10 ? 'medium' : 'low')
            ];
        }
        
        // Check for ID selectors
        $idCount = preg_match_all('/#[a-zA-Z][\w-]*\s*{/', $content, $matches);
        if ($idCount > 0) {
            $this->stats['id_selectors'] += $idCount;
            $fileIssues[] = [
                'type' => 'id_selectors',
                'count' => $idCount,
                'severity' => 'medium'
            ];
        }
        
        // Check for deep nesting (simplified check)
        if (preg_match('/\s{32,}/', $content)) {
            $this->stats['deep_nesting']++;
            $fileIssues[] = [
                'type' => 'deep_nesting',
                'severity' => 'medium'
            ];
        }
        
        // Check migration status
        $filename = basename($relativePath);
        if (isset($this->migrationMap[$filename])) {
            $this->stats['migration_status'][$relativePath] = $this->migrationMap[$filename];
        } else {
            // Check if it's already in new structure
            if (strpos($relativePath, '-v2.css') !== false || 
                strpos($relativePath, 'core/') === 0 || 
                strpos($relativePath, 'components/') === 0 ||
                strpos($relativePath, 'pages/') === 0) {
                $this->stats['migration_status'][$relativePath] = 'MIGRATED';
            } else {
                $this->stats['migration_status'][$relativePath] = 'NEEDS_REVIEW';
            }
        }
        
        if (!empty($fileIssues)) {
            $this->issues[$relativePath] = $fileIssues;
        }
    }
    
    private function analyzeImportantUsage() {
        // Additional analysis of !important patterns
        foreach ($this->issues as $file => $issues) {
            foreach ($issues as $issue) {
                if ($issue['type'] === 'important' && $issue['count'] > 10) {
                    // Flag files with excessive !important usage
                    echo "⚠️  High !important usage in: $file ({$issue['count']} instances)\n";
                }
            }
        }
    }
    
    private function analyzeSelectors() {
        // Analyze selector complexity
        $complexSelectors = [];
        foreach ($this->issues as $file => $issues) {
            foreach ($issues as $issue) {
                if ($issue['type'] === 'id_selectors') {
                    $complexSelectors[] = $file;
                }
            }
        }
        
        if (!empty($complexSelectors)) {
            echo "\n⚠️  Files using ID selectors: " . implode(', ', array_slice($complexSelectors, 0, 5)) . "\n";
        }
    }
    
    private function analyzeDuplicates() {
        // Group files by similar names
        $groups = [];
        foreach (array_keys($this->stats['file_sizes']) as $file) {
            $base = preg_replace('/[-_](fix|clean|enhanced|v2|final|complete)\.css$/', '', basename($file, '.css'));
            $groups[$base][] = $file;
        }
        
        foreach ($groups as $base => $files) {
            if (count($files) > 1) {
                $this->stats['duplicate_rules'] += count($files) - 1;
                echo "\n📋 Potential duplicates for '$base': " . implode(', ', $files) . "\n";
            }
        }
    }
    
    private function checkMigrationStatus() {
        $migrated = 0;
        $needsReview = 0;
        $toRemove = 0;
        
        foreach ($this->stats['migration_status'] as $file => $status) {
            if ($status === 'MIGRATED') {
                $migrated++;
            } elseif ($status === 'REMOVE') {
                $toRemove++;
            } elseif ($status === 'NEEDS_REVIEW') {
                $needsReview++;
            }
        }
        
        echo "\n📊 Migration Status:\n";
        echo "  ✅ Migrated: $migrated files\n";
        echo "  🔍 Needs Review: $needsReview files\n";
        echo "  🗑️  To Remove: $toRemove files\n";
    }
    
    private function generateReport() {
        $report = [
            'summary' => [
                'total_files' => $this->stats['total_files'],
                'total_lines' => $this->stats['total_lines'],
                'total_size' => array_sum($this->stats['file_sizes']),
                'important_count' => $this->stats['important_count'],
                'files_with_issues' => count($this->issues),
                'duplicate_candidates' => $this->stats['duplicate_rules']
            ],
            'issues' => $this->issues,
            'migration_status' => $this->stats['migration_status'],
            'recommendations' => $this->generateRecommendations()
        ];
        
        return $report;
    }
    
    private function generateRecommendations() {
        $recommendations = [];
        
        // High priority
        if ($this->stats['important_count'] > 100) {
            $recommendations['high'][] = [
                'issue' => 'Excessive !important usage',
                'action' => 'Refactor CSS to use proper specificity and cascade',
                'files' => array_slice(array_keys($this->issues), 0, 10)
            ];
        }
        
        if ($this->stats['duplicate_rules'] > 10) {
            $recommendations['high'][] = [
                'issue' => 'Many duplicate/similar files',
                'action' => 'Consolidate duplicate CSS files',
                'impact' => 'Could reduce CSS size by ~30%'
            ];
        }
        
        // Medium priority
        if ($this->stats['id_selectors'] > 0) {
            $recommendations['medium'][] = [
                'issue' => 'ID selectors used for styling',
                'action' => 'Convert to class selectors using BEM',
                'benefit' => 'Improved reusability and lower specificity'
            ];
        }
        
        // Low priority
        $recommendations['low'][] = [
            'issue' => 'Legacy naming conventions',
            'action' => 'Adopt BEM methodology consistently',
            'benefit' => 'Better maintainability'
        ];
        
        return $recommendations;
    }
    
    public function generateHTMLReport($report) {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSS Migration Analysis Report</title>
    <style>
        body { font-family: -apple-system, sans-serif; margin: 2rem; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1, h2, h3 { color: #333; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .stat { background: #f8f9fa; padding: 1rem; border-radius: 4px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2d5a3d; }
        .stat-label { color: #666; font-size: 0.875rem; }
        .issue { background: #fff3cd; padding: 1rem; margin: 0.5rem 0; border-radius: 4px; border-left: 4px solid #ffc107; }
        .recommendation { margin: 1rem 0; padding: 1rem; border-radius: 4px; }
        .high { background: #f8d7da; border-left: 4px solid #dc3545; }
        .medium { background: #fff3cd; border-left: 4px solid #ffc107; }
        .low { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .file-list { font-family: monospace; font-size: 0.875rem; }
        .migration-status { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 3px; font-size: 0.75rem; }
        .migrated { background: #d4edda; color: #155724; }
        .needs-review { background: #fff3cd; color: #856404; }
        .remove { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSS Migration Analysis Report</h1>
        <p>Generated: {$this->getCurrentDateTime()}</p>
        
        <h2>Summary</h2>
        <div class="summary">
            <div class="stat">
                <div class="stat-number">{$report['summary']['total_files']}</div>
                <div class="stat-label">Total CSS Files</div>
            </div>
            <div class="stat">
                <div class="stat-number">{$this->formatBytes($report['summary']['total_size'])}</div>
                <div class="stat-label">Total Size</div>
            </div>
            <div class="stat">
                <div class="stat-number">{$report['summary']['important_count']}</div>
                <div class="stat-label">!important Uses</div>
            </div>
            <div class="stat">
                <div class="stat-number">{$report['summary']['files_with_issues']}</div>
                <div class="stat-label">Files with Issues</div>
            </div>
        </div>
        
        <h2>Recommendations</h2>
HTML;
        
        foreach ($report['recommendations'] as $priority => $items) {
            foreach ($items as $rec) {
                $html .= "<div class='recommendation $priority'>\n";
                $html .= "<h4>{$rec['issue']}</h4>\n";
                $html .= "<p><strong>Action:</strong> {$rec['action']}</p>\n";
                if (isset($rec['benefit'])) {
                    $html .= "<p><strong>Benefit:</strong> {$rec['benefit']}</p>\n";
                }
                $html .= "</div>\n";
            }
        }
        
        $html .= <<<HTML
        
        <h2>Migration Status</h2>
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Target</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($report['migration_status'] as $file => $status) {
            $size = $this->formatBytes($this->stats['file_sizes'][$file] ?? 0);
            $statusClass = strtolower(str_replace('_', '-', $status));
            $target = $status === 'MIGRATED' ? '✓' : ($status === 'REMOVE' ? '🗑️' : $status);
            
            $html .= "<tr>\n";
            $html .= "<td class='file-list'>$file</td>\n";
            $html .= "<td><span class='migration-status $statusClass'>$status</span></td>\n";
            $html .= "<td>$size</td>\n";
            $html .= "<td>$target</td>\n";
            $html .= "</tr>\n";
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
        
        <h2>Next Steps</h2>
        <ol>
            <li>Review and migrate high-priority files first</li>
            <li>Remove duplicate and obsolete files</li>
            <li>Update all page templates to use new CSS structure</li>
            <li>Test thoroughly at all breakpoints</li>
            <li>Remove legacy CSS imports from template headers</li>
        </ol>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function getCurrentDateTime() {
        return date('Y-m-d H:i:s');
    }
}

// Run the analyzer
$analyzer = new CSSMigrationAnalyzer();
$report = $analyzer->analyze();

// Output options
if (in_array('--output=json', $argv)) {
    echo json_encode($report, JSON_PRETTY_PRINT);
} elseif (preg_match('/--output=(.+\.html)/', implode(' ', $argv), $matches)) {
    $htmlReport = $analyzer->generateHTMLReport($report);
    file_put_contents($matches[1], $htmlReport);
    echo "\n✅ HTML report saved to: {$matches[1]}\n";
} else {
    // Console output
    echo "\n";
    echo "========================================\n";
    echo "SUMMARY\n";
    echo "========================================\n";
    echo "Total Files: {$report['summary']['total_files']}\n";
    echo "Total Size: " . number_format($report['summary']['total_size'] / 1024) . " KB\n";
    echo "!important Count: {$report['summary']['important_count']}\n";
    echo "Files with Issues: {$report['summary']['files_with_issues']}\n";
    echo "Duplicate Candidates: {$report['summary']['duplicate_candidates']}\n";
    echo "\n";
    echo "Run with --output=report.html for detailed HTML report\n";
}