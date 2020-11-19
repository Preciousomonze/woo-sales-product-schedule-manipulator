<?php
defined( 'ABSPATH' ) || exit;

/**
 * WC_SPTM_Scheduler class.
 *
 * To run our action scheduler stuff.
 */
class WC_SPTM_Scheduler {

	/**
	 * Sales Schedule hook.
	 *
	 * @var string
	 * @since 1.0.9
	 */
	private static $sales_schedule_hook = 'woo_sptm_run_sales_update';

	/**
	 * Sales Schedule group.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $sales_schedule_group = 'woo-sptm-sales-group';

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'stash_product_sales_schedule' ), 10, 2 );
		add_action( self::$sales_schedule_hook, array( __CLASS__, 'process_batch' ), 10, 2 );
	}

	/**
	 * Schedule the update that needs to be batched.
	 *
	 * @param WC_Product $product
	 * @param Object     $data_store
	 * @since 1.0.0
	 */
	public static function stash_product_sales_schedule( $product, $data_store ) {

		$next_scheduled_date = WC()->queue()->get_next( self::$sales_schedule_hook, null, self::$sales_schedule_group );
		
		// Schedule the update with the time the sales ends.
		$end_date   = $product->get_meta( '_sale_price_dates_to', true );
		$end_time   = $product->get_meta( '_sale_price_times_to', true ); // Lool, end times :).
		$our_action = $product->get_meta( '_woo_sptm_after_sales_action', true );

		WC()->queue()->schedule_single(
			self::get_time_period( $end_date, $end_date ),
			self::$sales_schedule_hook,
			array(
				'product' => $product,
				'action'  => $our_action,
			),
			self::$sales_schedule_group
		);

	}

	/**
	 * Batch process all product posts
	 *
	 * @param WC_Product $product
	 * @param string     $action  The action to take that was set.
	 * @since 1.0.0
	 */
	public static function process_batch( $product, $action ) {

		// If action is empty, do nothing abeg.
		if ( empty( $action) ) {
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
			$product->update_meta_data( '_woo_sptm_schedule_done', 'yes' );
			$product->save();
		}

	}

	/** 
	 * Gets the time period you put in.
	 *
	 * Converts to the format you want. if format is set to null,
	 * it converts to time().
	 * Note: this uses the date and time format for WC and Sales countdown time plugin.
	 * Might not be needed, I tried to handle it in the function :). 
	 *
	 * @param mixed $date
	 * @param mixed $time
	 * @param mixed $format (optional) if set to null, returns time().
	 */
	public static function get_time_period( $date, $time, $format = null ) {
		$date   = ( is_int( $date ) ? date( 'm/d/Y', $date ) : $date );
		$time   = ( is_int( $time ) ? date( 'H:i:s', $time ) : $time );
		$period = $date . ' ' . $time;

		// If format ain't set, convert to raw time();
		if ( ! $format ) {
			$result = strtotime( $period ); 
		}
		else {
			$result = date( $format, strtotime( $period ) );
		}
		return $result;
	}

}

WC_SPTM_Scheduler::init();
