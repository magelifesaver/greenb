<?php
/**
 * The Entry builder class for Logs
 *
 * @package        AtumLogs
 * @subpackage     Models
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.5.1
 */

namespace AtumLogs\Models;

use AtumLogs\Inc\Helpers;


defined( 'ABSPATH' ) || die;

final class LogEntry {

	const ACTION_SC_EDIT                 = 'atum-sc-edit';
	const ACTION_SC_SET_LOC              = 'atum-sc-set-location';
	const ACTION_SC_DEL_LOC              = 'atum-sc-del-location';
	const ACTION_SC_UNCONTROL_STOCK      = 'atum-sc-bulk-uncontrol-stock';
	const ACTION_SC_CONTROL_STOCK        = 'atum-sc-bulk-control-stock';
	const ACTION_SC_UNMANAGE_STOCK       = 'atum-sc-bulk-unmanage-stock';
	const ACTION_SC_MANAGE_STOCK         = 'atum-sc-bulk-manage-stock';
	const ACTION_SC_EXPORT               = 'atum-sc-export';
	const ACTION_PO_CREATE               = 'atum-po-create';
	const ACTION_PO_EDIT_STATUS          = 'atum-po-edit-status';
	const ACTION_PO_EDIT_TOTALS          = 'atum-po-edit-totals';
	const ACTION_PO_EDIT_DATA            = 'atum-po-edit-data';
	const ACTION_PO_ADD_ITEM             = 'atum-po-add-item';
	const ACTION_PO_ADD_FEE_SHIP         = 'atum-po-add-fee-shipping';
	const ACTION_PO_ADD_TAX              = 'atum-po-add-tax';
	const ACTION_PO_ITEM_CHANGED         = 'atum-po-changed-order-item';
	const ACTION_PO_ITEM_META            = 'atum-po-order-item-meta';
	const ACTION_PO_DEL_ITEM_META        = 'atum-po-del-order-item-meta';
	const ACTION_PO_DEL_ORDER_ITEM       = 'atum-po-delete-order-item';
	const ACTION_PO_ADD_META             = 'atum-po-add-meta';
	const ACTION_PO_PURCHASE_PRICE       = 'atum-po-purchase-price';
	const ACTION_PO_ADD_NOTE             = 'atum-po-add-note';
	const ACTION_PO_DEL_NOTE             = 'atum-po-del-note';
	const ACTION_PO_GENERATE_PDF         = 'atum-po-generate-pdf';
	const ACTION_PO_STOCK_LEVELS         = 'atum-po-changed-stock-levels';
	const ACTION_PO_DEL                  = 'atum-po-del';
	const ACTION_PO_TRASH                = 'atum-po-trash';
	const ACTION_PO_UNTRASH              = 'atum-po-untrash';
	const ACTION_PO_EMAILED              = 'atum-po-emailed';
	const ACTION_PO_DELIVERY_ADD         = 'atum-po-delivery-add';
	const ACTION_PO_DELIVERY_DEL         = 'atum-po-delivery-del';
	const ACTION_PO_DELIVERY_EDIT        = 'atum-po-delivery-edit';
	const ACTION_PO_DELIVERY_INV_EDIT    = 'atum-po-delivery-inv-edit';
	const ACTION_PO_DELIVERY_STOCK       = 'atum-po-delivery-stock';
	const ACTION_PO_DELIVERY_ITEM_DEL    = 'atum-po-delivery-item-del';
	const ACTION_PO_DELIVERY_FILE_ADD    = 'atum-po-delivery-file-add';
	const ACTION_PO_DELIVERY_FILE_DEL    = 'atum-po-delivery-file-del';
	const ACTION_PO_FILE_ADDED           = 'atum-po-file-added';
	const ACTION_PO_FILE_DEL             = 'atum-po-file-del';
	const ACTION_PO_INVOICE_ADD          = 'atum-po-invoice-add';
	const ACTION_PO_INVOICE_DEL          = 'atum-po-invoice-del';
	const ACTION_PO_INVOICE_EDIT         = 'atum-po-invoice-edit';
	const ACTION_PO_INVOICE_TAX_EDIT     = 'atum-po-invoice-tax-edit';
	const ACTION_PO_INVOICE_FEE_EDIT     = 'atum-po-invoice-fee-edit';
	const ACTION_PO_INVOICE_SHIP_EDIT    = 'atum-po-invoice-shipping-edit';
	const ACTION_PO_INVOICE_ITEM_DEL     = 'atum-po-invoice-item-edit';
	const ACTION_PO_MERGE                = 'atum-po-merge';
	const ACTION_PO_CLONE                = 'atum-po-clone';
	const ACTION_PO_APPROVAL             = 'atum-po-approval';
	const ACTION_IL_CREATE               = 'atum-il-create';
	const ACTION_IL_EDIT_STATUS          = 'atum-il-edit-status';
	const ACTION_IL_EDIT_TOTALS          = 'atum-il-edit-totals';
	const ACTION_IL_EDIT_DATA            = 'atum-il-edit-data';
	const ACTION_IL_ADD_ITEM             = 'atum-il-add-item';
	const ACTION_IL_ADD_FEE_SHIP         = 'atum-il-add-fee-shipping';
	const ACTION_IL_ADD_TAX              = 'atum-il-add-tax';
	const ACTION_IL_ITEM_CHANGED         = 'atum-il-changed-order-item';
	const ACTION_IL_ITEM_META            = 'atum-il-order-item-meta';
	const ACTION_IL_DEL_ITEM_META        = 'atum-il-del-order-item-meta';
	const ACTION_IL_DEL_ORDER_ITEM       = 'atum-il-delete-order-item';
	const ACTION_IL_ADD_META             = 'atum-il-add-meta';
	const ACTION_IL_ADD_NOTE             = 'atum-il-add-note';
	const ACTION_IL_DEL_NOTE             = 'atum-il-del-note';
	const ACTION_IL_DEL                  = 'atum-il-del';
	const ACTION_IL_TRASH                = 'atum-il-trash';
	const ACTION_IL_UNTRASH              = 'atum-il-untrash';
	const ACTION_IL_INCREASE_STOCK       = 'atum-il-increase-stock';
	const ACTION_IL_DECREASE_STOCK       = 'atum-il-decrease-stock';
	const ACTION_SUPPLIER_DEL            = 'atum-supplier-del';
	const ACTION_SUPPLIER_TRASH          = 'atum-supplier-trash';
	const ACTION_SUPPLIER_UNTRASH        = 'atum-supplier-untrash';
	const ACTION_SUPPLIER_NEW            = 'atum-supplier-new';
	const ACTION_SUPPLIER_STATUS         = 'atum-supplier-change-status';
	const ACTION_SUPPLIER_DETAILS        = 'atum-supplier-change-details';
	const ACTION_SET_ENABLE_MOD          = 'atum-settings-enable-module';
	const ACTION_SET_DISABLE_MOD         = 'atum-settings-disable-module';
	const ACTION_SET_CHANGE_OPT          = 'atum-settings-change-option';
	const ACTION_SET_RUN_TOOL            = 'atum-settings-run-tool';
	const ACTION_SET_RUN_TOOL_CLI        = 'atum-settings-run-tool-cli';
	const ACTION_ADDON_ACTIVATE          = 'atum-addon-activate-license';
	const ACTION_ADDON_DEACTIVATE        = 'atum-addon-deactivate-license';
	const ACTION_LOC_CREATE              = 'atum-location-create';
	const ACTION_LOC_DEL                 = 'atum-location-delete';
	const ACTION_LOC_CHANGE              = 'atum-location-change';
	const ACTION_LOC_ASSIGN              = 'atum-location-assign';
	const ACTION_LOC_UNASSIGN            = 'atum-location-unassign';
	const ACTION_ATUM_MIN_THRESHOLD      = 'atum-reach-minimum-threshold';
	const ACTION_WC_PRODUCT_CREATE       = 'wc-product-create';
	const ACTION_WC_PRODUCT_DEL          = 'wc-product-del';
	const ACTION_WC_PRODUCT_TRASH        = 'wc-product-trash';
	const ACTION_WC_PRODUCT_UNTRASH      = 'wc-product-untrash';
	const ACTION_WC_VARIATION_LINK       = 'wc-product-variation-link';
	const ACTION_WC_VARIATION_DELETE     = 'wc-product-variation-delete';
	const ACTION_WC_ORDER_CREATE         = 'wc-order-create';
	const ACTION_WC_ORDER_CREATE_M       = 'wc-order-create-manually';
	const ACTION_WC_ORDER_STATUS         = 'wc-order-edit-status';
	const ACTION_WC_ORDER_STOCK_LVL      = 'wc-order-stock-level';
	const ACTION_WC_ORDER_CH_STOCK_LVL   = 'wc-order-ch-stock-level';
	const ACTION_WC_ORDER_ST_BULK        = 'wc-order-status-bulk';
	const ACTION_WC_ORDER_ADD_PRODUCT    = 'wc-order-add-product';
	const ACTION_WC_ORDER_ADD_FEE        = 'wc-order-add-fee';
	const ACTION_WC_ORDER_ADD_TAX        = 'wc-order-add-tax';
	const ACTION_WC_ORDER_ADD_SHIP       = 'wc-order-add-shipping-cost';
	const ACTION_WC_ORDER_ITEM_DELETE    = 'wc-order-delete-order-item';
	const ACTION_WC_ORDER_ITEM_EDIT      = 'wc-order-edit-order-item';
	const ACTION_WC_ORDER_ITEM_META      = 'wc-order-add-meta-order-item';
	const ACTION_WC_ORDER_DEL_ITEM_META  = 'wc-order-del-meta-order-item';
	const ACTION_WC_ORDER_ADD_NOTE       = 'wc-order-add-note';
	const ACTION_WC_ORDER_DATA           = 'wc-order-edit-data';
	const ACTION_WC_ORDER_TOTALS         = 'wc-order-edit-totals';
	const ACTION_WC_ORDER_ADD_COUPON     = 'wc-order-add-coupon';
	const ACTION_WC_ORDER_ADD_REFUND     = 'wc-order-add-refund';
	const ACTION_WC_ORDER_EMAIL          = 'wc-order-details-email-manual';
	const ACTION_WC_ORDER_EMAIL_AUTO     = 'wc-order-details-email-auto';
	const ACTION_WC_ORDER_INV_EMAIL      = 'wc-order-invoice-email-manual';
	const ACTION_WC_ORDER_INV_EMAIL_A    = 'wc-order-invoice-email-auto';
	const ACTION_WC_ORDER_NEW_EMAIL      = 'wc-new-order-notification-email-manual';
	const ACTION_WC_ORDER_NEW_EMAIL_A    = 'wc-new-order-notification-email-auto';
	const ACTION_WC_ORDER_DEL            = 'wc-order-del';
	const ACTION_WC_ORDER_TRASH          = 'wc-order-trash';
	const ACTION_WC_ORDER_UNTRASH        = 'wc-order-untrash';
	const ACTION_WC_COUPON_CREATE        = 'wc-coupon-create';
	const ACTION_WC_COUPON_EDIT          = 'wc-coupon-edit-details';
	const ACTION_WC_COUPON_DEL           = 'wc-couppon-del';
	const ACTION_WC_COUPON_TRASH         = 'wc-couppon-trash';
	const ACTION_WC_COUPON_UNTRASH       = 'wc-couppon-untrash';
	const ACTION_WC_CATEGORY_CREATE      = 'wc-category-create';
	const ACTION_WC_CATEGORY_REMOVE      = 'wc-category-remove';
	const ACTION_WC_CATEGORY_EDIT        = 'wc-category-edit';
	const ACTION_WC_CATEGORY_ADD         = 'wc-category-add-product';
	const ACTION_WC_CATEGORY_DEL         = 'wc-category-del-product';
	const ACTION_WC_TAG_CREATE           = 'wc-tag-create';
	const ACTION_WC_TAG_REMOVE           = 'wc-tag-remove';
	const ACTION_WC_TAG_EDIT             = 'wc-tag-edit';
	const ACTION_WC_TAG_ADD              = 'wc-tag-add-product';
	const ACTION_WC_TAG_DEL              = 'wc-tag-del-product';
	const ACTION_WC_ATTR_CREATE          = 'wc-attribute-create';
	const ACTION_WC_ATTR_DELETE          = 'wc-attribute-delete';
	const ACTION_WC_ATTR_UPDATE          = 'wc-attribute-update';
	const ACTION_WC_ATTR_ADD             = 'wc-attribute-add-term';
	const ACTION_WC_ATTR_ASSIGN          = 'wc-attribute-assign';
	const ACTION_WC_ATTR_UNASSIGN        = 'wc-attribute-unassign';
	const ACTION_WC_ATTR_ASSIGN_VALUE    = 'wc-attribute-assign-value';
	const ACTION_WC_ATTR_UNASSIGN_VALUE  = 'wc-attribute-unassign-value';
	const ACTION_WC_PRODUCT_STATUS       = 'wc-product-status-changed';
	const ACTION_WC_PRODUCT_REVIEW       = 'wc-product-review-added';
	const ACTION_WC_SETTINGS             = 'wc-settings';
	const ACTION_MC_EXPORT               = 'pl-mc-export';
	const ACTION_MI_INV_OUT_STOCK        = 'mi-inventory-out-of-stock';
	const ACTION_MI_INV_DEPLETED         = 'mi-inventory-depleted';
	const ACTION_MI_INV_USE_NEXT         = 'mi-inventory-depleted-use-next';
	const ACTION_MI_EDIT_INVENTORY       = 'mi-inventory-edit';
	const ACTION_MI_INVENTORY_CREATE     = 'mi-inventory-new';
	const ACTION_MI_INVENTORY_DELETE     = 'mi-inventory-delete';
	const ACTION_MI_MARK_WRITE_OFF       = 'mi-inventory-mark-write-off';
	const ACTION_MI_UNMARK_WRITE_OFF     = 'mi-inventory-unmark-write-off';
	const ACTION_MI_INVENTORY_EXPIRED    = 'mi-inventory-expired';
	const ACTION_MI_INV_REG_RESTRICT     = 'mi-inventory-region-restriction';
	const ACTION_MI_ORDER_ITEM_QTY       = 'mi-order-item-inventory-quantity';
	const ACTION_MI_PO_ITEM_QTY          = 'mi-po-item-inventory-quantity';
	const ACTION_MI_PO_ITEM_STLEVEL      = 'mi-po-item-inventory-stock-levels';
	const ACTION_MI_MULTIPRICE_SOLD      = 'mi-multiprice-units-sold';
	const ACTION_MI_INVENTORIES_USED     = 'mi-order-item-inventories-used';
	const ACTION_MI_PO_INV_USED          = 'mi-po-item-inventories-used';
	const ACTION_MI_INVENTORIES_EDIT     = 'mi-order-item-inventories-changed';
	const ACTION_MI_PO_INV_EDIT          = 'mi-po-item-inventories-changed';
	const ACTION_MI_ORDERITEM_INV_ADD    = 'mi-order-item-inventory-add';
	const ACTION_MI_ORDERITEM_INV_DEL    = 'mi-order-item-inventory-del';
	const ACTION_MI_ORDERITEM_INV_PO_DEL = 'mi-order-item-inventory-po-del';
	const ACTION_MI_ORDERITEM_INV_IL_DEL = 'mi-order-item-inventory-il-del';
	const ACTION_MI_PO_ITEM_INV_ADD      = 'mi-po-item-inventory-add';
	const ACTION_PL_BOM_MIN_THRESHOLD    = 'pl-bom-minimum-threshold';
	const ACTION_PL_BOM_USED             = 'pl-order-item-bom-used';
	const ACTION_PL_BOM_INV_USED         = 'pl-order-item-bom-inventory-used';
	const ACTION_PL_LINK_RAW_MAT         = 'pl-bom-link-raw-material';
	const ACTION_PL_LINK_PROD_PART       = 'pl-bom-link-product-part';
	const ACTION_PL_UNLINK_RAW_MAT       = 'pl-bom-unlink-raw-material';
	const ACTION_PL_UNLINK_PROD_PART     = 'pl-bom-unlink-product-part';
	const ACTION_PL_BOM_EDIT_QTY         = 'pl-order-item-bom-edit-qty';
	const ACTION_PL_ORDER_ITEM_BOMS      = 'pl-order-item-boms-changed';
	const ACTION_PL_ORDER_ITEM_QTY       = 'pl-order-item-bom-qty';
	const ACTION_PL_IL_ORDER_ITEM_QTY    = 'pl-il-order-item-bom-qty';
	const ACTION_PL_PO_ORDER_ITEM_QTY    = 'pl-po-order-item-bom-qty';
	const ACTION_PL_STOCK_LEVEL_BOM      = 'pl-product-stock-levels-bom';
	const ACTION_PL_PRODUCE_STOCK        = 'pl-produce-stock';
	const ACTION_PL_PRODUCE_STOCK_INV    = 'pl-produce-stock-inv';
	const ACTION_PL_PRODUCE_BOM_STOCK    = 'pl-produce-bom-stock';
	const ACTION_PD_MANAGE_STOCK         = 'product-data-atum-manage-stock';
	const ACTION_PD_ENABLE_SYNC          = 'product-data-enable-sync-purchase-price';
	const ACTION_PD_DISABLE_SYNC         = 'product-data-disable-sync-purchase-price';
	const ACTION_PD_EDIT                 = 'product-data-edit';
	const ACTION_PD_EDIT_2               = 'product-data-edit-2';
	const ACTION_MI_PD_EDIT              = 'mi-product-data-edit';
	const ACTION_EP_TEMPLATE_EXPORT      = 'ep-template-export';
	const ACTION_EP_TEMPLATE_DOWNLOAD    = 'ep-template-download';
	const ACTION_EP_NO_TEMPLATE_EXPORT   = 'ep-no-template-export';
	const ACTION_EP_CUSTOM_TEMPLATE      = 'ep-save-custom-template';
	const ACTION_EP_TEMPLATE_EDIT        = 'ep-save-template-edit';
	const ACTION_EP_TEMPLATE_FIELDS      = 'ep-save-template-fields';
	const ACTION_EP_TEMPLATE_DEL_SUCCESS = 'ep-delete-template-success';
	const ACTION_EP_TEMPLATE_DEL_FAIL    = 'ep-delete-template-fail';
	const ACTION_EP_SCHEDULED_EXPORT     = 'ep-template-scheduled-export';
	const ACTION_EP_EMAIL_SENT           = 'ep-email-sent';
	const ACTION_EP_EMAIL_NOT_SENT       = 'ep-email-not-sent';
	const ACTION_EP_FILE_STORED          = 'ep-file-stored';
	const ACTION_EP_MAX_FILES_REACHED    = 'ep-max-files-reached';
	const ACTION_EP_IMPORT_START         = 'ep-template-import-start';
	const ACTION_EP_IMPORT_SUCCESS       = 'ep-template-import-success';
	const ACTION_EP_IMPORT_FAIL          = 'ep-template-import-fail';
	const ACTION_EP_IMPORT_WARNING       = 'ep-template-import-warning';
	const ACTION_ST_ORDER_ADD            = 'st-create-order';
	const ACTION_ST_ORDER_TRASH          = 'st-trash-order';
	const ACTION_ST_ORDER_UNTRASH        = 'st-untrash-order';
	const ACTION_ST_ORDER_DEL            = 'st-delete-order';
	const ACTION_ST_ORDER_RECONCILE      = 'st-reconcile-order';
	const ACTION_ST_RECONCILE_STOCK_LVL  = 'st-reconcile-stock-level';
	const ACTION_ST_RECONCILE_INV_STLVL  = 'st-reconcile-inventory-stock-level';
	const ACTION_PP_ORDER_ADD            = 'pp-create-order';
	const ACTION_PP_ORDER_TRASH          = 'pp-trash-order';
	const ACTION_PP_ORDER_UNTRASH        = 'pp-untrash-order';
	const ACTION_PP_ORDER_DEL            = 'pp-delete-order';
	const ACTION_PP_ORDER_PRINT          = 'pp-print-order';
	const ACTION_PP_PACKED_ORDER_ITEM    = 'pp-packed-order-item';
	const ACTION_PP_PACKED_ORDER         = 'pp-packed-order';
	const ACTION_PP_COMPLETED            = 'pp-completed-picking-list';
	const ACTION_PP_EDIT_STATUS          = 'pp-edit-status';

