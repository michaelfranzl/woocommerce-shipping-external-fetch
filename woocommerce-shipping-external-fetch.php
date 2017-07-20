<?php
/**
 * Plugin Name: WooCommerce Shipping External Fetch
 * Plugin URI: https://github.com/michaelfranzl/woocommerce-shipping-external-fetch
 * Description: Fetch shipping rates from an external web service using JSON
 * Version: 0.1.0
 * Author: Michael Franzl
 * Author URI: https://michaelfranzl.com
 * Requires at least: 4.0
 * Tested up to: 4.8
 * WC requires at least: 3.0
 * WC tested up to: 3.1
 * Copyright: 2017 Michael Franzl
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	class WC_External_Fetch_Shipping {
	
		public function __construct() {
			define( 'EXTERNAL_FETCH_SHIPPING_VERSION', '0.1.0' );
			define( 'EXTERNAL_FETCH_SHIPPING_DEBUG', defined( 'WP_DEBUG' ) && 'true' == WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || 'true' == WP_DEBUG_DISPLAY ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}
		
		
		public function add_to_shipping_methods( $shipping_methods ) {
			$shipping_methods['external_fetch'] = 'WC_Shipping_External_Fetch';
			return $shipping_methods;
		}
		
		
		public function init() {
			add_filter( 'woocommerce_shipping_methods', array( $this, 'add_to_shipping_methods' ) );
			add_action( 'woocommerce_shipping_init', array( $this, 'shipping_init' ) );
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
		
		
		public function enqueue_scripts() {
			wp_enqueue_style('woocommerce-external-fetch-shipping-style', plugins_url( '/assets/css/style.css', __FILE__ ));
		}
		
		
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-external-fetch-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		
		
		public function shipping_init() {
			include_once( 'includes/class-wc-shipping-external-fetch.php' );
		}
	}

	new WC_External_Fetch_Shipping();
}
