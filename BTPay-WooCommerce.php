<?php
/**
 * Plugin Name: BT Pay Integration WooCommerce
 * Plugin URI: https://www.ithit.ro
 * Description: Integrates BT Pay with wordpress - One-Phase. Processing is made via external API
 * Author: Erik Henning @ IT HIT
 * Version: 1.0
 * Author URI: https://www.ithit.ro
 */

define ('IT_HIT_BACKEND', "http://btplug.ithit.ro/rest/ecommerce/");
add_action('plugins_loaded', 'woocommerce_btpay_card_init', 0);

function woocommerce_btpay_card_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

	// btpay Card Gateway Class
	class WC_btpay_Card extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'btpayecommerce';
			$this->method_title       = __( 'btpay Card', 'btpay' );
			$this->icon               = "https://ithit.ro/wp-content/uploads/2018/10/logo-118x96-69x96.png";
			$this->has_fields         = false;
			$this->order_button_text  = __( 'Continue to btpay', 'btpay' );
			
			$this->init_form_fields();
			$this->init_settings();

			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->username = $this->settings['username'];
            $this->password = $this->settings['password'];
			$this->sandbox = $this->settings['sandbox'];

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			
			add_action( 'woocommerce_thankyou_btpayecommerce', array( $this, 'btpay_thankyou_page' ) );
        }

		function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'btpay' ),
					'label'   => __( 'Enable BTPay Payment', 'btpay' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
				'title' => array(
					'title'    => __( 'Title', 'btpay' ),
					'type'     => 'text',
					'desc_tip' => __( 'This controls the title which the user sees during checkout.', 'btpay' ),
					'default'  => __( 'Credit/Debit Card BTPay', 'btpay' ),
				),
				'description' => array(
					'title'    => __( 'Description', 'btpay' ),
					'type'     => 'textarea',
					'desc_tip' => __( 'This controls the description which the user sees during checkout.', 'btpay' ),
					'default'  => __( 'Pay with your credit/debit card via btpay 3D Secure gateway.', 'btpay' ),
				),
				'username' => array(
					'title'    => __( 'Username', 'btpay' ),
					'type'     => 'text',
					'desc_tip' => __( 'Unique username assigned to your btpay merchant account for the payment process.', 'btpay' ),
				),
				'password' => array(
					'title'    => __( 'Password', 'btpay' ),
					'type'     => 'text',
					'desc_tip' => __( 'Unique password assigned to your btpay merchant account for the payment process.', 'btpay' ),
                ),
                'sandbox' => array(
					'title'   => __( 'Enable/Disable SandBox', 'btpay' ),
					'label'   => __( 'Enable BTPay Payment Sandbox', 'btpay' ),
					'type'    => 'checkbox',
					'default' => 'no',
				),
			);
		}

		// Display admin panel options
		public function admin_options() {
			echo '<h3>'.__('btpay Card Gateway', 'btpay').'</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		// Show description for this payment option
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}


		// Thank you page
		function btpay_thankyou_page( $order_id ) {
			$order = new WC_Order($order_id);
			if ($_GET['btpay_success'])
			{
				$order->payment_complete();
			}
		}

		// Process the payment and return the result
		function process_payment( $order_id ) {
			global $woocommerce;
            $order = new WC_Order( $order_id );
            // Mark as on-hold (we're awaiting the cheque)
            $order->update_status('on-hold', 'Awaiting BTPay payment');
            // Reduce stock levels
            $order->reduce_order_stock();
            // Remove cart
            $woocommerce->cart->empty_cart();
			// Return thankyou redirect
			
			$backend_url = IT_HIT_BACKEND . "pay?user=%s&pass=%s&transactionId=%s&amount=%d&returnUrl=%s&failUrl=%s&description=%s&sandbox=%s";
			$amount = $order->get_total();
			$returnUrl = $this->get_return_url($order) . "&btpay_success=true";
			$failUrl = IT_HIT_BACKEND . "failed";
			$description = "Ati cumparat de pe un magazin online";
			$backend_url = sprintf($backend_url, $this->username, $this->password, $order_id, $amount, $returnUrl, $failUrl, $description, $this->sandbox);
            return array(
               'result' => 'success',
               'redirect' => $backend_url
            );
		}

		}
	}

	// Add the gateway to WooCommerce
	function woocommerce_add_btpay_card_gateway( $methods ) {
		$methods[] = 'WC_btpay_Card';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_btpay_card_gateway' );


 ?>