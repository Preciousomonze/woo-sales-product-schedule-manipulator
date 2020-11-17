
<?php
defined( 'ABSPATH' ) || exit;

/**
 * WC_SPTM_Meta_Box_Product_Data class.
 */
class WC_SPTM_Meta_Box_Product_Data {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		// Product Meta boxes.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_to_metabox' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_meta' ) );

		// Variable Product.
		add_action( 'woocommerce_variation_options', array( __CLASS__, 'product_variations_options' ), 10, 3 );

		// Save variations.
		add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'save_product_variation' ), 30, 2 );

	}



	/**
	 * Metabox display callback.
	 *
	 * @since 1.0.0
	 */
	public static function add_to_metabox() {
        woocommerce_wp_select( 
            array( 
                'id'          => '_woo_sptm_after_sales_action', 
                'label'       => __( 'What happens after sales ends?', 'woo-sptm' ), 
                'desc_tip'    => 'true',
                'description' => __('Choose what should happen to the product after sales ends.',  'woo-sptm' ),
                'options'     => array(
                    ''       => __( '', 'woo-sptm' ),
                    'delete' => __( 'Delete Product',  'woo-sptm' ),
                    'draft'  => __( 'Draft Product',  'woo-sptm' ),
                )
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

		if ( isset( $_POST['_woo_sptm_after_sales_action'] ) ) {
			$product->update_meta_data( '_woo_sptm_after_sales_action', esc_attr( $_POST['_woo_sptm_after_sales_action'] ) );
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
                'id'          => '_woo_sptm_after_sales_action[' . $loop . ']', 
                'label'       => __( 'What happens after sales ends?', 'woo-sptm' ), 
                'desc_tip'    => 'true',
                'description' => __('Choose what should happen to the product after sales ends.',  'woo-sptm' ),
                'options'     => array(
                    ''       => __( '', 'woo-sptm' ),
                    'delete' => __( 'Delete Product',  'woo-sptm' ),
                    'draft'  => __( 'Draft Product',  'woo-sptm' ),
                )
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
        
        $variation_after_sales_action = isset( $_POST['_woo_sptm_after_sales_action'][ $i ] ) ? $_post['_woo_sptm_after_sales_action'] : false ;
        if ( $variation_after_sales_action === false ) {
            return;
        }

		$is_legacy = false;

		// Need to instantiate the product object on WC<3.8.
		if ( is_numeric( $variation ) ) {
			$variation = wc_get_product( $variation );
			$is_legacy = true;
		}
        
        $variation->update_meta_data( '_woo_sptm_after_sales_action', $variation_after_sales_action );

		// Save the meta on WC<3.8.
		if ( $is_legacy ) {
			$variation->save();
		}

	}



}
WC_SPTM_Meta_Box_Product_Data::init();