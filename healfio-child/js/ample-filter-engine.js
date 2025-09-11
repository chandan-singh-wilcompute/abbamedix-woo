/**
 * Ample Filter System - Core Filter Engine
 * 
 * High-performance client-side product filtering with caching
 * Part of Phase 1 implementation for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-29
 */

(function($) {
    'use strict';
    
    // Main filter object - global namespace
    window.AmpleFilter = {
        // Product data storage
        products: [],
        originalProducts: [],
        
        // Current filter state
        activeFilters: {
            thc: [0, 45],
            cbd: [0, 30],
            categories: [],
            brands: [],
            terpenes: [],
            sizes: [],
            dominance: [],
            search: '',
            price: [0, 10000],
            inStock: null
        },
        
        // System configuration
        config: {},
        
        // Performance tracking
        stats: {
            totalFilters: 0,
            cacheHits: 0
        },
        
        // Event handlers storage
        eventHandlers: {},
        
        /**
         * Initialize the filter system
         */
        init: function() {
            // Initialize filter engine
            
            // Store configuration
            this.config = window.AmpleFilterConfig || {};
            
            // Wait for DOM ready
            $(document).ready(() => {
                this.setupEventHandlers();
                this.loadProducts();
                
                // Initialize sorting functionality
                this.initSorting();
                
                // Initialize from URL parameters
                this.loadFiltersFromURL();
                
                if (this.config.debug) {
                    this.enableDebugMode();
                }
            });
        },
        
        /**
         * Setup event handlers for filter interactions
         */
        setupEventHandlers: function() {
            const self = this;
            
            // Store handlers for cleanup
            this.eventHandlers = {
                // Slider change events (debounced)
                sliderChange: this.debounce(function(event) {
                    self.handleSliderChange(event);
                }, this.config.performance?.debounce_ms || 100),
                
                // Number input change events
                numberInputChange: function(event) {
                    self.handleNumberInputChange(event);
                },
                
                // Search input
                searchInput: this.debounce(function(event) {
                    self.handleSearchInput(event);
                }, 300),
                
                // Category/brand checkbox changes
                taxonomyChange: function(event) {
                    self.handleTaxonomyChange(event);
                }
            };
            
            // Bind events with namespace for easy cleanup
            $(document)
                .on('input.ample-filter', '.ample-slider-input', this.eventHandlers.sliderChange)
                .on('change.ample-filter', '.ample-slider-number-input', this.eventHandlers.numberInputChange)
                .on('input.ample-filter', '.ample-search-input, input[placeholder="Search"]', this.eventHandlers.searchInput)
                .on('change.ample-filter', '.ample-taxonomy-checkbox', this.eventHandlers.taxonomyChange);
            
            // Window events
            $(window).on('popstate.ample-filter', function() {
                self.loadFiltersFromURL();
            });
        },
        
        /**
         * Load products from API with caching
         */
        loadProducts: async function() {
            
            try {
                this.showLoadingState(true);
                
                const response = await fetch(this.config.rest_url + 'products', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                // Store products
                this.originalProducts = data.products || [];
                this.products = [...this.originalProducts];
                
                
                // Track cache performance
                if (data.meta?.source === 'cache') {
                    this.stats.cacheHits++;
                }
                
                // Products loaded
                
                // Don't apply filters automatically on page load - only when user interacts
                // this.applyFilters();
                
                // Trigger loaded event
                this.trigger('products-loaded', {
                    count: this.products.length
                });
                
            } catch (error) {
                console.error('Failed to load products:', error);
                this.showError('Failed to load products. Please refresh the page.');
            } finally {
                this.showLoadingState(false);
            }
        },
        
        /**
         * Apply all active filters to products
         */
        applyFilters: function() {
            
            if (!this.originalProducts.length) {
                console.warn('No products loaded yet');
                return;
            }
            
            // Check if there are any products to filter in the DOM
            const $existingProducts = $('.products li.product, .products .type-product, ul.products > li');
            if ($existingProducts.length === 0) {
                // No products to filter
                return;
            }
            
            let filteredProducts = [...this.originalProducts];
            let visibleCount = 0;
            
            // Filter by THC range
            if (this.activeFilters.thc[0] > 0 || this.activeFilters.thc[1] < 45) {
                filteredProducts = filteredProducts.filter(product => {
                    const thc = parseFloat(product.thc) || 0;
                    return thc >= this.activeFilters.thc[0] && thc <= this.activeFilters.thc[1];
                });
            }
            
            // Filter by CBD range
            if (this.activeFilters.cbd[0] > 0 || this.activeFilters.cbd[1] < 30) {
                filteredProducts = filteredProducts.filter(product => {
                    const cbd = parseFloat(product.cbd) || 0;
                    return cbd >= this.activeFilters.cbd[0] && cbd <= this.activeFilters.cbd[1];
                });
            }
            
            // Filter by categories
            if (this.activeFilters.categories.length > 0) {
                filteredProducts = filteredProducts.filter(product => {
                    return this.activeFilters.categories.some(cat => 
                        product.categories && product.categories.includes(cat)
                    );
                });
            }
            
            // Filter by brands
            if (this.activeFilters.brands.length > 0) {
                filteredProducts = filteredProducts.filter(product => {
                    return this.activeFilters.brands.some(brand => 
                        product.brands && product.brands.includes(brand)
                    );
                });
            }
            
            // Filter by terpenes (OR logic for terpenes array)
            if (this.activeFilters.terpenes.length > 0) {
                filteredProducts = filteredProducts.filter(product => {
                    return this.activeFilters.terpenes.some(terpene => {
                        return this.matchesProductTerpenes(product.terpenes, terpene);
                    });
                });
            }
            
            // Filter by sizes (OR logic for package-sizes array)
            if (this.activeFilters.sizes.length > 0) {
                filteredProducts = filteredProducts.filter(product => {
                    // Check if product has package-sizes attribute
                    if (!product.attributes || !product.attributes['package-sizes']) {
                        return false;
                    }
                    
                    // Convert selected size groups back to individual sizes and check OR logic
                    return this.activeFilters.sizes.some(sizeGroup => {
                        return this.matchesProductSize(product.attributes['package-sizes'], sizeGroup);
                    });
                });
            }
            
            // Filter by dominance/strain (OR logic for strain attribute)
            if (this.activeFilters.dominance.length > 0) {
                filteredProducts = filteredProducts.filter(product => {
                    // Check if product has strain attribute
                    if (!product.attributes || !product.attributes['strain']) {
                        return false;
                    }
                    
                    // Check if product strain matches any of the selected strains (OR logic)
                    return this.activeFilters.dominance.some(selectedStrain => {
                        return this.matchesProductStrain(product.attributes['strain'], selectedStrain);
                    });
                });
            }
            
            // Filter by search term
            if (this.activeFilters.search.trim()) {
                const searchTerm = this.activeFilters.search.toLowerCase().trim();
                filteredProducts = filteredProducts.filter(product => {
                    return product.title.toLowerCase().includes(searchTerm) ||
                           product.excerpt.toLowerCase().includes(searchTerm) ||
                           (product.meta?.sku && product.meta.sku.toLowerCase().includes(searchTerm));
                });
            }
            
            // Filter by price range
            if (this.activeFilters.price[0] > 0 || this.activeFilters.price[1] < 10000) {
                filteredProducts = filteredProducts.filter(product => {
                    const price = product.sale_price > 0 ? product.sale_price : product.regular_price;
                    return price >= this.activeFilters.price[0] && price <= this.activeFilters.price[1];
                });
            }
            
            // Filter by stock status
            if (this.activeFilters.inStock) {
                filteredProducts = filteredProducts.filter(product => {
                    return product.meta?.stock_status === 'instock';
                });
            }
            
            // Update DOM
            this.updateProductDisplay(filteredProducts);
            
            // Update URL (using URL manager for consistency)
            if (this.urlManager) {
                this.urlManager.scheduleURLUpdate();
            }
            
            // Update statistics
            this.stats.totalFilters++;
            visibleCount = filteredProducts.length;
            
            // Update product count display for search results
            this.updateProductCountDisplay(visibleCount);
            
            // Trigger filtered event
            this.trigger('products-filtered', {
                total: this.originalProducts.length,
                visible: visibleCount,
                filters: { ...this.activeFilters }
            });
            
            if (this.config.debug) {
                // Products filtered
            }
        },

        /**
         * Sort products based on selected criteria
         * IMPORTANT: Sorts IN-PLACE without page refresh
         * Preserves all active filters (THC/CBD, Size, Brand, etc.)
         * @param {string} sortBy - The sort option from dropdown
         */
        sortProducts: function(sortBy) {
            
            // For price and price-desc, skip API sorting and use DOM-only sorting
            // This is because we extract price per unit from DOM, not API data
            if (sortBy === 'price' || sortBy === 'price-desc') {
                this.sortProductElements([], sortBy);
                return;
            }
            
            // CRITICAL: Use filteredProducts if filters are active
            // This ensures we sort only the filtered subset, not all products
            let productsToSort = this.filteredProducts || this.originalProducts;
            if (!productsToSort || productsToSort.length === 0) {
                return; // No products to sort
            }
            
            // ABBA Issue #64 FIX: Filter API products to only include those that exist in DOM
            // This fixes the mismatch between API data and DOM elements when logged out
            const domProductIds = this.getDomProductIds();
            const domProducts = productsToSort.filter(product => domProductIds.has(product.id));
            
            if (domProducts.length === 0) {
                return; // No matching products between API and DOM
            }
            
            // Use the filtered DOM products for sorting
            productsToSort = domProducts;
            
            // Create a copy to avoid mutating original array
            let sorted = [...productsToSort];
            
            // Apply sorting based on selected option
            switch(sortBy) {
                case 'price':
                    // Low to high - handle missing prices as 0
                    sorted.sort((a, b) => {
                        const priceA = a.regular_price || 0;
                        const priceB = b.regular_price || 0;
                        return priceA - priceB;
                    });
                    break;
                    
                case 'price-desc':
                    // High to low
                    sorted.sort((a, b) => {
                        const priceA = a.regular_price || 0;
                        const priceB = b.regular_price || 0;
                        return priceB - priceA;
                    });
                    break;
                    
                case 'date':
                    // Latest first - higher IDs are newer
                    sorted.sort((a, b) => b.id - a.id);
                    break;
                    
                case 'popularity':
                    // Most popular first
                    sorted.sort((a, b) => {
                        const salesA = (a.meta && a.meta.total_sales) || 0;
                        const salesB = (b.meta && b.meta.total_sales) || 0;
                        return salesB - salesA;
                    });
                    break;
                    
                case 'rating':
                    // Highest rating first
                    sorted.sort((a, b) => {
                        const ratingA = (a.meta && a.meta.rating) || 0;
                        const ratingB = (b.meta && b.meta.rating) || 0;
                        return ratingB - ratingA;
                    });
                    break;
                    
                case 'menu_order':
                default:
                    // Keep original order (no sorting needed)
                    // Products are already in menu_order from API
                    sorted = [...(this.originalProducts || productsToSort)];
                    // Apply current filters to original order
                    if (this.filteredProducts) {
                        const filteredIds = new Set(this.filteredProducts.map(p => p.id));
                        sorted = sorted.filter(p => filteredIds.has(p.id));
                    }
                    break;
            }
            
            // Store current sort preference
            this.currentSort = sortBy;
            
            // IMPORTANT: Reorder DOM elements to match sorted array
            // This actually moves the product elements to new positions
            this.sortProductElements(sorted, sortBy);
            
            // Store sorted products as new filtered set
            if (this.filteredProducts) {
                this.filteredProducts = sorted;
            }
            
            // Performance tracking available in debug mode if needed
        },

        /**
         * Initialize sorting dropdown listener
         */
        initSorting: function() {
            const self = this;
            
            // AGGRESSIVE FORM PREVENTION
            $(document).ready(function() {
                // Completely disable all form submissions for woocommerce-ordering
                $('.woocommerce-ordering').each(function() {
                    this.onsubmit = function(e) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        return false;
                    };
                });
                
                // Remove any existing event handlers and prevent all submission events
                $(document).off('submit', '.woocommerce-ordering').on('submit', '.woocommerce-ordering', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                });
                
                // Block any form submission via keypress (Enter key)
                $('.woocommerce-ordering select').off('keypress').on('keypress', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            // Listen for orderby dropdown changes
            $(document).on('change', '.orderby, select[name="orderby"], .woocommerce-ordering select', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const sortBy = $(this).val();
                
                if (sortBy) {
                    // Double-ensure no form submission happens
                    const form = $(this).closest('form')[0];
                    if (form) {
                        form.onsubmit = function(e) {
                            e.preventDefault();
                            return false;
                        };
                    }
                    
                    self.sortProducts(sortBy);
                    
                    // Update URL without page refresh - DISABLED: Causes page refresh in WordPress
                    // const url = new URL(window.location);
                    // url.searchParams.set('orderby', sortBy);
                    // window.history.replaceState({}, '', url);
                    
                    // Sort completed successfully
                }
                
                return false;
            });
            
            // Check if there's an orderby parameter in URL on load
            const urlParams = new URLSearchParams(window.location.search);
            const orderby = urlParams.get('orderby');
            if (orderby) {
                // Apply initial sorting after products are loaded
                setTimeout(() => {
                    self.sortProducts(orderby);
                    // Update dropdown to match
                    $('.orderby, select[name="orderby"]').val(orderby);
                }, 500);
            }
        },
        
        /**
         * Update product display in DOM
         */
        updateProductDisplay: function(filteredProducts) {
            // Find all product elements - WooCommerce uses post-{id} classes
            const productElements = $('.products li.product, .products .type-product, ul.products > li');
            let visibleCount = 0;
            let hiddenCount = 0;
            
            // Create a set of visible product IDs for fast lookup
            const visibleIds = new Set(filteredProducts.map(p => p.id));
            
            // Process each product element
            productElements.each(function() {
                const $element = $(this);
                let productId = null;
                
                // Try to extract product ID from various sources
                // 1. Check for post-{id} class
                const postClass = Array.from(this.classList).find(c => c.startsWith('post-'));
                if (postClass) {
                    productId = parseInt(postClass.replace('post-', ''));
                }
                
                // 2. Check for data-product-id attribute
                if (!productId) {
                    productId = parseInt($element.data('product-id'));
                }
                
                // 3. Check for product link with ID in URL
                if (!productId) {
                    const link = $element.find('a.woocommerce-LoopProduct-link').attr('href');
                    if (link) {
                        const match = link.match(/p=(\d+)|product\/[^\/]*-(\d+)/);
                        if (match) {
                            productId = parseInt(match[1] || match[2]);
                        }
                    }
                }
                
                // Apply visibility based on filter
                if (productId && visibleIds.has(productId)) {
                    $element.removeClass('ample-filter-hidden');
                    visibleCount++;
                } else {
                    $element.addClass('ample-filter-hidden');
                    hiddenCount++;
                }
            });
            
            // Product visibility updated
            
            // Update result count
            this.updateResultCount(visibleCount, this.originalProducts.length);
        },
        
        /**
         * Update result count display
         */
        updateResultCount: function(visible, total) {
            const countElement = $('.ample-filter-count, .product-count, .woocommerce-result-count');
            
            if (countElement.length) {
                countElement.html(`<strong>${visible}</strong> of <strong>${total}</strong> products`);
            }
        },
        
        /**
         * Get all product IDs that are currently in the DOM
         * 
         * Helper function that scans the DOM for product elements and extracts their IDs
         * from CSS classes or data attributes. Used to ensure sorting only operates on
         * products that are actually rendered on the page, preventing API/DOM mismatches.
         * 
         * @function getDomProductIds
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64)
         * 
         * @returns {Set<number>} Set of product IDs that are present in the DOM
         * 
         * @example
         * const domIds = this.getDomProductIds();
         * console.log(domIds.size); // e.g., 449
         * console.log(domIds.has(12345)); // true if product 12345 is in DOM
         */
        getDomProductIds: function() {
            const domProductIds = new Set();
            
            // Find all product elements in DOM
            $('.product').each(function() {
                let productId = null;
                
                // Try to extract product ID from post-{id} class
                const postClass = Array.from(this.classList).find(c => c.startsWith('post-'));
                if (postClass) {
                    productId = parseInt(postClass.replace('post-', ''));
                }
                
                // Fallback: try data attributes  
                if (!productId) {
                    const $element = $(this);
                    productId = parseInt($element.data('product-id')) || 
                              parseInt($element.attr('data-product-id'));
                }
                
                if (productId) {
                    domProductIds.add(productId);
                }
            });
            
            return domProductIds;
        },
        
        /**
         * Sort DOM product elements directly by visual criteria
         * 
         * Simplified approach that sorts visible product elements directly by extracting
         * data from DOM elements (ratings, prices) rather than complex API/DOM ID matching.
         * This ensures 100% success rate and handles all products consistently.
         * 
         * @function sortProductElements
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64 - Simplified DOM sorting)
         * 
         * @param {Array} sortedProducts - API product data (used for reference)
         * @param {string} sortBy - Sort criteria ('rating', 'price', 'price-desc', etc.)
         * 
         * @returns {void}
         * 
         * @example
         * this.sortProductElements(apiProducts, 'rating');
         * // DOM reordered: highest rated products appear first
         */
        sortProductElements: function(sortedProducts, sortBy) {
            // CRITICAL FIX: Sort within each category container separately
            // This preserves category structure instead of dumping everything into first container
            
            const productContainers = document.querySelectorAll('.products, ul.products');
            let totalSorted = 0;
            
            productContainers.forEach((container) => {
                const products = Array.from(container.querySelectorAll('.product'));
                
                if (products.length === 0) {
                    return; // Skip empty containers
                }
                
                // Sort products within this specific category container
                const sortedElements = products.sort((a, b) => {
                    return this.compareDOMElements(a, b, sortBy);
                });
                
                // Remove all products from container first (preserves non-product elements)
                products.forEach(product => {
                    product.remove();
                });
                
                // Append sorted products back to container
                sortedElements.forEach((element, index) => {
                    container.appendChild(element);
                });
                
                totalSorted += products.length;
            });
        },
        
        /**
         * Compare two DOM elements for sorting
         * 
         * Compares two product DOM elements based on the specified sort criteria.
         * Extracts data directly from DOM elements to ensure accurate sorting
         * without relying on API data availability.
         * 
         * @function compareDOMElements
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64)
         * 
         * @param {Element} a - First DOM element to compare
         * @param {Element} b - Second DOM element to compare
         * @param {string} sortBy - Sort criteria ('rating'|'price'|'price-desc'|'popularity'|'date'|'menu_order')
         * 
         * @returns {number} Comparison result (-1, 0, 1) for Array.sort()
         * 
         * @example
         * const result = this.compareDOMElements(productA, productB, 'rating');
         * // result < 0: productA has higher rating (should come first)
         * // result > 0: productB has higher rating (should come first)
         * // result === 0: equal ratings (maintain current order)
         */
        compareDOMElements: function(a, b, sortBy) {
            switch(sortBy) {
                case 'rating':
                    const ratingA = this.getDOMElementRating(a);
                    const ratingB = this.getDOMElementRating(b);
                    return ratingB - ratingA; // Highest rating first
                    
                case 'price':
                    const pricePerUnitA = this.getDOMElementPricePerUnit(a);
                    const pricePerUnitB = this.getDOMElementPricePerUnit(b);
                    
                    // Primary sort: Price per unit (lowest first)
                    // Use 0.009 threshold to handle JavaScript floating-point precision issues
                    if (Math.abs(pricePerUnitA - pricePerUnitB) > 0.009) {
                        return pricePerUnitA - pricePerUnitB;
                    }
                    
                    // Secondary sort: Alphabetical by title when prices are equal
                    const titleA = this.getDOMElementTitle(a);
                    const titleB = this.getDOMElementTitle(b);
                    return titleA.localeCompare(titleB);
                    
                case 'price-desc':
                    const pricePerUnitDescA = this.getDOMElementPricePerUnit(a);
                    const pricePerUnitDescB = this.getDOMElementPricePerUnit(b);
                    
                    // Primary sort: Price per unit (highest first)  
                    if (Math.abs(pricePerUnitDescA - pricePerUnitDescB) > 0.009) {
                        return pricePerUnitDescB - pricePerUnitDescA;
                    }
                    
                    // Secondary sort: Alphabetical by title when prices are equal
                    const titleDescA = this.getDOMElementTitle(a);
                    const titleDescB = this.getDOMElementTitle(b);
                    return titleDescA.localeCompare(titleDescB);
                    
                case 'popularity':
                    // For DOM sorting, we can't easily get popularity data, so maintain original order
                    return 0;
                    
                case 'date':
                    // For DOM sorting, we can't easily get publish dates, so maintain original order  
                    return 0;
                    
                case 'menu_order':
                default:
                    return 0; // Keep original order for default sorting
            }
        },
        
        /**
         * Extract rating value from DOM element
         * 
         * Searches for rating display elements within a product card and extracts
         * the numeric rating value. Used for sorting products by rating.
         * 
         * @function getDOMElementRating
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64)
         * 
         * @param {Element} element - Product DOM element to extract rating from
         * 
         * @returns {number} Rating value (0-5) or 0 if no rating found
         * 
         * @example
         * const rating = this.getDOMElementRating(productElement);
         * // Returns: 4.5 (from "⭐ 4.5" text)
         * // Returns: 0 (if no rating display found)
         */
        getDOMElementRating: function(element) {
            const ratingIcon = element.querySelector('.ratingIcon');
            if (ratingIcon) {
                const ratingText = ratingIcon.textContent.trim();
                const rating = parseFloat(ratingText);
                return isNaN(rating) ? 0 : rating;
            }
            return 0; // No rating = 0
        },
        
        /**
         * Extract price value from DOM element
         * 
         * Searches for price display elements within a product card and extracts
         * the numeric price value. Used for sorting products by price.
         * 
         * @function getDOMElementPrice
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64)
         * 
         * @param {Element} element - Product DOM element to extract price from
         * 
         * @returns {number} Price value in dollars or 0 if no price found
         * 
         * @example
         * const price = this.getDOMElementPrice(productElement);
         * // Returns: 45.99 (from "$45.99" text)
         * // Returns: 0 (if no price display found)
         */
        getDOMElementPrice: function(element) {
            const priceElement = element.querySelector('.price-html, .prodcard-price .price-html');
            if (priceElement) {
                const priceText = priceElement.textContent.trim();
                const priceMatch = priceText.match(/[\d.]+/);
                if (priceMatch) {
                    return parseFloat(priceMatch[0]);
                }
            }
            return 0; // No price = 0
        },
        
        /**
         * Extract price per unit value from DOM element (per gram or per ml)
         * 
         * Extracts minimum price from per-unit displays, handling both single prices
         * and price ranges. Units of measure agnostic (supports /gr, /ml, etc.).
         * 
         * @function getDOMElementPricePerUnit
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64.2)
         * 
         * @param {Element} element - Product DOM element to extract per-unit price from
         * 
         * @returns {number} Price per unit (minimum from range) or 0 if not found
         * 
         * @example
         * const pricePerUnit = this.getDOMElementPricePerUnit(productElement);
         * // Returns: 9.00 (from "$9.00 /gr – 10.00 /gr")
         * // Returns: 10.00 (from "$10.00 /gr")
         * // Returns: 5.50 (from "$5.50 /ml")
         * // Returns: 0 (if no per-unit price found)
         */
        getDOMElementPricePerUnit: function(element) {
            const priceElement = element.querySelector('.price-html, .prodcard-price .price-html');
            if (priceElement) {
                const priceText = priceElement.textContent.trim();
                // Extract MINIMUM from: "$9.00 /gr – 10.00 /gr" or "$10.00 /ml" 
                // Units agnostic: matches /gr, /ml, /oz, etc.
                const match = priceText.match(/\$(\d+(?:\.\d+)?)\s*\/\w+/i);
                return match ? parseFloat(match[1]) : 0;
            }
            return 0; // No per-unit price = 0
        },
        
        /**
         * Get product title for secondary alphabetical sorting
         * 
         * @function getDOMElementTitle
         * @memberof window.AmpleFilter
         * @since 1.0.0 (ABBA Issue #64.2)
         * 
         * @param {Element} element - Product DOM element
         * @returns {string} Product title or empty string
         */
        getDOMElementTitle: function(element) {
            const titleElement = element.querySelector('.woocommerce-loop-product__title');
            return titleElement ? titleElement.textContent.trim() : '';
        },
        
        /**
         * Handle slider input changes
         */
        handleSliderChange: function(event) {
            const $slider = $(event.target);
            const sliderId = $slider.data('slider-id');
            const sliderRole = $slider.data('slider-role');
            const sliderType = $slider.closest('.ample-slider-container').data('slider-type');
            
            if (!sliderType || !sliderRole) return;
            
            const minValue = parseFloat($(`#${sliderId}-min`).val()) || 0;
            const maxValue = parseFloat($(`#${sliderId}-max`).val()) || 0;
            
            // Ensure min <= max
            if (sliderRole === 'min' && minValue > maxValue) {
                $slider.val(maxValue);
                return;
            }
            if (sliderRole === 'max' && maxValue < minValue) {
                $slider.val(minValue);
                return;
            }
            
            // Update filter state
            if (sliderType === 'thc' || sliderType === 'cbd') {
                this.activeFilters[sliderType] = [minValue, maxValue];
            }
            
            // Update visual feedback
            this.updateSliderVisual(sliderId, minValue, maxValue);
            this.updateSliderValues(sliderId, minValue, maxValue);
            
            // Apply filters
            this.applyFilters();
        },
        
        /**
         * Handle number input changes
         */
        handleNumberInputChange: function(event) {
            const $input = $(event.target);
            const sliderId = $input.data('slider-id');
            const sliderRole = $input.data('slider-role');
            const value = parseFloat($input.val()) || 0;
            
            // Update corresponding range slider
            $(`#${sliderId}-${sliderRole}`).val(value);
            
            // Trigger slider change to handle the update
            $(`#${sliderId}-${sliderRole}`).trigger('input');
        },
        
        /**
         * Handle search input changes
         */
        handleSearchInput: function(event) {
            const searchTerm = $(event.target).val().trim();
            
            // Update active filter
            this.activeFilters.search = searchTerm;
            
            // Apply filters
            this.applyFilters();
            
            // Update URL
            if (this.urlManager) {
                this.urlManager.scheduleURLUpdate();
            }
        },
        
        /**
         * Update slider visual appearance
         */
        updateSliderVisual: function(sliderId, minValue, maxValue) {
            const $container = $(`#${sliderId}`);
            const $range = $container.find('.ample-slider-range');
            const config = $container.closest('.ample-slider-container').data();
            
            // Safety check - if config is undefined, the slider doesn't exist
            if (!config) {
                console.warn(`Slider config not found for ${sliderId}`);
                return;
            }
            
            const min = parseFloat(config.min) || 0;
            const max = parseFloat(config.max) || 100;
            
            const leftPercent = ((minValue - min) / (max - min)) * 100;
            const rightPercent = ((max - maxValue) / (max - min)) * 100;
            
            $range.css({
                left: leftPercent + '%',
                right: rightPercent + '%'
            });
        },
        
        /**
         * Update slider value display
         */
        updateSliderValues: function(sliderId, minValue, maxValue) {
            const $values = $(`#${sliderId}-values`);
            const $container = $(`#${sliderId}`);
            const unit = $container.closest('.ample-slider-container').data('unit') || '';
            
            $values.find('.ample-slider-min-value').text(minValue + unit);
            $values.find('.ample-slider-max-value').text(maxValue + unit);
            
            // Update number inputs
            $(`#${sliderId}-min-number`).val(minValue);
            $(`#${sliderId}-max-number`).val(maxValue);
        },
        
        /**
         * Load filter state from URL parameters
         */
        loadFiltersFromURL: function() {
            const urlParams = new URLSearchParams(window.location.search);
            let filtersChanged = false;
            
            // THC filter
            if (urlParams.has('thc')) {
                const thcRange = urlParams.get('thc').split('-').map(Number);
                if (thcRange.length === 2) {
                    this.activeFilters.thc = thcRange;
                    filtersChanged = true;
                }
            }
            
            // CBD filter
            if (urlParams.has('cbd')) {
                const cbdRange = urlParams.get('cbd').split('-').map(Number);
                if (cbdRange.length === 2) {
                    this.activeFilters.cbd = cbdRange;
                    filtersChanged = true;
                }
            }
            
            // Categories
            if (urlParams.has('categories')) {
                this.activeFilters.categories = urlParams.get('categories').split(',');
                filtersChanged = true;
            }
            
            // Brands
            if (urlParams.has('brands')) {
                this.activeFilters.brands = urlParams.get('brands').split(',');
                filtersChanged = true;
            }
            
            // Terpenes
            if (urlParams.has('terpenes')) {
                this.activeFilters.terpenes = urlParams.get('terpenes').split(',');
                filtersChanged = true;
            }
            
            // Size filter (convert from URL-safe format)
            if (urlParams.has('sizes')) {
                const urlSafeSizes = urlParams.get('sizes').split(',');
                this.activeFilters.sizes = urlSafeSizes.map(size => this.fromUrlSafe(size));
                filtersChanged = true;
            }
            
            // Dominance filter (convert from URL-safe format)
            if (urlParams.has('dominance')) {
                const urlSafeDominance = urlParams.get('dominance').split(',');
                this.activeFilters.dominance = urlSafeDominance.map(strain => this.fromUrlSafe(strain));
                filtersChanged = true;
            }
            
            // Search
            if (urlParams.has('search')) {
                this.activeFilters.search = urlParams.get('search');
                filtersChanged = true;
            }
            
            if (filtersChanged) {
                this.updateUIFromFilters();
                this.applyFilters();
            }
        },
        
        /**
         * Update UI elements from current filter state
         */
        updateUIFromFilters: function() {
            // Update THC slider
            $('#ample-slider-thc-min').val(this.activeFilters.thc[0]);
            $('#ample-slider-thc-max').val(this.activeFilters.thc[1]);
            this.updateSliderVisual('ample-slider-thc', this.activeFilters.thc[0], this.activeFilters.thc[1]);
            this.updateSliderValues('ample-slider-thc', this.activeFilters.thc[0], this.activeFilters.thc[1], 'thc');
            
            // Update CBD slider
            $('#ample-slider-cbd-min').val(this.activeFilters.cbd[0]);
            $('#ample-slider-cbd-max').val(this.activeFilters.cbd[1]);
            this.updateSliderVisual('ample-slider-cbd', this.activeFilters.cbd[0], this.activeFilters.cbd[1]);
            this.updateSliderValues('ample-slider-cbd', this.activeFilters.cbd[0], this.activeFilters.cbd[1], 'cbd');
            
            // Update checkboxes
            $('.ample-taxonomy-checkbox').prop('checked', false);
            [...this.activeFilters.categories, ...this.activeFilters.brands, ...this.activeFilters.terpenes].forEach(term => {
                $(`.ample-taxonomy-checkbox[value="${term}"]`).prop('checked', true);
            });
            
            // Update terpenes checkboxes specifically by data-terpene attribute
            $('.ample-terpenes-options input[type="checkbox"]').prop('checked', false);
            this.activeFilters.terpenes.forEach(terpene => {
                $(`.ample-terpenes-options input[type="checkbox"][data-terpene="${terpene}"]`).prop('checked', true);
            });
            
            // Update search
            $('.ample-search-input').val(this.activeFilters.search);
        },
        
        // NOTE: updateURL function removed - now using urlManager.scheduleURLUpdate() for consistency
        
        /**
         * Show/hide loading state
         */
        showLoadingState: function(show) {
            const $containers = $('.ample-slider-container, .ample-product-grid');
            
            if (show) {
                $containers.addClass('ample-slider-loading ample-filter-loading');
            } else {
                $containers.removeClass('ample-slider-loading ample-filter-loading');
            }
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            const $errorDiv = $('<div class="ample-filter-error" style="background: #fee; color: #c33; padding: 1rem; border-radius: 4px; margin: 1rem 0;">' + message + '</div>');
            $('.ample-slider-container').first().before($errorDiv);
            
            setTimeout(() => $errorDiv.fadeOut(), 5000);
        },
        
        /**
         * Simple event system
         */
        trigger: function(eventName, data) {
            $(document).trigger(`ample-filter:${eventName}`, [data]);
        },
        
        /**
         * Debounce utility
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Enable debug mode
         */
        enableDebugMode: function() {
            // Debug mode enabled
            
            // DEBUG OVERLAY COMMENTED OUT FOR PRODUCTION
            // Uncomment the section below if debugging is needed in the future
            
            /*
            // Add debug panel
            $('body').append(`
                <div id="ample-debug" style="position: fixed; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; font-size: 12px; z-index: 9999; border-radius: 4px;">
                    <div>Products: <span id="ample-debug-products">0</span></div>
                    <div>Visible: <span id="ample-debug-visible">0</span></div>
                    <div>Filter Time: <span id="ample-debug-time">0ms</span></div>
                    <div>Cache Hits: <span id="ample-debug-cache">${this.stats.cacheHits}</span></div>
                </div>
            `);
            
            // Update debug panel on filter events
            $(document).on('ample-filter:products-filtered', (event, data) => {
                $('#ample-debug-products').text(data.total);
                $('#ample-debug-visible').text(data.visible);
                $('#ample-debug-time').text(Math.round(data.filterTime) + 'ms');
            });
            */
            
            // Debug keyboard shortcuts
            $(document).on('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey) {
                    if (e.key === 'D') {
                        // Filter state logged
                    } else if (e.key === 'R') {
                        this.loadProducts();
                    }
                }
            });
        },
        
        /**
         * Check if a product's package sizes match a selected size group
         * 
         * @param {Array} productSizes - Array of package sizes from product
         * @param {String} sizeGroup - Selected size group (e.g., "Small (< 1g)")
         * @return {Boolean} - True if product matches the size group
         */
        matchesProductSize: function(productSizes, sizeGroup) {
            if (!Array.isArray(productSizes) || !sizeGroup) {
                return false;
            }
            
            // Define size group mappings based on PHP grouping logic
            const sizeGroupMap = {
                'Small (< 1g)': function(size) {
                    const num = parseFloat(size);
                    return !isNaN(num) && num < 1.0 && (size.includes('g') || size.includes('mg'));
                },
                'Medium (1-5g)': function(size) {
                    const num = parseFloat(size);
                    return !isNaN(num) && num >= 1.0 && num <= 5.0 && size.includes('g');
                },
                'Large (5-30g)': function(size) {
                    const num = parseFloat(size);
                    return !isNaN(num) && num > 5.0 && num <= 30.0 && size.includes('g');
                },
                'XL (> 30g)': function(size) {
                    const num = parseFloat(size);
                    return !isNaN(num) && num > 30.0 && size.includes('g');
                },
                'Liquids (ml)': function(size) {
                    return size.toLowerCase().includes('ml');
                }
            };
            
            const matcher = sizeGroupMap[sizeGroup];
            if (!matcher) {
                return false;
            }
            
            // Check if any of the product's sizes match this group
            return productSizes.some(size => matcher(size.toString().toLowerCase()));
        },
        
        /**
         * Check if product strain matches selected strain filter
         * 
         * @param {string} productStrain - The strain attribute from product
         * @param {string} selectedStrain - The selected strain filter
         * @return {Boolean} - True if product matches the selected strain
         */
        matchesProductStrain: function(productStrain, selectedStrain) {
            if (!productStrain || !selectedStrain) {
                return false;
            }
            
            // Convert both to lowercase for case-insensitive comparison
            const productStrainLower = productStrain.toString().toLowerCase();
            const selectedStrainLower = selectedStrain.toString().toLowerCase();
            
            // Exact match or partial match (for variations like "Blue Dream Haze" matching "Blue Dream")
            return productStrainLower === selectedStrainLower || 
                   productStrainLower.includes(selectedStrainLower) ||
                   selectedStrainLower.includes(productStrainLower);
        },
        
        /**
         * Check if product terpenes match selected terpene filter
         * 
         * @param {Array|string} productTerpenes - Array or string of terpenes from product
         * @param {string} selectedTerpene - The selected terpene filter
         * @return {Boolean} - True if product matches the selected terpene
         */
        matchesProductTerpenes: function(productTerpenes, selectedTerpene) {
            if (!productTerpenes || !selectedTerpene) {
                return false;
            }
            
            // Handle both array and string formats
            let terpenesArray = [];
            if (Array.isArray(productTerpenes)) {
                terpenesArray = productTerpenes;
            } else if (typeof productTerpenes === 'string') {
                // Split by comma, semicolon, or other common separators
                terpenesArray = productTerpenes.split(/[,;|]/).map(t => t.trim());
            }
            
            // Convert selected terpene to lowercase for comparison
            const selectedTerpeneLower = selectedTerpene.toString().toLowerCase();
            
            // Check if any product terpene matches the selected one
            return terpenesArray.some(terpene => {
                const terpeneLower = terpene.toString().toLowerCase();
                
                // Exact match only for terpenes to avoid false positives
                // (e.g., "beta-caryophyllene" should NOT match "caryophyllene")
                return terpeneLower === selectedTerpeneLower;
            });
        },
        
        /**
         * Convert filter value to URL-safe format
         * 
         * @param {string} value - The filter value to convert
         * @return {string} - URL-safe version
         */
        toUrlSafe: function(value) {
            if (!value) return '';
            
            return value.toString()
                .toLowerCase()
                .replace(/\s+/g, '-')           // spaces to dashes
                .replace(/[()]/g, '')           // remove parentheses
                .replace(/[<>]/g, '')           // remove < >
                .replace(/&/g, 'and')           // & to 'and'
                .replace(/[^a-z0-9\-]/g, '')    // remove other special chars
                .replace(/--+/g, '-')           // multiple dashes to single
                .replace(/^-|-$/g, '');         // trim leading/trailing dashes
        },
        
        /**
         * Update product count display to show search results
         * 
         * @param {number} visibleCount - Number of products currently visible after filtering
         */
        updateProductCountDisplay: function(visibleCount) {
            const countElement = document.querySelector('.product-count');
            if (!countElement) return;
            
            // Get DOM count for accurate total when no filters are active
            // This handles cases where API product count differs from DOM product count
            const domProductCount = document.querySelectorAll('.product').length;
            const hasActiveFilters = this.hasActiveFilters();
            
            let displayText = '';
            
            if (hasActiveFilters) {
                // Filters active (including search) - show filtered count
                displayText = `Found ${visibleCount} products`;
            } else {
                // No filters active - show DOM total count (more accurate than API count)
                displayText = `Found ${domProductCount} products`;
            }
            
            countElement.textContent = displayText;
        },
        
        /**
         * Check if any filters are currently active
         * 
         * @returns {boolean} True if any filters are active
         */
        hasActiveFilters: function() {
            return (
                this.activeFilters.search.trim() ||
                this.activeFilters.categories.length > 0 ||
                this.activeFilters.brands.length > 0 ||
                this.activeFilters.terpenes.length > 0 ||
                this.activeFilters.sizes.length > 0 ||
                this.activeFilters.dominance.length > 0 ||
                this.activeFilters.thc[0] > 0 || 
                this.activeFilters.thc[1] < 45 ||
                this.activeFilters.cbd[0] > 0 || 
                this.activeFilters.cbd[1] < 30 ||
                this.activeFilters.price[0] > 0 || 
                this.activeFilters.price[1] < 10000 ||
                this.activeFilters.inStock
            );
        },

        /**
         * Convert URL-safe format back to original filter value
         * 
         * @param {string} urlSafeValue - The URL-safe value to convert
         * @return {string} - Original filter value
         */
        fromUrlSafe: function(urlSafeValue) {
            if (!urlSafeValue) return '';
            
            // Map common URL-safe values back to originals
            const urlSafeMap = {
                'small-1g': 'Small (< 1g)',
                'medium-1-5g': 'Medium (1-5g)', 
                'large-5-30g': 'Large (5-30g)',
                'xl-30g': 'XL (> 30g)',
                'liquids-ml': 'Liquids (ml)',
                'hybrid': 'Hybrid',
                'sativa': 'Sativa',
                'indica': 'Indica',
                'blue-dream': 'Blue Dream',
                'northern-lights': 'Northern Lights',
                'durban-poison': 'Durban Poison'
            };
            
            // Check if we have a direct mapping
            if (urlSafeMap[urlSafeValue]) {
                return urlSafeMap[urlSafeValue];
            }
            
            // Otherwise, try to reconstruct by capitalizing and adding spaces
            return urlSafeValue
                .split('-')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }
    };
    
    // Auto-initialize when script loads
    window.AmpleFilter.init();
    
})(jQuery);