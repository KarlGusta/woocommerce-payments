<?php
/**
 * WooCommerce Payments Multi Currency Frontend Prices
 *
 * @package WooCommerce\Payments
 */

namespace WCPay\Multi_Currency;

defined( 'ABSPATH' ) || exit;

/**
 * Class that applies Multi Currency prices on the frontend.
 */
class Frontend_Prices {
	/**
	 * Multi-Currency instance.
	 *
	 * @var Multi_Currency
	 */
	protected $multi_currency;

	/**
	 * Constructor.
	 *
	 * @param Multi_Currency $multi_currency The Multi_Currency instance.
	 */
	public function __construct( Multi_Currency $multi_currency ) {
		$this->multi_currency = $multi_currency;

		$frontend_request = ! is_admin() && ! defined( 'DOING_CRON' ) && ! WC()->is_rest_api_request();

		if ( $frontend_request ) {
			// Simple product price hooks.
			add_filter( 'woocommerce_product_get_price', [ $this, 'get_product_price' ], 50 );
			add_filter( 'woocommerce_product_get_regular_price', [ $this, 'get_product_price' ], 50 );
			add_filter( 'woocommerce_product_get_sale_price', [ $this, 'get_product_price' ], 50 );

			// Variation price hooks.
			add_filter( 'woocommerce_product_variation_get_price', [ $this, 'get_product_price' ], 50 );
			add_filter( 'woocommerce_product_variation_get_regular_price', [ $this, 'get_product_price' ], 50 );
			add_filter( 'woocommerce_product_variation_get_sale_price', [ $this, 'get_product_price' ], 50 );

			// Variation price range hooks.
			add_filter( 'woocommerce_variation_prices', [ $this, 'get_variation_price_range' ], 50 );
			add_filter( 'woocommerce_get_variation_prices_hash', [ $this, 'add_exchange_rate_to_variation_prices_hash' ], 50 );

			// Shipping methods hooks.
			add_filter( 'woocommerce_package_rates', [ $this, 'convert_package_rates_prices' ], 50 );
			add_action( 'init', [ $this, 'register_free_shipping_filters' ], 50 );

			// Coupon hooks.
			add_filter( 'woocommerce_coupon_get_amount', [ $this, 'get_coupon_amount' ], 50, 2 );
			add_filter( 'woocommerce_coupon_get_minimum_amount', [ $this, 'get_coupon_min_max_amount' ], 50 );
			add_filter( 'woocommerce_coupon_get_maximum_amount', [ $this, 'get_coupon_min_max_amount' ], 50 );
		}
	}

	/**
	 * Returns the price for a product.
	 *
	 * @param mixed $price The product's price.
	 *
	 * @return mixed The converted product's price.
	 */
	public function get_product_price( $price ) {
		if ( ! $price ) {
			return $price;
		}

		return $this->multi_currency->get_price( $price, 'product' );
	}

	/**
	 * Returns the price range for a variation.
	 *
	 * @param array $variation_prices The variation's prices.
	 *
	 * @return array The converted variation's prices.
	 */
	public function get_variation_price_range( $variation_prices ) {
		foreach ( $variation_prices as $price_type => $prices ) {
			foreach ( $prices as $variation_id => $price ) {
				$variation_prices[ $price_type ][ $variation_id ] = $this->get_product_price( $price );
			}
		}

		return $variation_prices;
	}

	/**
	 * Add the exchange rate into account for the variation prices hash.
	 * This is used to recalculate the variation price range when the exchange
	 * rate changes, otherwise the old prices will be cached.
	 *
	 * @param array $prices_hash The variation prices hash.
	 *
	 * @return array The variation prices hash with the current exchange rate.
	 */
	public function add_exchange_rate_to_variation_prices_hash( $prices_hash ) {
		$prices_hash[] = $this->get_product_price( 1 );
		return $prices_hash;
	}

	/**
	 * Returns the shipping rates with their prices converted.
	 *
	 * @param array $rates Shipping rates.
	 *
	 * @return array Shipping rates with converted costs.
	 */
	public function convert_package_rates_prices( $rates ) {
		foreach ( $rates as $rate ) {
			if ( $rate->cost ) {
				$rate->cost = $this->multi_currency->get_price( $rate->cost, 'shipping' );
			}
			if ( $rate->taxes ) {
				$rate->taxes = array_map(
					function ( $tax ) {
						return $this->multi_currency->get_price( $tax, 'tax' );
					},
					$rate->taxes
				);
			}
		}
		return $rates;
	}

	/**
	 * Returns the amount for a coupon.
	 *
	 * @param mixed  $amount The coupon's amount.
	 * @param object $coupon The coupon object.
	 *
	 * @return mixed The converted coupon's amount.
	 */
	public function get_coupon_amount( $amount, $coupon ) {
		$percent_coupon_types = [ 'percent' ];

		if ( ! $amount || $coupon->is_type( $percent_coupon_types ) ) {
			return $amount;
		}

		return $this->multi_currency->get_price( $amount, 'coupon' );
	}

	/**
	 * Returns the min or max amount for a coupon.
	 *
	 * @param mixed $amount The coupon's min or max amount.
	 *
	 * @return mixed The converted coupon's min or max amount.
	 */
	public function get_coupon_min_max_amount( $amount ) {
		if ( ! $amount ) {
			return $amount;
		}

		// Coupon mix/max prices are treated as products to avoid inconsistencies with charm pricing
		// making a coupon invalid when the coupon min/max amount is the same as the product's price.
		return $this->multi_currency->get_price( $amount, 'product' );
	}

	/**
	 * Returns the free shipping zone settings with converted min_amount.
	 *
	 * @param array $data The shipping zone settings.
	 *
	 * @return array The shipping zone settings with converted min_amount.
	 */
	public function get_free_shipping_min_amount( $data ) {
		if ( ! isset( $data['min_amount'] ) || ! $data['min_amount'] ) {
			return $data;
		}

		// Free shipping min amount is treated as products to avoid inconsistencies with charm pricing
		// making a method invalid when its min amount is the same as the product's price.
		$data['min_amount'] = $this->multi_currency->get_price( $data['min_amount'], 'product' );
		return $data;
	}

	/**
	 * Register the hooks to set the min amount for free shipping methods.
	 */
	public function register_free_shipping_filters() {
		$shipping_zones = \WC_Shipping_Zones::get_zones();

		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		if ( $default_zone ) {
			$shipping_zones[] = [ 'shipping_methods' => $default_zone->get_shipping_methods() ];
		}

		foreach ( $shipping_zones as $shipping_zone ) {
			foreach ( $shipping_zone['shipping_methods'] as $shipping_method ) {
				if ( 'free_shipping' === $shipping_method->id ) {
					$option_name = 'option_woocommerce_' . trim( $shipping_method->id ) . '_' . intval( $shipping_method->instance_id ) . '_settings';
					add_filter( $option_name, [ $this, 'get_free_shipping_min_amount' ], 50 );
				}
			}
		}
	}
}
