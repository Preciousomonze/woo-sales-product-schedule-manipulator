<?php
/**
 * Plugin Name: Woo Sales Product Schedule Manipulation
 * Plugin URI: https://github.com/Preciousomonze/woo-sales-product-schedule-manipulation
 * Description: This helps you modify the status of a product after the countdown or sales period has ended. Requires <strong>WooCommerce</strong>.
 * Author: Precious Omonzejele (CodeXplorer 🤾🏽‍♂️🥞🦜🤡)
 * Author URI: https://codexplorer.ninja
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 5.5
 * WC requires at least: 4.0
 * WC tested up to: 4.7
 *
 * Text Domain: woo-sptm
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// Make sure you update the version values when necessary.
define( 'WOO_SPTM_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WOO_SPTM_PLUGIN_FILE', __FILE__ );

// Include dependencies file
if ( ! class_exists( 'WC_SPTM_Dependencies' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-wc-sptm-dependencies.php';
}

// Include the main class.
if ( ! class_exists( 'WC_SPTM' ) ) {
    include_once dirname(__FILE__) . '/includes/class-wc-sptm.php';
}

/**
 * Return instance of the func.
 * 
 * @return Instanace 
 */
function woo_sptm() {
    return WC_SPTM::instance();
}

add_action( 'plugins_loaded', 'woo_sptm' );

$GLOBALS['woo_sptm'] = woo_sptm();
