/**
 * BTT Unified State Management System
 * Provides reactive state management and event coordination across components
 * Prevents state conflicts and enables clean component communication
 * 
 * @package BeyondTrailTales
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * Centralized State Manager with reactive capabilities
     */
    window.BTTState = {
        // Application state store
        _state: {
            user: {
                id: null,
                username: null,
                preferences: {}
            },
            ui: {
                theme: 'forest',
                sidebarOpen: false,
                activeModal: null,
                loading: false,
                notifications: []
            },
            data: {
                trips: [],
                gear: [],
                backpacks: [],
                lastUpdated: null
            },
            filters: {
                trips: { search: '', completed: null, type: null },
                gear: { search: '', category: '', tags: [] }
            },
            cache: {
                api: new Map(),
                images: new Map()
            }
        },

        // Event listeners registry
        _listeners: new Map(),

        // State change history for debugging
        _history: [],

        // Maximum history entries
        _maxHistory: 50,

        /**
         * Initialize state manager
         */
        init: function() {
            // Load state from localStorage if available
            this.loadPersistedState();
            
            // Set up automatic persistence
            this.setupAutoPersistence();
            
            // Initialize state history
            this._history.push({
                timestamp: Date.now(),
                state: JSON.parse(JSON.stringify(this._state)),
                action: 'INIT'
            });

            console.log('BTTState initialized with state:', this._state);
        },

        /**
         * Get current state or specific path
         */
        getState: function(path) {
            if (!path) return JSON.parse(JSON.stringify(this._state));
            
            const keys = path.split('.');
            let current = this._state;
            
            for (const key of keys) {
                if (current && typeof current === 'object' && key in current) {
                    current = current[key];
                } else {
                    return undefined;
                }
            }
            
            return JSON.parse(JSON.stringify(current));
        },

        /**
         * Set state with automatic change detection and notifications
         */
        setState: function(path, value, options = {}) {
            const oldState = JSON.parse(JSON.stringify(this._state));
            const keys = path.split('.');
            let current = this._state;
            
            // Navigate to parent object
            for (let i = 0; i < keys.length - 1; i++) {
                const key = keys[i];
                if (!(key in current) || typeof current[key] !== 'object') {
                    current[key] = {};
                }
                current = current[key];
            }
            
            const finalKey = keys[keys.length - 1];
            const oldValue = current[finalKey];
            
            // Set new value
            current[finalKey] = value;
            
            // Record change in history
            this._history.push({
                timestamp: Date.now(),
                path: path,
                oldValue: oldValue,
                newValue: value,
                action: options.action || 'SET_STATE'
            });
            
            // Trim history if needed
            if (this._history.length > this._maxHistory) {
                this._history.shift();
            }
            
            // Notify listeners
            this._notifyListeners(path, value, oldValue, options);
            
            // Auto-persist if enabled
            if (options.persist !== false) {
                this._debouncedPersist();
            }
            
            return this;
        },

        /**
         * Update state by merging with existing value
         */
        updateState: function(path, updates, options = {}) {
            const currentValue = this.getState(path);
            let newValue;
            
            if (Array.isArray(currentValue)) {
                // For arrays, replace entirely or merge based on options
                newValue = options.merge ? [...currentValue, ...updates] : updates;
            } else if (typeof currentValue === 'object' && currentValue !== null) {
                // For objects, deep merge
                newValue = this._deepMerge(currentValue, updates);
            } else {
                // For primitives, replace
                newValue = updates;
            }
            
            return this.setState(path, newValue, { ...options, action: 'UPDATE_STATE' });
        },

        /**
         * Subscribe to state changes
         */
        subscribe: function(path, callback, options = {}) {
            if (!this._listeners.has(path)) {
                this._listeners.set(path, []);
            }
            
            const subscription = {
                id: Date.now() + Math.random(),
                callback: callback,
                options: options
            };
            
            this._listeners.get(path).push(subscription);
            
            // Return unsubscribe function
            return () => {
                const listeners = this._listeners.get(path);
                if (listeners) {
                    const index = listeners.findIndex(sub => sub.id === subscription.id);
                    if (index > -1) {
                        listeners.splice(index, 1);
                    }
                }
            };
        },

        /**
         * Dispatch custom actions
         */
        dispatch: function(action, payload = {}, options = {}) {
            console.log(`BTTState: Dispatching action ${action}`, payload);
            
            // Record action in history
            this._history.push({
                timestamp: Date.now(),
                action: action,
                payload: payload,
                state: JSON.parse(JSON.stringify(this._state))
            });
            
            // Handle built-in actions
            switch (action) {
                case 'RESET_STATE':
                    this._resetState(payload.keep || []);
                    break;
                    
                case 'LOAD_DATA':
                    this._handleDataLoad(payload);
                    break;
                    
                case 'SET_LOADING':
                    this.setState('ui.loading', payload.loading, { action: action });
                    break;
                    
                case 'SHOW_NOTIFICATION':
                    this._addNotification(payload);
                    break;
                    
                case 'CLEAR_CACHE':
                    this._clearCache(payload.type);
                    break;
                    
                default:
                    // Custom action - notify listeners
                    this._notifyActionListeners(action, payload);
            }
            
            return this;
        },

        /**
         * Notify state change listeners
         */
        _notifyListeners: function(path, newValue, oldValue, options) {
            // Notify exact path listeners
            const exactListeners = this._listeners.get(path) || [];
            exactListeners.forEach(sub => {
                if (!sub.options.immediate && this._deepEqual(newValue, oldValue)) return;
                
                try {
                    sub.callback(newValue, oldValue, path);
                } catch (error) {
                    console.error(`Error in state listener for ${path}:`, error);
                }
            });
            
            // Notify wildcard listeners
            const pathParts = path.split('.');
            for (let i = 1; i <= pathParts.length; i++) {
                const wildcardPath = pathParts.slice(0, i).join('.') + '.*';
                const wildcardListeners = this._listeners.get(wildcardPath) || [];
                
                wildcardListeners.forEach(sub => {
                    try {
                        sub.callback(newValue, oldValue, path);
                    } catch (error) {
                        console.error(`Error in wildcard listener for ${wildcardPath}:`, error);
                    }
                });
            }
        },

        /**
         * Notify action listeners
         */
        _notifyActionListeners: function(action, payload) {
            const actionListeners = this._listeners.get(`action:${action}`) || [];
            actionListeners.forEach(sub => {
                try {
                    sub.callback(payload, this._state);
                } catch (error) {
                    console.error(`Error in action listener for ${action}:`, error);
                }
            });
        },

        /**
         * Reset state to defaults
         */
        _resetState: function(keepPaths = []) {
            const newState = {
                user: { id: null, username: null, preferences: {} },
                ui: { theme: 'forest', sidebarOpen: false, activeModal: null, loading: false, notifications: [] },
                data: { trips: [], gear: [], backpacks: [], lastUpdated: null },
                filters: { trips: { search: '', completed: null, type: null }, gear: { search: '', category: '', tags: [] } },
                cache: { api: new Map(), images: new Map() }
            };
            
            // Keep specified paths
            keepPaths.forEach(path => {
                const value = this.getState(path);
                if (value !== undefined) {
                    this._setDeepValue(newState, path, value);
                }
            });
            
            this._state = newState;
            this._notifyListeners('*', newState, {}, { action: 'RESET_STATE' });
        },

        /**
         * Handle data loading action
         */
        _handleDataLoad: function(payload) {
            const { type, data, error } = payload;
            
            if (error) {
                console.error(`Data load error for ${type}:`, error);
                this.dispatch('SHOW_NOTIFICATION', {
                    type: 'error',
                    message: `Failed to load ${type}`,
                    duration: 5000
                });
                return;
            }
            
            // Update data and timestamp
            this.setState(`data.${type}`, data, { action: 'LOAD_DATA' });
            this.setState('data.lastUpdated', Date.now(), { action: 'LOAD_DATA' });
            
            // Cache the data
            this._state.cache.api.set(type, {
                data: data,
                timestamp: Date.now()
            });
        },

        /**
         * Add notification
         */
        _addNotification: function(notification) {
            const notifications = [...this._state.ui.notifications];
            const newNotification = {
                id: Date.now() + Math.random(),
                timestamp: Date.now(),
                ...notification
            };
            
            notifications.push(newNotification);
            this.setState('ui.notifications', notifications, { action: 'SHOW_NOTIFICATION' });
            
            // Auto-remove after duration
            if (notification.duration) {
                setTimeout(() => {
                    this.removeNotification(newNotification.id);
                }, notification.duration);
            }
        },

        /**
         * Remove notification
         */
        removeNotification: function(notificationId) {
            const notifications = this._state.ui.notifications.filter(n => n.id !== notificationId);
            this.setState('ui.notifications', notifications, { action: 'REMOVE_NOTIFICATION' });
        },

        /**
         * Clear cache
         */
        _clearCache: function(type) {
            if (type) {
                this._state.cache.api.delete(type);
                this._state.cache.images.delete(type);
            } else {
                this._state.cache.api.clear();
                this._state.cache.images.clear();
            }
        },

        /**
         * Load persisted state from localStorage
         */
        loadPersistedState: function() {
            try {
                const persistedData = localStorage.getItem('btt_state');
                if (persistedData) {
                    const parsed = JSON.parse(persistedData);
                    
                    // Only restore safe state paths
                    const safeToRestore = ['user.preferences', 'ui.theme', 'filters'];
                    safeToRestore.forEach(path => {
                        const value = this._getDeepValue(parsed, path);
                        if (value !== undefined) {
                            this.setState(path, value, { persist: false });
                        }
                    });
                }
            } catch (error) {
                console.warn('Could not load persisted state:', error);
            }
        },

        /**
         * Set up automatic state persistence
         */
        setupAutoPersistence: function() {
            this._debouncedPersist = this._debounce(() => {
                try {
                    const stateToPersist = {
                        user: { preferences: this._state.user.preferences },
                        ui: { theme: this._state.ui.theme },
                        filters: this._state.filters
                    };
                    
                    localStorage.setItem('btt_state', JSON.stringify(stateToPersist));
                } catch (error) {
                    console.warn('Could not persist state:', error);
                }
            }, 1000);
        },

        /**
         * Get state history for debugging
         */
        getHistory: function() {
            return [...this._history];
        },

        /**
         * Utility: Deep merge objects
         */
        _deepMerge: function(target, source) {
            const result = { ...target };
            
            for (const key in source) {
                if (source.hasOwnProperty(key)) {
                    if (typeof source[key] === 'object' && source[key] !== null && 
                        typeof target[key] === 'object' && target[key] !== null) {
                        result[key] = this._deepMerge(target[key], source[key]);
                    } else {
                        result[key] = source[key];
                    }
                }
            }
            
            return result;
        },

        /**
         * Utility: Deep equality check
         */
        _deepEqual: function(obj1, obj2) {
            if (obj1 === obj2) return true;
            
            if (obj1 == null || obj2 == null) return obj1 === obj2;
            
            if (typeof obj1 !== 'object' || typeof obj2 !== 'object') return obj1 === obj2;
            
            const keys1 = Object.keys(obj1);
            const keys2 = Object.keys(obj2);
            
            if (keys1.length !== keys2.length) return false;
            
            for (const key of keys1) {
                if (!keys2.includes(key)) return false;
                if (!this._deepEqual(obj1[key], obj2[key])) return false;
            }
            
            return true;
        },

        /**
         * Utility: Get deep value from object
         */
        _getDeepValue: function(obj, path) {
            const keys = path.split('.');
            let current = obj;
            
            for (const key of keys) {
                if (current && typeof current === 'object' && key in current) {
                    current = current[key];
                } else {
                    return undefined;
                }
            }
            
            return current;
        },

        /**
         * Utility: Set deep value in object
         */
        _setDeepValue: function(obj, path, value) {
            const keys = path.split('.');
            let current = obj;
            
            for (let i = 0; i < keys.length - 1; i++) {
                const key = keys[i];
                if (!(key in current) || typeof current[key] !== 'object') {
                    current[key] = {};
                }
                current = current[key];
            }
            
            current[keys[keys.length - 1]] = value;
        },

        /**
         * Utility: Debounce function
         */
        _debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize state manager when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.BTTState.init();
        });
    } else {
        window.BTTState.init();
    }

})();