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

</body>
</html>
