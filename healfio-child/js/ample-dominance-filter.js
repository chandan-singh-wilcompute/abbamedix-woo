/**
 * Ample Dominance Filter - JavaScript Integration
 * 
 * Integrates dominance (strain) filter checkboxes with the main filter engine
 * Part of Phase 2 implementation for ABBA Issue #10
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-09-03
 */

(function($) {
    'use strict';
    
    // Dominance filter management object
    window.AmpleDominanceFilter = {
        
        /**
         * Initialize dominance filter functionality
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.initializeFilterState();
            });
        },
        
        /**
         * Setup event handlers for dominance filter
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Dominance checkbox change handler
            $(document).on('change.ample-dominance-filter', '.ample-dominance-options input[type="checkbox"]', function() {
                self.handleCheckboxChange();
            });
            
            // Apply button click handler - use distinct class name to avoid conflicts
            $(document).on('click.ample-dominance-filter', '.ample-dominance-apply-button', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers from firing
                self.applyFilter();
                return false;
            });
            
            // Clear filter when X is clicked (integrates with button indicators system)
            $(document).on('click.ample-dominance-filter', '.filter-clear-icon[data-filter="dominance"]', function(e) {
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
                // Add dominance to the active filters if not already present
                if (!window.AmpleFilter.activeFilters.hasOwnProperty('dominance')) {
                    window.AmpleFilter.activeFilters.dominance = [];
                }
                
                // Update button text if dominance is already selected (from URL) - faster like terpenes
                if (window.AmpleFilter.activeFilters.dominance.length > 0) {
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
            
            // Auto-apply filter on checkbox change
            this.applyFilter();
        },
        
        /**
         * Apply dominance filter
         */
        applyFilter: function() {
            // Ensure AmpleFilter is available
            if (typeof window.AmpleFilter === 'undefined') {
                console.warn('AmpleFilter not loaded - dominance filter cannot function');
                return;
            }
            
            // Collect selected strains from data-strain attributes
            const selectedStrains = [];
            $('.ample-dominance-options input[type="checkbox"]:checked').each(function() {
                const strain = $(this).data('strain');
                if (strain) {
                    selectedStrains.push(strain);
                }
            });
            
            
            // Update the main filter system
            window.AmpleFilter.activeFilters.dominance = selectedStrains;
            
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
         * Update button text to show active filter count (like THC/CBD/Size)
         */
        updateButtonText: function() {
            const checkedCount = $('.ample-dominance-options input[type="checkbox"]:checked').length;
            const $button = $('.toggleBtn#dominance');
            
            if (checkedCount > 0) {
                // Get selected strain names for display
                const selectedStrains = [];
                $('.ample-dominance-options input[type="checkbox"]:checked').each(function() {
                    const strainName = $(this).data('strain');
                    if (strainName) {
                        selectedStrains.push(strainName);
                    }
                });
                
                // Create multi-line button display - show individual name for 1, count for 2+
                const strainsText = selectedStrains.length === 1 ? 
                    selectedStrains[0] : 
                    `${checkedCount} strains`;
                
                $button.html(`
                    <span class="filter-button-label">DOMINANCE</span>
                    <span class="filter-button-range">${strainsText}</span>
                    <span class="filter-clear-icon" data-filter="dominance" title="Clear dominance filter">✕</span>
                `);
                $button.addClass('has-filter');
            } else {
                $button.html('DOMINANCE');
                $button.removeClass('has-filter');
            }
        },
        
        /**
         * Clear dominance filter
         */
        clearFilter: function() {
            // Uncheck all checkboxes
            $('.ample-dominance-options input[type="checkbox"]').prop('checked', false);
            
            // Clear the filter from main system
            if (typeof window.AmpleFilter !== 'undefined') {
                window.AmpleFilter.activeFilters.dominance = [];
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
            const $applyButton = $('.ample-dominance-apply-button');
            const originalText = $applyButton.text();
            
            // Temporarily change button text
            $applyButton.text('Applied!').addClass('ample-filter-applied');
            
            setTimeout(function() {
                $applyButton.text(originalText).removeClass('ample-filter-applied');
            }, 1000);
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleDominanceFilter.init();
    
})(jQuery);