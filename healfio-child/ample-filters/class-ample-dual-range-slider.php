<?php
/**
 * Ample Dual Range Slider Component
 * 
 * A clean, modern dual-range slider component for THC/CBD filtering
 * Provides proper draggable handles like OCS.ca reference
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 * @author Claude Code
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ample_Dual_Range_Slider {
    
    /**
     * Component version
     */
    const VERSION = '1.0.0';
    
    /**
     * Initialize the dual range slider component
     */
    public static function init() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }
    
    /**
     * Enqueue component assets
     */
    public static function enqueue_assets() {
        // Load on all pages for now (for testing)
        // TODO: Restrict to product filter page later
        
        wp_enqueue_script(
            'ample-dual-range-slider',
            get_stylesheet_directory_uri() . '/ample-filters/dual-range-slider.js',
            ['jquery'],
            self::VERSION,
            true
        );
        
        wp_enqueue_style(
            'ample-dual-range-slider',
            get_stylesheet_directory_uri() . '/ample-filters/dual-range-slider.css',
            [],
            self::VERSION
        );
    }
    
    /**
     * Render a dual range slider
     * 
     * @param array $args Slider configuration
     * @return string HTML output
     */
    public static function render($args = []) {
        $defaults = [
            'id' => 'dual-range-slider',
            'label' => 'Range',
            'min' => 0,
            'max' => 100,
            'min_value' => null,
            'max_value' => null,
            'step' => 1,
            'unit' => '',
            'color' => '#059669',
            'class' => '',
            'show_inputs' => true,
            'show_apply_button' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Set default values if not provided
        if ($args['min_value'] === null) {
            $args['min_value'] = $args['min'];
        }
        if ($args['max_value'] === null) {
            $args['max_value'] = $args['max'];
        }
        
        // Sanitize values
        $id = sanitize_html_class($args['id']);
        $label = esc_html($args['label']);
        $min = floatval($args['min']);
        $max = floatval($args['max']);
        $min_value = floatval($args['min_value']);
        $max_value = floatval($args['max_value']);
        $step = floatval($args['step']);
        $unit = esc_html($args['unit']);
        $color = sanitize_hex_color($args['color']);
        $class = sanitize_html_class($args['class']);
        
        ob_start();
        ?>
        <div class="ample-dual-range-container <?php echo $class; ?>" 
             data-min="<?php echo $min; ?>" 
             data-max="<?php echo $max; ?>" 
             data-step="<?php echo $step; ?>"
             data-unit="<?php echo $unit; ?>"
             data-color="<?php echo $color; ?>"
             id="<?php echo $id; ?>-container">
            
            <!-- Slider Header -->
            <div class="ample-dual-range-header">
                <label class="ample-dual-range-label"><?php echo $label; ?></label>
                <div class="ample-dual-range-values">
                    <span class="ample-dual-range-min-value"><?php echo $min_value . $unit; ?></span>
                    <span class="ample-dual-range-separator">-</span>
                    <span class="ample-dual-range-max-value"><?php echo $max_value . $unit; ?></span>
                </div>
            </div>
            
            <!-- Slider Track and Handles -->
            <div class="ample-dual-range-slider" id="<?php echo $id; ?>">
                <!-- Background track -->
                <div class="ample-dual-range-track"></div>
                
                <!-- Active range highlight -->
                <div class="ample-dual-range-highlight" style="background-color: <?php echo $color; ?>"></div>
                
                <!-- Range inputs (invisible but functional) -->
                <input type="range" 
                       class="ample-dual-range-input ample-dual-range-min" 
                       min="<?php echo $min; ?>" 
                       max="<?php echo $max; ?>" 
                       step="<?php echo $step; ?>"
                       value="<?php echo $min_value; ?>"
                       data-role="min"
                       id="<?php echo $id; ?>-min">
                       
                <input type="range" 
                       class="ample-dual-range-input ample-dual-range-max" 
                       min="<?php echo $min; ?>" 
                       max="<?php echo $max; ?>" 
                       step="<?php echo $step; ?>"
                       value="<?php echo $max_value; ?>"
                       data-role="max"
                       id="<?php echo $id; ?>-max">
                       
                <!-- Custom thumb handles -->
                <div class="ample-dual-range-thumb ample-dual-range-thumb-min" 
                     style="border-color: <?php echo $color; ?>"></div>
                <div class="ample-dual-range-thumb ample-dual-range-thumb-max" 
                     style="border-color: <?php echo $color; ?>"></div>
            </div>
            
            <?php if ($args['show_inputs']): ?>
            <!-- Number inputs for precise control -->
            <div class="ample-dual-range-inputs">
                <div class="ample-dual-range-input-group">
                    <label for="<?php echo $id; ?>-min-input">Min</label>
                    <input type="number" 
                           class="ample-dual-range-number-input" 
                           id="<?php echo $id; ?>-min-input"
                           min="<?php echo $min; ?>" 
                           max="<?php echo $max; ?>" 
                           step="<?php echo $step; ?>"
                           value="<?php echo $min_value; ?>"
                           data-role="min">
                </div>
                
                <div class="ample-dual-range-input-group">
                    <label for="<?php echo $id; ?>-max-input">Max</label>
                    <input type="number" 
                           class="ample-dual-range-number-input" 
                           id="<?php echo $id; ?>-max-input"
                           min="<?php echo $min; ?>" 
                           max="<?php echo $max; ?>" 
                           step="<?php echo $step; ?>"
                           value="<?php echo $max_value; ?>"
                           data-role="max">
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($args['show_apply_button']): ?>
            <!-- Apply button -->
            <div class="ample-dual-range-actions">
                <button type="button" 
                        class="ample-dual-range-apply" 
                        data-slider="<?php echo $id; ?>"
                        style="background-color: <?php echo $color; ?>">
                    Apply Filter
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render THC slider with predefined settings
     */
    public static function render_thc_slider() {
        return self::render([
            'id' => 'thc-dual-range',
            'label' => 'THC Range',
            'min' => 0,
            'max' => 45,
            'step' => 0.5,
            'unit' => '%',
            'color' => '#059669',
            'class' => 'thc-slider'
        ]);
    }
    
    /**
     * Render CBD slider with predefined settings
     */
    public static function render_cbd_slider() {
        return self::render([
            'id' => 'cbd-dual-range',
            'label' => 'CBD Range',
            'min' => 0,
            'max' => 30,
            'step' => 0.5, // Changed from 0.1 to prevent floating point precision errors
            'unit' => '%',
            'color' => '#7c3aed',
            'class' => 'cbd-slider'
        ]);
    }
}

// Initialize the component
Ample_Dual_Range_Slider::init();