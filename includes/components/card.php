<?php
/**
 * Reusable Card Component
 * 
 * Usage:
 * echo render_card([
 *     'title' => 'Card Title',
 *     'description' => 'Card description',
 *     'image' => 'path/to/image.jpg',
 *     'badges' => ['New', 'Featured'],
 *     'meta' => [
 *         ['icon' => '📍', 'text' => 'Location'],
 *         ['icon' => '📅', 'text' => 'Date']
 *     ],
 *     'actions' => [
 *         ['label' => 'View', 'url' => '#', 'primary' => true],
 *         ['label' => 'Edit', 'url' => '#']
 *     ]
 * ]);
 */

function render_card($options = []) {
    $defaults = [
        'title' => '',
        'subtitle' => '',
        'description' => '',
        'image' => null,
        'image_alt' => '',
        'badges' => [],
        'meta' => [],
        'actions' => [],
        'class' => '',
        'id' => '',
        'data_attributes' => [],
        'empty' => false,
        'loading' => false
    ];
    
    $card = array_merge($defaults, $options);
    
    // Handle empty state
    if ($card['empty']) {
        return render_empty_card($card);
    }
    
    // Build data attributes
    $data_attrs = '';
    foreach ($card['data_attributes'] as $key => $value) {
        $data_attrs .= ' data-' . e($key) . '="' . e($value) . '"';
    }
    
    // Build class list
    $classes = ['card'];
    if ($card['class']) {
        $classes[] = $card['class'];
    }
    if ($card['loading']) {
        $classes[] = 'card--loading';
    }
    $classes[] = 'card--animate-in';
    
    $class_str = implode(' ', $classes);
    $id_attr = $card['id'] ? ' id="' . e($card['id']) . '"' : '';
    
    ob_start();
    ?>
    <article class="<?php echo $class_str; ?>"<?php echo $id_attr . $data_attrs; ?>>
        <?php if ($card['image']): ?>
        <div class="card__image">
            <img src="<?php echo e($card['image']); ?>" 
                 alt="<?php echo e($card['image_alt'] ?: $card['title']); ?>"
                 loading="lazy">
            <?php if (!empty($card['badges'])): ?>
            <div class="card__badges">
                <?php foreach ($card['badges'] as $badge): ?>
                    <?php
                    $badge_class = 'card__badge';
                    if (is_array($badge)) {
                        $badge_text = $badge['text'] ?? '';
                        $badge_type = $badge['type'] ?? '';
                        if ($badge_type) {
                            $badge_class .= ' card__badge--' . $badge_type;
                        }
                    } else {
                        $badge_text = $badge;
                    }
                    ?>
                    <span class="<?php echo $badge_class; ?>"><?php echo e($badge_text); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="card__content">
            <?php if ($card['title']): ?>
            <h3 class="card__title"><?php echo e($card['title']); ?></h3>
            <?php endif; ?>
            
            <?php if ($card['subtitle']): ?>
            <div class="card__subtitle"><?php echo e($card['subtitle']); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($card['meta'])): ?>
            <div class="card__meta">
                <?php foreach ($card['meta'] as $meta_item): ?>
                <div class="card__meta-item">
                    <?php if (isset($meta_item['icon'])): ?>
                    <span class="card__meta-icon"><?php echo $meta_item['icon']; ?></span>
                    <?php endif; ?>
                    <span><?php echo e($meta_item['text'] ?? ''); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($card['description']): ?>
            <p class="card__description"><?php echo e($card['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($card['actions'])): ?>
        <div class="card__actions">
            <?php foreach ($card['actions'] as $action): ?>
                <?php
                $action_class = 'card__action';
                if (isset($action['primary']) && $action['primary']) {
                    $action_class .= ' card__action--primary';
                } else {
                    $action_class .= ' card__action--secondary';
                }
                
                $action_tag = isset($action['url']) ? 'a' : 'button';
                $action_attrs = '';
                
                if (isset($action['url'])) {
                    $action_attrs .= ' href="' . e($action['url']) . '"';
                }
                if (isset($action['onclick'])) {
                    $action_attrs .= ' onclick="' . e($action['onclick']) . '"';
                }
                if (isset($action['id'])) {
                    $action_attrs .= ' id="' . e($action['id']) . '"';
                }
                ?>
                <<?php echo $action_tag; ?> class="<?php echo $action_class; ?>"<?php echo $action_attrs; ?>>
                    <?php if (isset($action['icon'])): ?>
                    <span><?php echo $action['icon']; ?></span>
                    <?php endif; ?>
                    <?php echo e($action['label'] ?? 'Action'); ?>
                </<?php echo $action_tag; ?>>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}

function render_empty_card($options = []) {
    $defaults = [
        'icon' => '📦',
        'title' => 'No items yet',
        'subtitle' => 'Create your first item to get started',
        'action_label' => 'Create New',
        'action_url' => '#',
        'action_onclick' => '',
        'class' => '',
        'id' => ''
    ];
    
    $card = array_merge($defaults, $options);
    
    $classes = ['card', 'card--empty'];
    if ($card['class']) {
        $classes[] = $card['class'];
    }
    $class_str = implode(' ', $classes);
    $id_attr = $card['id'] ? ' id="' . e($card['id']) . '"' : '';
    
    ob_start();
    ?>
    <div class="<?php echo $class_str; ?>"<?php echo $id_attr; ?>>
        <div class="card__empty-content">
            <div class="card__empty-icon"><?php echo $card['icon']; ?></div>
            <h3 class="card__empty-title"><?php echo e($card['title']); ?></h3>
            <p class="card__empty-subtitle"><?php echo e($card['subtitle']); ?></p>
            <?php if ($card['action_label']): ?>
                <?php if ($card['action_url']): ?>
                <a href="<?php echo e($card['action_url']); ?>" class="card__empty-action">
                    <span>➕</span> <?php echo e($card['action_label']); ?>
                </a>
                <?php else: ?>
                <button class="card__empty-action" onclick="<?php echo e($card['action_onclick']); ?>">
                    <span>➕</span> <?php echo e($card['action_label']); ?>
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a grid of cards
 */
function render_cards_grid($cards = [], $empty_state = null) {
    if (empty($cards)) {
        if ($empty_state) {
            return '<div class="cards-grid">' . render_empty_card($empty_state) . '</div>';
        }
        return '<div class="cards-grid"></div>';
    }
    
    $html = '<div class="cards-grid">';
    foreach ($cards as $card) {
        $html .= render_card($card);
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Render a list of cards
 */
function render_cards_list($cards = [], $empty_state = null) {
    if (empty($cards)) {
        if ($empty_state) {
            return '<div class="cards-list">' . render_empty_card($empty_state) . '</div>';
        }
        return '<div class="cards-list"></div>';
    }
    
    $html = '<div class="cards-list">';
    foreach ($cards as $card) {
        $card['class'] = ($card['class'] ?? '') . ' card--list';
        $html .= render_card($card);
    }
    $html .= '</div>';
    
    return $html;
}

// Helper function for escaping
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
?>
