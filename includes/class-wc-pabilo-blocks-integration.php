<?php
/**
 * WooCommerce Blocks Integration for Pabilo Gateway
 *
 * @package WC_Pabilo_Gateway
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Pabilo payment method integration for WooCommerce Blocks
 */
final class WC_Pabilo_Blocks_Integration extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Pabilo_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'pabilo_gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_pabilo_gateway_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = isset( $gateways['pabilo_gateway'] ) ? $gateways['pabilo_gateway'] : null;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway && $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/pabilo-blocks.js';
		$script_asset_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/js/pabilo-blocks.asset.php';
		$script_url        = plugins_url( $script_path, dirname( __FILE__ ) );
		
		// Try to load asset file if it exists (for builds), otherwise use defaults
		$script_asset = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( plugin_dir_path( dirname( __FILE__ ) ) . $script_path )
			);

		wp_register_script(
			'wc-pabilo-blocks-integration',
			$script_url,
			array_merge( $script_asset['dependencies'], array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n' ) ),
			$script_asset['version'],
			true
		);

		// Localize script with payment method data
		wp_localize_script(
			'wc-pabilo-blocks-integration',
			'wc_pabilo_params',
			array(
				'title'       => $this->get_setting( 'title' ),
				'description' => $this->get_setting( 'description' ),
			)
		);

		return array( 'wc-pabilo-blocks-integration' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'supports'    => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
			'icon'        => $this->gateway->icon,
		);
	}
}
