/**
 * Ample Filter System - URL Manager
 * 
 * Handles URL synchronization for shareable filter states
 * Part of Phase 1 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 */

(function($) {
    'use strict';
    
    // URL management system
    window.AmpleFilter.urlManager = {
        
        // URL update settings
        config: {
            updateDelay: 500,    // ms delay before updating URL
            replaceState: true,  // Use replaceState vs pushState
            includeDefaults: false, // Include default values in URL
            maxUrlLength: 2000   // Maximum URL length
        },
        
        // Update timeout ID
        updateTimeout: null,
        
        /**
         * Initialize URL manager
         */
        init: function() {
            $(document).ready(() => {
                this.setupEventHandlers();
                this.loadInitialState();
                
                if (window.AmpleFilterConfig?.debug) {
                    // URL manager initialized
                }
            });
        },
        
        /**
         * Setup event handlers
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Listen for filter changes
            $(document).on('ample-filter:products-filtered', function(event, data) {
                self.scheduleURLUpdate();
            });
            
            // Listen for browser navigation
            $(window).on('popstate.ample-url', function(event) {
                self.handleBrowserNavigation(event);
            });
            
            // Listen for slider changes
            $(document).on('slider-changed', function(event, data) {
                self.scheduleURLUpdate();
            });
            
            // Cleanup on page unload
            $(window).on('beforeunload.ample-url', function() {
                if (self.updateTimeout) {
                    clearTimeout(self.updateTimeout);
                }
            });
        },
        
        /**
         * Load initial filter state from URL
         */
        loadInitialState: function() {
            const urlParams = this.parseURLParams();
            let hasFilters = false;
            
            if (Object.keys(urlParams).length === 0) {
                return; // No URL parameters to process
            }
            
            // Process THC filter
            if (urlParams.thc) {
                const thcRange = this.parseRangeParam(urlParams.thc);
                if (thcRange && this.isValidRange(thcRange, 'thc')) {
                    window.AmpleFilter.activeFilters.thc = thcRange;
                    this.setSliderFromURL('thc', thcRange);
                    hasFilters = true;
                }
            }
            
            // Process CBD filter
            if (urlParams.cbd) {
                const cbdRange = this.parseRangeParam(urlParams.cbd);
                if (cbdRange && this.isValidRange(cbdRange, 'cbd')) {
                    window.AmpleFilter.activeFilters.cbd = cbdRange;
                    this.setSliderFromURL('cbd', cbdRange);
                    hasFilters = true;
                }
            }
            
            // Process categories
            if (urlParams.categories) {
                const categories = urlParams.categories.split(',').filter(Boolean);
                if (categories.length > 0) {
                    window.AmpleFilter.activeFilters.categories = categories;
                    this.setCheckboxesFromURL('categories', categories);
                    hasFilters = true;
                }
            }
            
            // Process brands
            if (urlParams.brands) {
                const brands = urlParams.brands.split(',').filter(Boolean);
                if (brands.length > 0) {
                    window.AmpleFilter.activeFilters.brands = brands;
                    this.setCheckboxesFromURL('brands', brands);
                    hasFilters = true;
                }
            }
            
            // Process terpenes
            if (urlParams.terpenes) {
                const terpenes = urlParams.terpenes.split(',').filter(Boolean);
                if (terpenes.length > 0) {
                    window.AmpleFilter.activeFilters.terpenes = terpenes;
                    this.setCheckboxesFromURL('terpenes', terpenes);
                    hasFilters = true;
                }
            }
            
            // Process sizes
            if (urlParams.sizes) {
                const urlSafeSizes = urlParams.sizes.split(',').filter(Boolean);
                if (urlSafeSizes.length > 0) {
                    const sizes = urlSafeSizes.map(size => window.AmpleFilter.fromUrlSafe(size));
                    window.AmpleFilter.activeFilters.sizes = sizes;
                    this.setCheckboxesFromURL('sizes', sizes);
                    hasFilters = true;
                }
            }
            
            // Process dominance
            if (urlParams.dominance) {
                const urlSafeDominance = urlParams.dominance.split(',').filter(Boolean);
                if (urlSafeDominance.length > 0) {
                    const dominance = urlSafeDominance.map(strain => window.AmpleFilter.fromUrlSafe(strain));
                    window.AmpleFilter.activeFilters.dominance = dominance;
                    this.setCheckboxesFromURL('dominance', dominance);
                    hasFilters = true;
                }
            }
            
            // Process search
            if (urlParams.search) {
                window.AmpleFilter.activeFilters.search = decodeURIComponent(urlParams.search);
                $('.ample-search-input').val(window.AmpleFilter.activeFilters.search);
                hasFilters = true;
            }
            
            // Process price range
            if (urlParams.price) {
                const priceRange = this.parseRangeParam(urlParams.price);
                if (priceRange && this.isValidRange(priceRange, 'price')) {
                    window.AmpleFilter.activeFilters.price = priceRange;
                    hasFilters = true;
                }
            }
            
            // If we loaded filters from URL, trigger a filter update
            if (hasFilters) {
                // Wait for products to load before applying filters
                $(document).on('ample-filter:products-loaded', function() {
                    window.AmpleFilter.applyFilters();
                });
                
                // Trigger custom event
                $(document).trigger('ample-filter:url-loaded', [urlParams]);
            }
        },
        
        /**
         * Parse URL parameters into object
         */
        parseURLParams: function() {
            const params = {};
            const urlParams = new URLSearchParams(window.location.search);
            
            for (const [key, value] of urlParams) {
                params[key] = value;
            }
            
            return params;
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
         * Validate range values
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
                case 'price':
                    return min >= 0 && max <= 10000 && min <= max;
                default:
                    return true;
            }
        },
        
        /**
         * Set slider values from URL
         */
        setSliderFromURL: function(sliderType, range) {
            if (!window.AmpleFilter.sliderControls) return;
            
            const [min, max] = range;
            window.AmpleFilter.sliderControls.setSliderValues(sliderType, min, max);
        },
        
        /**
         * Set checkbox states from URL
         */
        setCheckboxesFromURL: function(filterType, values) {
            values.forEach(value => {
                let $checkbox;
                
                // Special handling for different filter types with their specific selectors
                if (filterType === 'terpenes') {
                    $checkbox = $(`.ample-terpenes-options input[type="checkbox"][data-terpene="${value}"]`);
                } else if (filterType === 'brands') {
                    $checkbox = $(`.ample-brand-options input[type="checkbox"][data-brand="${value}"]`);
                } else if (filterType === 'categories') {
                    $checkbox = $(`.ample-category-options input[type="checkbox"][data-category="${value}"]`);
                } else if (filterType === 'sizes') {
                    // Size checkboxes might use data-group attribute like the button text function
                    $checkbox = $(`.ample-size-options input[type="checkbox"][data-group="${value}"]`);
                    if (!$checkbox.length) {
                        // Fallback to value attribute
                        $checkbox = $(`.ample-size-options input[type="checkbox"][value="${value}"]`);
                    }
                } else if (filterType === 'dominance') {
                    // Dominance checkboxes use data-strain attribute like the button text function
                    $checkbox = $(`.ample-dominance-options input[type="checkbox"][data-strain="${value}"]`);
                    if (!$checkbox.length) {
                        // Fallback to value attribute
                        $checkbox = $(`.ample-dominance-options input[type="checkbox"][value="${value}"]`);
                    }
                } else {
                    // Fallback for other filter types
                    $checkbox = $(`.ample-${filterType}-checkbox[value="${value}"]`);
                }
                
                if ($checkbox.length) {
                    $checkbox.prop('checked', true);
                }
            });
        },
        
        /**
         * Schedule URL update with debouncing
         */
        scheduleURLUpdate: function() {
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
            }
            
            this.updateTimeout = setTimeout(() => {
                this.updateURL();
            }, this.config.updateDelay);
        },
        
        /**
         * Format number for URL (same as button display)
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
         * Update URL with current filter state
         */
        updateURL: function() {
            if (!window.AmpleFilter.activeFilters) return;
            
            const params = new URLSearchParams();
            const filters = window.AmpleFilter.activeFilters;
            
            // Add THC filter if not default
            if (!this.isDefaultRange(filters.thc, 'thc')) {
                params.set('thc', `${this.formatNumber(filters.thc[0])}-${this.formatNumber(filters.thc[1])}`);
            }
            
            // Add CBD filter if not default
            if (!this.isDefaultRange(filters.cbd, 'cbd')) {
                params.set('cbd', `${this.formatNumber(filters.cbd[0])}-${this.formatNumber(filters.cbd[1])}`);
            }
            
            // Add categories if any selected
            if (filters.categories && filters.categories.length > 0) {
                params.set('categories', filters.categories.join(','));
            }
            
            // Add brands if any selected
            if (filters.brands && filters.brands.length > 0) {
                params.set('brands', filters.brands.join(','));
            }
            
            // Add terpenes if any selected
            if (filters.terpenes && filters.terpenes.length > 0) {
                params.set('terpenes', filters.terpenes.join(','));
            }
            
            // Add search if not empty
            if (filters.search && filters.search.trim()) {
                params.set('search', encodeURIComponent(filters.search.trim()));
            }
            
            // Add price range if not default
            if (!this.isDefaultRange(filters.price, 'price')) {
                params.set('price', `${filters.price[0]}-${filters.price[1]}`);
            }
            
            // Add size filter if any selected (ABBA Issue #10 fix)
            if (filters.sizes && filters.sizes.length > 0) {
                const urlSafeSizes = filters.sizes.map(size => window.AmpleFilter.toUrlSafe(size));
                params.set('sizes', urlSafeSizes.join(','));
            }
            
            // Add dominance filter if any selected (ABBA Issue #10 fix)
            if (filters.dominance && filters.dominance.length > 0) {
                const urlSafeDominance = filters.dominance.map(strain => window.AmpleFilter.toUrlSafe(strain));
                params.set('dominance', urlSafeDominance.join(','));
            }
            
            // Add stock filter if changed from default
            if (!filters.inStock) {
                params.set('out_of_stock', '1');
            }
            
            // Build new URL
            const newURL = this.buildURL(params);
            
            // Check URL length
            if (newURL.length > this.config.maxUrlLength) {
                console.warn('URL too long, skipping update:', newURL.length);
                return;
            }
            
            // Update browser URL
            this.setBrowserURL(newURL);
            
            // Trigger URL updated event
            $(document).trigger('ample-filter:url-updated', [newURL, params]);
        },
        
        /**
         * Check if range is default value
         */
        isDefaultRange: function(range, filterType) {
            if (!range || !Array.isArray(range)) return true;
            
            const defaults = {
                thc: [0, 45],
                cbd: [0, 30],
                price: [0, 10000]
            };
            
            const defaultRange = defaults[filterType];
            if (!defaultRange) return true;
            
            return range[0] === defaultRange[0] && range[1] === defaultRange[1];
        },
        
        /**
         * Build complete URL from parameters
         */
        buildURL: function(params) {
            const baseURL = window.location.pathname;
            const paramString = params.toString();
            
            return paramString ? `${baseURL}?${paramString}` : baseURL;
        },
        
        /**
         * Set browser URL using appropriate method
         */
        setBrowserURL: function(newURL) {
            const currentURL = window.location.pathname + window.location.search;
            
            if (newURL === currentURL) return; // No change needed
            
            try {
                if (this.config.replaceState) {
                    history.replaceState(null, '', newURL);
                } else {
                    history.pushState(null, '', newURL);
                }
                
                if (window.AmpleFilterConfig?.debug) {
                    // URL updated
                }
            } catch (error) {
                console.warn('Failed to update URL:', error);
            }
        },
        
        /**
         * Handle browser navigation (back/forward)
         */
        handleBrowserNavigation: function(event) {
            // Prevent infinite loops
            if (this.updateTimeout) {
                clearTimeout(this.updateTimeout);
                this.updateTimeout = null;
            }
            
            // Load new state from URL
            this.loadInitialState();
            
            if (window.AmpleFilterConfig?.debug) {
                // Browser navigation handled
            }
        },
        
        /**
         * Get shareable URL for current filter state
         */
        getShareableURL: function() {
            const params = new URLSearchParams();
            const filters = window.AmpleFilter.activeFilters;
            
            // Always include all active filters for sharing
            if (filters.thc && !this.isDefaultRange(filters.thc, 'thc')) {
                params.set('thc', `${this.formatNumber(filters.thc[0])}-${this.formatNumber(filters.thc[1])}`);
            }
            
            if (filters.cbd && !this.isDefaultRange(filters.cbd, 'cbd')) {
                params.set('cbd', `${this.formatNumber(filters.cbd[0])}-${this.formatNumber(filters.cbd[1])}`);
            }
            
            if (filters.categories && filters.categories.length > 0) {
                params.set('categories', filters.categories.join(','));
            }
            
            if (filters.brands && filters.brands.length > 0) {
                params.set('brands', filters.brands.join(','));
            }
            
            if (filters.search && filters.search.trim()) {
                params.set('search', encodeURIComponent(filters.search.trim()));
            }
            
            // Build full URL for sharing
            const baseURL = window.location.origin + window.location.pathname;
            const paramString = params.toString();
            
            return paramString ? `${baseURL}?${paramString}` : baseURL;
        },
        
        /**
         * Copy current filter URL to clipboard
         */
        copyShareableURL: function() {
            const url = this.getShareableURL();
            
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(url).then(() => {
                    // URL copied to clipboard
                    return url;
                }).catch(error => {
                    console.warn('Failed to copy URL:', error);
                    return this.fallbackCopyToClipboard(url);
                });
            } else {
                return this.fallbackCopyToClipboard(url);
            }
        },
        
        /**
         * Fallback clipboard copy method
         */
        fallbackCopyToClipboard: function(text) {
            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    
                    if (successful) {
                        resolve(text);
                    } else {
                        reject(new Error('Copy command failed'));
                    }
                } catch (error) {
                    document.body.removeChild(textarea);
                    reject(error);
                }
            });
        },
        
        /**
         * Reset URL to default state (no parameters)
         */
        resetURL: function() {
            const baseURL = window.location.pathname;
            this.setBrowserURL(baseURL);
            
            $(document).trigger('ample-filter:url-reset');
        },
        
        /**
         * Get URL parameters as object
         */
        getCurrentParams: function() {
            return this.parseURLParams();
        },
        
        /**
         * Check if URL has filter parameters
         */
        hasFilterParams: function() {
            const params = this.parseURLParams();
            return Object.keys(params).some(key => 
                ['thc', 'cbd', 'categories', 'brands', 'search', 'price', 'out_of_stock'].includes(key)
            );
        }
    };
    
    // Auto-initialize URL manager
    window.AmpleFilter.urlManager.init();
    
    // Add public methods to main AmpleFilter object
    window.AmpleFilter.getShareableURL = function() {
        return window.AmpleFilter.urlManager.getShareableURL();
    };
    
    window.AmpleFilter.copyShareableURL = function() {
        return window.AmpleFilter.urlManager.copyShareableURL();
    };
    
})(jQuery);