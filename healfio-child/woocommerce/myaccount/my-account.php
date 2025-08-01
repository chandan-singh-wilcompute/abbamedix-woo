<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<?php
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get custom user meta
$phone_number        = get_user_meta($user_id, 'phone_number', true);
$date_of_birth       = get_user_meta($user_id, 'date_of_birth', true);
$prescription        = get_user_meta($user_id, 'prescription_text', true);
$prescription_until  = get_user_meta($user_id, 'prescription_until', true);
$order_amount        = get_user_meta($user_id, 'order_amount_available', true);
$shipping_address    = get_user_meta($user_id, 'shipping_address', true); // Or use WooCommerce address function below

?>

<div class="container myProfile">
	<h1>MY PROFILE</h1>
	<div class="row">
		<div class="col-md-6">
			<div class="group1">
				<h5 class="mt-0">PERSONAL INFORMATION</h5>
				<p><strong>Client Name:</strong> <?php echo esc_html($current_user->display_name); ?></p>
				<p><strong>Client ID:</strong> <?php echo esc_html($user_id); ?></p>
				<p><strong>Phone Number:</strong> <?php echo esc_html($phone_number); ?></p>
				<p><strong>Date of Birth:</strong> <?php echo esc_html($date_of_birth); ?></p>
				<p><strong>Email Address:</strong> <?php echo esc_html($current_user->user_email); ?></p>
				<p><strong>Registration Date:</strong> <?php echo esc_html($current_user->user_registered); ?></p>
				<p><strong>Prescription:</strong> <?php echo esc_html($prescription); ?></p>
				<p><strong>Prescription Available Until:</strong> <?php echo esc_html($prescription_until); ?></p>
				<p><strong>Amount Available for Order:</strong> <?php echo esc_html($order_amount); ?></p>
			</div>

			<div class="group2">				
				<h6>SHIPPING ADDRESS</h6>

				<p style="margin-top: 5px" class="address"><strong>Shipping Address:</strong><br>
					<?php
					if (function_exists('wc_get_account_formatted_address')) {
							echo wc_get_account_formatted_address('shipping');
					} else {
							echo esc_html($shipping_address); // fallback
					}
					?>
			</p>

				<p class="mb-0"><span class="badgeApproved">Approved</span></p>
				
				<h6>CREDIT CARDS <a href="<?php bloginfo('url'); ?>/my-account/manage-card" class="btnManageCard">Manage Cards</a> </h6>
				<div class="ccinfoContainer mb-5">
					<span class="visaCard"></span>
					<span><label>Visa</label><br><label>4222****8428</label>
					</span>
					<span>Expiry: 07-2026</span>
					<span><a role="button" class="btnCardRemove"><i class="bi bi-x"></i></a></span>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="scriptHistoryWrapper">
				<h5 class="mt-0">ORDER HISTORY</h5>
				
				<div class="prescription-wrapper">
					<div class="white-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right">
							<span class="badge-active">ACTIVE</span>
						</div>
					</div>
					<div class="grey-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right">
							<span class="badge-inactive">ACTIVE</span>
						</div>
					</div>			
					<div class="grey-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right">
							<span class="badge-inactive">ACTIVE</span>
						</div>
					</div>												
					<div class="grey-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right">
							<span class="badge-inactive">ACTIVE</span>
						</div>
					</div>												
					<div class="grey-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right">
							<span class="badge-inactive">ACTIVE</span>
						</div>
					</div>		
					<div class="grey-card">
						<div>
							<strong>Physician name:</strong> Karen Wallace<br>
							<strong>End Date:</strong> 2025-09-06<br>
							<span><i class="verified"></i>Verified</span>
						</div>
						<div class="card-col-right" >
							<span class="badge-inactive">ACTIVE</span>
						</div>
					</div>													
				</div>
			</div>
		</div>
	</div>
</div>



<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
