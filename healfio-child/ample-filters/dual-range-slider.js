/**
 * Ample Dual Range Slider - JavaScript
 * 
 * Modern, interactive dual-range slider component
 * Provides proper draggable handles like OCS.ca reference
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 * @author Claude Code
 */

(function($) {
    'use strict';
    
    /**
     * Dual Range Slider Class
     */
    class AmpleDualRangeSlider {
        
        constructor(container) {
            this.container = $(container);
            this.sliderId = this.container.attr('id');
            this.slider = this.container.find('.ample-dual-range-slider');
            
            // Get configuration from data attributes
            this.min = parseFloat(this.container.data('min')) || 0;
            this.max = parseFloat(this.container.data('max')) || 100;
            this.step = parseFloat(this.container.data('step')) || 1;
            this.unit = this.container.data('unit') || '';
            this.color = this.container.data('color') || '#059669';
            
            // Get elements
            this.minInput = this.container.find('.ample-dual-range-min');
            this.maxInput = this.container.find('.ample-dual-range-max');
            this.minThumb = this.container.find('.ample-dual-range-thumb-min');
            this.maxThumb = this.container.find('.ample-dual-range-thumb-max');
            this.highlight = this.container.find('.ample-dual-range-highlight');
            this.minNumberInput = this.container.find('.ample-dual-range-number-input[data-role="min"]');
            this.maxNumberInput = this.container.find('.ample-dual-range-number-input[data-role="max"]');
            this.minValueDisplay = this.container.find('.ample-dual-range-min-value');
            this.maxValueDisplay = this.container.find('.ample-dual-range-max-value');
            this.applyButton = this.container.find('.ample-dual-range-apply');
            
            // Current values - ensure they're set to the input values or defaults
            this.minValue = parseFloat(this.minInput.val()) || this.min;
            this.maxValue = parseFloat(this.maxInput.val()) || this.max;
            
            // Dual range slider initialized
            
            this.init();
        }
        
        /**
         * Initialize the slider
         */
        init() {
            this.bindEvents();
            
            // Ensure values are properly synchronized
            this.minValue = parseFloat(this.minInput.val()) || this.min;
            this.maxValue = parseFloat(this.maxInput.val()) || this.max;
            
            // Force initial update
            this.updateSlider();
            this.updateNumberInputs();
            this.updateValueDisplays();
            
            // Only log initialization once
            if (window.AmpleFilterConfig && window.AmpleFilterConfig.debug) {
                // Slider initialized
            }
        }
        
        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;
            
            // Range input events - multiple event types for better responsiveness
            this.minInput.on('input change', function() {
                self.handleMinInput();
            });
            
            this.maxInput.on('input change', function() {
                self.handleMaxInput();
            });
            
            // Add direct mouse interaction on visual thumbs
            this.bindDirectThumbInteraction();
            
            // Number input events
            this.minNumberInput.on('input', function() {
                self.handleMinNumberInput();
            });
            
            this.maxNumberInput.on('input', function() {
                self.handleMaxNumberInput();
            });
            
            // Apply button event
            this.applyButton.on('click', function() {
                self.handleApply();
            });
            
            // Keyboard support
            this.minInput.on('keydown', function(e) {
                self.handleKeyboard(e, 'min');
            });
            
            this.maxInput.on('keydown', function(e) {
                self.handleKeyboard(e, 'max');
            });
            
            // Focus events for better UX
            this.minInput.on('focus', function() {
                self.minThumb.addClass('focused');
            }).on('blur', function() {
                self.minThumb.removeClass('focused');
            });
            
            this.maxInput.on('focus', function() {
                self.maxThumb.addClass('focused');
            }).on('blur', function() {
                self.maxThumb.removeClass('focused');
            });
        }
        
        /**
         * Bind direct mouse interaction on visual thumbs
         */
        bindDirectThumbInteraction() {
            const self = this;
            
            // Make visual thumbs interactive
            this.minThumb.add(this.maxThumb).css({
                'cursor': 'grab',
                'pointer-events': 'auto'
            });
            
            // Mouse down on thumbs
            this.minThumb.on('mousedown', function(e) {
                e.preventDefault();
                $(this).css('cursor', 'grabbing');
                $(document).on('mousemove.slider', function(e) {
                    self.handleDirectDrag(e, 'min');
                });
                $(document).on('mouseup.slider', function() {
                    self.stopDirectDrag();
                });
            });
            
            this.maxThumb.on('mousedown', function(e) {
                e.preventDefault();
                $(this).css('cursor', 'grabbing');
                $(document).on('mousemove.slider', function(e) {
                    self.handleDirectDrag(e, 'max');
                });
                $(document).on('mouseup.slider', function() {
                    self.stopDirectDrag();
                });
            });
            
            // Direct thumb interaction bound (no need to log each time)
        }
        
        /**
         * Handle direct drag of visual thumbs
         */
        handleDirectDrag(event, thumbType) {
            const sliderRect = this.slider[0].getBoundingClientRect();
            const mouseX = event.clientX - sliderRect.left;
            const percentage = Math.max(0, Math.min(100, (mouseX / sliderRect.width) * 100));
            const value = this.min + (percentage / 100) * (this.max - this.min);
            
            // Round to step
            const roundedValue = Math.round(value / this.step) * this.step;
            
            if (thumbType === 'min') {
                if (roundedValue < this.maxValue - this.step) {
                    this.minValue = roundedValue;
                    this.minInput.val(roundedValue);
                }
            } else {
                if (roundedValue > this.minValue + this.step) {
                    this.maxValue = roundedValue;
                    this.maxInput.val(roundedValue);
                }
            }
            
            this.updateSlider();
            this.updateNumberInputs();
            this.updateValueDisplays();
            this.triggerChange();
        }
        
        /**
         * Stop direct drag interaction
         */
        stopDirectDrag() {
            $(document).off('mousemove.slider mouseup.slider');
            this.minThumb.add(this.maxThumb).css('cursor', 'grab');
        }
        
        /**
         * Handle min range input change
         */
        handleMinInput() {
            let value = parseFloat(this.minInput.val());
            const gap = this.step;
            
            // Ensure min doesn't exceed max - gap
            if (value > this.maxValue - gap) {
                value = Math.max(this.maxValue - gap, this.min);
                this.minInput.val(value);
            }
            
            this.minValue = value;
            this.updateSlider();
            this.updateNumberInputs();
            this.updateValueDisplays();
            
            this.triggerChange();
        }
        
        /**
         * Handle max range input change
         */
        handleMaxInput() {
            let value = parseFloat(this.maxInput.val());
            const gap = this.step;
            
            // Ensure max doesn't go below min + gap
            if (value < this.minValue + gap) {
                value = Math.min(this.minValue + gap, this.max);
                this.maxInput.val(value);
            }
            
            this.maxValue = value;
            this.updateSlider();
            this.updateNumberInputs();
            this.updateValueDisplays();
            
            this.triggerChange();
        }
        
        /**
         * Handle min number input change
         */
        handleMinNumberInput() {
            let value = parseFloat(this.minNumberInput.val()) || this.min;
            value = this.clampValue(value);
            
            if (value > this.maxValue - this.step) {
                value = this.maxValue - this.step;
            }
            
            this.minValue = value;
            this.minInput.val(value);
            this.minNumberInput.val(value);
            this.updateSlider();
            this.updateValueDisplays();
            
            this.triggerChange();
        }
        
        /**
         * Handle max number input change
         */
        handleMaxNumberInput() {
            let value = parseFloat(this.maxNumberInput.val()) || this.max;
            value = this.clampValue(value);
            
            if (value < this.minValue + this.step) {
                value = this.minValue + this.step;
            }
            
            this.maxValue = value;
            this.maxInput.val(value);
            this.maxNumberInput.val(value);
            this.updateSlider();
            this.updateValueDisplays();
            
            this.triggerChange();
        }
        
        /**
         * Handle keyboard navigation
         */
        handleKeyboard(event, type) {
            const currentValue = type === 'min' ? this.minValue : this.maxValue;
            let newValue = currentValue;
            
            switch(event.key) {
                case 'ArrowRight':
                case 'ArrowUp':
                    newValue = Math.min(currentValue + this.step, this.max);
                    event.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'ArrowDown':
                    newValue = Math.max(currentValue - this.step, this.min);
                    event.preventDefault();
                    break;
                case 'Home':
                    newValue = this.min;
                    event.preventDefault();
                    break;
                case 'End':
                    newValue = this.max;
                    event.preventDefault();
                    break;
            }
            
            if (newValue !== currentValue) {
                if (type === 'min') {
                    this.minInput.val(newValue);
                    this.handleMinInput();
                } else {
                    this.maxInput.val(newValue);
                    this.handleMaxInput();
                }
            }
        }
        
        /**
         * Update slider visual elements
         */
        updateSlider() {
            const range = this.max - this.min;
            const minPercent = ((this.minValue - this.min) / range) * 100;
            const maxPercent = ((this.maxValue - this.min) / range) * 100;
            
            // Update thumb positions - ensure they match the range input values exactly
            this.minThumb.css('left', minPercent + '%');
            this.maxThumb.css('left', maxPercent + '%');
            
            // Update highlight bar
            this.highlight.css({
                'left': minPercent + '%',
                'width': (maxPercent - minPercent) + '%'
            });
            
            // Also ensure range inputs have correct values
            this.minInput.val(this.minValue);
            this.maxInput.val(this.maxValue);
            
            // Remove verbose visual update logging
        }
        
        /**
         * Update number inputs
         */
        updateNumberInputs() {
            this.minNumberInput.val(this.minValue);
            this.maxNumberInput.val(this.maxValue);
        }
        
        /**
         * Update value displays
         */
        updateValueDisplays() {
            this.minValueDisplay.text(this.formatValue(this.minValue));
            this.maxValueDisplay.text(this.formatValue(this.maxValue));
        }
        
        /**
         * Format value for display
         */
        formatValue(value) {
            return value.toFixed(this.getDecimalPlaces()) + this.unit;
        }
        
        /**
         * Get appropriate decimal places based on step
         */
        getDecimalPlaces() {
            const step = this.step.toString();
            if (step.indexOf('.') !== -1) {
                return step.split('.')[1].length;
            }
            return 0;
        }
        
        /**
         * Clamp value to valid range
         */
        clampValue(value) {
            return Math.max(this.min, Math.min(this.max, value));
        }
        
        /**
         * Handle apply button click
         */
        handleApply() {
            this.logDebug('Apply button clicked:', {
                min: this.minValue,
                max: this.maxValue,
                slider: this.sliderId
            });
            
            // Trigger custom event for filter system
            this.container.trigger('ample-dual-range:applied', {
                min: this.minValue,
                max: this.maxValue,
                range: [this.minValue, this.maxValue],
                slider: this.sliderId
            });
            
            // Visual feedback
            this.applyButton.addClass('applied');
            setTimeout(() => {
                this.applyButton.removeClass('applied');
            }, 200);
        }
        
        /**
         * Trigger change event
         */
        triggerChange() {
            this.container.trigger('ample-dual-range:changed', {
                min: this.minValue,
                max: this.maxValue,
                range: [this.minValue, this.maxValue],
                slider: this.sliderId
            });
        }
        
        /**
         * Set values programmatically
         */
        setValues(min, max) {
            this.minValue = this.clampValue(min);
            this.maxValue = this.clampValue(max);
            
            // Ensure min <= max
            if (this.minValue > this.maxValue) {
                [this.minValue, this.maxValue] = [this.maxValue, this.minValue];
            }
            
            this.minInput.val(this.minValue);
            this.maxInput.val(this.maxValue);
            this.updateSlider();
            this.updateNumberInputs();
            this.updateValueDisplays();
        }
        
        /**
         * Get current values
         */
        getValues() {
            return {
                min: this.minValue,
                max: this.maxValue,
                range: [this.minValue, this.maxValue]
            };
        }
        
        /**
         * Reset to default values
         */
        reset() {
            this.setValues(this.min, this.max);
            this.triggerChange();
        }
        
        /**
         * Debug logging
         */
        logDebug(message, data = null) {
            // Only log in debug mode and when explicitly enabled
            if (window.AmpleFilterConfig && window.AmpleFilterConfig.debug && window.AmpleFilterConfig.verboseSliderLogs) {
                // Debug message logged
            }
        }
    }
    
    /**
     * jQuery Plugin
     */
    $.fn.ampleDualRangeSlider = function() {
        return this.each(function() {
            if (!$(this).data('ampleDualRangeSlider')) {
                $(this).data('ampleDualRangeSlider', new AmpleDualRangeSlider(this));
            }
        });
    };
    
    /**
     * Auto-initialize sliders
     */
    $(document).ready(function() {
        // Wait a moment for all elements to be fully rendered
        setTimeout(function() {
            $('.ample-dual-range-container').ampleDualRangeSlider();
            
            // Auto-initialized sliders
            
            // Force update all sliders after initialization
            $('.ample-dual-range-container').each(function() {
                const sliderInstance = $(this).data('ampleDualRangeSlider');
                if (sliderInstance) {
                    sliderInstance.updateSlider();
                    // Forced slider update
                }
            });
        }, 100);
    });
    
    /**
     * Global access
     */
    window.AmpleDualRangeSlider = AmpleDualRangeSlider;
    
})(jQuery);