<?php
/**
 * Trips List - Simple functional version with SQLite integration
 */
require_once dirname(__DIR__) . '/app/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips - BeyondTrailTales</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0d3b2e 0%, #1a5f4a 100%);
            color: #e8f5e9;
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            margin-bottom: 2rem;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: #81c784;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .nav-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-link {
            color: #81c784;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(129, 199, 132, 0.2);
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #66bb6a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .filter-group select {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
        }
        
        .filter-group select option {
            background: #1a5f4a;
        }
        
        .trips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .trip-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            position: relative;
        }
        
        .trip-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border-color: #81c784;
        }
        
        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .trip-card h3 {
            color: #81c784;
            font-size: 1.3rem;
            flex: 1;
        }
        
        .trip-badges {
            display: flex;
            gap: 0.5rem;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .badge-completed {
            background: rgba(76, 175, 80, 0.3);
            color: #4caf50;
        }
        
        .badge-upcoming {
            background: rgba(255, 193, 7, 0.3);
            color: #ffc107;
        }
        
        .badge-favorite {
            color: #ffc107;
            font-size: 1.2rem;
        }
        
        .trip-location {
            opacity: 0.9;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .trip-dates {
            opacity: 0.8;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }
        
        .trip-description {
            opacity: 0.9;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .trip-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #a5d6a7;
        }
        
        .trip-backpack {
            background: rgba(129, 199, 132, 0.1);
            padding: 0.5rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .trip-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            background: rgba(129, 199, 132, 0.2);
            border: 1px solid #81c784;
            color: #81c784;
        }
        
        .btn-small:hover {
            background: rgba(129, 199, 132, 0.3);
        }
        
        .btn-delete {
            background: rgba(239, 83, 80, 0.2);
            border-color: #ef5350;
            color: #ef5350;
        }
        
        .btn-delete:hover {
            background: rgba(239, 83, 80, 0.3);
        }
        
        .difficulty-easy {
            color: #4caf50;
        }
        
        .difficulty-moderate {
            color: #ffc107;
        }
        
        .difficulty-hard {
            color: #ff9800;
        }
        
        .difficulty-expert {
            color: #ef5350;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin: 2rem 0;
        }
        
        .empty-state h2 {
            color: #81c784;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            opacity: 0.8;
            margin-bottom: 2rem;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            font-size: 1.2rem;
        }
        
        .error {
            background: rgba(239, 83, 80, 0.2);
            border: 1px solid #ef5350;
            color: #ef5350;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .trips-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏔️ My Trips</h1>
            <p class="subtitle">Plan and track your outdoor adventures</p>
        </header>
        
        <nav class="nav-bar">
            <a href="index.php" class="nav-link">🏠 Dashboard</a>
            <a href="backpacks-list.php" class="nav-link">🎒 Backpacks</a>
            <a href="trips-list.php" class="nav-link active">🏔️ Trips</a>
            <a href="test-status.php" class="nav-link">🔧 System Status</a>
        </nav>
        
        <div class="actions-bar">
            <button class="btn" onclick="createTrip()">
                ➕ New Trip
            </button>
            <div class="filter-group">
                <select id="filter-status" onchange="filterTrips()">
                    <option value="all">All Trips</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="completed">Completed</option>
                    <option value="favorites">Favorites</option>
                </select>
                <select id="filter-difficulty" onchange="filterTrips()">
                    <option value="all">All Difficulties</option>
                    <option value="easy">Easy</option>
                    <option value="moderate">Moderate</option>
                    <option value="hard">Hard</option>
                    <option value="expert">Expert</option>
                </select>
            </div>
        </div>
        
        <div id="trips-container">
            <div class="loading">Loading trips...</div>
        </div>
    </div>
    
    <script>
        let allTrips = [];
        let backpacks = [];
        
        // Load trips on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadBackpacks();
            loadTrips();
        });
        
        async function loadBackpacks() {
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
                const data = await response.json();
                if (data.success) {
                    backpacks = data.data;
                }
            } catch (error) {
                console.error('Error loading backpacks:', error);
            }
        }
        
        async function loadTrips() {
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips');
                const data = await response.json();
                
                if (data.success) {
                    allTrips = data.data;
                    displayTrips(allTrips);
                } else {
                    showError('Failed to load trips: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                showError('Error loading trips: ' + error.message);
            }
        }
        
        function displayTrips(trips) {
            const container = document.getElementById('trips-container');
            
            if (trips.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h2>No Trips Yet</h2>
                        <p>Start planning your next adventure!</p>
                        <button class="btn" onclick="createTrip()">➕ Plan Your First Trip</button>
                    </div>
                `;
                return;
            }
            
            const grid = trips.map(trip => {
                const startDate = new Date(trip.start_date);
                const endDate = new Date(trip.end_date);
                const isUpcoming = startDate > new Date();
                const isCompleted = trip.completed == 1;
                
                return `
                    <div class="trip-card">
                        <div class="trip-header">
                            <h3>${escapeHtml(trip.title)}</h3>
                            <div class="trip-badges">
                                ${trip.favorite == 1 ? '<span class="badge-favorite">⭐</span>' : ''}
                                ${isCompleted ? '<span class="badge badge-completed">✓ Completed</span>' : 
                                  isUpcoming ? '<span class="badge badge-upcoming">Upcoming</span>' : ''}
                            </div>
                        </div>
                        
                        <div class="trip-location">
                            📍 ${escapeHtml(trip.location)}
                        </div>
                        
                        <div class="trip-dates">
                            📅 ${formatDate(trip.start_date)} - ${formatDate(trip.end_date)}
                        </div>
                        
                        <p class="trip-description">${escapeHtml(trip.description || 'No description')}</p>
                        
                        <div class="trip-stats">
                            <div class="stat">
                                <span class="stat-label">Distance</span>
                                <span class="stat-value">${trip.distance || 0} ${trip.distance_unit || 'mi'}</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Elevation</span>
                                <span class="stat-value">${formatElevation(trip.elevation_gain)}</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Difficulty</span>
                                <span class="stat-value difficulty-${trip.difficulty}">${capitalizeFirst(trip.difficulty || 'moderate')}</span>
                            </div>
                        </div>
                        
                        ${trip.backpack_name ? `
                            <div class="trip-backpack">
                                🎒 ${escapeHtml(trip.backpack_name)} (${formatWeight(trip.base_weight || 0)})
                            </div>
                        ` : ''}
                        
                        <div class="trip-actions">
                            <button class="btn btn-small" onclick="editTrip(${trip.id})">✏️ Edit</button>
                            <button class="btn btn-small" onclick="viewTrip(${trip.id})">👁️ View</button>
                            <button class="btn btn-small btn-delete" onclick="deleteTrip(${trip.id}, '${escapeHtml(trip.title)}')">🗑️ Delete</button>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = '<div class="trips-grid">' + grid + '</div>';
        }
        
        function filterTrips() {
            const statusFilter = document.getElementById('filter-status').value;
            const difficultyFilter = document.getElementById('filter-difficulty').value;
            
            let filtered = allTrips;
            
            // Filter by status
            if (statusFilter === 'upcoming') {
                filtered = filtered.filter(trip => new Date(trip.start_date) > new Date());
            } else if (statusFilter === 'completed') {
                filtered = filtered.filter(trip => trip.completed == 1);
            } else if (statusFilter === 'favorites') {
                filtered = filtered.filter(trip => trip.favorite == 1);
            }
            
            // Filter by difficulty
            if (difficultyFilter !== 'all') {
                filtered = filtered.filter(trip => trip.difficulty === difficultyFilter);
            }
            
            displayTrips(filtered);
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
        
        function formatWeight(grams) {
            if (grams >= 1000) {
                return (grams / 1000).toFixed(1) + 'kg';
            }
            return grams + 'g';
        }
        
        function formatElevation(feet) {
            if (!feet) return '0 ft';
            return feet.toLocaleString() + ' ft';
        }
        
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showError(message) {
            const container = document.getElementById('trips-container');
            container.innerHTML = `<div class="error">${message}</div>`;
        }
        
        async function createTrip() {
            const title = prompt('Enter trip name:');
            if (!title) return;
            
            const location = prompt('Enter location:');
            if (!location) return;
            
            const startDate = prompt('Enter start date (YYYY-MM-DD):');
            if (!startDate) return;
            
            const endDate = prompt('Enter end date (YYYY-MM-DD):');
            if (!endDate) return;
            
            // Select backpack
            let backpackId = null;
            if (backpacks.length > 0) {
                const backpackOptions = backpacks.map((b, i) => `${i + 1}. ${b.name}`).join('\n');
                const selection = prompt(`Select a backpack (enter number):\n${backpackOptions}`);
                if (selection && parseInt(selection) > 0 && parseInt(selection) <= backpacks.length) {
                    backpackId = backpacks[parseInt(selection) - 1].id;
                }
            }
            
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: title,
                        location: location,
                        start_date: startDate,
                        end_date: endDate,
                        backpack_id: backpackId,
                        distance: 0,
                        difficulty: 'moderate',
                        trip_type: 'weekend'
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Trip created successfully!');
                    loadTrips();
                } else {
                    alert('Failed to create trip: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error creating trip: ' + error.message);
            }
        }
        
        function editTrip(id) {
            alert('Edit functionality coming soon for trip ID: ' + id);
        }
        
        function viewTrip(id) {
            window.location.href = 'trip-detail.php?id=' + id;
        }
        
        async function deleteTrip(id, title) {
            if (!confirm(`Are you sure you want to delete "${title}"?`)) return;
            
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=trips&id=' + id, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Trip deleted successfully!');
                    loadTrips();
                } else {
                    alert('Failed to delete trip: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error deleting trip: ' + error.message);
            }
        }
    </script>
</body>
</html>
