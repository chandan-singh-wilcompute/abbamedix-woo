<footer id="site-footer" role="contentinfo">

    <div id="footer-wave"></div>

    <div class="footer-bg" style="background-color:black">

        <div class="footer-inner container-xl pt-xl-5 pb-2 pt-5 px-4">

            <?php /*get_template_part('template-parts/footer-widgets');*/ ?>
			<div class="footer-top">
				<div class="row">
					<div class="col-sm-6 col-lg-2" style="align-self: center;">
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/img/logo-white.png" alt="footer-logo">
					</div>
					<div class="col-sm-6 col-lg-2">
						<h3>QUICK LINKS</h3>
						<p><a href="<?php bloginfo('url'); ?>/contact-us" class="f-item">CONTACT US</a></p>
						<p><a href="<?php bloginfo('url'); ?>/about-us" class="f-item">ABOUT</a></p>
						<p><a href="<?php bloginfo('url'); ?>/faq" class="f-item">FAQ</a></p>
						
					</div>
					<div class="col-sm-6 col-lg-2">
						<h3>PRIVACY</h3>
						<p><a href="<?php bloginfo('url'); ?>/privacy-policy" class="f-item">PRIVACY POLICY</a></p>
						<p><a href="<?php bloginfo('url'); ?>/terms-conditions" class="f-item">TERMS & CONDITIONS</a></p>
						
					</div>
					<div class="col-sm-6 col-lg-2">
					<h3>CURIOUS ABOUT MEDICAL CANNABIS ?</h3>
						<p><a href="https://canadahouseclinics.ca/" targertarget="_blank" class="f-item">TALK TO A CANNABIS CLINIC</a></p>
					</div>
					<div class="col-sm-6 col-lg-4"> <?php dynamic_sidebar('sidebar-3'); ?></div>
					
				</div>
			</div>
				
				
            <div class="footer-bottom" style="display:none">

                <div class="footer-credits">

                    <p class="footer-copyright"><?php if (false == get_theme_mod('copyright_text_switcher')) {
                            echo 'Copyright ';
                        } ?>&copy;<?php
                        echo date_i18n(
                        /* translators: Copyright date format, see https://www.php.net/date */
                            esc_html_x('Y ', 'copyright date format', 'healfio')
                        );
                        $cop_txt = get_theme_mod('copyright_text');
                        if ('' == $cop_txt) {
                            bloginfo('name');
                            esc_html_e('. All rights reserved. ', 'healfio');
                        } else {
                            echo wp_kses($cop_txt, 'regular');
                        } ?>

                    </p><!-- .footer-copyright -->

                </div><!-- .footer-credits -->

                <nav class="footer-menu-wrapper" aria-label="<?php esc_attr_e('Footer', 'healfio'); ?>" role="navigation">
                    <ul class="footer-menu">
                        <?php
                        if (has_nav_menu('footer')) {
                            wp_nav_menu(
                                array(
                                    'container' => '',
                                    'depth' => 1,
                                    'items_wrap' => '%3$s',
                                    'theme_location' => 'footer',
                                )
                            );
                        }
                        ?>
                    </ul>
                </nav>

            </div><!-- .footer-bottom  -->

        </div><!-- .footer-inner -->

    </div>

    <?php /*get_template_part('template-parts/bg-footer'); */?>
    <!-- .footer-bg -->

</footer><!-- #site-footer -->

<!-- Modal for Notify me -->
<div id="notifyMeModal" class="notify-me-modal" style="display:none;">
    <div class="notify-me-content">
        <span class="close">&times;</span>
        <!-- <h5>Product Notification</h5> -->
        <!-- <input type="email" id="notifyEmail" placeholder="Enter your email">
        <button id="notifySubmit">Submit</button> -->
        <h6 id="notifyMessage"></h6>
    </div>
</div>
<?php wp_footer(); ?>

