<?php
	/**
	 * Template Name: View all
	 *
	 * @package WordPress
	 * @subpackage Healfio-child
	 * @since Healfio 1.0
	 */

	get_header(); ?>

  <section class="woocommerce viewAllProducts">
		<div class="titleWrapper">
				<div class="container-fluid">
						<a id="goback" class="backBtn">
						Back
				</a>
				<h5>View All Products</h5>
				</div>
		</div>


		<div class="container-fluid woocommerce-products">

			<?php echo do_shortcode('[my_custom_filter]');?>

			<?php echo do_shortcode('[products paginate="true" columns="4" per_page="12"]');?>	

			<?php //echo do_shortcode('[products limit="-1" columns="4" orderby="title"]');?>			

		</div>
		
  </section>

	<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/product-filter.js"></script>
	<?php
	get_footer();


  