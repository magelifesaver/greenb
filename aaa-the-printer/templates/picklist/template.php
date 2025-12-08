<?php
/**
 * Template: AAA The Printer — Picklist (DOMPDF-safe, inline logo)
 * Returns an HTML string; DOMPDF converts to PDF for PrintNode.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Top-level product_cat name helper (unchanged in spirit). */
if ( ! function_exists( 'aaa_lpm_get_top_level_category_name' ) ) {
	function aaa_lpm_get_top_level_category_name( $product_id ) {
		if ( ! $product_id ) { return ''; }
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) { return ''; }
		$cat = array_shift( $terms );
		while ( $cat && ! is_wp_error( $cat ) && (int) $cat->parent !== 0 ) {
			$cat = get_term( (int) $cat->parent, 'product_cat' );
		}
		return ( $cat && ! is_wp_error( $cat ) ) ? (string) $cat->name : '';
	}
}

/** Inline uploads image as data URI for DOMPDF reliability. */
if ( ! function_exists( 'aaa_lpm_inline_uploads_image' ) ) {
	function aaa_lpm_inline_uploads_image( $url, $fallback_mime = 'image/png' ) {
		$u = wp_upload_dir();
		if ( empty( $u['basedir'] ) || empty( $u['baseurl'] ) ) { return esc_url( $url ); }
		$baseurl = rtrim( $u['baseurl'], '/' );
		$basedir = rtrim( $u['basedir'], DIRECTORY_SEPARATOR );
		if ( strpos( $url, $baseurl ) !== 0 ) { return esc_url( $url ); }
		$rel  = ltrim( substr( $url, strlen( $baseurl ) ), '/' );
		$path = $basedir . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
		if ( ! is_readable( $path ) ) { return esc_url( $url ); }
		$mime = wp_check_filetype( $path )['type'] ?: $fallback_mime;
		$bin  = @file_get_contents( $path );
		if ( $bin === false ) { return esc_url( $url ); }
		return 'data:' . esc_attr( $mime ) . ';base64,' . base64_encode( $bin );
	}
}

