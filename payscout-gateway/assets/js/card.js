/**
 * EPF Embed and Payment Method creation
 *
 * @package 1.0.0
 * @version 1.0.0
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
let execute = (function(postLoad=false,counter=0) {
	if ( ! document.querySelector( '#payscout_paywire_gateway_container iframe' ) ) {
		return new Promise(
			(resolve, reject) => {
				if ( postLoad || ! window.jQuery ) {
					return resolve( postLoad );
				}
				if ( document.querySelector( '.blockOverlay' ) ) {
					return resolve( postLoad );
				} else if ( counter >= 8 ) {
					// We assume that if it's been a whole bunch of checks, blockOverlay is probably not being used.
					// This is a sub-par approach.
					return resolve( postLoad );
				} else {
					setTimeout(
						function() {
							execute( false,counter + 1 );
						},
						1000
					);
					return reject( postLoad );
				}
			}
		).then(
			() => {
				return new Promise(
					(resolve, reject) => {
						if ( ! document.querySelector( '.blockOverlay' ) ) {
							return resolve( true );
						} else {
							setTimeout(
								function() {
									execute( true,8 );
								},
								1000
							);
							return reject( true );
						}
					}
				);
			}
		).then(
			() => {
				let key           = wc_payment_gateway_params.key;
				let client_secret = wc_payment_gateway_params.client_secret;
				let payscout      = new Payscout( key );
				let data          = {};
				let components    = payscout.components();
				let style         = {base: { }};
				if ( wc_payment_gateway_params.style !== null && wc_payment_gateway_params.style.length > 0 ) {
					style = JSON.parse( wc_payment_gateway_params.style );
				}
				let card = components.create( 'card', {style: style} );
				if ( ! document.querySelector( '#payscout_paywire_gateway_container iframe' ) ) {
					card.mount( '#payscout_paywire_gateway_container' );
					document.querySelector( '#reloadButton' ).remove();
					// We know that when there's no loader, it will execute on load, no problem
					// We also know that when there's a loader, it will appear on load, which will trigger a change, therefore a duplicate
					// So, to avoid duplicates, we need to be sure it's not already running.

					setTimeout(
						function() {
							const payment_method_component = document.querySelector( '#order_review' );

							// WC will reload components when form values change, which can cause this to disappear.  To address this, we need to listen to changes.
							const observer = new MutationObserver(
								function(mutationList, observer) {
									for (const mutation of mutationList) {
										if ( mutation.type === 'childList' ) {
											if ( ! document.querySelector( '#payscout_paywire_gateway_container iframe' ) ) {
												card.mount( '#payscout_paywire_gateway_container' );
												document.querySelector( '#reloadButton' ).remove();
											}
										}
									}
								}
							);

							// Call the observe function by passing the node you want to watch with configuration options.
							observer.observe(
								payment_method_component,
								{
									childList: true,
									subtree: true
								}
							);
						},
						1000
					);
				}
				function clearMessage(){
					let item         = document.querySelector( '#payscout_paywire_gateway_message' );
					item.textContent = '';
					item.classList.remove( "alert-danger","alert-warning","alert-success" );
				}
				function showMessage(type,message){
					clearMessage();
					let item = document.querySelector( '#payscout_paywire_gateway_message' );
					if ( type == 'error' ) {
						item.classList.add( "alert-danger" );
					} else if ( type == 'success' ) {
						item.classList.add( "alert-success" );
					} else {
						item.classList.add( "alert-warning" );
					}
					item.innerHTML = message;
				}
				function preventOnClick(event){
					let target = event.target;
					if ( target.ariaDisabled === 'true' ) {
						event.preventDefault();
						target.setAttribute( 'aria-disabled', false );
						target.classList.remove( 'disabled' );
					} else {
						target.removeEventListener( 'click', preventOnClick );
					}
				}
				// For WC Payment components all we want to do is create the PM then attach it to the PI and we'll confirm it server-side.
				// For WC Card components, we cannot use the create PM method, so we'll just set our PI's to manual capture and run confirmation here.
				if ( client_secret ) {
					card.clientSecret = client_secret;
					let params        = { card: card };

					card.on(
						'focus',
						function(e){
							clearMessage();
							if ( document.querySelector( '[type="submit"]' ) ) {
								document.querySelectorAll( '[type="submit"]' ).forEach(
									(button) => {
										button.setAttribute( 'aria-disabled', true );
										button.classList.add( 'disabled' );
										button.addEventListener( 'click', preventOnClick );
									}
								);
							}
						}
					);

					let processTransaction = (function(e){
						let address_fields = {'city':'city','country':'country','address_1':'line1','address_2':'line2','postcode':'postal_code','state':'state','phone':'phone'};

						if ( document.querySelector( '.woocommerce-billing-fields' ) && document.querySelector( '.woocommerce-billing-fields' ).style.display !== "none" ) {
							params.billing_details = { 'name':'', 'address': {}};
							document.querySelectorAll( '.woocommerce-billing-fields input, .woocommerce-billing-fields select' ).forEach(
								(item) => {
									let id         = item.getAttribute( 'id' ).replace( 'billing_','' );
									if ( id === 'first_name' || id === 'last_name' || id === 'name' ) {
										params.billing_details.name += item.value + ' '.trim();
									} else if ( id in address_fields ) {
											let index                             = address_fields[id];
											params.billing_details.address[index] = item.value;
									} else if ( id === 'billing_email' ) {
										params.billing_details = item.value;
										params.receipt_email   = item.value;
									}
								}
							);
						}

						if ( document.querySelector( '.woocommerce-shipping-fields' ) && document.querySelector( '.woocommerce-shipping-fields' ).style.display !== "none" ) {
							params.shipping = { 'name':'', 'address': {}};
							document.querySelectorAll( '.woocommerce-shipping-fields input, .woocommerce-shipping-fields select' ).forEach(
								(item) => {
									let id  = item.getAttribute( 'id' ).replace( 'shipping_','' );
									if ( id === 'first_name' || id === 'last_name' || id === 'name' ) {
										params.shipping.name += item.value + ' '.trim();
									} else if (id in address_fields) {
											let index                      = address_fields[id];
											params.shipping.address[index] = item.value;
									}
								}
							);
						} else {
							params.shipping = params.billing_details;
						}

						/*payscout.confirmCardPayment(client_secret, {
							payment_method: params,
							// setup_future_usage: 'off_session',
						})*/
						params.type = 'card';

						payscout.createPaymentMethod(
							params
							/* setup_future_usage: 'off_session', */
						).then(
							(result) => {
								if ( result.error ) {
									showMessage( 'error',result.error.message );
									return false;
								} else if ( ! result.id ) {
									throw new Error( 'Payment Method Invalid' );
									return false;
								} else {
									// Now that we have a PM, attach it to the PI.
									return payscout.updatePaymentIntent( client_secret, {'payment_method':result.id} );
								}
							}
						).then(
							function(result) {
								if ( result.error ) {
									if (result.error.message === 'Nullable object must have a value.') {
										clearMessage();
									} else {
										showMessage( 'error',result.error.message );
									}
								} else if ( ! result ) {
									// If the previous step is false, we should already have a message from either the 1st
									// condition or the 2nd condition which is caught.
								} else {
									showMessage( 'success','Payment Method Valid' );
								}

								if (document.querySelector( '[type="submit"]' )) {
									document.querySelectorAll( '[type="submit"]' ).forEach(
										(button) => {
											if ( button.ariaDisabled === 'true' ) {
												setTimeout(
													function () {
														button.setAttribute( 'aria-disabled', false );
														button.classList.remove( 'disabled' );
														button.removeEventListener( 'click', preventOnClick );
													},
													500
												);
											}
										}
									);
								}

								return;
							}
						).catch(
							result => {
								if ( result.error ) {
									if ( result.error.message === 'Nullable object must have a value.' ) {
										clearMessage();
									} else {
										showMessage( 'error',result.error.message );
									}
								} else if ( result.type && result.type === 'card_error' ) {
										showMessage( 'error',result.message );
								} else {
										clearMessage();
								}
								if ( document.querySelector( '[type="submit"]' ) ) {
									document.querySelectorAll( '[type="submit"]' ).forEach(
										(button) => {
											if ( button.ariaDisabled === 'true' ) {
												setTimeout(
													function () {
														button.setAttribute( 'aria-disabled', false );
														button.classList.remove( 'disabled' );
														button.removeEventListener( 'click', preventOnClick );
													},
													500
												);
											}
										}
									);
								}
								return;
							}
						);
					});

					card.on(
						'blur',
						function(e){
							processTransaction( e );
						}
					);
					/*
					var timer;

					card.on('change', function(e){
						clearTimeout(timer);
						timer = setTimeout(processTransaction(e), 2500);
					});
					*/
				}

				}
		).catch(
			() => {
				// Do nothing.
			}
		);
	}
});
/* global wc_payment_gateway_params */
document.addEventListener(
	'DOMContentLoaded',
	function(event) {
		execute();
	}
);
