/**
 * Pack Builder Display Fix
 * Overrides the gear library rendering for better single-line display
 */

(function() {
    'use strict';
    
    // Wait for PackBuilder to be loaded
    function waitForPackBuilder() {
        if (typeof PackBuilder !== 'undefined' && PackBuilder.renderGearLibrary) {
            overrideRenderGearLibrary();
        } else {
            setTimeout(waitForPackBuilder, 100);
        }
    }
    
    function overrideRenderGearLibrary() {
        // Save original function
        const originalRenderGearLibrary = PackBuilder.renderGearLibrary;
        
        // Override with compact single-line version
        PackBuilder.renderGearLibrary = function() {
            const filtered = this.state.gearLibrary.filter(gear => {
                const matchesCategory = this.state.filters.category === 'all' || 
                                       gear.category === this.state.filters.category;
                const matchesSearch = !this.state.filters.search || 
                                     gear.name.toLowerCase().includes(this.state.filters.search);
                return matchesCategory && matchesSearch;
            });
            
            if (filtered.length === 0) {
                $('#gear-items').html(`
                    <div class="empty-gear-library">
                        <div class="empty-icon">🔍</div>
                        <div class="empty-text">No gear found</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                            Try adjusting your search or category filter
                        </div>
                    </div>
                `);
                return;
            }
            
            // New compact single-line HTML structure
            const html = filtered.map(gear => {
                const weight = this.formatWeight(gear.weight_g || gear.weight || 0);
                const icon = gear.icon || this.getCategoryIcon(gear.category);
                
                return `
                    <div class="gear-list-item compact-single-line" 
                         draggable="true" 
                         data-gear-id="${gear.id}" 
                         data-item='${JSON.stringify(gear)}' 
                         title="${gear.name} - ${weight}">
                        <span class="gear-row-icon">${icon}</span>
                        <span class="gear-row-name">${gear.name}</span>
                        <span class="gear-row-weight">${weight}</span>
                        <span class="gear-row-category">${gear.category || 'other'}</span>
                        <button class="gear-row-add" onclick="PackBuilder.quickAddItem('${gear.id}')" title="Add to Pack">+</button>
                    </div>
                `;
            }).join('');
            
            $('#gear-items').html(html);
            
            // Apply compact styles
            applyCompactStyles();
        };
        
        // If gear is already loaded, re-render
        if (PackBuilder.state && PackBuilder.state.gearLibrary.length > 0) {
            PackBuilder.renderGearLibrary();
        }
    }
    
    function applyCompactStyles() {
        // Inject compact styles if not already present
        if (!document.getElementById('pack-builder-compact-styles')) {
            const style = document.createElement('style');
            style.id = 'pack-builder-compact-styles';
            style.innerHTML = `
                .gear-list-item.compact-single-line {
                    display: grid !important;
                    grid-template-columns: 24px 1fr 60px 80px 32px !important;
                    align-items: center !important;
                    gap: 8px !important;
                    padding: 4px 8px !important;
                    margin: 1px 0 !important;
                    height: 28px !important;
                    background: linear-gradient(90deg, rgba(42, 63, 46, 0.8) 0%, rgba(29, 46, 31, 0.6) 100%) !important;
                    border: 1px solid rgba(88, 204, 2, 0.2) !important;
                    border-radius: 6px !important;
                    cursor: grab !important;
                    overflow: hidden !important;
                    font-size: 13px !important;
                    color: #e8f5e9 !important;
                    backdrop-filter: blur(4px) !important;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
                }
                
                .gear-list-item.compact-single-line:hover {
                    background: linear-gradient(90deg, rgba(61, 82, 66, 0.9) 0%, rgba(42, 63, 46, 0.8) 100%) !important;
                    border-color: #58cc02 !important;
                    box-shadow: 0 2px 4px rgba(88, 204, 2, 0.2) !important;
                }
                
                .gear-list-item.compact-single-line:nth-child(even) {
                    background: linear-gradient(90deg, rgba(26, 46, 31, 0.6) 0%, rgba(42, 63, 46, 0.4) 100%) !important;
                }
                
                .gear-row-icon {
                    font-size: 16px !important;
                    text-align: center !important;
                }
                
                .gear-row-name {
                    font-weight: 500 !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                }
                
                .gear-row-weight {
                    color: #58cc02 !important;
                    font-weight: 600 !important;
                    font-size: 12px !important;
                    text-align: right !important;
                }
                
                .gear-row-category {
                    color: #94a3b8 !important;
                    font-size: 11px !important;
                    text-transform: capitalize !important;
                    white-space: nowrap !important;
                    overflow: hidden !important;
                    text-overflow: ellipsis !important;
                }
                
                .gear-row-add {
                    width: 24px !important;
                    height: 24px !important;
                    padding: 0 !important;
                    background: #58cc02 !important;
                    color: white !important;
                    border: none !important;
                    border-radius: 4px !important;
                    font-size: 16px !important;
                    font-weight: bold !important;
                    cursor: pointer !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    transition: transform 0.1s !important;
                }
                
                .gear-row-add:hover {
                    background: #46a302 !important;
                    transform: scale(1.1) !important;
                }
                
                #gear-items, #modal-gear-list {
                    max-height: 500px !important;
                    overflow-y: auto !important;
                    padding: 4px !important;
                    background: rgba(0,0,0,0.2) !important;
                    border-radius: 6px !important;
                }
                
                /* Thin scrollbar */
                #gear-items::-webkit-scrollbar {
                    width: 6px !important;
                }
                
                #gear-items::-webkit-scrollbar-thumb {
                    background: rgba(88,204,2,0.3) !important;
                    border-radius: 3px !important;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Start the process
    waitForPackBuilder();
    
    // Also hook into jQuery ready
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            waitForPackBuilder();
        });
    }
})();