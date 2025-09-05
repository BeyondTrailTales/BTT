/**
 * Section Deletion Handler
 * Handles deletion of pack sections with confirmation
 */

$(document).ready(function() {
    console.log('🗑️ Initializing section deletion...');
    
    // Handle delete section button clicks
    $(document).on('click', '.btn-delete-section', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $button = $(this);
        const sectionId = $button.data('section');
        const $section = $button.closest('.pack-section');
        const sectionName = $section.find('.section-name').val() || sectionId;
        
        // Check if section has items
        const itemCount = $section.find('.pack-item').length;
        
        let confirmMessage = `Delete the "${sectionName}" section?`;
        if (itemCount > 0) {
            confirmMessage = `Delete the "${sectionName}" section? This will remove ${itemCount} item(s).`;
        }
        
        // Show confirmation dialog
        if (confirm(confirmMessage)) {
            deleteSection($section, sectionId, sectionName);
        }
    });
    
    function deleteSection($section, sectionId, sectionName) {
        console.log('🗑️ Deleting section:', sectionId);
        
        // Add deletion animation
        $section.addClass('deleting');
        
        // Fade out and remove
        $section.fadeOut(300, function() {
            $section.remove();
            
            // Update total weight after section removal
            if (typeof window.PackBuilderCRUD !== 'undefined' && window.PackBuilderCRUD.updateWeightSummary) {
                window.PackBuilderCRUD.updateWeightSummary();
            }
            
            // Show success message
            showDeletionToast(`Deleted "${sectionName}" section`);
            
            console.log('✅ Section deleted:', sectionId);
            
            // Mark pack as dirty for saving
            if (typeof window.PackBuilderCRUD !== 'undefined') {
                window.PackBuilderCRUD.state.isDirty = true;
            }
        });
    }
    
    function showDeletionToast(message) {
        const toast = $(`<div class="pack-toast pack-toast-info">${message}</div>`);
        $('body').append(toast);
        
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    console.log('✅ Section deletion initialized');
});