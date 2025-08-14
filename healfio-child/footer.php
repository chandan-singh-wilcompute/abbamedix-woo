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
        <h5>Get Notified When Available</h3>
        <input type="email" id="notifyEmail" placeholder="Enter your email">
        <button id="notifySubmit">Submit</button>
        <p id="notifyMessage"></p>
    </div>
</div>
<?php wp_footer(); ?>

<script>
    // GO Back
    document.getElementById("goback").addEventListener("click", () => {
      history.back();
    });
    
    // Quantity
    // const qtyInput = document.querySelector('.productQuantity .quantity .qty');
    // const increaseBtn = document.querySelector('.productQuantity .increase');
    // const decreaseBtn = document.querySelector('.productQuantity .decrease');

    // increaseBtn.addEventListener('click', () => {
    //   const currentValue = parseInt(qtyInput.value) || 0;
    //   qtyInput.value = currentValue + 1;
    // });

    // decreaseBtn.addEventListener('click', () => {
    //   const currentValue = parseInt(qtyInput.value) || 0;
    //   const min = parseInt(qtyInput.min) || 0;
    //   if (currentValue > min) {
    //     qtyInput.value = currentValue - 1;
    //   }
    // }); 


    // Product card quantity
    function increaseQty(button) {
        const input = button.previousElementSibling;
        input.value = parseInt(input.value) + 1;
        updateTotal(input);
    }

    function decreaseQty(button) {
        const input = button.nextElementSibling;
        if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateTotal(input);
        }
    }

    function updateTotal(input) {
        // const card = input.closest('.product');
        // const price = parseFloat(card.querySelector('#prod-price').value);
        // const cur = card.querySelector('#prod-cur').value;
        // // const priceText = card.querySelector('.prodcard-price span').innerText;
        // // const price = parseFloat(priceText.replace('$', ''));
        // const qty = parseInt(input.value);
        // const totalPrice = card.querySelector('.prodcard-price span');
        // totalPrice.innerText = `${cur}${(price * qty).toFixed(2)}`;

        const card = input.closest('.product');
        const price = parseFloat(card.querySelector('#prod-price').value);
        const cur = card.querySelector('#prod-cur').value;
        const qty = parseInt(input.value);

        // Update total price display
        const totalPrice = card.querySelector('.prodcard-price .price-html');
        totalPrice.innerText = `${cur}${(price * qty).toFixed(2)}`;

        // ✅ Update WooCommerce form's quantity field
        const hiddenQtyInput = card.querySelector('form.cart input[name="quantity"]');
        if (hiddenQtyInput) {
            hiddenQtyInput.value = qty;
        }
    }
    

   
    jQuery(document).ready(function($) {
        // Increase quantity
        $('.qty-plus').on('click', function(e) {
            e.preventDefault();
            var input = $('input[name="quantity"]'); // global selector
            var currentVal = parseInt(input.val());
            if (!isNaN(currentVal)) {
            input.val(currentVal + 1).change();
            }
        });

        // Decrease quantity
        $('.qty-minus').on('click', function(e) {
            e.preventDefault();
            var input = $('input[name="quantity"]'); // global selector
            var currentVal = parseInt(input.val());
            var min = parseInt(input.attr('min')) || 1;
            if (!isNaN(currentVal) && currentVal > min) {
            input.val(currentVal - 1).change();
            }
        });
    });

  </script>

  <script>

    document.addEventListener('DOMContentLoaded', function () {
        const swatchItems = document.querySelectorAll('.swatch-item');

        swatchItems.forEach(function (swatch) {
            swatch.addEventListener('click', function () {
            const productCard = swatch.closest('.product-card');

            if (productCard) {
                const addToCartButton = productCard.querySelector('.single_add_to_cart_button');
                const productQuantity = productCard.querySelector('.productQuantity');

                // Toggle 'active' on swatch item itself
                swatch.classList.toggle('active');

                if (addToCartButton) {
                addToCartButton.classList.toggle('active');

                // Change button text
                if (addToCartButton.classList.contains('active')) {
                    addToCartButton.textContent = 'Add to Cart';
                } else {
                    addToCartButton.textContent = 'Select Size';
                }
                }

                if (productQuantity) {
                productQuantity.classList.toggle('active');
                }
            }
            });
        });
    });


    // jQuery(document).ready(function ($) {
    //     $('.swatch-item').on('click', function () {
    //         // Reset ALL other swatches
    //         $('.swatch-item').removeClass('active');

    //         // 🔁 Reset all product cards' quantity and price to default
    //         $('li.product').each(function () {
    //             const priceWrapper = $(this).find('.price.prodcard-price');
    //             const basePrice = priceWrapper.data('base-price');
    //             priceWrapper.find('.price-html').text(basePrice);
    //             $(this).find('.quantity').val(1); // Reset quantity
    //             $(this).find('.productQuantity').removeClass('active');
    //             $(this).find('.single_add_to_cart_button').removeClass('active').text('SELECT SIZE');
    //         });

    //         // ✅ Now apply "active" state only to this card
    //         $(this).addClass('active');
    //         const productCard = $(this).closest('li.product');
    //         productCard.find('.productQuantity').addClass('active');
    //         productCard.find('.single_add_to_cart_button').addClass('active').text('Add to cart');
    //     });
    // });
    
    jQuery(document).ready(function ($) {
        $('.swatch-item').on('click', function () {

            // If swatch is disabled, stop here
            if ($(this).hasClass('disabled')) {
                return;
            }

            const productCard = $(this).closest('li.product');

            // Skip if this product card has only "Notify Me"
            if (productCard.find('.notify-me-button').length) {
                return;
            }

            // Reset swatches only for in-stock products
            $('li.product').each(function () {
                if (!$(this).find('.notify-me-button').length) {
                    $(this).find('.swatch-item').removeClass('active');

                    const priceWrapper = $(this).find('.price.prodcard-price');
                    const basePrice = priceWrapper.data('base-price');
                    priceWrapper.find('.price-html').text(basePrice);

                    $(this).find('.quantity').val(1);
                    $(this).find('.productQuantity').removeClass('active');
                    $(this).find('.single_add_to_cart_button')
                        .removeClass('active')
                        .text('SELECT SIZE');
                }
            });

            // Activate current swatch
            $(this).addClass('active');
            productCard.find('.productQuantity').addClass('active');
            productCard.find('.single_add_to_cart_button')
                    .addClass('active')
                    .text('Add to cart');
        });
    });


    // Notify user about the product.
    jQuery(document).ready(function ($) {

        let selectedProductId = null;

        // Open popup on Notify Me click
        $(document).on('click', '.notify-me-button', function () {
            selectedProductId = $(this).data('product-id');
            console.log("I got licked", selectedProductId);
            $('#notifyMeModal').fadeIn();
        });

        // Close popup
        $(document).on('click', '.close', function () {
            $('#notifyMeModal').fadeOut();
            $('#notifyMessage').text('');
            $('#notifyEmail').val('');
        });

        // Submit email
        $('#notifySubmit').on('click', function () {
            let email = $('#notifyEmail').val().trim();

            if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                $('#notifyMessage').text('Please enter a valid email.').css('color', 'red');
                return;
            }

            $.post(wc_add_to_cart_params.ajax_url, {
                action: 'add_to_notify_list',
                product_id: selectedProductId,
                email: email
            }, function (response) {
                if (response.success) {
                    $('#notifyMessage').text('You will be notified!').css('color', 'green');
                    setTimeout(() => { $('#notifyMeModal').fadeOut(); }, 1500);
                } else {
                    $('#notifyMessage').text(response.data).css('color', 'red');
                }
            });
        });
    });


    jQuery(function($) {
        $('.shop-variation-swatches').each(function () {
            const container = $(this);
            const productId = container.data('product-id');
            let variations = [];

            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                method: 'POST',
                data: {
                    action: 'get_variations_for_product',
                    product_id: productId
                },
                success: function(response) {
                    variations = response.data;
                }
            });
            container.on('click', '.swatch-item', function () {
                const swatch = $(this);
                const swatchWrapper = swatch.closest('.shop-variation-swatches');
                const productCard = swatch.closest('li.product');
                const selectedAttrs = getSelectedAttributes(swatchWrapper);
                const match = findMatchingVariation(productId, selectedAttrs, variations);

                const button = swatchWrapper.find('.single_add_to_cart_button');
                const qtyInput = productCard.find('.productQuantity input.quantity');
                const qty = parseInt(qtyInput.val()) || 1;
                const currency = productCard.find('#prod-cur').val() || '$';

                if (match) {
                    // ✅ Update hidden fields
                    swatchWrapper.find('.variation_id').val(match.variation_id);
                    swatchWrapper.find('form.cart input[name="quantity"]').val(qty);
                    swatchWrapper.find('#prod-price').val(match.display_price);

                    // ✅ Update price box
                    const total = (match.display_price * qty).toFixed(2);
                    productCard.find('.prodcard-price .price-html').html(`${currency}${total}`);

                    // ✅ Show Add to Cart button
                    swatchWrapper.find('.variation-add-to-cart').show();
                    button.addClass('active').text('Add to Cart');

                } else {
                    swatchWrapper.find('.variation_id').val('');
                    productCard.find('.prodcard-price .price-html').html('');
                    swatchWrapper.find('.variation-add-to-cart').hide();
                    button.removeClass('active').text('Select Size');
                }

                // ✅ Toggle classes
                $('.swatch-item').removeClass('active');
                swatch.addClass('active');
                $('li.product .productQuantity').removeClass('active');
                productCard.find('.productQuantity').addClass('active');
            });

            // container.on('click', '.swatch-item', function () {
            //     const swatchWrapper = $(this).closest('.shop-variation-swatches');
            //     const selectedAttrs = getSelectedAttributes(swatchWrapper);

            //     const match = findMatchingVariation(productId, selectedAttrs, variations);

            //     const button = swatchWrapper.find('.single_add_to_cart_button');

            //     if (match) {
            //         container.find('.variation_id').val(match.variation_id);

            //         // ✅ Update price HTML dynamically
            //         // container.find('.prodcard-price span').html(match.price_html);
            //         // container.find('.prodcard-price .price-html').html(match.price_html);
            //         // console.log(container.closest('li.product').find('.prodcard-price .price-html'));
                    
            //         const qtyInput = container.closest('li.product').find('.productQuantity input.quantity');
            //         const quantity = parseInt(qtyInput.val()) || 1;
            //         const unitPrice = parseFloat(match.display_price || 0);
            //         const totalPrice = (unitPrice * quantity).toFixed(2);
            //         const currency = container.closest('li.product').find('#prod-cur').val() || '$';

            //         // Update price
            //         container.closest('li.product').find('.prodcard-price .price-html').html(`${currency}${totalPrice}`);

            //         // Update hidden price field
            //         container.find('#prod-price').val(unitPrice);


            //         // Optional: update hidden field if used elsewhere
            //         container.find('#prod-price').val(match.display_price);
            //         container.find('.variation-add-to-cart').show();

            //     } else {
            //         container.find('.variation_id').val('');
            //         container.find('.prodcard-price span').html('');
            //         container.find('.variation-add-to-cart').hide();
            //     }


            //     // if (match) {
            //     //     swatchWrapper.find('.variation_id').val(match.variation_id);
            //     //     // swatchWrapper.find('.variation-price').html(match.display_price_html || '');
            //     //     // swatchWrapper.find('.variation-stock').html(match.is_in_stock ? 'In Stock' : 'Out of Stock');
            //     //     swatchWrapper.find('.variation-add-to-cart').show();
            //     //     // button.prop('disabled', false);
            //     // } else {
            //     //     swatchWrapper.find('.variation_id').val('');
            //     //     // swatchWrapper.find('.variation-price').html('');
            //     //     // swatchWrapper.find('.variation-stock').html();
            //     //     swatchWrapper.find('.variation-add-to-cart').show();
            //     //     // button.prop('disabled', true);
            //     // }
            // });
        });

        function wc_price_format(price) {
            // Customize to your locale/needs
            return parseFloat(price).toFixed(2);
        }

        function getSelectedAttributes(container) {
            const attributes = {};
            container.find('.swatch-item.active').each(function () {
                const attr = $(this).data('attribute'); // already like pa_package-size
                const val = $(this).data('value');
                attributes[attr] = val;
            });
            return attributes;
        }


        // function getSelectedAttributes(container) {
        //     const attributes = {};
        //     container.find('.swatch-item.active').each(function () {
        //         const attr = $(this).data('attribute');
        //         const val = $(this).data('value');
        //         attributes[attr] = val;
        //     });
        //     return attributes;
        // }

        // function findMatchingVariation(productId, selectedAttrs, variations) {
        //     return variations.find(function (variation) {
        //         return Object.entries(selectedAttrs).every(function ([key, value]) {
        //             return variation.attributes[key] === value;
        //         });
        //     });
        // }

        function findMatchingVariation(productId, selectedAttrs, variations) {
            return variations.find(function (variation) {
                return Object.entries(selectedAttrs).every(function ([key, value]) {
                    const attrKey = 'attribute_' + key;
                    return variation.attributes[attrKey] === value;
                });
            });
        }


    });

