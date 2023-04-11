<?php
/**
 * Payscout Paywire Gateway Plugin Uninstall
 *
 * @package 1.0.0
 */

/**
 * Plugin Name: Payscout Paywire Gateway
 * Plugin URI: https://www.payscout.com
 * Description: Take credit card payments using Payscout / Paywire
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// if uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Only remove ALL product and page data if WC_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'WC_REMOVE_ALL_DATA' ) && true === WC_REMOVE_ALL_DATA ) {
	// Delete options.
	delete_option( 'PAYSCOUT_PAYWIRE_GATEWAY_VERSION' );
	delete_option( 'woocommerce_payscout_settings' );
}
