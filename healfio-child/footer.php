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

<?php wp_footer(); ?>

<script>
    // GO Back
    document.getElementById("goback").addEventListener("click", () => {
      history.back();
    });
    
    // Quantity
    const qtyInput = document.querySelector('.productQuantity .quantity .qty');
    const increaseBtn = document.querySelector('.productQuantity .increase');
    const decreaseBtn = document.querySelector('.productQuantity .decrease');

    increaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(qtyInput.value) || 0;
      qtyInput.value = currentValue + 1;
    });

    decreaseBtn.addEventListener('click', () => {
      const currentValue = parseInt(qtyInput.value) || 0;
      const min = parseInt(qtyInput.min) || 0;
      if (currentValue > min) {
        qtyInput.value = currentValue - 1;
      }
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

    // Triggerd product size on window load
    window.addEventListener('load', function() {
        setTimeout(function() {
            const items = document.querySelectorAll('.variable-items-wrapper li');
            const lastLi = items[items.length - 1];
            if (lastLi) {
                lastLi.click();
                console.log('Last <li> clicked');
            } else {
                console.log('No <li> found');
            }
        }, 500); // 0.5 second delay
    });


    jQuery(document).ready(function ($) {
        $('.swatch-item').on('click', function () {
            $('.swatch-item').removeClass('active');
            $('li.product .productQuantity').removeClass('active');
            $('li.product .single_add_to_cart_button').removeClass('active').text('SELECT SIZE');
            
            $(this).addClass('active');            
            $(this).parents('.shop-variation-swatches').find('.single_add_to_cart_button').addClass('active');
            $(this).parents('li.product').find('.productQuantity').addClass('active');

            $(this).parents('.shop-variation-swatches').find('.single_add_to_cart_button').text('Add to cart');
           
        });


        // $(".shop-variation-swatches").mouseover(function(){
        //   $(".woocommerce-LoopProduct-link").css("pointer-events", "none");
        // });

        // $(".shop-variation-swatches").mouseleave(function(){
        //   $(".woocommerce-LoopProduct-link").css("pointer-events", "all");
        // });
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
                const swatchWrapper = $(this).closest('.shop-variation-swatches');
                const selectedAttrs = getSelectedAttributes(swatchWrapper);
                const match = findMatchingVariation(productId, selectedAttrs, variations);

                const button = swatchWrapper.find('.single_add_to_cart_button');


                if (match) {
                    swatchWrapper.find('.variation_id').val(match.variation_id);
                    // swatchWrapper.find('.variation-price').html(match.display_price_html || '');
                    // swatchWrapper.find('.variation-stock').html(match.is_in_stock ? 'In Stock' : 'Out of Stock');
                    swatchWrapper.find('.variation-add-to-cart').show();
                    // button.prop('disabled', false);
                } else {
                    swatchWrapper.find('.variation_id').val('');
                    // swatchWrapper.find('.variation-price').html('');
                    // swatchWrapper.find('.variation-stock').html();
                    swatchWrapper.find('.variation-add-to-cart').show();
                    // button.prop('disabled', true);
                }
            });
        });

        function getSelectedAttributes(container) {
            const attributes = {};
            container.find('.swatch-item.active').each(function () {
                const attr = $(this).data('attribute');
                const val = $(this).data('value');
                attributes[attr] = val;
            });
            return attributes;
        }

        function findMatchingVariation(productId, selectedAttrs, variations) {
            return variations.find(function (variation) {
                return Object.entries(selectedAttrs).every(function ([key, value]) {
                    return variation.attributes[key] === value;
                });
            });
        }
    });





  </script>

</body>
</html>
