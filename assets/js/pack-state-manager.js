/**
 * Pack State Manager
 * Handles all pack operations with proper state management
 */
class PackStateManager {
    constructor() {
        // State flags
        this.isLoading = false;
        this.isSaving = false;
        this.isDeleting = false;
        
        // Event handlers
        this.bindEvents();
        
        // Debug mode
        this.debug = true;
    }
    
    log(...args) {
        if (this.debug) {
            console.log('[PackManager]', ...args);
        }
    }
    
    bindEvents() {
        const self = this;
        
        // Direct button actions
        $(document).on('click', '.btn-edit-pack, .btn-primary[data-pack-id]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const packId = $(this).data('pack-id');
            if (packId) {
                self.log('Edit clicked:', packId);
                self.editPack(packId);
            }
        });
        
        $(document).on('click', '.btn-delete-pack', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const packId = $(this).data('pack-id');
            if (packId) {
                self.log('Delete clicked:', packId);
                self.deletePack(packId);
            }
        });
        
        $(document).on('click', '.btn-duplicate-pack', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const packId = $(this).data('pack-id');
            if (packId) {
                self.log('Duplicate clicked:', packId);
                self.duplicatePack(packId);
            }
        });
        
        // Quick actions
        $(document).on('click', '.btn-create-new-pack, #quick-create', function(e) {
            e.preventDefault();
            e.stopPropagation();
            self.log('Create new clicked');
            self.createNewPack();
        });
    }
    
    editPack(packId) {
        if (this.isLoading) {
            this.log('Already loading, skipping edit');
            return;
        }
        
        this.isLoading = true;
        this.log('Loading pack:', packId);
        
        // First switch view
        window.packBuilder.switchView('builder');
        
        // Then load the pack
        $.ajax({
            url: BTT.apiUrl + '/?route=backpacks&id=' + packId,
            method: 'GET',
            headers: {
                'X-CSRF-Token': BTT.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    window.packBuilder.currentPack = response.data;
                    window.packBuilder.currentPackId = response.data.id;
                    window.packBuilder.displayLoadedPack(response.data);
                } else {
                    this.showError('Failed to load pack: ' + response.message);
                }
            },
            error: (xhr) => {
                this.showError('Failed to load pack. Please try again.');
            },
            complete: () => {
                this.isLoading = false;
            }
        });
    }
    
    deletePack(packId) {
        if (this.isDeleting) {
            this.log('Already deleting, skipping');
            return;
        }
        
        if (!confirm('Are you sure you want to delete this pack?')) {
            return;
        }
        
        this.isDeleting = true;
        this.log('Deleting pack:', packId);
        
        $.ajax({
            url: BTT.apiUrl + '/?route=backpacks&id=' + packId + '&force=true',
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': BTT.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Pack deleted successfully');
                    window.packBuilder.loadUserPacks();
                } else {
                    this.showError('Failed to delete pack: ' + response.message);
                }
            },
            error: (xhr) => {
                this.showError('Failed to delete pack. Please try again.');
            },
            complete: () => {
                this.isDeleting = false;
            }
        });
    }
    
    duplicatePack(packId) {
        if (this.isLoading) {
            this.log('Already loading, skipping duplicate');
            return;
        }
        
        this.isLoading = true;
        this.log('Duplicating pack:', packId);
        
        $.ajax({
            url: BTT.apiUrl + '/?route=backpacks/' + packId + '/duplicate',
            method: 'POST',
            headers: {
                'X-CSRF-Token': BTT.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.showSuccess('Pack duplicated successfully');
                    window.packBuilder.loadUserPacks();
                } else {
                    this.showError('Failed to duplicate pack: ' + response.message);
                }
            },
            error: (xhr) => {
                this.showError('Failed to duplicate pack. Please try again.');
            },
            complete: () => {
                this.isLoading = false;
            }
        });
    }
    
    createNewPack() {
        window.packBuilder.currentPackId = null;
        window.packBuilder.currentPack = null;
        window.packBuilder.switchView('builder');
        
        // Clear form
        $('.pack-item').remove();
        $('.drop-hint').show();
        $('.pack-name-input').val('');
        $('.pack-notes').val('');
        
        // Update weights
        window.packBuilder.updateWeights();
        window.packBuilder.updateSectionStats();
        
        this.showSuccess('Ready to create a new pack');
    }
    
    showError(message) {
        if (window.DuoConfirm && window.DuoConfirm.error) {
            window.DuoConfirm.error(message);
        } else {
            window.packBuilder.showToast(message, 'error');
        }
    }
    
    showSuccess(message) {
        window.packBuilder.showToast(message, 'success');
    }
}

// Initialize when document is ready
$(document).ready(() => {
    // Create global instance
    window.packManager = new PackStateManager();
});
