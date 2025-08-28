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


$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get custom user meta
$phone_number        = get_user_meta($user_id, 'phone_number', true);
$date_of_birth       = get_user_meta($user_id, 'date_of_birth', true);
$prescription        = get_user_meta($user_id, 'prescription_text', true);
$prescription_until  = get_user_meta($user_id, 'prescription_until', true);
$order_amount        = get_user_meta($user_id, 'order_amount_available', true);
$shipping_address    = get_user_meta($user_id, 'shipping_address', true); // Or use WooCommerce address function below

$prescriptions = Ample_Session_Cache::get('prescriptions');
$prescriptions = array_reverse($prescriptions);
$current_prescription = Ample_Session_Cache::get('current_prescription');
$credit_cards = Ample_Session_Cache::get('credit_cards');
$needs_renewal = Ample_Session_Cache::get('needs_renewal', false);
$status = Ample_Session_Cache::get('status', false);
?>

<div class="container myProfile">
	<h1>MY PROFILE</h1>
	<div class="row">
		<div class="col-md-6">
			<div class="group1">
				<h5 class="mt-0">PERSONAL INFORMATION</h5>
				<p><strong>Client Name</strong> <?php echo esc_html($current_user->display_name); ?></p>
				<p><strong>Client ID</strong> <?php echo esc_html($user_id); ?></p>
				<p><strong>Phone Number</strong> <?php echo esc_html($phone_number); ?></p>
				<p><strong>Date of Birth</strong> <?php echo esc_html($date_of_birth); ?></p>
				<p><strong>Email Address</strong> <?php echo esc_html($current_user->user_email); ?></p>
			</div>

			<div class="group2">	
				<h5 class="mt-0">PERSONAL INFORMATION</h5>
				<p><span class="badgeApproved"><?php echo $status; ?></span></p>
				<p><strong>Registration Date</strong> <?php echo esc_html($current_user->user_registered); ?></p>
				<button id="registrationDcoument" class="registrationDcoument"><i class="bi bi-cloud-arrow-down-fill"></i> &nbsp; Registration Document</button>
				<?php if($needs_renewal) : ?>
					<a id="renewalFormBtn" class="renewalFormBtn" href="<?php bloginfo('url'); ?>/my-account/renewal"><i class="bi bi-repeat"></i> &nbsp; Renewal Form</a>
				<?php else : ?>
					<a id="renewalFormBtn" class="renewalFormBtn" href="<?php bloginfo('url'); ?>/my-account/renewal"><i class="bi bi-repeat"></i> &nbsp; Renewal Form</a>
				<?php endif; ?>
			</div>
			
			<div class="group3">
				<h6>CREDIT CARDS <a href="<?php bloginfo('url'); ?>/my-account/manage-card" class="btnManageCard">Manage Cards</a> </h6>
				
			<?php 	
				
				if (empty($credit_cards)) {
					echo '<div class="ccinfoContainer"><p class="alert alert-info">No saved cards found.</p></div>';
				} else {
					foreach ($credit_cards as $credit_card) {
						echo '<div class="ccinfoContainer">';
						echo '<span><label>' . esc_html($credit_card['brand']) . '</label><br><label>' . esc_html($credit_card['protected_card_number']) . '</label></span>';
						echo '<span>Expiry: ' . esc_html($credit_card['expiry']) . '</span>';
						echo '<span><a role="button" class="btnCardRemove"><i class="bi bi-x"></i></a></span>';
						echo '</div>';
					}
				}
			?>
			</div>
		</div>

		<div class="col-md-6 prescriptionInformation">
			<div class="group1">
				<h5 class="mt-0">PRESCRIPTION Information</h5>
				<p><strong>Prescription</strong> <?php echo $current_prescription['number_of_grams'] ? esc_html($current_prescription['number_of_grams']) . 'g / day' : 'NA'; ?></p>
				<p><strong>Amount Available for Order</strong> 
				<ul>
					<li>
						Now : <?php echo $current_prescription['available_to_order'] ? esc_html($current_prescription['available_to_order']) . ' grams' : 'NA'; ?></p>
					</li>
					<li>
						<?php echo array_key_first($current_prescription['available_to_order_future']); ?> : <?php echo $current_prescription['available_to_order'] ? esc_html($current_prescription['available_to_order']) . ' grams' : 'NA'; ?></p>
					</li>
				</ul>
				<p><strong>Prescription Available Until</strong> <?php echo $current_prescription['script_end'] ? esc_html($current_prescription['script_end']) : 'NA'; ?></p>
			</div>
			
			<h6>SHIPPING ADDRESS</h6>

				<!-- <p style="margin-top: 5px" class="address"><strong>Shipping Address:</strong><br> -->
					<?php
					if (function_exists('wc_get_account_formatted_address')) {
							echo wc_get_account_formatted_address('shipping');
					} else {
							echo esc_html($shipping_address); // fallback
					}
					?>
			</p>

		</div>
	</div>
</div>

<div class="scriptHistoryWrapper">
	<div class="container">
		<h6 class="mt-0">SCRIPT HISTORY</h6>
		<div class="row">
			<div class="col-md-6">
				<div class="prescription-wrapper">
					<?php foreach ($prescriptions as $pres) : 
							if ($pres['is_current']) :	
					?>
								<div class="white-card">
									<div>
										<strong>Physician name:</strong> <?php echo $pres['physician_name']; ?><br>
										<strong>End Date:</strong> <?php echo $pres['script_end']; ?><br>
										<span><i class="verified"></i><?php echo $pres['verified'] ? 'Verified' : 'Not Verified'; ?></span>
									</div>
									<div class="card-col-right">
										<span class="badge-active">ACTIVE</span>
									</div>
								</div>




					<?php 	else :	?>
								<div class="grey-card">
									<div>
										<strong>Physician name:</strong> <?php echo $pres['physician_name']; ?><br>
										<strong>End Date:</strong> <?php echo $pres['script_end']; ?><br>
										<span><i class="verified"></i><?php echo $pres['verified'] ? 'Verified' : 'Not Verified'; ?></span>
									</div>
									<div class="card-col-right">
										<span class="badge-inactive">ACTIVE</span>
									</div>
								</div>
					<?php 	endif;
							
						endforeach;
						
					?>

					<!-- <?php // echo do_shortcode('[user_order_history]') ;?>	 -->
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
