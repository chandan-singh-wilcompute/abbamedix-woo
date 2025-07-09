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

<div class="container myProfile">
	<h1>MY PROFILE</h1>
	<div class="row">
		<div class="col-md-6">
			<div class="group1">
				<h5 class="mt-0">PERSONAL INFORMATION</h5>
				<p><strong>Client name</strong> Cindi Jones</p>
				<p><strong>Client ID</strong> 1234-1234-1234</p>
				<p><strong>Phone Number</strong> (905) 123-1234</p>
				<p><strong>Date of Birth</strong> 1975-01-01</p>
				<p><strong>Email address</strong> souryoli@hotmail.com </p>
				<p><strong>Registration Date</strong> 2020-10-10</p>

				<p><strong>Prescription available until:</strong> 2025-09-06</p>
				<p><strong>Prescription</strong> - 5g/day	</p>
				<p><strong>Amount available for order</strong>- 5g/day	
					- Now 150 grams<br>
					- 2025-06-01 150 grams
				</p>
			</div>

			<div class="group2">				
				<h6>SHIPPING ADDRESS</h6>
				<p style="margin-top: 5px" class="address">
					123 Rue de Verdeun<br>
					Montreal, Qc H4G 1J9
				</p>

				<p class="mb-0"><span class="badgeApproved">Approved</span></p>
				
				<h6>CREDIT CARDS</h6>
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
				<h5 class="mt-0">SCRIPT HISTORY</h5>
				
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
