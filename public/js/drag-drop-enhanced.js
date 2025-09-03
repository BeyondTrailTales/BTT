/**
 * Enhanced Drag & Drop System
 * Provides visual feedback, keyboard support, and accessibility
 */

class DragDropManager {
    constructor(options = {}) {
        this.options = {
            animation: 180,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            chosenClass: 'sortable-chosen',
            dropzoneClass: 'dropzone-active',
            invalidClass: 'dropzone-invalid',
            handleClass: 'drag-handle',
            ...options
        };
        
        this.draggedItem = null;
        this.dropzones = new Map();
        this.keyboardMode = false;
        this.grabbedElement = null;
        
        this.init();
    }
    
    init() {
        // Add global styles for drag states
        this.injectStyles();
        
        // Initialize keyboard support
        this.initKeyboardSupport();
        
        // Initialize ARIA live region for announcements
        this.initAriaAnnouncer();
    }
    
    /**
     * Initialize a draggable container
     */
    initDraggable(container, options = {}) {
        const element = typeof container === 'string' ? 
            document.querySelector(container) : container;
            
        if (!element) return null;
        
        // Merge options
        const config = {
            ...this.options,
            ...options,
            group: options.group || 'shared',
            animation: this.options.animation,
            ghostClass: this.options.ghostClass,
            dragClass: this.options.dragClass,
            chosenClass: this.options.chosenClass,
            handle: options.handle || `.${this.options.handleClass}`,
            
            // Event handlers
            onStart: (evt) => this.handleDragStart(evt, options),
            onEnd: (evt) => this.handleDragEnd(evt, options),
            onAdd: (evt) => this.handleAdd(evt, options),
            onUpdate: (evt) => this.handleUpdate(evt, options),
            onMove: (evt) => this.handleMove(evt, options)
        };
        
        // Initialize Sortable if available
        if (typeof Sortable !== 'undefined') {
            const sortable = new Sortable(element, config);
            this.dropzones.set(element, sortable);
            return sortable;
        }
        
        // Fallback to native drag and drop
        this.initNativeDragDrop(element, config);
        return element;
    }
    
    /**
     * Initialize native HTML5 drag and drop
     */
    initNativeDragDrop(container, config) {
        const items = container.querySelectorAll('[draggable="true"]');
        
        items.forEach(item => {
            // Add drag handle if specified
            if (config.handle && !item.querySelector(config.handle)) {
                const handle = document.createElement('div');
                handle.className = this.options.handleClass;
                handle.innerHTML = '☰';
                handle.setAttribute('aria-label', 'Drag handle');
                item.prepend(handle);
            }
            
            // Make items focusable for keyboard support
            if (!item.hasAttribute('tabindex')) {
                item.setAttribute('tabindex', '0');
            }
            
            // Add ARIA attributes
            item.setAttribute('aria-grabbed', 'false');
            item.setAttribute('role', 'listitem');
            
            // Drag events
            item.addEventListener('dragstart', (e) => this.onDragStart(e, item));
            item.addEventListener('dragend', (e) => this.onDragEnd(e, item));
            item.addEventListener('dragenter', (e) => this.onDragEnter(e, item));
            item.addEventListener('dragleave', (e) => this.onDragLeave(e, item));
            item.addEventListener('dragover', (e) => this.onDragOver(e));
            item.addEventListener('drop', (e) => this.onDrop(e, item, config));
            
            // Touch events for mobile
            this.addTouchSupport(item);
        });
        
        // Mark container as drop zone
        container.setAttribute('role', 'list');
        container.setAttribute('aria-dropeffect', 'move');
    }
    
    /**
     * Handle drag start
     */
    onDragStart(e, item) {
        this.draggedItem = item;
        item.classList.add(this.options.dragClass);
        item.setAttribute('aria-grabbed', 'true');
        
        // Store drag data
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', item.innerHTML);
        
        // Visual feedback
        this.highlightDropzones();
        
        // Announce to screen readers
        this.announce(`Grabbed ${this.getItemLabel(item)}. Use arrow keys to move.`);
    }
    
    /**
     * Handle drag end
     */
    onDragEnd(e, item) {
        item.classList.remove(this.options.dragClass);
        item.setAttribute('aria-grabbed', 'false');
        
        // Clear highlights
        this.clearHighlights();
        
        // Announce completion
        this.announce('Item dropped');
        
        this.draggedItem = null;
    }
    
