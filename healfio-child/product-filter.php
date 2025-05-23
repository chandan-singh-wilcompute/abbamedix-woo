<?php
	/**
	 * Template Name: Product filter
	 *
	 * @package WordPress
	 * @subpackage Healfio-child
	 * @since Healfio 1.0
	 */

	get_header(); ?>

  <section class="woocommerce productFilterResultWrapper">
		<?php echo do_shortcode('[custom_product_filter_results]'); ?>
  </section>

	<?php
	get_footer();


  