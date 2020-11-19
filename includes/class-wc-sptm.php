<?php
defined( 'ABSPATH' ) || exit;

/**
 * Main Class to load stuff.
 */
final class WC_SPTM {

    /**
     * The single instance of the class.
     *
     * @var WC_SPTM
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main instance
     * @return class object
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        if ( WC_SPTM_Dependencies::is_dep_active() ) {
            self::init();
        } else {
            add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ), 15 );
        }
    }
    /**
     * Initialiseeee
     */
    public static function init() {
        self::define_constants(); //Define the constants.
        self::includes(); // Include relevant files.

        /**
         * Init.
         */
        do_action( 'woo_sptm_init' );
    }

    /**
     * Constants define
     */
    private function define_constants() {
        self::define( 'WOO_SPTM_ABSPATH', dirname( WOO_SPTM_PLUGIN_FILE ) . '/' );
        self::define( 'WOO_SPTM_PLUGIN_FILE', plugin_basename( WOO_SPTM_PLUGIN_FILE ) );
        self::define( 'WOO_SPTM_PLUGIN_VERSION', '1.0.0' );
    }

    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    private static function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Check request
     * @param string $type
     * @return bool
     */
    private static function is_request( $type ) {
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
     * load plugin files
     */
    public static function includes() {
        // Include for all.
		include_once WOO_SPTM_ABSPATH . 'includes/class-wc-sptm-scheduler.php';

        // Admin side.
        if ( self::is_request( 'admin' ) ) {
            include_once WOO_SPTM_ABSPATH . 'includes/admin/meta-boxes/class-wc-sptm-meta-box-product-data.php';
        }
    }
	
    /**
     * Display admin notice
     */
    public static function admin_notices() {
        echo '<div class="error"><p>';
        _e('<strong>Woo Sales Product Timer Manipulation</strong> plugin requires <strong>WooCommerce</strong> and <strong>Sales Countdown Timer</strong> plugins to be active!', 'woo-sptm' );
        echo '</p></div>';
    }

    /**
     * Load Localisation files.
     *
     * @since  1.0.0
     */
    public static function load_plugin_textdomain() {
        load_plugin_textdomain( 'woo-sptm', false, plugin_basename( dirname( WOO_SPTM_PLUGIN_FILE ) ) . '/languages' );
    }

}
