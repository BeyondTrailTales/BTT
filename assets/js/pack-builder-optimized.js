/**
 * Pack Builder Optimized - Drag & Drop Handler
 */

const PackBuilderOptimized = {
    init() {
        this.bindEvents();
        this.initDragDrop();
        this.initFilters();
        this.updateWeights();
    },

    bindEvents() {
        // Add section button
        document.getElementById('add-section')?.addEventListener('click', () => {
            this.addNewSection();
        });

        // Section toggles
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('section-toggle')) {
                this.toggleSection(e.target.closest('.pack-section'));
            }
        });

        // Remove item buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-remove-item')) {
                this.removeItem(e.target.closest('.pack-item'));
            }
        });

        // Category filters
        document.querySelectorAll('.cat-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                this.filterByCategory(pill.dataset.category);
            });
        });

        // Filter chips
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                chip.classList.toggle('active');
                this.applyFilters();
            });
        });

        // Search
        document.getElementById('gear-search')?.addEventListener('input', (e) => {
            this.filterBySearch(e.target.value);
        });

        // Section name changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('section-name')) {
                this.updateSectionName(e.target);
            }
        });

        // Quantity changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('item-qty')) {
                this.updateItemQuantity(e.target);
            }
        });
    },

    initDragDrop() {
        // Make all gear items draggable
        this.refreshDraggables();

        // Set up all drop zones
        document.querySelectorAll('.section-dropzone').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                zone.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', (e) => {
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('drag-over');
                }
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                
                const data = e.dataTransfer.getData('text/plain');
                if (data) {
                    try {
                        const itemData = JSON.parse(data);
                        this.addItemToSection(itemData, zone);
                    } catch (err) {
                        console.error('Invalid drag data:', err);
                    }
                }
            });
        });
    },

    refreshDraggables() {
        document.querySelectorAll('.gear-item-card').forEach(item => {
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', (e) => {
                const itemData = {
                    id: item.dataset.itemId || 'item-' + Date.now(),
                    name: item.querySelector('.gear-item-name')?.textContent || 'Unknown Item',
                    weight: item.querySelector('.gear-item-weight')?.textContent || '0g',
                    icon: item.querySelector('.gear-item-icon')?.textContent || '📦',
                    category: item.dataset.category || 'other'
                };
                
                e.dataTransfer.setData('text/plain', JSON.stringify(itemData));
                e.dataTransfer.effectAllowed = 'copy';
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
            });
        });
    },

    addItemToSection(itemData, dropzone) {
        // Remove placeholder if exists
        const hint = dropzone.querySelector('.dropzone-hint');
        if (hint) {
            hint.style.display = 'none';
        }

        // Check if item already exists in this section
        const existingItem = dropzone.querySelector(`[data-item-id="${itemData.id}"]`);
        if (existingItem) {
            // Increase quantity instead of adding duplicate
            const qtyInput = existingItem.querySelector('.item-qty');
            if (qtyInput) {
                qtyInput.value = parseInt(qtyInput.value) + 1;
                this.updateItemQuantity(qtyInput);
            }
            this.showDuoNotification('Item quantity increased!', 'success');
            return;
        }

        // Create pack items list if doesn't exist
        let itemsList = dropzone.querySelector('.pack-items-list');
        if (!itemsList) {
            itemsList = document.createElement('div');
            itemsList.className = 'pack-items-list';
            dropzone.appendChild(itemsList);
        }

        // Create new pack item
        const packItem = document.createElement('div');
        packItem.className = 'pack-item';
        packItem.dataset.itemId = itemData.id;
        packItem.innerHTML = `
            <span class="item-handle">≡</span>
            <span class="item-icon">${itemData.icon}</span>
            <span class="item-name">${itemData.name}</span>
            <input type="number" class="item-qty" value="1" min="1" max="99">
            <span class="item-weight" data-base="${parseInt(itemData.weight)}">${itemData.weight}</span>
            <button class="btn-remove-item">×</button>
        `;

        itemsList.appendChild(packItem);
        this.updateSectionWeight(dropzone.closest('.pack-section'));
        this.updateTotalWeight();
        this.showDuoNotification(`Added ${itemData.name} to pack!`, 'success');
    },

    removeItem(item) {
        const section = item.closest('.pack-section');
        const itemName = item.querySelector('.item-name').textContent;
        
        item.style.animation = 'fadeOut 0.3s';
        setTimeout(() => {
            item.remove();
            this.updateSectionWeight(section);
            this.updateTotalWeight();
            this.checkEmptySection(section);
            this.showDuoNotification(`Removed ${itemName}`, 'info');
        }, 250);
    },

    checkEmptySection(section) {
        const dropzone = section.querySelector('.section-dropzone');
        const items = dropzone.querySelectorAll('.pack-item');
        const hint = dropzone.querySelector('.dropzone-hint');
        
        if (items.length === 0 && hint) {
            hint.style.display = 'block';
        }
    },

    updateItemQuantity(input) {
        const item = input.closest('.pack-item');
        const baseWeight = parseInt(item.querySelector('.item-weight').dataset.base) || 0;
        const qty = parseInt(input.value) || 1;
        const totalWeight = baseWeight * qty;
        
        item.querySelector('.item-weight').textContent = totalWeight + 'g';
        
        this.updateSectionWeight(item.closest('.pack-section'));
        this.updateTotalWeight();
    },

    updateSectionWeight(section) {
        if (!section) return;
        
        let totalWeight = 0;
        section.querySelectorAll('.pack-item').forEach(item => {
            const weight = parseInt(item.querySelector('.item-weight').textContent) || 0;
            totalWeight += weight;
        });
        
        const weightBadge = section.querySelector('.section-weight');
        if (weightBadge) {
            weightBadge.textContent = totalWeight + 'g';
        }
    },

    updateTotalWeight() {
        let totalWeight = 0;
        document.querySelectorAll('.section-weight').forEach(badge => {
            totalWeight += parseInt(badge.textContent) || 0;
        });
        
        const totalDisplay = document.getElementById('total-weight');
        if (totalDisplay) {
            totalDisplay.textContent = totalWeight + 'g';
        }
    },

    toggleSection(section) {
        const content = section.querySelector('.section-content');
        const toggle = section.querySelector('.section-toggle');
        
        if (content.classList.contains('collapsed')) {
            content.classList.remove('collapsed');
            toggle.textContent = '▼';
        } else {
            content.classList.add('collapsed');
            toggle.textContent = '▶';
        }
    },

    addNewSection() {
        this.showDuoPrompt('New Section Name:', 'Enter section name...', (name) => {
            if (!name) return;
            
            const sectionsContainer = document.getElementById('pack-sections');
            const sectionId = 'section-' + Date.now();
            
            const newSection = document.createElement('div');
            newSection.className = 'pack-section';
            newSection.dataset.sectionId = sectionId;
            newSection.innerHTML = `
                <div class="section-header">
                    <button class="section-toggle">▼</button>
                    <span class="section-icon">📦</span>
                    <input type="text" class="section-name" value="${name}">
                    <span class="section-weight">0g</span>
                    <button class="btn-remove-section" onclick="PackBuilderOptimized.removeSection('${sectionId}')">×</button>
                </div>
                <div class="section-content">
                    <div class="section-dropzone" data-section="${sectionId}">
                        <div class="dropzone-hint">Drag gear here</div>
                    </div>
                </div>
            `;
            
            sectionsContainer.appendChild(newSection);
            this.initDropZone(newSection.querySelector('.section-dropzone'));
            this.showDuoNotification(`Section "${name}" added!`, 'success');
        });
    },

    removeSection(sectionId) {
        if (sectionId === 'main') {
            this.showDuoNotification('Cannot remove main compartment', 'error');
            return;
        }
        
        this.showDuoConfirm('Remove this section?', 'All items will be removed.', () => {
            const section = document.querySelector(`[data-section-id="${sectionId}"]`);
            if (section) {
                section.style.animation = 'fadeOut 0.3s';
                setTimeout(() => {
                    section.remove();
                    this.updateTotalWeight();
                    this.showDuoNotification('Section removed', 'info');
                }, 250);
            }
        });
    },

    initDropZone(zone) {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            zone.classList.add('drag-over');
        });

        zone.addEventListener('dragleave', (e) => {
            if (!zone.contains(e.relatedTarget)) {
                zone.classList.remove('drag-over');
            }
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            
            const data = e.dataTransfer.getData('text/plain');
            if (data) {
                try {
                    const itemData = JSON.parse(data);
                    this.addItemToSection(itemData, zone);
                } catch (err) {
                    console.error('Invalid drag data:', err);
                }
            }
        });
    },

    filterByCategory(category) {
        const items = document.querySelectorAll('.gear-item-card');
        items.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    },

    filterBySearch(query) {
        const items = document.querySelectorAll('.gear-item-card');
        const searchLower = query.toLowerCase();
        
        items.forEach(item => {
            const name = item.querySelector('.gear-item-name')?.textContent.toLowerCase() || '';
            if (name.includes(searchLower)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    },

    applyFilters() {
        // Combine all active filters
        const activeFilters = [];
        document.querySelectorAll('.filter-chip.active').forEach(chip => {
            activeFilters.push(chip.id.replace('filter-', ''));
        });
        
        // Apply filters logic here
        // This is a placeholder for filter implementation
    },

    // Duolingo-style notifications
    showDuoNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `duo-notification duo-${type}`;
        notification.innerHTML = `
            <div class="duo-notification-content">
                <span class="duo-notification-icon">${this.getNotificationIcon(type)}</span>
                <span class="duo-notification-message">${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    },

    showDuoPrompt(title, placeholder, callback) {
        const modal = document.createElement('div');
        modal.className = 'duo-modal';
        modal.innerHTML = `
            <div class="duo-modal-content">
                <h3>${title}</h3>
                <input type="text" class="duo-input" placeholder="${placeholder}" id="duo-prompt-input">
                <div class="duo-modal-actions">
                    <button class="btn-duo btn-duo--secondary" onclick="this.closest('.duo-modal').remove()">Cancel</button>
                    <button class="btn-duo btn-duo--primary" id="duo-prompt-confirm">Add</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const input = modal.querySelector('#duo-prompt-input');
        const confirmBtn = modal.querySelector('#duo-prompt-confirm');
        
        input.focus();
        
        const confirm = () => {
            const value = input.value.trim();
            if (value) {
                callback(value);
                modal.remove();
            }
        };
        
        confirmBtn.addEventListener('click', confirm);
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') confirm();
        });
        
        setTimeout(() => modal.classList.add('show'), 10);
    },

    showDuoConfirm(title, message, callback) {
        const modal = document.createElement('div');
        modal.className = 'duo-modal';
        modal.innerHTML = `
            <div class="duo-modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="duo-modal-actions">
                    <button class="btn-duo btn-duo--secondary" onclick="this.closest('.duo-modal').remove()">Cancel</button>
                    <button class="btn-duo btn-duo--primary" id="duo-confirm-yes">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('#duo-confirm-yes').addEventListener('click', () => {
            callback();
            modal.remove();
        });
        
        setTimeout(() => modal.classList.add('show'), 10);
    },

    getNotificationIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️',
            warning: '⚠️'
        };
        return icons[type] || icons.info;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    PackBuilderOptimized.init();
    
    // Initialize pack builder if it exists
    if (window.packBuilder) {
        // Refresh draggables when gear is loaded
        const originalDisplayGear = window.packBuilder.displayGear;
        window.packBuilder.displayGear = function() {
            originalDisplayGear.call(this);
            setTimeout(() => PackBuilderOptimized.refreshDraggables(), 100);
        };
    }
});

// Add fadeOut animation
const style = document.createElement('style');
style.textContent = `
@keyframes fadeOut {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(0.9); }
}
`;
document.head.appendChild(style);
