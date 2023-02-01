<?php
/**
 * Payscout Gateway
 *
 * Provides a Payscout or Paywire Payment Gateway.
 *
 * @class       WC_Payscout_Paywire_Gateway
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce\Classes\Payment
 */
if(!class_exists('WC_Payscout_Paywire_Gateway')){
	class WC_Payscout_Paywire_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			
			$this->force_guest_session();
			
			// Setup general properties.
			$this->setup_properties();
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Get settings.
			$this->title              = $this->settings[ 'title' ];
			$this->description        = $this->settings[ 'description'];
			$this->style              = $this->get_option( 'style' , '');
			$this->instructions       = $this->settings[ 'instructions' ];
			$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
			$this->client_secret      = $this->make_client_secret();
			$this->livemode			  = $this->get_option( 'livemode', 'no' );
			
			if($this->livemode === 'yes'){
				$this->public_key         = $this->get_option( 'public_key' , '');
				$this->secret_key         = $this->get_option( 'secret_key' , '');
				$this->script_library = 'https://dbtranz.paywire.com/epf/library/embed.js';
			} else {
				$this->public_key         = $this->get_option( 'public_test' , '');
				$this->secret_key         = $this->get_option( 'secret_test' , '');
				$this->script_library = 'https://dbstage1.paywire.com/epf/library/embed.js';
				//$this->script_library = 'http://www.paywire.com/library/embed.js';
			}

			// Actions.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

			// Customer Emails.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			
			if(!empty($this->public_key) && !empty($this->client_secret)){
				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			}
		}
		
		// Add call back function for sessions.
		public function force_guest_session() {
			if (is_user_logged_in() || is_admin()) {
				return;
			}
			if (isset(WC()->session)) {
				if (!WC()->session->has_session()) {
					WC()->session->set_customer_session_cookie(true);
				}
			}
		}

		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                 = 'payscout';
			$this->icon               = apply_filters( 'woocommerce_payscout_icon', plugins_url('../assets/logo.svg', __FILE__ ));
			$this->method_title       = __( 'Payscout', 'payscout-gateway' );
			$this->method_description = __( 'Have your customers pay with Payscout or Paywire.', 'payscout-gateway' );
			$this->has_fields         = false;
		}
		
		/**
		 *
		 */
		public function payment_scripts() {
			$params = [
				'key'	=> $this->public_key,
				'style'	=> $this->style,
				'client_secret' => $this->client_secret
			];
			
			if(function_exists( 'is_pos' ) && is_pos()){
				$this->echo_scripts($params);
			} else {
				$this->enqueue_scripts($params);
			}
			$this->tokenization_script();
		}
		
		private function format_js( $script ) {
			if ( substr( $script, 0, 7 ) === '<script' )
			  return $script;

			if ( substr( $script, 0, 4 ) === 'http' )
			  return '<script src="' . $script . '"></script>';

			return '<script>' . $script . '</script>';
		}
		
		public function enqueue_scripts($params){
			wp_register_style('payscout', PAYSCOUT_PAYWIRE_GATEWAY_PLUGIN_URL . '/assets/css/payscout.css');
			wp_enqueue_style('payscout');
			wp_register_script('payscout', $this->script_library, '', '1.0', true);
			wp_enqueue_script('payscout');
			wp_register_script('payscout_native', PAYSCOUT_PAYWIRE_GATEWAY_PLUGIN_URL . '/assets/js/card.js?ver='.(defined('PAYSCOUT_PAYWIRE_GATEWAY_VERSION')? PAYSCOUT_PAYWIRE_GATEWAY_VERSION : '1.0.0'), ['payscout'], PAYSCOUT_PAYWIRE_GATEWAY_VERSION, true);
			wp_enqueue_script('payscout_native');
			wp_localize_script('payscout_native', 'wc_payment_gateway_params', apply_filters('wc_payment_gateway_params',$params));
		}
		
		public function echo_scripts($params){
			$scripts = [
							$this->script_library,
							PAYSCOUT_PAYWIRE_GATEWAY_PLUGIN_URL . '/assets/js/card.js?ver='.(defined('PAYSCOUT_PAYWIRE_GATEWAY_VERSION')? PAYSCOUT_PAYWIRE_GATEWAY_VERSION : '1.0.0'),
							'var wc_payment_gateway_params = '.json_encode($params)
			];
			foreach ( $scripts as $script ) {
			  echo $this->format_js( trim( $script ) ) . "\n";
			}
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'       => __( 'Enable/Disable', 'payscout-gateway' ),
					'label'       => __( 'Enable payment via Payscout for Paywire', 'payscout-gateway' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'livemode'            => array(
					'title'       => __( 'Live/Test', 'payscout-gateway' ),
					'label'       => __( 'Enable live mode via Payscout for Paywire', 'payscout-gateway' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no',
				),
				'title'              => array(
					'title'       => __( 'Title', 'payscout-gateway' ),
					'type'        => 'safe_text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'payscout-gateway' ),
					'default'     => __( 'Payscout', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'public_key'              => array(
					'title'       => __( 'Public Key', 'payscout-gateway' ),
					'type'        => 'text',
					'description' => __( 'Public key that will be used to authenticate API calls', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'secret_key'              => array(
					'title'       => __( 'Secret Key', 'payscout-gateway' ),
					'type'        => 'password',
					'description' => __( 'Secret key that will be used to authenticate API calls', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'public_test'              => array(
					'title'       => __( 'Public Test Key', 'payscout-gateway' ),
					'type'        => 'text',
					'description' => __( 'Public test key that will be used to authenticate API calls', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'secret_test'              => array(
					'title'       => __( 'Secret Test Key', 'payscout-gateway' ),
					'type'        => 'password',
					'description' => __( 'Secret test key that will be used to authenticate API calls', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'description'        => array(
					'title'       => __( 'Description', 'payscout-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website.', 'payscout-gateway' ),
					'default'     => __( 'Pay with Credit Card', 'payscout-gateway' ),
					//'default'     => __( 'Pay with Credit or ACH', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'style'        => array(
					'title'       => __( 'Style Object', 'payscout-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Create your style object with quote-encapsulated keys in accordance with Payscout installation instructions.', 'payscout-gateway' ),
					'default'     => __( '{"base": {"color": "inherit","fontSmoothing": "antialiased","input":{"padding":"10px 0"}},"invalid": {"color": "inherit"}}', 'payscout-gateway' ),
					//'default'     => __( 'Pay with Credit or ACH', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'instructions'       => array(
					'title'       => __( 'Instructions', 'payscout-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page.', 'payscout-gateway' ),
					'default'     => __( 'Pay with Credit Card.', 'payscout-gateway' ),
					'desc_tip'    => true,
				),
				'enable_for_methods' => array(
					'title'             => __( 'Enable for shipping methods', 'payscout-gateway' ),
					'type'              => 'multiselect',
					'class'             => 'wc-enhanced-select',
					'css'               => 'width: 400px;',
					'default'           => '',
					'description'       => __( 'If Payscout is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'payscout-gateway' ),
					'options'           => $this->load_shipping_method_options(),
					'desc_tip'          => true,
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select shipping methods', 'payscout-gateway' ),
					),
				),
				'enable_for_virtual' => array(
					'title'   => __( 'Accept for virtual orders', 'payscout-gateway' ),
					'label'   => __( 'Accept Payscout if the order is virtual', 'payscout-gateway' ),
					'type'    => 'checkbox',
					'default' => 'yes',
				),
			);
		}

		/**
		 * Check If The Gateway Is Available For Use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$order          = null;
			$needs_shipping = false;

			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				if ( $order && 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			}

			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

			// Virtual order, with virtual disabled.
			if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
				return false;
			}

			// Only apply if all packages are being shipped via chosen method, or order is virtual.
			if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
				$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( $order_shipping_items ) {
					$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
				} else {
					$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
				}

				if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
					return false;
				}
			}

			return parent::is_available();
		}
		
		private function make_client_secret(){

			if(!empty($this->client_secret)){
				return $this->client_secret;
			} else if(isset(WC()->session) && !empty(WC()->session->get('payscout_gateway_client_secret'))){
				$this->client_secret = WC()->session->get('payscout_gateway_client_secret');
				return $this->client_secret;
			}
			
			global $woocommerce, $post;
			
			$post_data = [
							'amount'=>50,
							'currency'=>'usd',
							'payment_method_types'=>['card','us_bank_account'],
							'capture_method'=>'manual',
							'confirmation_method'=>'automatic',
							'application'=>'ArcanePOS'
						];
						
			//$post_data['currency'] = get_option('woocommerce_currency','usd');
			//$post_data['currency'] = get_woocommerce_currency_symbol();
			$post_data['currency'] = strtolower(get_woocommerce_currency());
			
			$result = null;

			if( is_checkout() ){
				
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				if($order){
					$post_data['amount'] = ($order->get_total()*100);
					$post_data['application_fee_amount'] = (($post_data['amount']*0.05)+0.15);
				} else {
					$post_data['amount'] = (WC()->cart->get_cart_contents_total()*100);
					$post_data['application_fee_amount'] = (($post_data['amount']*0.05)+0.15);
				}

				$postdata = json_decode(json_encode($post_data));

				$pi = WC_Payscout_API::create_payment_intent($postdata);
				
				if(isset($pi['body'])){
					$body = json_decode($pi['body']);
					$result = $body->client_secret;
					WC()->session->set('payscout_gateway_client_secret',$result);
					if (preg_match('/pi_(.*?)_secret/', $result, $match) == 1) {
						WC()->session->set('payscout_gateway_payment_intent', $match[1]);
					}
				}
			}

			return $result;
		}

		/**
		 * Checks to see whether or not the admin settings are being accessed by the current request.
		 *
		 * @return bool
		 */
		private function is_accessing_settings() {
			if ( is_admin() ) {
				// phpcs:disable WordPress.Security.NonceVerification
				if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
					return false;
				}
				if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
					return false;
				}
				if ( ! isset( $_REQUEST['section'] ) || 'payscout' !== $_REQUEST['section'] ) {
					return false;
				}
				// phpcs:enable WordPress.Security.NonceVerification

				return true;
			}

			/*
			if ( Constants::is_true( 'REST_REQUEST' ) ) {
				global $wp;
				if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
					return true;
				}
			}
			*/

			return false;
		}

		/**
		 * Loads all of the shipping method options for the enable_for_methods field.
		 *
		 * @return array
		 */
		private function load_shipping_method_options() {
			// Since this is expensive, we only want to do it if we're actually on the settings page.
			if ( ! $this->is_accessing_settings() ) {
				return array();
			}

			$data_store = WC_Data_Store::load( 'shipping-zone' );
			$raw_zones  = $data_store->get_zones();

			foreach ( $raw_zones as $raw_zone ) {
				$zones[] = new WC_Shipping_Zone( $raw_zone );
			}

			$zones[] = new WC_Shipping_Zone( 0 );

			$options = array();
			foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

				$options[ $method->get_method_title() ] = array();

				// Translators: %1$s shipping method name.
				$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'payscout-gateway' ), $method->get_method_title() );

				foreach ( $zones as $zone ) {

					$shipping_method_instances = $zone->get_shipping_methods();

					foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

						if ( $shipping_method_instance->id !== $method->id ) {
							continue;
						}

						$option_id = $shipping_method_instance->get_rate_id();

						// Translators: %1$s shipping method title, %2$s shipping method id.
						$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'payscout-gateway' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

						// Translators: %1$s zone name, %2$s shipping method instance name.
						$option_title = sprintf( __( '%1$s &ndash; %2$s', 'payscout-gateway' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'payscout-gateway' ), $option_instance_title );

						$options[ $method->get_method_title() ][ $option_id ] = $option_title;
					}
				}
			}

			return $options;
		}

		/**
		 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
		 *
		 * @since  1.1.0
		 *
		 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
		 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
		 */
		private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

			$canonical_rate_ids = array();

			foreach ( $order_shipping_items as $order_shipping_item ) {
				$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
			}

			return $canonical_rate_ids;
		}

		/**
		 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
		 *
		 * @since  1.1.0
		 *
		 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
		 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
		 */
		private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

			$shipping_packages  = WC()->shipping()->get_packages();
			$canonical_rate_ids = array();

			if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
				foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
					if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
						$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
						$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
					}
				}
			}

			return $canonical_rate_ids;
		}

		/**
		 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
		 *
		 * @since  1.1.0
		 *
		 * @param array $rate_ids Rate ids to check.
		 * @return boolean
		 */
		private function get_matching_rates( $rate_ids ) {
			// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
			return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {

			if(empty($this->client_secret)){
				$this->client_secret = WC()->session->get('payscout_gateway_client_secret');
			}

			$order = wc_get_order( $order_id );

			if ( $order->get_total() > 0 ) {
				$this->payscout_payment_processing( $order_id );
			} else {
				$order->payment_complete();
			}
			WC()->session->set('payscout_gateway_client_secret',null);
			WC()->session->set('payscout_gateway_payment_intent',null);
			// Remove cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
		
		public function get_customer_info( $order ) {
			
			$result = ['receipt_email','shipping'=>['name','address','phone'],'payment_method_data'=>['billing_details'=>['address','email','name','phone']]];
			
			if(!empty($order->get_billing_email())){
				$result['receipt_email'] = $result['payment_method_data']['billing_details']['email'] = $order->get_billing_email();
			}
			
			if(!empty($order->get_shipping_first_name())){
				$result['shipping']['name'] = $result['payment_method_data']['billing_details']['name'] = $order->get_shipping_first_name();
			}
			if(!empty($order->get_shipping_last_name())){
				$result['shipping']['name'] .= (' '.$order->get_shipping_last_name());
				$result['payment_method_data']['billing_details']['name'] .= (' '.$order->get_shipping_last_name());
			}
			if(!empty($result['shipping']['name'])){
				$result['shipping']['name'] = trim($result['shipping']['name']);
				$result['payment_method_data']['billing_details']['name'] = trim($result['payment_method_data']['billing_details']['name']);
			}
			
			if(!empty($order->get_billing_phone())){
				$result['shipping']['phone'] = $order->get_billing_phone();
				$result['payment_method_data']['billing_details']['phone'] = $order->get_billing_phone();
			}
			
			if(!empty($order->get_shipping_address_1())){
				$result['shipping']['address']['line1'] = $order->get_shipping_address_1();
			}
			
			if(!empty($order->get_shipping_address_2())){
				$result['shipping']['address']['line2'] = $order->get_shipping_address_2();
			}
			
			if(!empty($order->get_shipping_city())){
				$result['shipping']['address']['city'] = $order->get_shipping_city();
			}
			
			if(!empty($order->get_shipping_state())){
				$result['shipping']['address']['state'] = $order->get_shipping_state();
			}
			
			if(!empty($order->get_shipping_postcode())){
				$result['shipping']['address']['postal_code'] = $order->get_shipping_postcode();
			}
			
			if(!empty($order->get_shipping_country())){
				$result['shipping']['address']['country'] = $order->get_shipping_country();
			}
		
			if(!empty($order->get_billing_address_1())){
				$result['payment_method_data']['billing_details']['address']['line1'] = $order->get_billing_address_1();
			}
			
			if(!empty($order->get_billing_address_2())){
				$result['payment_method_data']['billing_details']['address']['line2'] = $order->get_billing_address_2();
			}
			
			if(!empty($order->get_billing_city())){
				$result['payment_method_data']['billing_details']['address']['city'] = $order->get_billing_city();
			}
			
			if(!empty($order->get_billing_state())){
				$result['payment_method_data']['billing_details']['address']['state'] = $order->get_billing_state();
			}
			
			if(!empty($order->get_billing_postcode())){
				$result['payment_method_data']['billing_details']['address']['postal_code'] = $order->get_billing_postcode();
			}
			
			if(!empty($order->get_billing_country())){
				$result['payment_method_data']['billing_details']['address']['country'] = $order->get_billing_country();
			}
			
			return $result;

		}
		
		private function payscout_payment_processing( $order_id ){

			if(empty($this->client_secret)){
				$this->client_secret = WC()->session->get('payscout_gateway_client_secret');
			}
			if(empty($this->payment_intent)){
				$this->payment_intent = WC()->session->get('payscout_gateway_payment_intent');
			}
			if(empty($this->payment_intent)){
				// PI can be found in the CS between pi_ and _secret
				if (preg_match('/pi_(.*?)_secret/', $this->client_secret, $match) == 1) {
					$this->payment_intent = $match[1];
				}
			}
			
			$order = wc_get_order( $order_id );
			
			if ( $this->lock_order_payment( $order, $this->payment_intent ) ) {
				return;
			}
			
			$amount = $order->get_total()*100;
			$appfeemt = ($amount*0.05)+0.15;
			$postData = [
							'amount'=>$amount,
							'capture_method'=>'automatic',
							'application_fee_amount'=>$appfeemt,
							// 'customer'=>$customer // Will be enabled when customer endpoints go live, provided that we have customer ID stored in WC metadata
						];
			
			$customerDetails = $this->get_customer_info( $order );
			
			$pm_billing_details = $customerDetails['payment_method_data'];
			
			if(count(array_filter($pm_billing_details))){
				$payment_intent = WC_Payscout_API::get_payment_intent($this->payment_intent);
				if(!empty($payment_intent) && !empty($payment_intent['body']) && !empty(json_decode($payment_intent['body'])->payment_method)){
					WC_Payscout_API::update_payment_method(json_decode($payment_intent['body'])->payment_method,$customerDetails['payment_method_data']);
				}
			}
			
			unset($customerDetails['payment_method_data']);
			
			$postData = array_merge($postData, $customerDetails);

			// Update the PI based on updated shipping and tax
			$updated = WC_Payscout_API::update_payment_intent($this->payment_intent,$postData);
			
			$flag = false;
			
			if(!empty($updated) && !empty($updated['body'])){
			
				// Confirm and Capture the PI (capture method was automatic in previous step, so we do not need to capture again)
				$confirm = WC_Payscout_API::confirm_payment_intent(json_decode($updated['body'])->id);

				//$capture = WC_Payscout_API::capture_payment_intent($this->payment_intent);

				if(!empty($confirm)){
					$body = json_decode($confirm['body']);
					if(empty($body->error)){
						$flag = true;
					} else {
						$localized_message = __( $body->error->message, 'payscout-gateway' );
					}
				}
			}
			if($flag === false){
				$localized_message = !empty($localized_message)? $localized_message : __( 'Payment processing failed. Please retry.', 'payscout-gateway' );
				$order->add_order_note( $localized_message );
				$this->unlock_order_payment( $order );
				throw new \Exception( $localized_message );
			} else {
				$order->payment_complete();
				// Mark as processing or on-hold (payment won't be taken until delivery).
				$order->update_status( apply_filters( 'woocommerce_payscout_process_payment_order_status', $order->has_downloadable_item() ? 'wc-invoiced' : 'processing', $order ), __( 'Payments pending.', 'payscout-gateway' ) );
			}
			
			$order->save();
			
			$this->unlock_order_payment( $order );
			WC()->session->set('payscout_gateway_client_secret',null);
			WC()->session->set('payscout_gateway_payment_intent',null);
			
		}

		/**
		 * Locks an order for payment intent processing for 5 minutes.
		 *
		 * @since 1.0
		 * @param WC_Order $order  The order that is being paid.
		 * @param stdClass $intent The intent that is being processed.
		 * @return bool            A flag that indicates whether the order is already locked.
		 */
		public function lock_order_payment( $order, $intent = null ) {
			$order_id       = $order->get_id();
			$transient_name = 'wc_payscout_processing_intent_' . $order_id;
			$processing     = get_transient( $transient_name );

			// Block the process if the same intent is already being handled.
			if ( '-1' === $processing || ( isset( $intent ) && $processing === $intent ) ) {
				return true;
			}
			WC()->session->set('payscout_gateway_payment_intent',$intent);
			// Save the new intent as a transient, eventually overwriting another one.
			set_transient( $transient_name, empty( $intent ) ? '-1' : $intent, 5 * MINUTE_IN_SECONDS );

			return false;
		}

		/**
		 * Unlocks an order for processing by payment intents.
		 *
		 * @since 4.2
		 * @param WC_Order $order The order that is being unlocked.
		 */
		public function unlock_order_payment( $order ) {
			$order_id = $order->get_id();
			delete_transient( 'wc_payscout_processing_intent_' . $order_id );
		}

		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
			}
		}

		/**
		 * Change payment complete order status to completed for Payscout orders.
		 *
		 * @since  1.1.0
		 * @param  string         $status Current order status.
		 * @param  int            $order_id Order ID.
		 * @param  WC_Order|false $order Order object.
		 * @return string
		 */
		public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
			if ( $order && 'payscout' === $order->get_payment_method() ) {
				$status = 'completed';
			}
			return $status;
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @param WC_Order $order Order object.
		 * @param bool     $sent_to_admin  Sent to admin.
		 * @param bool     $plain_text Email format: plain text or HTML.
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}
	}
}
?>