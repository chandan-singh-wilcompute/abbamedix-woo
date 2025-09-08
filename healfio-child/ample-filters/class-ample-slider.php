<?php
/**
 * Ample Filter System - Slider Components Class
 * 
 * Handles THC/CBD dual-range slider rendering and configuration
 * Part of Phase 1 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ample_Filter_Slider {
    
    /**
     * Default slider configurations
     */
    const DEFAULT_THC_CONFIG = [
        'min' => 0,
        'max' => 45,
        'step' => 0.5,
        'unit' => '%',
        'label' => 'THC Range',
        'id' => 'thc'
    ];
    
    const DEFAULT_CBD_CONFIG = [
        'min' => 0,
        'max' => 30,
        'step' => 0.5,
        'unit' => '%',
        'label' => 'CBD Range', 
        'id' => 'cbd'
    ];
    
    /**
     * Render THC range slider
     */
    public static function render_thc_slider($config = []) {
        $config = wp_parse_args($config, self::DEFAULT_THC_CONFIG);
        return self::render_dual_range_slider($config);
    }
    
    /**
     * Render CBD range slider
     */
    public static function render_cbd_slider($config = []) {
        $config = wp_parse_args($config, self::DEFAULT_CBD_CONFIG);
        return self::render_dual_range_slider($config);
    }
    
    /**
     * Render a dual-range slider component
     */
    public static function render_dual_range_slider($config) {
        $config = wp_parse_args($config, [
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'unit' => '',
            'label' => 'Range',
            'id' => 'range',
            'class' => '',
            'default_min' => null,
            'default_max' => null
        ]);
        
        // Set default values if not provided
        if ($config['default_min'] === null) {
            $config['default_min'] = $config['min'];
        }
        if ($config['default_max'] === null) {
            $config['default_max'] = $config['max'];
        }
        
        // Generate unique IDs for multiple sliders
        $slider_id = 'ample-slider-' . $config['id'];
        $min_id = $slider_id . '-min';
        $max_id = $slider_id . '-max';
        
        ob_start();
        ?>
        <div class="ample-slider-container <?php echo esc_attr($config['class']); ?>" 
             data-slider-type="<?php echo esc_attr($config['id']); ?>"
             data-min="<?php echo esc_attr($config['min']); ?>"
             data-max="<?php echo esc_attr($config['max']); ?>"
             data-step="<?php echo esc_attr($config['step']); ?>"
             data-unit="<?php echo esc_attr($config['unit']); ?>">
            
            <div class="ample-slider-header">
                <label class="ample-slider-label" for="<?php echo esc_attr($slider_id); ?>">
                    <?php echo esc_html($config['label']); ?>
                </label>
                <div class="ample-slider-values" id="<?php echo esc_attr($slider_id); ?>-values">
                    <span class="ample-slider-min-value">
                        <?php echo esc_html($config['default_min'] . $config['unit']); ?>
                    </span>
                    <span class="ample-slider-separator"> - </span>
                    <span class="ample-slider-max-value">
                        <?php echo esc_html($config['default_max'] . $config['unit']); ?>
                    </span>
                </div>
            </div>
            
            <div class="ample-slider-track-container" id="<?php echo esc_attr($slider_id); ?>">
                <div class="ample-slider-track">
                    <div class="ample-slider-range" id="<?php echo esc_attr($slider_id); ?>-range"></div>
                </div>
                
                <input type="range" 
                       class="ample-slider-input ample-slider-min" 
                       id="<?php echo esc_attr($min_id); ?>"
                       min="<?php echo esc_attr($config['min']); ?>"
                       max="<?php echo esc_attr($config['max']); ?>"
                       step="<?php echo esc_attr($config['step']); ?>"
                       value="<?php echo esc_attr($config['default_min']); ?>"
                       data-slider-id="<?php echo esc_attr($slider_id); ?>"
                       data-slider-role="min"
                       aria-label="<?php echo esc_attr($config['label'] . ' minimum value'); ?>">
                
                <input type="range" 
                       class="ample-slider-input ample-slider-max" 
                       id="<?php echo esc_attr($max_id); ?>"
                       min="<?php echo esc_attr($config['min']); ?>"
                       max="<?php echo esc_attr($config['max']); ?>"
                       step="<?php echo esc_attr($config['step']); ?>"
                       value="<?php echo esc_attr($config['default_max']); ?>"
                       data-slider-id="<?php echo esc_attr($slider_id); ?>"
                       data-slider-role="max"
                       aria-label="<?php echo esc_attr($config['label'] . ' maximum value'); ?>">
            </div>
            
            <div class="ample-slider-inputs">
                <div class="ample-slider-input-group">
                    <label for="<?php echo esc_attr($min_id); ?>-number" class="sr-only">
                        Minimum <?php echo esc_html($config['label']); ?>
                    </label>
                    <input type="number" 
                           class="ample-slider-number-input ample-slider-min-input" 
                           id="<?php echo esc_attr($min_id); ?>-number"
                           min="<?php echo esc_attr($config['min']); ?>"
                           max="<?php echo esc_attr($config['max']); ?>"
                           step="<?php echo esc_attr($config['step']); ?>"
                           value="<?php echo esc_attr($config['default_min']); ?>"
                           data-slider-id="<?php echo esc_attr($slider_id); ?>"
                           data-slider-role="min">
                    <span class="ample-slider-unit"><?php echo esc_html($config['unit']); ?></span>
                </div>
                
                <span class="ample-slider-to">to</span>
                
                <div class="ample-slider-input-group">
                    <label for="<?php echo esc_attr($max_id); ?>-number" class="sr-only">
                        Maximum <?php echo esc_html($config['label']); ?>
                    </label>
                    <input type="number" 
                           class="ample-slider-number-input ample-slider-max-input" 
                           id="<?php echo esc_attr($max_id); ?>-number"
                           min="<?php echo esc_attr($config['min']); ?>"
                           max="<?php echo esc_attr($config['max']); ?>"
                           step="<?php echo esc_attr($config['step']); ?>"
                           value="<?php echo esc_attr($config['default_max']); ?>"
                           data-slider-id="<?php echo esc_attr($slider_id); ?>"
                           data-slider-role="max">
                    <span class="ample-slider-unit"><?php echo esc_html($config['unit']); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get slider configuration for JavaScript
     */
    public static function get_slider_config($slider_id) {
        $configs = [
            'thc' => self::DEFAULT_THC_CONFIG,
            'cbd' => self::DEFAULT_CBD_CONFIG
        ];
        
        if (!isset($configs[$slider_id])) {
            return null;
        }
        
        $config = $configs[$slider_id];
        
        // Add JavaScript-specific settings
        $config['gap'] = $config['step']; // Minimum gap between handles
        $config['tooltips'] = true;
        $config['animate'] = true;
        $config['debounce'] = 100; // ms
        
        return $config;
    }
    
    /**
     * Get all slider configurations for JavaScript
     */
    public static function get_all_slider_configs() {
        return [
            'thc' => self::get_slider_config('thc'),
            'cbd' => self::get_slider_config('cbd')
        ];
    }
    
    /**
     * Render slider styles inline (for critical CSS)
     */
    public static function render_inline_styles() {
        ob_start();
        ?>
        <style>
        .ample-slider-container {
            margin: 1rem 0;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .ample-slider-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .ample-slider-label {
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .ample-slider-values {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        
        .ample-slider-track-container {
            position: relative;
            height: 2rem;
            margin: 1rem 0;
        }
        
        .ample-slider-track {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            transform: translateY(-50%);
        }
        
        .ample-slider-range {
            position: absolute;
            top: 0;
            height: 100%;
            background: #007cba;
            border-radius: 2px;
            transition: all 0.1s ease;
        }
        
        .ample-slider-input {
            position: absolute;
            top: 50%;
            width: 100%;
            height: 2rem;
            margin: 0;
            padding: 0;
            background: transparent;
            border: none;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            transform: translateY(-50%);
            cursor: pointer;
        }
        
        .ample-slider-input::-webkit-slider-thumb {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            background: #007cba;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.1s ease;
        }
        
        .ample-slider-input::-webkit-slider-thumb:hover {
            transform: scale(1.1);
        }
        
        .ample-slider-input::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #007cba;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.1s ease;
        }
        
        .ample-slider-inputs {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .ample-slider-input-group {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .ample-slider-number-input {
            width: 4rem;
            padding: 0.25rem 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .ample-slider-unit {
            font-size: 0.9rem;
            color: #666;
        }
        
        .ample-slider-to {
            color: #666;
            font-size: 0.9rem;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            border: 0;
        }
        
        @media (max-width: 768px) {
            .ample-slider-inputs {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            
            .ample-slider-input-group {
                justify-content: center;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}