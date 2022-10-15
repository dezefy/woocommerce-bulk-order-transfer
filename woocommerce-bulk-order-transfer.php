<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Plugin Name:       WooCommerce Bulk Order Transfer
 * Plugin URI:        https://dezefy.com/plugins/woocommerce-bulk-order-transfer
 * Description:       This plugin allows bulk transferring orders from one user to another in same website
 * Version:           0.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Dezefy
 * Author URI:        https://dezefy.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://dezefy.com/plugins/woocommerce-bulk-order-transfer
 * Text Domain:       wbot
 * Domain Path:       /languages
 */


class WooCommerceBulkOrderTransfer {
	private $woocommerce_bulk_order_transfer_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'woocommerce_bulk_order_transfer_add_plugin_page' ) );
        	add_action( 'wp_ajax_woo_transfer_order', array( $this, 'woocommerce_bulk_order_transfer_ajax'));
        	add_action( 'wp_ajax_nopriv_woo_transfer_order', array( $this, 'not_allowed'));
	}

	public function woocommerce_bulk_order_transfer_add_plugin_page() {
		add_submenu_page(
            		'woocommerce',
			'WooCommerce Bulk Order Transfer', // page_title
			'WooCommerce Bulk Order Transfer', // menu_title
			'manage_options', // capability
			'woocommerce-bulk-order-transfer', // menu_slug
			array( $this, 'woocommerce_bulk_order_transfer_create_admin_page' ) // function
		);
	}

	public function woocommerce_bulk_order_transfer_create_admin_page() {
        	wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
        	wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', array( 'jquery', 'selectWoo' ), '1.0' );
        	wp_localize_script(
		    'wc-enhanced-select',
		    'wc_enhanced_select_params',
		    array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		    )
		);



		?>

		<div class="wrap">
			<h2>WooCommerce Bulk Order Transfer</h2>
			<p>This plugin allows bulk transferring orders from one user to another user in same woocommerce website</p>

			<form method="post" action="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="wbot_form" id="wbot_form">
				<input type="hidden" name="action" value="woo_transfer_order" />
				<?php wp_nonce_field( 'woo_transfer_order' ); ?>
				<table class="form-table" role="presentation">
				    <tbody>
					<tr>
					    <th scope="row">From User</th>
					    <td>
						<select required="required" id="wbot_from_user" class="wc-customer-search" name="wbot_from_user" data-placeholder="Search User" data-allow_clear="true">
						    <option value="" selected="selected"></option>
						</select>

					    </td>
					</tr>
					<tr>
					    <th scope="row">To User</th>
					    <td>
						<select required="required" id="wbot_to_user" class="wc-customer-search" name="wbot_to_user" data-placeholder="Search User" data-allow_clear="true">
						    <option value="" selected="selected"></option>
						</select>
					    </td>
					</tr>
				    </tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="wbot-submit" class="button button-primary" value="Transfer Orders"></p>
			</form>
		</div>

        <script>
            jQuery(document).ready(function(){
                jQuery('#wbot-submit').click(function(e) {
                    e.preventDefault();
                    var wbot_from_user = jQuery('#wbot_from_user').val();
                    var wbot_to_user = jQuery('#wbot_to_user').val();
                    if( wbot_from_user != '' && wbot_to_user != ''){
                        if (confirm('Are you sure?')) {
                            var form = jQuery('#wbot_form');
                            var actionUrl = form.attr('action');
                            var formdata = form.serialize();
                            jQuery.ajax({
                                type : "post",
                                dataType : "json",
                                url : actionUrl,
                                data : formdata,
                                success: function(response) {
                                    if(response.type == 'failed'){
                                        alert('Something Went Wrong...');
                                    }
                                    if(response.type == 'success'){
                                        alert( response.order_count + ' Order Transfered');
                                    }
                                    if(response.type == 'no_order'){
                                        alert( 'No Order Found...');
                                    }
                                }
                            })
                        }
                    } else {
                        alert('Please select both from and to user');
                    }
                });
            })
        </script>
        <style>
            .wbot_form .select2-container {
                float: left;
                width: 320px!important;
                font-size: 14px;
                vertical-align: middle;
                margin: 1px 6px 4px 1px;
            }
        </style>
	<?php }

	public function woocommerce_bulk_order_transfer_ajax() {
        
		if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], "woo_transfer_order")  ) {
            		exit("Request Source Verification Failed");
        	}

        	if( current_user_can( 'edit_posts' ) ){

            		$wbot_from_user_id = intval($_REQUEST['wbot_from_user']);
            		$wbot_to_user_id = intval($_REQUEST['wbot_to_user']);

            		$wbot_from_user = get_userdata( $wbot_from_user_id );
            		$wbot_to_user = get_userdata( $wbot_to_user_id );

			    if( $wbot_from_user && $wbot_to_user ){

				$args = array(
				    'customer_id' => $wbot_from_user_id,
				    'limit' => -1,
				    'return' => 'ids',
				);
				$orders = wc_get_orders($args);

				if( count($orders) > 0 ){
				    foreach($orders as $orderid){
					update_post_meta($orderid, '_customer_user', $wbot_to_user_id);
				    }
				    $result['type'] = "success";
				    $result['order_count'] = count($orders);
				} else {
				    $result['type'] = "no_order";
				}

			    } else {
				$result['type'] = "failed";
			    }

            		$result = json_encode($result);
            		echo $result;
		} else {
		    exit("Only Administrators Allowed");
		}
        die();
	}

    public function not_allowed() {
        echo "You must log in to like";
        die();
    }

}
if ( is_admin() )
	$woocommerce_bulk_order_transfer = new WooCommerceBulkOrderTransfer();