<script>
    /**
     * Unified, non-redundant variant + qty + price script
     * Keeps your classes/markup exactly as-is, fixes glitches.
     */
    jQuery(function($){

        // Initialize single product page button visibility
        if ($('body').hasClass('single-product')) {
            const $variationForm = $('.variations_form');
            if ($variationForm.length) {
                // Check if initial variation should be selected
                setTimeout(function() {
                    // Trigger change to set initial state
                    $variationForm.find('.variations select').first().trigger('change');
                }, 100);
            }
        }

        // -------------------------
        // Helpers
        // -------------------------
        function parsePriceString(str){
            if(!str) return 0;
            const num = String(str).replace(/[^0-9.\-]+/g,'');
            return parseFloat(num) || 0;
        }

        // store base price for each product card so resets restore it
        $('li.product').each(function(){
            const $card = $(this);
            const $priceHtml = $card.find('.prodcard-price .price-html').first();
            const base = $priceHtml.length ? $priceHtml.text().trim() : '';
            $card.find('.prodcard-price').data('base-price', base);
        });

        // -------------------------
        // Expose global functions used by inline onclick in markup
        // -------------------------
        window.increaseQty = function(button){
            try {
            const input = button.previousElementSibling;
            if (!input) return;
            input.value = parseInt(input.value || 0) + 1;
            window.updateTotal(input);
            } catch(e){ console.error(e); }
        };
        window.decreaseQty = function(button){
            try {
            const input = button.nextElementSibling;
            if (!input) return;
            if (parseInt(input.value || 0) > 1) {
                input.value = parseInt(input.value || 0) - 1;
                window.updateTotal(input);
            }
            } catch(e){ console.error(e); }
        };

        window.updateTotal = function(input){
            try {
            const card = input.closest('.product');
            if (!card) return;
            const $card = $(card);

            // prefer hidden numeric prod-price if present (set on swatch selection)
            let unit = parseFloat($card.find('#prod-price').val());
            if (!unit || isNaN(unit)) {
                // fallback to base-price (strip currency)
                const baseTxt = $card.find('.prodcard-price').data('base-price') || $card.find('.prodcard-price .price-html').first().text().trim();
                unit = parsePriceString(baseTxt);
            }
            const cur = $card.find('#prod-cur').val() || '$';
            const qty = parseInt(input.value) || 1;

            const totalPriceEl = $card.find('.prodcard-price .price-html').first();
            if (totalPriceEl.length) {
                totalPriceEl.text(cur + ( (unit * qty).toFixed(2) ) );
            }

            // Update WooCommerce form's quantity field for this card (if it exists)
            const $hiddenQty = $card.find('form.cart input[name="quantity"]').first();
            if ($hiddenQty.length) $hiddenQty.val(qty);

            } catch(e){ console.error(e); }
        };

        // Delegated handlers for plus/minus (covers .qty-plus/.qty-minus, .increase/.decrease, .addQuntity/.minusQuntity)
        $(document).on('click', '.increase, .addQuntity', function(e){
            e.preventDefault();
            const $btn = $(this);
            // find closest product card then the qty input inside it
            const $card = $btn.closest('li.product, .product, .product-card, .singleProductContainer');
            let $qty = $card.find('input[name="quantity"], input.quantity, input.qty').first();
            if (!$qty.length) $qty = $btn.siblings('input.qty').first();
            if (!$qty.length) return;
            const curVal = parseInt($qty.val()) || 0;
            const max = parseInt($qty.attr('max')) || null;
            const newVal = (max && curVal >= max) ? max : curVal + 1;
            $qty.val(newVal).trigger('change');
            // call update for product card totals
            const nativeInput = $qty.get(0);
            if (nativeInput) window.updateTotal(nativeInput);
            // call single product total update as well
            updateDisplayedPriceIfSingle();
        });

        $(document).on('click', '.decrease, .minusQuntity', function(e){
            e.preventDefault();
            const $btn = $(this);
            const $card = $btn.closest('li.product, .product, .product-card, .singleProductContainer');
            let $qty = $card.find('input[name="quantity"], input.quantity, input.qty').first();
            if (!$qty.length) $qty = $btn.siblings('input.qty').first();
            if (!$qty.length) return;
            const curVal = parseInt($qty.val()) || 0;
            const min = parseInt($qty.attr('min')) || 1;
            const newVal = curVal > min ? curVal - 1 : min;
            $qty.val(newVal).trigger('change');
            const nativeInput = $qty.get(0);
            if (nativeInput) window.updateTotal(nativeInput);
            updateDisplayedPriceIfSingle();
        });

        // -----------------
            // AUTO SELECT FIRST VARIATION ON SINGLE PRODUCT PAGE
            // -----------------
            if ($('body').hasClass('single-product')) {
                var $firstOption = $('.variations_form select').first().find('option:not(:disabled):not([value=""])').first();
                if ($firstOption.length) {
                    $('.variations_form select').first().val($firstOption.val()).trigger('change');
                }

                // if using swatches instead of select dropdowns
                var $firstSwatch = $('.variations_form .swatch').first();
                if ($firstSwatch.length) {
                    $firstSwatch.trigger('click');
                }
            }

        // Also ensure change/input on quantity input triggers updateTotal
        $(document).on('input change', 'li.product input[name="quantity"], li.product input.quantity', function() {
            // update only this card
            const nativeInput = this;
            window.updateTotal(nativeInput);
        });

        // -------------------------
        // Notify Me (keep your ajax)
        // -------------------------
        $(document).on('click', '.notify-me-button', function() {
            const $btn = $(this);
            const selectedProductId = $btn.data('product-id');
            if (!selectedProductId) return;

            // Check login status immediately on client-side
            if (!wc_notify_me_params.is_logged_in) {
                // Instant redirect to login page - no AJAX delay
                window.location.href = wc_notify_me_params.login_url;
                return;
            }

            // Save original button content
            const originalText = $btn.html();

            // Disable button & show loading animation
            $btn.prop('disabled', true).html('<span class="spinner"></span> Please wait...');

            $.post(
                wc_notify_me_params.ajax_url,
                {
                    action: 'add_to_notify_list',
                    product_id: selectedProductId
                },
                function(response) {
                    if (response && response.success) {
                        $('#notifyMeModal').fadeIn();
                        $('#notifyMessage')
                            .text(response.data)
                            .css('color', 'green');
                        setTimeout(() => $('#notifyMeModal').fadeOut(), 4000);
                    } else if (response && response.data && response.data.redirect) {
                        window.location = response.data.redirect;
                    } else {

                        $('#notifyMeModal').fadeIn();
                        $('#notifyMessage')
                            .text('You will be notified!')
                            .css('color', 'green');
                        setTimeout(() => $('#notifyMeModal').fadeOut(), 4000);
                    }
                }
            ).always(function() {
                // Restore button state
                $btn.prop('disabled', false).html(originalText);
            });
        });


        $(document).on('click', '.close', function(){
            $('#notifyMeModal').fadeOut();
            $('#notifyMessage').text('');
        });

        // -------------------------
        // Variation swatches IN LOOP (per-container ajax + queued clicks)
        // -------------------------
        $('.shop-variation-swatches').each(function(){
            const container = $(this);
            const productId = container.data('product-id');
            let variations = [];
            let loaded = false;
            let queued = [];

            // store base-price if not already set
            const $card = container.closest('li.product');
            if ($card.length) {
            const base = $card.find('.prodcard-price .price-html').first().text().trim();
            container.data('base-price', base);
            }

            // fetch variations via AJAX (server side endpoint you already have)
            $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            method: 'POST',
            data: { action: 'get_variations_for_product', product_id: productId }
            }).done(function(res){
            variations = (res && res.success && res.data) ? res.data : [];
            loaded = true;
            container.data('variations', variations);
            // process queued clicks
            while (queued.length) {
                const sw = queued.shift();
                processSwatchClick(sw);
            }
            container.trigger('variations_loaded');
            }).fail(function(){
            variations = [];
            loaded = true;
            container.data('variations', variations);
            container.trigger('variations_loaded');
            });

            // delegated click handler attached to container
            container.on('click', '.swatch-item', function(e){
                const swatch = $(this);
                if (!loaded) {
                    queued.push(swatch);
                    swatch.addClass('queued');
                    return;
                }
                processSwatchClick(swatch);
            });

            function processSwatchClick(swatch) {
            swatch.removeClass('queued');
            const swatchWrapper = swatch.closest('.shop-variation-swatches');
            const productCard = swatch.closest('li.product');
            // Reset all other product cards to base state (keep base price)
            $('li.product').each(function(){
                const $p = $(this);
                if ($p.find('.notify-me-button').length) return; // keep notify for true OOS items
                $p.find('.swatch-item').removeClass('active');
                $p.find('.variation_id').val('');
                $p.find('.variation-add-to-cart').show();
                $p.find('.single_add_to_cart_button').removeClass('active').text('SELECT SIZE').prop('disabled', false).removeClass('disabled');
                $p.find('.productQuantity').removeClass('active');
                const base = $p.find('.prodcard-price').data('base-price') || $p.find('.prodcard-price .price-html').first().text().trim() || '';
                $p.find('.prodcard-price .price-html').html(base);
            });

            // Activate clicked swatch only
            swatchWrapper.find('.swatch-item').removeClass('active');
            swatch.addClass('active');

            const selectedAttrs = getSelectedAttributes(swatchWrapper);
            const match = findMatchingVariation(productId, selectedAttrs, variations);

            const button = swatchWrapper.find('.single_add_to_cart_button');
            const qtyInput = productCard.find('.productQuantity input.quantity');
            const qty = parseInt(qtyInput.val()) || 1;
            const currency = productCard.find('#prod-cur').val() || '$';

            if (match) {
                swatchWrapper.find('.variation_id').val(match.variation_id);
                swatchWrapper.find('form.cart input[name="quantity"]').val(qty);
                const unitPrice = parseFloat(match.display_price || match.price || match.regular_price || 0) || 0;
                swatchWrapper.find('#prod-price').val(unitPrice);
                const total = (unitPrice * qty).toFixed(2);
                productCard.find('.prodcard-price .price-html').html(`${currency}${total}`);
                swatchWrapper.find('.variation-add-to-cart').show();
                button.addClass('active').text('Add to Cart');
            } else {
                swatchWrapper.find('.variation_id').val('');
                productCard.find('.prodcard-price .price-html').html(container.data('base-price') || '');
                swatchWrapper.find('.variation-add-to-cart').hide();
                button.removeClass('active').text('Select Size');
            }

            // Toggle quantity UI only for active card
            $('li.product .productQuantity').removeClass('active');
            productCard.find('.productQuantity').addClass('active');
            }

            // helper functions for this container
            function getSelectedAttributes(container) {
            const attributes = {};
            container.find('.swatch-item.active').each(function(){
                const a = $(this).data('attribute');
                const v = $(this).data('value');
                if (typeof a !== 'undefined') attributes[a] = v;
            });
            return attributes;
            }

            function findMatchingVariation(productId, selectedAttrs, variationsList) {
            if (!selectedAttrs || Object.keys(selectedAttrs).length === 0) return null;
            if (!variationsList || !variationsList.length) return null;
            function normalize(v){ return String(v||'').toLowerCase().trim().replace(/\s+/g,' '); }
            return variationsList.find(function(variation){
                if (!variation || !variation.attributes) return false;
                return Object.entries(selectedAttrs).every(function([key,value]){
                const want = normalize(value);
                const candidates = ['attribute_' + key];
                if (key.indexOf('pa_') !== 0) candidates.push('attribute_pa_' + key);
                candidates.push('attribute_' + key.replace(/-/g,'_'));
                candidates.push('attribute_' + key.replace(/_/g,'-'));
                return candidates.some(function(k){
                    if (typeof variation.attributes[k] === 'undefined' || variation.attributes[k] === null) return false;
                    return normalize(variation.attributes[k]) === want;
                });
                });
            }) || null;
            }

        }); 

        // -------------------------
        // Single product page: variation found + qty => update price & RX
        // -------------------------
        const $variationForm = $('form.variations_form');
        function updateDisplayedPriceIfSingle() {
            // find single product qty input
            const $cartForm = $('form.cart').first();
            const $qty = $cartForm.find('input[name="quantity"], input.qty').first();
            const qty = parseInt($qty.val()) || 1;

            let unit = parseFloat($('#productUnitPrice').val());
            let inStock = true; // assume in stock unless we detect otherwise

            if (!unit || isNaN(unit)) {
                // try to read variation id dump
                const $vf = $('form.variations_form').first();
                if ($vf.length) {
                    let variations = $vf.data('product_variations');
                    if (!variations) {
                        const raw = $vf.attr('data-product_variations') || $vf.attr('data-product-variations') || '';
                        try { variations = JSON.parse($('<textarea/>').html(raw).text() || '[]'); } catch(e) { variations = []; }
                    }
                    const vid = parseInt($vf.find('input.variation_id').val()) || 0;
                    if (vid && variations && variations.length) {
                        const v = variations.find(x => parseInt(x.variation_id) === vid);
                        if (v) {
                            unit = parseFloat(v.display_price || v.price || 0);
                            inStock = !!v.is_in_stock; // check stock
                        }
                    }
                }
            }

            const $priceBdi = $('.woocommerce-variation.single_variation .price bdi').first();

            if (!unit || isNaN(unit) || !inStock) {
                // variation not in stock → clear everything
                if ($priceBdi.length) {
                    $priceBdi.text('');
                }
                $('#rxDeductionAmnt').text('-');
                $('.quantity input, .quantity button').prop('disabled', true); // disable qty buttons & input
                return; // stop here
            }

            // if in stock → update normally
            if ($priceBdi.length) {
                const currency = $priceBdi.find('.woocommerce-Price-currencySymbol').text() || '$';
                $priceBdi.html(`${currency}${(unit * qty).toFixed(2)}`);
            }

            const rxBase = parseFloat($('#rxDeductionActual').val()) || 0;
            if (rxBase) {
                const rxTotal = (rxBase * qty).toFixed(2).replace(/\.00$/,'');
                $('#rxDeductionAmnt').text(rxTotal + 'g');
            }

            // re-enable qty controls when in stock
            $('.quantity input, .quantity button').prop('disabled', false);
        }

        // when native WooCommerce finds a variation
        // $variationForm.on('found_variation', function(event, variation) {
        //     try {
        //         const $qty = $variationForm.find('input[name="quantity"], input.qty').first();
        //         const $plus = $('.increase, .addQuntity');
        //         const $minus = $('.decrease, .minusQuntity');
        //         const $priceBdi = $('.woocommerce-variation.single_variation .price bdi').first();

        //         if (!variation.is_in_stock) {
        //             // Out of stock → disable controls + reset displays
        //             $qty.prop('disabled', true);
        //             $plus.prop('disabled', true).addClass('disabled');
        //             $minus.prop('disabled', true).addClass('disabled');

        //             $('#rxDeductionAmnt').text('—');
        //             $('#rxDeductionActual').val('');
        //             $('#productUnitPrice').val('');

        //             if ($priceBdi.length) {
        //                 $priceBdi.html('');
        //             }
        //             return; // bail out
        //         }

        //         // Otherwise: In stock → enable controls
        //         $qty.prop('disabled', false);
        //         $plus.prop('disabled', false).removeClass('disabled');
        //         $minus.prop('disabled', false).removeClass('disabled');

        //         // Store unit price
        //         const unit = parseFloat(variation.display_price || variation.price || 0) || 0;
        //         if ($('#productUnitPrice').length) {
        //             $('#productUnitPrice').val(unit);
        //         } else {
        //             $('<input>').attr({type:'hidden', id:'productUnitPrice', value: unit}).appendTo('form.variations_form');
        //         }

        //         // Set RX base
        //         if (variation.rx_reduction) {
        //             $('#rxDeductionActual').val(variation.rx_reduction);
        //         } else {
        //             $('#rxDeductionAmnt').text('—');
        //             $('#rxDeductionActual').val('');
        //         }

        //     } catch(e){ console.error(e); }
        // });
        $variationForm.on('found_variation', function(event, variation) {
            try {
                const $qty = $variationForm.find('input[name="quantity"], input.qty').first();
                const $plus = $('.increase, .addQuntity');
                const $minus = $('.decrease, .minusQuntity');
                const $priceBdi = $('.woocommerce-variation.single_variation .price bdi').first();
                const $cartBtn = $variationForm.find('.single_add_to_cart_button');

                if (!variation.is_in_stock) {
                    // Out of stock → disable controls + reset displays
                    $qty.prop('disabled', true);
                    $plus.prop('disabled', true).addClass('disabled');
                    $minus.prop('disabled', true).addClass('disabled');

                    $('#rxDeductionAmnt').text('-');
                    $('#rxDeductionActual').val('');
                    $('#productUnitPrice').val('');

                    if ($priceBdi.length) $priceBdi.html('');

                    // Change Add to Cart → Notify Me
                    if ($cartBtn.length) {
                        $cartBtn
                            .prop('disabled', false) // keep clickable
                            .removeClass('disabled')
                            .addClass('notify-me-button button-ready')
                            .attr('data-product-id', variation.variation_id)
                            .text('Notify Me')
                            .show()
                            .css('display', ''); // ensure it's visible
                    }

                    return;
                }

                // In stock → enable controls
                $qty.prop('disabled', false);
                $plus.prop('disabled', false).removeClass('disabled');
                $minus.prop('disabled', false).removeClass('disabled');

                // Restore Add to Cart button
                if ($cartBtn.length) {
                    $cartBtn
                        .removeClass('notify-me-button')
                        .addClass('button-ready')
                        .text('Add to cart')
                        .show()
                        .css('display', ''); // ensure it's visible
                }

                // Store unit price
                const unit = parseFloat(variation.display_price || variation.price || 0) || 0;
                if ($('#productUnitPrice').length) {
                    $('#productUnitPrice').val(unit);
                } else {
                    $('<input>').attr({type:'hidden', id:'productUnitPrice', value: unit}).appendTo('form.variations_form');
                }

                // Set RX base
                if (variation.rx_reduction) {
                    $('#rxDeductionActual').val(variation.rx_reduction);
                } else {
                    $('#rxDeductionAmnt').text('-');
                    $('#rxDeductionActual').val('');
                }

            } catch(e){ console.error(e); }
        });

        // when native WooCommerce shows a variation
        $variationForm.on('show_variation', function(event, variation){
            setTimeout(updateDisplayedPriceIfSingle, 20); // slight delay to let Woo overwrite
        });

        // when variation resets
        $variationForm.on('reset_data', function(){
            $('#rxDeductionAmnt').text('—');
            $('#rxDeductionActual').val('');
            $('#productUnitPrice').val('');
            // restore price area to default - let WooCommerce handle server-rendered price
        });

        // -------------------------
        // Fade Woo notices (keeps your behavior)
        // -------------------------
        function fadeWooNotices() {
            const notices = document.querySelectorAll('.woocommerce-message, .woocommerce-error, .woocommerce-info');
            if (notices.length > 0) {
            setTimeout(() => {
                notices.forEach(el => {
                // Skip approval status notice - keep it permanent
                if (el.classList.contains('ample-approval-notice')) {
                    return;
                }
                el.style.transition = 'opacity 1s ease-out';
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 800);
                });
            }, 4000);
            }
        }
        $(document).ready(fadeWooNotices);
        $(document.body).on('updated_wc_div wc_fragments_refreshed', fadeWooNotices);

        // -------------------------
        // Approval notice close button
        // -------------------------
        $(document).on('click', '.ample-approval-close', function(e) {
            e.preventDefault();
            $(this).closest('.ample-approval-notice').fadeOut(300);
        });

        // -------------------------
        // AJAX Add to Cart for Variable Products (Single Product Page)
        // -------------------------
        var singleProductProcessing = false;
        
        $(document).on('submit', 'form.variations_form', function(e) {
            e.preventDefault();
            
            // Prevent multiple submissions
            if (singleProductProcessing) {
                return false;
            }
            
            const $form = $(this);
            const $button = $form.find('.single_add_to_cart_button');
            
            // Skip if it's a notify-me button
            if ($button.hasClass('notify-me-button')) {
                return;
            }
            
            singleProductProcessing = true;
            const formData = $form.serialize();
            const originalText = $button.text();
            
            // Show loading state
            $button.prop('disabled', true).text('Adding...');
            
            // Add nonce for security
            const postData = formData + '&action=woocommerce_add_to_cart&_wpnonce=' + wc_add_to_cart_params.nonce;
            
            $.post(wc_add_to_cart_params.ajax_url, postData)
                .done(function(response) {
                    console.log('Single product AJAX response:', response);
                    
                    if (response && response.fragments) {
                        // Update cart fragments
                        $.each(response.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                        
                        // Trigger cart update event
                        $(document.body).trigger('wc_fragments_refreshed');
                        
                        // Refresh gram quota information
                        refreshGramQuota();
                        
                        // Get product name for toast (try multiple selectors)
                        let productName = $('.product_title, .entry-title, h1.product_title, h1.entry-title, .product-title, .single-product h1, .single-product .product_title, .singleProductContainer .product-title').first().text().trim();
                        
                        // Debug: log what we found
                        console.log('Product name found:', productName);
                        console.log('Available elements:', {
                            '.product_title': $('.product_title').length,
                            '.entry-title': $('.entry-title').length, 
                            'h1': $('h1').length,
                            'document.title': document.title
                        });
                        
                        // Fallback to page title if no product name found
                        if (!productName) {
                            productName = document.title.split(' - ')[0].split(' | ')[0].trim() || 'Product';
                        }
                        
                        // Show toast notification
                        showToast(`${productName} added to cart!`, 'success');
                        
                        // Show success on button
                        $button.text('Added!');
                        
                    } else {
                        console.warn('No fragments in response, but request succeeded');
                        
                        // Still show success feedback
                        const productName = $('.product_title, .entry-title, h1.product_title, h1.entry-title, .product-title, .single-product h1, .single-product .product_title, .singleProductContainer .product-title').first().text().trim() || 'Product';
                        showToast(`${productName} added to cart!`, 'success');
                        refreshGramQuota();
                        $button.text('Added!');
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Single product AJAX failed:', status, error, xhr.responseText);
                    
                    // Show error feedback
                    $button.text('Error - Try Again');
                    
                    // Show error toast
                    const productName = $('.product_title, .entry-title, h1.product_title, h1.entry-title, .product-title, .single-product h1, .single-product .product_title, .singleProductContainer .product-title').first().text().trim() || 'Product';
                    showToast(`Failed to add ${productName} to cart. Please try again.`, 'error');
                })
                .always(function() {
                    // Always reset after delay regardless of success/failure
                    setTimeout(function() {
                        $button.prop('disabled', false).text(originalText);
                        singleProductProcessing = false;
                    }, 2000);
                });
        });

        // -------------------------
        // Gram Quota Refresh System
        // -------------------------
        function refreshGramQuota() {
            // Check if GramQuotaAjax is available (from the ample-connect plugin)
            if (typeof GramQuotaAjax !== 'undefined' && GramQuotaAjax.ajax_url) {
                fetch(GramQuotaAjax.ajax_url + '?action=get_gram_quota_data', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(res => res.json())
                .then(data => {
                    console.log("Refreshing gram quota data:", data);
                    if (data && typeof data.policy_grams === 'number' && typeof data.prescription_grams === 'number') {
                        updateGramBar(data.policy_grams, data.prescription_grams);
                    }
                })
                .catch(err => {
                    console.error('Error refreshing gram quota:', err);
                });
            }
        }

        function updateGramBar(policyGrams, presGrams) {
            // Update the gram info in the header/top area
            const rxDed = document.getElementById('rx-dedu-info');
            if (rxDed) {
                rxDed.innerText = `${presGrams} gr remaining`;
            }
            
            // Update cart page gram info if we're on cart page
            const pathSegments = window.location.pathname.split('/').filter(Boolean);
            const lastSegment = pathSegments[pathSegments.length - 1];

            if (lastSegment === 'cart') {
                const policyvalue = document.getElementById('policyvalue');
                const prescvalue = document.getElementById('prescvalue');

                if (prescvalue) {
                    prescvalue.innerHTML = `<div class="skillBar">
                        You have <strong class="skillvalue">${presGrams}g</strong> left in your prescription.
                    </div>`;
                }

                if (policyvalue) {
                    policyvalue.innerHTML = `<div class="skillBar">
                        You have <strong class="skillvalue">${policyGrams}g</strong> left in your policy.
                    </div>`;
                }
            }
        }

        // -------------------------
        // Global Overlay System
        // -------------------------
        function showGlobalOverlay() {
            // Remove existing overlay
            $('.global-cart-overlay').remove();
            
            // Create overlay element
            const overlay = $(`
                <div class="global-cart-overlay">
                    <div class="overlay-content">
                        <div class="overlay-spinner"></div>
                        <div class="overlay-text">Processing...</div>
                    </div>
                </div>
            `);
            
            // Add to page
            $('body').append(overlay);
            
            // Show with animation
            setTimeout(() => overlay.addClass('show'), 10);
        }

        function hideGlobalOverlay() {
            $('.global-cart-overlay').removeClass('show');
            setTimeout(() => $('.global-cart-overlay').remove(), 300);
        }

        // -------------------------
        // Toast Notification System
        // -------------------------
        function showToast(message, type = 'success') {
            // Remove existing toasts
            $('.custom-toast').remove();
            
            // Create toast element
            const toast = $(`
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-content">
                        <span class="toast-icon">${type === 'success' ? '✓' : '⚠'}</span>
                        <span class="toast-message">${message}</span>
                        <button class="toast-close" onclick="$(this).parent().parent().fadeOut()">&times;</button>
                    </div>
                </div>
            `);
            
            // Add to page
            $('body').append(toast);
            
            // Show with animation
            setTimeout(() => toast.addClass('show'), 100);
            
            // Auto hide after 4 seconds
            setTimeout(() => {
                toast.removeClass('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // -------------------------
        // Prevent form submission for product cards to avoid page reload
        // -------------------------
        $(document).on('submit', 'li.product form.cart', function(e) {
            e.preventDefault();
            // Trigger button click instead of form submission
            $(this).find('.single_add_to_cart_button').trigger('click');
        });


        // -------------------------
        // AJAX Add to Cart for Product Cards (Shop/Archive Pages ONLY) 
        // -------------------------
        $(document).on('click', 'li.product .single_add_to_cart_button:not(.notify-me-button)', function(e) {
            // Skip if we're on a single product page
            if ($('body').hasClass('single-product')) {
                return;
            }
            e.preventDefault();
            
            const $button = $(this);
            const $form = $button.closest('form.cart');
            const $card = $button.closest('li.product');
            
            // Get product data
            const productId = $form.find('input[name="product_id"]').val() || $card.data('product_id');
            const variationId = $form.find('input[name="variation_id"]').val() || 0;
            const quantity = $form.find('input[name="quantity"]').val() || 1;
            
            if (!productId) {
                console.error('No product ID found');
                return;
            }
            
            const originalText = $button.text();
            
            // Show loading state and prevent other interactions
            $button.prop('disabled', true).text('Adding...');
            $card.addClass('adding-to-cart');
            
            // Add global overlay to prevent other add-to-cart clicks
            showGlobalOverlay();
            
            // Disable all other add-to-cart buttons
            $('.single_add_to_cart_button').not($button).prop('disabled', true);
            
            // AJAX call to add product to cart
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'woocommerce_ajax_add_to_cart',
                    product_id: productId,
                    quantity: quantity,
                    variation_id: variationId
                },
                success: function(response) {
                    console.log('Add to cart success:', response);
                    
                    // Handle WooCommerce response
                    if (response.success && response.data) {
                        // Update cart fragments if available
                        if (response.data.fragments) {
                            $.each(response.data.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                            $(document.body).trigger('wc_fragments_refreshed');
                        } else {
                            // Fallback: trigger manual cart refresh
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                        
                        // Refresh gram quota information
                        refreshGramQuota();
                    }
                    
                    // Get product name for toast
                    const productName = $card.find('.woocommerce-loop-product__title, .product-title, h3 a, h2 a').first().text().trim() || 'Product';
                    
                    // Show toast notification
                    showToast(`${productName} added to cart!`, 'success');
                    
                    // Show success feedback on button
                    $button.text('Added!');
                    $card.addClass('added-to-cart');
                    
                    // Reset button after delay
                    setTimeout(function() {
                        $button.prop('disabled', false).text(originalText);
                        $card.removeClass('adding-to-cart added-to-cart');
                        
                        // Remove global overlay and re-enable all buttons
                        hideGlobalOverlay();
                        $('.single_add_to_cart_button').prop('disabled', false);
                    }, 2000);
                },
                error: function(xhr, status, error) {
                    console.error('Add to cart failed:', status, error, xhr.responseText);
                    
                    // Get product name for error toast
                    const productName = $card.find('.woocommerce-loop-product__title, .product-title, h3 a, h2 a').first().text().trim() || 'Product';
                    
                    // Show error toast
                    showToast(`Failed to add ${productName} to cart. Please try again.`, 'error');
                    
                    // Show error feedback on button
                    $button.text('Try Again');
                    $card.removeClass('adding-to-cart');
                    
                    // Reset button after delay
                    setTimeout(function() {
                        $button.prop('disabled', false).text(originalText);
                        
                        // Remove global overlay and re-enable all buttons
                        hideGlobalOverlay();
                        $('.single_add_to_cart_button').prop('disabled', false);
                    }, 3000);
                }
            });
        });

    }); // end jQuery wrapper
</script>


<!-- Talkdesk Webchat -->
<script>
  var webchat;
  (function(window, document, node, props, configs) {
    if (window.TalkdeskChatSDK) {
      console.error("TalkdeskChatSDK already included");
      return;
    }
    var divContainer = document.createElement("div");
    divContainer.id = node;
    document.body.appendChild(divContainer);
    var src = "https://talkdeskchatsdk.talkdeskapp.com/v2/talkdeskchatsdk.js";
    var script = document.createElement("script");
    var firstScriptTag = document.getElementsByTagName("script")[0];
    script.type = "text/javascript";
    script.charset = "UTF-8";
    script.id = "tdwebchatscript";
    script.src = src;
    script.async = true;
    firstScriptTag.parentNode.insertBefore(script, firstScriptTag);
    script.onload = function() {
      webchat = TalkdeskChatSDK(node, props);
      webchat.init(configs);
    };
  })(
    window,
    document,
    "tdWebchat",
    { touchpointId: "6f4f4755162c438199a48936ea0bceff", accountId: "", region: "td-ca-1" },
    { enableValidation: false, enableEmoji: true, enableUserInput: true, enableAttachments: true }
  );
</script>

<script>
    document.querySelectorAll(".dropdown > a.menu-item").forEach((trigger) => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault(); // prevent link navigation if needed
            this.parentElement.classList.toggle("open");
        });
    });
</script>


</body>
</html>
