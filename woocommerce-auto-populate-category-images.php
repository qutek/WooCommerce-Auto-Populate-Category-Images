<?php
/**
 * @link           	http://astahdziq.in/
 * @since          	1.0
 * @package        	WooCommerce Auto Populate Category Images
 *
 * @wordpress-plugin
 * Plugin Name: 	WooCommerce Auto Populate Category Images
 * Description: 	Auto populate woocommerce category image from product images
 * Version:         1.0
 * Author:          Lafif Astahdziq
 * Author URI:     	http://astahdziq.in/
 * License:        	GPL-2.0+
 * License URI:    	http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:    	wapci
 * Domain Path:    	/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WooCommerce_Auto_Populate_Category_Images' ) ) :

/**
 * Main WooCommerce_Auto_Populate_Category_Images Class
 *
 * @class WooCommerce_Auto_Populate_Category_Images
 * @version	1.0
 */
final class WooCommerce_Auto_Populate_Category_Images {

	/**
	 * @var string
	 */
	public $version = '1.0';

	public $capability = 'manage_options'; // admin

	/**
	 * @var WooCommerce_Auto_Populate_Category_Images The single instance of the class
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * Main WooCommerce_Auto_Populate_Category_Images Instance
	 *
	 * Ensures only one instance of WooCommerce_Auto_Populate_Category_Images is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return WooCommerce_Auto_Populate_Category_Images - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WooCommerce_Auto_Populate_Category_Images Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'wapci_loaded' );
	}

	/**
	 * Hook into actions and filters
	 * @since  1.0
	 */
	private function init_hooks() {
		add_action( 'init', array($this, 'register_scripts') );
	}

	public function register_scripts(){
		wp_register_style( 'wapci-admin', plugins_url( '/assets/css/wapci-admin.css', __FILE__ ) );
		wp_register_script( 'wapci-admin', plugins_url( '/assets/js/wapci-admin.js', __FILE__ ), array('jquery'), '', true );
	}


	/**
	 * Define WooCommerce_Auto_Populate_Category_Images Constants
	 */
	private function define_constants() {

		$this->define( 'WAPCI_PLUGIN_FILE', __FILE__ );
		$this->define( 'WAPCI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WAPCI_VERSION', $this->version );
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 * string $type ajax, frontend or admin
	 * @return bool
	 */
	public function is_request( $type ) {
		switch ( $type ) {
			case 'admin' :
				return is_admin();
			case 'ajax' :
				return defined( 'DOING_AJAX' );
			case 'cron' :
				return defined( 'DOING_CRON' );
			case 'frontend' :
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {

		if ( $this->is_request( 'admin' ) ) {
			include_once( 'includes/class-wapci_admin.php' );
		}

		if ( $this->is_request( 'ajax' ) ) {
			// include_once( 'includes/ajax/..*.php' );
		}

		if ( $this->is_request( 'frontend' ) ) {

		}
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

}

endif;

/**
 * Notice if woocommerce not activated
 * @return [type] [description]
 */
function wapci_need_deps(){
	?>
    <div class="updated">
        <p><?php _e('Please activate Woocommerce plugins', 'wapci'); ?></p>
    </div>
    <?php
}

/**
 * Returns the main instance of WooCommerce_Auto_Populate_Category_Images to prevent the need to use globals.
 *
 * @since  1.0
 * @return WooCommerce_Auto_Populate_Category_Images
 */
function WooCommerce_Auto_Populate_Category_Images() {

	$needed = array('woocommerce/woocommerce.php');
	$activated = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	
	$pass = count(array_intersect($needed, $activated)) == count($needed);

	if ( $pass ) {
		return WooCommerce_Auto_Populate_Category_Images::instance();
	} else {
		add_action( 'admin_notices', 'wapci_need_deps' );
	}
}

// Global for backwards compatibility.
$GLOBALS['wapci'] = WooCommerce_Auto_Populate_Category_Images();