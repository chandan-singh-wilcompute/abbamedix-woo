/**
 * Ample Terpenes Filter - JavaScript Integration
 * 
 * Integrates terpenes filter checkboxes with the main filter engine
 * Part of Phase 1 implementation for ABBA Issue #11
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-09-03
 */

(function($) {
    'use strict';
    
    // Terpenes filter management object
    window.AmpleTerpenesFilter = {
        
        /**
         * Initialize terpenes filter functionality
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.initializeFilterState();
            });
        },
        
        /**
         * Setup event handlers for terpenes filter
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Terpenes checkbox change handler
            $(document).on('change.ample-terpenes-filter', '.ample-terpenes-options input[type="checkbox"]', function() {
                self.handleCheckboxChange();
            });
            
            // Apply button click handler - use distinct class name to avoid conflicts
            $(document).on('click.ample-terpenes-filter', '.ample-terpenes-apply-button', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers from firing
                self.applyFilter();
                return false;
            });
            
            // Clear filter when X is clicked (integrates with button indicators system)
            $(document).on('click.ample-terpenes-filter', '.filter-clear-icon[data-filter="terpenes"]', function(e) {
                e.stopPropagation();
                e.preventDefault();
                self.clearFilter();
                return false;
            });
        },
        
        /**
         * Initialize filter state in main system
         */
        initializeFilterState: function() {
            // Ensure AmpleFilter is loaded
            if (typeof window.AmpleFilter !== 'undefined') {
                // Add terpenes to the active filters if not already present
                if (!window.AmpleFilter.activeFilters.hasOwnProperty('terpenes')) {
                    window.AmpleFilter.activeFilters.terpenes = [];
                }
                
                // Update button text if terpenes are already selected (from URL)
                if (window.AmpleFilter.activeFilters.terpenes.length > 0) {
                    setTimeout(() => {
                        this.updateButtonText();
                    }, 100);
                }
            }
        },
        
        /**
         * Handle checkbox state changes
         */
        handleCheckboxChange: function() {
            // Update UI feedback (like button text)
            this.updateButtonText();
        },
        
        /**
         * Apply terpenes filter
         */
        applyFilter: function() {
            // Ensure AmpleFilter is available
            if (typeof window.AmpleFilter === 'undefined') {
                console.warn('AmpleFilter not loaded - terpenes filter cannot function');
                return;
            }
            
            // Collect selected terpenes from data-terpene attributes
            const selectedTerpenes = [];
            $('.ample-terpenes-options .filter-option input[type="checkbox"]:checked').each(function() {
                const terpene = $(this).data('terpene');
                if (terpene) {
                    selectedTerpenes.push(terpene);
                }
            });
            
            // Update the main filter system
            window.AmpleFilter.activeFilters.terpenes = selectedTerpenes;
            
            // Apply all filters
            window.AmpleFilter.applyFilters();
            
            // Update URL parameters (using URL manager for consistency)
            if (window.AmpleFilter.urlManager) {
                window.AmpleFilter.urlManager.scheduleURLUpdate();
            }
            
            // Update button text to show selection count
            this.updateButtonText();
            
            // Visual feedback
            this.showApplyFeedback();
        },
        
        /**
         * Update button text to show active filter count (like Size/Dominance)
         */
        updateButtonText: function() {
            const checkedCount = $('.ample-terpenes-options input[type="checkbox"]:checked').length;
            const $button = $('.toggleBtn#terpenes');
            
            if (checkedCount > 0) {
                // Get selected terpene names for display
                const selectedTerpenes = [];
                $('.ample-terpenes-options input[type="checkbox"]:checked').each(function() {
                    const terpeneName = $(this).data('terpene');
                    if (terpeneName) {
                        // Format for display (capitalize, clean up)
                        const displayName = terpeneName.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        selectedTerpenes.push(displayName);
                    }
                });
                
                // Create multi-line button display - show individual name for 1, count for 2+
                const terpenesText = selectedTerpenes.length === 1 ? 
                    selectedTerpenes[0] : 
                    `${checkedCount} terpenes`;
                
                $button.html(`
                    <span class="filter-button-label">TERPENES</span>
                    <span class="filter-button-range">${terpenesText}</span>
                    <span class="filter-clear-icon" data-filter="terpenes" title="Clear terpenes filter">✕</span>
                `);
                $button.addClass('has-filter');
            } else {
                $button.html('TERPENES');
                $button.removeClass('has-filter');
            }
        },
        
        /**
         * Clear terpenes filter
         */
        clearFilter: function() {
            // Uncheck all checkboxes
            $('.ample-terpenes-options input[type="checkbox"]').prop('checked', false);
            
            // Clear the filter from main system
            if (typeof window.AmpleFilter !== 'undefined') {
                window.AmpleFilter.activeFilters.terpenes = [];
                window.AmpleFilter.applyFilters();
                
                // Update URL parameters (using URL manager for consistency)
                if (window.AmpleFilter.urlManager) {
                    window.AmpleFilter.urlManager.scheduleURLUpdate();
                }
            }
            
            // Update button display
            this.updateButtonText();
        },
        
        /**
         * Show visual feedback when filter is applied
         */
        showApplyFeedback: function() {
            const $applyButton = $('.ample-terpenes-apply-button');
            const originalText = $applyButton.text();
            
            // Temporarily change button text
            $applyButton.text('Applied!').addClass('ample-filter-applied');
            
            setTimeout(function() {
                $applyButton.text(originalText).removeClass('ample-filter-applied');
            }, 1000);
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleTerpenesFilter.init();
    
})(jQuery);