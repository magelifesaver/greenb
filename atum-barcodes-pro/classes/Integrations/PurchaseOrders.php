<?php
/**
 * Handle the Purchase Orders PRO integration for Barcodes PRO.
 *
 * @package     AtumBarcodes
 * @subpackage  Integrations
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.1.7
 */

namespace AtumBarcodes\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumHelpGuide;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders as AtumPurchaseOrders;
use AtumBarcodes\Entities\Orders;
use AtumBarcodes\Inc\Helpers;
use AtumPO\Models\POExtended;

class PurchaseOrders {

	/**
	 * The singleton instance holder
	 *
	 * @var PurchaseOrders
	 */
	private static $instance;

	/**
	 * PurchaseOrders singleton constructor
	 *
	 * @since 0.1.7
	 */
	private function __construct() {

		if ( is_admin() ) {

			// Add the barcode to the PO PRO header.
			add_action( 'atum/purchase_orders_pro/after_po_number', array( $this, 'add_po_pro_barcode' ) );

			// Enqueue the barcode edit popover script.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Ajax callback to save the PO barcode.
			add_action( 'wp_ajax_atum_bp_edit_po_barcode', array( $this, 'edit_po_barcode' ) );

			// Maybe customize the barcode shown on PO PRO PDF template.
			add_filter( 'atum/barcodes_pro/document_header_barcode_options', array( $this, 'po_pdf_header_barcode_options' ), 10, 2 );

			// Do not add the barcodes' meta-box to the PO PRO.
			add_filter( 'atum/barcodes_pro/show_meta_box', array( $this, 'show_po_pro_meta_box' ), 10, 2 );

            // Add customizations for the Barcodes PRO help guides for PO PRO.
            add_filter( 'atum/help_guides/guide_steps', array( $this, 'add_po_pro_help_guide_steps' ), 10, 2 );

			// Add the barcode tracking filter to the PO PRO list table.
			add_filter( 'atum/purchase_orders_pro/extra_filters', array( $this, 'add_barcode_tracking_filter' ) );
			add_filter( 'atum/purchase_orders_pro/extra_filters/no_auto_filter', array( $this, 'barcode_tracking_no_auto_filter' ) );
			add_filter( 'atum/purchase_orders_pro/list_table/extra_filter', array( $this, 'pos_list_barcode_tracking' ), 10, 2 );
			add_action( 'atum/purchase_orders_pro/list_table/after_nav_filters', array( $this, 'add_auto_filters' ) );

		}

	}

	/**
	 * Add the ATUM barcode to the single PO PRO UI
	 *
	 * @since 0.1.7
	 *
	 * @param POExtended $po
	 */
	public function add_po_pro_barcode( $po ) {

		$barcode = $po->atum_barcode;

		if ( ! $barcode && 'yes' === AtumHelpers::get_option( 'bp_orders_barcodes', 'yes' ) ) {
			$barcode = $po->get_id();
		}

		$barcode_img = Helpers::generate_barcode( $barcode );

		if ( $barcode_img && ! is_wp_error( $barcode_img ) ) : ?>
			<div class="atum-barcodes">
				<?php echo $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="atum-tooltip" title="<?php esc_attr_e( 'Edit PO barcode', ATUM_BARCODES_TEXT_DOMAIN ); ?>">
					<span class="po-barcode-edit set-meta" data-meta="barcode" data-input-type="text"
						data-content-id="edit-po-barcode"
						title="<?php esc_attr_e( 'Edit PO Barcode', ATUM_BARCODES_TEXT_DOMAIN ) ?>"
					>
						<i class="atum-icon atmi-pencil"></i>
					</span>

					<input type="hidden" name="barcode" value="<?php echo esc_attr( $barcode ) ?>">

					<script type="text/template" id="edit-po-barcode">
						<input type="text" class="meta-value" value="<?php echo esc_attr( $barcode ) ?>">
					</script>
				</span>
			</div>
		<?php endif;

	}