function fadeWooNotices() {
    const notices = document.querySelectorAll('.woocommerce-message, .woocommerce-error, .woocommerce-info');
    if (notices.length > 0) {
        setTimeout(() => {
            notices.forEach(el => {
                el.style.transition = 'opacity 1s ease-out';
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 800);
            });
        }, 4000);
    }
}

document.addEventListener('DOMContentLoaded', fadeWooNotices);

// WooCommerce AJAX triggers
jQuery(document.body).on('updated_wc_div wc_fragments_refreshed', function () {
    fadeWooNotices();
});


// // confirmation receipt and shipped receipt
// jQuery(document).ready(function($) {

//     function downloadDocument(orderId, docType) {
//         $.ajax({
//             url: wc_add_to_cart_params.ajax_url,
//             method: 'POST',
//             data: {
//                 action: 'view_order_document',
//                 order_id: orderId,
//                 doc_type: docType
//             },
//             xhrFields: {
//                 responseType: 'blob' 
//             },
//             success: function(blob) {
//                 let fileURL = URL.createObjectURL(blob);
//                 let a = document.createElement('a');
//                 a.href = fileURL;
//                 a.download = `${docType}-${orderId}.pdf`; 
//                 document.body.appendChild(a);
//                 a.click();
//                 document.body.removeChild(a);
//                 URL.revokeObjectURL(fileURL); // clean up
//             },
//             error: function(err) {
//                 console.error('Error fetching document', err);
//             }
//         });
//     }

