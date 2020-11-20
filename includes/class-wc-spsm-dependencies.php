<?php
 defined( 'ABSPATH' ) || exit;

class WC_SPSM_Dependencies {
	private static $active_plugins;

	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * Check if a plugin exists or is active.
	 *
	 * @return Boolean
	 */
	public static function plugin_active_check( $boot_file ) {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return ( in_array( $boot_file, self::$active_plugins, true ) || array_key_exists( $boot_file, self::$active_plugins ) );
	}

	/**
	 * Check if our dependencies are active.
	 *
	 * @return Boolean
	 */
	public static function is_dep_active() {
		return self::plugin_active_check( 'woocommerce/woocommerce.php' );
	}
}
