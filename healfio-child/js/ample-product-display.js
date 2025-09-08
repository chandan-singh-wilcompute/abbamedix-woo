/**
 * Ample Product Display Helper
 * 
 * Ensures products are visible for filtering even when no category is selected
 * Part of Step 4 fixes for ABBA Issue #8
 * 
 * @package AmpleFilter
 * @version 1.0.0
 * @since 2025-08-30
 */

(function($) {
    'use strict';
    
    // DISABLED: No longer needed after server-side PHP rendering fix
    // This JavaScript was overriding properly rendered WooCommerce products
    // with basic templates missing variation swatches and terpene data
    // See BUGFIX-product-images-missing-when-logged-in.md for details
    if (true) {
        console.log('[AmpleProductDisplay] DISABLED - Server-side rendering now works correctly');
        return;
    }
    
    const AmpleProductDisplay = {
        
        /**
         * Initialize product display helper
         */
        init: function() {
            // Initialize product display helper
            
            $(document).ready(() => {
                this.checkAndFixProductDisplay();
            });
        },
        
        /**
         * Check if products are missing and fix if needed
         */
        checkAndFixProductDisplay: function() {
            // Wait a moment for existing systems to load
            setTimeout(() => {
                this.ensureProductsVisible();
            }, 1500); // Increased delay to avoid interfering with existing systems
        },
        
        /**
         * Ensure products are visible for filtering
         */
        ensureProductsVisible: function() {
            const $productContainer = $('.productFilterResultWrapper');
            const $noFilterMessage = $('.noProductFound').filter(':contains("No filter terms selected")');
            const $existingProducts = $('.products li.product, .products .type-product');
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilterParams = urlParams.has('thc') || urlParams.has('cbd') || urlParams.has('categories') || urlParams.has('brands');
            
            // Inject products if:
            // 1. We see "No filter terms selected" AND no existing products AND we have cached products
            // 2. OR we have URL filter parameters (so filters can be applied)
            const shouldInject = ($noFilterMessage.length > 0 && $existingProducts.length === 0 && window.AmpleFilter && window.AmpleFilter.products.length > 0) || 
                               (hasFilterParams && $existingProducts.length === 0 && window.AmpleFilter && window.AmpleFilter.products.length > 0);
            
            if (shouldInject) {
                // Injecting products from cached data
                
                // Hide the "no filter terms selected" message
                $noFilterMessage.hide();
                
                // Create product grid if it doesn't exist
                let $productGrid = $productContainer.find('.products');
                if ($productGrid.length === 0) {
                    $productGrid = $('<ul class="products elementor-grid columns-4"></ul>');
                    $productContainer.append($productGrid);
                }
                
                // Inject products from our cached data
                this.injectProducts($productGrid, window.AmpleFilter.products.slice(0, 20)); // Show first 20 products
                
                // Products injected successfully
                
                // If we have URL parameters, trigger filter application after injection
                if (hasFilterParams) {
                    setTimeout(() => {
                        if (window.AmpleFilter && typeof window.AmpleFilter.applyFilters === 'function') {
                            // Triggering filter application for URL params
                            window.AmpleFilter.applyFilters();
                        }
                    }, 100);
                }
            } else if ($existingProducts.length > 0) {
                // Existing products found, no injection needed
            }
        },
        
        /**
         * Inject product HTML from cached data
         */
        injectProducts: function($container, products) {
            $container.empty();
            
            products.forEach(product => {
                const productHtml = this.createProductHtml(product);
                $container.append(productHtml);
            });
        },
        
        /**
         * Create product HTML from product data
         */
        createProductHtml: function(product) {
            const $productLi = $(`
                <li class="product type-product post-${product.id} instock taxable shipping-taxable product-type-simple">
                    <div class="product-wrapper">
                        <div class="product-image">
                            <a href="${product.permalink || '#'}" class="woocommerce-LoopProduct-link">
                                <img src="${(product.image && product.image.medium) ? product.image.medium : '/wp-content/uploads/woocommerce-placeholder.png'}" 
                                     alt="${product.title}" 
                                     class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" />
                            </a>
                        </div>
                        <div class="product-details">
                            <h2 class="woocommerce-loop-product__title">
                                <a href="${product.permalink || '#'}">${product.title}</a>
                            </h2>
                            <div class="product-meta">
                                <span class="thc-content">THC: ${product.thc}%</span>
                                <span class="cbd-content">CBD: ${product.cbd}%</span>
                            </div>
                            <span class="price">${product.price_html}</span>
                            <div class="product-actions">
                                <a href="${product.permalink || '#'}" class="button product_type_simple">View Product</a>
                            </div>
                        </div>
                    </div>
                </li>
            `);
            
            return $productLi;
        }
    };
    
    // Initialize the product display helper
    AmpleProductDisplay.init();
    
    // Export for debugging
    window.AmpleProductDisplay = AmpleProductDisplay;
    
})(jQuery);