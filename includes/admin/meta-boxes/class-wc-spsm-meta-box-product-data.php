
<?php
defined( 'ABSPATH' ) || exit;

/**
 * WC_SPSM_Meta_Box_Product_Data class.
 */
class WC_SPSM_Meta_Box_Product_Data {

	/**
	 * Action custom meta.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $action_custom_meta = '_woo_spsm_after_sales_action';

	/**
	 * Option label.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $option_label = '';

	/**
	 * Option values.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private static $option_values = array();

	/**
	 * Option description
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private static $option_description = '';

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		// Set what we need.
		self::set_needed_values();

		// Product Meta boxes.
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'add_to_metabox' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_meta' ) );

		// Variable Product.
		add_action( 'woocommerce_variation_options_pricing', array( __CLASS__, 'product_variations_options' ), 10, 3 );

		// Save variations.
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_product_variation' ), 30, 2 );

	}

	/**
	 * Set needed values.
	 *
	 * @since 1.0.0
	 */
	public static function set_needed_values() {

		self::$option_label       = __( 'What should happen after scheduled sales period ends?', 'woo-spsm' );
		self::$option_description = __('Choose what should happen to the product after sales ends. Only valid for scheduled sales products.', 'woo-spsm' );
		self::$option_values      = array(
			''       => __( '', 'woo-spsm' ),
			'delete' => __( 'Delete Product',  'woo-spsm' ),
			'draft'  => __( 'Draft Product',  'woo-spsm' ),
		);

	}


	/**
	 * Metabox display callback.
	 *
	 * @since 1.0.0
	 */
	public static function add_to_metabox() {
        woocommerce_wp_select( 
            array( 
                'id'          => self::$action_custom_meta, 
                'label'       => self::$option_label, 
                'desc_tip'    => 'true',
                'description' => self::$option_description,
                'options'     => self::$option_values,
				'wrapper_class' => 'show_if_simple',
            )
        );
	}

	/**
	 * Save extra meta info
	 *
	 * @param object $product
	 * @since 1.0.0
	 */
	public static function save_product_meta( $product ) {

		// phpcs:disable WordPress.Security.NonceVerification

		if ( isset( $_POST[ self::$action_custom_meta ] ) ) {
			$product->update_meta_data( self::$action_custom_meta, esc_attr( $_POST[ self::$action_custom_meta ] ) );
        }
	}

	/**
	 * Add to each variation
	 *
	 * @param string  $loop
	 * @param array   $variation_data
	 * @param WP_Post $variation
	 * @since 1.0.0
	 */
	public static function product_variations_options( $loop, $variation_data, $variation ) {
		woocommerce_wp_select( 
            array( 
                'id'          => self::$action_custom_meta . '[' . $loop . ']', 
                'label'       => self::$option_label, 
                'desc_tip'    => 'true',
                'description' => self::$option_description,
				'options'     => self::$option_values,
				'value'       => get_post_meta( $variation->ID, self::$action_custom_meta, true ),
			)
        );
	}


	/**
	 * Save extra meta info for variable products
	 *
	 * @param mixed int|WC_Product_Variation $variation
	 * @param int $i
	 * @since 1.0.0
	 */
	public static function save_product_variation( $variation, $i ) {

        // phpcs:disable WordPress.Security.NonceVerification
        
        $variation_after_sales_action = isset( $_POST[ self::$action_custom_meta ][ $i ] ) ? $_POST[ self::$action_custom_meta ][ $i ] : false ;
        if ( false === $variation_after_sales_action ) {
            return;
        }

		$is_legacy = false;

		// Need to instantiate the product object on WC<3.8.
		if ( is_numeric( $variation ) ) {
			$variation = wc_get_product( $variation );
			$is_legacy = true;
		}
        
        $variation->update_meta_data( self::$action_custom_meta, $variation_after_sales_action );

		// Save the meta on WC<3.8.
		if ( $is_legacy ) {
			$variation->save();
		}

	}

}

WC_SPSM_Meta_Box_Product_Data::init();
