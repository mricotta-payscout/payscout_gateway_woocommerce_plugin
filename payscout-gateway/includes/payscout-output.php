<?php

add_filter( 'woocommerce_gateway_description', 'payscout_gateway_mount_point', 20, 2);
add_filter( 'woocommerce_checkout_process', 'payscout_gateway_validate', 20, 2);

function payscout_gateway_mount_point( $description, $payment_id ) {
	
	if($payment_id !== "payscout"){
		return $description;
	}
	
	ob_start();
	
	echo '<div id="payscout_paywire_gateway_container"><button id="reloadButton" type="button" class="button" onClick="window.location.reload()">Form Not Loaded? Click Here</button></div><div id="payscout_paywire_gateway_message" class="alert">&nbsp;</div>';
	
	$description .= ob_get_clean();
	
	return $description;
	
}

function payscout_gateway_validate() {
	//
}