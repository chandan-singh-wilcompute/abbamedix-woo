<?php
	/**
	 * Template Name: Product filter
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

		<div class="titleWrapper">
				<div class="container-fluid">
						<a id="goback" class="backBtn">
						Back
				</a>
				<?php
						$slug_string = get_query_var('filter_slugs', '');
						$slug_string = urldecode($slug_string); // Decode URL-encoded characters first
						$slug_string = str_replace(' ', '-', $slug_string); // Replace spaces with hyphens
						$slugs = explode('+', sanitize_text_field($slug_string));
						echo '<h5>' . esc_html($slug_string) . '</h5>';
					?>
				</div>
		</div>

		<?php echo do_shortcode('[my_custom_filter]');?>

		<div class="container-fluid">
				<?php echo do_shortcode('[products paginate="true" columns="4" per_page="12"]');?>	
		</div>

	</section>


	<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/product-filter.js"></script>
	<script>
		jQuery(document).ready(function ($) {
			$('.productFilter').insertAfter('.titleWrapper');
  	});

	</script>

	<?php
	get_footer();


  