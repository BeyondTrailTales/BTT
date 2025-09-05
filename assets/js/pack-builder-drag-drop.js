/**
 * Pack Builder Drag & Drop Handler
 * Optimized for single-column layout with fixed gear library
 */

const PackBuilderDragDrop = {
    init() {
        this.initDragAndDrop();
        this.initScrollBehavior();
        this.initAccessibility();
    },

    initDragAndDrop() {
        // Make gear items draggable
        document.querySelectorAll('.gear-item').forEach(item => {
            item.setAttribute('draggable', 'true');
            item.setAttribute('role', 'button');
            item.setAttribute('aria-grabbed', 'false');

            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', item.dataset.gearId);
                item.classList.add('dragging');
                item.setAttribute('aria-grabbed', 'true');
                
                // Set drag image
                const dragImage = item.cloneNode(true);
                dragImage.style.opacity = '0.7';
                dragImage.style.position = 'absolute';
                dragImage.style.top = '-1000px';
                document.body.appendChild(dragImage);
                e.dataTransfer.setDragImage(dragImage, 10, 10);
                setTimeout(() => document.body.removeChild(dragImage), 0);
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                item.setAttribute('aria-grabbed', 'false');
                document.querySelectorAll('.section-dropzone').forEach(zone => {
                    zone.classList.remove('drag-active');
                });
            });
        });

        // Handle drop zones
        document.querySelectorAll('.section-dropzone').forEach(zone => {
            zone.setAttribute('role', 'region');
            zone.setAttribute('aria-dropeffect', 'copy');

            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!zone.classList.contains('drag-active')) {
                    zone.classList.add('drag-active');
                }
            });

            zone.addEventListener('dragleave', (e) => {
                // Only remove class if we're actually leaving the zone (not entering a child)
                if (!e.relatedTarget || !zone.contains(e.relatedTarget)) {
                    zone.classList.remove('drag-active');
                }
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-active');

                const gearId = e.dataTransfer.getData('text/plain');
                this.handleDrop(gearId, zone);
            });
        });
    },

    initScrollBehavior() {
        const gearLibrary = document.querySelector('.gear-items-container');
        const packSections = document.querySelector('.pack-sections');
        const scrollZoneSize = 50; // pixels from edge that triggers scroll

        // Auto-scroll during drag
        document.addEventListener('dragover', (e) => {
            if (gearLibrary) {
                const libraryRect = gearLibrary.getBoundingClientRect();
                if (e.clientY >= libraryRect.top && e.clientY <= libraryRect.bottom) {
                    if (e.clientY - libraryRect.top < scrollZoneSize) {
                        gearLibrary.scrollBy({ top: -10, behavior: 'smooth' });
                    } else if (libraryRect.bottom - e.clientY < scrollZoneSize) {
                        gearLibrary.scrollBy({ top: 10, behavior: 'smooth' });
                    }
                }
            }

            if (packSections) {
                const sectionsRect = packSections.getBoundingClientRect();
                if (e.clientY >= sectionsRect.top && e.clientY <= sectionsRect.bottom) {
                    if (e.clientY - sectionsRect.top < scrollZoneSize) {
                        packSections.scrollBy({ top: -10, behavior: 'smooth' });
                    } else if (sectionsRect.bottom - e.clientY < scrollZoneSize) {
                        packSections.scrollBy({ top: 10, behavior: 'smooth' });
                    }
                }
            }
        });
    },

    initAccessibility() {
        // Keyboard handling for gear items
        document.querySelectorAll('.gear-item').forEach(item => {
            item.setAttribute('tabindex', '0');
            
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.showSectionSelectionDialog(item);
                }
            });
        });

        // Section headers keyboard navigation
        document.querySelectorAll('.section-header').forEach(header => {
            header.setAttribute('tabindex', '0');
            header.setAttribute('role', 'button');
            header.setAttribute('aria-expanded', 'true');

            header.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    const section = header.closest('.pack-section');
                    this.toggleSection(section);
                }
            });
        });
    },

    handleDrop(gearId, zone) {
        const gearItem = document.querySelector(`[data-gear-id="${gearId}"]`);
        if (!gearItem) return;

        // Get the section information
        const section = zone.closest('.pack-section');
        const sectionId = section.dataset.sectionId;

        // Create the pack item
        const packItem = this.createPackItem(gearItem);
        
        // Clear placeholder if it exists
        const placeholder = zone.querySelector('.dropzone-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        // Add to pack items container or create one
        let packItems = zone.querySelector('.pack-items');
        if (!packItems) {
            packItems = document.createElement('div');
            packItems.className = 'pack-items';
            zone.appendChild(packItems);
        }

        packItems.appendChild(packItem);
        this.updateSectionWeight(section);
        
        // Announce for screen readers
        this.announceAction(`Added ${gearItem.dataset.name} to ${section.querySelector('.section-name').value}`);
    },

    createPackItem(gearItem) {
        const packItem = document.createElement('div');
        packItem.className = 'pack-item';
        packItem.dataset.gearId = gearItem.dataset.gearId;
        packItem.setAttribute('role', 'listitem');
        packItem.setAttribute('tabindex', '0');

        packItem.innerHTML = `
            <div class="pack-item-content">
                <span class="item-handle" aria-hidden="true">≡</span>
                <i class="item-icon">${gearItem.dataset.icon || '📦'}</i>
                <span class="item-name">${gearItem.dataset.name}</span>
                <div class="item-qty-wrapper">
                    <button class="qty-decrease" aria-label="Decrease quantity">-</button>
                    <span class="item-qty-display" aria-label="Quantity">1</span>
                    <button class="qty-increase" aria-label="Increase quantity">+</button>
                </div>
                <span class="item-weight">${gearItem.dataset.weight}</span>
                <button class="btn-remove-item" aria-label="Remove ${gearItem.dataset.name}">×</button>
            </div>
        `;

        // Add event listeners
        const removeBtn = packItem.querySelector('.btn-remove-item');
        removeBtn.addEventListener('click', () => {
            packItem.remove();
            this.updateSectionWeight(packItem.closest('.pack-section'));
            this.announceAction(`Removed ${gearItem.dataset.name} from pack`);
        });

        // Quantity controls
        const qtyDecrease = packItem.querySelector('.qty-decrease');
        const qtyIncrease = packItem.querySelector('.qty-increase');
        const qtyDisplay = packItem.querySelector('.item-qty-display');

        qtyDecrease.addEventListener('click', () => {
            const currentQty = parseInt(qtyDisplay.textContent);
            if (currentQty > 1) {
                qtyDisplay.textContent = currentQty - 1;
                this.updateSectionWeight(packItem.closest('.pack-section'));
            }
        });

        qtyIncrease.addEventListener('click', () => {
            const currentQty = parseInt(qtyDisplay.textContent);
            if (currentQty < 99) {
                qtyDisplay.textContent = currentQty + 1;
                this.updateSectionWeight(packItem.closest('.pack-section'));
            }
        });

        return packItem;
    },

    updateSectionWeight(section) {
        if (!section) return;

        let totalWeight = 0;
        section.querySelectorAll('.pack-item').forEach(item => {
            const weight = parseInt(item.querySelector('.item-weight').textContent);
            const quantity = parseInt(item.querySelector('.item-qty-display').textContent);
            if (!isNaN(weight) && !isNaN(quantity)) {
                totalWeight += weight * quantity;
            }
        });

        const weightDisplay = section.querySelector('.section-weight');
        weightDisplay.textContent = `${totalWeight}g`;
        weightDisplay.setAttribute('aria-label', `Section weight: ${totalWeight} grams`);

        // Update total pack weight
        this.updateTotalWeight();
    },

    updateTotalWeight() {
        let totalWeight = 0;
        document.querySelectorAll('.section-weight').forEach(weight => {
            const sectionWeight = parseInt(weight.textContent);
            if (!isNaN(sectionWeight)) {
                totalWeight += sectionWeight;
            }
        });

        const totalWeightDisplay = document.getElementById('total-weight');
        if (totalWeightDisplay) {
            totalWeightDisplay.textContent = `${totalWeight}g`;
            totalWeightDisplay.setAttribute('aria-label', `Total pack weight: ${totalWeight} grams`);
        }
    },

    toggleSection(section) {
        const content = section.querySelector('.section-content');
        const header = section.querySelector('.section-header');
        const isExpanded = section.classList.contains('expanded');

        if (isExpanded) {
            section.classList.remove('expanded');
            content.style.maxHeight = null;
            header.setAttribute('aria-expanded', 'false');
        } else {
            section.classList.add('expanded');
            content.style.maxHeight = content.scrollHeight + 'px';
            header.setAttribute('aria-expanded', 'true');
        }
    },

    showSectionSelectionDialog(gearItem) {
        // Create and show a dialog for keyboard users to select a section
        const dialog = document.createElement('div');
        dialog.className = 'section-selection-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-label', 'Select section for gear');

        dialog.innerHTML = `
            <div class="dialog-content">
                <h3>Add ${gearItem.dataset.name} to section:</h3>
                <div class="section-options">
                    ${Array.from(document.querySelectorAll('.pack-section')).map(section => `
                        <button class="section-option" data-section-id="${section.dataset.sectionId}">
                            ${section.querySelector('.section-name').value}
                        </button>
                    `).join('')}
                </div>
                <button class="dialog-close">Cancel</button>
            </div>
        `;

        document.body.appendChild(dialog);

        // Handle section selection
        dialog.querySelectorAll('.section-option').forEach(option => {
            option.addEventListener('click', () => {
                const sectionId = option.dataset.sectionId;
                const dropzone = document.querySelector(`.pack-section[data-section-id="${sectionId}"] .section-dropzone`);
                this.handleDrop(gearItem.dataset.gearId, dropzone);
                dialog.remove();
            });
        });

        // Handle cancel
        dialog.querySelector('.dialog-close').addEventListener('click', () => {
            dialog.remove();
        });

        // Close on escape
        dialog.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                dialog.remove();
            }
        });

        // Focus first option
        dialog.querySelector('.section-option').focus();
    },

    announceAction(message) {
        // Announce actions for screen readers
        const announcement = document.getElementById('pack-announcements');
        if (!announcement) {
            const newAnnouncement = document.createElement('div');
            newAnnouncement.id = 'pack-announcements';
            newAnnouncement.className = 'sr-only';
            newAnnouncement.setAttribute('role', 'status');
            newAnnouncement.setAttribute('aria-live', 'polite');
            document.body.appendChild(newAnnouncement);
        }
        announcement.textContent = message;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    PackBuilderDragDrop.init();
});
