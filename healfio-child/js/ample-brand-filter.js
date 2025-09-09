/**
 * Ample Brand Filter - JavaScript Integration
 * 
 * Integrates brand filter checkboxes with the main filter engine
 * Part of Phase 2 implementation for ABBA Issue #12
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-09-03
 */

(function($) {
    'use strict';
    
    // Brand filter management object
    window.AmpleBrandFilter = {
        
        /**
         * Initialize brand filter functionality
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.initializeFilterState();
            });
        },
        
        /**
         * Setup event handlers for brand filter
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Brand checkbox change handler
            $(document).on('change.ample-brand-filter', '.ample-brand-options input[type="checkbox"]', function() {
                self.handleCheckboxChange();
            });
            
            // Make entire filter option row clickable
            $(document).on('click.ample-brand-filter', '.ample-brand-options .filter-option', function(e) {
                // Don't trigger if clicking on the checkbox itself (avoid double-trigger)
                if (e.target.type === 'checkbox') {
                    return;
                }
                
                // Prevent default label behavior to avoid conflicts
                e.preventDefault();
                e.stopPropagation();
                
                // Find and toggle the checkbox in this row
                const checkbox = $(this).find('input[type="checkbox"]');
                if (checkbox.length) {
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });
            
            // Apply button click handler - use distinct class name to avoid conflicts
            $(document).on('click.ample-brand-filter', '.ample-brand-apply-button', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers from firing
                self.applyFilter();
                return false;
            });
            
            // Clear filter when X is clicked (integrates with button indicators system)
            $(document).on('click.ample-brand-filter', '.filter-clear-icon[data-filter="brands"]', function(e) {
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
                // Add brands to the active filters if not already present
                if (!window.AmpleFilter.activeFilters.hasOwnProperty('brands')) {
                    window.AmpleFilter.activeFilters.brands = [];
                }
                
                // Update button text if brands are already selected (from URL)
                if (window.AmpleFilter.activeFilters.brands.length > 0) {
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
         * Apply brand filter
         */
        applyFilter: function() {
            // Ensure AmpleFilter is available
            if (typeof window.AmpleFilter === 'undefined') {
                console.warn('AmpleFilter not loaded - brand filter cannot function');
                return;
            }
            
            // Collect selected brands from data-brand attributes
            const selectedBrands = [];
            $('.ample-brand-options .filter-option input[type="checkbox"]:checked').each(function() {
                const brand = $(this).data('brand');
                if (brand) {
                    selectedBrands.push(brand);
                }
            });
            
            // Update the main filter system
            window.AmpleFilter.activeFilters.brands = selectedBrands;
            
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
            const checkedCount = $('.ample-brand-options input[type="checkbox"]:checked').length;
            const $button = $('.toggleBtn#brands');
            
            if (checkedCount > 0) {
                // Get selected brand names for display
                const selectedBrands = [];
                $('.ample-brand-options input[type="checkbox"]:checked').each(function() {
                    const brandName = $(this).data('brand');
                    if (brandName) {
                        // Ensure brandName is a string and format for display
                        const brandNameStr = String(brandName);
                        const displayName = brandNameStr.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        selectedBrands.push(displayName);
                    }
                });
                
                // Create multi-line button display - show individual name for 1, count for 2+
                const brandsText = selectedBrands.length === 1 ? 
                    selectedBrands[0] : 
                    `${checkedCount} brands`;
                
                $button.html(`
                    <span class="filter-button-label">BRANDS</span>
                    <span class="filter-button-range">${brandsText}</span>
                    <span class="filter-clear-icon" data-filter="brands" title="Clear brands filter">✕</span>
                `);
                $button.addClass('has-filter');
            } else {
                $button.html('BRANDS');
                $button.removeClass('has-filter');
            }
        },
        
        /**
         * Clear brand filter
         */
        clearFilter: function() {
            // Uncheck all checkboxes
            $('.ample-brand-options input[type="checkbox"]').prop('checked', false);
            
            // Clear the filter from main system
            if (typeof window.AmpleFilter !== 'undefined') {
                window.AmpleFilter.activeFilters.brands = [];
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
            const $applyButton = $('.ample-brand-apply-button');
            const originalText = $applyButton.text();
            
            // Temporarily change button text
            $applyButton.text('Applied!').addClass('ample-filter-applied');
            
            setTimeout(function() {
                $applyButton.text(originalText).removeClass('ample-filter-applied');
            }, 1000);
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleBrandFilter.init();
    
})(jQuery);