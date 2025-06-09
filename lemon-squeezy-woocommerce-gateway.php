<?php
/**
 * Plugin Name: Lemon Squeezy for WooCommerce
 * Plugin URI:  https://louisoft.com
 * Description: Integrates Lemon Squeezy payment gateway with WooCommerce for one-time and subscription payments. Supports simple and variable products.
 * Version:     1.8.4
 * Author:      Loquisoft
 * Author URI:  https://proxyle.com
 * License:     Commercial License
 * License URI: https://loquisoft.com/license
 * Text Domain: lemon-squeezy-woocommerce-gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active.
 */
function lemon_squeezy_woocommerce_active() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true ) ) {
		return true;
	}
	if ( is_multisite() ) {
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}
	}
	return false;
}

if ( ! lemon_squeezy_woocommerce_active() ) {
	add_action( 'admin_notices', 'lemon_squeezy_woocommerce_notice' );
	return;
}

function lemon_squeezy_woocommerce_notice() {
	echo '<div class="error"><p><strong>Lemon Squeezy for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * Register the payment gateway.
 */
add_filter( 'woocommerce_payment_gateways', 'lemon_squeezy_add_gateway_class' );
function lemon_squeezy_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Lemon_Squeezy_Gateway';
	return $gateways;
}

/**
 * Initialize the gateway class.
 */
