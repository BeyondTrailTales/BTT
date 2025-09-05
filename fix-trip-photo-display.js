// Patch for trips.js to ensure photos display properly after reload
// Add this to the end of trips.js or include it after trips.js loads

(function() {
    // Store the original updateGrid function
    const originalUpdateGrid = window.updateGrid || updateGrid;
    
    // Override updateGrid to add debugging
    window.updateGrid = function() {
        console.log('[Photo Fix] updateGrid called');
        console.log('[Photo Fix] Current trips:', state.trips);
        console.log('[Photo Fix] Filtered trips:', state.filtered);
        
        // Check each trip for photo_path
        state.filtered.forEach(trip => {
            if (trip.photo_path) {
                console.log(`[Photo Fix] Trip ${trip.id} has photo_path:`, trip.photo_path);
            }
        });
        
        // Call original function
        return originalUpdateGrid.apply(this, arguments);
    };
    
    // Store the original loadTrips function
    const originalLoadTrips = window.loadTrips || loadTrips;
    
    // Override loadTrips to add debugging
    window.loadTrips = async function() {
        console.log('[Photo Fix] loadTrips called');
        
        // Call original function
        const result = await originalLoadTrips.apply(this, arguments);
        
        console.log('[Photo Fix] Trips loaded:', state.trips.length);
        // Log trips with photos
        const tripsWithPhotos = state.trips.filter(t => t.photo_path);
        console.log('[Photo Fix] Trips with photos:', tripsWithPhotos.length);
        tripsWithPhotos.forEach(trip => {
            console.log(`[Photo Fix] Trip ${trip.id} (${trip.title}) photo:`, trip.photo_path);
        });
        
        return result;
    };
    
    // Add a manual refresh button for testing
    const addRefreshButton = () => {
        const container = document.querySelector('.trips-header-controls');
        if (container && !document.getElementById('photo-fix-refresh')) {
            const button = document.createElement('button');
            button.id = 'photo-fix-refresh';
            button.className = 'btn btn-secondary';
            button.textContent = '🔄 Refresh Photos';
            button.style.marginLeft = '10px';
            button.onclick = async () => {
                console.log('[Photo Fix] Manual refresh triggered');
                await loadTrips();
                filterTrips();
                console.log('[Photo Fix] Refresh complete');
            };
            container.appendChild(button);
        }
    };
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addRefreshButton);
    } else {
        addRefreshButton();
    }
    
    console.log('[Photo Fix] Trip photo display patch loaded');
})();