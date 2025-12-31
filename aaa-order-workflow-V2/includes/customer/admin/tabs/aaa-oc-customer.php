<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/admin/tabs/aaa-oc-customer.php
 * Purpose: Customer settings tab — Warnings & Special Needs libraries with per-value colors/icons + global defaults,
 *          and single Birthday icon/color set. Keeps border option keys unchanged for board-border hooks.
 * Version: 1.2.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Options helper */
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	require_once dirname( __FILE__, 4 ) . '/core/options/class-aaa-oc-options.php';
	AAA_OC_Options::init();
}
$scope = 'customer';

/** Core border keys (unchanged so hooks continue to work) */
$k_top_en    = 'customer_border_top_enabled';
$k_top_col   = 'customer_border_top_color';
$k_right_en  = 'customer_border_right_enabled';
$k_right_col = 'customer_border_right_color';
$k_bot_en    = 'customer_border_bottom_enabled';
$k_bot_col   = 'customer_border_bottom_color';

/** Birthday (single option set for icon+color) */
$k_bday_icon      = 'customer_birthday_icon';
$k_bday_icon_col  = 'customer_birthday_icon_color';
$k_bday_icon_size = 'customer_birthday_icon_size';

/** Sanitizers */
$hex = static function( $v, $def ) { $v=(string)$v; return preg_match('/^#?[0-9a-fA-F]{6}$/',$v) ? ( $v[0]==='#' ? $v : '#'.$v ) : $def; };
$num = static function( $v, $def=16,$min=10,$max=64 ){ $n=(int)$v; return max($min,min($max,$n?:$def)); };
$str = static function( $v ){ return sanitize_text_field( (string)$v ); };

/** Globals per category */
$G = [
	'warn'  => ['key'=>'warn_globals','d'=>['label_color'=>'#ffffff','bg_color'=>'#d64040','icon'=>'dashicons-warning','display'=>'icon_label','icon_color'=>'#ffffff','icon_size'=>16]],
	'needs' => ['key'=>'needs_globals','d'=>['label_color'=>'#ffffff','bg_color'=>'#1e90ff','icon'=>'dashicons-universal-access','display'=>'icon_label','icon_color'=>'#ffffff','icon_size'=>16]],
];

/** Library defaults (10 each) */
$defaults_warn = [
	['label'=>'Do Not Serve'],['label'=>'ID Expired'],['label'=>'Previous Chargeback'],['label'=>'Address Risk'],
	['label'=>'High Fraud Risk'],['label'=>'Must Verify ID'],['label'=>'Do Not Leave at Door'],['label'=>'Cash Only'],
	['label'=>'Manager Approval'],['label'=>'Account Flagged'],
];
$defaults_needs = [
	['label'=>'Mobility Assistance'],['label'=>'Hearing Impaired'],['label'=>'Vision Impaired'],['label'=>'No Stairs'],
	['label'=>'Gate Code Required'],['label'=>'Service Animal'],['label'=>'Contactless Drop-off'],['label'=>'Call on Arrival'],
	['label'=>'Large Print Needed'],['label'=>'Language Assistance'],
];

/** Load saved or defaults */
$warn_g   = (array) aaa_oc_get_option( $G['warn']['key'],  $scope, $G['warn']['d'] );
$needs_g  = (array) aaa_oc_get_option( $G['needs']['key'], $scope, $G['needs']['d'] );
$warn_vals= (array) aaa_oc_get_option( 'customer_warning_values', $scope, $defaults_warn );
$need_vals= (array) aaa_oc_get_option( 'customer_needs_values',   $scope, $defaults_needs );

/** Borders (so hooks keep working) */
$border = [
	$k_top_en  => (string) aaa_oc_get_option( $k_top_en,  $scope, 'yes' ),
	$k_top_col => (string) aaa_oc_get_option( $k_top_col, $scope, '#0073aa' ),
	$k_right_en=> (string) aaa_oc_get_option( $k_right_en,$scope, 'yes' ),
	$k_right_col= (string) aaa_oc_get_option( $k_right_col,$scope,'#cc0000' ),
	$k_bot_en  => (string) aaa_oc_get_option( $k_bot_en,  $scope, 'yes' ),
	$k_bot_col => (string) aaa_oc_get_option( $k_bot_col, $scope, '#ff00aa' ),
];

