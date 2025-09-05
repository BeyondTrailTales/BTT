/**
 * BttApi Client
 * Centralized API client for all AJAX operations
 */

window.BttApi = {
    baseUrl: window.BTT ? window.BTT.apiUrl : '/BTT/ajax-handler.php',
    
    // Generic request handler
    request: async function(route, method = 'GET', data = null, options = {}) {
        const url = `${this.baseUrl}?route=${route}${options.id ? '&id=' + options.id : ''}`;
        
        const fetchOptions = {
            method: method,
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (data && method !== 'GET') {
            if (data instanceof FormData) {
                fetchOptions.body = data;
            } else {
                fetchOptions.headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(data);
            }
        }
        
        try {
            const response = await fetch(url, fetchOptions);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    // Backpacks API
    backpacks: {
        list: function() {
            return BttApi.request('backpacks');
        },
        
        get: function(id) {
            return BttApi.request('backpacks', 'GET', null, { id });
        },
        
        create: function(data) {
            return BttApi.request('backpacks', 'POST', data);
        },
        
        update: function(id, data) {
            return BttApi.request('backpacks', 'PUT', data, { id });
        },
        
        delete: function(id) {
            return BttApi.request('backpacks', 'DELETE', null, { id });
        }
    },
    
    // Gear API
    gear: {
        list: function() {
            return BttApi.request('gear');
        },
        
        create: function(data) {
            return BttApi.request('gear', 'POST', data);
        },
        
        update: function(id, data) {
            return BttApi.request('gear', 'PUT', data, { id });
        },
        
        delete: function(id) {
            return BttApi.request('gear', 'DELETE', null, { id });
        }
    },
    
    // Generic methods for file uploads (used by trips)
    post: async function(route, body = {}, files = {}) {
        console.log('BttApi.post called:', {route, body, files});
        const formData = new FormData();
        
        // Add regular data
        for (const key in body) {
            if (body[key] !== null && body[key] !== undefined) {
                formData.append(key, body[key]);
            }
        }
        
        // Add files
        for (const key in files) {
            if (files[key]) {
                formData.append(key, files[key]);
            }
        }
        
        const url = `${this.baseUrl}?route=${route}`;
        console.log('BttApi.post URL:', url);
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            console.log('BttApi.post response:', {ok: response.ok, status: response.status, data});
            
            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || data.error || 'Request failed');
            }
            
            // Return the full trip data, not just the data.data
            return data.success ? data.data : data;
        } catch (error) {
            console.error('BttApi.post ERROR:', error);
            if (window.BTTUtils && window.BTTUtils.showToast) {
                window.BTTUtils.showToast(error.message, 'error');
            }
            throw error;
        }
    },

    put: async function(route, id, body = {}, files = {}) {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        
        // Add regular data
        for (const key in body) {
            if (body[key] !== null && body[key] !== undefined) {
                formData.append(key, body[key]);
            }
        }
        
        // Add files
        for (const key in files) {
            if (files[key]) {
                formData.append(key, files[key]);
            }
        }
        
        const url = `${this.baseUrl}?route=${route}&id=${id}`;
        console.log('BttApi.put URL:', url);
        
        try {
            const response = await fetch(url, {
                method: 'POST', // Use POST with _method=PUT
                body: formData,
                credentials: 'include'
            });
            const data = await response.json();
            console.log('BttApi.put response:', {ok: response.ok, status: response.status, data});
            
            if (!response.ok || (data.success === false)) {
                throw new Error(data.message || data.error || 'Request failed');
            }
            
            // Return the full trip data, not just the data.data
            return data.success ? data.data : data;
        } catch (error) {
            console.error('BttApi.put ERROR:', error);
            if (window.BTTUtils && window.BTTUtils.showToast) {
                window.BTTUtils.showToast(error.message, 'error');
            }
            throw error;
        }
    },
    
    get: async function(route, params = {}) {
        return this.request(route, 'GET', null, params);
    }
};