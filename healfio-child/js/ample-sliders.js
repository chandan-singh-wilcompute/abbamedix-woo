/**
 * Ample Filter System - Slider Controls
 * 
 * Enhanced dual-range slider interactions and visual feedback
 * Part of Phase 1 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 */

(function($) {
    'use strict';
    
    // Slider control system
    window.AmpleFilter.sliderControls = {
        
        // Debounce timeout for filter updates
        filterTimeout: null,
        
        /**
         * Initialize all sliders on the page
         */
        init: function() {
            $(document).ready(() => {
                this.setupSliders();
                this.bindEvents();
                
                if (window.AmpleFilterConfig?.debug) {
                    // Sliders initialized
                }
            });
        },
        
        /**
         * Setup all slider components
         */
        setupSliders: function() {
            // Setup existing HTML structure sliders
            $('#ample-thc-slider').each((index, element) => {
                this.initializeExistingSlider($(element), 'thc');
            });
            
            $('#ample-cbd-slider').each((index, element) => {
                this.initializeExistingSlider($(element), 'cbd');
            });
            
            // Also setup any new-style slider containers
            $('.ample-slider-container').each((index, element) => {
                this.initializeSlider($(element));
            });
        },
        
        /**
         * Initialize existing HTML structure sliders (THC/CBD)
         */
        initializeExistingSlider: function($slider, sliderType) {
            const sliderConfig = this.getSliderConfig(sliderType);
            if (!sliderConfig) return;
            
            // Find the range inputs
            const $minInput = $slider.find(`.ample-${sliderType}-min`);
            const $maxInput = $slider.find(`.ample-${sliderType}-max`);
            const $minNumberInput = $slider.find(`.ample-${sliderType}-input-min`);
            const $maxNumberInput = $slider.find(`.ample-${sliderType}-input-max`);
            const $progress = $slider.find('.progress');
            
            if ($minInput.length && $maxInput.length) {
                // Set up data attributes
                $slider.attr('data-slider-type', sliderType);
                $minInput.attr('data-slider-role', 'min');
                $maxInput.attr('data-slider-role', 'max');
                
                // Initialize values
                const minValue = parseFloat($minInput.val()) || sliderConfig.min;
                const maxValue = parseFloat($maxInput.val()) || sliderConfig.max;
                
                // Bind events for this slider
                this.bindExistingSliderEvents($slider, sliderType);
                
                // Update visuals
                this.updateExistingSliderVisual($slider, minValue, maxValue, sliderType);
                
                // Update number inputs
                $minNumberInput.val(minValue);
                $maxNumberInput.val(maxValue);
                
                if (window.AmpleFilterConfig?.debug) {
                    // Slider initialized with values
                }
            }
        },
        
        /**
         * Bind events for existing slider structure
         */
        bindExistingSliderEvents: function($slider, sliderType) {
            const self = this;
            const $minInput = $slider.find(`.ample-${sliderType}-min`);
            const $maxInput = $slider.find(`.ample-${sliderType}-max`);
            const $minNumberInput = $slider.find(`.ample-${sliderType}-input-min`);
            const $maxNumberInput = $slider.find(`.ample-${sliderType}-input-max`);
            
            // Range input events
            $minInput.on('input', function() {
                self.handleExistingSliderInput($slider, sliderType, 'min');
            });
            
            $maxInput.on('input', function() {
                self.handleExistingSliderInput($slider, sliderType, 'max');
            });
            
            // Number input events  
            $minNumberInput.on('input', function() {
                self.handleExistingNumberInput($slider, sliderType, 'min');
            });
            
            $maxNumberInput.on('input', function() {
                self.handleExistingNumberInput($slider, sliderType, 'max');
            });
            
            // Apply button event
            $slider.closest('.filterDropdown').find(`.ample-${sliderType}-apply`).on('click', function() {
                self.handleApplyClick(sliderType);
            });
        },
        
        /**
         * Handle slider input for existing structure
         */
        handleExistingSliderInput: function($slider, sliderType, role) {
            const sliderConfig = this.getSliderConfig(sliderType);
            const $minInput = $slider.find(`.ample-${sliderType}-min`);
            const $maxInput = $slider.find(`.ample-${sliderType}-max`);
            const $minNumberInput = $slider.find(`.ample-${sliderType}-input-min`);
            const $maxNumberInput = $slider.find(`.ample-${sliderType}-input-max`);
            
            let minValue = parseFloat($minInput.val()) || 0;
            let maxValue = parseFloat($maxInput.val()) || 0;
            
            const gap = sliderConfig?.step || 0.5;
            
            // Enforce minimum gap between sliders
            if (role === 'min' && minValue >= maxValue - gap) {
                minValue = Math.max(maxValue - gap, sliderConfig.min);
                $minInput.val(minValue);
            } else if (role === 'max' && maxValue <= minValue + gap) {
                maxValue = Math.min(minValue + gap, sliderConfig.max);
                $maxInput.val(maxValue);
            }
            
            // Update number inputs
            $minNumberInput.val(minValue);
            $maxNumberInput.val(maxValue);
            
            // Update visuals
            this.updateExistingSliderVisual($slider, minValue, maxValue, sliderType);
            
            // Update active filters
            this.updateActiveFilters(sliderType, minValue, maxValue);
            
            // Trigger debounced filter update
            this.scheduleFilterUpdate();
        },
        
        /**
         * Handle number input for existing structure
         */
        handleExistingNumberInput: function($slider, sliderType, role) {
            const sliderConfig = this.getSliderConfig(sliderType);
            const $numberInput = $slider.find(`.ample-${sliderType}-input-${role}`);
            const $rangeInput = $slider.find(`.ample-${sliderType}-${role}`);
            
            let value = parseFloat($numberInput.val()) || 0;
            
            // Clamp to valid range
            value = Math.max(sliderConfig.min, Math.min(sliderConfig.max, value));
            $numberInput.val(value);
            $rangeInput.val(value);
            
            // Trigger slider input handler
            this.handleExistingSliderInput($slider, sliderType, role);
        },
        
        /**
         * Update visual progress for existing slider structure
         */
        updateExistingSliderVisual: function($slider, minValue, maxValue, sliderType) {
            const sliderConfig = this.getSliderConfig(sliderType);
            const $progress = $slider.find('.progress');
            
            const min = sliderConfig.min;
            const max = sliderConfig.max;
            
            const leftPercent = ((minValue - min) / (max - min)) * 100;
            const widthPercent = ((maxValue - minValue) / (max - min)) * 100;
            
            $progress.css({
                'left': leftPercent + '%',
                'width': widthPercent + '%'
            });
        },
        
        /**
         * Update active filters object
         */
        updateActiveFilters: function(filterType, minValue, maxValue) {
            if (!window.AmpleFilter.activeFilters) {
                window.AmpleFilter.activeFilters = {};
            }
            
            window.AmpleFilter.activeFilters[filterType] = [minValue, maxValue];
            
            if (window.AmpleFilterConfig?.debug) {
                // Filter updated with slider values
            }
        },
        
        /**
         * Schedule debounced filter update
         */
        scheduleFilterUpdate: function() {
            if (this.filterTimeout) {
                clearTimeout(this.filterTimeout);
            }
            
            this.filterTimeout = setTimeout(() => {
                if (window.AmpleFilter.applyFilters) {
                    window.AmpleFilter.applyFilters();
                }
                
                // Trigger event for URL manager
                $(document).trigger('ample-filter:products-filtered');
            }, 300); // 300ms debounce
        },
        
        /**
         * Handle Apply button click
         */
        handleApplyClick: function(filterType) {
            // Clear any pending debounced updates
            if (this.filterTimeout) {
                clearTimeout(this.filterTimeout);
            }
            
            // Apply filters immediately
            if (window.AmpleFilter.applyFilters) {
                window.AmpleFilter.applyFilters();
            }
            
            // Trigger event for URL manager
            $(document).trigger('ample-filter:products-filtered');
            
            if (window.AmpleFilterConfig?.debug) {
                // Filter applied immediately
            }
        },
        
        /**
         * Initialize a single slider component
         */
        initializeSlider: function($container) {
            const sliderType = $container.data('slider-type');
            const sliderConfig = this.getSliderConfig(sliderType);
            
            if (!sliderConfig) {
                console.warn(`Unknown slider type: ${sliderType}`);
                return;
            }
            
            // Update container with config data
            $container.attr({
                'data-min': sliderConfig.min,
                'data-max': sliderConfig.max,
                'data-step': sliderConfig.step,
                'data-unit': sliderConfig.unit
            });
            
            // Initialize slider visuals
            const $minSlider = $container.find('.ample-slider-min');
            const $maxSlider = $container.find('.ample-slider-max');
            
            if ($minSlider.length && $maxSlider.length) {
                const minValue = parseFloat($minSlider.val()) || sliderConfig.min;
                const maxValue = parseFloat($maxSlider.val()) || sliderConfig.max;
                
                this.updateSliderVisual($container, minValue, maxValue);
                this.updateSliderValues($container, minValue, maxValue);
            }
            
            // Add accessibility attributes
            this.enhanceAccessibility($container);
            
            // Add touch support for mobile
            this.addTouchSupport($container);
        },
        
        /**
         * Get slider configuration by type
         */
        getSliderConfig: function(sliderType) {
            const configs = {
                thc: {
                    min: 0,
                    max: 45,
                    step: 0.5,
                    unit: '%',
                    label: 'THC Range',
                    color: '#059669'
                },
                cbd: {
                    min: 0,
                    max: 30,
                    step: 0.5,
                    unit: '%',
                    label: 'CBD Range',
                    color: '#7c3aed'
                }
            };
            
            return configs[sliderType] || null;
        },
        
        /**
         * Bind slider events
         */
        bindEvents: function() {
            const self = this;
            
            // Enhanced slider input handling
            $(document).on('input.ample-slider', '.ample-slider-input', function(e) {
                self.handleSliderInput($(this), e);
            });
            
            // Mouse events for better UX
            $(document).on('mousedown.ample-slider', '.ample-slider-input', function() {
                $(this).closest('.ample-slider-container').addClass('ample-slider-dragging');
            });
            
            $(document).on('mouseup.ample-slider', '.ample-slider-input', function() {
                $(this).closest('.ample-slider-container').removeClass('ample-slider-dragging');
            });
            
            // Keyboard navigation
            $(document).on('keydown.ample-slider', '.ample-slider-input', function(e) {
                self.handleKeyboardNavigation($(this), e);
            });
            
            // Number input synchronization
            $(document).on('input.ample-slider', '.ample-slider-number-input', function() {
                self.handleNumberInput($(this));
            });
            
            // Focus/blur events for styling
            $(document).on('focus.ample-slider', '.ample-slider-input', function() {
                $(this).closest('.ample-slider-container').addClass('ample-slider-focused');
            });
            
            $(document).on('blur.ample-slider', '.ample-slider-input', function() {
                $(this).closest('.ample-slider-container').removeClass('ample-slider-focused');
            });
        },
        
        /**
         * Handle slider input changes with enhanced logic
         */
        handleSliderInput: function($slider, event) {
            const $container = $slider.closest('.ample-slider-container');
            const sliderRole = $slider.data('slider-role');
            const sliderType = $container.data('slider-type');
            
            const $minSlider = $container.find('.ample-slider-min');
            const $maxSlider = $container.find('.ample-slider-max');
            
            let minValue = parseFloat($minSlider.val()) || 0;
            let maxValue = parseFloat($maxSlider.val()) || 0;
            
            const sliderConfig = this.getSliderConfig(sliderType);
            const gap = sliderConfig?.step || 0.5;
            
            // Enforce minimum gap between sliders
            if (sliderRole === 'min' && minValue >= maxValue - gap) {
                minValue = Math.max(maxValue - gap, sliderConfig.min);
                $minSlider.val(minValue);
            } else if (sliderRole === 'max' && maxValue <= minValue + gap) {
                maxValue = Math.min(minValue + gap, sliderConfig.max);
                $maxSlider.val(maxValue);
            }
            
            // Update visuals with animation
            this.updateSliderVisual($container, minValue, maxValue, true);
            this.updateSliderValues($container, minValue, maxValue);
            
            // Provide haptic feedback on mobile
            if (this.isMobile() && navigator.vibrate) {
                navigator.vibrate(10);
            }
            
            // Trigger change event for filter system
            $container.trigger('slider-changed', {
                type: sliderType,
                min: minValue,
                max: maxValue,
                range: [minValue, maxValue]
            });
        },
        
        /**
         * Handle keyboard navigation for accessibility
         */
        handleKeyboardNavigation: function($slider, event) {
            const $container = $slider.closest('.ample-slider-container');
            const sliderType = $container.data('slider-type');
            const sliderConfig = this.getSliderConfig(sliderType);
            
            const currentValue = parseFloat($slider.val()) || 0;
            const step = sliderConfig?.step || 1;
            let newValue = currentValue;
            
            switch (event.key) {
                case 'ArrowRight':
                case 'ArrowUp':
                    newValue = Math.min(currentValue + step, sliderConfig.max);
                    event.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'ArrowDown':
                    newValue = Math.max(currentValue - step, sliderConfig.min);
                    event.preventDefault();
                    break;
                case 'Home':
                    newValue = sliderConfig.min;
                    event.preventDefault();
                    break;
                case 'End':
                    newValue = sliderConfig.max;
                    event.preventDefault();
                    break;
                case 'PageUp':
                    newValue = Math.min(currentValue + (step * 10), sliderConfig.max);
                    event.preventDefault();
                    break;
                case 'PageDown':
                    newValue = Math.max(currentValue - (step * 10), sliderConfig.min);
                    event.preventDefault();
                    break;
            }
            
            if (newValue !== currentValue) {
                $slider.val(newValue);
                this.handleSliderInput($slider, event);
            }
        },
        
        /**
         * Handle number input changes
         */
        handleNumberInput: function($input) {
            const $container = $input.closest('.ample-slider-container');
            const sliderRole = $input.data('slider-role');
            const value = parseFloat($input.val()) || 0;
            
            const sliderConfig = this.getSliderConfig($container.data('slider-type'));
            
            // Validate input range
            const clampedValue = Math.max(sliderConfig.min, Math.min(sliderConfig.max, value));
            
            if (clampedValue !== value) {
                $input.val(clampedValue);
            }
            
            // Update corresponding range slider
            const $slider = $container.find(`.ample-slider-${sliderRole}`);
            $slider.val(clampedValue);
            
            // Trigger slider update
            this.handleSliderInput($slider, { target: $slider[0] });
        },
        
        /**
         * Update slider visual appearance with animation
         */
        updateSliderVisual: function($container, minValue, maxValue, animate = false) {
            const $range = $container.find('.ample-slider-range');
            const sliderType = $container.data('slider-type');
            const sliderConfig = this.getSliderConfig(sliderType);
            
            const min = sliderConfig.min;
            const max = sliderConfig.max;
            
            const leftPercent = ((minValue - min) / (max - min)) * 100;
            const rightPercent = ((max - maxValue) / (max - min)) * 100;
            
            const css = {
                left: leftPercent + '%',
                right: rightPercent + '%'
            };
            
            if (animate) {
                $range.animate(css, {
                    duration: 150,
                    easing: 'swing'
                });
            } else {
                $range.css(css);
            }
            
            // Update dynamic color based on range
            this.updateSliderColor($container, minValue, maxValue);
        },
        
        /**
         * Update slider color based on values
         */
        updateSliderColor: function($container, minValue, maxValue) {
            const sliderType = $container.data('slider-type');
            const sliderConfig = this.getSliderConfig(sliderType);
            const $range = $container.find('.ample-slider-range');
            
            // Calculate intensity based on range selection
            const totalRange = sliderConfig.max - sliderConfig.min;
            const selectedRange = maxValue - minValue;
            const intensity = 1 - (selectedRange / totalRange);
            
            // Adjust opacity or saturation based on selection
            const baseColor = sliderConfig.color;
            const alpha = Math.max(0.3, Math.min(1, 0.5 + intensity * 0.5));
            
            // Convert hex to rgba
            const rgb = this.hexToRgb(baseColor);
            if (rgb) {
                $range.css('background', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`);
            }
        },
        
        /**
         * Update slider value displays
         */
        updateSliderValues: function($container, minValue, maxValue) {
            const unit = $container.data('unit') || '';
            const $values = $container.find('.ample-slider-values');
            
            // Format values for display
            const formattedMin = this.formatValue(minValue, unit);
            const formattedMax = this.formatValue(maxValue, unit);
            
            $values.find('.ample-slider-min-value').text(formattedMin);
            $values.find('.ample-slider-max-value').text(formattedMax);
            
            // Update number inputs
            $container.find('.ample-slider-min-input').val(minValue);
            $container.find('.ample-slider-max-input').val(maxValue);
            
            // Add visual feedback for extreme values
            this.updateExtremeValueFeedback($container, minValue, maxValue);
        },
        
        /**
         * Add visual feedback for extreme values
         */
        updateExtremeValueFeedback: function($container, minValue, maxValue) {
            const sliderConfig = this.getSliderConfig($container.data('slider-type'));
            const $values = $container.find('.ample-slider-values');
            
            // Highlight when at extremes
            const isMinExtreme = minValue <= sliderConfig.min;
            const isMaxExtreme = maxValue >= sliderConfig.max;
            
            $values.toggleClass('ample-slider-min-extreme', isMinExtreme);
            $values.toggleClass('ample-slider-max-extreme', isMaxExtreme);
            $values.toggleClass('ample-slider-full-range', isMinExtreme && isMaxExtreme);
        },
        
        /**
         * Enhance accessibility features
         */
        enhanceAccessibility: function($container) {
            const sliderType = $container.data('slider-type');
            const sliderConfig = this.getSliderConfig(sliderType);
            
            const $sliders = $container.find('.ample-slider-input');
            
            $sliders.each(function() {
                const $slider = $(this);
                const role = $slider.data('slider-role');
                
                $slider.attr({
                    'aria-valuemin': sliderConfig.min,
                    'aria-valuemax': sliderConfig.max,
                    'aria-valuenow': $slider.val(),
                    'aria-label': `${sliderConfig.label} ${role} value`,
                    'role': 'slider',
                    'tabindex': '0'
                });
            });
            
            // Update aria-valuenow on changes
            $container.on('slider-changed', function(e, data) {
                $container.find('.ample-slider-min').attr('aria-valuenow', data.min);
                $container.find('.ample-slider-max').attr('aria-valuenow', data.max);
            });
        },
        
        /**
         * Add touch support for mobile devices
         */
        addTouchSupport: function($container) {
            if (!this.isMobile()) return;
            
            const $sliders = $container.find('.ample-slider-input');
            
            $sliders.on('touchstart', function() {
                $(this).closest('.ample-slider-container').addClass('ample-slider-touch-active');
            });
            
            $sliders.on('touchend', function() {
                $(this).closest('.ample-slider-container').removeClass('ample-slider-touch-active');
            });
            
            // Prevent page scroll during slider interaction
            $sliders.on('touchmove', function(e) {
                e.preventDefault();
            });
        },
        
        /**
         * Format value for display
         */
        formatValue: function(value, unit) {
            if (unit === '%') {
                return value.toFixed(1) + unit;
            }
            
            if (value >= 1000) {
                return (value / 1000).toFixed(1) + 'k' + unit;
            }
            
            return value.toFixed(2) + unit;
        },
        
        /**
         * Convert hex color to RGB
         */
        hexToRgb: function(hex) {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        },
        
        /**
         * Check if device is mobile
         */
        isMobile: function() {
            return window.innerWidth <= 768 || ('ontouchstart' in window);
        },
        
        /**
         * Reset slider to default values
         */
        resetSlider: function(sliderType) {
            const $container = $(`.ample-slider-container[data-slider-type="${sliderType}"]`);
            const sliderConfig = this.getSliderConfig(sliderType);
            
            if ($container.length && sliderConfig) {
                $container.find('.ample-slider-min').val(sliderConfig.min);
                $container.find('.ample-slider-max').val(sliderConfig.max);
                
                this.updateSliderVisual($container, sliderConfig.min, sliderConfig.max);
                this.updateSliderValues($container, sliderConfig.min, sliderConfig.max);
                
                $container.trigger('slider-changed', {
                    type: sliderType,
                    min: sliderConfig.min,
                    max: sliderConfig.max,
                    range: [sliderConfig.min, sliderConfig.max]
                });
            }
        },
        
        /**
         * Get current slider values
         */
        getSliderValues: function(sliderType) {
            const $container = $(`.ample-slider-container[data-slider-type="${sliderType}"]`);
            
            if (!$container.length) return null;
            
            const minValue = parseFloat($container.find('.ample-slider-min').val()) || 0;
            const maxValue = parseFloat($container.find('.ample-slider-max').val()) || 0;
            
            return [minValue, maxValue];
        },
        
        /**
         * Set slider values programmatically
         */
        setSliderValues: function(sliderType, minValue, maxValue) {
            const $container = $(`.ample-slider-container[data-slider-type="${sliderType}"]`);
            const sliderConfig = this.getSliderConfig(sliderType);
            
            if (!$container.length || !sliderConfig) return false;
            
            // Clamp values to valid range
            minValue = Math.max(sliderConfig.min, Math.min(sliderConfig.max, minValue));
            maxValue = Math.max(sliderConfig.min, Math.min(sliderConfig.max, maxValue));
            
            // Ensure min <= max
            if (minValue > maxValue) {
                [minValue, maxValue] = [maxValue, minValue];
            }
            
            $container.find('.ample-slider-min').val(minValue);
            $container.find('.ample-slider-max').val(maxValue);
            
            this.updateSliderVisual($container, minValue, maxValue);
            this.updateSliderValues($container, minValue, maxValue);
            
            $container.trigger('slider-changed', {
                type: sliderType,
                min: minValue,
                max: maxValue,
                range: [minValue, maxValue]
            });
            
            return true;
        }
    };
    
    // Auto-initialize slider controls
    window.AmpleFilter.sliderControls.init();
    
})(jQuery);