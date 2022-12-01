<?php
/**
 * Payscout Gateway
 *
 * Provides a Payscout or Paywire Payment Gateway.
 *
 * @class       WC_Payscout_API
 * @version     1.0.0
 */
class WC_Payscout_API {
	
	public $public_key = null;
	public $secret_key = null;
	public $client_secret = null;
	
	public function __construct(array $keys) {
		if(!empty($keys)){
			foreach($keys as $index=>$value){
				$this->$index = $value;
			}
		}		
	}
	
	public static function capture_payment_intent($id,$post=[]){
		$path = 'payment_intents/'.$id.'/capture';		
		$plugin_settings = get_option('woocommerce_payscout_settings');

		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );
			
			$response = wp_remote_post($host.$path, array(
				'body' => json_encode($post),
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));

			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			}
		}

		return null;
	}	

	public static function confirm_payment_intent($id,$post=[]){
		$path = 'payment_intents/'.$id.'/confirm';		
		$plugin_settings = get_option('woocommerce_payscout_settings');

		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );
			
			$response = wp_remote_post($host.$path, array(
				'body' => json_encode($post),
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));

			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			} else if (isset($response['body']) && isset(json_decode($response['body'])->error) && isset(json_decode($response['body'])->error->message) && (json_decode($response['body'])->error->message === 'Invalid payment intent status: processing' || json_decode($response['body'])->error->message === 'Invalid payment intent status: succeeded')){
				return self::get_payment_intent($id);
			}
		}

		return null;
	}	
	
	public static function create_payment_intent($post){
		$path = 'payment_intents';		
		$plugin_settings = get_option('woocommerce_payscout_settings');
		
		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );
			
			$response = wp_remote_post($host.$path, array(
				'body' => json_encode($post),
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));
			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			}
		}
		return null;
	}

	public static function update_payment_intent($id,$post){
		$path = 'payment_intents/'.$id;		
		$plugin_settings = get_option('woocommerce_payscout_settings');
		
		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post($host.$path, array(
				'body' => json_encode($post),
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));
			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			} else if (isset($response['body']) && isset(json_decode($response['body'])->error) && isset(json_decode($response['body'])->error->message) && (json_decode($response['body'])->error->message === 'Invalid payment intent status: processing' || json_decode($response['body'])->error->message === 'Invalid payment intent status: succeeded')){
				return self::get_payment_intent($id);
			}
		}

		return null;
	}

	public static function get_payment_intent($id){
		$path = 'payment_intents/'.$id;		
		$plugin_settings = get_option('woocommerce_payscout_settings');
		
		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_get($host.$path, array(
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));

			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			}
		}

		return null;
	}

	public static function update_payment_method($id,$post){
		$path = 'payment_methods/'.$id;		
		$plugin_settings = get_option('woocommerce_payscout_settings');
		
		$secret_key = (isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? $plugin_settings['secret_key'] : (!empty($plugin_settings['secret_test'])? $plugin_settings['secret_test'] : '');
		
		if(!empty($secret_key)){
			$host = ((isset($plugin_settings['livemode']) && $plugin_settings['livemode']==='yes')? PAYSCOUT_PAYWIRE_GATEWAY_API_LIVE : PAYSCOUT_PAYWIRE_GATEWAY_API_TEST );

			$response = wp_remote_post($host.$path, array(
				'body' => json_encode($post),
				'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$secret_key),
			));

			if((!$response instanceof WP_Error) && $response['response']['code'] == 200){
				return $response;
			}
		}

		return null;
	}
	
}