<?php
/**
 * Plugin Name: Payscout Paywire Gateway
 * Plugin URI: https://www.payscout.com
 * Description: Take credit card payments using Payscout / Paywire
 * Author: Payscout
 * Author URI: https://www.payscout.com/
 * Contributors: Arcane Strategies
 * Version: 1.0.0
 * Requires at least: 5.5.0
 * Tested up to: 5.9.2
 * WC requires at least: 5.1.0
 * WC tested up to: 6.3.1
 * Text Domain: payscout-gateway
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'payscout_payment_init', 11 );
add_filter( 'woocommerce_payment_gateways', 'payscout_add_payment_gateway' );
add_filter( 'woocommerce_currencies', 'payscout_add_world_currencies' );
add_filter( 'woocommerce_currency_symbol', 'payscout_add_world_currencies_symbol', 10, 2 );
add_action( 'wp_enqueue_scripts', 'payscout_disable_woocommerce_loading_js' );

add_action('payscout_reconcile_cron_hook', 'payscout_update_order_statuses');
register_activation_hook( __FILE__, 'payscout_reconcile_order_statuses_activate' );

function payscout_payment_init(){

	define( 'PAYSCOUT_PAYWIRE_GATEWAY_VERSION', '2.2.3' );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_MIN_PHP_VER', '7.3.0' );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_MIN_WC_VER', '5.1.0' );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_API_TEST', 'https://dbstage1.paywire.com/pwapi/epf/' );
	define( 'PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE', 'https://dbtranz.paywire.com/pwapi/epf/' );
	
	class WC_Payment_Gateway_Init {
		
			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			public function __wakeup() {}

			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() {
				register_deactivation_hook( __FILE__, [$this,'reconcile_order_statuses_deactivate'] ); 				
				
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}
			
			/**
			 * Updates the plugin version in db
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function update_plugin_version() {
				delete_option( 'PAYSCOUT_PAYWIRE_GATEWAY_VERSION' );
				update_option( 'PAYSCOUT_PAYWIRE_GATEWAY_VERSION', (defined('PAYSCOUT_PAYWIRE_GATEWAY_VERSION')? PAYSCOUT_PAYWIRE_GATEWAY_VERSION : '1.0.0') );
			}

			/**
			 * Handles upgrade routines.
			 *
			 * @since 1.0.0
			 * @version 1.0.0
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}

				if ( ! defined( 'IFRAME_REQUEST' ) && defined('PAYSCOUT_PAYWIRE_GATEWAY_VERSION') && ( PAYSCOUT_PAYWIRE_GATEWAY_VERSION !== get_option( 'PAYSCOUT_PAYWIRE_GATEWAY_VERSION' ) ) ) {
					do_action( 'payscout_paywire_gateway_updated' );

					if ( ! defined( 'PAYSCOUT_PAYWIRE_GATEWAY_INSTALLING' ) ) {
						define( 'PAYSCOUT_PAYWIRE_GATEWAY_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}
			
			public function init(){
				add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), [$this, 'settings_link'] );
				
				if( class_exists( 'WC_Payment_Gateway' ) ){
					require_once plugin_dir_path(__FILE__) . '/includes/class-wc-payment-gateway-payscout-api.php';
					require_once plugin_dir_path(__FILE__) . '/includes/class-wc-payment-gateway-payscout-gateway.php';
					require_once plugin_dir_path(__FILE__) . '/includes/payscout-order-statuses.php';
					require_once plugin_dir_path(__FILE__) . '/includes/payscout-output.php';
				}
			}
			
			public function settings_link( array $links ) {
				$url = get_admin_url() . "admin.php?page=wc-settings&tab=checkout&section=payscout";
				$settings_link = '<a href="' . $url . '">' . __('Settings', 'payscout-gateway') . '</a>';
				$links[] = $settings_link;
				return $links;
			}

			public function reconcile_order_statuses_deactivate() {
				$timestamp = wp_next_scheduled( 'payscout_reconcile_cron_hook' );
				wp_unschedule_event( $timestamp, 'payscout_reconcile_cron_hook' );
			}
	}
		
	WC_Payment_Gateway_Init::get_instance();
	
}

function payscout_reconcile_order_statuses_activate() {
	if ( ! wp_next_scheduled( 'payscout_reconcile_cron_hook' ) ) {
		wp_schedule_event( time(), 'daily', 'payscout_reconcile_cron_hook' );
	}
}

function payscout_update_order_statuses(){
	return WC_Payscout_Paywire_Gateway::update_order_statuses();
}

function payscout_add_payment_gateway( $gateways ){
	$gateways[] = 'WC_Payscout_Paywire_Gateway';
	return $gateways;
}

function payscout_add_world_currencies( $currencies ){
	
	$currencies['USD'] = __( 'US Dollar', 'ocotw' );
	
	return $currencies;
	
}

function payscout_add_world_currencies_symbol( $currency_symbol, $currency ){
	switch($currency) {
		case 'USD':
			$currency_sumbol = 'USD';
		break;
	}
	return $currency_symbol;
}

function payscout_disable_woocommerce_loading_js() {
 
}