	/**
	 * Enqueue scripts for PO PRO
	 *
	 * @since 0.1.9
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		global $post;

		if ( $post instanceof \WP_Post && AtumPurchaseOrders::POST_TYPE === $post->post_type ) {
			wp_register_script( 'barcode-edit-popover', ATUM_BARCODES_URL . 'assets/js/build/atum-barcodes-edit-popover.js', [ 'atum-barcodes-metabox' ], ATUM_BARCODES_VERSION, TRUE );
			wp_localize_script( 'barcode-edit-popover', 'atumBPVars', array(
				'nonce'           => wp_create_nonce( 'barcodes-metabox-nonce' ),
				'none'            => __( 'None', ATUM_BARCODES_TEXT_DOMAIN ),
				'setButton'       => __( 'Set', ATUM_BARCODES_TEXT_DOMAIN ),
				'unexpectedError' => __( 'Unexpected error. The Barcode could not be saved.', ATUM_BARCODES_TEXT_DOMAIN ),
			) );
			wp_enqueue_script( 'barcode-edit-popover' );
		}

	}

	/**
	 * Ajax callback to save the PO barcode
	 *
	 * @since 0.1.9
	 */
	public function edit_po_barcode() {

		check_ajax_referer( 'barcodes-metabox-nonce', 'security' );

		if ( empty( $_POST['po'] ) || empty( $_POST['barcode'] ) ) {
			wp_send_json_error( __( 'Missing data', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		$po = new POExtended( absint( $_POST['po'] ), FALSE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( 'The PO does not exist', ATUM_BARCODES_TEXT_DOMAIN ) );
		}

		$barcode = sanitize_text_field( $_POST['barcode'] );

        $barcode_found = Orders::get_order_id_by_barcode( $po->get_id(), $barcode, AtumPurchaseOrders::POST_TYPE );

        if ( $barcode_found ) {
            wp_send_json_error( __( 'The barcode is already in use by another PO', ATUM_BARCODES_TEXT_DOMAIN ) );
        }

		$po->set_atum_barcode( sanitize_text_field( $_POST['barcode'] ) );
		$po->save_meta();

		$barcode_img = '';

		if ( $barcode ) {
			$barcode_img = Helpers::generate_barcode( $barcode );

			if ( is_wp_error( $barcode_img ) ) {
				$barcode_img = '';
			}
		}

		wp_send_json_success( array(
			'msg'        => __( 'Barcode saved successfully', ATUM_BARCODES_TEXT_DOMAIN ),
			'barcodeImg' => $barcode_img,
		) );

	}

	/**
	 * Maybe customize the barcode shown on PO PRO PDF template.
	 *
	 * @since 0.2.5
	 *
	 * @param array                $options
	 * @param \WC_Order|POExtended $order
	 *
	 * @return array
	 */
	public function po_pdf_header_barcode_options( $options, $order ) {

		// Change the color for the template 1.
		if ( $order instanceof POExtended && 'template1' === $order->pdf_template ) {
			$options['color'] = '#FFFFFF';
		}

		return $options;

	}

	/**
	 * Disable the ATUM Barcodes meta box on the PO PRO.
	 *
	 * @since 1.0.0
	 *
	 * @param bool     $show
	 * @param \WP_Post $post
	 *
	 * @return bool
	 */
	public function show_po_pro_meta_box( $show, $post ) {

		if ( $post instanceof \WP_Post && AtumPurchaseOrders::POST_TYPE === $post->post_type ) {
			return FALSE;
		}

		return $show;

	}

    /**
     * Add customizations for the Barcodes PRO help guides for PO PRO.
     *
     * @since 1.0.0
     *
     * @param array  $guide_steps
     * @param string $guide
     *
     * @return array
     */
    public function add_po_pro_help_guide_steps( $guide_steps, $guide ) {

        // Avoid entering here again.
        remove_action( 'atum/help_guides/guide_steps', array( $this, 'add_mi_product_help_guide_steps' ) );


        if ( str_contains( $guide, 'atum-barcodes-pro/help-guides/atum-orders.json' ) ) {
            $atum_help_guide = AtumHelpGuide::get_instance();
            $guide_steps     = $atum_help_guide->get_guide_steps( ATUM_BARCODES_PATH . 'help-guides/po-pro.json' );
        }
        elseif ( str_contains( $guide, 'atum-purchase-orders/help-guides/first-po-access.json' ) ) {
            $atum_help_guide = AtumHelpGuide::get_instance();
            $guide_steps     = array_merge( $guide_steps, $atum_help_guide->get_guide_steps( ATUM_BARCODES_PATH . 'help-guides/po-pro.json' ) );
        }

        return $guide_steps;

    }

	/**
	 * Add the barcode tracking extra filter to the POs list table
	 *
	 * @since 1.0.7
	 *
	 * @param array $extra_filters
	 *
	 * @return array
	 */
	public function add_barcode_tracking_filter( $extra_filters ) {

		$extra_filters['barcode_tracking'] = __( 'Barcode Tracking', ATUM_PO_TEXT_DOMAIN );

		return $extra_filters;

	}

	/**
	 * Do not enable auto-filtering on the Barcode Tracking extra filter
	 *
	 * @since 1.0.7
	 *
	 * @param array $no_auto_filters
	 *
	 * @return array
	 */
	public function barcode_tracking_no_auto_filter( $no_auto_filters ) {

		$no_auto_filters[] = 'barcode_tracking';

		return $no_auto_filters;

	}

	/**
	 * Handle the Barcode tracking extra filter.
	 *
	 * @since 1.0.7
	 *
	 * @param array  $filtered_pos
	 * @param string $extra_filter
	 *
	 * @return array
	 */
	public function pos_list_barcode_tracking( $filtered_pos, $extra_filter ) {

		if ( 'barcode_tracking' === $extra_filter && ! empty( $_REQUEST['barcode'] ) ) {

			$barcode = wc_clean( $_REQUEST['barcode'] );

			if ( $barcode ) {
				$barcode_orders = Orders::get_instance();
				$filtered_pos   = $barcode_orders->search_orders_by_product_barcode( $barcode, AtumPurchaseOrders::POST_TYPE );
			}

		}

		return $filtered_pos;

	}

	/**
	 * Add the barcode tracking auto-filter to the POs list table
	 *
	 * @since 1.0.7
	 */
	public function add_auto_filters() {
		$barcode = ! empty( $_REQUEST['barcode'] ) ? esc_attr( $_REQUEST['barcode'] ) : '';
		echo '<input type="hidden" class="auto-filter" name="barcode" value="' . $barcode . '">';
	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return PurchaseOrders instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