//     // Handle Order Confirmation button
//     $(document).on('click', '#order-confirmation', function() {
//         let orderId = $(this).data('order-id');
//         console.log("order id: ", orderId);
//         downloadDocument(orderId, 'order-confirmation');
//     });

//     // Handle Shipped Receipt button
//     $(document).on('click', '#shipped-receipt', function() {
//         let orderId = $(this).data('order-id');
//         downloadDocument(orderId, 'shipped-receipt');
//     });
// });

jQuery(document).ready(function($) {

    function downloadDocument(orderId, docType, button) {
        // Disable button and show loading state
        button.prop('disabled', true).text('Loading...');

        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            method: 'POST',
            data: {
                action: 'view_order_document',
                order_id: orderId,
                doc_type: docType
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data, status, xhr) {
                const contentType = xhr.getResponseHeader('Content-Type');
                
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    // JSON error
                    const reader = new FileReader();
                    reader.onload = function () {
                        const json = JSON.parse(reader.result);
                        alert(json.confirmation_receipt?.[0] || 'No PDF available.');
                    };
                    reader.readAsText(data);
                } else {
                    // PDF download
                    const blob = new Blob([data], { type: 'application/pdf' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'order-' + orderId + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            },
            error: function(err) {
                console.error('Error fetching document', err);
                alert('Unable to fetch the document.');
            },
            complete: function() {
                // Re-enable button and reset text
                button.prop('disabled', false).text(button.data('original-text'));
            }
        });
    }

    // Handle Order Confirmation button
    $(document).on('click', '#order-confirmation', function() {
        let button = $(this);
        button.data('original-text', button.text());
        let orderId = button.data('order-id');
        downloadDocument(orderId, 'order-confirmation', button);
    });

    // Handle Shipped Receipt button
    $(document).on('click', '#shipped-receipt', function() {
        let button = $(this);
        button.data('original-text', button.text());
        let orderId = button.data('order-id');
        downloadDocument(orderId, 'shipped-receipt', button);
    });

});



