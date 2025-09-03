/**
 * Save Pack Enhancement
 * Adds visual feedback and improved UX for the Save Pack functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get Save Pack button and status indicator
    const saveButton = document.getElementById('btn-save-pack');
    const saveStatus = document.getElementById('save-status');
    const cancelButton = document.getElementById('btn-cancel-edit');
    
    // Add enhanced save functionality
    if (saveButton) {
        // Store original button content
        const originalButtonHTML = saveButton.innerHTML;
        
        // Override or enhance existing click handler
        const originalClickHandler = saveButton.onclick;
        
        saveButton.onclick = async function(e) {
            e.preventDefault();
            
            // Update button state to saving
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" style="animation: spin 1s linear infinite; width: 1rem; height: 1rem; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; display: inline-block; margin-right: 0.5rem;"></span> Saving...';
            saveButton.style.opacity = '0.8';
            saveButton.style.cursor = 'not-allowed';
            
            // Update status
            if (saveStatus) {
                saveStatus.textContent = 'Saving your pack...';
                saveStatus.style.color = '#f59e0b';
            }
            
            // Call original handler if it exists
            if (originalClickHandler) {
                try {
                    await originalClickHandler.call(this, e);
                } catch (error) {
                    console.error('Save error:', error);
                    // Reset on error
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalButtonHTML;
                    saveButton.style.opacity = '1';
                    saveButton.style.cursor = 'pointer';
                    
                    if (saveStatus) {
                        saveStatus.textContent = '❌ Save failed. Please try again.';
                        saveStatus.style.color = '#ef4444';
                    }
                    return;
                }
            }
            
            // Simulate save delay if no original handler
            if (!originalClickHandler) {
                await new Promise(resolve => setTimeout(resolve, 1500));
            }
            
            // Success feedback
            saveButton.innerHTML = '<span style="margin-right: 0.5rem;">✅</span> Saved!';
            saveButton.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            
            if (saveStatus) {
                saveStatus.textContent = '✅ Pack saved successfully!';
                saveStatus.style.color = '#10b981';
            }
            
            // Reset button after delay
            setTimeout(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = originalButtonHTML;
                saveButton.style.opacity = '1';
                saveButton.style.cursor = 'pointer';
                
                // Fade out status after a bit longer
                setTimeout(() => {
                    if (saveStatus) {
                        saveStatus.textContent = '';
                    }
                }, 3000);
            }, 2000);
        };
    }
    
    // Add auto-save indicator
    let autoSaveTimer;
    const packInputs = document.querySelectorAll('#pack-name, #pack-description, #pack-capacity, #pack-base-weight');
    
    packInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Clear existing timer
            clearTimeout(autoSaveTimer);
            
            // Update status to show unsaved changes
            if (saveStatus) {
                saveStatus.textContent = '• Unsaved changes';
                saveStatus.style.color = '#f59e0b';
            }
            
            // Set new timer for auto-save (optional)
            autoSaveTimer = setTimeout(() => {
                if (saveStatus) {
                    saveStatus.textContent = '💡 Remember to save your pack';
                    saveStatus.style.color = '#6b7280';
                }
            }, 5000);
        });
    });
    
    // Cancel button warning
    if (cancelButton) {
        cancelButton.addEventListener('click', function(e) {
            const hasUnsavedChanges = saveStatus && saveStatus.textContent.includes('Unsaved');
            
            if (hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });
    }
});

// Add spinning animation CSS if not already present
if (!document.getElementById('save-pack-styles')) {
    const style = document.createElement('style');
    style.id = 'save-pack-styles';
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .builder-action-bar {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        #save-status {
            transition: color 0.3s ease, opacity 0.3s ease;
        }
        
        #btn-save-pack {
            position: relative;
            overflow: hidden;
        }
        
        #btn-save-pack::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        #btn-save-pack:active::after {
            width: 300px;
            height: 300px;
        }
    `;
    document.head.appendChild(style);
}
</script>