add_action( 'plugins_loaded', 'lemon_squeezy_init_gateway_class' );
function lemon_squeezy_init_gateway_class() {

	class WC_Lemon_Squeezy_Gateway extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'lemon_squeezy';
			$this->icon               = ''; // URL of the icon to display on checkout (optional)
			$this->has_fields         = false;
			$this->method_title       = 'Lemon Squeezy';
			$this->method_description = 'Accept payments through Lemon Squeezy. Supports one-time payments and subscriptions.';

			// Declare support for subscriptions and refunds.
			$this->supports = array(
				'products',
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change_admin',
				'subscription_payment_method_change_customer',
				'multiple_subscriptions',
				'refunds',
			);

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Load user-provided settings.
			$this->title            = $this->get_option( 'title' );
			$this->description      = $this->get_option( 'description' );
			$this->api_key          = $this->get_option( 'api_key' );
			$this->store_id         = $this->get_option( 'store_id' );
			$this->webhook_secret   = $this->get_option( 'webhook_secret' );
			$this->test_mode        = 'yes' === $this->get_option( 'test_mode' );
			$this->support_variants = 'yes' === $this->get_option( 'support_variants' );

			// Hooks for updating settings and webhook handling.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_api_lemon_squeezy_webhook', array( $this, 'handle_webhook' ) );
		}

		/**
		 * Initialize Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => 'Enable/Disable',
					'type'    => 'checkbox',
					'label'   => 'Enable Lemon Squeezy Payment',
					'default' => 'yes',
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Lemon Squeezy',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Pay securely using Lemon Squeezy.',
				),
				'api_key' => array(
					'title'       => 'API Key',
					'type'        => 'password',
					'description' => 'Enter your Lemon Squeezy API key.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'store_id' => array(
					'title'       => 'Store ID',
					'type'        => 'text',
					'description' => 'Enter your Lemon Squeezy Store ID (e.g., 153951).',
					'default'     => '',
					'desc_tip'    => true,
				),
				'webhook_secret' => array(
					'title'       => 'Webhook Secret',
					'type'        => 'password',
					'description' => 'Enter your Lemon Squeezy Webhook Secret for verifying incoming webhooks.',
					'default'     => '',
					'desc_tip'    => true,
				),
				'test_mode' => array(
					'title'       => 'Test Mode',
					'type'        => 'checkbox',
					'label'       => 'Enable Test Mode',
					'description' => 'Place the payment gateway in test mode using Lemon Squeezy\'s sandbox environment.',
					'default'     => 'no',
				),
				'support_variants' => array(
					'title'       => 'Enable Variable Products Support',
					'type'        => 'checkbox',
					'label'       => 'Support Variable Products and Subscriptions',
					'description' => 'Check to enable support for variable products and variable subscriptions.',
					'default'     => 'no',
				),
			);
		}

		/**
		 * Process the payment and return the result.
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Get order items.
			$items = $order->get_items();
			if ( empty( $items ) ) {
				wc_add_notice( 'No items found in the order.', 'error' );
				return;
			}

			// For simplicity, assume one product per order.
			$item     = reset( $items );
			$product  = $item->get_product();
			$quantity = $item->get_quantity();

			// Retrieve the Lemon Squeezy Variant ID (for simple or variable product).
			$lemon_variant_id = get_post_meta( $product->get_id(), '_lemon_squeezy_variant_id', true );
			if ( ! $lemon_variant_id ) {
				wc_add_notice( 'Payment error: Lemon Squeezy Variant ID not set for this product.', 'error' );
				return;
			}

			// Generate the return URL (usually the thank you page).
			$return_url = $this->get_return_url( $order );

			// Create a Lemon Squeezy checkout session via the API.
			$checkout_response = $this->create_lemon_squeezy_checkout( $order, $lemon_variant_id, $quantity, $return_url );

			if ( is_wp_error( $checkout_response ) ) {
				wc_add_notice( 'Payment error: ' . $checkout_response->get_error_message(), 'error' );
				return;
			}

			if ( $checkout_response && isset( $checkout_response['checkout_url'] ) ) {
				// Save the checkout identifier using a consistent meta key.
				if ( isset( $checkout_response['checkout_id'] ) ) {
					$order->update_meta_data( '_lemon_squeezy_checkout_id', $checkout_response['checkout_id'] );
					$order->save();
				}

				// Update the order status to pending payment.
				$order->update_status( 'pending', 'Awaiting payment through Lemon Squeezy.' );

				// Empty the cart.
				WC()->cart->empty_cart();

				// Redirect to the Lemon Squeezy hosted checkout page.
				return array(
					'result'   => 'success',
					'redirect' => $checkout_response['checkout_url'],
				);
			} else {
				wc_add_notice( 'An unexpected error occurred.', 'error' );
				return;
			}
		}

		/**
		 * Create a checkout session with Lemon Squeezy via API.
		 */
		private function create_lemon_squeezy_checkout( $order, $variant_id, $quantity, $return_url ) {
			$data = array(
				'data' => array(
					'type'       => 'checkouts',
					'attributes' => array(
						'custom_price'    => null,
						'product_options' => array(
							'redirect_url' => $return_url,
						),
						'checkout_options' => array(
							'embed'                => false,
							'media'                => true,
							'logo'                 => true,
							'desc'                 => true,
							'discount'             => true,
							'skip_trial'           => false,
							'subscription_preview' => true,
						),
						'checkout_data' => array(
							'email'           => $order->get_billing_email(),
							'name'            => $order->get_formatted_billing_full_name(),
							'custom'          => array(
								'order_id' => strval( $order->get_id() ),
							),
							'billing_address' => array(
								'country' => $order->get_billing_country(),
								'zip'     => $order->get_billing_postcode(),
							),
							// Include variant quantities.
							'variant_quantities' => array(
								array(
									'variant_id' => (int) $variant_id,
									'quantity'   => (int) $quantity,
								),
							),
						),
						'test_mode' => $this->test_mode,
					),
					'relationships' => array(
						'store' => array(
							'data' => array(
								'type' => 'stores',
								'id'   => strval( $this->store_id ),
							),
						),
						'variant' => array(
							'data' => array(
								'type' => 'variants',
								'id'   => strval( $variant_id ),
							),
						),
					),
				),
			);

			$response = $this->send_request( 'POST', '/v1/checkouts', $data );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['data']['attributes']['url'] ) ) {
				return array(
					'checkout_url' => $body['data']['attributes']['url'],
					'checkout_id'  => $body['data']['id'],
				);
			} else {
				return new WP_Error( 'lemon_squeezy_error', 'Unable to create checkout session.' );
			}
		}

		/**
		 * Send an API request to Lemon Squeezy.
		 */
		private function send_request( $method, $endpoint, $body = null ) {
			$base_url = 'https://api.lemonsqueezy.com';
			$url      = $base_url . $endpoint;
			$args     = array(
				'method'    => $method,
				'headers'   => array(
					'Accept'        => 'application/vnd.api+json',
					'Content-Type'  => 'application/vnd.api+json',
					'Authorization' => 'Bearer ' . $this->api_key,
				),
				'timeout'   => 60,
				'sslverify' => true,
			);

			if ( $body ) {
				$args['body'] = wp_json_encode( $body );
			}

			$response = wp_remote_request( $url, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			if ( $status_code >= 200 && $status_code < 300 ) {
				return $response;
			} else {
				return new WP_Error( 'lemon_squeezy_api_error', 'Lemon Squeezy API error: ' . wp_remote_retrieve_body( $response ) );
			}
		}

		/**
		 * Handle incoming webhooks from Lemon Squeezy.
		 */
		public function handle_webhook() {
			$logger = wc_get_logger();
			$logger->info( 'Lemon Squeezy Webhook handler initiated.', array( 'source' => 'lemon_squeezy' ) );

			$raw_body  = file_get_contents( 'php://input' );
			$signature = isset( $_SERVER['HTTP_X_LEMONSQUEEZY_SIGNATURE'] ) ? sanitize_text_field( $_SERVER['HTTP_X_LEMONSQUEEZY_SIGNATURE'] ) : '';

			$logger->info( 'Raw webhook payload: ' . $raw_body, array( 'source' => 'lemon_squeezy' ) );
			$logger->info( 'Received signature: ' . $signature, array( 'source' => 'lemon_squeezy' ) );

			// Verify the webhook signature.
			if ( ! $this->verify_webhook( $raw_body, $signature ) ) {
				$logger->error( 'Webhook signature verification failed.', array( 'source' => 'lemon_squeezy' ) );
				status_header( 400 );
				exit;
			}

			$data = json_decode( $raw_body, true );
			if ( ! isset( $data['meta']['event_name'] ) ) {
				$logger->error( 'Invalid webhook payload: missing event_name.', array( 'source' => 'lemon_squeezy' ) );
				status_header( 200 );
				exit;
			}

			$event_type = $data['meta']['event_name'];
			$attributes = isset( $data['data']['attributes'] ) ? $data['data']['attributes'] : array();

			// Get the Lemon Squeezy order_id (i.e. the checkout id).
			$lemon_squeezy_order_id = isset( $data['data']['id'] ) ? $data['data']['id'] : '';
			if ( empty( $lemon_squeezy_order_id ) ) {
				$logger->error( 'Missing Lemon Squeezy order_id in webhook payload.', array( 'source' => 'lemon_squeezy' ) );
				status_header( 200 );
				exit;
			}

			$logger->info( 'Lemon Squeezy order_id received: ' . $lemon_squeezy_order_id, array( 'source' => 'lemon_squeezy' ) );

			// Locate the WooCommerce order using the _lemon_squeezy_checkout_id meta value.
			$query  = new WC_Order_Query( array(
				'limit'      => 1,
				'meta_key'   => '_lemon_squeezy_checkout_id',
				'meta_value' => $lemon_squeezy_order_id,
			) );
			$orders = $query->get_orders();

			if ( ! empty( $orders ) ) {
				$order = $orders[0];
				if ( $order && $order->get_payment_method() === $this->id ) {
					$logger->info( 'Matching order found: ' . $order->get_id(), array( 'source' => 'lemon_squeezy' ) );
					switch ( $event_type ) {
						case 'order_created':
							if ( isset( $attributes['status'] ) && 'paid' === $attributes['status'] ) {
								// Mark the order as complete using the Lemon Squeezy order_id.
								$order->payment_complete( $lemon_squeezy_order_id );
								$order->add_order_note( 'Payment completed via Lemon Squeezy.' );
								$logger->info( 'Order ' . $order->get_id() . ' marked as complete.', array( 'source' => 'lemon_squeezy' ) );
							}
							break;

						case 'order_refunded':
							$order->update_status( 'refunded', 'Order refunded via Lemon Squeezy.' );
							$logger->info( 'Order ' . $order->get_id() . ' marked as refunded.', array( 'source' => 'lemon_squeezy' ) );
							break;

						// Subscription-related events use the same checkout id.
						case 'subscription_created':
						case 'subscription_updated':
						case 'subscription_cancelled':
						case 'subscription_resumed':
						case 'subscription_expired':
						case 'subscription_paused':
						case 'subscription_unpaused':
						case 'subscription_payment_failed':
						case 'subscription_payment_success':
						case 'subscription_payment_recovered':
						case 'subscription_payment_refunded':
						case 'subscription_plan_changed':
							$subscriptions = wcs_get_subscriptions_for_order( $order );
							foreach ( $subscriptions as $subscription ) {
								$logger->info( 'Processing subscription event for Subscription ID: ' . $subscription->get_id(), array( 'source' => 'lemon_squeezy' ) );
								switch ( $event_type ) {
									case 'subscription_created':
										$subscription->update_status( 'active', 'Subscription created via Lemon Squeezy.' );
										$logger->info( 'Subscription marked as active.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_updated':
										$logger->info( 'Subscription updated.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_cancelled':
										$subscription->update_status( 'cancelled', 'Subscription cancelled via Lemon Squeezy.' );
										$logger->info( 'Subscription cancelled.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_resumed':
									case 'subscription_unpaused':
										$subscription->update_status( 'active', 'Subscription resumed via Lemon Squeezy.' );
										$logger->info( 'Subscription resumed.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_expired':
										$subscription->update_status( 'expired', 'Subscription expired via Lemon Squeezy.' );
										$logger->info( 'Subscription expired.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_paused':
										$subscription->update_status( 'on-hold', 'Subscription paused via Lemon Squeezy.' );
										$logger->info( 'Subscription paused.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_payment_failed':
										$subscription->payment_failed();
										$subscription->update_status( 'on-hold', 'Subscription payment failed via Lemon Squeezy.' );
										$logger->info( 'Subscription payment failure processed.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_payment_success':
									case 'subscription_payment_recovered':
										$subscription->payment_complete();
										$subscription->add_order_note( 'Subscription payment successful via Lemon Squeezy.' );
										$logger->info( 'Subscription payment completed.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_payment_refunded':
										$subscription->add_order_note( 'Subscription payment refunded via Lemon Squeezy.' );
										$logger->info( 'Subscription payment refunded.', array( 'source' => 'lemon_squeezy' ) );
										break;
									case 'subscription_plan_changed':
										$logger->info( 'Subscription plan changed.', array( 'source' => 'lemon_squeezy' ) );
										break;
									default:
										$logger->warning( 'Unhandled subscription event type: ' . $event_type, array( 'source' => 'lemon_squeezy' ) );
										break;
								}
								// Update subscription meta with _lemon_squeezy_checkout_id (identifier for all events)
								if ( isset( $attributes['id'] ) ) {
									$subscription->update_meta_data( '_lemon_squeezy_checkout_id', $attributes['id'] );
									$subscription->save();
								}
							}
							break;

						default:
							$logger->warning( 'Unhandled event type: ' . $event_type, array( 'source' => 'lemon_squeezy' ) );
							break;
					}
				} else {
					$logger->error( 'Order not found or payment method mismatch for checkout id: ' . $lemon_squeezy_order_id, array( 'source' => 'lemon_squeezy' ) );
				}
			} else {
				$logger->error( 'No matching order found for Lemon Squeezy checkout id: ' . $lemon_squeezy_order_id, array( 'source' => 'lemon_squeezy' ) );
			}

			status_header( 200 );
			exit;
		}

		/**
		 * Verify the webhook signature using our webhook secret.
		 */
		private function verify_webhook( $body, $signature ) {
			$logger = wc_get_logger();
			if ( empty( $this->webhook_secret ) ) {
				$logger->error( 'Webhook secret is not set.', array( 'source' => 'lemon_squeezy' ) );
				return false;
			}

			$computed_signature = hash_hmac( 'sha256', $body, $this->webhook_secret );
			$logger->info( 'Computed signature: ' . $computed_signature, array( 'source' => 'lemon_squeezy' ) );
			$logger->info( 'Received signature: ' . $signature, array( 'source' => 'lemon_squeezy' ) );

			if ( hash_equals( $computed_signature, $signature ) ) {
				$logger->info( 'Webhook signature verification succeeded.', array( 'source' => 'lemon_squeezy' ) );
				return true;
			} else {
				$logger->error( 'Webhook signature mismatch.', array( 'source' => 'lemon_squeezy' ) );
				return false;
			}
		}
	}
}

/**
 * Add custom field for Lemon Squeezy Variant ID to simple products.
 */
add_action( 'woocommerce_product_options_general_product_data', 'lemon_squeezy_add_custom_fields' );
function lemon_squeezy_add_custom_fields() {
	echo '<div class="options_group">';
	woocommerce_wp_text_input( array(
		'id'          => '_lemon_squeezy_variant_id',
		'label'       => 'Lemon Squeezy Variant ID',
		'placeholder' => '',
		'description' => 'Enter the corresponding Lemon Squeezy Variant ID.',
		'desc_tip'    => true,
	) );
	echo '</div>';
}

/**
 * Save custom field for Lemon Squeezy Variant ID for simple products.
 */
add_action( 'woocommerce_process_product_meta', 'lemon_squeezy_save_custom_fields' );
function lemon_squeezy_save_custom_fields( $post_id ) {
	$lemon_squeezy_variant_id = isset( $_POST['_lemon_squeezy_variant_id'] ) ? sanitize_text_field( $_POST['_lemon_squeezy_variant_id'] ) : '';
	update_post_meta( $post_id, '_lemon_squeezy_variant_id', $lemon_squeezy_variant_id );
}

/**
 * Add custom field for Lemon Squeezy Variant ID to product variations.
 */
add_action( 'woocommerce_product_after_variable_attributes', 'lemon_squeezy_add_variation_custom_fields', 10, 3 );
function lemon_squeezy_add_variation_custom_fields( $loop, $variation_data, $variation ) {
	woocommerce_wp_text_input( array(
		'id'          => '_lemon_squeezy_variant_id[' . $variation->ID . ']',
		'name'        => '_lemon_squeezy_variant_id[' . $variation->ID . ']',
		'label'       => 'Lemon Squeezy Variant ID',
		'placeholder' => '',
		'description' => 'Enter the corresponding Lemon Squeezy Variant ID for this variation.',
		'desc_tip'    => true,
		'value'       => get_post_meta( $variation->ID, '_lemon_squeezy_variant_id', true ),
	) );
}

/**
 * Save custom field for Lemon Squeezy Variant ID for product variations.
 */
add_action( 'woocommerce_save_product_variation', 'lemon_squeezy_save_variation_custom_fields', 10, 2 );
function lemon_squeezy_save_variation_custom_fields( $variation_id, $i ) {
	$lemon_squeezy_variant_id = isset( $_POST['_lemon_squeezy_variant_id'][ $variation_id ] ) ? sanitize_text_field( $_POST['_lemon_squeezy_variant_id'][ $variation_id ] ) : '';
	update_post_meta( $variation_id, '_lemon_squeezy_variant_id', $lemon_squeezy_variant_id );
}
?>
