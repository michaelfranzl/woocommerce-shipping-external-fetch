<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Shipping_External_Fetch extends WC_Shipping_Method {

	public function __construct($instance_id = 0) {
		$this->id                 = 'external_fetch';
		$this->instance_id        = $instance_id;
		$this->method_title       = __( 'External Fetch' );
		$this->method_description = __( 'Description of your shipping method' );
		$this->supports           = array( 'zones', 'shipping-zones', 'instance-settings' );
		$this->enabled            = 'yes';
		$this->last_response = array();
	
		if ($instance_id == 0) {
			// If the constructor is called without $instance_id, there is nothing more to do.
			return;
		}
		
		$this->init();
	}
	
	
	function init() {
		$this->init_form_fields();
		$this->init_settings();
		
		$this->title              = $this->get_option( 'title', __( 'Label', 'woocommerce-external-fetch-shipping' ) );
		$this->description        = $this->get_option( 'description' );
		$this->method_description = $this->description;
		
		add_filter( 'woocommerce_cart_no_shipping_available_html', array( $this, 'filter_cart_no_shipping_available_html' ), 10, 1 );
		add_action( 'woocommerce_after_shipping_rate', array($this, 'action_after_shipping_rate'), 10, 2);
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'action_add_text_before_proceed_to_checkout' ));
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'maybe_clear_wc_shipping_rates_cache' ));
	}
	
	
	public function action_after_shipping_rate($rate, $index) {
		$rate_id = $rate->id;
		$rates = $this->last_response['rates'];
		foreach( $rates as $r ) {
			if ( $rate_id == $r['id'] ) { // This rate ID belongs to this instance
				echo "<div class='shipping_rate_description'>" . $r['description'] . "</div>";
			}
		}
	}
	
	public function maybe_clear_wc_shipping_rates_cache() {
		if ( $this->get_option('clear_wc_shipping_cache') == 'yes' ) {
			$packages = WC()->cart->get_shipping_packages();
			foreach ($packages as $key => $value) {
				$shipping_session = "shipping_for_package_$key";
				unset(WC()->session->$shipping_session);
			}
		}
	}
	
	
	public function action_add_text_before_proceed_to_checkout() {
		echo $this->last_response['before_checkout_button_html'];
	}
	
	
	public function filter_cart_no_shipping_available_html($previous) {
		return $previous . $this->last_response['cart_no_shipping_available_html'];
	}
	
	
	public function get_option( $key, $empty_value = null ) {
		// Instance options take priority over global options
		if ( in_array( $key, array_keys( $this->instance_form_fields ) ) ) {
			return $this->get_instance_option( $key, $empty_value );
		}

		// Return global option
		return parent::get_option( $key, $empty_value );
	}
	
	
	public function get_instance_option( $key, $empty_value = null ) {
		if ( empty( $this->instance_settings ) ) {
			$this->init_instance_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->instance_settings[ $key ] ) ) {
			$form_fields = $this->instance_form_fields;

			if ( is_callable( array( $this, 'get_field_default' ) ) ) {
				$this->instance_settings[ $key ] = $this->get_field_default( $form_fields[ $key ] );
			} else {
				$this->instance_settings[ $key ] = empty( $form_fields[ $key ]['default'] ) ? '' : $form_fields[ $key ]['default'];
			}
		}

		if ( ! is_null( $empty_value ) && '' === $this->instance_settings[ $key ] ) {
			$this->instance_settings[ $key ] = $empty_value;
		}

		return $this->instance_settings[ $key ];
	}
	
	
	public function get_instance_option_key() {
		return $this->instance_id ? $this->plugin_id . $this->id . '_' . $this->instance_id . '_settings' : '';
	}
	
	
	public function init_instance_settings() {
		// 2nd option is for BW compat
		$this->instance_settings = get_option( $this->get_instance_option_key(), get_option( $this->plugin_id . $this->id . '-' . $this->instance_id . '_settings', null ) );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->instance_settings ) ) {
			$form_fields             = $this->get_instance_form_fields();
			$this->instance_settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}
	
	
	public function init_form_fields() {
		$this->form_fields     = array(); // No global options for table rates
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Method Title', 'woocommerce-external-fetch-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-external-fetch-shipping' ),
				'default'     => __( 'Fetch from localhost', 'woocommerce-external-fetch-shipping' )
			),
			'description' => array(
				'title'       => __( 'Method Description', 'woocommerce-external-fetch-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-external-fetch-shipping' ),
				'default'     => __( '', 'woocommerce-external-fetch-shipping' )
			),
			'endpoint' => array(
				'title'       => __( 'JSON API endpoint', 'woocommerce-external-fetch-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Protocol, Hostname, Port and URL of API endpoint', 'woocommerce-external-fetch-shipping' ),
				'placeholder'     => __( 'http://localhost:4040/calculate', 'woocommerce-external-fetch-shipping' )
			),
			'fallback_cost' => array(
				'title'       => __( 'Fallback cost', 'woocommerce-external-fetch-shipping' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'default'     => '0',
				'description' => __( 'Use this shipping cost when service unavailable', 'woocommerce-external-fetch-shipping' ),
			),
			'debug' => array(
				'title'       => __( 'Debug', 'woocommerce-external-fetch-shipping' ),
				'label'       => ' ',
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'Set a "debug": "yes" flag in the JSON sent to the service.', 'woocommerce-external-fetch-shipping' ),
			),
			'clear_wc_shipping_cache' => array(
				'title'       => __( 'Disable Shipping Cache', 'woocommerce-external-fetch-shipping' ),
				'label'       => ' ',
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( "Clear WooCommerce's session-based shipping calculation cache at every load.", 'woocommerce-external-fetch-shipping' ),
			),
		);
	}
	
	
	public function calculate_shipping( $package = array() ) {
		try {
			//throw new Exception( 'blah' ); // to test catch block
			
			// prepare a JSON object to be sent to shipping calculation API
			foreach ( $package['contents'] as $item_id => $values ) {
				$_product = $values['data'];
				$class_slug = $_product->get_shipping_class();
				$package['contents'][ $item_id ]['shipping_class_slug'] = $class_slug;
				
				// collect category slugs
				$catids = $_product->get_category_ids();
				$catslugs = array();
				foreach ( $catids as $catid ) {
					$cat = get_category( $catid );
					array_push( $catslugs, $cat->slug );
				}
				
				// collect product attributes
				$attrs = array();
				
				//mylog($_product->get_attributes());
				$attributes = $_product->get_attributes();
				//
				foreach ( $_product->get_attributes() as $att ) {
					if ( is_object($att) ) { // of class WC_Product_Attribute
						$terms = $att->get_terms();
						if ( $terms ) {
							// This is a woocommerce predefined product attribute (Menu: WooCommerce -> Attributes)
							$termvalues = array();
							foreach( $terms as $term ) {
								array_push( $termvalues, $term->name );
							}
							$attrs[ $att->get_name() ] = $termvalues;
						} else {
							// This is a woocommerce custom product attribute
							$attrs[ $att->get_name() ] = $att->get_options();
						}
					} else {
						// for variations, attributes are strings
						array_push($attrs, $att);
					}
				}
				
				$package['contents'][ $item_id ]['categories'] = $catslugs;
				$package['contents'][ $item_id ]['attributes'] = $attrs;
				$package['contents'][ $item_id ]['name'] = $_product->name;
				$package['contents'][ $item_id ]['sku'] = $_product->sku;
				$package['contents'][ $item_id ]['dimensions'] = $_product->get_dimensions(false);
				$package['contents'][ $item_id ]['purchase_note'] = $_product->get_purchase_note();
				$package['contents'][ $item_id ]['weight'] = $_product->get_weight();
				$package['contents'][ $item_id ]['downloadable'] = $_product->get_downloadable();
				$package['contents'][ $item_id ]['virtual'] = $_product->get_virtual();
			}
			
			$package['site']['locale'] = get_locale();
			$package['shipping_method']['instance_id'] = $this->instance_id;
			$package['debug'] = $this->get_option('debug');
		

		  $endpoint = $this->get_option( 'endpoint' );
			$ch = curl_init( $endpoint );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $package ) );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 1 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 1 );

			$response_json = curl_exec( $ch );
			$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); 
			$curl_errno = curl_errno( $ch );
			$curl_error = curl_error( $ch );
			
			//throw new Exception( 'blah' ); // to test catch block

			curl_close( $ch );
			
		} catch ( Exception $e ) {
			// Errors are not the fault of the customer, and we don't want to lose business, so we offer free shipping.
			$this->add_free_shipping( "E" . $e->getCode(), $package ); // "E" short for "Exception"
			return;
		}

		// Errors are not the fault of the customer, and we don't want to lose business, so we offer free shipping.
		if ( 0 < $curl_errno ) {
			$this->add_free_shipping( "C$curl_errno", $package );  // "C" short for "Curl error"
			
		} elseif ( 200 != $status ) {
			$this->add_free_shipping( "S$status", $package ); // "S" short for "Server error"
			
		} elseif ( ! $response_json ) {
			$this->add_free_shipping( "Y", $package ); // "Y" short for "emptY response"
			
		} else {
			// successful request
			$response = json_decode( $response_json, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				// received invalid JSON
				$this->add_free_shipping( "J", $package ); // "J" short for "Json error"
				return;
			}
			
			// received valid JSON response
			$this->last_response = $response;
			$rates = $response['rates'];
			foreach ( $rates as $rate ) {
				// Register all shipping rates
				$r = array(
					'id' => $rate['id'],
					'label' => $rate['label'],
					'cost' => $rate['cost']
				);
				$this->add_rate( $r, $package );
			}
			
			$notices = $response['notices'];
			foreach ( $notices['notices'] as $txt ) {
				wc_add_notice( $txt, 'notice' );
			}
			foreach ( $notices['successes'] as $txt ) {
				wc_add_notice( $txt, 'success' );
			}
			foreach ( $notices['errors'] as $txt ) {
				wc_add_notice( $txt, 'error' );
			}
		}
	}
	
	public function add_free_shipping( $reason = '', $package = array() ) {
		$fallback_cost = $this->get_option('fallback_cost');
		$rate = array(
			'id' => $this->id,
			'label' => "E" . $reason,
			'cost' => $fallback_cost
		);
		$this->add_rate( $rate, $package );
	}
}
