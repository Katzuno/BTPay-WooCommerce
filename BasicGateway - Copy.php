<?php
/**
 * Plugin Name: BT Pay Integration WooCommerce
 * Plugin URI: http://erikhenning.ro/btpay
 * Description: Integrates BT Pay with wordpress - One-Phase
 * Author: Erik Henning
 * Version: 1.0
 * Author URI: http://erikhenning.ro
 */


 add_action('plugins_loaded', 'btpay_gateway_class_init', 0);

 function btpay_gateway_class_init() {
     class WC_Gateway_BTPAY extends WC_Payment_Gateway
     {
         function _construct()
         {
            $this->id = "btpay";
           // $this->icon = "https://ithit.ro/wp-content/uploads/2018/10/logo-118x96-69x96.png";
            $this->has_fields = false;
            $this->method_title = "BT Pay";
            $this->method_description = "BT Pay Ecommerce plugin";
            $this->order_button_text  = 'Continue to BYPAY';
            $this->init_form_fields();
            $this->init_settings();    

            $this->title       = "BTPAY";
			$this->description = "BT PAY Ecommerce plugin";
			$this->username = $this->settings['username'];
            $this->password = $this->settings['password'];
            wp_die($this->title);
        
            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
        }
        function init_form_fields()
         {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable / Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable BTPay Payment',
                    'default' => 'no'
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'BTPay Payment',
                    'desc_tip'      => 'This controls the title',
                ),
                'description' => array(
                    'title' => 'Customer Message',
                    'type' => 'textarea',
                    'default' => ''
                ),
                'username' => array(
                    'title' => 'Username',
                    'type' => 'text',
                    'default' => ''
                ),
                'password' => array(
                    'title' => 'Password',
                    'type' => 'text',
                    'default' => ''
                )
            );
        }

        function admin_options() {
			echo '<h3>' . 'BTPay WooCommerce Gateway' . '</h3>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
            }
    
        // Show description for this payment option
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
        }
        
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
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url( $order )
                );
            }
        }
     }
 
    function add_btpay_gateway($methods)
    {
        $methods[] = 'WC_Gateway_BTPAY';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_btpay_gateway');


 ?>