</script>

<?php if (is_product()) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    


    function updateQuantity(button, change) {
        const group = button.closest('.group');
        if (!group) return;

        const qtyInput = group.querySelector('input.qty');
        if (!qtyInput) return;

        let currentVal = parseInt(qtyInput.value) || 1;
        const min = parseInt(qtyInput.getAttribute('min')) || 1;
        const max = parseInt(qtyInput.getAttribute('max')) || 9999;

        let newVal = currentVal + change;
        newVal = Math.max(min, Math.min(newVal, max));

        qtyInput.value = newVal;
        qtyInput.dispatchEvent(new Event('change'));

        updateDisplayedPrice(newVal);
    }

    function updateDisplayedPrice(quantity) {
        const priceBdi = document.querySelector('.woocommerce-variation.single_variation .price bdi');
        const variationForm = document.querySelector('.variations_form.cart');
        const variationIdInput = variationForm?.querySelector('.variation_id');

        if (!priceBdi || !variationForm || !variationIdInput) return;

        const variationId = parseInt(variationIdInput.value);
        if (!variationId || variationId === 0) return; // Variation not selected

        // Get data-product_variations (as JSON string)
        const variationDataJson = variationForm.getAttribute('data-product_variations');
        if (!variationDataJson) return;

        let variations;
        try {
            variations = JSON.parse(variationDataJson);
        } catch (e) {
            console.error('Failed to parse variation data:', e);
            return;
        }

        // Find current selected variation
        const variation = variations.find(v => parseInt(v.variation_id) === variationId);
        if (!variation) return;

        const unitPrice = parseFloat(variation.display_price || 0);
        const totalPrice = (unitPrice * quantity).toFixed(2);

        const currencySymbol = priceBdi.querySelector('.woocommerce-Price-currencySymbol')?.textContent || '$';

        // Set new price inside <bdi>
        priceBdi.innerHTML = `${currencySymbol}${totalPrice}`;
    }

    // Attach event listeners to + and - buttons
    document.querySelectorAll('.addQuntity').forEach(function (btn) {
        btn.addEventListener('click', function () {
            updateQuantity(this, 1);
        });
    });

    document.querySelectorAll('.minusQuntity').forEach(function (btn) {
        btn.addEventListener('click', function () {
            updateQuantity(this, -1);
        });
    });

    // Also watch manual input in quantity field
    document.querySelectorAll('input.qty').forEach(function (input) {
        input.addEventListener('change', function () {
            const qty = parseInt(this.value) || 1;
            updateDisplayedPrice(qty);
        });
    });

    // Ensure price updates when variation is selected
    const variationForm = document.querySelector('.variations_form.cart');
    if (variationForm) {
        variationForm.addEventListener('woocommerce_variation_has_changed', function () {
            const qty = parseInt(document.querySelector('input.qty')?.value || 1);
            updateDisplayedPrice(qty);
        });
        if (variationForm) {
            variationForm.addEventListener('show_variation', function () {
                const qty = parseInt(document.querySelector('input.qty')?.value || 1);
                updateDisplayedPrice(qty);
            });
        }

    }

    const priceContainer = document.querySelector('.woocommerce-variation.single_variation');

    if (priceContainer) {
        const observer = new MutationObserver(function (mutationsList, observerInstance) {
            // Disconnect temporarily to avoid infinite loop
            observerInstance.disconnect();

            // Wait for WooCommerce to fully inject price HTML
            setTimeout(() => {
                const qty = parseInt(document.querySelector('input.qty')?.value || 1);
                updateDisplayedPrice(qty);

                // Reconnect observer
                observerInstance.observe(priceContainer, { childList: true, subtree: true });
            }, 50); // short delay ensures DOM update is complete
        });

        observer.observe(priceContainer, { childList: true, subtree: true });
    }

});


