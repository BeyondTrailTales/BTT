/**
 * BeyondTrailTales Unified API Client
 * Clean, consistent API interface for all endpoints
 * Version: 2.0.0
 * 
 * Features:
 * - Consistent error handling
 * - Automatic retries for network errors
 * - Loading state management
 * - Response caching where appropriate
 * - Clean promise-based interface
 */

(function(window) {
    'use strict';

    const BTT_API = {
        // Configuration
        config: {
            baseUrl: '/BTT/ajax-handler.php',
            timeout: 30000, // 30 seconds
            retryAttempts: 3,
            retryDelay: 1000, // 1 second
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        },

        // Cache for GET requests
        cache: new Map(),
        cacheTimeout: 5 * 60 * 1000, // 5 minutes

        // Active requests tracking (prevent duplicates)
        activeRequests: new Map(),

        /**
         * Core request method - handles all HTTP requests
         */
        async request(endpoint, options = {}) {
            const {
                method = 'GET',
                data = null,
                params = {},
                useCache = (method === 'GET'),
                skipRetry = false,
                onProgress = null
            } = options;

            // Build URL
            const url = new URL(this.config.baseUrl, window.location.origin);
            url.searchParams.append('route', endpoint);
            
            // Add query parameters
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    url.searchParams.append(key, value);
                }
            });

            const cacheKey = `${method}:${url.toString()}`;

            // Check cache for GET requests
            if (useCache && method === 'GET') {
                const cached = this.getFromCache(cacheKey);
                if (cached) {
                    console.log(`[BTT API] Cache hit for ${endpoint}`);
                    return cached;
                }
            }

            // Check if request is already in progress
            if (this.activeRequests.has(cacheKey)) {
                console.log(`[BTT API] Reusing active request for ${endpoint}`);
                return this.activeRequests.get(cacheKey);
            }

            // Create request promise
            const requestPromise = this.executeRequest(url, method, data, options)
                .then(response => {
                    // Cache successful GET responses
                    if (useCache && method === 'GET' && response.success !== false) {
                        this.setCache(cacheKey, response);
                    }
                    return response;
                })
                .finally(() => {
                    // Remove from active requests
                    this.activeRequests.delete(cacheKey);
                });

            // Track active request
            this.activeRequests.set(cacheKey, requestPromise);

            return requestPromise;
        },

        /**
         * Execute HTTP request with retries
         */
        async executeRequest(url, method, data, options) {
            const { skipRetry, onProgress } = options;
            let lastError;

            for (let attempt = 0; attempt < this.config.retryAttempts; attempt++) {
                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);

                    const fetchOptions = {
                        method,
                        headers: { ...this.config.headers },
                        credentials: 'same-origin',
                        signal: controller.signal
                    };

                    // Handle request body
                    if (data && method !== 'GET') {
                        if (data instanceof FormData) {
                            // Let browser set Content-Type for FormData
                            delete fetchOptions.headers['Content-Type'];
                            fetchOptions.body = data;
                        } else {
                            fetchOptions.headers['Content-Type'] = 'application/json';
                            fetchOptions.body = JSON.stringify(data);
                        }
                    }

                    console.log(`[BTT API] ${method} ${url.pathname}${url.search}`);

                    const response = await fetch(url.toString(), fetchOptions);
                    clearTimeout(timeoutId);

                    // Parse response
                    const responseText = await response.text();
                    let responseData;

                    try {
                        responseData = JSON.parse(responseText);
                    } catch (e) {
                        console.error('[BTT API] Failed to parse JSON:', responseText);
                        throw new Error('Invalid JSON response from server');
                    }

                    // Check for HTTP errors
                    if (!response.ok) {
                        throw new Error(responseData.message || `HTTP ${response.status}: ${response.statusText}`);
                    }

                    // Check for API errors
                    if (responseData.success === false) {
                        throw new Error(responseData.message || responseData.error || 'Request failed');
                    }

                    // Return data directly for cleaner access
                    return responseData.data || responseData;

                } catch (error) {
                    lastError = error;

                    // Don't retry for client errors or abort
                    if (skipRetry || attempt === this.config.retryAttempts - 1 || 
                        error.name === 'AbortError' || 
                        (error.message && error.message.includes('4'))) {
                        break;
                    }

                    console.warn(`[BTT API] Retry ${attempt + 1}/${this.config.retryAttempts} after error:`, error);
                    await this.delay(this.config.retryDelay * (attempt + 1));
                }
            }

            // All retries failed
            console.error('[BTT API] Request failed:', lastError);
            throw lastError;
        },

        /**
         * Cache management
         */
        getFromCache(key) {
            const cached = this.cache.get(key);
            if (!cached) return null;

            if (Date.now() - cached.timestamp > this.cacheTimeout) {
                this.cache.delete(key);
                return null;
            }

            return cached.data;
        },

        setCache(key, data) {
            this.cache.set(key, {
                data,
                timestamp: Date.now()
            });
        },

        clearCache(pattern) {
            if (pattern) {
                for (const key of this.cache.keys()) {
                    if (key.includes(pattern)) {
                        this.cache.delete(key);
                    }
                }
            } else {
                this.cache.clear();
            }
        },

        /**
         * Utility methods
         */
        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        /**
         * API Endpoints
         */

        // Trips
        trips: {
            list: (params = {}) => BTT_API.request('trips', { params }),
            
            get: (id) => BTT_API.request('trips', { 
                params: { id } 
            }),
            
            create: (data, photo) => {
                const formData = new FormData();
                Object.entries(data).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value);
                    }
                });
                if (photo) {
                    formData.append('photo', photo);
                }
                return BTT_API.request('trips', { 
                    method: 'POST', 
                    data: formData 
                });
            },
            
            update: (id, data, photo) => {
                const formData = new FormData();
                formData.append('_method', 'PUT'); // Method override for PHP
                Object.entries(data).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        formData.append(key, value);
                    }
                });
                if (photo) {
                    formData.append('photo', photo);
                }
                return BTT_API.request('trips', { 
                    method: 'POST', // Use POST with method override
                    params: { id },
                    data: formData 
                });
            },
            
            delete: (id) => BTT_API.request('trips', { 
                method: 'DELETE', 
                params: { id } 
            }),

            updatePhoto: (id, photo) => {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('photo', photo);
                return BTT_API.request('trips', {
                    method: 'POST',
                    params: { id },
                    data: formData
                });
            }
        },

        // Backpacks
        backpacks: {
            list: () => BTT_API.request('backpacks'),
            
            get: (id) => BTT_API.request('backpacks', { 
                params: { id } 
            }),
            
            create: (data) => BTT_API.request('backpacks', { 
                method: 'POST', 
                data 
            }),
            
            update: (id, data) => BTT_API.request('backpacks', { 
                method: 'PUT', 
                params: { id }, 
                data 
            }),
            
            delete: (id) => BTT_API.request('backpacks', { 
                method: 'DELETE', 
                params: { id } 
            }),

            duplicate: (id) => BTT_API.request('backpacks', {
                method: 'POST',
                params: { id, action: 'duplicate' }
            }),

            listWithItems: () => BTT_API.request('backpacks', {
                params: { include_items: true }
            }),

            items: {
                add: (packId, items) => BTT_API.request('backpack-items', {
                    method: 'POST',
                    data: { pack_id: packId, items }
                }),

                remove: (packId, itemId) => BTT_API.request('backpack-items', {
                    method: 'DELETE',
                    params: { pack_id: packId, item_id: itemId }
                }),

                update: (packId, itemId, data) => BTT_API.request('backpack-items', {
                    method: 'PUT',
                    params: { pack_id: packId, item_id: itemId },
                    data
                })
            }
        },

        // Gear
        gear: {
            list: (params = {}) => BTT_API.request('gear', { params }),
            
            get: (id) => BTT_API.request('gear', { 
                params: { id } 
            }),
            
            create: (data) => BTT_API.request('gear', { 
                method: 'POST', 
                data 
            }),
            
            update: (id, data) => BTT_API.request('gear', { 
                method: 'PUT', 
                params: { id }, 
                data 
            }),
            
            delete: (id) => BTT_API.request('gear', { 
                method: 'DELETE', 
                params: { id } 
            }),

            categories: () => BTT_API.request('gear-categories'),

            search: (query) => BTT_API.request('gear', {
                params: { search: query }
            })
        },

        // Trip Packing
        tripPacking: {
            get: (tripId) => BTT_API.request('trip-packing', {
                params: { trip_id: tripId }
            }),

            save: (tripId, packId) => BTT_API.request('trip-packing', {
                method: 'POST',
                data: { trip_id: tripId, pack_id: packId }
            }),

            update: (tripId, packId) => BTT_API.request('trip-packing', {
                method: 'PUT',
                params: { trip_id: tripId },
                data: { pack_id: packId }
            }),

            items: {
                toggle: (tripId, itemId, packed) => BTT_API.request('trip-packing-items', {
                    method: 'PUT',
                    params: { trip_id: tripId, item_id: itemId },
                    data: { packed }
                })
            }
        },

        // User/Auth
        auth: {
            login: (email, password) => BTT_API.request('auth/login', {
                method: 'POST',
                data: { email, password },
                skipRetry: true
            }),

            logout: () => BTT_API.request('auth/logout', {
                method: 'POST'
            }),

            register: (data) => BTT_API.request('auth/register', {
                method: 'POST',
                data,
                skipRetry: true
            }),

            checkSession: () => BTT_API.request('auth/check')
        },

        // Statistics
        stats: {
            dashboard: () => BTT_API.request('stats/dashboard'),
            
            trips: () => BTT_API.request('stats/trips'),
            
            gear: () => BTT_API.request('stats/gear'),
            
            achievements: () => BTT_API.request('stats/achievements')
        },

        // Settings
        settings: {
            get: () => BTT_API.request('settings'),
            
            update: (data) => BTT_API.request('settings', {
                method: 'PUT',
                data
            })
        }
    };

    // Export to window
    window.BTT_API = BTT_API;

    // Also expose as BttApi for backward compatibility
    window.BttApi = BTT_API;

    console.log('[BTT API] Unified API client loaded v2.0.0');

})(window);