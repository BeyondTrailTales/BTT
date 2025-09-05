<?php
/**
 * Skill Tree Navigation Component
 * Displays a skill progression tree with connected nodes
 * 
 * @param array $skills Array of skill data with structure:
 *   [
 *     ['title' => 'Trip Planning', 'icon' => '🗺️', 'progress' => 100, 'state' => 'is-complete'],
 *     ['title' => 'Pack Building', 'icon' => '🎒', 'progress' => 65, 'state' => 'is-active'],
 *     ['title' => 'Trail Master', 'icon' => '🏆', 'progress' => 0, 'state' => 'is-locked']
 *   ]
 */

// Default skills if none provided
if (!isset($skills) || empty($skills)) {
    $skills = [
        [
            'title' => 'Trip Planning',
            'icon' => '🗺️',
            'progress' => 100,
            'state' => 'is-complete',
            'hint' => 'Plan your adventures'
        ],
        [
            'title' => 'Pack Building', 
            'icon' => '🎒',
            'progress' => 65,
            'state' => 'is-active',
            'hint' => 'Build your perfect pack'
        ],
        [
            'title' => 'Trail Master',
            'icon' => '🏆', 
            'progress' => 0,
            'state' => 'is-locked',
            'hint' => 'Complete 5 trips to unlock'
        ]
    ];
}
?>

<nav class="skill-tree" aria-label="Skill progression tree">
    <ol class="skill-level" role="list">
        <?php foreach ($skills as $index => $skill): 
            // Ensure safe values
            $title = htmlspecialchars($skill['title'] ?? 'Skill');
            $icon = htmlspecialchars($skill['icon'] ?? '📍');
            $progress = max(0, min(100, (int)($skill['progress'] ?? 0)));
            $state = htmlspecialchars($skill['state'] ?? '');
            $hint = htmlspecialchars($skill['hint'] ?? '');
            
            // ARIA attributes
            $ariaCurrent = ($state === 'is-active') ? 'step' : 'false';
            $ariaLabel = $title;
            if ($state === 'is-locked') {
                $ariaLabel .= ' - Locked. ' . $hint;
            } elseif ($state === 'is-complete') {
                $ariaLabel .= ' - Completed';
            } elseif ($state === 'is-active') {
                $ariaLabel .= ' - In progress: ' . $progress . '%';
            }
        ?>
            <li class="skill-node <?= $state ?>" 
                tabindex="0"
                role="button"
                aria-current="<?= $ariaCurrent ?>"
                aria-label="<?= $ariaLabel ?>"
                <?php if ($state === 'is-locked' && $hint): ?>
                title="<?= $hint ?>"
                <?php endif; ?>>
                
                <span class="skill-icon" aria-hidden="true">
                    <?= $icon ?>
                </span>
                
                <div class="skill-title">
                    <?= $title ?>
                </div>
                
                <?php if ($progress > 0 || $state !== 'is-locked'): ?>
                <div class="meter" 
                     role="progressbar"
                     aria-label="<?= $title ?> progress"
                     aria-valuenow="<?= $progress ?>"
                     aria-valuemin="0"
                     aria-valuemax="100">
                    <span style="--val: <?= $progress ?>%"></span>
                </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