	/**
	 * Translates a slug into a text string
	 *
	 * @since 0.5.1
	 * @param string $slug
	 * @param bool   $save
	 *
	 * @return string|void
	 */
	public static function get_text( $slug, $save = FALSE ) {

		switch ( $slug ) {
			case self::ACTION_SC_EDIT:
				/* Translators: %1$s: field, %2$s: entity, %3$s: product link */
				$text = __( 'Changed the %1$s to the %2$s %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_SET_LOC:
				/* Translators: %1$s: added locations, %2$s: entity, %3$s: product link */
				$text = __( 'Assigned the ATUM locations %1$s to the %2$s %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_DEL_LOC:
				/* Translators: %1$s: removed locations, %2$s: entity, %3$s: product link */
				$text = __( 'Removed the ATUM locations %1$s from the %2$s %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_UNCONTROL_STOCK:
				/* Translators: %1$s: type of product, %2$s: List of products */
				$text = __( 'Disabled the ATUM’s stock control in bulk to the %1$s %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_CONTROL_STOCK:
				/* Translators: %1$s: type of product, %2$s: List of products */
				$text = __( 'Enabled the ATUM’s stock control in bulk to the %1$s %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_UNMANAGE_STOCK:
				/* Translators: %1$s: type of product, %2$s: List of products */
				$text = __( 'Disabled the WC’s manage stock in bulk to the %1$s %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_MANAGE_STOCK:
				/* Translators: %1$s: type of product, %2$s: List of products */
				$text = __( 'Enabled the WC’s manage stock in bulk to the %1$s %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SC_EXPORT:
				$text = __( 'Stock Central data exported', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MC_EXPORT:
				$text = __( 'Exported the BOM products', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_CREATE:
				/* Translators: %s: purchase order link */
				$text = __( 'Created a new Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_EDIT_STATUS:
				/* Translators: %s: purchase order link */
				$text = __( 'Changed the Status to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_EDIT_TOTALS:
				/* Translators: %s: purchase order link */
				$text = __( 'Changed the Totals to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_EDIT_DATA:
				/* Translators: %1$s: field name, %2$s purchase order link */
				$text = __( 'Set the %1$s for the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_EDIT_STATUS:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Changed the Status to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_EDIT_TOTALS:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Changed the Totals to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_EDIT_DATA:
				/* Translators: %1$s: field name, %2$s purchase order link */
				$text = __( 'Set the %1$s for the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ADD_ITEM:
				/* Translators: %s purchase order link */
				$text = __( 'Added a new product to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ADD_ITEM:
				/* Translators: %s inventory log link */
				$text = __( 'Added a new product to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ADD_FEE_SHIP:
				/* Translators: %1$s fee/shipping cost, %2$s: purchase order link */
				$text = __( 'Added a new %1$s to the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_CREATE:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Created a new Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ADD_FEE_SHIP:
				/* Translators: %1$s fee/shipping cost, %2$s: inventory log link */
				$text = __( 'Added a new %1$s to the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ADD_TAX:
				/* Translators: %s purchase order link */
				$text = __( 'Added a new tax to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ADD_TAX:
				/* Translators: %s inventory log link */
				$text = __( 'Added a new tax to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ITEM_CHANGED:
				/* Translators: %1$s: field name, %2$s: order_item, %3$s: purchase order link */
				$text = __( 'Changed the %1$s for the order item %2$s on the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ITEM_META:
				/* Translators: %1$s: order_item, %2$s: purchase order link */
				$text = __( 'Added meta to the order item %1$s on the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DEL_ITEM_META:
				/* Translators: %1$s: order_item, %2$s: purchase order link */
				$text = __( 'Removed meta from the order item %1$s on the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ITEM_CHANGED:
				/* Translators: %1$s: field name, %2$s: order_item, %3$s: inventory log link */
				$text = __( 'Changed the %1$s for the order item %2$s on the Inventory Log %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ITEM_META:
				/* Translators: %1$s: order_item, %2$s: inventory log link */
				$text = __( 'Added meta to the order item %1$s on the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_DEL_ITEM_META:
				/* Translators: %1$s: order_item, %2$s: inventory log link */
				$text = __( 'Removed meta from the order item %1$s on the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DEL_ORDER_ITEM:
				/* Translators: %1$s: order item id, %2$s: Purchase Order link */
				$text = __( 'Deleted the order item %1$s from the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_DEL_ORDER_ITEM:
				/* Translators: %1$s: order item id, %2$s: Inventory Log link */
				$text = __( 'Deleted the order item %1$s from the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ADD_META:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Added meta to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ADD_META:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Added meta to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_PURCHASE_PRICE:
				/* Translators: %1$s: Product link, %2$s: Purchase Order link */
				$text = __( 'Set the purchase price for the product %1$s from the Purchase Order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_ADD_NOTE:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Added a note to the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DEL_NOTE:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Removed a note from the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_ADD_NOTE:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Added a note to the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_DEL_NOTE:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Removed a note from the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_GENERATE_PDF:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Printed the Purchase Order %s to PDF', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_STOCK_LEVELS:
				/* Translators: %1$s: Product link, %2$s: Purchase Order link */
				$text = __( 'The stock levels of product %1$s changed after changing the PO %2$s status', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DEL:
				/* Translators: %s: Purchase Order id */
				$text = __( 'Deleted Permanently the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_DEL:
				/* Translators: %s: Inventory Log id */
				$text = __( 'Deleted Permanently the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_INCREASE_STOCK:
				/* Translators: %1$s: Product link, %2$s: Inventory Log link */
				$text = __( 'Increased the stock for the product %1$s from the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_DECREASE_STOCK:
				/* Translators: %1$s: Product link, %2$s: Inventory Log link */
				$text = __( 'Decreased the stock for the product %1$s from the Inventory Log %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_DEL:
				/* Translators: %s: Supplier id */
				$text = __( 'Deleted Permanently the Supplier %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_DEL:
				/* Translators: %s: Product id */
				$text = __( 'Deleted Permanently the Product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_DEL:
				/* Translators: %s: Order id */
				$text = __( 'Deleted Permanently the Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_COUPON_DEL:
				/* Translators: %s: Coupon id */
				$text = __( 'Deleted Permanently the Coupon %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_TRASH:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Moved to trash the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_TRASH:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Moved to trash the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_TRASH:
				/* Translators: %s: Supplier link */
				$text = __( 'Moved to trash the Supplier %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_TRASH:
				/* Translators: %s: Product link */
				$text = __( 'Moved to trash the Product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_TRASH:
				/* Translators: %s: Order link */
				$text = __( 'Moved to trash the Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_COUPON_TRASH:
				/* Translators: %s: Coupon link */
				$text = __( 'Moved to trash the Coupon %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_UNTRASH:
				/* Translators: %s: Purchase Order link */
				$text = __( 'Restored the Purchase Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_EMAILED:
				/* Translators: %s: Purchase Order link */
				$text = __( 'The PO %s has been mailed', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_ADD:
				/* Translators: %1$s: Delivery name, %2$s: Purchase Order link */
				$text = __( 'A new delivery %1$s was added to the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_DEL:
				/* Translators: %1$s: Delivery name, %2$s: Purchase Order link */
				$text = __( 'The delivery %1$s was removed from the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_EDIT:
				/* Translators: %1$s: product link, %2$s: Delivery name, %3$s: Purchase Order link */
				$text = __( 'Edited the item %1$s for the Delivery %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_INV_EDIT:
				/* Translators: %1$s: inventory name, %2$s: Delivery name, %3$s: Purchase Order link */
				$text = __( 'Edited the inventory %1$s for the Delivery %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_STOCK:
				/* Translators: %1$s: Product link, %2$s; Delivery name, %3$s: Purchase Order link */
				$text = __( 'Increased the stock for the product %1$s from the Delivery %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_ITEM_DEL:
				/* Translators: %1$s: item name, %2$s: Delivery name, %3$s: Purchase Order link */
				$text = __( 'The item %1$s in the Delivery %2$s was removed from the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_FILE_ADD:
				/* Translators: %1$s: Delivery name, %2$s: Purchase Order link */
				$text = __( 'A new file was added to the Delivery %1$s from the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_DELIVERY_FILE_DEL:
				/* Translators: %1$s: File, %2$s: Delivery name, %3$s: Purchase Order link */
				$text = __( 'The file %1$s was removed from the Delivery %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_FILE_ADDED:
				/* Translators: %s: Purchase Order link */
				$text = __( 'A new file was added to the PO %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_FILE_DEL:
				/* Translators: %1$s: File, %2$s Purchase Order link */
				$text = __( 'The file %1$s was removed from the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_ADD:
				/* Translators: %1$s: Invoice name, %2$s: Purchase Order link */
				$text = __( 'A new invoice %1$s was added to the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_DEL:
				/* Translators: %1$s: Invoice name, %2$s: Purchase Order link */
				$text = __( 'The invoice %1$s was removed from the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_EDIT:
				/* Translators: %1$s: product link, %2$s: Invoice name, %3$s: Purchase Order link */
				$text = __( 'Edited the item %1$s for the Invoice %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_TAX_EDIT:
				/* Translators: %1$s: product link, %2$s: Invoice name, %3$s: Purchase Order link */
				$text = __( 'Edited the tax %1$s for the Invoice %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_FEE_EDIT:
				/* Translators: %1$s: product link, %2$s: Invoice name, %3$s: Purchase Order link */
				$text = __( 'Edited the fee %1$s for the Invoice %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_SHIP_EDIT:
				/* Translators: %1$s: product link, %2$s: Invoice name, %3$s: Purchase Order link */
				$text = __( 'Edited the shipping cost %1$s for the Invoice %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_INVOICE_ITEM_DEL:
				/* Translators: %1$s: product link, %2$s: Invoice name, %3$s: Purchase Order link */
				$text = __( 'Removed the item %1$s for the Invoice %2$s within the PO %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_MERGE:
				/* Translators: %1$s: Purchase Order link, %2$s: Purchase Order link */
				$text = __( 'Merged the Purchase Order %1$s with the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_CLONE:
				/* Translators: %1$s: Purchase Order link, %2$s: Purchase Order link */
				$text = __( 'Cloned the Purchase Order %1$s from the PO %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PO_APPROVAL:
				/* Translators: %1$s: Purchase Order link, %2$s: User name */
				$text = __( 'The Purchase Order %1$s has been approved by %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_IL_UNTRASH:
				/* Translators: %s: Inventory Log link */
				$text = __( 'Restored the Inventory Log %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_UNTRASH:
				/* Translators: %s: Supplier link */
				$text = __( 'Restored the Supplier %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_UNTRASH:
				/* Translators: %s: Product link */
				$text = __( 'Restored the Product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_UNTRASH:
				/* Translators: %s: Order link */
				$text = __( 'Restored the Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_COUPON_UNTRASH:
				/* Translators: %s: Coupon link */
				$text = __( 'Restored the Coupon %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_NEW:
				/* Translators: %s: Supplier link */
				$text = __( 'Created a new Supplier %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_STATUS:
				/* Translators: %s: Supplier link */
				$text = __( 'Changed the Supplier Status to %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SUPPLIER_DETAILS:
				/* Translators: %1$s: field, %2$s: Supplier link */
				$text = __( 'Changed %1$s to the Supplier %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SET_ENABLE_MOD:
				/* Translators: %s: Module name */
				$text = __( 'Enabled the module %s from ATUM Settings', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SET_DISABLE_MOD:
				/* Translators: %s: Module name */
				$text = __( 'Disabled the module %s from ATUM Settings', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SET_CHANGE_OPT:
				/* Translators: %s: Option name */
				$text = __( 'Changed the option %s from ATUM Settings', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SET_RUN_TOOL:
				/* Translators: %s: Tool name */
				$text = __( 'Run the tool %s from ATUM Settings', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_SET_RUN_TOOL_CLI:
				/* Translators: %s: Tool name */
				$text = __( 'Run the tool %s from command line', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_ADDON_ACTIVATE:
				/* Translators: %s: Addon */
				$text = __( 'Activated the license key for the add-on %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_ADDON_DEACTIVATE:
				/* Translators: %s: Addon */
				$text = __( 'Deactivated the license key for the add-on %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_LOC_CREATE:
				/* Translators: %s: Location link */
				$text = __( 'Created a new ATUM Location %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_LOC_DEL:
				/* Translators: %s: Location */
				$text = __( 'Deleted the ATUM Location %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_LOC_CHANGE:
				/* Translators: %s: Location link */
				$text = __( 'Changed the ATUM Location %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_LOC_ASSIGN:
				/* Translators: %1$s: Location link, %2$s: Product link */
				$text = __( 'Assigned the ATUM Location %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_LOC_UNASSIGN:
				/* Translators: %1$s: Location link, %2$s: Product link */
				$text = __( 'Removed the ATUM Location %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_CREATE:
				/* Translators: %s: Product link */
				$text = __( 'Created a new product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_VARIATION_LINK:
				/* Translators: %1$s: Variation name, %2$s: Product link */
				$text = __( 'Added a new variation %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_VARIATION_DELETE:
				/* Translators: %1$s: Variation name, %2$s: Product link */
				$text = __( 'Removed the variation %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_CREATE:
				/* Translators: %s: Order link */
				$text = __( 'A new order %s was created by a customer', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_CREATE_M:
				/* Translators: %s: Order link */
				$text = __( 'Created a new manual order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_TOTALS:
				/* Translators: %s: Order link */
				$text = __( 'Changed the Totals for the order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_STATUS:
				/* Translators: %1$s: Field name, %2$s: Order link */
				$text = __( 'Changed the %1$s for the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_DATA:
				/* Translators: %1$s: Field name, %2$s: Order link */
				$text = __( 'Set the %1$s for the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_COUPON:
				/* Translators: %1$s: Coupon link, %2$s: Order link */
				$text = __( 'Applied the coupon %1$s to the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_REFUND:
				/* Translators: %s: Order link */
				$text = __( 'Applied a refund to the order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_EMAIL:
				/* Translators: %s: mode */
				$text = __( 'An email was sent (Manually) to the customer with the order details', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_EMAIL_AUTO:
				/* Translators: %s: mode */
				$text = __( 'An email was sent (Automatically) to the customer with the order details', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_INV_EMAIL:
				/* Translators: %s: mode */
				$text = __( 'An email was sent (Manually) to the customer with the invoice', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_INV_EMAIL_A:
				/* Translators: %s: mode */
				$text = __( 'An email was sent (Automatically) to the customer with the invoice', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_NEW_EMAIL:
				/* Translators: %s: mode */
				$text = __( 'An order notification email was sent (Manually)', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_NEW_EMAIL_A:
				/* Translators: %s: mode */
				$text = __( 'An order notification email was sent (Automatically)', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_STOCK_LVL:
				/* Translators: %1$s: Product link, %2$s: Order link */
				$text = __( 'The stock levels of product %1$s changed after changing the order status %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_CH_STOCK_LVL:
				/* Translators: %1$s: Product link, %2$s: Order link */
				$text = __( 'The stock levels of product %1$s changed after adding to the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ST_BULK:
				/* Translators: %s: Orders links list */
				$text = __( 'Changed the status in bulk to the orders %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_PRODUCT:
				/* Translators: %1$s: Product link, %2$s Orders link */
				$text = __( 'Added a new product %1$s to the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_FEE:
				/* Translators: %s: Orders link */
				$text = __( 'Added a new Fee to the order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_TAX:
				/* Translators: %s: Orders link */
				$text = __( 'Added a new Tax to the order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_SHIP:
				/* Translators: %s: Orders link */
				$text = __( 'Added a new Shipping Cost to the order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ITEM_DELETE:
				/* Translators: %1$s: order items list, %2$s: Order link */
				$text = __( 'Deleted the order items %1$s on the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ITEM_EDIT:
				/* Translators: %1$s: field name, %2$s: Order item id, %3$s: Order link */
				$text = __( 'Changed the %1$s for the order item %2$s on the order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ITEM_META:
				/* Translators: %1$s: Order item id, %2$s: Order link */
				$text = __( 'Added meta to the order item %1$s on the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_DEL_ITEM_META:
				/* Translators: %1$s: Order item id, %2$s: Order link */
				$text = __( 'Removed meta from the order item %1$s on the order %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ORDER_ADD_NOTE:
				/* Translators: %s: Order link */
				$text = __( 'Added a note to the Order %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_CATEGORY_CREATE:
				/* Translators: %s: Category link */
				$text = __( 'Created a new Category %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_CATEGORY_REMOVE:
				/* Translators: %s: Category name */
				$text = __( 'Deleted the Category %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_CATEGORY_EDIT:
				/* Translators: %s: Category link */
				$text = __( 'Changed the Category %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_CATEGORY_ADD:
				/* Translators: %1$s: Category link, %2$s: Product link */
				$text = __( 'Added the Category %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_CATEGORY_DEL:
				/* Translators: %1$s: Category link, %2$s: Product link */
				$text = __( 'Removed the Category %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_TAG_CREATE:
				/* Translators: %s: Tag link */
				$text = __( 'Created a new Tag %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_TAG_REMOVE:
				/* Translators: %s: Tag name */
				$text = __( 'Deleted the Tag %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_TAG_EDIT:
				/* Translators: %s: Tag link */
				$text = __( 'Changed the Tag %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_TAG_ADD:
				/* Translators: %1$s: Tag link, %2$s: Product link */
				$text = __( 'Added the Tag %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_TAG_DEL:
				/* Translators: %1$s: Tag link, %2$s: Product link */
				$text = __( 'Removed the Tag %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_CREATE:
				/* Translators: %s: Attribute link */
				$text = __( 'Created a new attribute %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_ADD:
				/* Translators: %1$s: new value, %2$s: Attribute link */
				$text = __( 'Added a new value %1$s to attribute %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_DELETE:
				/* Translators: %s: Attribute name */
				$text = __( 'Deleted the attribute %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_UPDATE:
				/* Translators: %s: Attribute link */
				$text = __( 'Changed the Attribute %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_ASSIGN:
				/* Translators: %1$s: Attribute link, %2$s: Product link */
				$text = __( 'Assigned the Attribute %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_UNASSIGN:
				/* Translators: %1$s: Attribute link, %2$s: Product link */
				$text = __( 'Removed the Attribute %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_ASSIGN_VALUE:
				/* Translators: %1$s: Value, %2$s: Attribute link, %3$s: Product link */
				$text = __( 'Assigned the value %1$s to the Attribute %2$s within the product %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_ATTR_UNASSIGN_VALUE:
				/* Translators: %1$s: Value, %2$s: Attribute link, %3$s: Product link */
				$text = __( 'Removed the value %1$s from the Attribute %2$s within the product %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_STATUS:
				/* Translators: %s: Product link */
				$text = __( 'Changed the status to the product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_PRODUCT_REVIEW:
				/* Translators: %s: Product link */
				$text = __( 'Added a new review to the product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_COUPON_CREATE:
				/* Translators: %s: Coupon link */
				$text = __( 'Created a new coupon %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_COUPON_EDIT:
				/* Translators: %1$s: field, %2$s: Coupon link */
				$text = __( 'Changed the %1$s for the coupon %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_WC_SETTINGS:
				/* Translators: %s: setting option */
				$text = __( 'Changed the option “%s” from WC settings', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PD_MANAGE_STOCK:
				/* Translators: %s: Product link */
				$text = __( 'Switched the ATUM Controlled at product level for the product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PD_ENABLE_SYNC:
				/* Translators: %s: Product link */
				$text = __( 'Enabled the “Sync Purchase Price” on the product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PD_DISABLE_SYNC:
				/* Translators: %s: Product link */
				$text = __( 'Disabled the “Sync Purchase Price” on the product %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PD_EDIT:
				/* Translators: %1$s: Field, %2$s: Product link */
				$text = __( 'Changed the %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PD_EDIT_2:
				/* Translators: %1$s: Field, %2$s: Product link */
				$text = __( 'Changed the %1$s for the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PD_EDIT:
				/* Translators: %1$s: Field, %2$s: Product link */
				$text = __( 'Changed the setting %1$s for the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INV_OUT_STOCK:
				/* Translators: %1$s: inventory id, %2$s: Product link */
				$text = __( 'The inventory %1$s of product %2$s has reached its “Out of stock threshold” and it was marked as “Out of stock”', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INV_DEPLETED:
				/* Translators: %1$s: inventory id, %2$s: Product link */
				$text = __( 'Inventory %1$s of product %2$s depleted, the inventory has been marked as “Out of stock”', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INV_USE_NEXT:
				/* Translators: %1$s: inventory id, %2$s: Product link */
				$text = __( 'Inventory %1$s of product %2$s depleted, using the next one in the list', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_EDIT_INVENTORY:
				/* Translators: %1$s: field, %2$s: inventory id, %3$s: Product link */
				$text = __( 'Changed the %1$s to the inventory %2$s on the product %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INVENTORY_CREATE:
				/* Translators: %1$s: inventory id, %2$s: Product link */
				$text = __( 'Created a new inventory %1$s on the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INVENTORY_DELETE:
				/* Translators: %1$s: inventory id, %2$s: Product link */
				$text = __( 'Removed the inventory %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_MARK_WRITE_OFF:
				/* Translators: %1$s: inventory list, %2$s: Product link */
				$text = __( 'Marked the inventories %1$s as “write-off” on the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_UNMARK_WRITE_OFF:
				/* Translators: %1$s: inventory list, %2$s: Product link */
				$text = __( 'Unmarked the inventories %1$s as “write-off” on the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INVENTORY_EXPIRED:
				/* Translators: %s: inventory id */
				$text = __( 'The inventory %s has expired', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INV_REG_RESTRICT:
				/* Translators: %1$s: Order link, %2$s: inventory id */
				$text = __( 'The region restriction was applied on order %1$s and the inventory %2$s was bypassed', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_ORDER_ITEM_QTY:
				/* Translators: %1$s: quantity, %2$s: Inventory, %3$s: Order link */
				$text = __( '%1$s units of Inventory %2$s were used for the Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PO_ITEM_QTY:
				/* Translators: %1$s: inventory id, %2$s: Order item id, %3$s: Purchase Order link */
				$text = __( 'Changed the quantity for the inventory %1$s of the order item %2$s within the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PO_ITEM_STLEVEL:
				/* Translators: %1$s: inventory id, %2$s: Order item id, %3$s: Purchase Order link */
				$text = __( 'Changed the stock levels for the inventory %1$s of the product %2$s within the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_MULTIPRICE_SOLD:
				/* Translators: %1$s: quantity, %2$s: inventory, %3$s: price */
				$text = __( '%1$s units of inventory %2$s were sold at %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INVENTORIES_USED:
				/* Translators: %1$s: inventories list, %2$s: Order item id, %3$s: Order link */
				$text = __( 'The Inventories %1$s were used automatically for the order item %2$s from the order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PO_INV_USED:
				/* Translators: %1$s: inventories list, %2$s: Order item id, %3$s: Purchase Order link */
				$text = __( 'The Inventories %1$s were used automatically for the order item %2$s from the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_INVENTORIES_EDIT:
				/* Translators: %1$s: inventories list, %2$s: Order item id, %3$s: Order link */
				$text = __( 'Changed manually the Inventories %1$s for the order item %2$s within the order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PO_INV_EDIT:
				/* Translators: %1$s: inventories list, %2$s: Order item, %3$s: Purchase Order link */
				$text = __( 'Changed manually the Inventories %1$s for the order item %2$s within the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_ORDERITEM_INV_ADD:
				/* Translators: %1$s: inventory, %2$s: order item, %3$s: Order link */
				$text = __( 'Added manually a new inventory %1$s for the order item %2$s within the order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_ORDERITEM_INV_DEL:
				/* Translators: %1$s: inventory, %2$s: order item, %3$s: Order link */
				$text = __( 'Removed Inventory %1$s from the order item %2$s within the order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_ORDERITEM_INV_PO_DEL:
				/* Translators: %1$s: inventory, %2$s: order item, %3$s: Order link */
				$text = __( 'Removed Inventory %1$s from the order item %2$s within the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_ORDERITEM_INV_IL_DEL:
				/* Translators: %1$s: inventory, %2$s: order item, %3$s: Order link */
				$text = __( 'Removed Inventory %1$s from the order item %2$s within the Inventory Log %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_MI_PO_ITEM_INV_ADD:
				/* Translators: %1$s: inventory id, %2$s: order item id, %3$s: Purchase Order link */
				$text = __( 'Added manually a new inventory %1$s for the order item %2$s within the Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_BOM_MIN_THRESHOLD:
				/* Translators: %1$s: Product link, %2$s: BOM Product link */
				$text = __( 'The product %1$s has reached its minimum threshold on the BOM %2$s and all the associated products with a lower priority were set as “Out of stock”', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_ATUM_MIN_THRESHOLD:
				/* Translators: %s: Product link */
				$text = __( 'The product %s has reached its “Out of stock threshold” and it was marked as “Out of stock”', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_BOM_USED:
				/* Translators: %1$s: BOM list links, %2$s: order item id, %3$s: Order link */
				$text = __( 'The BOMs %1$s were used automatically for the order item %2$s from order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_LINK_RAW_MAT:
				/* Translators: %1$s: Raw Material link, %2$s: Product link */
				$text = __( 'Linked a Raw Material %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_LINK_PROD_PART:
				/* Translators: %1$s: product part link, %2$s: Product link */
				$text = __( 'Linked a Product Part %1$s to the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_UNLINK_RAW_MAT:
				/* Translators: %1$s: Raw Material link, %2$s: Product link */
				$text = __( 'Unlinked a Raw Material %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_UNLINK_PROD_PART:
				/* Translators: %1$s: product part link, %2$s: Product link */
				$text = __( 'Unlinked a Product Part %1$s from the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_BOM_INV_USED:
				/* Translators: %1$s: BOM list links, %2$s: order item id, %3$s: Order link */
				$text = __( 'The BOMs %1$s were used automatically for the main inventory used on order item %2$s on order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_BOM_EDIT_QTY:
				/* Translators: %1$s: BOM link, %2$s: Product link */
				$text = __( 'Changed the quantity to the linked BOM %1$s on the product %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_ORDER_ITEM_QTY:
				/* Translators: %1$s: BOM, %2$s: order item id, %3$s: Order link */
				$text = __( 'Changed the quantity used for BOM %1$s on the order item %2$s from order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_IL_ORDER_ITEM_QTY:
				/* Translators: %1$s: BOM, %2$s: order item id, %3$s: Order link */
				$text = __( 'Changed the quantity used for BOM %1$s on the order item %2$s from Inventory Log %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_PO_ORDER_ITEM_QTY:
				/* Translators: %1$s: BOM, %2$s: order item id, %3$s: Order link */
				$text = __( 'Changed the quantity used for BOM %1$s on the order item %2$s from Purchase Order %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_STOCK_LEVEL_BOM:
				/* Translators: %s: Product link */
				$text = __( 'The stock levels of product %1$s changed after changing their BOMs stock', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_ORDER_ITEM_BOMS:
				/* Translators: %1$s: BOM product, %2$s: source inventory, %3$s: target inventory, %4$s: Order link */
				$text = __( 'The BOM %1$s has changed the inventory %2$s by inventory %3$s in the order %4$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_EXPORT:
				/* Translators: %s: template name */
				$text = __( 'Exported the template %s manually', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_DOWNLOAD:
				/* Translators: %s: template name */
				$text = __( 'Downloaded the export %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_NO_TEMPLATE_EXPORT:
				$text = __( 'Run a manual export', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_CUSTOM_TEMPLATE:
				/* Translators: %s: template name */
				$text = __( 'Saved the template %s as a new custom template', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_EDIT:
				/* Translators: %1$s: field, %2$s: template name */
				$text = __( 'Changed the %1$s to the template %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_FIELDS:
				/* Translators: %s: template name */
				$text = __( 'Changed the fields to the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_DEL_SUCCESS:
				/* Translators:  %1$s: name */
				$text = __( 'Deleted template %1$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_TEMPLATE_DEL_FAIL:
				/* Translators:  %1$s: name */
				$text = __( 'Failed to delete template %1$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_SCHEDULED_EXPORT:
				/* Translators: %s: template name */
				$text = __( 'A scheduled export has executed for the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_EMAIL_SENT:
				/* Translators: %s: template name */
				$text = __( 'An email was sent after exporting the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_EMAIL_NOT_SENT:
				/* Translators: %s: template name */
				$text = __( 'An email was not sent after exporting the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_FILE_STORED:
				/* Translators: %s: template name */
				$text = __( 'A file was stored on the server after exporting the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_IMPORT_START:
				/* Translators: %s: template name */
				$text = __( 'An import using the template %s has started', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_IMPORT_SUCCESS:
				/* Translators: %s: template name */
				$text = __( 'An import was executed successfully using the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_IMPORT_FAIL:
				/* Translators: %s: template name */
				$text = __( 'An import was executed and failed using the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_IMPORT_WARNING:
				/* Translators: %s: template name */
				$text = __( 'An import was executed successfully with warnings using the template %s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_EP_MAX_FILES_REACHED:
				$text = 'The max number of retained files was reached, deleting older files';
				break;
			case self::ACTION_PL_PRODUCE_STOCK:
				/* Translators: %1$s: quantity, %2$s: Product link */
				$text = __( '%1$s units of product %2$s were produced', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_PRODUCE_STOCK_INV:
				/* Translators: %1$s: quantity, %2$s: Product link, %3$s: Inventory name */
				$text = __( '%1$s units of product %2$s were produced for the inventory %3$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			case self::ACTION_PL_PRODUCE_BOM_STOCK:
				/* Translators: %1$s: BOM Product link, %2$s: Produced Product link */
				$text = __( 'The stock levels of BOM %1$s changed after producing %2$s', ATUM_LOGS_TEXT_DOMAIN );
				break;
			default:
				$text = __( 'No message', ATUM_LOGS_TEXT_DOMAIN );
		}

		return apply_filters( 'atum/logs/get_entry_text', $text, $slug, $save );
	}

	/**
	 * Gets the params list for an entry type
	 *
	 * @since 0.5.1
	 *
	 * @param string $slug
	 * @param bool   $save
	 * @return array
	 */
	public static function get_params( $slug, $save = FALSE ) {

		$params = [];

		switch ( $slug ) {
			case self::ACTION_SC_EDIT:
				$params = [
					'field_name'   => 'uc_rep',
					'entity'       => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_SC_SET_LOC:
				$params = [
					'added'        => 'term_link_list',
					'entity'       => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_SC_DEL_LOC:
				$params = [
					'removed'      => 'term_link_list',
					'entity'       => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_SC_UNCONTROL_STOCK:
			case self::ACTION_SC_CONTROL_STOCK:
			case self::ACTION_SC_UNMANAGE_STOCK:
			case self::ACTION_SC_MANAGE_STOCK:
				$params = [
					'entity'   => 'content',
					'products' => 'link_list',
				];
				break;
			case self::ACTION_PO_ADD_FEE_SHIP:
			case self::ACTION_IL_ADD_FEE_SHIP:
			case self::ACTION_PO_EDIT_DATA:
			case self::ACTION_IL_EDIT_DATA:
			case self::ACTION_WC_ORDER_STATUS:
			case self::ACTION_WC_ORDER_DATA:
				$params = [
					'field'      => 'uc_rep',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_PO_CREATE:
			case self::ACTION_PO_EDIT_STATUS:
			case self::ACTION_PO_EDIT_TOTALS:
			case self::ACTION_PO_GENERATE_PDF:
			case self::ACTION_PO_ADD_ITEM:
			case self::ACTION_PO_ADD_TAX:
			case self::ACTION_PO_ADD_META:
			case self::ACTION_PO_ADD_NOTE:
			case self::ACTION_PO_DEL_NOTE:
			case self::ACTION_IL_CREATE:
			case self::ACTION_IL_EDIT_STATUS:
			case self::ACTION_IL_EDIT_TOTALS:
			case self::ACTION_IL_ADD_ITEM:
			case self::ACTION_IL_ADD_TAX:
			case self::ACTION_IL_ADD_META:
			case self::ACTION_IL_ADD_NOTE:
			case self::ACTION_IL_DEL_NOTE:
			case self::ACTION_WC_ORDER_CREATE:
			case self::ACTION_WC_ORDER_CREATE_M:
			case self::ACTION_WC_ORDER_TOTALS:
			case self::ACTION_WC_ORDER_ADD_FEE:
			case self::ACTION_WC_ORDER_ADD_TAX:
			case self::ACTION_WC_ORDER_ADD_SHIP:
			case self::ACTION_WC_ORDER_ADD_NOTE:
			case self::ACTION_WC_ORDER_ADD_REFUND:
			case self::ACTION_PO_EMAILED:
			case self::ACTION_PO_FILE_ADDED:
				$params = [
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_PO_FILE_DEL:
				$params = [
					'file'       => 'content',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_PO_DELIVERY_ADD:
			case self::ACTION_PO_DELIVERY_DEL:
			case self::ACTION_PO_DELIVERY_FILE_ADD:
				$params = [
					'delivery_name' => 'content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PO_DELIVERY_FILE_DEL:
				$params = [
					'file'          => 'content',
					'delivery_name' => 'content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PO_INVOICE_ADD:
			case self::ACTION_PO_INVOICE_DEL:
				$params = [
					'invoice_name' => 'content',
					'order_id'     => 'link',
					'order_name'   => 'content',
				];
				break;
			case self::ACTION_PO_DELIVERY_ITEM_DEL:
				$params = [
					'item_name'     => 'content',
					'delivery_name' => 'content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PO_DELIVERY_EDIT:
			case self::ACTION_PO_DELIVERY_STOCK:
				$params = [
					'product_id'    => 'link',
					'product_name'  => 'content',
					'delivery_name' => 'content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PO_DELIVERY_INV_EDIT:
				$params = [
					'inventory_name' => 'content',
					'delivery_name'  => 'content',
					'order_id'       => 'link',
					'order_name'     => 'content',
				];
				break;
			case self::ACTION_PO_INVOICE_EDIT:
			case self::ACTION_PO_INVOICE_TAX_EDIT:
			case self::ACTION_PO_INVOICE_FEE_EDIT:
			case self::ACTION_PO_INVOICE_SHIP_EDIT:
				$params = [
					'product_id'   => 'link',
					'product_name' => 'content',
					'invoice_name' => 'content',
					'order_id'     => 'link',
					'order_name'   => 'content',
				];
				break;
			case self::ACTION_PO_INVOICE_ITEM_DEL:
				$params = [
					'item_name'    => 'content',
					'invoice_name' => 'content',
					'order_id'     => 'link',
					'order_name'   => 'content',
				];
				break;
			case self::ACTION_PO_MERGE:
			case self::ACTION_PO_CLONE:
				$params = [
					'order_id'    => 'link',
					'order_name'  => 'content',
					'source_id'   => 'link',
					'source_name' => 'content',
				];
				break;
			case self::ACTION_PO_APPROVAL:
				$params = [
					'order_id'   => 'link',
					'order_name' => 'content',
					'user_name'  => 'content',
				];
				break;
			case self::ACTION_WC_ORDER_ITEM_DELETE:
				$params = [
					'list'       => 'list',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_PO_ITEM_CHANGED:
			case self::ACTION_IL_ITEM_CHANGED:
			case self::ACTION_WC_ORDER_ITEM_EDIT:
				$params = [
					'field'      => 'uc_rep',
					'item_name'  => 'content',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_PO_ITEM_META:
			case self::ACTION_IL_ITEM_META:
			case self::ACTION_PO_DEL_ITEM_META:
			case self::ACTION_IL_DEL_ITEM_META:
			case self::ACTION_WC_ORDER_ITEM_META:
			case self::ACTION_WC_ORDER_DEL_ITEM_META:
			case self::ACTION_PO_DEL_ORDER_ITEM:
			case self::ACTION_IL_DEL_ORDER_ITEM:
				$params = [
					'item_name'  => 'content',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_MI_INV_OUT_STOCK:
			case self::ACTION_MI_INV_DEPLETED:
			case self::ACTION_MI_INV_USE_NEXT:
			case self::ACTION_MI_INVENTORY_CREATE:
			case self::ACTION_MI_INVENTORY_DELETE:
				$params = [
					'inventory'    => 'name_content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_MI_ORDERITEM_INV_ADD:
			case self::ACTION_MI_ORDERITEM_INV_DEL:
			case self::ACTION_MI_ORDERITEM_INV_PO_DEL:
			case self::ACTION_MI_ORDERITEM_INV_IL_DEL:
			case self::ACTION_MI_PO_ITEM_QTY:
			case self::ACTION_MI_PO_ITEM_INV_ADD:
				$params = [
					'inventory'  => 'name_content',
					'order_item' => 'name_content',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
			case self::ACTION_MI_PO_ITEM_STLEVEL:
				$params = [
					'inventory'    => 'name_content',
					'product_id'   => 'link',
					'product_name' => 'name_content',
					'order_id'     => 'link',
					'order_name'   => 'content',
				];
				break;
			case self::ACTION_MI_INVENTORIES_USED:
			case self::ACTION_MI_INVENTORIES_EDIT:
			case self::ACTION_MI_PO_INV_USED:
			case self::ACTION_MI_PO_INV_EDIT:
				$params = [
					'inventories' => 'name_list',
					'order_item'  => 'name_content',
					'order_id'    => 'link',
					'order_name'  => 'content',
				];
				break;
			case self::ACTION_MI_ORDER_ITEM_QTY:
				$params = [
					'qty_used'   => 'content',
					'inventory'  => 'name_content',
					'order_id'   => 'link',
					'order_name' => 'content',
				];
				break;
			case self::ACTION_MI_MULTIPRICE_SOLD:
				$params = [
					'qty'       => 'content',
					'inventory' => 'name_content',
					'price'     => 'content',
				];
				break;
			case self::ACTION_MI_EDIT_INVENTORY:
				$params = [
					'field'        => 'uc_rep',
					'inventory'    => 'name_content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_MI_INV_REG_RESTRICT:
				$params = [
					'order_id'     => 'link',
					'order_name'   => 'content',
					'inventory_id' => 'name_content',
				];
				break;
			case self::ACTION_MI_MARK_WRITE_OFF:
			case self::ACTION_MI_UNMARK_WRITE_OFF:
				$params = [
					'inventories'  => 'name_list',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_PO_PURCHASE_PRICE:
			case self::ACTION_PO_STOCK_LEVELS:
			case self::ACTION_IL_INCREASE_STOCK:
			case self::ACTION_IL_DECREASE_STOCK:
			case self::ACTION_WC_ORDER_STOCK_LVL:
			case self::ACTION_WC_ORDER_CH_STOCK_LVL:
			case self::ACTION_WC_ORDER_ADD_PRODUCT:
				$params = [
					'product_id'   => 'link',
					'product_name' => 'content',
					'order_id'     => 'link',
					'order_name'   => 'content',
				];
				break;
			case self::ACTION_PO_DEL:
			case self::ACTION_IL_DEL:
			case self::ACTION_SUPPLIER_DEL:
			case self::ACTION_WC_PRODUCT_DEL:
			case self::ACTION_WC_ORDER_DEL:
			case self::ACTION_WC_COUPON_DEL:
			case self::ACTION_ADDON_ACTIVATE:
			case self::ACTION_ADDON_DEACTIVATE:
			case self::ACTION_LOC_DEL:
			case self::ACTION_WC_CATEGORY_REMOVE:
			case self::ACTION_WC_TAG_REMOVE:
			case self::ACTION_WC_ATTR_DELETE:
			case self::ACTION_WC_SETTINGS:
				$params = [ 'name' => 'content' ];
				break;
			case self::ACTION_PO_TRASH:
			case self::ACTION_IL_TRASH:
			case self::ACTION_SUPPLIER_TRASH:
			case self::ACTION_WC_PRODUCT_TRASH:
			case self::ACTION_WC_ORDER_TRASH:
			case self::ACTION_WC_COUPON_TRASH:
			case self::ACTION_PO_UNTRASH:
			case self::ACTION_IL_UNTRASH:
			case self::ACTION_SUPPLIER_UNTRASH:
			case self::ACTION_WC_PRODUCT_UNTRASH:
			case self::ACTION_WC_ORDER_UNTRASH:
			case self::ACTION_WC_COUPON_UNTRASH:
			case self::ACTION_SUPPLIER_NEW:
			case self::ACTION_SUPPLIER_STATUS:
			case self::ACTION_WC_PRODUCT_CREATE:
			case self::ACTION_PD_MANAGE_STOCK:
			case self::ACTION_PD_ENABLE_SYNC:
			case self::ACTION_PD_DISABLE_SYNC:
			case self::ACTION_WC_PRODUCT_STATUS:
			case self::ACTION_WC_PRODUCT_REVIEW:
			case self::ACTION_ATUM_MIN_THRESHOLD:
			case self::ACTION_WC_COUPON_CREATE:
			case self::ACTION_PL_STOCK_LEVEL_BOM:
				$params = [
					'id'   => 'link',
					'name' => 'content',
				];
				break;
			case self::ACTION_WC_VARIATION_LINK:
			case self::ACTION_WC_VARIATION_DELETE:
				$params = [
					'variation_name' => 'content',
					'product_id'     => 'link',
					'product_name'   => 'content',
				];
				break;
			case self::ACTION_PL_BOM_MIN_THRESHOLD:
				$params = [
					'product_id'       => 'link',
					'product_name'     => 'content',
					'bom_product_id'   => 'link',
					'bom_product_name' => 'content',
				];
				break;
			case self::ACTION_SUPPLIER_DETAILS:
			case self::ACTION_PD_EDIT:
			case self::ACTION_PD_EDIT_2:
			case self::ACTION_MI_PD_EDIT:
			case self::ACTION_WC_COUPON_EDIT:
				$params = [
					'field' => 'uc_rep',
					'id'    => 'link',
					'name'  => 'content',
				];
				break;
			case self::ACTION_SET_ENABLE_MOD:
			case self::ACTION_SET_DISABLE_MOD:
			case self::ACTION_SET_CHANGE_OPT:
				$params = [ 'field' => 'content' ];
				break;
			case self::ACTION_SET_RUN_TOOL:
			case self::ACTION_SET_RUN_TOOL_CLI:
				$params = [ 'tool' => 'content' ];
				break;
			case self::ACTION_EP_TEMPLATE_EDIT:
				$params = [
					'field'       => 'uc_rep',
					'template_id' => 'template_link',
					'name'        => 'content',
				];
				break;
			case self::ACTION_EP_CUSTOM_TEMPLATE:
			case self::ACTION_EP_TEMPLATE_FIELDS:
			case self::ACTION_EP_SCHEDULED_EXPORT:
			case self::ACTION_EP_EMAIL_SENT:
			case self::ACTION_EP_EMAIL_NOT_SENT:
			case self::ACTION_EP_FILE_STORED:
			case self::ACTION_EP_IMPORT_START:
			case self::ACTION_EP_IMPORT_SUCCESS:
			case self::ACTION_EP_IMPORT_FAIL:
			case self::ACTION_EP_IMPORT_WARNING:
			case self::ACTION_EP_TEMPLATE_DEL_SUCCESS:
			case self::ACTION_EP_TEMPLATE_DEL_FAIL:
			case self::ACTION_EP_TEMPLATE_EXPORT:
			case self::ACTION_EP_TEMPLATE_DOWNLOAD:
				$params = [
					'template_id' => 'template_link',
					'name'        => 'content',
				];
				break;
			case self::ACTION_MI_INVENTORY_EXPIRED:
				$params = [ 'inventory' => 'name_content' ];
				break;
			case self::ACTION_LOC_CREATE:
			case self::ACTION_LOC_CHANGE:
			case self::ACTION_WC_CATEGORY_CREATE:
			case self::ACTION_WC_TAG_CREATE:
			case self::ACTION_WC_CATEGORY_EDIT:
			case self::ACTION_WC_TAG_EDIT:
				$params = [
					'term_id' => 'term_link',
					'name'    => 'content',
				];
				break;
			case self::ACTION_LOC_ASSIGN:
			case self::ACTION_LOC_UNASSIGN:
			case self::ACTION_WC_CATEGORY_ADD:
			case self::ACTION_WC_CATEGORY_DEL:
			case self::ACTION_WC_TAG_ADD:
			case self::ACTION_WC_TAG_DEL:
				$params = [
					'term_id'      => 'term_link',
					'name'         => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_WC_ATTR_CREATE:
			case self::ACTION_WC_ATTR_UPDATE:
				$params = [
					'attribute_name'  => 'tax_link',
					'attribute_label' => 'content',
				];
				break;
			case self::ACTION_WC_ATTR_ADD:
				$params = [
					'new_value'       => 'content',
					'attribute_name'  => 'tax_link',
					'attribute_label' => 'content',
				];
				break;
			case self::ACTION_WC_ATTR_ASSIGN:
			case self::ACTION_WC_ATTR_UNASSIGN:
				$params = [
					'attribute_name'  => 'tax_link',
					'attribute_label' => 'content',
					'product_id'      => 'link',
					'product_name'    => 'content',
				];
				break;
			case self::ACTION_WC_ATTR_ASSIGN_VALUE:
			case self::ACTION_WC_ATTR_UNASSIGN_VALUE:
				$params = [
					'value'           => 'content',
					'attribute_name'  => 'tax_link',
					'attribute_label' => 'content',
					'product_id'      => 'link',
					'product_name'    => 'content',
				];
				break;
			case self::ACTION_PL_ORDER_ITEM_QTY:
			case self::ACTION_PL_IL_ORDER_ITEM_QTY:
			case self::ACTION_PL_PO_ORDER_ITEM_QTY:
				$params = [
					'linked_bom'    => 'content',
					'order_item_id' => 'id_content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PL_ORDER_ITEM_BOMS:
				$params = [
					'product_id'         => 'link',
					'product_name'       => 'content',
					'source_inventories' => 'name_list',
					'target_inventories' => 'name_list',
					'order_id'           => 'link',
					'order_name'         => 'content',
				];
				break;
			case self::ACTION_PL_BOM_USED:
			case self::ACTION_PL_BOM_INV_USED:
				$params = [
					'bom_list'      => 'link_list',
					'order_item_id' => 'id_content',
					'order_id'      => 'link',
					'order_name'    => 'content',
				];
				break;
			case self::ACTION_PL_LINK_RAW_MAT:
			case self::ACTION_PL_LINK_PROD_PART:
			case self::ACTION_PL_UNLINK_RAW_MAT:
			case self::ACTION_PL_UNLINK_PROD_PART:
			case self::ACTION_PL_BOM_EDIT_QTY:
			case self::ACTION_PL_PRODUCE_BOM_STOCK:
				$params = [
					'bom_id'       => 'link',
					'bom_name'     => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_WC_ORDER_ST_BULK:
				$params = [ 'order_list' => 'link_list' ];
				break;
			case self::ACTION_WC_ORDER_ADD_COUPON:
				$params = [
					'coupon_id'   => 'link',
					'coupon_code' => 'content',
					'order_id'    => 'link',
					'order_name'  => 'content',
				];
				break;
			case self::ACTION_PL_PRODUCE_STOCK:
				$params = [
					'qty'          => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
				];
				break;
			case self::ACTION_PL_PRODUCE_STOCK_INV:
				$params = [
					'qty'          => 'content',
					'product_id'   => 'link',
					'product_name' => 'content',
					'inventory'    => 'name_content',
				];
				break;

		}

		return apply_filters( 'atum/logs/get_entry_params', $params, $slug, $save );
	}

	/**
	 * Parse params from an action
	 *
	 * @since 0.5.1
	 *
	 * @param string $slug
	 * @param mixed  $data
	 * @param bool   $search
	 * @param bool   $save
	 *
	 * @return array
	 */
	public static function parse_params( $slug, $data, $search = FALSE, $save = FALSE ) {

		$params    = self::get_params( $slug );
		$result    = [];
		$open_link = FALSE;
		$data      = maybe_unserialize( $data, $save );

		foreach ( $params as $name => $format ) {

			if ( ! isset( $data[ $name ] ) ) {
				$result[ $name ] = '';
				continue;
			}

			switch ( $format ) {

				/**
				 * Add a post link. This param type needs be followed by a text param.
				 *
				 * @since 1.0.0
				 */
				case 'link':
					if ( $search ) break;
					$open_link = get_edit_post_link( $data[ $name ] );
					// Add support for linking to parents posts.
					if ( ! $open_link || FALSE === wp_http_validate_url( $open_link ) ) {
						$parent_name = str_replace( 'id', 'parent', $name, $count );
						if ( $count && ! empty( $data[ $parent_name ] ) ) {
							$open_link = get_edit_post_link( $data[ $parent_name ] );
						}
					}
					break;
				/**
				 * Add a term link. This param type needs be followed by a text param.
				 *
				 * @since 1.0.0
				 */
				case 'term_link':
					if ( $search ) break;
					$open_link = get_edit_term_link( $data[ $name ] );
					break;
				/**
				 * Add a taxonomy link. This param type needs be followed by a text param.
				 *
				 * @since 1.0.0
				 */
				case 'tax_link':
					if ( $search ) break;
					$tax_slug  = ( 'pa_' === substr( $data[ $name ], 0, 3 ) ? $data[ $name ] : 'pa_' . $data[ $name ] );
					$open_link = get_site_url() . '/wp-admin/edit-tags.php?taxonomy=' . $tax_slug . '&post_type=product';
					break;
				/**
				 * Add an Export Pro template link. This param type needs be followed by a text param.
				 *
				 * @since 1.0.0
				 */
				case 'template_link':
					if ( $search ) break;
					if ( ! empty( $data[ $name ] ) && is_numeric( $data[ $name ] ) ) {
						$open_link = get_site_url() . '/wp-admin/admin.php?page=atum-export-pro#/export-center?template_id=' . $data[ $name ];
					}
					else {
						$open_link = get_site_url() . '/wp-admin/admin.php?page=atum-export-pro#/templates';
					}
					break;
				/**
				 * Display the content of a field. If a link was previously added this content will close the link.
				 *
				 * @since 1.0.0
				 */
				case 'content':
					if ( $open_link ) {
						$result[ $name ] = Helpers::build_link( $open_link, $data[ $name ] );
						$open_link       = FALSE;
					} else {
						$result[ $name ] = "'{$data[ $name ]}'";
					}
					break;
				/**
				 * Display an #ID. If a link was previously added this id will close the link.
				 *
				 * @since 1.0.0
				 */
				case 'id_content':
					if ( $open_link ) {
						$result[ $name ] = Helpers::build_link( $open_link, '#' . $data[ $name ] );
						$open_link       = FALSE;
					} else {
						$result[ $name ] = '#' . $data[ $name ];
					}
					break;
				/**
				 * Display name from an array [ id, name ]. If name is not available, will show #id.
				 * If a link was previously added this content will close the link.
				 *
				 * @since 1.0.0
				 */
				case 'name_content':
					$name_content = is_array( $data[ $name ] ) ? ( isset( $data[ $name ]['name'] ) && $data[ $name ]['name'] ? $data[ $name ]['name'] : '#' . $data[ $name ]['id'] ) : $data[ $name ];
					if ( $open_link ) {
						$result[ $name ] = Helpers::build_link( $open_link, $name_content );
						$open_link       = FALSE;
					} else {
						$result[ $name ] = "'$name_content'";
					}
					break;
				/**
				 * Show the concatenated elements of a list.
				 *
				 * @since 1.0.0
				 */
				case 'list':
					$result[ $name ] = "'" . implode( "', '", $data[ $name ] ) . "'";
					break;
				/**
				 * Search for the 'name' of each element in an array and show the concatenated list.
				 *
				 * @since 1.0.0
				 */
				case 'name_list':
					$list = [];
					foreach ( $data[ $name ] as $item ) {
						$list[] = isset( $item['name'] ) && $item['name'] ? $item['name'] : '#' . $item['id'];
					}
					$result[ $name ] = "'" . implode( "', '", $list ) . "'";
					break;
				/**
				 * Parse an array list of elements [ id, name ] and show the Posts links list.
				 *
				 * @since 1.0.0
				 */
				case 'link_list':
					$list = [];
					foreach ( $data[ $name ] as $item ) {
						if ( is_array( $item ) && isset( $item['id'], $item['name'] ) ) {

							if ( $search ) {
								$list[] = $item['name'];
							}
							else {
								$link = get_edit_post_link( $item['id'] );
								// Add support fot linking to parents posts.
								if ( ( ! $link || FALSE === wp_http_validate_url( $link ) ) && ! empty( $item['parent'] ) ) {
									$link = get_edit_post_link( $item['parent'] );
								}

								$list[] = Helpers::build_link( $link, $item['name'] );
							}
						} else {
							$list[] = $search ? '#' . $item : Helpers::build_link( get_edit_post_link( $item ), '#' . $item );
						}
					}
					$result[ $name ] = implode( ', ', $list );
					break;
				/**
				 * Parse an array list of elements [ id, name ] and show the Terms links list.
				 *
				 * @since 1.0.0
				 */
				case 'term_link_list':
					$list = [];
					foreach ( $data[ $name ] as $item ) {
						if ( is_array( $item ) ) {
							$list[] = $search ? $item['name'] : Helpers::build_link( get_edit_term_link( $item['id'] ), $item['name'] );
						} else {
							$list[] = $search ? '#' . $item : Helpers::build_link( get_edit_term_link( $item ), '#' . $item );
						}
					}
					$result[ $name ] = implode( ', ', $list );
					break;
				/**
				 * Show the content as separate words with first capital letter and quotes.
				 *
				 * @since 1.0.0
				 */
				case 'uc_rep':
					$result[ $name ] = "'" . ucwords( trim( str_replace( '_', ' ', $data[ $name ] ) ) ) . "'";
					break;
				/**
				 * Show the content as separate words with first capital letter without quotes.
				 *
				 * @since 1.2.0
				 */
				case 'uc_rep_only':
					$result[ $name ] = ucwords( trim( str_replace( '_', ' ', $data[ $name ] ) ) );
					break;
				/**
				 * Export the data to create custom formats.
				 *
				 * @since 1.1.9
				 */
				default:
					$result[ $name ] = apply_filters( 'atum/logs/parse_params_custom_format', $data[ $name ], $format );
			}

		}

		return $result;
	}

	/**
	 * Parse the descriptive text with log vars
	 *
	 * @since 0.5.1
	 *
	 * @param string $slug
	 * @param array  $data
	 * @return string
	 */
	public static function parse_text( $slug, $data ) {

		$format = self::get_text( $slug );
		$params = self::parse_params( $slug, $data );

		return empty( $params ) ? $format : vsprintf( $format, $params );
	}

	/**
	 * Returns the list of entries
	 *
	 * @since 0.5.1
	 *
	 * @return array
	 */
	public static function get_entries() {
		return array(
			self::ACTION_SC_EDIT,
			self::ACTION_SC_SET_LOC,
			self::ACTION_SC_DEL_LOC,
			self::ACTION_SC_UNCONTROL_STOCK,
			self::ACTION_SC_CONTROL_STOCK,
			self::ACTION_SC_UNMANAGE_STOCK,
			self::ACTION_SC_MANAGE_STOCK,
			self::ACTION_SC_EXPORT,
			self::ACTION_PO_CREATE,
			self::ACTION_PO_EDIT_STATUS,
			self::ACTION_PP_EDIT_STATUS,
			self::ACTION_PO_EDIT_TOTALS,
			self::ACTION_PO_EDIT_DATA,
			self::ACTION_PO_ADD_ITEM,
			self::ACTION_PO_ADD_FEE_SHIP,
			self::ACTION_PO_ADD_TAX,
			self::ACTION_PO_ITEM_CHANGED,
			self::ACTION_PO_ITEM_META,
			self::ACTION_PO_DEL_ITEM_META,
			self::ACTION_PO_DEL_ORDER_ITEM,
			self::ACTION_PO_ADD_META,
			self::ACTION_PO_PURCHASE_PRICE,
			self::ACTION_PO_ADD_NOTE,
			self::ACTION_PO_DEL_NOTE,
			self::ACTION_PO_GENERATE_PDF,
			self::ACTION_PO_STOCK_LEVELS,
			self::ACTION_PO_DEL,
			self::ACTION_PO_TRASH,
			self::ACTION_PO_UNTRASH,
			self::ACTION_PO_EMAILED,
			self::ACTION_PO_DELIVERY_ADD,
			self::ACTION_PO_DELIVERY_DEL,
			self::ACTION_PO_DELIVERY_EDIT,
			self::ACTION_PO_DELIVERY_INV_EDIT,
			self::ACTION_PO_DELIVERY_STOCK,
			self::ACTION_PO_DELIVERY_ITEM_DEL,
			self::ACTION_PO_DELIVERY_FILE_ADD,
			self::ACTION_PO_DELIVERY_FILE_DEL,
			self::ACTION_PO_FILE_ADDED,
			self::ACTION_PO_FILE_DEL,
			self::ACTION_PO_INVOICE_ADD,
			self::ACTION_PO_INVOICE_DEL,
			self::ACTION_PO_INVOICE_EDIT,
			self::ACTION_PO_INVOICE_TAX_EDIT,
			self::ACTION_PO_INVOICE_FEE_EDIT,
			self::ACTION_PO_INVOICE_SHIP_EDIT,
			self::ACTION_PO_INVOICE_ITEM_DEL,
			self::ACTION_PO_MERGE,
			self::ACTION_PO_CLONE,
			self::ACTION_PO_APPROVAL,
			self::ACTION_IL_CREATE,
			self::ACTION_IL_EDIT_STATUS,
			self::ACTION_IL_EDIT_TOTALS,
			self::ACTION_IL_EDIT_DATA,
			self::ACTION_IL_ADD_ITEM,
			self::ACTION_IL_ADD_FEE_SHIP,
			self::ACTION_IL_ADD_TAX,
			self::ACTION_IL_ITEM_CHANGED,
			self::ACTION_IL_ITEM_META,
			self::ACTION_IL_DEL_ITEM_META,
			self::ACTION_IL_DEL_ORDER_ITEM,
			self::ACTION_IL_ADD_META,
			self::ACTION_IL_ADD_NOTE,
			self::ACTION_IL_DEL_NOTE,
			self::ACTION_IL_DEL,
			self::ACTION_IL_TRASH,
			self::ACTION_IL_UNTRASH,
			self::ACTION_IL_INCREASE_STOCK,
			self::ACTION_IL_DECREASE_STOCK,
			self::ACTION_SUPPLIER_DEL,
			self::ACTION_SUPPLIER_TRASH,
			self::ACTION_SUPPLIER_UNTRASH,
			self::ACTION_SUPPLIER_NEW,
			self::ACTION_SUPPLIER_STATUS,
			self::ACTION_SUPPLIER_DETAILS,
			self::ACTION_SET_ENABLE_MOD,
			self::ACTION_SET_DISABLE_MOD,
			self::ACTION_SET_CHANGE_OPT,
			self::ACTION_SET_RUN_TOOL,
			self::ACTION_SET_RUN_TOOL_CLI,
			self::ACTION_ADDON_ACTIVATE,
			self::ACTION_ADDON_DEACTIVATE,
			self::ACTION_LOC_CREATE,
			self::ACTION_LOC_DEL,
			self::ACTION_LOC_CHANGE,
			self::ACTION_LOC_ASSIGN,
			self::ACTION_LOC_UNASSIGN,
			self::ACTION_ATUM_MIN_THRESHOLD,
			self::ACTION_WC_PRODUCT_CREATE,
			self::ACTION_WC_PRODUCT_DEL,
			self::ACTION_WC_PRODUCT_TRASH,
			self::ACTION_WC_PRODUCT_UNTRASH,
			self::ACTION_WC_VARIATION_LINK,
			self::ACTION_WC_VARIATION_DELETE,
			self::ACTION_WC_ORDER_CREATE,
			self::ACTION_WC_ORDER_CREATE_M,
			self::ACTION_WC_ORDER_STATUS,
			self::ACTION_WC_ORDER_STOCK_LVL,
			self::ACTION_WC_ORDER_CH_STOCK_LVL,
			self::ACTION_WC_ORDER_ST_BULK,
			self::ACTION_WC_ORDER_ADD_PRODUCT,
			self::ACTION_WC_ORDER_ADD_FEE,
			self::ACTION_WC_ORDER_ADD_TAX,
			self::ACTION_WC_ORDER_ADD_SHIP,
			self::ACTION_WC_ORDER_ITEM_DELETE,
			self::ACTION_WC_ORDER_ITEM_EDIT,
			self::ACTION_WC_ORDER_ITEM_META,
			self::ACTION_WC_ORDER_DEL_ITEM_META,
			self::ACTION_WC_ORDER_ADD_NOTE,
			self::ACTION_WC_ORDER_DATA,
			self::ACTION_WC_ORDER_TOTALS,
			self::ACTION_WC_ORDER_ADD_COUPON,
			self::ACTION_WC_ORDER_ADD_REFUND,
			self::ACTION_WC_ORDER_EMAIL,
			self::ACTION_WC_ORDER_EMAIL_AUTO,
			self::ACTION_WC_ORDER_INV_EMAIL,
			self::ACTION_WC_ORDER_INV_EMAIL_A,
			self::ACTION_WC_ORDER_NEW_EMAIL,
			self::ACTION_WC_ORDER_NEW_EMAIL_A,
			self::ACTION_WC_ORDER_DEL,
			self::ACTION_WC_ORDER_TRASH,
			self::ACTION_WC_ORDER_UNTRASH,
			self::ACTION_WC_COUPON_CREATE,
			self::ACTION_WC_COUPON_EDIT,
			self::ACTION_WC_COUPON_DEL,
			self::ACTION_WC_COUPON_TRASH,
			self::ACTION_WC_COUPON_UNTRASH,
			self::ACTION_WC_CATEGORY_CREATE,
			self::ACTION_WC_CATEGORY_REMOVE,
			self::ACTION_WC_CATEGORY_EDIT,
			self::ACTION_WC_CATEGORY_ADD,
			self::ACTION_WC_CATEGORY_DEL,
			self::ACTION_WC_TAG_CREATE,
			self::ACTION_WC_TAG_REMOVE,
			self::ACTION_WC_TAG_EDIT,
			self::ACTION_WC_TAG_ADD,
			self::ACTION_WC_TAG_DEL,
			self::ACTION_WC_ATTR_CREATE,
			self::ACTION_WC_ATTR_DELETE,
			self::ACTION_WC_ATTR_UPDATE,
			self::ACTION_WC_ATTR_ADD,
			self::ACTION_WC_ATTR_ASSIGN,
			self::ACTION_WC_ATTR_UNASSIGN,
			self::ACTION_WC_ATTR_ASSIGN_VALUE,
			self::ACTION_WC_ATTR_UNASSIGN_VALUE,
			self::ACTION_WC_PRODUCT_STATUS,
			self::ACTION_WC_PRODUCT_REVIEW,
			self::ACTION_WC_SETTINGS,
			self::ACTION_MC_EXPORT,
			self::ACTION_MI_INV_OUT_STOCK,
			self::ACTION_MI_INV_DEPLETED,
			self::ACTION_MI_INV_USE_NEXT,
			self::ACTION_MI_EDIT_INVENTORY,
			self::ACTION_MI_INVENTORY_CREATE,
			self::ACTION_MI_INVENTORY_DELETE,
			self::ACTION_MI_MARK_WRITE_OFF,
			self::ACTION_MI_UNMARK_WRITE_OFF,
			self::ACTION_MI_INVENTORY_EXPIRED,
			self::ACTION_MI_INV_REG_RESTRICT,
			self::ACTION_MI_ORDER_ITEM_QTY,
			self::ACTION_MI_PO_ITEM_QTY,
			self::ACTION_MI_PO_ITEM_STLEVEL,
			self::ACTION_MI_MULTIPRICE_SOLD,
			self::ACTION_MI_INVENTORIES_USED,
			self::ACTION_MI_PO_INV_USED,
			self::ACTION_MI_INVENTORIES_EDIT,
			self::ACTION_MI_PO_INV_EDIT,
			self::ACTION_MI_ORDERITEM_INV_ADD,
			self::ACTION_MI_ORDERITEM_INV_DEL,
			self::ACTION_MI_ORDERITEM_INV_PO_DEL,
			self::ACTION_MI_ORDERITEM_INV_IL_DEL,
			self::ACTION_MI_PO_ITEM_INV_ADD,
			self::ACTION_PL_BOM_MIN_THRESHOLD,
			self::ACTION_PL_BOM_USED,
			self::ACTION_PL_LINK_RAW_MAT,
			self::ACTION_PL_LINK_PROD_PART,
			self::ACTION_PL_UNLINK_RAW_MAT,
			self::ACTION_PL_UNLINK_PROD_PART,
			self::ACTION_PL_BOM_INV_USED,
			self::ACTION_PL_BOM_EDIT_QTY,
			self::ACTION_PL_ORDER_ITEM_BOMS,
			self::ACTION_PL_ORDER_ITEM_QTY,
			self::ACTION_PL_IL_ORDER_ITEM_QTY,
			self::ACTION_PL_PO_ORDER_ITEM_QTY,
			self::ACTION_PL_STOCK_LEVEL_BOM,
			self::ACTION_PL_PRODUCE_STOCK,
			self::ACTION_PL_PRODUCE_STOCK_INV,
			self::ACTION_PL_PRODUCE_BOM_STOCK,
			self::ACTION_PD_MANAGE_STOCK,
			self::ACTION_PD_ENABLE_SYNC,
			self::ACTION_PD_DISABLE_SYNC,
			self::ACTION_PD_EDIT,
			self::ACTION_PD_EDIT_2,
			self::ACTION_MI_PD_EDIT,
			self::ACTION_EP_TEMPLATE_EXPORT,
			self::ACTION_EP_TEMPLATE_DOWNLOAD,
			self::ACTION_EP_NO_TEMPLATE_EXPORT,
			self::ACTION_EP_CUSTOM_TEMPLATE,
			self::ACTION_EP_TEMPLATE_EDIT,
			self::ACTION_EP_TEMPLATE_FIELDS,
			self::ACTION_EP_TEMPLATE_DEL_SUCCESS,
			self::ACTION_EP_TEMPLATE_DEL_FAIL,
			self::ACTION_EP_SCHEDULED_EXPORT,
			self::ACTION_EP_EMAIL_SENT,
			self::ACTION_EP_EMAIL_NOT_SENT,
			self::ACTION_EP_FILE_STORED,
			self::ACTION_EP_MAX_FILES_REACHED,
			self::ACTION_EP_IMPORT_START,
			self::ACTION_EP_IMPORT_SUCCESS,
			self::ACTION_EP_IMPORT_FAIL,
			self::ACTION_EP_IMPORT_WARNING,
			self::ACTION_ST_ORDER_ADD,
			self::ACTION_ST_ORDER_TRASH,
			self::ACTION_ST_ORDER_UNTRASH,
			self::ACTION_ST_ORDER_DEL,
			self::ACTION_ST_ORDER_RECONCILE,
			self::ACTION_ST_RECONCILE_STOCK_LVL,
			self::ACTION_ST_RECONCILE_INV_STLVL,
			self::ACTION_PP_ORDER_ADD,
			self::ACTION_PP_ORDER_TRASH,
			self::ACTION_PP_ORDER_UNTRASH,
			self::ACTION_PP_ORDER_DEL,
			self::ACTION_PP_ORDER_PRINT,
			self::ACTION_PP_PACKED_ORDER_ITEM,
			self::ACTION_PP_PACKED_ORDER,
			self::ACTION_PP_COMPLETED,
			self::ACTION_PP_EDIT_STATUS,
		);
	}

	/**
	 * Returns the maximum params quantity
	 *
	 * @since 0.5.1
	 *
	 * @return int
	 */
	public static function get_max_args() {

		$max = 0;

		foreach ( self::get_entries() as $entry ) {
			if ( count( self::get_params( $entry ) ) > $max )
				$max = count( self::get_params( $entry ) );
		}

		return $max;
	}

	/**
	 * Returns consts values
	 *
	 * @param string $ref
	 *
	 * @return mixed|false
	 */
	public static function get( $ref ) {
		return constant( 'self::' . $ref );
	}

}
