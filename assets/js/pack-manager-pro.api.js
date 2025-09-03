/**
 * Pack Manager Pro API
 * Handles all backend communication for the Pack Manager Pro
 */

const PackManagerAPI = (function() {
    'use strict';

    // Base configuration
    const API_BASE = '/BTT/api';
    const ENDPOINTS = {
        backpacks: `${API_BASE}/backpacks`,
        gear: `${API_BASE}/gear`,
        templates: `${API_BASE}/backpacks/templates`
    };

    // Helper function for API calls
    async function apiCall(url, options = {}) {
        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            };

            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }

            // Check for API success flag
            if (data.success === false) {
                throw new Error(data.message || 'API request failed');
            }

            return data.data || data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Public API methods
    return {
        /**
         * Get all backpacks
         */
        async getAllBackpacks() {
            return apiCall(ENDPOINTS.backpacks);
        },

        /**
         * Get a single backpack by ID with sections and items
         */
        async getBackpack(id) {
            if (!id) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${id}`);
        },

        /**
         * Create a new backpack
         */
        async createBackpack(data) {
            return apiCall(ENDPOINTS.backpacks, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },

        /**
         * Update an existing backpack
         */
        async updateBackpack(id, data) {
            if (!id) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },

        /**
         * Delete a backpack
         */
        async deleteBackpack(id, force = false) {
            if (!id) {
                throw new Error('Backpack ID is required');
            }
            const url = force ? `${ENDPOINTS.backpacks}/${id}?force=true` : `${ENDPOINTS.backpacks}/${id}`;
            return apiCall(url, {
                method: 'DELETE'
            });
        },

        /**
         * Duplicate a backpack
         */
        async duplicateBackpack(id, name) {
            if (!id) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${id}/duplicate`, {
                method: 'POST',
                body: JSON.stringify({ name })
            });
        },

        /**
         * Get all gear items
         */
        async getGear(query = '') {
            const url = query ? `${ENDPOINTS.gear}?q=${encodeURIComponent(query)}` : ENDPOINTS.gear;
            return apiCall(url);
        },

        /**
         * Get gear item by ID
         */
        async getGearItem(id) {
            if (!id) {
                throw new Error('Gear ID is required');
            }
            return apiCall(`${ENDPOINTS.gear}/${id}`);
        },

        /**
         * Create custom gear item
         */
        async createGearItem(data) {
            return apiCall(ENDPOINTS.gear, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },

        /**
         * Get backpack templates
         */
        async getTemplates() {
            return apiCall(ENDPOINTS.templates);
        },

        /**
         * Create backpack from template
         */
        async createFromTemplate(templateId, data) {
            if (!templateId) {
                throw new Error('Template ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${templateId}/from-template`, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },

        /**
         * Export backpack
         */
        async exportBackpack(id) {
            if (!id) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${id}/export`);
        },

        /**
         * Import backpack
         */
        async importBackpack(data) {
            return apiCall(`${ENDPOINTS.backpacks}/import`, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        },

        /**
         * Add section to backpack
         */
        async addSection(backpackId, sectionData) {
            if (!backpackId) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/sections`, {
                method: 'POST',
                body: JSON.stringify(sectionData)
            });
        },

        /**
         * Update section
         */
        async updateSection(backpackId, sectionId, data) {
            if (!backpackId || !sectionId) {
                throw new Error('Backpack ID and Section ID are required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/sections/${sectionId}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },

        /**
         * Delete section
         */
        async deleteSection(backpackId, sectionId) {
            if (!backpackId || !sectionId) {
                throw new Error('Backpack ID and Section ID are required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/sections/${sectionId}`, {
                method: 'DELETE'
            });
        },

        /**
         * Add item to backpack section
         */
        async addItem(backpackId, sectionId, itemData) {
            if (!backpackId) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/items`, {
                method: 'POST',
                body: JSON.stringify({ section_id: sectionId, ...itemData })
            });
        },

        /**
         * Update item
         */
        async updateItem(backpackId, itemId, data) {
            if (!backpackId || !itemId) {
                throw new Error('Backpack ID and Item ID are required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/items/${itemId}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        },

        /**
         * Delete item
         */
        async deleteItem(backpackId, itemId) {
            if (!backpackId || !itemId) {
                throw new Error('Backpack ID and Item ID are required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/items/${itemId}`, {
                method: 'DELETE'
            });
        },

        /**
         * Move item between sections
         */
        async moveItem(backpackId, itemId, fromSectionId, toSectionId, position) {
            if (!backpackId || !itemId || !fromSectionId || !toSectionId) {
                throw new Error('All IDs are required for moving an item');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/items/${itemId}/move`, {
                method: 'POST',
                body: JSON.stringify({
                    from_section_id: fromSectionId,
                    to_section_id: toSectionId,
                    position: position || 0
                })
            });
        },

        /**
         * Batch update items (for drag and drop operations)
         */
        async batchUpdateItems(backpackId, updates) {
            if (!backpackId) {
                throw new Error('Backpack ID is required');
            }
            return apiCall(`${ENDPOINTS.backpacks}/${backpackId}/items/batch`, {
                method: 'PUT',
                body: JSON.stringify({ updates })
            });
        }
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PackManagerAPI;
}
