<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="col2-set" id="customer_details">
			<div class="col-1">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

  <a id="local-pickup-link" href="https://www.google.com/maps/place/Drukkersweg+14,+2031+EE+Haarlem,+Netherlands/@52.3991484,4.6576888,340m/data=!3m1!1e3!4m15!1m8!3m7!1s0x47c5ef8040ed48b3:0x558491be95398e63!2sDrukkersweg+14,+2031+EE+Haarlem,+Netherlands!3b1!8m2!3d52.3989582!4d4.6594854!16s%2Fg%2F11q2ndfcr3!3m5!1s0x47c5ef8040ed48b3:0x558491be95398e63!8m2!3d52.3989582!4d4.6594854!16s%2Fg%2F11q2ndfcr3?entry=ttu&g_ep=EgoyMDI1MTAyMi4wIKXMDSoASAFQAw%3D%3D" target="_blank"></a>
	<script>
		jQuery(function($){
			// When the billing Google Map field is clicked, trigger a click on the local pickup link
			$('#billing_google_map_field').on('click', function(e){
				e.preventDefault();
				$('#local-pickup-link')[0].click();
			});
		});
	</script>
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	
	<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'woocommerce' ); ?></h3>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
