/**
 * Ample Button Indicators
 * 
 * Shows active filter ranges in button text and visual indicators
 * Fixes for ABBA Issue visual feedback
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-30
 */

(function($) {
    'use strict';
    
    const AmpleButtonIndicators = {
        
        /**
         * Initialize button indicators
         */
        init: function() {
            // Initialize filter button indicators
            
            $(document).ready(() => {
                this.setupEventListeners();
                this.initializeFromURL();
            });
        },
        
        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            const self = this;
            
            // Listen for filter apply events
            $(document).on('ample-dual-range:applied', function(event, data) {
                // Filter applied
                self.updateButtonDisplay(data.slider, data.min, data.max);
            });
            
            // Listen for filter changes (real-time updates)
            $(document).on('ample-dual-range:changed', function(event, data) {
                // Only update if live mode is enabled
                if (window.AmpleFilterConfig && window.AmpleFilterConfig.liveFiltering) {
                    self.updateButtonDisplay(data.slider, data.min, data.max);
                }
            });
            
            // Listen for filter resets
            $(document).on('ample-filter:reset', function() {
                self.resetAllButtons();
            });
            
            // Listen for clear icon clicks
            $(document).on('click', '.filter-clear-icon', function(e) {
                e.stopPropagation(); // Prevent button dropdown from opening
                const filterType = $(this).data('filter');
                self.clearFilter(filterType);
            });
        },
        
        /**
         * Initialize button states from URL parameters
         */
        initializeFromURL: function() {
            // Wait for other systems to initialize
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Check THC parameter
                if (urlParams.has('thc')) {
                    const thcRange = this.parseRangeParam(urlParams.get('thc'));
                    if (thcRange) {
                        this.updateButtonDisplay('thc-dual-range-container', thcRange[0], thcRange[1]);
                        // THC button updated from URL
                    }
                }
                
                // Check CBD parameter
                if (urlParams.has('cbd')) {
                    const cbdRange = this.parseRangeParam(urlParams.get('cbd'));
                    if (cbdRange) {
                        this.updateButtonDisplay('cbd-dual-range-container', cbdRange[0], cbdRange[1]);
                        // CBD button updated from URL
                    }
                }
                
                // Check Size parameter
                if (urlParams.has('sizes')) {
                    const sizeParams = urlParams.get('sizes').split(',');
                    if (sizeParams.length > 0) {
                        // Convert from URL-safe format
                        const originalSizes = sizeParams.map(size => window.AmpleFilter.fromUrlSafe(size));
                        this.updateSizeButtonFromURL(originalSizes);
                        // Size button updated from URL
                    }
                }
                
                // Check Dominance parameter
                if (urlParams.has('dominance')) {
                    const dominanceParams = urlParams.get('dominance').split(',');
                    if (dominanceParams.length > 0) {
                        // Convert from URL-safe format
                        const originalStrains = dominanceParams.map(strain => window.AmpleFilter.fromUrlSafe(strain));
                        this.updateDominanceButtonFromURL(originalStrains);
                        // Dominance button updated from URL
                    }
                }
            }, 3000); // Wait for sliders to be fully initialized
        },
        
        /**
         * Update button display with filter range
         */
        updateButtonDisplay: function(sliderId, min, max) {
            const filterType = this.getFilterType(sliderId);
            if (!filterType) return;
            
            const button = document.getElementById(filterType);
            if (!button) {
                console.warn('[AmpleButtonIndicators] Button not found:', filterType);
                return;
            }
            
            const isDefault = this.isDefaultRange(filterType, min, max);
            const originalLabel = filterType.toUpperCase();
            
            if (isDefault) {
                // Reset to original single-line button
                button.innerHTML = originalLabel;
                button.classList.remove('has-filter');
            } else {
                // Create two-line button with range below
                const unit = filterType === 'thc' || filterType === 'cbd' ? '%' : '';
                const minFormatted = this.formatNumber(min);
                const maxFormatted = this.formatNumber(max);
                const rangeText = `${minFormatted}-${maxFormatted}${unit}`;
                
                // Update button with two-line layout and clear icon
                button.innerHTML = `
                    <span class="filter-button-label">${originalLabel}</span>
                    <span class="filter-button-range">${rangeText}</span>
                    <span class="filter-clear-icon" data-filter="${filterType}" title="Clear ${filterType.toUpperCase()} filter">✕</span>
                `;
                button.classList.add('has-filter');
            }
            
            // Button updated with filter range
        },
        
        /**
         * Add filter range tag below button
         */
        addFilterTag: function(button, min, max) {
            // Remove existing tag first
            this.removeFilterTag(button);
            
            const unit = button.id === 'thc' || button.id === 'cbd' ? '%' : '';
            const minFormatted = window.AmpleFilter.urlManager.formatNumber(min);
            const maxFormatted = window.AmpleFilter.urlManager.formatNumber(max);
            const rangeText = `${minFormatted}-${maxFormatted}${unit}`;
            
            // Create tag element
            const tag = document.createElement('span');
            tag.className = 'filter-range-tag';
            tag.textContent = rangeText;
            
            // Add tag to button
            button.appendChild(tag);
        },
        
        /**
         * Remove filter range tag
         */
        removeFilterTag: function(button) {
            const existingTag = button.querySelector('.filter-range-tag');
            if (existingTag) {
                existingTag.remove();
            }
        },
        
        /**
         * Format button text based on filter state
         */
        formatButtonText: function(filterType, min, max, isDefault) {
            const label = filterType.toUpperCase();
            
            if (isDefault) {
                return label;
            }
            
            // Format the range nicely
            const unit = filterType === 'thc' || filterType === 'cbd' ? '%' : '';
            const minFormatted = window.AmpleFilter.urlManager.formatNumber(min);
            const maxFormatted = window.AmpleFilter.urlManager.formatNumber(max);
            
            // If button text would be too long, use abbreviated format
            const fullText = `${label} ${minFormatted}-${maxFormatted}${unit}`;
            const maxLength = 15; // Adjust based on button width
            
            if (fullText.length > maxLength) {
                return `${label} ${minFormatted}...${unit}`;
            }
            
            return fullText;
        },
        
        /**
         * Format number for display
         */
        formatNumber: function(num) {
            // Convert to number and handle floating point precision errors
            const cleanNum = Math.round(parseFloat(num) * 10) / 10;
            
            // Remove unnecessary decimal places
            if (cleanNum % 1 === 0) {
                return cleanNum.toString(); // Integer
            }
            
            // Return with 1 decimal place maximum, remove trailing zeros
            return cleanNum.toFixed(1).replace(/\.0$/, '');
        },

        /**
         * Check if range is default (no filter applied)
         */
        isDefaultRange: function(filterType, min, max) {
            const defaults = {
                thc: { min: 0, max: 45 },
                cbd: { min: 0, max: 30 }
            };
            
            const defaultRange = defaults[filterType];
            if (!defaultRange) return true;
            
            return min === defaultRange.min && max === defaultRange.max;
        },
        
        /**
         * Get filter type from slider ID
         */
        getFilterType: function(sliderId) {
            if (sliderId.includes('thc')) return 'thc';
            if (sliderId.includes('cbd')) return 'cbd';
            return null;
        },
        
        /**
         * Parse range parameter (e.g., "10-25" -> [10, 25])
         */
        parseRangeParam: function(param) {
            if (!param || typeof param !== 'string') return null;
            
            const parts = param.split('-');
            if (parts.length !== 2) return null;
            
            const min = parseFloat(parts[0]);
            const max = parseFloat(parts[1]);
            
            if (isNaN(min) || isNaN(max)) return null;
            
            return [min, max];
        },
        
        /**
         * Clear specific filter and reset to default range
         */
        clearFilter: function(filterType) {
            // Handle Size filter clearing
            if (filterType === 'size') {
                // Clear checkboxes
                $('.ample-size-options input[type="checkbox"]').prop('checked', false);
                
                // Update global filter state
                if (window.AmpleFilter && window.AmpleFilter.activeFilters) {
                    window.AmpleFilter.activeFilters.sizes = [];
                }
                
                // Reset button display
                const $button = $('.toggleBtn#size');
                $button.html('SIZE').removeClass('has-filter');
                
                // Apply filters and update URL
                if (window.AmpleFilter) {
                    window.AmpleFilter.applyFilters();
                    if (window.AmpleFilter.urlManager) window.AmpleFilter.urlManager.scheduleURLUpdate();
                }
                return;
            }
            
            // Handle Dominance filter clearing
            if (filterType === 'dominance') {
                // Clear checkboxes
                $('.ample-dominance-options input[type="checkbox"]').prop('checked', false);
                
                // Update global filter state
                if (window.AmpleFilter && window.AmpleFilter.activeFilters) {
                    window.AmpleFilter.activeFilters.dominance = [];
                }
                
                // Reset button display
                const $button = $('.toggleBtn#dominance');
                $button.html('DOMINANCE').removeClass('has-filter');
                
                // Apply filters and update URL
                if (window.AmpleFilter) {
                    window.AmpleFilter.applyFilters();
                    if (window.AmpleFilter.urlManager) window.AmpleFilter.urlManager.scheduleURLUpdate();
                }
                return;
            }
            
            // Handle THC/CBD range filters
            const defaults = {
                thc: { min: 0, max: 45 },
                cbd: { min: 0, max: 30 }
            };
            
            const defaultRange = defaults[filterType];
            if (!defaultRange) return;
            
            // Reset the slider to default values
            const containerSelector = `#${filterType}-dual-range-container`;
            const container = document.querySelector(containerSelector);
            if (container && container.sliderInstance) {
                container.sliderInstance.setValues(defaultRange.min, defaultRange.max);
                
                // Update the input fields as well
                const minInput = container.querySelector(`#${filterType}-dual-range-min-input`);
                const maxInput = container.querySelector(`#${filterType}-dual-range-max-input`);
                if (minInput) minInput.value = defaultRange.min;
                if (maxInput) maxInput.value = defaultRange.max;
            }
            
            // Update global filter state
            if (window.AmpleFilter && window.AmpleFilter.activeFilters) {
                window.AmpleFilter.activeFilters[filterType] = [defaultRange.min, defaultRange.max];
            }
            
            // Trigger slider change event to update URL and apply filters
            $(document).trigger('slider-changed', {
                slider: containerSelector,
                min: defaultRange.min,
                max: defaultRange.max,
                range: [defaultRange.min, defaultRange.max]
            });
            
            // Also trigger the filter application to refresh products
            if (window.AmpleFilter && window.AmpleFilter.applyFilters) {
                setTimeout(() => {
                    window.AmpleFilter.applyFilters();
                }, 100); // Small delay to ensure state is updated
            }
            
            // Update button display
            this.updateButtonDisplay(containerSelector, defaultRange.min, defaultRange.max);
            
            // Filter cleared and URL updated
        },

        /**
         * Reset all buttons to default state
         */
        resetAllButtons: function() {
            const buttons = ['thc', 'cbd'];
            
            buttons.forEach(buttonId => {
                const button = document.getElementById(buttonId);
                if (button) {
                    button.textContent = buttonId.toUpperCase();
                    button.classList.remove('has-filter');
                }
            });
            
            // All buttons reset to default
        },
        
        /**
         * Update Size button display from URL parameters
         */
        updateSizeButtonFromURL: function(sizeParams) {
            const $button = $('.toggleBtn#size');
            if (!$button.length) return;
            
            const sizeCount = sizeParams.length;
            if (sizeCount > 0) {
                // Create multi-line button display - show individual name for 1, count for 2+
                const sizesText = sizeCount === 1 ? sizeParams[0] : `${sizeCount} sizes`;
                
                $button.html(`
                    <span class="filter-button-label">SIZE</span>
                    <span class="filter-button-range">${sizesText}</span>
                    <span class="filter-clear-icon" data-filter="size" title="Clear size filter">✕</span>
                `);
                $button.addClass('has-filter');
                
                // Also update the checkboxes to match URL state
                setTimeout(() => {
                    $('.ample-size-options input[type="checkbox"]').prop('checked', false);
                    sizeParams.forEach(sizeGroup => {
                        $('.ample-size-options input[data-group="' + sizeGroup + '"]').prop('checked', true);
                    });
                }, 100);
            }
        },
        
        /**
         * Update Dominance button display from URL parameters
         */
        updateDominanceButtonFromURL: function(dominanceParams) {
            const $button = $('.toggleBtn#dominance');
            if (!$button.length) return;
            
            const dominanceCount = dominanceParams.length;
            if (dominanceCount > 0) {
                // Create multi-line button display - show individual name for 1, count for 2+
                const dominanceText = dominanceCount === 1 ? dominanceParams[0] : `${dominanceCount} strains`;
                
                $button.html(`
                    <span class="filter-button-label">DOMINANCE</span>
                    <span class="filter-button-range">${dominanceText}</span>
                    <span class="filter-clear-icon" data-filter="dominance" title="Clear dominance filter">✕</span>
                `);
                $button.addClass('has-filter');
                
                // Also update the checkboxes to match URL state
                setTimeout(() => {
                    $('.ample-dominance-options input[type="checkbox"]').prop('checked', false);
                    dominanceParams.forEach(strain => {
                        $('.ample-dominance-options input[data-strain="' + strain + '"]').prop('checked', true);
                    });
                }, 100);
            }
        },
        
        /**
         * Get current filter state for debugging
         */
        getFilterState: function() {
            const buttons = ['thc', 'cbd'];
            const state = {};
            
            buttons.forEach(buttonId => {
                const button = document.getElementById(buttonId);
                if (button) {
                    state[buttonId] = {
                        text: button.textContent,
                        hasFilter: button.classList.contains('has-filter'),
                        hasActive: button.classList.contains('active')
                    };
                }
            });
            
            return state;
        }
    };
    
    // Initialize the indicators
    AmpleButtonIndicators.init();
    
    // Export for debugging
    window.AmpleButtonIndicators = AmpleButtonIndicators;
    
})(jQuery);