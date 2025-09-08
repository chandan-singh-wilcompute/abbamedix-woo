/**
 * Ample Slider Integration
 * 
 * Connects dual-range sliders to the filter engine
 * Part of Step 4 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-30
 */

(function($) {
    'use strict';
    
    /**
     * Slider Integration Module
     */
    const AmpleSliderIntegration = {
        
        /**
         * Initialize the integration
         */
        init: function() {
            // Initialize slider integration
            
            // Wait for both components to be ready
            $(document).ready(() => {
                this.setupEventListeners();
                
                // Wait longer for sliders to be fully initialized before applying URL params
                setTimeout(() => {
                    this.initializeFilterState();
                }, 2000);
            });
        },
        
        /**
         * Setup event listeners for slider events
         */
        setupEventListeners: function() {
            const self = this;
            
            // Listen for Apply button clicks from dual-range sliders
            $(document).on('ample-dual-range:applied', function(event, data) {
                // Slider values applied
                self.handleSliderApply(data);
            });
            
            // Listen for real-time slider changes (for live filtering)
            $(document).on('ample-dual-range:changed', function(event, data) {
                // Only apply if live filtering is enabled
                if (window.AmpleFilterConfig && window.AmpleFilterConfig.liveFiltering) {
                    self.handleSliderChange(data);
                }
            });
            
            // Override or extend the Apply button handler
            $(document).on('click', '.ample-dual-range-apply', function(e) {
                const $button = $(this);
                const sliderId = $button.data('slider');
                const $container = $button.closest('.ample-dual-range-container');
                
                
                // Skip if this is a size filter button (handled by separate system)
                if ($button.hasClass('ample-size-apply') || $button.data('filter') === 'size') {
                    return;
                }
                
                // Get slider instance
                const sliderInstance = $container.data('ampleDualRangeSlider');
                if (sliderInstance) {
                    const values = sliderInstance.getValues();
                    // Apply button clicked
                    
                    // Apply the filter
                    self.applySliderFilter(sliderId, values);
                }
            });
        },
        
        /**
         * Initialize filter state from current slider values or URL parameters
         */
        initializeFilterState: function() {
            // Wait for AmpleFilter to be ready
            if (!window.AmpleFilter) {
                console.warn('[AmpleSliderIntegration] AmpleFilter not ready, retrying...');
                setTimeout(() => this.initializeFilterState(), 100);
                return;
            }
            
            // Check for URL parameters first
            const urlParams = new URLSearchParams(window.location.search);
            let urlFiltersApplied = false;
            
            // Apply THC from URL if present
            if (urlParams.has('thc')) {
                const thcParam = urlParams.get('thc');
                const thcRange = this.parseRangeParam(thcParam);
                if (thcRange && this.isValidRange(thcRange, 'thc')) {
                    this.setSliderValues('thc', thcRange[0], thcRange[1]);
                    window.AmpleFilter.activeFilters.thc = thcRange;
                    urlFiltersApplied = true;
                    // THC applied from URL
                }
            }
            
            // Apply CBD from URL if present
            if (urlParams.has('cbd')) {
                const cbdParam = urlParams.get('cbd');
                const cbdRange = this.parseRangeParam(cbdParam);
                if (cbdRange && this.isValidRange(cbdRange, 'cbd')) {
                    this.setSliderValues('cbd', cbdRange[0], cbdRange[1]);
                    window.AmpleFilter.activeFilters.cbd = cbdRange;
                    urlFiltersApplied = true;
                    // CBD applied from URL
                }
            }
            
            // If no URL filters, get initial values from sliders
            if (!urlFiltersApplied) {
                // Get initial values from THC slider if it exists
                const $thcSlider = $('#thc-dual-range-container');
                if ($thcSlider.length) {
                    const thcInstance = $thcSlider.data('ampleDualRangeSlider');
                    if (thcInstance) {
                        const thcValues = thcInstance.getValues();
                        window.AmpleFilter.activeFilters.thc = [thcValues.min, thcValues.max];
                        // THC filter initialized
                    }
                }
                
                // Get initial values from CBD slider if it exists
                const $cbdSlider = $('#cbd-dual-range-container');
                if ($cbdSlider.length) {
                    const cbdInstance = $cbdSlider.data('ampleDualRangeSlider');
                    if (cbdInstance) {
                        const cbdValues = cbdInstance.getValues();
                        window.AmpleFilter.activeFilters.cbd = [cbdValues.min, cbdValues.max];
                        // CBD filter initialized
                    }
                }
            }
        },
        
        /**
         * Handle slider apply event
         */
        handleSliderApply: function(data) {
            if (!data || !data.slider) return;
            
            // Extract slider type from ID (e.g., 'thc-dual-range' -> 'thc')
            const sliderType = this.getSliderType(data.slider);
            
            if (sliderType && window.AmpleFilter) {
                // Update the active filters
                window.AmpleFilter.activeFilters[sliderType] = [data.min, data.max];
                
                // Apply the filters
                window.AmpleFilter.applyFilters();
                
                // Filter applied
                
                // Visual feedback
                this.showFilterAppliedFeedback(sliderType, data);
            }
        },
        
        /**
         * Handle real-time slider changes
         */
        handleSliderChange: function(data) {
            if (!data || !data.slider) return;
            
            const sliderType = this.getSliderType(data.slider);
            
            if (sliderType && window.AmpleFilter) {
                // Debounced update
                clearTimeout(this.changeTimeout);
                this.changeTimeout = setTimeout(() => {
                    window.AmpleFilter.activeFilters[sliderType] = [data.min, data.max];
                    window.AmpleFilter.applyFilters();
                }, 100);
            }
        },
        
        /**
         * Apply slider filter directly
         */
        applySliderFilter: function(sliderId, values) {
            const sliderType = this.getSliderType(sliderId);
            
            if (!sliderType || !window.AmpleFilter) {
                console.error('[AmpleSliderIntegration] Cannot apply filter - AmpleFilter not ready or invalid slider type');
                return;
            }
            
            // Update the filter state
            window.AmpleFilter.activeFilters[sliderType] = [values.min, values.max];
            
            // Load products if not already loaded
            if (!window.AmpleFilter.products || window.AmpleFilter.products.length === 0) {
                // Loading products
                window.AmpleFilter.loadProducts().then(() => {
                    // Products loaded, applying filters
                });
            } else {
                // Apply filters immediately
                window.AmpleFilter.applyFilters();
            }
            
            // Show feedback
            this.showFilterAppliedFeedback(sliderType, values);
        },
        
        /**
         * Get slider type from slider ID
         */
        getSliderType: function(sliderId) {
            if (sliderId.includes('thc')) return 'thc';
            if (sliderId.includes('cbd')) return 'cbd';
            return null;
        },
        
        /**
         * Show visual feedback when filter is applied
         */
        showFilterAppliedFeedback: function(sliderType, values) {
            // Add a temporary success class to the apply button
            const $button = $(`.ample-dual-range-apply[data-slider*="${sliderType}"]`);
            $button.addClass('ample-filter-applied');
            
            // Show a toast notification
            const message = `${sliderType.toUpperCase()} filter applied: ${values.min}% - ${values.max}%`;
            this.showToast(message, 'success');
            
            // Remove the success class after animation
            setTimeout(() => {
                $button.removeClass('ample-filter-applied');
            }, 1000);
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info') {
            // Remove any existing toasts
            $('.ample-filter-toast').remove();
            
            // Create and show new toast
            const $toast = $(`
                <div class="ample-filter-toast ample-filter-toast-${type}">
                    ${message}
                </div>
            `);
            
            $('body').append($toast);
            
            // Animate in
            setTimeout(() => $toast.addClass('show'), 10);
            
            // Remove after delay
            setTimeout(() => {
                $toast.removeClass('show');
                setTimeout(() => $toast.remove(), 300);
            }, 2000);
        },
        
        /**
         * Parse range parameter from URL (e.g., "10-25" -> [10, 25])
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
         * Validate range values for specific filter type
         */
        isValidRange: function(range, filterType) {
            if (!Array.isArray(range) || range.length !== 2) return false;
            
            const [min, max] = range;
            
            // Check for valid numbers
            if (isNaN(min) || isNaN(max)) return false;
            
            // Check min <= max
            if (min > max) return false;
            
            // Type-specific validation
            switch (filterType) {
                case 'thc':
                    return min >= 0 && max <= 45 && min <= max;
                case 'cbd':
                    return min >= 0 && max <= 30 && min <= max;
                default:
                    return true;
            }
        },
        
        /**
         * Set slider values programmatically with retry
         */
        setSliderValues: function(sliderType, min, max, retries = 3) {
            const $container = $(`#${sliderType}-dual-range-container`);
            if ($container.length) {
                const sliderInstance = $container.data('ampleDualRangeSlider');
                if (sliderInstance) {
                    sliderInstance.setValues(min, max);
                    
                    // Manually trigger button text update after setting values from URL
                    setTimeout(() => {
                        if (window.AmpleButtonIndicators) {
                            window.AmpleButtonIndicators.updateButtonDisplay(`${sliderType}-dual-range-container`, min, max);
                        }
                    }, 100);
                    
                    // Slider values set
                    return true;
                } else if (retries > 0) {
                    // Retry after delay if slider instance not ready
                    setTimeout(() => {
                        this.setSliderValues(sliderType, min, max, retries - 1);
                    }, 500);
                    return false;
                }
            }
            console.warn(`[AmpleSliderIntegration] Could not find ${sliderType} slider to set values`);
            return false;
        }
    };
    
    // Initialize the integration
    AmpleSliderIntegration.init();
    
    // Export for debugging
    window.AmpleSliderIntegration = AmpleSliderIntegration;
    
})(jQuery);