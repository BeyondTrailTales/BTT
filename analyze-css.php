<?php
/**
 * CSS Analysis Tool
 * Analyzes CSS files for !important usage and other metrics
 */

$cssDir = __DIR__ . '/assets/css';
$results = [
    'total_files' => 0,
    'total_lines' => 0,
    'important_count' => 0,
    'important_by_file' => [],
    'file_sizes' => [],
    'duplicate_selectors' => [],
    'fix_files' => []
];

// Function to recursively scan CSS files
function scanCssFiles($dir, &$results) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'css') {
            $filepath = $file->getPathname();
            $filename = str_replace(dirname($dir) . '/', '', $filepath);
            $content = file_get_contents($filepath);
            
            $results['total_files']++;
            $results['file_sizes'][$filename] = $file->getSize();
            
            // Check if it's a "fix" file
            if (stripos($filename, 'fix') !== false) {
                $results['fix_files'][] = $filename;
            }
            
            // Count lines
            $lines = explode("\n", $content);
            $results['total_lines'] += count($lines);
            
            // Count !important declarations
            $importantCount = substr_count($content, '!important');
            if ($importantCount > 0) {
                $results['important_by_file'][$filename] = $importantCount;
                $results['important_count'] += $importantCount;
            }
            
            // Extract selectors (basic regex)
            preg_match_all('/([^{]+)\{[^}]*\}/s', $content, $matches);
            foreach ($matches[1] as $selector) {
                $selector = trim($selector);
                if (!empty($selector) && !str_starts_with($selector, '@')) {
                    if (!isset($results['duplicate_selectors'][$selector])) {
                        $results['duplicate_selectors'][$selector] = [];
                    }
                    $results['duplicate_selectors'][$selector][] = $filename;
                }
            }
        }
    }
}

// Scan the CSS directory
scanCssFiles($cssDir, $results);

// Filter out non-duplicate selectors
$results['duplicate_selectors'] = array_filter($results['duplicate_selectors'], function($files) {
    return count($files) > 1;
});

// Sort files by !important usage
arsort($results['important_by_file']);

// Generate report
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Analysis Report - BTT</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        h1 {
            color: #2d5a3d;
            border-bottom: 3px solid #2d5a3d;
            padding-bottom: 10px;
        }
        
        .summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .metric {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }
        
        .metric-value {
            font-size: 2em;
            font-weight: bold;
            color: #2d5a3d;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .important-count {
            color: #dc3545;
            font-weight: bold;
        }
        
        .file-size {
            color: #666;
            font-size: 0.9em;
        }
        
        .fix-file {
            background: #fff3cd;
        }
        
        code {
            background: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Consolas', 'Monaco', monospace;
        }
    </style>
