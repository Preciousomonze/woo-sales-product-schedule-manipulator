<?php
defined( 'ABSPATH' ) || exit;

/**
 * WC_SPSM_Scheduler class.
 *
 * To run our action scheduler stuff.
 */
class WC_SPSM_Scheduler {

	/**
	 * Sales Schedule hook.
	 *
	 * @var string
	 * @since 1.0.9
	 */
	private static $sales_schedule_hook = 'woo_spsm_schedule_sales_update';

	/**
	 * Sales Schedule group.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $sales_schedule_group = 'woo-spsm-sales-group';

	/**
	 * Default date format.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $default_date_format = 'Y-m-d H:i:sP';

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Tends to create the schedule twice, an extra 23:59 ish, no better hook yet :)
		// add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'stash_product_sales_schedule' ), 99, 1 );

		// Works well for simple and variable.
		add_action( 'woocommerce_process_product_meta_simple', array( __CLASS__, 'stash_product_sales_schedule' ), 99, 1 );
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'stash_product_sales_schedule' ), 99, 1 );
		add_action( self::$sales_schedule_hook, array( __CLASS__, 'process_batch' ), 10, 2 );
	}

	/**
	 * Schedule the update that needs to be batched.
	 *
	 * @param WC_Product|int $product
	 * @since 1.0.0
	 */
	public static function stash_product_sales_schedule( $product ) {

		$product = is_int( $product ) ? wc_get_product( $product ) : $product;

		// Schedule the update with the time the sales ends.
		$scheduled_period = self::get_schedulable_period( $product );

		$our_action = $product->get_meta( '_woo_spsm_after_sales_action', true );

		$schedule_args = array(
			'product' => $product->get_id(),
			'action'  => $our_action,
		);

		$next_scheduled_date   = WC()->queue()->get_next( self::$sales_schedule_hook, $schedule_args, self::$sales_schedule_group );
		$next_scheduled_period = $next_scheduled_date ? self::get_time_period( $next_scheduled_date->date( self::$default_date_format ) ) : 0;

		// Has this been scheduled before, with same args? Then no need.
		if ( $next_scheduled_period === $scheduled_period ) {
			return;
		}

		if ( ! $scheduled_period ) {
			return;
		}

		// Schedule the task!
		WC()->queue()->schedule_single(
			$scheduled_period,
			self::$sales_schedule_hook,
			$schedule_args,
			self::$sales_schedule_group
		);

	}

	/**
	 * Batch process all product posts
	 *
	 * @param mixed  $product Mostly ID.
	 * @param string $action  The action to take that was set.
	 * @since 1.0.0
	 */
	public static function process_batch( $product, $action ) {

		$product = is_int( $product ) ? wc_get_product( $product ) : $product;
		// If action is empty, do nothing abeg.
		if ( ! $product instanceof WC_Product || empty( $action ) ) {
			return;
		}

		switch( $action ) {
			case 'delete':
				$product->delete();
			break;
			default:
				$product->set_status( $action );
		}

		if ( 'delete' !== $action ) {
			// Send a note.
			$product->update_meta_data( '_woo_spsm_schedule_done', 'yes' );
			$product->save();
		}

	}

	/**
	 * Get scheduled period if the product be sheduled?
	 *
	 * @param WC_Product $product
	 * @return int|bool The timestamp, or false if not eligible.
	 */
	public static function get_schedulable_period( $product ) {
		
		$our_action = $product->get_meta( '_woo_spsm_after_sales_action', true );
		
		// Incase there's no sales period.
		$default_end_date = '1970-01-01 00:00:00';

		// Schedule the update with the time the sales ends.
		$_end_date  = $product->get_date_on_sale_to( 'edit' ) ? $product->get_date_on_sale_to( 'edit' ) : $default_end_date;
		$end_period = self::get_time_period( $_end_date, null );

		$present_site_time = self::get_time_period( 'now', null );

		// Is end date in the past?
		if ( $present_site_time > $end_period || '' === trim( $our_action ) ) {
			return false;
		}

		return $end_period;

	}

	/** 
	 * Gets the time period you put in.
	 *
	 * Converts to the format you want. if format is set to null,
	 * it converts to time().
	 * Note: this uses the date and time format for WC plugin.
	 * Might not be needed, I tried to handle it in the function :). 
	 *
	 * @param string $date_string Proper support date string format.
	 * @param mixed $format (optional) If set to null, returns timestamp.
	 */
	public static function get_time_period( $date_string, $format = null ) {

		// phpcs:ignore
		// $wc_time_zone = new DateTimeZone( wc_timezone_string() );

		$date = new WC_DateTime( $date_string );

		/* 
		Set timezone to the site, for some reason, passing timezone to the construct with a preset
		date_string doesn't seem to change the timezone properly :).
		Ignore, not needed.
		*/
		// phpcs:ignore
		// $date->setTimezone( $wc_time_zone );

		// If format ain't set, convert to raw time();
		if ( ! $format ) {
			$result = (int) strtotime( $date->date( self::$default_date_format ) ); 
		}
		else {
			$result = $date->date( $format );
		}
		return $result;

	}

}

WC_SPSM_Scheduler::init();
