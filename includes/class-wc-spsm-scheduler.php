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
	private static $sales_schedule_hook = 'woo_spsm_run_sales_update';

	/**
	 * Sales Schedule group.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $sales_schedule_group = 'woo-spsm-sales-group';

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		add_action( 'woocommerce_after_product_object_save', array( __CLASS__, 'stash_product_sales_schedule' ), 10, 1 );
		add_action( self::$sales_schedule_hook, array( __CLASS__, 'process_batch' ), 10, 2 );
	}

	/**
	 * Schedule the update that needs to be batched.
	 *
	 * @param WC_Product $product
	 * @since 1.0.0
	 */
	public static function stash_product_sales_schedule( $product ) {

		$next_scheduled_date = WC()->queue()->get_next( self::$sales_schedule_hook, null, self::$sales_schedule_group );
		
		// Schedule the update with the time the sales ends.
		$scheduled_period = self::get_schedulable_period( $product );
		echo "<pre>";
		var_dump( $scheduled_period );
		echo "</pre>";
		
		//exit;
		if ( ! $scheduled_period ) {
			return;
		}

		$our_action = $product->get_meta( '_woo_spsm_after_sales_action', true );

		WC()->queue()->schedule_single(
			$scheduled_period,
			self::$sales_schedule_hook,
			array(
				'product' => $product->get_id(),
				'action'  => $our_action,
			),
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
		// Schedule the update with the time the sales ends.
		$end_date   = $product->get_meta( '_sale_price_dates_to', true );
		$end_time   = $product->get_meta( '_sale_price_times_to', true ); // Lool, end times :).
		$our_action = $product->get_meta( '_woo_spsm_after_sales_action', true );
		$end_period = self::get_time_period( $end_date, $end_time, null );
		echo "<pre>";
		var_dump($end_period);
		echo "</pre>";
		echo "<pre>";
		var_dump(time());
		echo "</pre>";
		echo "<pre>";
		var_dump($our_action);
		echo "</pre>";
		echo "<pre>";
		var_dump(date("Y-m-d H:i:s",$end_period));
		echo "</pre>";
		echo "<pre>";
		var_dump(date("Y-m-d H:i:s",time() ));
		echo "</pre>";
		// Is end date in the past?
		if ( time() > $end_period || '' === trim( $our_action ) ) {
			return false;
		}

		return $end_period;
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
			$result = (int) strtotime( $period ); 
		}
		else {
			$result = date( $format, strtotime( $period ) );
		}
		return $result;
	}

}

WC_SPSM_Scheduler::init();