</head>
<body>
    <h1>CSS Analysis Report</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="metric">
            <div class="metric-value"><?php echo $results['total_files']; ?></div>
            <div class="metric-label">Total CSS Files</div>
        </div>
        <div class="metric">
            <div class="metric-value"><?php echo number_format($results['total_lines']); ?></div>
            <div class="metric-label">Total Lines</div>
        </div>
        <div class="metric">
            <div class="metric-value important-count"><?php echo number_format($results['important_count']); ?></div>
            <div class="metric-label">!important Declarations</div>
        </div>
        <div class="metric">
            <div class="metric-value"><?php echo count($results['fix_files']); ?></div>
            <div class="metric-label">"Fix" Files</div>
        </div>
        <div class="metric">
            <div class="metric-value"><?php echo count($results['duplicate_selectors']); ?></div>
            <div class="metric-label">Duplicate Selectors</div>
        </div>
    </div>
    
    <?php if ($results['important_count'] > 100): ?>
    <div class="warning">
        <strong>Warning:</strong> High number of !important declarations detected (<?php echo $results['important_count']; ?>). 
        This indicates specificity conflicts and should be addressed.
    </div>
    <?php endif; ?>
    
    <?php if (count($results['fix_files']) > 0): ?>
    <div class="warning">
        <strong>Warning:</strong> <?php echo count($results['fix_files']); ?> "fix" files detected. 
        These often indicate CSS architecture problems.
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>Files with Most !important Declarations</h2>
        <?php if (empty($results['important_by_file'])): ?>
            <div class="success">
                <strong>Excellent!</strong> No !important declarations found.
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>File</th>
                    <th>!important Count</th>
                    <th>File Size</th>
                </tr>
                <?php 
                $count = 0;
                foreach ($results['important_by_file'] as $file => $importantCount): 
                    if ($count++ >= 20) break;
                    $isFixFile = stripos($file, 'fix') !== false;
                ?>
                <tr <?php if ($isFixFile) echo 'class="fix-file"'; ?>>
                    <td><code><?php echo htmlspecialchars($file); ?></code></td>
                    <td class="important-count"><?php echo $importantCount; ?></td>
                    <td class="file-size"><?php echo number_format($results['file_sizes'][$file] / 1024, 2); ?> KB</td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <?php if (count($results['fix_files']) > 0): ?>
    <div class="section">
        <h2>"Fix" Files Detected</h2>
        <p>These files often indicate quick patches that should be refactored:</p>
        <ul>
            <?php foreach ($results['fix_files'] as $file): ?>
            <li><code><?php echo htmlspecialchars($file); ?></code></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (count($results['duplicate_selectors']) > 0): ?>
    <div class="section">
        <h2>Top Duplicate Selectors</h2>
        <p>These selectors appear in multiple files, which may cause conflicts:</p>
        <table>
            <tr>
                <th>Selector</th>
                <th>Files</th>
            </tr>
            <?php 
            $count = 0;
            foreach ($results['duplicate_selectors'] as $selector => $files): 
                if ($count++ >= 10) break;
            ?>
            <tr>
                <td><code><?php echo htmlspecialchars(substr($selector, 0, 50)); ?></code></td>
                <td><?php echo count($files); ?> files</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="section">
        <h2>Largest CSS Files</h2>
        <table>
            <tr>
                <th>File</th>
                <th>Size</th>
            </tr>
            <?php 
            arsort($results['file_sizes']);
            $count = 0;
            foreach ($results['file_sizes'] as $file => $size): 
                if ($count++ >= 10) break;
            ?>
            <tr>
                <td><code><?php echo htmlspecialchars($file); ?></code></td>
                <td><?php echo number_format($size / 1024, 2); ?> KB</td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>New Architecture Status</h2>
        <?php
        $newArchFiles = [
            'core/reset.css' => 'Reset styles',
            'core/variables.css' => 'CSS Variables',
            'core/base.css' => 'Base styles',
            'core/typography.css' => 'Typography',
            'layouts/container.css' => 'Container system',
            'layouts/grid.css' => 'Grid system',
            'layouts/spacing.css' => 'Spacing utilities',
            'components/navigation.css' => 'Navigation',
            'components/buttons.css' => 'Buttons',
            'components/forms.css' => 'Forms',
            'components/cards.css' => 'Cards',
            'utilities/helpers.css' => 'Helpers',
            'utilities/animations.css' => 'Animations'
        ];
        ?>
        <table>
            <tr>
                <th>File</th>
                <th>Description</th>
                <th>Status</th>
            </tr>
            <?php foreach ($newArchFiles as $file => $desc): ?>
            <tr>
                <td><code><?php echo $file; ?></code></td>
                <td><?php echo $desc; ?></td>
                <td>
                    <?php if (file_exists($cssDir . '/' . $file)): ?>
                        <span style="color: green;">✓ Created</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Missing</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Recommendations</h2>
        <ol>
            <li>Continue migrating components to the new architecture</li>
            <li>Remove !important declarations except in utility classes</li>
            <li>Consolidate duplicate selectors</li>
            <li>Delete "fix" files after incorporating changes properly</li>
            <li>Use the AssetLoader for consistent CSS loading order</li>
            <li>Follow BEM naming convention for new components</li>
        </ol>
    </div>
    
    <p style="text-align: center; color: #666; margin-top: 40px;">
        Generated on <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</body>
</html>