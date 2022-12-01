/* global wc_payment_gateway_params */
document.addEventListener('DOMContentLoaded', function(event) {
	
	let execute = (function(postLoad=false,counter=0) {
	
		return new Promise((resolve, reject) => {
			if(postLoad || !window.jQuery){
				return resolve(postLoad);
			}
			if(document.querySelector('.blockOverlay')) {
				return resolve(postLoad);
			} else if(counter>=8){
				// We assume that if it's been like 5 times, blockOverlay is probably not being used.
				// This is a sub-par approach.
				return resolve(postLoad);
			} else {
				setTimeout(function() {
					execute(counter+1);
				}, 1000);
				return reject(postLoad);
			}
		}).then(() => {
			return new Promise((resolve, reject) => {
				if(!document.querySelector('.blockOverlay')) {
					return resolve(true);
				} else {
					setTimeout(function() {
						execute(true,8);
					}, 1000);
					return reject(true);
				}
			});
		}).then(() => {			
			let key = wc_payment_gateway_params.key;
			let client_secret = wc_payment_gateway_params.client_secret;
			let payscout = new Payscout(key);
			let data = {};
						
			let components = payscout.components();

			let style = {base: { }};

			if(wc_payment_gateway_params.style !== null && wc_payment_gateway_params.style.length > 0){
				style = JSON.parse(wc_payment_gateway_params.style);
			}
						
			let card = components.create('card', {style: style});
			card.mount('#payscout_paywire_gateway_container');

			function clearMessage(){
				let item = document.querySelector('#payscout_paywire_gateway_message');
				item.textContent = '';
				item.classList.remove("alert-danger","alert-warning","alert-success");
			}

			function showMessage(type,message){
				clearMessage();
				let item = document.querySelector('#payscout_paywire_gateway_message');
				if(type=='error'){
					item.classList.add("alert-danger");
				} else if(type=='success'){
					item.classList.add("alert-success");
				} else {
					item.classList.add("alert-warning");
				}
				item.innerHTML = message;
			}
			
			function preventOnClick(event){
				let target = event.target;
				if(target.ariaDisabled === 'true'){
					event.preventDefault();
					target.setAttribute('aria-disabled', false);
				} else {
					target.removeEventListener('click', preventOnClick);
				}
			}
			
			// For WC Payment components all we want to do is create the PM then attach it to the PI and we'll confirm it server-side
			// For WC Card components, we cannot use the create PM method, so we'll just set our PI's to manual capture and run confirmation here
			if(client_secret){
				card.clientSecret = client_secret;
				let params = { card: card };
				
				card.on('focus', function(e){
					if(document.querySelector('[type="submit"]')){
						document.querySelectorAll('[type="submit"]').forEach((button) => {
							button.setAttribute('aria-disabled', true);
							button.addEventListener('click', preventOnClick);
						});
					}
				});
				
				card.on('blur', function(e){
					let address_fields = {'city':'city','country':'country','address_1':'line1','address_2':'line2','postcode':'postal_code','state':'state','phone':'phone'};

					if(document.querySelector('.woocommerce-billing-fields') && document.querySelector('.woocommerce-billing-fields').style.display !== "none"){
						params.billing_details = { 'name':'', 'address': {}};
						document.querySelectorAll('.woocommerce-billing-fields input, .woocommerce-billing-fields select').forEach((item) => {
							let id = item.getAttribute('id').replace('billing_','');
							if(id === 'first_name' || id === 'last_name' || id === 'name'){
								params.billing_details.name += item.value+' '.trim();
							} else if(id in address_fields) {
								let index = address_fields[id];
								params.billing_details.address[index] = item.value;
							} else if(id === 'billing_email'){
								params.billing_details = item.value;
								params.receipt_email = item.value;
							}
						});
					}
					
					if(document.querySelector('.woocommerce-shipping-fields') && document.querySelector('.woocommerce-shipping-fields').style.display !== "none"){
						params.shipping = { 'name':'', 'address': {}};
						document.querySelectorAll('.woocommerce-shipping-fields input, .woocommerce-shipping-fields select').forEach((item) => {
							let id = item.getAttribute('id').replace('shipping_','');
							if(id === 'first_name' || id === 'last_name' || id === 'name'){
								params.shipping.name += item.value+' '.trim();
							} else if(id in address_fields) {
								let index = address_fields[id];
								params.shipping.address[index] = item.value;
							}
						});
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
					  // setup_future_usage: 'off_session',
					).then((result) => {
					  if (result.error) {
						showMessage('error',result.error.message);
						return false;
					  } else if(!result.id) {
						throw new Error('Payment Method Invalid');
						return false;
					  } else {
					    // Now that we have a PM, attach it to the PI.
						return payscout.updatePaymentIntent(client_secret, {'payment_method':result.id});
					  }
					}).then(function(result) {
					  if (result.error) {
							if(result.error.message === 'Nullable object must have a value.'){
								clearMessage();
							} else {
								showMessage('error',result.error.message);
							}
					  } else {
						showMessage('success','Payment Method Valid');
					  }
					  
					  if(document.querySelector('[type="submit"]')){
						  document.querySelectorAll('[type="submit"]').forEach((button) => {
							  if(button.ariaDisabled === 'true'){
								button.setAttribute('aria-disabled', false);
								button.removeEventListener('click', preventOnClick);
							  }
						  });
					  }
					  
					  return;
					}).catch(result => {
						if (result.error) {
							if(result.error.message === 'Nullable object must have a value.'){
								clearMessage();
							} else {
								showMessage('error',result.error.message);
							}
						} else if(result.type && result.type === 'card_error'){
							showMessage('error',result.message);
						} else {
							clearMessage();
						}
						
						if(document.querySelector('[type="submit"]')){
							document.querySelectorAll('[type="submit"]').forEach((button) => {
								if(button.ariaDisabled === 'true'){
									button.setAttribute('aria-disabled', false);
									button.removeEventListener('click', preventOnClick);
								}
							});
						}
						
						return;
					});
				});
			}

		}).catch(() => {		
			// Do nothing
		});
	});
	
	execute();
});