<?php
/**
 * Payscout Paywire Gateway Plugin Adds Invoices Status
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
add_action( 'init', 'register_payscout_order_statuses' );

/**
 * Registers the order status to be added via the wc_order_statuses filter hook.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function register_payscout_order_statuses() {
	register_post_status(
		'wc-invoiced',
		array(
			'label'                     => _x( 'Invoiced', 'Order Status', 'payscout-gateway' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: 0 */
			'label_count'               => _n_noop( 'Invoices <span class="count">(%s)</span>', 'Invoices <span class="count">(%s)</span>', 'pascout-gateway' ),
		),
	);
}

add_filter( 'wc_order_statuses', 'payscout_wc_order_statuses' );

/**
 * Registers the order status to be added via the wc_order_statuses filter hook.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @param Array $order_statuses list of existing order statuses to be hooked into.
 * @return Array List of valid WC order statuses.
 */
function payscout_wc_order_statuses( $order_statuses ) {
	$order_statuses['wc-invoiced'] = _x( 'Invoiced', 'Order Status', 'payscout-gateway' );
	return $order_statuses;
}

/**
 * Adds the custom order status to the bulk status change dropdown in the CMS.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function payscout_add_bulk_invoice_order_status() {
	global $post_type;

	if ( 'shop_order' === $post_type ) {
		?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('<option>').val('mark_invoiced').text('<?php esc_html_e( 'Change status to invoiced', 'payscout-gateway' ); ?>').appendTo("select[name='action']");
					jQuery('<option>').val('mark_invoiced').text('<?php esc_html_e( 'Change status to invoiced', 'payscout-gateway' ); ?>').appendTo("select[name='action2']");
				});
			</script>
		<?php
	}
}
add_action( 'admin_footer', 'payscout_add_bulk_invoice_order_status' );