jQuery(function($){

  // Enable/disable controls inside a given form (.variations_form or .cart)
  function setQuantityButtonsState($form, isInStock) {
    // find the add-to-cart wrapper for this form (fallback to first matching)
    var $wrap = $form.find('.woocommerce-variation-add-to-cart').first();
    if (!$wrap.length) {
      $wrap = $('.woocommerce-variation-add-to-cart').first();
    }

    // toggle wrapper class (WooCommerce uses this class when showing unavailable UI)
    $wrap.toggleClass('woocommerce-variation-add-to-cart-disabled', !isInStock);

    // quantity input and +/- buttons
    $wrap.find('input[name="quantity"]').prop('disabled', !isInStock);
    $wrap.find('.increase, .decrease').prop('disabled', !isInStock).toggleClass('disabled', !isInStock);

    // also disable the Add to cart button
    var $addBtn = $wrap.find('.single_add_to_cart_button');
    $addBtn.prop('disabled', !isInStock);
    $addBtn.toggleClass('disabled', !isInStock);
    if (!isInStock) {
      // optional: show unavailable text if you want
      // $addBtn.text('Out of stock');
      $('.singleProductContainer').addClass('outofstock');
    } else {
      // restore original text if needed (depends on your theme)
      // $addBtn.text($addBtn.data('original-text') || 'Add to cart');
      $('.singleProductContainer').removeClass('outofstock');
    }
  }

  // ===========================
  // 1) Variable products
  // ===========================
  var $variationsForm = $('form.variations_form');

  if ($variationsForm.length) {

    // Disable until a variation is selected (safe default)
    setQuantityButtonsState($variationsForm, false);

    // When a variation is found by WooCommerce
    $variationsForm.on('found_variation', function(event, variation) {
      // variation.is_in_stock is a boolean
      setQuantityButtonsState($(this), !!variation.is_in_stock);

      // update rx reduction UI (your existing logic)
      if (variation.rx_reduction) {
        $('#rxDeductionAmnt').text(variation.rx_reduction + 'g');
      } else {
        $('#rxDeductionAmnt').text('—');
      }
    });

    // When variations are reset
    $variationsForm.on('reset_data', function() {
      setQuantityButtonsState($(this), false);
      $('#rxDeductionAmnt').text('—');
    });

    // If the page already displays an availability element (some themes do),
    // set initial state accordingly (handles server-rendered selected variation)
    var $avail = $variationsForm.find('.woocommerce-variation-availability .stock');
    if ($avail.length) {
      var inStock = !$avail.hasClass('out-of-stock');
      setQuantityButtonsState($variationsForm, inStock);
    }
  }

  // ===========================
  // 2) Simple products (single)
  // ===========================
  var $simpleForm = $('form.cart').not('.variations_form');
  if ($simpleForm.length) {
    var isInStock = $('p.stock').hasClass('in-stock'); // adjust selector if theme differs
    setQuantityButtonsState($simpleForm, isInStock);
  }

  // ===========================
  // 3) Safeguard +/- handlers (delegated)
  //    they won't do anything when disabled
  // ===========================
//   $(document).on('click', '.increase', function(e) {
//     var $btn = $(this);
//     if ($btn.prop('disabled')) return;
//     var $wrap = $btn.closest('.woocommerce-variation-add-to-cart');
//     var $input = $wrap.find('input[name="quantity"]');
//     var val = parseInt($input.val()) || 1;
//     var max = parseInt($input.attr('max')) || 999;
//     if (val < max) $input.val(val + 1).trigger('change');
//   });

//   $(document).on('click', '.decrease', function(e) {
//     var $btn = $(this);
//     if ($btn.prop('disabled')) return;
//     var $wrap = $btn.closest('.woocommerce-variation-add-to-cart');
//     var $input = $wrap.find('input[name="quantity"]');
//     var val = parseInt($input.val()) || 1;
//     var min = parseInt($input.attr('min')) || 1;
//     if (val > min) $input.val(val - 1).trigger('change');
//   });

});

</script>


<?php endif; ?>
</body>
</html>