/** MAIN: Build the picklist HTML. */
if ( ! function_exists( 'aaa_lpm_get_picklist_html' ) ) {
function aaa_lpm_get_picklist_html( $order ) {

	// ----- Order-level meta -----
	$date_obj        = $order->get_date_created();
	$order_date      = $date_obj ? $date_obj->format( 'F j, Y' ) : '';
	$order_time_12hr = $date_obj ? $date_obj->format( 'g:i A' ) : '';
	$customer_name   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$order_number    = $order->get_order_number();
	$shipping_city   = $order->get_shipping_city() ?: '';
	$source          = 'Online Order';
	$daily_order_num = $order->get_meta( '_daily_order_number', true ) ?: $order_number;

	$total_items  = (int) $order->get_item_count();
	$unique_items = count( $order->get_items() );

	// ----- Logo (inline for PDF) + Order QR -----
	$site_id = get_current_blog_id();
	if ( $site_id == 9 ) {
		$logo_url = 'https://lokeydelivery.com/wp-content/uploads/sites/9/2024/10/Label_Logo-e1729637401233.png';
	} elseif ( $site_id == 13 ) {
		$logo_url = 'https://35setup.lokey.delivery/wp-content/uploads/sites/13/2025/02/35CAP-logo.png';
	} else {
		$logo_url = 'https://example.com/path/to/default-logo.png';
	}
	$logo_src   = aaa_lpm_inline_uploads_image( $logo_url ); // data URI or https fallback
	$order_qr   = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode( $order_number );
	$payment_qr = 'https://api.qrserver.com/v1/create-qr-code/?size=70x70&data=' . urlencode( $order_number . '|payment' );

	// ----- Output -----
	ob_start(); ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Picklist</title></head>
<body style="margin:0;padding:0;font-family:Arial, sans-serif;width:80mm;">

	<!-- Logo & Order QR -->
	<table style="width:100%;margin-bottom:5px;">
		<tr>
			<td style="text-align:left;">
				<img src="<?php echo esc_attr( $logo_src ); ?>" alt="Logo" style="width:50px;height:auto;">
			</td>
			<td style="text-align:right;">
				<img src="<?php echo esc_url( $order_qr ); ?>" alt="Order QR" style="width:70px;height:auto;">
			</td>
		</tr>
	</table>

	<div style="text-align:center;font-size:12px;font-weight:bold;">Inventory Pick List</div>
	<hr style="border:0;border-top:1px solid #000;margin:5px 0;">

	<!-- Top info -->
	<div style="text-align:left;margin-left:10px;font-size:10px;">
		<div>Source: <?php echo esc_html( $source ); ?></div>
		<div>Customer: <?php echo esc_html( $customer_name ); ?></div>
		<div>Order #: <?php echo esc_html( $order_number ); ?></div>
		<div>Date: <?php echo esc_html( $order_date ); ?></div>
		<div>Time: <?php echo esc_html( $order_time_12hr ); ?></div>
		<div>City: <?php echo esc_html( $shipping_city ); ?></div>
	</div>

	<?php foreach ( $order->get_items() as $item ): ?>
		<?php
		$product      = $item->get_product();
		$product_name = $item->get_name();
		$qty          = (int) $item->get_quantity();

		$brand_name = '';
		$sku        = '';
		$prod_id    = 0;

		if ( $product ) {
			$sku     = (string) $product->get_sku();
			$prod_id = (int) $product->get_id();
			$terms   = get_the_terms( $prod_id, 'berocket_brand' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$brand_name = implode( ', ', wp_list_pluck( $terms, 'name' ) );
			}
		}

		$parent_cat_name = aaa_lpm_get_top_level_category_name( $prod_id );

		// ATUM hierarchical location (first term full path)
		$location_path  = '';
		$location_terms = $prod_id ? get_the_terms( $prod_id, 'atum_location' ) : false;
		if ( $location_terms && ! is_wp_error( $location_terms ) ) {
			foreach ( $location_terms as $term ) {
				$full = get_term_parents_list( $term->term_id, 'atum_location', [ 'separator' => ' > ', 'inclusive' => true ] );
				if ( $full ) { $location_path = wp_strip_all_tags( $full ); break; }
			}
		}
		?>
		<hr style="border:0;border-top:2px dashed #000;margin:10px 0;">
		<div style="margin-left:10px;margin-bottom:5px;font-size:10px;">
			<div style="font-weight:bold;"><?php echo esc_html( $brand_name ); ?></div>
			<div style="font-weight:bold;color:#555;">Category: <?php echo esc_html( $parent_cat_name ); ?></div>
			<div>ID: <?php echo esc_html( $prod_id ); ?> | SKU: <?php echo esc_html( $sku ); ?></div>
			<div style="font-size:12px;font-weight:bold;"><?php echo esc_html( $product_name ); ?></div>
			<?php if ( $location_path ) : ?>
				<div style="font-weight:bold;color:#006400;">Location: <?php echo esc_html( $location_path ); ?></div>
			<?php endif; ?>
		</div>

		<table style="width:100%;border-collapse:collapse;margin-top:10px;">
			<tr>
				<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;">
					<span style="font-size:16px;font-weight:bold;"><?php echo esc_html( $qty ); ?></span>
				</td>
				<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;"></td>
				<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;">
					<div style="font-size:10px;font-weight:bold;">Pick</div>
					<div style="font-size:16px;">_____</div>
				</td>
				<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;">
					<div style="font-size:10px;font-weight:bold;">Pack</div>
					<div style="font-size:16px;">_____</div>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>

	<hr style="border:0;border-top:7px double #000;margin:10px 0;">

	<!-- Bottom boxes -->
	<table style="width:100%;border-collapse:collapse;margin-top:10px;">
		<tr>
			<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;">
				<div style="font-size:10px;font-weight:bold;">ITEMS</div>
				<div style="font-size:16px;font-weight:bold;"><?php echo esc_html( $total_items ); ?></div>
			</td>
			<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;">
				<div style="font-size:10px;font-weight:bold;">UNIQUE</div>
				<div style="font-size:16px;font-weight:bold;"><?php echo esc_html( $unique_items ); ?></div>
			</td>
			<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;"></td>
			<td style="border:1px solid #000;width:25%;text-align:center;vertical-align:middle;height:40px;"></td>
		</tr>
	</table>

	<div style="border:1px dotted #000;padding:10px;margin:5px;font-size:8px;">
		Notes: <?php echo esc_html( $order->get_customer_note() ); ?>
	</div>

	<!-- Payment + DON + Payment QR -->
	<table style="width:100%;border-collapse:collapse;margin-top:10px;">
		<tr>
			<td style="width:50%;text-align:center;vertical-align:top;">
				<div style="font-size:10px;">Payment: <?php echo esc_html( $order->get_payment_method_title() ); ?></div>
				<div style="padding:10px;font-size:12px;font-weight:bold;">DON: <?php echo esc_html( $daily_order_num ); ?></div>
			</td>
			<td style="width:50%;text-align:center;vertical-align:top;">
				<img src="<?php echo esc_url( $payment_qr ); ?>" alt="Payment QR" style="width:70px;height:auto;">
			</td>
		</tr>
	</table>

</body>
</html>
<?php
	return ob_get_clean();
}}
