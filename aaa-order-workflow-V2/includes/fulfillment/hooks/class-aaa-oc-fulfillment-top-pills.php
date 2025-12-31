<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/hooks/class-aaa-oc-fulfillment-top-pills.php
 * Purpose: Inject a short Fulfillment pill (NP/PR/FP/PK) into the board’s top-left pill row.
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AAA_OC_Fulfillment_Top_Pills' ) ) :

final class AAA_OC_Fulfillment_Top_Pills {

	public static function init(): void {
		// Correct hook for pills row
		add_action( 'aaa_oc_board_top_left', [ __CLASS__, 'render' ], 20, 1 );
	}

	public static function render( array $ctx ): void {
		$oi   = isset($ctx['oi']) ? (object) $ctx['oi'] : (object)[];
		$oid  = isset($ctx['order_id']) ? (int)$ctx['order_id'] : (int)($oi->order_id ?? 0);

		// Normalize order status (for data- attrs only)
		$order_status = self::norm( (string)($oi->status ?? $oi->order_status ?? '') );

		// 1) Prefer fulfillment_status from index snapshot
		$fs = (string) ($oi->fulfillment_status ?? '');

		// 2) Fallback to order meta (_aaa_fulfillment_status)
		if ( $fs === '' && $oid > 0 ) {
			$meta = get_post_meta( $oid, '_aaa_fulfillment_status', true );
			if ( is_string($meta) && $meta !== '' ) $fs = $meta;
		}

		// 3) Infer from picked_items if still empty
		if ( $fs === '' ) {
			$picked_json = (string) ($oi->picked_items ?? '');
			$fs = self::infer_from_picked_json( $picked_json );
		}

		$fulfillment_status = self::norm( $fs );

		// Map to short labels + classes
		$map = [
			'not_picked'   => [ 'label' => 'NP', 'title' => __( 'Not Picked', 'aaa-oc' ),     'cls' => 'is-warn'  ],
			'partial'      => [ 'label' => 'PR', 'title' => __( 'Partially Picked', 'aaa-oc' ),'cls' => 'is-attn'  ],
			'fully_picked' => [ 'label' => 'FP', 'title' => __( 'Fully Picked', 'aaa-oc' ),    'cls' => 'is-ok'    ],
			'packed'       => [ 'label' => 'PK', 'title' => __( 'Packed', 'aaa-oc' ),          'cls' => 'is-ok'    ],
		];
		$meta = $map[ $fulfillment_status ] ?? [ 'label' => '—', 'title' => ucfirst( $fulfillment_status ?: 'Fulfillment' ), 'cls' => 'is-muted' ];

		echo '<span class="aaa-oc-pill aaa-oc-pill--fulfillment ' . esc_attr($meta['cls']) . '"'
		   . ' data-order-status="' . esc_attr($order_status) . '"'
		   . ' data-fulfillment-status="' . esc_attr($fulfillment_status) . '"'
		   . ' title="' . esc_attr($meta['title']) . '">'
		   . esc_html($meta['label'])
		   . '</span>';
	}

	private static function norm( string $s ): string {
		$s = strtolower( trim( $s ) );
		return (strpos($s,'wc-') === 0) ? substr($s, 3) : $s;
	}

	private static function infer_from_picked_json( string $json ): string {
		if ( $json === '' ) return 'not_picked';
		$rows = json_decode( $json, true );
		if ( ! is_array($rows) || empty($rows) ) return 'not_picked';

		$any = false; $all = true;
		foreach ( $rows as $r ) {
			$p = (int)($r['picked'] ?? 0);
			$m = (int)($r['max'] ?? 0);
			if ( $p > 0 ) $any = true;
			if ( $m > 0 && $p < $m ) $all = false;
		}
		if ( $all && $any ) return 'fully_picked';
		if ( $any )          return 'partial';
		return 'not_picked';
	}
}

AAA_OC_Fulfillment_Top_Pills::init();

endif;
