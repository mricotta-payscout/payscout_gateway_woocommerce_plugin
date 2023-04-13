<?php
/**
 * Payment Method Initial Render
 *
 * @package 1.0.0
 */

/**
 * Plugin Name: Payscout Paywire Gateway
 * Plugin URI: https://www.payscout.com
 * Description: Take credit card payments using Payscout via the Paywire API
 * Author: Payscout
 * Author URI: https://www.payscout.com/
 * Contributors: Arcane Strategies
 * Version: 1.0.0
 * Requires at least: 5.5.0
 * Tested up to: 6.2
 * WC requires at least: 5.1.0
 * WC tested up to: 7.5.1
 * Text Domain: payscout-gateway
 * Domain Path: /languages
 */

add_filter( 'woocommerce_gateway_description', 'payscout_gateway_mount_point', 20, 2 );
add_filter( 'woocommerce_checkout_process', 'payscout_gateway_validate', 20, 2 );

/**
 * Renders the payscout gateway container
 *
 * @since 1.0.0
 * @version 1.0.0
 * @param string $description echod html from Woocommerce which we are hooking into.
 * @param string $payment_id unique payment intent id.
 * @return string
 */
function payscout_gateway_mount_point( $description, $payment_id ) {
	if ( 'payscout' !== $payment_id ) {
		return $description;
	}

	ob_start();

	echo '<div id="payscout_paywire_gateway_container"><button id="reloadButton" type="button" class="button" onClick="window.location.reload()">Form Not Loading? Wait 10 Seconds Then Click Here</button></div><div id="payscout_paywire_gateway_message" class="alert">&nbsp;</div>';

	$description .= ob_get_clean();

	return $description;
}

/**
 * Handles server-side validation.  Version 1.0.0 is client-side.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function payscout_gateway_validate() {
	// Do nothing.  Staged for future roll-outs.
}
