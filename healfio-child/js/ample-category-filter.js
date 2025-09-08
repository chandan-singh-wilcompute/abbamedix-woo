/**
 * Ample Category Filter - JavaScript Integration
 * 
 * Integrates category filter checkboxes with the main filter engine
 * Part of Phase 3 implementation for ABBA Issue #12
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-09-03
 */

(function($) {
    'use strict';
    
    // Category filter management object
    window.AmpleCategoryFilter = {
        
        /**
         * Initialize category filter functionality
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.initializeFilterState();
            });
        },
        
        /**
         * Setup event handlers for category filter
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Category checkbox change handler
            $(document).on('change.ample-category-filter', '.ample-category-options input[type="checkbox"]', function() {
                self.handleCheckboxChange();
            });
            
            // Make entire filter option row clickable
            $(document).on('click.ample-category-filter', '.ample-category-options .filter-option', function(e) {
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
            $(document).on('click.ample-category-filter', '.ample-category-apply-button', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent other handlers from firing
                self.applyFilter();
                return false;
            });
            
            // Clear filter when X is clicked (integrates with button indicators system)
            $(document).on('click.ample-category-filter', '.filter-clear-icon[data-filter="categories"]', function(e) {
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
                // Add categories to the active filters if not already present
                if (!window.AmpleFilter.activeFilters.hasOwnProperty('categories')) {
                    window.AmpleFilter.activeFilters.categories = [];
                }
                
                // Update button text if categories are already selected (from URL)
                if (window.AmpleFilter.activeFilters.categories.length > 0) {
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
         * Apply category filter
         */
        applyFilter: function() {
            // Ensure AmpleFilter is available
            if (typeof window.AmpleFilter === 'undefined') {
                console.warn('AmpleFilter not loaded - category filter cannot function');
                return;
            }
            
            // Collect selected categories from data-category attributes
            const selectedCategories = [];
            $('.ample-category-options .filter-option input[type="checkbox"]:checked').each(function() {
                const category = $(this).data('category');
                if (category) {
                    selectedCategories.push(category);
                }
            });
            
            // Update the main filter system
            window.AmpleFilter.activeFilters.categories = selectedCategories;
            
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
            const checkedCount = $('.ample-category-options input[type="checkbox"]:checked').length;
            const $button = $('.toggleBtn#categories');
            
            if (checkedCount > 0) {
                // Get selected category names for display
                const selectedCategories = [];
                $('.ample-category-options input[type="checkbox"]:checked').each(function() {
                    const categoryName = $(this).data('category');
                    if (categoryName) {
                        // Ensure categoryName is a string and format for display
                        const categoryNameStr = String(categoryName);
                        const displayName = categoryNameStr.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        selectedCategories.push(displayName);
                    }
                });
                
                // Create multi-line button display - show individual name for 1, count for 2+
                const categoriesText = selectedCategories.length === 1 ? 
                    selectedCategories[0] : 
                    `${checkedCount} categories`;
                
                $button.html(`
                    <span class="filter-button-label">CATEGORIES</span>
                    <span class="filter-button-range">${categoriesText}</span>
                    <span class="filter-clear-icon" data-filter="categories" title="Clear categories filter">✕</span>
                `);
                $button.addClass('has-filter');
            } else {
                $button.html('CATEGORIES');
                $button.removeClass('has-filter');
            }
        },
        
        /**
         * Clear category filter
         */
        clearFilter: function() {
            // Uncheck all checkboxes
            $('.ample-category-options input[type="checkbox"]').prop('checked', false);
            
            // Clear the filter from main system
            if (typeof window.AmpleFilter !== 'undefined') {
                window.AmpleFilter.activeFilters.categories = [];
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
            const $applyButton = $('.ample-category-apply-button');
            const originalText = $applyButton.text();
            
            // Temporarily change button text
            $applyButton.text('Applied!').addClass('ample-filter-applied');
            
            setTimeout(function() {
                $applyButton.text(originalText).removeClass('ample-filter-applied');
            }, 1000);
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleCategoryFilter.init();
    
})(jQuery);