<?php
/**
 * WooCommerce Payments Multi-currency Settings
 *
 * @package WooCommerce\Admin
 */

namespace WCPay\MultiCurrency;

defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 */
class Settings extends \WC_Settings_Page {

	/**
	 * The id of the plugin.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The tab label.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * Instance of MultiCurrency class.
	 *
	 * @var MultiCurrency
	 */
	protected $multi_currency;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrency $multi_currency The MultiCurrency instance.
	 */
	public function __construct( MultiCurrency $multi_currency ) {
		$this->multi_currency = $multi_currency;
		$this->id             = $this->multi_currency->id;
		$this->label          = _x( 'Multi-currency', 'Settings tab label', 'woocommerce-payments' );

		// TODO: We need to re-enable it on every screen we use emoji flags. Until WC Admin decide if they will enable it too: https://github.com/woocommerce/woocommerce-admin/issues/6388.
		add_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		add_action( 'woocommerce_admin_field_wcpay_multi_currency_settings_page', [ $this, 'wcpay_multi_currency_settings_page' ] );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section Section being shown.
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		return [
			[
				'type' => 'wcpay_multi_currency_settings_page',
			],
		];
	}

	/**
	 * Output container for enabled currencies list.
	 */
	public function wcpay_multi_currency_settings_page() {
		// Hide original save button.
		$GLOBALS['hide_save_button'] = true;
		?>
			<script>
				document.addEventListener('DOMContentLoaded', () => {
					window.wcSettings.dateFormat = "<?php echo esc_attr( get_option( 'date_format', 'F j, Y' ) ); ?>";
					window.wcSettings.timeFormat = "<?php echo esc_attr( get_option( 'time_format', 'g:i a' ) ); ?>";
				});
			</script>
			<div id="wcpay_multi_currency_settings_container" aria-describedby="wcpay_multi_currency_settings_container-description"></div>
		<?php
	}
}
