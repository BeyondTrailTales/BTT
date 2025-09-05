<?php
/**
 * Page Header Component (Duolingo Forest style)
 * Renders a unified page header matching gear.php styling.
 *
 * Usage:
 *   render_page_header([
 *     'title' => 'My Title',
 *     'description' => 'Optional description',
 *     'icon' => '📦',
 *     'actions' => [
 *        ['label' => 'Primary', 'href' => '/path', 'variant' => 'primary'],
 *        ['label' => 'Secondary', 'href' => '/path2', 'variant' => 'secondary']
 *     ],
 *     'classes' => 'page-header-section--compact'
 *   ]);
 */

if (!function_exists('render_page_header')) {
    /**
     * Render the page header
     * @param array $opts
     *  - title (string, required)
     *  - description (string, optional)
     *  - icon (string, optional) emoji or small icon text
     *  - actions (array, optional) each: ['label' => string, 'href' => string, 'variant' => 'primary'|'secondary']
     *  - classes (string, optional) additional classes for the header section
     */
    function render_page_header(array $opts = []) {
        $title = $opts['title'] ?? '';
        if ($title === '') return;
        $desc = $opts['description'] ?? '';
        $icon = $opts['icon'] ?? '🏔️';
        $actions = $opts['actions'] ?? [];
        $classes = 'gear-header-section';
        if (!empty($opts['classes'])) {
            $classes .= ' ' . $opts['classes'];
        }
        ?>
        <header class="<?php echo e($classes); ?>" role="region" aria-labelledby="page-title">
            <div class="gear-header-content">
                <div class="gear-title-group">
                    <h1 id="page-title" class="gear-page-title">
                        <span class="page-icon" aria-hidden="true"><?php echo e($icon); ?></span>
                        <?php echo e($title); ?>
                    </h1>
                    <?php if ($desc !== ''): ?>
                        <p id="page-description" class="gear-page-subtitle"><?php echo e($desc); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($actions)): ?>
                <div class="page-header-actions">
                    <?php foreach ($actions as $action): 
                        $label = $action['label'] ?? '';
                        $href = $action['href'] ?? '#';
                        $variant = $action['variant'] ?? 'primary';
                        $cls = 'page-header-action' . ($variant === 'secondary' ? ' page-header-action--secondary' : '');
                    ?>
                        <a class="<?php echo e($cls); ?>" href="<?php echo e($href); ?>"><?php echo e($label); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }
}