    /**
     * Handle drag enter
     */
    onDragEnter(e, item) {
        if (!this.draggedItem || item === this.draggedItem) return;
        
        item.classList.add('dropzone-hover');
        
        // Show drop indicator
        this.showDropIndicator(item);
    }
    
    /**
     * Handle drag leave
     */
    onDragLeave(e, item) {
        item.classList.remove('dropzone-hover');
        this.hideDropIndicator(item);
    }
    
    /**
     * Handle drag over
     */
    onDragOver(e) {
        e.preventDefault(); // Allow drop
        e.dataTransfer.dropEffect = 'move';
    }
    
    /**
     * Handle drop
     */
    onDrop(e, target, config) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!this.draggedItem || target === this.draggedItem) return;
        
        // Animate the drop
        this.animateDrop(this.draggedItem, target, () => {
            // Move the item
            const parent = target.parentNode;
            const targetIndex = Array.from(parent.children).indexOf(target);
            const draggedIndex = Array.from(parent.children).indexOf(this.draggedItem);
            
            if (draggedIndex < targetIndex) {
                parent.insertBefore(this.draggedItem, target.nextSibling);
            } else {
                parent.insertBefore(this.draggedItem, target);
            }
            
            // Call update callback
            if (config.onUpdate) {
                config.onUpdate({ item: this.draggedItem, target });
            }
            
            // Announce change
            this.announce(`Moved ${this.getItemLabel(this.draggedItem)} to position ${targetIndex + 1}`);
        });
        
        // Clear states
        target.classList.remove('dropzone-hover');
        this.hideDropIndicator(target);
    }
    
    /**
     * Initialize keyboard support
     */
    initKeyboardSupport() {
        document.addEventListener('keydown', (e) => {
            const focused = document.activeElement;
            
            if (!focused || !focused.hasAttribute('draggable')) return;
            
            switch(e.key) {
                case ' ':
                case 'Enter':
                    e.preventDefault();
                    this.toggleGrab(focused);
                    break;
                    
                case 'ArrowUp':
                    if (this.grabbedElement) {
                        e.preventDefault();
                        this.moveItem(this.grabbedElement, 'up');
                    }
                    break;
                    
                case 'ArrowDown':
                    if (this.grabbedElement) {
                        e.preventDefault();
                        this.moveItem(this.grabbedElement, 'down');
                    }
                    break;
                    
                case 'Escape':
                    if (this.grabbedElement) {
                        e.preventDefault();
                        this.cancelGrab();
                    }
                    break;
            }
        });
    }
    
    /**
     * Toggle grab state for keyboard
     */
    toggleGrab(element) {
        const isGrabbed = element.getAttribute('aria-grabbed') === 'true';
        
        if (isGrabbed) {
            // Release
            element.setAttribute('aria-grabbed', 'false');
            element.classList.remove(this.options.chosenClass);
            this.grabbedElement = null;
            this.announce('Released ' + this.getItemLabel(element));
        } else {
            // Grab
            element.setAttribute('aria-grabbed', 'true');
            element.classList.add(this.options.chosenClass);
            this.grabbedElement = element;
            this.announce(`Grabbed ${this.getItemLabel(element)}. Use arrow keys to move, Escape to cancel.`);
        }
    }
    
    /**
     * Move item with keyboard
     */
    moveItem(element, direction) {
        const parent = element.parentNode;
        const sibling = direction === 'up' ? 
            element.previousElementSibling : 
            element.nextElementSibling;
            
        if (!sibling) {
            this.announce(`Cannot move ${direction}, at ${direction === 'up' ? 'beginning' : 'end'} of list`);
            return;
        }
        
        // Animate movement
        this.animateSwap(element, sibling, () => {
            if (direction === 'up') {
                parent.insertBefore(element, sibling);
            } else {
                parent.insertBefore(sibling, element);
            }
            
            // Keep focus
            element.focus();
            
            // Announce new position
            const newIndex = Array.from(parent.children).indexOf(element) + 1;
            this.announce(`Moved to position ${newIndex} of ${parent.children.length}`);
        });
    }
    
    /**
     * Cancel grab
     */
    cancelGrab() {
        if (this.grabbedElement) {
            this.grabbedElement.setAttribute('aria-grabbed', 'false');
            this.grabbedElement.classList.remove(this.options.chosenClass);
            this.announce('Cancelled move');
            this.grabbedElement = null;
        }
    }
    
    /**
     * Add touch support for mobile
     */
    addTouchSupport(element) {
        let touchItem = null;
        let touchOffset = { x: 0, y: 0 };
        let touchClone = null;
        
        element.addEventListener('touchstart', (e) => {
            touchItem = element;
            const touch = e.touches[0];
            const rect = element.getBoundingClientRect();
            touchOffset.x = touch.clientX - rect.left;
            touchOffset.y = touch.clientY - rect.top;
            
            // Create clone for visual feedback
            touchClone = element.cloneNode(true);
            touchClone.style.position = 'fixed';
            touchClone.style.zIndex = '9999';
            touchClone.style.opacity = '0.8';
            touchClone.style.pointerEvents = 'none';
            touchClone.style.width = rect.width + 'px';
            document.body.appendChild(touchClone);
            
            element.classList.add(this.options.dragClass);
        });
        
        element.addEventListener('touchmove', (e) => {
            if (!touchItem || !touchClone) return;
            
            e.preventDefault();
            const touch = e.touches[0];
            
            // Move clone
            touchClone.style.left = (touch.clientX - touchOffset.x) + 'px';
            touchClone.style.top = (touch.clientY - touchOffset.y) + 'px';
            
            // Find drop target
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            if (elementBelow && elementBelow !== touchItem && elementBelow.hasAttribute('draggable')) {
                this.showDropIndicator(elementBelow);
            }
        });
        
        element.addEventListener('touchend', (e) => {
            if (!touchItem || !touchClone) return;
            
            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            
            // Handle drop
            if (elementBelow && elementBelow !== touchItem && elementBelow.hasAttribute('draggable')) {
                this.onDrop({ preventDefault: () => {}, stopPropagation: () => {} }, elementBelow, {});
            }
            
            // Clean up
            if (touchClone) {
                document.body.removeChild(touchClone);
                touchClone = null;
            }
            
            element.classList.remove(this.options.dragClass);
            this.clearHighlights();
            touchItem = null;
        });
    }
    
    /**
     * Highlight valid dropzones
     */
    highlightDropzones() {
        this.dropzones.forEach((sortable, container) => {
            container.classList.add('dropzone-highlight');
        });
    }
    
    /**
     * Clear dropzone highlights
     */
    clearHighlights() {
        document.querySelectorAll('.dropzone-highlight, .dropzone-hover, .drop-indicator').forEach(el => {
            el.classList.remove('dropzone-highlight', 'dropzone-hover');
        });
        
        // Remove all drop indicators
        document.querySelectorAll('.drop-indicator').forEach(el => el.remove());
    }
    
    /**
     * Show drop indicator
     */
    showDropIndicator(target) {
        // Remove existing indicators
        document.querySelectorAll('.drop-indicator').forEach(el => el.remove());
        
        const indicator = document.createElement('div');
        indicator.className = 'drop-indicator';
        indicator.innerHTML = '▼ Drop here';
        
        target.parentNode.insertBefore(indicator, target);
    }
    
    /**
     * Hide drop indicator
     */
    hideDropIndicator(target) {
        const indicator = target.parentNode.querySelector('.drop-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
    
    /**
     * Animate drop action
     */
    animateDrop(item, target, callback) {
        item.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
        item.style.transform = 'scale(1.05)';
        item.style.opacity = '0.8';
        
        setTimeout(() => {
            item.style.transform = 'scale(1)';
            item.style.opacity = '1';
            
            setTimeout(() => {
                item.style.transition = '';
                callback();
            }, 300);
        }, 100);
    }
    
    /**
     * Animate swap for keyboard movement
     */
    animateSwap(item1, item2, callback) {
        // Calculate positions
        const rect1 = item1.getBoundingClientRect();
        const rect2 = item2.getBoundingClientRect();
        const deltaY = rect2.top - rect1.top;
        
        // Animate
        item1.style.transition = 'transform 0.2s ease';
        item2.style.transition = 'transform 0.2s ease';
        
        item1.style.transform = `translateY(${deltaY}px)`;
        item2.style.transform = `translateY(${-deltaY}px)`;
        
        setTimeout(() => {
            item1.style.transition = '';
            item2.style.transition = '';
            item1.style.transform = '';
            item2.style.transform = '';
            callback();
        }, 200);
    }
    
    /**
     * Get item label for announcements
     */
    getItemLabel(item) {
        return item.getAttribute('aria-label') || 
               item.querySelector('.item-name')?.textContent || 
               'item';
    }
    
    /**
     * Initialize ARIA announcer
     */
    initAriaAnnouncer() {
        if (!document.getElementById('drag-drop-announcer')) {
            const announcer = document.createElement('div');
            announcer.id = 'drag-drop-announcer';
            announcer.className = 'sr-only';
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
    }
    
    /**
     * Announce to screen readers
     */
    announce(message) {
        const announcer = document.getElementById('drag-drop-announcer');
        if (announcer) {
            announcer.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }
    
    /**
     * Inject required styles
     */
    injectStyles() {
        if (document.getElementById('drag-drop-styles')) return;
        
        const styles = `
            .sortable-ghost {
                opacity: 0.4;
                background: rgba(74, 222, 128, 0.1) !important;
            }
            
            .sortable-drag {
                opacity: 0.8 !important;
                transform: rotate(2deg);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
            }
            
            .sortable-chosen {
                background: rgba(74, 222, 128, 0.05) !important;
                border: 2px solid rgba(74, 222, 128, 0.5) !important;
            }
            
            .dropzone-highlight {
                background: rgba(74, 222, 128, 0.03) !important;
                border: 2px dashed rgba(74, 222, 128, 0.3) !important;
                transition: all 0.2s ease;
            }
            
            .dropzone-hover {
                background: rgba(74, 222, 128, 0.1) !important;
                transform: scale(1.02);
                transition: all 0.2s ease;
            }
            
            .dropzone-invalid {
                background: rgba(239, 68, 68, 0.1) !important;
                border-color: rgba(239, 68, 68, 0.3) !important;
                animation: shake 0.5s;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
                20%, 40%, 60%, 80% { transform: translateX(2px); }
            }
            
            .drop-indicator {
                height: 2px;
                background: linear-gradient(90deg, transparent, #4ade80, transparent);
                margin: 0.5rem 0;
                position: relative;
                animation: pulse 1s ease infinite;
                text-align: center;
                font-size: 0.75rem;
                color: #4ade80;
                font-weight: 600;
            }
            
            @keyframes pulse {
                0%, 100% { opacity: 0.6; }
                50% { opacity: 1; }
            }
            
            .drag-handle {
                cursor: move;
                cursor: grab;
                padding: 0.25rem 0.5rem;
                color: #9ca3af;
                transition: color 0.2s ease;
                user-select: none;
            }
            
            .drag-handle:hover {
                color: #4ade80;
            }
            
            .drag-handle:active {
                cursor: grabbing;
            }
            
            [aria-grabbed="true"] {
                outline: 3px solid #4ade80 !important;
                outline-offset: 2px;
            }
            
            .sr-only {
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.id = 'drag-drop-styles';
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
    
    /**
     * Handle Sortable.js events
     */
    handleDragStart(evt, options) {
        this.highlightDropzones();
        evt.item.setAttribute('aria-grabbed', 'true');
        this.announce(`Grabbed ${this.getItemLabel(evt.item)}`);
        
        if (options.onStart) {
            options.onStart(evt);
        }
    }
    
    handleDragEnd(evt, options) {
        this.clearHighlights();
        evt.item.setAttribute('aria-grabbed', 'false');
        this.announce('Item dropped');
        
        if (options.onEnd) {
            options.onEnd(evt);
        }
    }
    
    handleAdd(evt, options) {
        this.announce(`Added ${this.getItemLabel(evt.item)} to ${evt.to.getAttribute('aria-label') || 'list'}`);
        
        if (options.onAdd) {
            options.onAdd(evt);
        }
    }
    
    handleUpdate(evt, options) {
        const newIndex = evt.newIndex + 1;
        const total = evt.to.children.length;
        this.announce(`Moved to position ${newIndex} of ${total}`);
        
        if (options.onUpdate) {
            options.onUpdate(evt);
        }
    }
    
    handleMove(evt, options) {
        // Check if move is allowed
        if (options.onMove) {
            return options.onMove(evt);
        }
        
        // Visual feedback for invalid moves
        if (evt.related && evt.related.classList.contains('no-drop')) {
            evt.related.classList.add('dropzone-invalid');
            setTimeout(() => {
                evt.related.classList.remove('dropzone-invalid');
            }, 500);
            return false;
        }
        
        return true;
    }
}

// Initialize global drag drop manager
window.dragDropManager = new DragDropManager();

// Convenience function
window.initDragDrop = function(container, options) {
    return window.dragDropManager.initDraggable(container, options);
};
