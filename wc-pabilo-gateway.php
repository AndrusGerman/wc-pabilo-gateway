<?php
/**
 * Plugin Name: Pabilo Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/AndrusGerman/wc-pabilo-gateway
 * Description: Accept Pago Móvil and bank transfers from Venezuela (Banco de Venezuela, Mercantil, Banesco, Provincial) via Pabilo.
 * Version: 1.0.4
 * Author: Pabilo
 * Author URI: https://github.com/AndrusGerman
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pabilo-payment-gateway
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'wc_pabilo_gateway_init', 11 );


function wc_pabilo_gateway_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	class WC_Pabilo_Gateway extends WC_Payment_Gateway {

		public function __construct() {
			$this->id                 = 'pabilo_gateway';
			$this->icon               = 'https://pabilo.app/pabilo_favicon_light.png'; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields         = false;
			$this->method_title       = __( 'Pasarela de Pago Pabilo', 'pabilo-payment-gateway' );
			$this->method_description = __( 'Acepta pagos vía Pago Móvil y Transferencia Bancaria (Banco de Venezuela, Mercantil, Banesco, Provincial) de forma segura con Pabilo.', 'pabilo-payment-gateway' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->enabled      = $this->get_option( 'enabled' ); // Explicitly load enabled status
			$this->api_key      = $this->get_option( 'api_key' );
			$this->user_bank_id = $this->get_option( 'user_bank_id' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_api_wc_pabilo_gateway', array( $this, 'webhook_handler' ) );
		}

		/**
		 * Check if the gateway is available for use.
		 *
		 * @return bool
		 */
		public function is_available() {
			// Check if enabled (strict check)
			$is_available = ( 'yes' === $this->enabled );

			if ( ! $is_available ) {
				return false;
			}

			if ( empty( $this->api_key ) || empty( $this->user_bank_id ) ) {
				return false;
			}
			
			// Force return true if passed checks
			return true;
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Activar/Desactivar', 'pabilo-payment-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Activar Pago con Pabilo', 'pabilo-payment-gateway' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Título', 'pabilo-payment-gateway' ),
					'type'        => 'text',
					'description' => __( 'Este título es el que el usuario ve durante el pago.', 'pabilo-payment-gateway' ),
					'default'     => __( 'Pago Móvil / Transferencia (Pabilo)', 'pabilo-payment-gateway' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Descripción', 'pabilo-payment-gateway' ),
					'type'        => 'textarea',
					'description' => __( 'Descripción del método de pago que el cliente verá en el checkout.', 'pabilo-payment-gateway' ),
					'default'     => __( 'Realiza tu pago de forma segura con Pago Móvil o Transferencia Bancaria.', 'pabilo-payment-gateway' ),
					'desc_tip'    => true,
				),
				'api_key' => array(
					'title'       => __( 'API Key', 'pabilo-payment-gateway' ),
					'type'        => 'password',
					'description' => __( 'Ingresa tu API Key de Pabilo. Se conectará automáticamente para obtener tu usuario.', 'pabilo-payment-gateway' ),
					'desc_tip'    => true,
				),
				'user_bank_id' => array(
					'title'       => __( 'Cuenta Bancaria', 'pabilo-payment-gateway' ),
					'type'        => 'select',
					'description' => __( 'Selecciona la cuenta bancaria para recibir los pagos.', 'pabilo-payment-gateway' ),
					'options'     => $this->get_bank_accounts_and_user_info(),
					'default'     => '',
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Fetch user info and bank accounts from Pabilo API
		 */
		private function get_bank_accounts_and_user_info() {
			$options = array();
			
			// Only run this in admin to avoid frontend slowdowns
			if ( ! is_admin() ) {
				return $options;
			}

			// Get values directly from options because this might be called before init_settings
			$settings = get_option( 'woocommerce_pabilo_gateway_settings' );
			$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';

			if ( empty( $api_key ) ) {
				$options[''] = __( 'Guarda tu API Key primero', 'pabilo-payment-gateway' );
				return $options;
			}

			// 0. Get User Info (/me) for security validation
			$response_me = wp_remote_get( 'https://api.pabilo.app/me', array(
				'headers' => array(
					'appkey' => $api_key,
				),
				'timeout' => 15,
			) );

			if ( ! is_wp_error( $response_me ) ) {
				$body_me = wp_remote_retrieve_body( $response_me );
				$me_data = json_decode( $body_me, true );

				if ( isset( $me_data['user']['id'] ) ) {
					update_option( 'wc_pabilo_admin_user_id', $me_data['user']['id'] );
				}
			}

			// 1. Get User Plan (/me/plan)
			$response_plan = wp_remote_get( 'https://api.pabilo.app/me/plan', array(
				'headers' => array(
					'appkey' => $api_key,
				),
				'timeout' => 15,
			) );

			$plan_info = '';
			if ( ! is_wp_error( $response_plan ) ) {
				$body_plan = wp_remote_retrieve_body( $response_plan );
				$plan_data = json_decode( $body_plan, true );
				
				if ( ! empty( $plan_data['name'] ) ) {
					$plan_name = $plan_data['name'];
					$plan_type = isset( $plan_data['planType'] ) ? $plan_data['planType'] : '';
					/* translators: %s: plan name from Pabilo API */
					$plan_info = ' - ' . sprintf( __( 'Plan: %s', 'pabilo-payment-gateway' ), $plan_name );
					if ( ! empty( $plan_type ) ) {
						$plan_info .= ' (' . $plan_type . ')';
					}
				}
			}

            // Add a disabled option to show connection status and plan
            $options[''] = __( 'Conectado', 'pabilo-payment-gateway' ) . $plan_info;

            // 2. Get Bank Accounts (/me/usersbank)
			$response_banks = wp_remote_get( 'https://api.pabilo.app/me/usersbank', array(
				'headers' => array(
					'appkey' => $api_key,
				),
				'timeout' => 15,
			) );

			if ( is_wp_error( $response_banks ) ) {
				$options[''] = __( 'Error obteniendo cuentas de banco', 'pabilo-payment-gateway' );
				return $options;
			}

			$body_banks = wp_remote_retrieve_body( $response_banks );
			$data_banks = json_decode( $body_banks, true );
			
			// Handle the structure: { "message": "...", "user_banks": [...] }
			$banks = array();
			if ( isset( $data_banks['user_banks'] ) && is_array( $data_banks['user_banks'] ) ) {
				$banks = $data_banks['user_banks'];
			} elseif ( is_array( $data_banks ) ) {
				// Fallback if the array is directly returned
				$banks = $data_banks;
			}

			if ( ! empty( $banks ) ) {
				foreach ( $banks as $account ) {
					// Use 'payment_link' flag or check if it's not in trash
					// The user provided JSON shows 'payment_link': true
					if ( ( isset( $account['payment_link'] ) && $account['payment_link'] === true ) && ( ! isset( $account['to_trash'] ) || $account['to_trash'] === false ) ) {
						
						$label = isset( $account['description'] ) ? $account['description'] : 'Cuenta';
						
						// Add provider info if available
						if ( isset( $account['provider'] ) ) {
							// Map providers to readable names if possible, otherwise use code
							$provider_name = $account['provider'];
							if ( 'VE_BAN' === $provider_name ) $provider_name = 'Banco de Venezuela';
							if ( 'VE_MER' === $provider_name || 'MERCANTIL_EMP_TEST_V1' === $provider_name ) $provider_name = 'Mercantil';
							
							$label .= ' - ' . $provider_name;
						}

						// Add account number (last 4 digits)
						if ( isset( $account['default_bank_account']['account_number'] ) ) {
							$acc_num = $account['default_bank_account']['account_number'];
							if ( strlen( $acc_num ) > 4 ) {
								$label .= ' (... ' . substr( $acc_num, -4 ) . ')';
							} else {
								$label .= ' (' . $acc_num . ')';
							}
						} elseif ( isset( $account['user_bank_phone']['number'] ) ) {
							// Fallback to phone number if it's a mobile payment account without account number visible
							$label .= ' (Telf: ' . $account['user_bank_phone']['number'] . ')';
						}
						
						$options[ $account['id'] ] = $label;
					}
				}
			}

			if ( count( $options ) <= 1 ) { // Only default option exists
				$options[''] = __( 'No se encontraron cuentas habilitadas para link de pago', 'pabilo-payment-gateway' );
			}

			return $options;
		}

		/**
		 * Process the payment and return the result
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			$api_key      = $this->get_option( 'api_key' );
			$user_bank_id = $this->get_option( 'user_bank_id' );

			if ( empty( $api_key ) || empty( $user_bank_id ) ) {
				wc_add_notice( __( 'Error de pago: Falta configuración.', 'pabilo-payment-gateway' ), 'error' );
				return array(
					'result' => 'failure',
				);
			}

			// Prepare payload - format must match Pabilo API expectations
			$amount = floatval( $order->get_total() );
			$payload = array(
				'name'                     => '',
				'amount'                   => $amount,
				'is_usd'                   => get_woocommerce_currency() === 'USD',
				/* translators: %s: WooCommerce order number */
				'description'              => sprintf( __( 'Orden #%s', 'pabilo-payment-gateway' ), $order->get_order_number() ),
				'notification_by_whastapp' => false,
				'webhook_url'              => add_query_arg( 'order_id', $order_id, WC()->api_request_url( 'WC_Pabilo_Gateway' ) ),
				'user_bank_id'             => $user_bank_id,
				'redirect_url'             => $this->get_return_url( $order ),
			);

			$response = wp_remote_post( 'https://api.pabilo.app/v1/paymentlink', array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'appkey'       => $api_key,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 45,
			) );

			if ( is_wp_error( $response ) ) {
				wc_add_notice( __( 'Error de conexión:', 'pabilo-payment-gateway' ) . ' ' . $response->get_error_message(), 'error' );
				return array(
					'result' => 'failure',
				);
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Debug API Response (only when WC logging is enabled)
			$logger = wc_get_logger();
			$logger->debug( 'Pabilo Payment Link Response: ' . $body, array( 'source' => 'pabilo-gateway' ) );

			// Check for API error response (has "error" field, e.g. BAD_REQUEST)
			if ( ! empty( $data['error'] ) ) {
				$error_msg = isset( $data['message'] ) ? $data['message'] : $data['error'];
				$order->add_order_note( 'Error API Pabilo: ' . $error_msg );
				wc_add_notice( __( 'Error de pago:', 'pabilo-payment-gateway' ) . ' ' . esc_html( $error_msg ), 'error' );
				return array(
					'result' => 'failure',
				);
			}

			// API returns: { "message": "...", "paymentlink": { "url": "https://pabilo.app/pay/..." } }
			$payment_url = '';
			if ( isset( $data['paymentlink']['url'] ) ) {
				$payment_url = $data['paymentlink']['url'];
			} elseif ( isset( $data['url'] ) ) {
				$payment_url = $data['url'];
			} elseif ( isset( $data['payment_link']['url'] ) ) {
				$payment_url = $data['payment_link']['url'];
			} elseif ( isset( $data['payment_link'] ) && is_string( $data['payment_link'] ) ) {
				$payment_url = $data['payment_link'];
			} elseif ( isset( $data['link'] ) ) {
				$payment_url = $data['link'];
			}

			if ( ! empty( $payment_url ) ) {
                // Security: Store Payment Link ID (if available) to verify webhook later
                $link_id = '';
                if ( isset( $data['paymentlink']['id'] ) ) {
                    $link_id = $data['paymentlink']['id'];
                } elseif ( isset( $data['id'] ) ) {
                    $link_id = $data['id'];
                } elseif ( isset( $data['payment_link']['id'] ) ) {
                    $link_id = $data['payment_link']['id'];
                } elseif ( isset( $data['uuid'] ) ) {
                     $link_id = $data['uuid'];
                }
                
                if ( ! empty( $link_id ) ) {
                    $order->update_meta_data( '_pabilo_payment_link_id', $link_id );
                    $order->save();
                }

				return array(
					'result'   => 'success',
					'redirect' => $payment_url,
				);
			}

			// Log the unexpected response for debugging
			$order->add_order_note( 'Error API Pabilo: Respuesta inesperada. ' . esc_html( substr( $body, 0, 500 ) ) );
			wc_add_notice( __( 'Error de pago: No se pudo generar el enlace de pago.', 'pabilo-payment-gateway' ), 'error' );
			return array(
				'result' => 'failure',
			);
		}

		/**
		 * Verify payment status with Pabilo API before trusting webhook (security mitigation).
		 *
		 * @param string $payment_link_id The payment link ID from the webhook.
		 * @param string $api_key         The Pabilo API key.
		 * @return bool True if API confirms status is 'paid'.
		 */
		private function verify_payment_status_via_api( $payment_link_id, $api_key ) {
			if ( empty( $payment_link_id ) || empty( $api_key ) ) {
				return false;
			}

			$response = wp_remote_get(
				'https://api.pabilo.app/paymentlink/' . sanitize_text_field( $payment_link_id ) . '/info',
				array(
					'headers' => array( 'appkey' => $api_key ),
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
            
            // 1. Verify Status
			$status = isset( $data['data']['payment_link']['status'] ) ? strtolower( $data['data']['payment_link']['status'] ) : '';
            if ( 'paid' !== $status ) {
                return false;
            }
            
            // 2. Verify User ID (Security: Prevent payment link spoofing from another account)
            $link_user_id = isset( $data['data']['payment_link']['user_id'] ) ? $data['data']['payment_link']['user_id'] : '';
            $admin_user_id = get_option( 'wc_pabilo_admin_user_id' );
            
            if ( ! empty( $admin_user_id ) && (string) $link_user_id !== (string) $admin_user_id ) {
                $logger = wc_get_logger();
                $logger->warning( "Pabilo Security Violation: Payment Link User ID ({$link_user_id}) does not match Store Admin ID ({$admin_user_id}).", array( 'source' => 'pabilo-gateway' ) );
                return false;
            }

			return true;
		}

		/**
		 * Handle webhook from Pabilo. Verifies payment status via API before marking order complete.
		 */
		public function webhook_handler() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Webhook from external Pabilo API; order_id in URL is validated via API verification.
			$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

			if ( ! $order_id ) {
				status_header( 400 );
				exit;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				status_header( 404 );
				exit;
			}

			$body = file_get_contents( 'php://input' );
			$data = json_decode( $body, true );

			if ( ! is_array( $data ) ) {
				status_header( 400 );
				exit;
			}

			// Check for payment status in the payload
			$status = isset( $data['status'] ) ? strtolower( (string) $data['status'] ) : '';
			$payment_link_id = isset( $data['payment_link_id'] ) ? sanitize_text_field( $data['payment_link_id'] ) : '';

			if ( 'paid' === $status ) {
                // Security 1: Check against stored ID in order meta
                $stored_link_id = $order->get_meta( '_pabilo_payment_link_id' );
                if ( ! empty( $stored_link_id ) && $stored_link_id !== $payment_link_id ) {
                    $order->add_order_note( __( 'Webhook rechazado: ID del link de pago no coincide con el guardado en la orden.', 'pabilo-payment-gateway' ) );
                    status_header( 400 ); // Bad request
                    exit;
                }
                
				// Security 2: verify with Pabilo API before marking as paid
				$api_key = $this->get_option( 'api_key' );
				if ( ! $this->verify_payment_status_via_api( $payment_link_id, $api_key ) ) {
					$order->add_order_note( __( 'Webhook Pabilo rechazado: verificación API fallida (status no es paid o user_id incorrecto).', 'pabilo-payment-gateway' ) );
					status_header( 200 );
					exit;
				}

				$order->payment_complete( $payment_link_id );

				$note = __( 'Pago Pabilo verificado vía webhook.', 'pabilo-payment-gateway' );
				if ( $payment_link_id ) {
					$note .= ' ID: ' . $payment_link_id;
				}
				if ( ! empty( $data['user_bank_payment']['bank_reference_id'] ) ) {
					$note .= ' Ref: ' . sanitize_text_field( $data['user_bank_payment']['bank_reference_id'] );
				}
				$order->add_order_note( $note );
			} elseif ( in_array( $status, array( 'failed', 'canceled', 'expired' ), true ) ) {
				$order->update_status( 'failed', __( 'Pago Pabilo fallido o cancelado.', 'pabilo-payment-gateway' ) );
			}

			status_header( 200 );
			exit;
		}
	}
}

add_filter( 'woocommerce_payment_gateways', 'wc_pabilo_add_gateway' );

function wc_pabilo_add_gateway( $methods ) {
	$methods[] = 'WC_Pabilo_Gateway';
	return $methods;
}

/**
 * Add support for WooCommerce Blocks Checkout
 */
add_action( 'woocommerce_blocks_loaded', 'wc_pabilo_gateway_block_support' );

function wc_pabilo_gateway_block_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	// Include the Blocks integration class
	$blocks_file = plugin_dir_path( __FILE__ ) . 'includes/class-wc-pabilo-blocks-integration.php';
	
	if ( ! file_exists( $blocks_file ) ) {
		return;
	}
	
	require_once $blocks_file;

	// Register the payment method with Blocks
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new WC_Pabilo_Blocks_Integration() );
		}
	);
}
