/**
 * Simplified Trips Loader
 * Quick fix to get trips working without complex dependencies
 */

(function($) {
    'use strict';
    
    // Only run on trips page
    if (!window.location.pathname.includes('trips')) return;
    
    console.log('Simple Trips Loader: Starting');
    
    // Wait for jQuery
    $(document).ready(function() {
        console.log('Simple Trips Loader: jQuery ready');
        
        // Initialize immediately
        loadTrips();
        
        // Bind events
        $('#btn-new-trip').on('click', createNewTrip);
        $('#trip-search').on('input', filterTrips);
        $('#sort-trips').on('change', sortTrips);
        
        // Handle tab switching
        $('#tab-my-trips').on('click', function() {
            showView('my-trips');
        });
        
        $('#tab-trip-editor').on('click', function() {
            showView('editor');
        });
    });
    
    function loadTrips() {
        const $grid = $('#trip-grid');
        const $empty = $('#trips-empty');
        
        console.log('Loading trips...');
        $grid.html('<div class="loading-spinner"><div class="spinner"></div><p>Loading trips...</p></div>');
        
        // Use jQuery AJAX directly
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'GET',
            data: { route: 'trips' },
            dataType: 'json',
            success: function(response) {
                console.log('Trips loaded:', response);
                
                // Handle wrapped response
                const trips = response.data || response;
                const tripsArray = Array.isArray(trips) ? trips : [];
                
                if (tripsArray.length === 0) {
                    $grid.html('');
                    $empty.show();
                } else {
                    $empty.hide();
                    renderTrips(tripsArray);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load trips:', error);
                $grid.html('<div class="alert alert-error">Failed to load trips. Please refresh the page.</div>');
            }
        });
    }
    
    function renderTrips(trips) {
        const $grid = $('#trip-grid');
        
        const html = trips.map(function(trip) {
            const photoUrl = trip.photo_path ? 
                `/BTT/${trip.photo_path}` : 
                'https://images.unsplash.com/photo-1533873984035-25970ab07461?w=400&h=300&fit=crop';
                
            return `
                <article class="trip-card trip-card-clickable" data-id="${trip.id}">
                    <div class="trip-card-header">
                        <h3>${escapeHtml(trip.title || 'Untitled Trip')}</h3>
                        <div class="trip-card-actions">
                            <button type="button" data-action="edit" title="Edit">✏️</button>
                            <button type="button" data-action="view" title="View">👁️</button>
                            <button type="button" data-action="delete" title="Delete">🗑️</button>
                        </div>
                    </div>
                    
                    <div class="trip-card-image">
                        <img src="${photoUrl}" alt="${escapeHtml(trip.photo_alt_text || 'Trip photo')}" />
                    </div>
                    
                    ${trip.location ? `<div class="trip-card-location"><span class="icon">📍</span>${escapeHtml(trip.location)}</div>` : ''}
                    
                    <div class="trip-card-stats">
                        <div class="trip-stat">
                            <span class="trip-stat-icon">⏱️</span>
                            <div class="trip-stat-content">
                                <span class="trip-stat-value">${calculateDuration(trip.start_date, trip.end_date)}</span>
                                <span class="trip-stat-label">Duration</span>
                            </div>
                        </div>
                        <div class="trip-stat">
                            <span class="trip-stat-icon">🥾</span>
                            <div class="trip-stat-content">
                                <span class="trip-stat-value">${trip.distance || '-'} ${trip.distance_unit || 'miles'}</span>
                                <span class="trip-stat-label">Distance</span>
                            </div>
                        </div>
                        <div class="trip-stat">
                            <span class="trip-stat-icon">📈</span>
                            <div class="trip-stat-content">
                                <span class="trip-stat-value">${trip.elevation_gain ? trip.elevation_gain + ' ft' : '-'}</span>
                                <span class="trip-stat-label">Elevation</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trip-card-footer">
                        <div class="trip-status">
                            <span class="trip-status-item ${trip.completed ? 'status-completed' : 'status-planning'}">
                                ${trip.completed ? '✓ Completed' : '📝 Planning'}
                            </span>
                            ${trip.favorite ? '<span class="trip-status-item status-favorite">⭐ Favorite</span>' : ''}
                        </div>
                    </div>
                </article>
            `;
        }).join('');
        
        $grid.html(html);
        
        // Bind card events
        $('.trip-card button[data-action]').on('click', handleTripAction);
        $('.trip-card').on('click', function(e) {
            if (!$(e.target).is('button')) {
                const id = $(this).data('id');
                openTripEditor(id);
            }
        });
    }
    
    function handleTripAction(e) {
        e.stopPropagation();
        const $button = $(this);
        const $card = $button.closest('.trip-card');
        const id = $card.data('id');
        const action = $button.data('action');
        
        switch(action) {
            case 'edit':
                openTripEditor(id);
                break;
            case 'view':
                viewTrip(id);
                break;
            case 'delete':
                if (confirm('Delete this trip?')) {
                    deleteTrip(id);
                }
                break;
        }
    }
    
    function createNewTrip() {
        showView('editor');
        $('#trip-form')[0].reset();
        $('#trip-id').val('');
        $('#trip-editor-title').text('New Trip');
    }
    
    function openTripEditor(id) {
        showView('editor');
        $('#trip-editor-title').text('Edit Trip');
        
        // Load trip data
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'GET',
            data: { route: 'trips', id: id },
            dataType: 'json',
            success: function(response) {
                const trip = response.data || response;
                populateTripForm(Array.isArray(trip) ? trip[0] : trip);
            }
        });
    }
    
    function viewTrip(id) {
        openTripEditor(id);
        // Add view-only mode later
    }
    
    function deleteTrip(id) {
        $.ajax({
            url: '/BTT/api/index.php',
            method: 'DELETE',
            data: { route: 'trips', id: id },
            dataType: 'json',
            success: function() {
                showToast('Trip deleted', 'success');
                loadTrips();
            },
            error: function() {
                showToast('Failed to delete trip', 'error');
            }
        });
    }
    
    function populateTripForm(trip) {
        $('#trip-id').val(trip.id);
        $('#title').val(trip.title || '');
        $('#location').val(trip.location || '');
        $('#start_date').val(trip.start_date || '');
        $('#end_date').val(trip.end_date || '');
        $('#trip_type').val(trip.trip_type || '');
        $('#backpack_id').val(trip.backpack_id || '');
        $('#favorite').val(trip.favorite ? '1' : '0');
        $('#completed').val(trip.completed ? '1' : '0');
        $('#description').val(trip.description || '');
        // Add more fields as needed
    }
    
    function filterTrips() {
        const query = $('#trip-search').val().toLowerCase();
        $('.trip-card').each(function() {
            const $card = $(this);
            const title = $card.find('h3').text().toLowerCase();
            const location = $card.find('.trip-card-location').text().toLowerCase();
            const matches = title.includes(query) || location.includes(query);
            $card.toggle(matches);
        });
    }
    
    function sortTrips() {
        // Implement sorting logic
        loadTrips(); // For now, just reload
    }
    
    function showView(view) {
        if (view === 'editor') {
            $('#panel-my-trips').removeClass('active');
            $('#panel-trip-editor').addClass('active');
            $('#tab-my-trips').removeClass('active');
            $('#tab-trip-editor').addClass('active');
        } else {
            $('#panel-my-trips').addClass('active');
            $('#panel-trip-editor').removeClass('active');
            $('#tab-my-trips').addClass('active');
            $('#tab-trip-editor').removeClass('active');
        }
    }
    
    function calculateDuration(startDate, endDate) {
        if (!startDate) return '-';
        const start = new Date(startDate);
        const end = endDate ? new Date(endDate) : start;
        const days = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
        return days === 1 ? 'Day hike' : `${days} days`;
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function showToast(message, type) {
        // Simple toast notification
        const $toast = $(`<div class="toast toast-${type}">${message}</div>`);
        $('body').append($toast);
        $toast.fadeIn();
        setTimeout(function() {
            $toast.fadeOut(function() {
                $toast.remove();
            });
        }, 3000);
    }
    
    // Handle form submission
    $('#trip-form').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#trip-id').val();
        const formData = {
            title: $('#title').val(),
            location: $('#location').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            trip_type: $('#trip_type').val(),
            backpack_id: $('#backpack_id').val() || null,
            favorite: parseInt($('#favorite').val()) || 0,
            completed: parseInt($('#completed').val()) || 0,
            description: $('#description').val()
            // Add more fields as needed
        };
        
        const url = '/BTT/api/index.php?route=trips' + (id ? '&id=' + id : '');
        const method = id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(formData),
            contentType: 'application/json',
            dataType: 'json',
            success: function() {
                showToast('Trip saved successfully', 'success');
                showView('my-trips');
                loadTrips();
            },
            error: function() {
                showToast('Failed to save trip', 'error');
            }
        });
    });
    
})(jQuery);
