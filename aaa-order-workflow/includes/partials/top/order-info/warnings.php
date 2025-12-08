<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$warnings_text = isset($warnings_text) ? $warnings_text : '';
$special_text  = isset($special_text) ? $special_text : '';
$has_warn      = !empty($warnings_text);
$has_special   = !empty($special_text);
?>
<!-- Row: Warnings & Delivery Info (Expanded) -->
<div class="expanded-only" style="display:none;">

	<div style="display:flex; gap:1rem; margin-bottom:1rem;">
	<!-- Warnings & Special -->
		<div style="flex:1; border-left:1px solid #ccc; padding:0.5rem;font-size:16px;">
			<div style="font-weight:bold; margin-bottom:0.5rem;">Warning:</div>
				<?php
				if ($has_warn) {
					echo '<div style="color:red;">' . esc_html($warnings_text) . '</div>';
				} else {
					echo '<div style="color:#666;">None</div>';
				}
				?>
				<div style="font-weight:bold; margin-top:1rem;">Special Needs:</div>
				<?php
				if ($has_special) {
					echo '<div style="color:#000;">' . esc_html($special_text) . '</div>';
				} else {
					echo '<div style="color:#666;">None</div>';
				}
				?>
		</div>

	<!-- Delivery Info (expanded) -->
		<div style="flex:1; padding:0.5rem;font-size:16px;">
			<div style="display:flex; gap:0; margin-bottom:1rem;">
				<div style="flex:1; border-left:1px solid #ccc; padding:0.5rem;font-size:16px;">
					<div><?php echo esc_html($shipping_method); ?></div>
					<div><?php echo esc_html($driver_name); ?></div>
				<?php if ($delivery_date_str): ?>
					<div>Date: <?php echo esc_html($delivery_date_str); ?></div>
				<?php endif; ?>
				<?php if ($delivery_time): ?>
					<div>Time: <?php echo esc_html($delivery_time); ?></div>
				<?php endif; ?>
				</div>
				<div style="flex:1; border-left:1px solid #ccc; padding:0.5rem;font-size:16px;">

					<div style="font-weight:bold; margin-bottom:0.5rem;">
						Delivery Address
						<?php if ( !empty($row->shipping_verified) && (int)$row->shipping_verified === 1 ): ?>
							<span title="Verified" style="color:#2e7d32; font-weight:bold;">✔</span>
						<?php else: ?>
							<span title="Not verified" style="color:#c62828; font-weight:bold;">✖</span>
						<?php endif; ?>
					</div>
					<?php
					// Build shipping address string from indexed row
					$sa1 = trim((string)($row->shipping_address_1 ?? ''));
					$sa2 = trim((string)($row->shipping_address_2 ?? ''));
					$sc  = trim((string)($row->shipping_city ?? ''));
					$ss  = trim((string)($row->shipping_state ?? ''));
					$sp  = trim((string)($row->shipping_postcode ?? ''));
					$sct = trim((string)($row->shipping_country ?? ''));

					$addr_line1 = trim($sa1 . ' ' . $sa2);
					$city_line  = trim($sc . ($ss ? ', ' . $ss : '') . ($sp ? ' ' . $sp : ''));
					$address    = trim(implode(', ', array_filter([$addr_line1, $city_line, $sct])));
					$maps_url   = $address ? 'https://maps.google.com/?q=' . rawurlencode($address) : '';

					if ($address) {
						echo '<div style="margin-top:0.5rem;">'
							. '<a href="' . esc_url($maps_url) . '" target="_blank" rel="noopener">'
							. esc_html($addr_line1) . '<br>'
							. esc_html($city_line . ($sct ? ', ' . $sct : ''))
							. '</a></div>';
					} else {
						echo '<div style="color:#666;">None</div>';
					}
					?>
				</div>
			</div>
		</div>
	</div>
