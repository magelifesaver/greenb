<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/hooks/class-aaa-oc-customer-card-hooks.php
 * Purpose: Board Expanded (Row C, Left) — Customer Warnings & Special Needs with inline modal (with defaults).
 * Version: 1.2.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'AAA_OC_Customer_Card_Hooks' ) ) :

final class AAA_OC_Customer_Card_Hooks {

	const DEBUG_THIS_FILE = true;

	public static function init(): void {
		add_action( 'aaa_oc_board_info_left', [ __CLASS__, 'render_info_left' ], 10, 1 );
	}

	public static function render_info_left( array $ctx ) : void {
		$oi = isset($ctx['oi']) ? $ctx['oi'] : [];
		if ( is_array($oi) ) { $oi = (object) $oi; } elseif ( ! is_object($oi) ) { $oi = (object) []; }

		$user_id = 0; foreach ( [ 'customer_id', '_customer_user', 'customer_user', 'user_id' ] as $k ) { if ( isset($oi->{$k}) && (int)$oi->{$k} > 0 ) { $user_id = (int) $oi->{$k}; break; } }
		$order_id = isset($oi->order_id) ? (int) $oi->order_id : 0;

		$opt = function_exists('aaa_oc_get_option') ? 'aaa_oc_get_option' : null;
		$scope='customer';
		$warn_g  = $opt ? (array) $opt('warn_globals',  $scope, ['bg_color'=>'#d64040']) : ['bg_color'=>'#d64040'];
		$needs_g = $opt ? (array) $opt('needs_globals', $scope, ['bg_color'=>'#1e90ff']) : ['bg_color'=>'#1e90ff'];

		$needsLib = $opt ? (array) $opt('customer_needs_values',   $scope, []) : [];
		$warnLib  = $opt ? (array) $opt('customer_warning_values', $scope, []) : [];

		/* Fallback defaults if libraries are empty so you can proceed immediately */
		if ( empty($needsLib) ) $needsLib = [
			['label'=>'Mobility Assistance'],['label'=>'Gate Code Required'],['label'=>'Language Assistance']
		];
		if ( empty($warnLib) ) $warnLib = [
			['label'=>'Request Prepayment'],['label'=>'No Cash'],['label'=>'No-show History']
		];

		$warnings_txt = isset($oi->customer_warnings_text) ? (string) $oi->customer_warnings_text : '';
		$needs_txt    = isset($oi->customer_special_needs_text) ? (string) $oi->customer_special_needs_text : '';

		if ( self::DEBUG_THIS_FILE ) {
			@error_log('[CUSTOMER][CARD] info_left uid=' . sanitize_text_field((string)$user_id) . ' hasWarn=' . ($warnings_txt!==''?'1':'0') . ' hasNeeds=' . ($needs_txt!==''?'1':'0'));
		}

		echo '<div class="aaa-oc-customer-info-left" style="display:flex;flex-direction:column;gap:8px;">';

		$warn_border = isset($warn_g['bg_color']) ? (string)$warn_g['bg_color'] : '#d64040';
		$needs_top   = isset($needs_g['bg_color']) ? (string)$needs_g['bg_color'] : '#1e90ff';

		// WARNINGS
		echo '<section class="aaa-oc-customer-warnings" style="margin:0;padding:8px;border:2px solid '.esc_attr($warn_border).';border-radius:6px;background:#fff;">';
		echo '  <h4 style="margin:0 0 6px 0;font-weight:600;color:'.esc_attr($warn_border).';display:flex;align-items:center;justify-content:space-between;"><span>Warnings</span>';
		echo $user_id>0 ? '<button type="button" class="button button-small aaa-oc-open-customer-modal" data-user="'.esc_attr($user_id).'" data-order="'.esc_attr($order_id).'" data-target="#aaa-oc-customer-modal-'.esc_attr($user_id).'">Add / Edit</button>' : '<span style="font-size:11px;color:#666;">no user linked</span>';
		echo '  </h4><div class="aaa-oc-box-body" data-box="warnings">'.( $warnings_txt!=='' ? esc_html($warnings_txt) : '<em>No warnings yet</em>' ).'</div></section>';

		// NEEDS
		echo '<section class="aaa-oc-customer-needs" style="margin:0;padding:8px;border:1px solid #cfe6ff;border-top:4px solid '.esc_attr($needs_top).';border-radius:6px;background:#fff;">';
		echo '  <h4 style="margin:0 0 6px 0;font-weight:600;color:'.esc_attr($needs_top).';display:flex;align-items:center;justify-content:space-between;"><span>Special Needs</span>';
		echo $user_id>0 ? '<button type="button" class="button button-small aaa-oc-open-customer-modal" data-user="'.esc_attr($user_id).'" data-order="'.esc_attr($order_id).'" data-target="#aaa-oc-customer-modal-'.esc_attr($user_id).'">Add / Edit</button>' : '<span style="font-size:11px;color:#666;">no user linked</span>';
		echo '  </h4><div class="aaa-oc-box-body" data-box="needs">'.( $needs_txt!=='' ? esc_html($needs_txt) : '<em>No special needs set</em>' ).'</div></section>';

		// Modal
		if ( $user_id > 0 ) {
			$nonce = wp_create_nonce( 'aaa_oc_customer_inline' );
			$mk = function(array $rows, string $name) {
				if (empty($rows)) return '<em>No options configured.</em>';
				$out='';
				foreach ($rows as $r) { $lbl = isset($r['label']) ? trim((string)$r['label']) : ''; if ($lbl==='') continue;
					$out .= '<label style="display:inline-block;margin:4px 14px 4px 0;"><input type="checkbox" name="'.esc_attr($name).'[]" value="'.esc_attr($lbl).'"> '.esc_html($lbl).'</label>';
				}
				return $out ? $out : '<em>No options configured.</em>';
			};
			$needs_list = $mk($needsLib,'needs');
			$warn_list  = $mk($warnLib,'warn_opts');

			echo '<div id="aaa-oc-customer-modal-'.esc_attr($user_id).'" class="aaa-oc-customer-modal" style="display:none;position:fixed;inset:0;z-index:100000;">'
				.'<div class="aaa-oc-customer-modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,.35);"></div>'
				.'<div class="aaa-oc-customer-modal-card" style="position:relative;max-width:680px;margin:8vh auto;background:#fff;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,.2);">'
				.'  <div class="aaa-oc-customer-modal-head" style="padding:12px 16px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">'
				.'    <strong>Warnings / Special Needs</strong>'
				.'    <button type="button" class="button-link aaa-oc-close-customer-modal" aria-label="Close" style="font-size:18px;">×</button>'
				.'  </div>'
				.'  <form class="aaa-oc-customer-form" method="post" style="padding:16px;" data-user="'.esc_attr($user_id).'" data-order="'.esc_attr($order_id).'" data-nonce="'.esc_attr($nonce).'">'
				.'    <fieldset style="margin:0 0 12px 0;"><legend style="font-weight:600;margin-bottom:6px;">Special Needs</legend>'.$needs_list.'</fieldset>'
				.'    <fieldset style="margin:0 0 12px 0;"><legend style="font-weight:600;margin-bottom:6px;">Warnings</legend>'.$warn_list
				.'      <div style="margin-top:8px;"><label>Note</label><textarea name="warn_note" rows="2" class="large-text" placeholder="Optional note"></textarea></div>'
				.'      <label style="display:block;margin-top:6px;"><input type="checkbox" name="warn_is_ban" value="yes"> Ban customer</label>'
				.'      <div style="margin-top:6px;">Ban Length: <select name="ban_length"><option value="none">None</option><option value="1_week">1 Week</option><option value="1_month">1 Month</option><option value="3_months">3 Months</option></select></div>'
				.'    </fieldset>'
				.'    <div style="text-align:right;margin-top:10px;"><button type="button" class="button button-secondary aaa-oc-close-customer-modal" style="margin-right:8px;">Cancel</button><button type="submit" class="button button-primary">Save</button></div>'
				.'  </form>'
				.'  <div class="aaa-oc-customer-modal-foot" style="padding:0 16px 16px;"></div>'
				.'</div></div>';
		}

		echo '</div>';
	}
}
AAA_OC_Customer_Card_Hooks::init();
endif;
