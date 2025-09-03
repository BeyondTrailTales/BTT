/**
 * BeyondTrailTales API Client
 * Centralized API client for consistent AJAX requests with proper session handling
 * 
 * @version 1.0.0
 */

(function(window, $) {
    'use strict';

    // API Base URL - use direct handler to avoid timeouts
    // const API_BASE = window.location.origin + '/BTT/api/index.php';  // OLD - times out
    const API_BASE = window.location.origin + '/BTT/ajax-handler.php';  // NEW - direct handler

    // Configure jQuery for all API requests
    $.ajaxSetup({
        xhrFields: {
            withCredentials: true  // Include cookies in requests
        },
        crossDomain: false,  // Same-origin only
        headers: {
            'X-Requested-With': 'XMLHttpRequest'  // Identify as AJAX request
        }
    });

    /**
     * BTT API Client
     */
    const BttApi = {
        /**
         * Make a GET request
         * @param {string} route - API route (e.g., 'backpacks', 'trips')
         * @param {object} params - Query parameters
         * @returns {Promise}
         */
        get: function(route, params = {}) {
            params.route = route;
            return $.ajax({
                url: API_BASE,
                method: 'GET',
                data: params,
                dataType: 'json'
            }).then(function(response) {
                // Unwrap successful responses
                return BttApi.handleSuccess(response);
            });
        },

        /**
         * Make a POST request
         * @param {string} route - API route
         * @param {object} data - Request body data
         * @param {object} files - Optional file uploads
         * @returns {Promise}
         */
        post: function(route, data = {}, files = {}) {
            const params = { route: route };
            
            // Check if we have files to upload
            if (files && Object.keys(files).length > 0) {
                // Use FormData for file uploads
                const formData = new FormData();
                
                // Add regular data fields
                for (const key in data) {
                    if (data.hasOwnProperty(key)) {
                        formData.append(key, data[key]);
                    }
                }
                
                // Add files
                for (const key in files) {
                    if (files.hasOwnProperty(key)) {
                        formData.append(key, files[key]);
                    }
                }
                
                return $.ajax({
                    url: API_BASE + '?' + $.param(params),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).then(function(response) {
                    return BttApi.handleSuccess(response);
                });
            } else {
                // Regular POST without files
                return $.ajax({
                    url: API_BASE + '?' + $.param(params),
                    method: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json'
                }).then(function(response) {
                    return BttApi.handleSuccess(response);
                });
            }
        },

        /**
         * Make a PUT request
         * @param {string} route - API route
         * @param {string|number} id - Resource ID
         * @param {object} data - Request body data
         * @returns {Promise}
         */
        put: function(route, id, data = {}, files = {}) {
            // Check if we have files to upload
            if (files && Object.keys(files).length > 0) {
                // Use FormData for file uploads
                const formData = new FormData();
                
                // Add regular data fields
                for (const key in data) {
                    if (data.hasOwnProperty(key)) {
                        formData.append(key, data[key]);
                    }
                }
                
                // Add files
                for (const key in files) {
                    if (files.hasOwnProperty(key)) {
                        formData.append(key, files[key]);
                    }
                }
                
                return $.ajax({
                    url: API_BASE + '?route=' + route + '&id=' + id,
                    method: 'POST',  // Use POST with _method override for PUT with files
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-HTTP-Method-Override': 'PUT'
                    }
                }).then(function(response) {
                    return BttApi.handleSuccess(response);
                });
            } else {
                // Regular PUT without files
                return $.ajax({
                    url: API_BASE + '?route=' + route + '&id=' + id,
                    method: 'PUT',
                    data: JSON.stringify(data),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json'
                }).then(function(response) {
                    return BttApi.handleSuccess(response);
                });
            }
        },

        /**
         * Make a DELETE request
         * @param {string} route - API route
         * @param {string|number} id - Resource ID
         * @returns {Promise}
         */
        delete: function(route, id) {
            return $.ajax({
                url: API_BASE + '?route=' + route + '&id=' + id,
                method: 'DELETE',
                dataType: 'json'
            }).then(function(response) {
                // Unwrap successful responses
                return BttApi.handleSuccess(response);
            });
        },

        /**
         * Backpacks API endpoints
         */
        backpacks: {
            /**
             * Get all backpacks for current user
             */
            list: function() {
                return BttApi.get('backpacks');
            },

            /**
             * Get a single backpack by ID
             */
            get: function(id) {
                return BttApi.get('backpacks', { id: id });
            },

            /**
             * Create a new backpack
             */
            create: function(data) {
                return BttApi.post('backpacks', data);
            },

            /**
             * Update a backpack
             */
            update: function(id, data) {
                return BttApi.put('backpacks', id, data);
            },

            /**
             * Delete a backpack
             */
            delete: function(id) {
                return BttApi.delete('backpacks', id);
            },

            /**
             * Duplicate a backpack
             */
            duplicate: function(id) {
                return BttApi.post('backpacks', {}, { id: id, action: 'duplicate' });
            },

            /**
             * Export a backpack
             */
            export: function(id) {
                return BttApi.get('backpacks', { id: id, action: 'export' });
            },

            /**
             * Import a backpack
             */
            import: function(data) {
                return BttApi.post('backpacks', data, { id: 'import' });
            },

            /**
             * Get backpack templates
             */
            templates: function() {
                return BttApi.get('backpacks', { id: 'templates' });
            },

            /**
             * Create from template
             */
            createFromTemplate: function(templateId) {
                return BttApi.post('backpacks', {}, { id: templateId, action: 'from-template' });
            }
        },

        /**
         * Gear API endpoints
         */
        gear: {
            /**
             * Get all gear items
             */
            list: function(params = {}) {
                return BttApi.get('gear', params);
            },

            /**
             * Get a single gear item
             */
            get: function(id) {
                return BttApi.get('gear', { id: id });
            },

            /**
             * Create a new gear item
             */
            create: function(data) {
                return BttApi.post('gear', data);
            },

            /**
             * Update a gear item
             */
            update: function(id, data) {
                return BttApi.put('gear', id, data);
            },

            /**
             * Delete a gear item
             */
            delete: function(id) {
                return BttApi.delete('gear', id);
            }
        },

        /**
         * Trips API endpoints
         */
        trips: {
            /**
             * Get all trips for current user
             */
            list: function() {
                return BttApi.get('trips');
            },

            /**
             * Get a single trip
             */
            get: function(id) {
                return BttApi.get('trips', { id: id });
            },

            /**
             * Create a new trip
             */
            create: function(data) {
                return BttApi.post('trips', data);
            },

            /**
             * Update a trip
             */
            update: function(id, data) {
                return BttApi.put('trips', id, data);
            },

            /**
             * Delete a trip
             */
            delete: function(id) {
                return BttApi.delete('trips', id);
            }
        },

        /**
         * Authentication API endpoints
         */
        auth: {
            /**
             * Login
             */
            login: function(email, password, remember = false) {
                return BttApi.post('auth', {
                    login: email,
                    password: password,
                    remember: remember
                }, { id: 'login' });
            },

            /**
             * Logout
             */
            logout: function() {
                return BttApi.post('auth', {}, { id: 'logout' });
            },

            /**
             * Register
             */
            register: function(email, username, password, passwordConfirm) {
                return BttApi.post('auth', {
                    email: email,
                    username: username,
                    password: password,
                    password_confirm: passwordConfirm
                }, { id: 'register' });
            },

            /**
             * Get current user
             */
            getCurrentUser: function() {
                return BttApi.get('auth', { id: 'current-user' });
            },

            /**
             * Request password reset
             */
            forgotPassword: function(email) {
                return BttApi.post('auth', {
                    email: email
                }, { id: 'forgot-password' });
            },

            /**
             * Reset password
             */
            resetPassword: function(token, password, passwordConfirm) {
                return BttApi.post('auth', {
                    token: token,
                    password: password,
                    password_confirm: passwordConfirm
                }, { id: 'reset-password' });
            }
        },

        /**
         * Error handler for AJAX requests
         * @param {jqXHR} jqXHR - jQuery XHR object
         * @param {string} textStatus - Error status
         * @param {string} errorThrown - Error message
         */
        handleError: function(jqXHR, textStatus, errorThrown) {
            console.error('API Error:', textStatus, errorThrown);
            
            // Handle authentication errors
            if (jqXHR.status === 401) {
                // Store current location for redirect after login
                const currentPath = window.location.pathname + window.location.search;
                window.location.href = '/BTT/auth/login.php?redirect=' + encodeURIComponent(currentPath);
                return;
            }
            
            // Try to parse error response
            let errorMessage = 'An error occurred. Please try again.';
            try {
                const response = JSON.parse(jqXHR.responseText);
                if (response.error) {
                    if (typeof response.error === 'string') {
                        errorMessage = response.error;
                    } else if (response.error.message) {
                        errorMessage = response.error.message;
                    }
                } else if (response.message) {
                    errorMessage = response.message;
                }
            } catch (e) {
                // Use default error message
            }
            
            // Trigger custom error event
            $(document).trigger('api:error', {
                status: jqXHR.status,
                message: errorMessage,
                response: jqXHR.responseText
            });
            
            return errorMessage;
        },

        /**
         * Success handler for standardized responses
         * @param {object} response - API response
         */
        handleSuccess: function(response) {
            // Standardize response format
            if (response && typeof response === 'object') {
                // Check if it's a wrapped response with success flag
                if (response.success === true && response.data !== undefined) {
                    return response.data;
                } else if (response.success === false) {
                    throw new Error(response.error || response.message || 'Request failed');
                }
            }
            
            // Return response as-is if not in standard format
            return response;
        }
    };

    // Set global error handler for all AJAX requests
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        // Only handle API requests
        if (ajaxSettings.url && ajaxSettings.url.includes('/api/')) {
            BttApi.handleError(jqXHR, thrownError, thrownError);
        }
    });

    // Export to global scope - use both names for compatibility
    window.BttApi = BttApi;
    window.BTTApi = BttApi;  // Alias for existing code expecting BTTApi

})(window, jQuery);
