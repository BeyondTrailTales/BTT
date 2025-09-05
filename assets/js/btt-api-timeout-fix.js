/**
 * BTT API Timeout Fix
 * Prevents API calls from hanging indefinitely
 * @version 1.0.0
 */

(function(window) {
    'use strict';
    
    // Store original BttApi methods if they exist
    if (window.BttApi && window.BttApi.backpacks) {
        const originalBackpacks = window.BttApi.backpacks;
        
        // Wrap each method with timeout
        window.BttApi.backpacks = {
            list: function() {
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('API timeout: Request took too long')), 5000)
                );
                return Promise.race([originalBackpacks.list(), timeoutPromise]);
            },
            
            get: function(id) {
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('API timeout: Request took too long')), 5000)
                );
                return Promise.race([originalBackpacks.get(id), timeoutPromise]);
            },
            
            create: function(data) {
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('API timeout: Request took too long')), 5000)
                );
                return Promise.race([originalBackpacks.create(data), timeoutPromise]);
            },
            
            update: function(id, data) {
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('API timeout: Request took too long')), 5000)
                );
                return Promise.race([originalBackpacks.update(id, data), timeoutPromise]);
            },
            
            delete: function(id) {
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('API timeout: Request took too long')), 5000)
                );
                return Promise.race([originalBackpacks.delete(id), timeoutPromise]);
            }
        };
        
        console.log('BttApi timeout protection applied');
    }
    
})(window);