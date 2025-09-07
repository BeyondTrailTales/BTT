<?php
/**
 * Trip Card Component
 */

require_once __DIR__ . '/card.php';

function render_trip_card($trip) {
    // Prepare badges - separate top and bottom
    $badges = [];
    $bottom_badges = [];
    
    // Status badges (bottom)
    if ($trip['favorite'] == 1) {
        $bottom_badges[] = ['text' => '⭐ Favorite', 'type' => 'warning'];
    }
    if ($trip['completed'] == 1) {
        $bottom_badges[] = ['text' => '✅ Completed', 'type' => 'success'];
    } else {
        // Show status for non-completed trips
        $status_labels = [
            'planning' => '📋 Planning',
            'upcoming' => '🎯 Upcoming',
            'active' => '🔥 Active'
        ];
        $status = $trip['status'] ?? 'planning';
        $bottom_badges[] = ['text' => $status_labels[$status] ?? '📋 Planning', 'type' => 'info'];
    }
    
    // Trip type badges (top right - minimal)
    if ($trip['trip_type']) {
        $type_labels = [
            'day_hike' => 'Day Hike',
            'overnight' => 'Overnight',
            'weekend' => 'Weekend',
            'section_hike' => 'Section',
            'thru_hike' => 'Thru-Hike'
        ];
        $badges[] = $type_labels[$trip['trip_type']] ?? $trip['trip_type'];
    }
    
    // Difficulty badge (top right)
    if (!empty($trip['difficulty'])) {
        $badges[] = ucfirst($trip['difficulty']);
    }
    
    // Prepare meta information with improved structure
    $meta = [];
    
    // Location
    if (!empty($trip['location'])) {
        $meta[] = [
            'icon' => '📍', 
            'label' => 'Location',
            'text' => $trip['location']
        ];
    }
    
    // Date range
    if (!empty($trip['start_date'])) {
        $start = date('M j', strtotime($trip['start_date']));
        $end = !empty($trip['end_date']) ? date('M j', strtotime($trip['end_date'])) : '';
        $date_text = $end && $end !== $start ? "$start - $end" : $start;
        $meta[] = [
            'icon' => '📅', 
            'label' => 'Dates',
            'text' => $date_text
        ];
    }
    
    // Duration
    if (!empty($trip['start_date']) && !empty($trip['end_date'])) {
        $start_date = new DateTime($trip['start_date']);
        $end_date = new DateTime($trip['end_date']);
        $duration = $start_date->diff($end_date)->days + 1;
        $duration_text = $duration == 1 ? '1 day' : "$duration days";
        $meta[] = [
            'icon' => '⏱️', 
            'label' => 'Duration',
            'text' => $duration_text
        ];
    }
    
    // Distance
    if (!empty($trip['distance'])) {
        $unit = $trip['distance_unit'] ?? 'miles';
        $meta[] = [
            'icon' => '🥾', 
            'label' => 'Distance',
            'text' => "{$trip['distance']} {$unit}"
        ];
    }
    
    // Elevation
    if (!empty($trip['elevation_gain'])) {
        $meta[] = [
            'icon' => '⛰️', 
            'label' => 'Elevation',
            'text' => number_format($trip['elevation_gain']) . ' ft'
        ];
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
        'bottom_badges' => $bottom_badges,
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
