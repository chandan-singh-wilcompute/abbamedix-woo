<?php
	/**
	 * Template Name: Brand Filter
	 *
	 * @package WordPress
	 * @subpackage Healfio-child
	 * @since Healfio 1.0
	 */

	get_header(); ?>

<!-- <section class="woocommerce productFilterResultWrapper">
		<?php //echo do_shortcode('[my_custom_filter]'); ?>

		<?php //echo do_shortcode('[wc_ordering_dropdown]'); ?>

		<?php //echo do_shortcode('[custom_product_filter_results]'); ?>
</section> -->

	<section class="woocommerce productFilterResultWrapper">
		<?php echo do_shortcode('[my_custom_filter]');?>
		
		<?php // echo do_shortcode('[products paginate="true" columns="4" per_page="12"]');
			echo do_shortcode('[custom_brand_filter_results]');
		?>	

	</section>


	<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/product-filter-dropdown.js"></script>
	<script>
		// jQuery(document).ready(function ($) {
		// 	$('.productFilter').insertAfter('.titleWrapper');
		// });

	</script>

	<?php
	get_footer();