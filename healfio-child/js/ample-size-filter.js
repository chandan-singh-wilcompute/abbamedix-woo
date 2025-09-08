/**
 * Ample Size Filter - JavaScript Integration
 * 
 * Integrates size filter checkboxes with the main filter engine
 * Part of Phase 1.2 implementation for ABBA Issue #10
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-09-03
 */

(function($) {
    'use strict';
    
    // Size filter management object
    window.AmpleSizeFilter = {
        
        /**
         * Initialize size filter functionality
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.initializeFilterState();
            });
        },
        
        /**
         * Setup event handlers for size filter
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Size checkbox change handler
            $(document).on('change.ample-size-filter', '.ample-size-options input[type="checkbox"]', function() {
                self.handleCheckboxChange();
            });
            
            // Apply button click handler - use distinct class name to avoid conflicts
            $(document).on('click.ample-size-filter', '.ample-size-apply-button', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers from firing
                self.applyFilter();
                return false;
            });
            
            // Clear filter when X is clicked (integrates with button indicators system)
            $(document).on('click.ample-size-filter', '.filter-clear-icon[data-filter="size"]', function(e) {
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
                // Add sizes to the active filters if not already present
                if (!window.AmpleFilter.activeFilters.hasOwnProperty('sizes')) {
                    window.AmpleFilter.activeFilters.sizes = [];
                }
                
                // Update button text if sizes are already selected (from URL) - faster like terpenes
                if (window.AmpleFilter.activeFilters.sizes.length > 0) {
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
         * Apply size filter
         */
        applyFilter: function() {
            // Ensure AmpleFilter is available
            if (typeof window.AmpleFilter === 'undefined') {
                console.warn('AmpleFilter not loaded - size filter cannot function');
                return;
            }
            
            // Collect selected size groups from data-group attributes
            const selectedSizes = [];
            $('.ample-size-options input[type="checkbox"]:checked').each(function() {
                const sizeGroup = $(this).data('group');
                if (sizeGroup) {
                    selectedSizes.push(sizeGroup);
                }
            });
            
            
            // Update the main filter system
            window.AmpleFilter.activeFilters.sizes = selectedSizes;
            
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
         * Update button text to show active filter count (like THC/CBD)
         */
        updateButtonText: function() {
            const checkedCount = $('.ample-size-options input[type="checkbox"]:checked').length;
            const $button = $('.toggleBtn#size');
            
            if (checkedCount > 0) {
                // Get selected size names for display
                const selectedSizes = [];
                $('.ample-size-options input[type="checkbox"]:checked').each(function() {
                    const sizeName = $(this).data('group');
                    if (sizeName) {
                        selectedSizes.push(sizeName);
                    }
                });
                
                // Create multi-line button display - show individual name for 1, count for 2+
                const sizesText = selectedSizes.length === 1 ? 
                    selectedSizes[0] : 
                    `${checkedCount} sizes`;
                
                $button.html(`
                    <span class="filter-button-label">SIZE</span>
                    <span class="filter-button-range">${sizesText}</span>
                    <span class="filter-clear-icon" data-filter="size" title="Clear size filter">✕</span>
                `);
                $button.addClass('has-filter');
            } else {
                $button.html('SIZE');
                $button.removeClass('has-filter');
            }
        },
        
        /**
         * Clear size filter
         */
        clearFilter: function() {
            // Uncheck all checkboxes
            $('.ample-size-options input[type="checkbox"]').prop('checked', false);
            
            // Clear the filter from main system
            if (typeof window.AmpleFilter !== 'undefined') {
                window.AmpleFilter.activeFilters.sizes = [];
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
            const $applyButton = $('.ample-size-apply-button');
            const originalText = $applyButton.text();
            
            // Temporarily change button text
            $applyButton.text('Applied!').addClass('ample-filter-applied');
            
            setTimeout(function() {
                $applyButton.text(originalText).removeClass('ample-filter-applied');
            }, 1000);
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleSizeFilter.init();
    
})(jQuery);