<?php

/**
 * Add invoiced status for woocommerce
 */
add_action( 'init', 'register_payscout_order_statuses');

function register_payscout_order_statuses(){
	register_post_status( 'wc-invoiced', array(
		'label'    => _x('Invoiced', 'Order Status', 'payscout-gateway'),
		'public'   => true,
		'exclude_from_search'  => false,
		'show_in_admin_all_list'  => true,
		'show_in_admin_status_list'  => true,
		'label_count'  => _n_noop( 'Invoices <span class="count">(%s)</span>', 'Invoices <span class="count">(%s)</span>', 'pascout-gateway' )	
	));
}

add_filter( 'wc_order_statuses', 'payscout_wc_order_statuses');

function payscout_wc_order_statuses( $order_statuses ) {
	$order_statuses['wc-invoiced'] = _x('Invoiced', 'Order Status', 'payscout-gateway');
	return $order_statuses;
}

function payscout_add_bulk_invoice_order_status(){
	global $post_type;
	
	if( $post_type == 'shop_order' ){
		?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('<option>').val('mark_invoiced').text('<?php _e('Change status to invoiced', 'payscout-gateway' ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('mark_invoiced').text('<?php _e('Change status to invoiced', 'payscout-gateway' ); ?>').appendTo("select[name='action2']");
				});
			</script>
		<?php
	}
}
add_action( 'admin_footer', 'payscout_add_bulk_invoice_order_status');