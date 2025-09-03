<?php
/**
 * Trip Card Component
 */

require_once __DIR__ . '/card.php';

function render_trip_card($trip) {
    // Prepare badges
    $badges = [];
    if ($trip['favorite'] == 1) {
        $badges[] = ['text' => '⭐ Favorite', 'type' => 'warning'];
    }
    if ($trip['completed'] == 1) {
        $badges[] = ['text' => '✅ Completed', 'type' => 'success'];
    }
    if ($trip['trip_type']) {
        $type_labels = [
            'day_hike' => '🥾 Day Hike',
            'overnight' => '🏕️ Overnight',
            'weekend' => '🎒 Weekend',
            'section_hike' => '🗺️ Section',
            'thru_hike' => '🏔️ Thru-Hike'
        ];
        $badges[] = $type_labels[$trip['trip_type']] ?? $trip['trip_type'];
    }
    
    // Prepare meta information
    $meta = [];
    if (!empty($trip['location'])) {
        $meta[] = ['icon' => '📍', 'text' => $trip['location']];
    }
    
    // Date range
    if (!empty($trip['start_date'])) {
        $start = date('M j, Y', strtotime($trip['start_date']));
        $end = !empty($trip['end_date']) ? date('M j, Y', strtotime($trip['end_date'])) : '';
        $date_text = $end && $end !== $start ? "$start - $end" : $start;
        $meta[] = ['icon' => '📅', 'text' => $date_text];
    }
    
    // Distance
    if (!empty($trip['distance'])) {
        $unit = $trip['distance_unit'] ?? 'miles';
        $meta[] = ['icon' => '📏', 'text' => "{$trip['distance']} {$unit}"];
    }
    
    // Elevation
    if (!empty($trip['elevation_gain'])) {
        $meta[] = ['icon' => '📈', 'text' => "{$trip['elevation_gain']}ft gain"];
    }
    
    // Difficulty
    if (!empty($trip['difficulty'])) {
        $difficulty_icons = [
            'easy' => '🟢',
            'moderate' => '🟡', 
            'hard' => '🔴',
            'expert' => '⚫'
        ];
        $icon = $difficulty_icons[$trip['difficulty']] ?? '⚪';
        $meta[] = ['icon' => $icon, 'text' => ucfirst($trip['difficulty'])];
    }
    
    // Prepare actions
    $actions = [
        [
            'label' => 'View',
            'icon' => '👁️',
            'onclick' => "viewTrip({$trip['id']})",
            'primary' => true
        ],
        [
            'label' => 'Edit',
            'icon' => '✏️',
            'onclick' => "editTrip({$trip['id']})"
        ]
    ];
    
    // Prepare image
    $image = $trip['photo'] ?? asset_url('images/default-trip.jpg');
    $image_alt = $trip['photo_alt_text'] ?? $trip['title'] ?? 'Trip photo';
    
    return render_card([
        'title' => $trip['title'] ?? 'Untitled Trip',
        'subtitle' => $trip['trail_name'] ?? '',
        'description' => $trip['description'] ?? '',
        'image' => $image,
        'image_alt' => $image_alt,
        'badges' => $badges,
        'meta' => $meta,
        'actions' => $actions,
        'class' => 'trip-card',
        'id' => 'trip-' . $trip['id'],
        'data_attributes' => [
            'trip-id' => $trip['id'],
            'completed' => $trip['completed'],
            'favorite' => $trip['favorite']
        ]
    ]);
}

function render_trip_empty_state() {
    return render_empty_card([
        'icon' => '🏔️',
        'title' => 'No trips yet',
        'subtitle' => 'Start planning your next adventure',
        'action_label' => 'Create First Trip',
        'action_onclick' => 'createNewTrip()',
        'class' => 'trip-empty-card'
    ]);
}

function render_trips_grid($trips) {
    if (empty($trips)) {
        return '<div class="cards-grid">' . render_trip_empty_state() . '</div>';
    }
    
    $html = '<div class="cards-grid">';
    foreach ($trips as $trip) {
        $html .= render_trip_card($trip);
    }
    $html .= '</div>';
    
    return $html;
}
?>
