<?php
/**
 * Backpacks List - Simple functional version with SQLite integration
 */
require_once dirname(__DIR__) . '/app/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backpacks - BeyondTrailTales</title>
    
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
        
        .search-box {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-box input {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            min-width: 250px;
        }
        
        .search-box input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .backpacks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .backpack-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .backpack-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            border-color: #81c784;
        }
        
        .backpack-card h3 {
            color: #81c784;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }
        
        .backpack-description {
            opacity: 0.9;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .backpack-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            flex: 1;
            min-width: 80px;
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
        
        .backpack-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
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
            
            .backpacks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🎒 My Backpacks</h1>
            <p class="subtitle">Manage your backpack configurations</p>
        </header>
        
        <nav class="nav-bar">
            <a href="index.php" class="nav-link">🏠 Dashboard</a>
            <a href="backpacks-list.php" class="nav-link active">🎒 Backpacks</a>
            <a href="trips.php" class="nav-link">🏔️ Trips</a>
            <a href="test-status.php" class="nav-link">🔧 System Status</a>
        </nav>
        
        <div class="actions-bar">
            <button class="btn" onclick="createBackpack()">
                ➕ New Backpack
            </button>
            <div class="search-box">
                <input type="search" placeholder="Search backpacks..." id="search-input" onkeyup="filterBackpacks()">
            </div>
        </div>
        
        <div id="backpacks-container">
            <div class="loading">Loading backpacks...</div>
        </div>
    </div>
    
    <script>
        let allBackpacks = [];
        
        // Load backpacks on page load
        document.addEventListener('DOMContentLoaded', loadBackpacks);
        
        async function loadBackpacks() {
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks');
                const data = await response.json();
                
                if (data.success) {
                    allBackpacks = data.data;
                    displayBackpacks(allBackpacks);
                } else {
                    showError('Failed to load backpacks: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                showError('Error loading backpacks: ' + error.message);
            }
        }
        
        function displayBackpacks(backpacks) {
            const container = document.getElementById('backpacks-container');
            
            if (backpacks.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h2>No Backpacks Yet</h2>
                        <p>Create your first backpack configuration to get started!</p>
                        <button class="btn" onclick="createBackpack()">➕ Create Your First Backpack</button>
                    </div>
                `;
                return;
            }
            
            const grid = backpacks.map(backpack => `
                <div class="backpack-card">
                    <h3>${escapeHtml(backpack.name)}</h3>
                    <p class="backpack-description">${escapeHtml(backpack.description || 'No description')}</p>
                    
                    <div class="backpack-stats">
                        <div class="stat">
                            <span class="stat-label">Capacity</span>
                            <span class="stat-value">${backpack.capacity || 0}L</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Base Weight</span>
                            <span class="stat-value">${formatWeight(backpack.base_weight || 0)}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Trips</span>
                            <span class="stat-value">${backpack.trip_count || 0}</span>
                        </div>
                    </div>
                    
                    <div class="backpack-actions">
                        <button class="btn btn-small" onclick="editBackpack(${backpack.id})">✏️ Edit</button>
                        <button class="btn btn-small" onclick="viewBackpack(${backpack.id})">👁️ View</button>
                        <button class="btn btn-small btn-delete" onclick="deleteBackpack(${backpack.id}, '${escapeHtml(backpack.name)}')">🗑️ Delete</button>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = '<div class="backpacks-grid">' + grid + '</div>';
        }
        
        function filterBackpacks() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const filtered = allBackpacks.filter(backpack => 
                backpack.name.toLowerCase().includes(searchTerm) ||
                (backpack.description && backpack.description.toLowerCase().includes(searchTerm))
            );
            displayBackpacks(filtered);
        }
        
        function formatWeight(grams) {
            if (grams >= 1000) {
                return (grams / 1000).toFixed(1) + 'kg';
            }
            return grams + 'g';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function showError(message) {
            const container = document.getElementById('backpacks-container');
            container.innerHTML = `<div class="error">${message}</div>`;
        }
        
        async function createBackpack() {
            const name = prompt('Enter backpack name:');
            if (!name) return;
            
            const description = prompt('Enter description (optional):');
            const capacity = prompt('Enter capacity in liters (default: 65):') || 65;
            
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name: name,
                        description: description || '',
                        capacity: parseInt(capacity),
                        base_weight: 0
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Backpack created successfully!');
                    loadBackpacks();
                } else {
                    alert('Failed to create backpack: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error creating backpack: ' + error.message);
            }
        }
        
        function editBackpack(id) {
            // For now, just alert - full edit functionality would go here
            alert('Edit functionality coming soon for backpack ID: ' + id);
        }
        
        function viewBackpack(id) {
            // Navigate to detailed view
            window.location.href = 'backpack-detail.php?id=' + id;
        }
        
        async function deleteBackpack(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
            
            try {
                const response = await fetch('<?php echo BTT_API_URL; ?>?route=backpacks&id=' + id, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Backpack deleted successfully!');
                    loadBackpacks();
                } else {
                    alert('Failed to delete backpack: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                alert('Error deleting backpack: ' + error.message);
            }
        }
    </script>
</body>
</html>
