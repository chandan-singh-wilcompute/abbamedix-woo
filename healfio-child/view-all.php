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
		<div class="container-fluid woocommerce-products">

		<?php echo do_shortcode('[products paginate="true" columns="4" per_page="12"]');?>	

		<?php //echo do_shortcode('[products limit="-1" columns="4" orderby="title"]');?>			

	</div>
		
  </section>

	<?php
	get_footer();


  