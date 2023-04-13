<?php
/**
 * Payscout Paywire Gateway API
 *
 * @package 1.0.0
 * @class       WC_Payscout_Paywire_API
 * @version     1.0.0
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
class WC_Payscout_Paywire_API {

	/**
	 * Public key.
	 *
	 * @var string
	 */
	public $public_key = null;

	/**
	 * Secret key (do not expose to client side).
	 *
	 * @var string
	 */
	public $secret_key = null;

	/**
	 * Client secret key for a given payment intent (can be exposed to client side).
	 *
	 * @var string
	 */
	public $client_secret = null;

	/**
	 * Constructor sets key values fom array params.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param array $keys key value pair of API keys.
	 */
	public function __construct( array $keys ) {
		if ( ! empty( $keys ) ) {
			foreach ( $keys as $index => $value ) {
				$this->$index = $value;
			}
		}
	}

	/**
	 * Captures a confirmed payment intent
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $id payment intent id.
	 * @param array  $post post data for payload body.
	 */
	public static function capture_payment_intent( $id, $post = array() ) {
		$path            = 'payment_intents/' . $id . '/capture';
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post(
				$host . $path,
				array(
					'body'    => wp_json_encode( $post ),
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);

			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			}
		}

		return null;
	}

	/**
	 * Confirms a payment intent
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $id payment intent id.
	 * @param array  $post post data for payload body.
	 */
	public static function confirm_payment_intent( $id, $post = array() ) {
		$path            = 'payment_intents/' . $id . '/confirm';
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post(
				$host . $path,
				array(
					'body'    => wp_json_encode( $post ),
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);

			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			} elseif ( is_array( $response ) && isset( $response['body'] ) && isset( json_decode( $response['body'] )->error ) && isset( json_decode( $response['body'] )->error->message ) && ( json_decode( $response['body'] )->error->message === 'Invalid payment intent status: processing' || json_decode( $response['body'] )->error->message === 'Invalid payment intent status: succeeded' ) ) {
				return self::get_payment_intent( $id );
			}
		}

		return null;
	}

	/**
	 * Creates a payment intent
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param array $post post data for payload body.
	 */
	public static function create_payment_intent( $post ) {
		$path            = 'payment_intents';
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post(
				$host . $path,
				array(
					'body'    => wp_json_encode( $post ),
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);
			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			}
		}
		return null;
	}

	/**
	 * Updates a payment intent with new data as permitted.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $id payment intent id.
	 * @param array  $post post data for payload body.
	 */
	public static function update_payment_intent( $id, $post ) {
		$path            = 'payment_intents/' . $id;
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post(
				$host . $path,
				array(
					'body'    => wp_json_encode( $post ),
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);
			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			} elseif ( is_array( $response ) && isset( $response['body'] ) && isset( json_decode( $response['body'] )->error ) && isset( json_decode( $response['body'] )->error->message ) && ( json_decode( $response['body'] )->error->message === 'Invalid payment intent status: processing' || json_decode( $response['body'] )->error->message === 'Invalid payment intent status: succeeded' ) ) {
				return self::get_payment_intent( $id );
			}
		}

		return null;
	}

	/**
	 * Retreives a payment intent by id.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $id payment intent id.
	 */
	public static function get_payment_intent( $id ) {
		$path            = 'payment_intents/' . $id;
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_get(
				$host . $path,
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);

			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			}
		}

		return null;
	}

	/**
	 * Updates a payment method with new data as permitted.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $id payment method id.
	 * @param array  $post post data for payload body.
	 */
	public static function update_payment_method( $id, $post ) {
		$path            = 'payment_methods/' . $id;
		$plugin_settings = get_option( 'woocommerce_payscout_settings' );

		$secret_key = ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? $plugin_settings['secret_key'] : ( ! empty( $plugin_settings['secret_test'] ) ? $plugin_settings['secret_test'] : '' );

		if ( ! empty( $secret_key ) ) {
			$host = ( ( isset( $plugin_settings['livemode'] ) && 'yes' === $plugin_settings['livemode'] ) ? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post(
				$host . $path,
				array(
					'body'    => wp_json_encode( $post ),
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $secret_key,
					),
				)
			);

			if ( ( ! $response instanceof WP_Error ) && 200 === $response['response']['code'] ) {
				return $response;
			}
		}

		return null;
	}
}