/** Birthday icon set */
$bday = [
	$k_bday_icon      => (string) aaa_oc_get_option( $k_bday_icon,      $scope, 'dashicons-buddicons-activity' ),
	$k_bday_icon_col  => (string) aaa_oc_get_option( $k_bday_icon_col,  $scope, '#ff00aa' ),
	$k_bday_icon_size => (int)    aaa_oc_get_option( $k_bday_icon_size, $scope, 16 ),
];

/** Save */
if ( isset($_POST['save_customer_tab']) && check_admin_referer('aaa_oc_customer_save') && current_user_can('manage_options') ) {
	// Borders
	$border[$k_top_en]   = ! empty($_POST[$k_top_en])   ? 'yes' : 'no';
	$border[$k_right_en] = ! empty($_POST[$k_right_en]) ? 'yes' : 'no';
	$border[$k_bot_en]   = ! empty($_POST[$k_bot_en])   ? 'yes' : 'no';
	$border[$k_top_col]   = $hex($_POST[$k_top_col]   ?? $border[$k_top_col],   $border[$k_top_col]);
	$border[$k_right_col] = $hex($_POST[$k_right_col] ?? $border[$k_right_col], $border[$k_right_col]);
	$border[$k_bot_col]   = $hex($_POST[$k_bot_col]   ?? $border[$k_bot_col],   $border[$k_bot_col]);

	// Globals
	foreach (['warn','needs'] as $cat) {
		$in = isset($_POST[$cat.'_globals']) ? (array) $_POST[$cat.'_globals'] : [];
		$ref =& ${$cat.'_g'};
		$ref['label_color'] = $hex($in['label_color'] ?? $ref['label_color'], $ref['label_color']);
		$ref['bg_color']    = $hex($in['bg_color']    ?? $ref['bg_color'],    $ref['bg_color']);
		$ref['icon']        = $str($in['icon']        ?? $ref['icon']);
		$d = in_array(($in['display'] ?? $ref['display']), ['icon','label','icon_label'], true) ? $in['display'] : 'icon_label';
		$ref['display']     = $d;
		$ref['icon_color']  = $hex($in['icon_color']  ?? $ref['icon_color'],  $ref['icon_color']);
		$ref['icon_size']   = $num($in['icon_size']   ?? $ref['icon_size'],   $ref['icon_size']);
	}

	// Libraries
	$collect = function($prefix, $g) use($hex,$num,$str){
		$L = $_POST["{$prefix}_label"] ?? [];
		$lc= $_POST["{$prefix}_label_color"] ?? [];
		$bg= $_POST["{$prefix}_bg_color"] ?? [];
		$ic= $_POST["{$prefix}_icon"] ?? [];
		$dp= $_POST["{$prefix}_display"] ?? [];
		$co= $_POST["{$prefix}_icon_color"] ?? [];
		$sz= $_POST["{$prefix}_icon_size"] ?? [];
		$out=[]; $m=max(count($L),count($lc),count($bg),count($ic),count($dp),count($co),count($sz));
		for($i=0;$i<$m;$i++){
			$label=$str($L[$i]??''); if($label==='') continue;
			$disp = in_array(($dp[$i]??$g['display']),['icon','label','icon_label'],true)?($dp[$i]??$g['display']):'icon_label';
			$out[]=[
				'label'=>$label,
				'label_color'=>$hex($lc[$i]??$g['label_color'],$g['label_color']),
				'bg_color'=>$hex($bg[$i]??$g['bg_color'],$g['bg_color']),
				'icon'=>$str($ic[$i]??$g['icon']),
				'display'=>$disp,
				'icon_color'=>$hex($co[$i]??$g['icon_color'],$g['icon_color']),
				'icon_size'=>$num($sz[$i]??$g['icon_size'],$g['icon_size']),
			];
		}
		return $out;
	};
	$warn_vals = $collect('warn',  $warn_g);
	$need_vals = $collect('needs', $needs_g);

	// Birthday single set
	$bday[$k_bday_icon]      = $str($_POST[$k_bday_icon]     ?? $bday[$k_bday_icon]);
	$bday[$k_bday_icon_col]  = $hex($_POST[$k_bday_icon_col] ?? $bday[$k_bday_icon_col], $bday[$k_bday_icon_col]);
	$bday[$k_bday_icon_size] = $num($_POST[$k_bday_icon_size]?? $bday[$k_bday_icon_size],$bday[$k_bday_icon_size]);

	// Persist
	foreach ($border as $k=>$v) aaa_oc_set_option($k,$v,$scope);
	aaa_oc_set_option($G['warn']['key'],  $warn_g,  $scope);
	aaa_oc_set_option($G['needs']['key'], $needs_g, $scope);
	aaa_oc_set_option('customer_warning_values',$warn_vals,$scope);
	aaa_oc_set_option('customer_needs_values',  $need_vals,$scope);
	aaa_oc_set_option($k_bday_icon,      $bday[$k_bday_icon],      $scope);
	aaa_oc_set_option($k_bday_icon_col,  $bday[$k_bday_icon_col],  $scope);
	aaa_oc_set_option($k_bday_icon_size, $bday[$k_bday_icon_size], $scope);

	echo '<div class="notice notice-success"><p>Customer settings saved.</p></div>';
}
?>
<div class="wrap">
	<h2><?php esc_html_e('Customer Settings','aaa-oc'); ?></h2>
	<form method="post">
		<?php wp_nonce_field('aaa_oc_customer_save'); ?>

		<h3>Warnings — Global Defaults</h3>
		<p>
			Label <input type="color" name="warn_globals[label_color]" value="<?php echo esc_attr($warn_g['label_color']); ?>">
			BG <input type="color" name="warn_globals[bg_color]" value="<?php echo esc_attr($warn_g['bg_color']); ?>">
			Icon <input type="text" name="warn_globals[icon]" value="<?php echo esc_attr($warn_g['icon']); ?>" class="regular-text">
			Display <select name="warn_globals[display]">
				<option value="icon" <?php selected($warn_g['display'],'icon'); ?>>Icon</option>
				<option value="label" <?php selected($warn_g['display'],'label'); ?>>Label</option>
				<option value="icon_label" <?php selected($warn_g['display'],'icon_label'); ?>>Icon + Label</option>
			</select>
			Icon Color <input type="color" name="warn_globals[icon_color]" value="<?php echo esc_attr($warn_g['icon_color']); ?>">
			Size <input type="number" min="10" max="64" name="warn_globals[icon_size]" value="<?php echo (int)$warn_g['icon_size']; ?>" style="width:70px">
		</p>

		<h3>Warnings — Values</h3>
		<table class="widefat striped">
			<thead><tr><th>Label</th><th>Label Color</th><th>BG</th><th>Icon</th><th>Display</th><th>Icon Color</th><th>Size</th></tr></thead>
			<tbody id="warn-rows">
				<?php foreach($warn_vals as $r): $e=function($x){return esc_attr((string)$x);} ?>
				<tr>
					<td><input type="text" name="warn_label[]" value="<?php echo $e($r['label']??''); ?>" class="regular-text"></td>
					<td><input type="color" name="warn_label_color[]" value="<?php echo $e($r['label_color']??$warn_g['label_color']); ?>"></td>
					<td><input type="color" name="warn_bg_color[]" value="<?php echo $e($r['bg_color']??$warn_g['bg_color']); ?>"></td>
					<td><input type="text" name="warn_icon[]" value="<?php echo $e($r['icon']??$warn_g['icon']); ?>" class="regular-text"></td>
					<td><select name="warn_display[]">
						<?php $d=$r['display']??$warn_g['display']; ?>
						<option value="icon" <?php selected($d,'icon'); ?>>Icon</option>
						<option value="label" <?php selected($d,'label'); ?>>Label</option>
						<option value="icon_label" <?php selected($d,'icon_label'); ?>>Icon + Label</option>
					</select></td>
					<td><input type="color" name="warn_icon_color[]" value="<?php echo $e($r['icon_color']??$warn_g['icon_color']); ?>"></td>
					<td><input type="number" min="10" max="64" name="warn_icon_size[]" value="<?php echo (int)($r['icon_size']??$warn_g['icon_size']); ?>" style="width:70px"></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" data-add="warn">Add Warning</button></p>

		<h3>Special Needs — Global Defaults</h3>
		<p>
			Label <input type="color" name="needs_globals[label_color]" value="<?php echo esc_attr($needs_g['label_color']); ?>">
			BG <input type="color" name="needs_globals[bg_color]" value="<?php echo esc_attr($needs_g['bg_color']); ?>">
			Icon <input type="text" name="needs_globals[icon]" value="<?php echo esc_attr($needs_g['icon']); ?>" class="regular-text">
			Display <select name="needs_globals[display]">
				<option value="icon" <?php selected($needs_g['display'],'icon'); ?>>Icon</option>
				<option value="label" <?php selected($needs_g['display'],'label'); ?>>Label</option>
				<option value="icon_label" <?php selected($needs_g['display'],'icon_label'); ?>>Icon + Label</option>
			</select>
			Icon Color <input type="color" name="needs_globals[icon_color]" value="<?php echo esc_attr($needs_g['icon_color']); ?>">
			Size <input type="number" min="10" max="64" name="needs_globals[icon_size]" value="<?php echo (int)$needs_g['icon_size']; ?>" style="width:70px">
		</p>

		<h3>Special Needs — Values</h3>
		<table class="widefat striped">
			<thead><tr><th>Label</th><th>Label Color</th><th>BG</th><th>Icon</th><th>Display</th><th>Icon Color</th><th>Size</th></tr></thead>
			<tbody id="needs-rows">
				<?php foreach($need_vals as $r): $e=function($x){return esc_attr((string)$x);} ?>
				<tr>
					<td><input type="text" name="needs_label[]" value="<?php echo $e($r['label']??''); ?>" class="regular-text"></td>
					<td><input type="color" name="needs_label_color[]" value="<?php echo $e($r['label_color']??$needs_g['label_color']); ?>"></td>
					<td><input type="color" name="needs_bg_color[]" value="<?php echo $e($r['bg_color']??$needs_g['bg_color']); ?>"></td>
					<td><input type="text" name="needs_icon[]" value="<?php echo $e($r['icon']??$needs_g['icon']); ?>" class="regular-text"></td>
					<td><select name="needs_display[]">
						<?php $d=$r['display']??$needs_g['display']; ?>
						<option value="icon" <?php selected($d,'icon'); ?>>Icon</option>
						<option value="label" <?php selected($d,'label'); ?>>Label</option>
						<option value="icon_label" <?php selected($d,'icon_label'); ?>>Icon + Label</option>
					</select></td>
					<td><input type="color" name="needs_icon_color[]" value="<?php echo $e($r['icon_color']??$needs_g['icon_color']); ?>"></td>
					<td><input type="number" min="10" max="64" name="needs_icon_size[]" value="<?php echo (int)($r['icon_size']??$needs_g['icon_size']); ?>" style="width:70px"></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button type="button" class="button" data-add="needs">Add Special Need</button></p>

		<h3>Birthday — Icon & Color</h3>
		<p>
			Icon <input type="text" name="<?php echo esc_attr($k_bday_icon); ?>" value="<?php echo esc_attr($bday[$k_bday_icon]); ?>" class="regular-text">
			Icon Color <input type="color" name="<?php echo esc_attr($k_bday_icon_col); ?>" value="<?php echo esc_attr($bday[$k_bday_icon_col]); ?>">
			Size <input type="number" min="10" max="64" name="<?php echo esc_attr($k_bday_icon_size); ?>" value="<?php echo (int)$bday[$k_bday_icon_size]; ?>" style="width:70px">
		</p>

		<p class="submit"><button type="submit" name="save_customer_tab" class="button button-primary">Save Settings</button></p>
	</form>
</div>
<script>
jQuery(function($){
	function newRow(prefix){
		return '<tr>'
		+'<td><input type="text" name="'+prefix+'_label[]" class="regular-text" placeholder="Label"></td>'
		+'<td><input type="color" name="'+prefix+'_label_color[]" value="#ffffff"></td>'
		+'<td><input type="color" name="'+prefix+'_bg_color[]" value="#000000"></td>'
		+'<td><input type="text" name="'+prefix+'_icon[]" class="regular-text" placeholder="dashicons-… / fa-… / svg"></td>'
		+'<td><select name="'+prefix+'_display[]"><option value="icon">Icon</option><option value="label">Label</option><option value="icon_label" selected>Icon + Label</option></select></td>'
		+'<td><input type="color" name="'+prefix+'_icon_color[]" value="#ffffff"></td>'
		+'<td><input type="number" min="10" max="64" name="'+prefix+'_icon_size[]" value="16" style="width:70px"></td>'
		+'</tr>';
	}
	$('[data-add]').on('click', function(){ var p=$(this).data('add'); $('#'+p+'-rows').append(newRow(p)); });
});
</script>
