/**
 * Enhanced Pack Sections
 * Handles the collapsible sections and drag-drop functionality
 */

const PackSectionsEnhanced = {
    init() {
        this.bindEvents();
        this.initializeDragDrop();
        this.initializeCollapsible();
    },

    bindEvents() {
        // Add section button
        const addSectionBtn = document.getElementById('add-section');
        if (addSectionBtn) {
            addSectionBtn.addEventListener('click', () => this.addNewSection());
        }

        // Section header clicks for collapse/expand
        document.querySelectorAll('.section-header').forEach(header => {
            header.addEventListener('click', (e) => {
                if (!e.target.matches('input, button')) {
                    this.toggleSection(header.closest('.pack-section'));
                }
            });
        });

        // Section toggle buttons
        document.querySelectorAll('.btn-section-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleSection(btn.closest('.pack-section'));
            });
        });

        // Section name inputs
        document.querySelectorAll('.section-name').forEach(input => {
            input.addEventListener('change', () => this.updateSectionName(input));
        });
    },

    initializeDragDrop() {
        // Initialize drag-drop zones
        document.querySelectorAll('.section-content').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('drag-active');
            });

            zone.addEventListener('dragleave', () => {
                zone.classList.remove('drag-active');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-active');
                this.handleDrop(e, zone);
            });
        });

        // Make gear items draggable
        document.querySelectorAll('.gear-item').forEach(item => {
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', item.dataset.gearId);
                item.classList.add('item-dragging');
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('item-dragging');
            });
        });
    },

    initializeCollapsible() {
        // Expand first section by default
        const firstSection = document.querySelector('.pack-section');
        if (firstSection) {
            this.expandSection(firstSection);
        }
    },

    toggleSection(section) {
        if (!section) return;
        
        const content = section.querySelector('.section-content');
        const button = section.querySelector('.btn-section-toggle');
        const isExpanded = content.classList.contains('expanded');

        if (isExpanded) {
            this.collapseSection(section);
        } else {
            this.expandSection(section);
        }
    },

    expandSection(section) {
        const content = section.querySelector('.section-content');
        const button = section.querySelector('.btn-section-toggle');

        content.classList.add('expanded');
        content.style.maxHeight = content.scrollHeight + 'px';
        button.textContent = '▼';
        button.setAttribute('aria-expanded', 'true');
    },

    collapseSection(section) {
        const content = section.querySelector('.section-content');
        const button = section.querySelector('.btn-section-toggle');

        content.classList.remove('expanded');
        content.style.maxHeight = null;
        button.textContent = '▶';
        button.setAttribute('aria-expanded', 'false');
    },

    addNewSection() {
        const sectionsContainer = document.getElementById('sections-list');
        const newSectionId = 'section-' + Date.now();
        
        const sectionHTML = `
            <div class="pack-section" data-section-id="${newSectionId}">
                <div class="section-header">
                    <span class="section-handle" aria-hidden="true">≡</span>
                    <i class="section-icon">📦</i>
                    <input type="text" class="section-name" value="New Section" aria-label="Section name">
                    <span class="section-weight">0g</span>
                    <button class="btn-section-toggle" aria-label="Toggle section">▼</button>
                </div>
                <div class="section-content dropzone" data-section="${newSectionId}">
                    <div class="section-dropzone">
                        <p>Drag gear here or click to add items</p>
                    </div>
                </div>
            </div>
        `;

        sectionsContainer.insertAdjacentHTML('beforeend', sectionHTML);
        
        const newSection = sectionsContainer.lastElementChild;
        this.bindSectionEvents(newSection);
        this.expandSection(newSection);
        
        // Focus the name input for immediate editing
        newSection.querySelector('.section-name').focus();
    },

    bindSectionEvents(section) {
        // Bind all necessary events for the new section
        const header = section.querySelector('.section-header');
        const toggleBtn = section.querySelector('.btn-section-toggle');
        const nameInput = section.querySelector('.section-name');
        const content = section.querySelector('.section-content');

        header.addEventListener('click', (e) => {
            if (!e.target.matches('input, button')) {
                this.toggleSection(section);
            }
        });

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleSection(section);
        });

        nameInput.addEventListener('change', () => this.updateSectionName(nameInput));

        // Initialize drag-drop for the new section
        content.addEventListener('dragover', (e) => {
            e.preventDefault();
            content.classList.add('drag-active');
        });

        content.addEventListener('dragleave', () => {
            content.classList.remove('drag-active');
        });

        content.addEventListener('drop', (e) => {
            e.preventDefault();
            content.classList.remove('drag-active');
            this.handleDrop(e, content);
        });
    },

    updateSectionName(input) {
        // Add any additional logic needed when section name changes
        const section = input.closest('.pack-section');
        section.dataset.name = input.value;
    },

    handleDrop(e, zone) {
        const gearId = e.dataTransfer.getData('text/plain');
        const gearItem = document.querySelector(`[data-gear-id="${gearId}"]`);
        
        if (!gearItem) return;

        // Create pack item from gear item
        const packItem = this.createPackItem(gearItem);
        
        // Find or create items container
        let itemsContainer = zone.querySelector('.pack-items');
        if (!itemsContainer) {
            itemsContainer = document.createElement('div');
            itemsContainer.className = 'pack-items';
            zone.innerHTML = ''; // Remove placeholder
            zone.appendChild(itemsContainer);
        }

        itemsContainer.appendChild(packItem);
        this.updateSectionWeight(zone.closest('.pack-section'));
    },

    createPackItem(gearItem) {
        const packItem = document.createElement('div');
        packItem.className = 'pack-item';
        packItem.dataset.gearId = gearItem.dataset.gearId;
        
        packItem.innerHTML = `
            <div class="pack-item-content">
                <i class="item-icon">${gearItem.dataset.icon || '📦'}</i>
                <span class="item-name">${gearItem.dataset.name}</span>
                <span class="item-weight">${gearItem.dataset.weight}</span>
            </div>
            <div class="pack-item-actions">
                <button class="btn-remove-item" aria-label="Remove item">×</button>
            </div>
        `;

        // Add remove functionality
        packItem.querySelector('.btn-remove-item').addEventListener('click', () => {
            packItem.remove();
            this.updateSectionWeight(packItem.closest('.pack-section'));
        });

        return packItem;
    },

    updateSectionWeight(section) {
        if (!section) return;

        const items = section.querySelectorAll('.pack-item');
        let totalWeight = 0;

        items.forEach(item => {
            const weight = parseInt(item.querySelector('.item-weight').textContent);
            if (!isNaN(weight)) {
                totalWeight += weight;
            }
        });

        const weightDisplay = section.querySelector('.section-weight');
        weightDisplay.textContent = `${totalWeight}g`;

        // Trigger event for total pack weight update
        const event = new CustomEvent('packWeightUpdate', {
            detail: { sectionId: section.dataset.sectionId, weight: totalWeight }
        });
        document.dispatchEvent(event);
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    PackSectionsEnhanced.init();
});
