<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/hooks/aaa-oc-sniffer-widget.php
 * Purpose: If enabled, inject a Sniffer panel on the Workflow Board (resilient anchors).
 * Version: 1.1.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---- Ensure options helpers are available (path from /board/hooks → /core/options) ---- */
if ( ! function_exists( 'aaa_oc_get_option' ) ) {
	$opt_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/options/class-aaa-oc-options.php';
	if ( file_exists( $opt_file ) ) {
		require_once $opt_file;
		if ( class_exists( 'AAA_OC_Options' ) ) {
			AAA_OC_Options::init();
		}
	}
}

/* ---- Inject on board page only, honoring settings ---- */
add_action( 'admin_enqueue_scripts', function () {

	// Toggle: on/off
	if ( ! function_exists( 'aaa_oc_get_option' ) ) return;
	$enabled = (int) aaa_oc_get_option( 'sniffer_widget_on_board', 'debug', 0 );
	if ( ! $enabled ) return;

	// Scope: only the Workflow Board
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	if ( $page !== 'aaa-oc-workflow-board' ) return;

	// Mark this request as handled so other injectors can bail (prevents duplicates)
	if ( ! defined( 'AAA_OC_SNIFFER_WIDGET_READY' ) ) {
		define( 'AAA_OC_SNIFFER_WIDGET_READY', true );
	}

	// Options: fallback file list + default-open state
	$use_fallback = (int) aaa_oc_get_option( 'sniffer_include_fallback', 'debug', 0 );
	$start_open   = (int) aaa_oc_get_option( 'sniffer_widget_open', 'debug', 0 );

	// Build payload (tracker comes from AAA_OC_Loader_Util if present)
		$plugin_root = WP_CONTENT_DIR . '/plugins/aaa-order-workflow';
		$tracker = ( isset( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) && is_array( $GLOBALS['AAA_OC_LOADER_TRACKER'] ) )
			? $GLOBALS['AAA_OC_LOADER_TRACKER']
			: array( 'attempts' => array(), 'note' => 'Tracker not initialized; add _track_load() in AAA_OC_Loader_Util::require_or_log().' );

		// De-duplicate identical loader attempts (optional visual cleanup)
		if ( isset( $GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'] ) && is_array( $GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'] ) ) {
			$GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'] = array_values( array_unique( $GLOBALS['AAA_OC_LOADER_TRACKER']['attempts'], SORT_REGULAR ) );
		}

		$payload = array(
			'meta'    => array(
			'now'         => current_time( 'mysql' ),
			'plugin_root' => $plugin_root,
			'screen'      => ( function_exists( 'get_current_screen' ) && is_object( get_current_screen() ) ) ? get_current_screen()->id : '',
			'refreshed'   => false,
		),
		'tracker' => $tracker,
	);

	if ( $use_fallback ) {
		$fallback_files = array();
		foreach ( get_included_files() as $f ) {
			if ( strpos( $f, $plugin_root ) !== false ) { $fallback_files[] = $f; }
		}
		sort( $fallback_files );
		$payload['fallback_included_files'] = $fallback_files;
	}

	// Register a virtual script + localized payload
	wp_register_script( 'aaa-oc-sniffer-widget', false, array( 'jquery' ), '1.1.2', true );
	wp_localize_script( 'aaa-oc-sniffer-widget', 'AAA_OC_SNIFFER', array(
		'payload' => $payload,
		'i18n'    => array(
			'title'  => __( 'Loader Sniffer (this request)', 'aaa-oc' ),
			'copy'   => __( 'Copy', 'aaa-oc' ),
			'copied' => __( 'Copied!', 'aaa-oc' ),
		),
		'state'   => array( 'open' => (int) $start_open ),
	) );

// Inline JS (as a normal PHP string to avoid heredoc parsing issues)
$inline  = '(function($){' . "\n";
$inline .= '"use strict";' . "\n";
$inline .= 'function findAnchor(){var sel=["#aaa-oc-actions-body","#aaa-oc-toolbar","#aaa-oc-header","#aaa-oc-board","#wpbody-content","body"];for(var i=0;i<sel.length;i++){var $a=$(sel[i]);if($a.length){return $a;}}return $("body");}' . "\n";
$inline .= 'function ensurePanel(){'
         . 'if(window.__AAA_OC_SNIFFER_MOUNTED__){return true;}'
         . 'if($("#aaa-oc-sniffer-panel").length){window.__AAA_OC_SNIFFER_MOUNTED__=true;return true;}'
         . 'var $anchor=findAnchor();'
         . 'var openAttr=(AAA_OC_SNIFFER.state&&AAA_OC_SNIFFER.state.open)?" open":"";'
         . 'var html=['
           . '"<details id=\"aaa-oc-sniffer-panel\""+openAttr+" style=\"margin:8px 0 12px;background:#fff;border:1px solid #ddd;max-width:100%;position:relative;z-index:99999;\">",'
           . '"<summary style=\"cursor:pointer;padding:8px 10px;font-weight:600;display:flex;gap:8px;align-items:center;\">",'
           . '(AAA_OC_SNIFFER.i18n&&AAA_OC_SNIFFER.i18n.title?AAA_OC_SNIFFER.i18n.title:"Loader Sniffer"),'
           . '"<button type=\"button\" id=\"aaa-oc-sniff-copy-mini\" class=\"button button-small\" style=\"margin-left:auto;\">",'
           . '(AAA_OC_SNIFFER.i18n&&AAA_OC_SNIFFER.i18n.copy?AAA_OC_SNIFFER.i18n.copy:"Copy"),"</button>",'
           . '"</summary>",'
           . '"<pre id=\"aaa-oc-sniff-pre\" style=\"margin:0;padding:10px;white-space:pre-wrap;max-height:320px;overflow:auto;border-top:1px solid #eee;\"></pre>",'
           . '"</details>"].join("");'
         . 'if($anchor.is("body")){$anchor.prepend(html);}else{$anchor.prepend(html);}try{$("#aaa-oc-sniff-pre").text(JSON.stringify(AAA_OC_SNIFFER.payload||{},null,2));}catch(e){}'
         . '$("#aaa-oc-sniff-copy-mini").on("click",function(){try{navigator.clipboard.writeText($("#aaa-oc-sniff-pre").text()).then(function(){var $b=$("#aaa-oc-sniff-copy-mini"),old=$b.text();$b.text(AAA_OC_SNIFFER.i18n?AAA_OC_SNIFFER.i18n.copied:"Copied!");setTimeout(function(){$b.text(old);},1200);});}catch(e){alert("Copy failed");}});'
         . 'window.__AAA_OC_SNIFFER_MOUNTED__=true;'
         . 'return true;'
         . '}' . "\n";
$inline .= 'var tries=0;var tid=setInterval(function(){if(ensurePanel()){clearInterval(tid);}tries++;if(tries>=10){clearInterval(tid);}},300);' . "\n";
$inline .= '$(ensurePanel);$(document).on("aaa-oc:toolbar:ready",ensurePanel);' . "\n";
$inline .= '})(jQuery);';

wp_add_inline_script( 'aaa-oc-sniffer-widget', $inline, 'after' );
wp_enqueue_script( 'aaa-oc-sniffer-widget' );
});
