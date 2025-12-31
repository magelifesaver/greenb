<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/board-hooks-map.php
 * Purpose: Central registry of Board card layout hooks (single source of truth).
 * Version: 1.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('aaa_oc_board_hooks_map', function(array $map){

	/* TOP (1) */
	$map['top'] = [
		['hook' => 'aaa_oc_board_top_left',   'args' => 1, 'desc' => 'Pills + status chips. ($ctx)'],
		['hook' => 'aaa_oc_board_top_right',  'args' => 1, 'desc' => 'Header buttons (Open Order, etc.). ($ctx)'],
	];

	/* COLLAPSED (2) */
	$map['collapsed'] = [
		['hook' => 'aaa_oc_board_collapsed_left',           'args' => 2, 'desc' => 'Override left compact area. Return truthy to skip defaults. ($handled,$ctx)'],
		['hook' => 'aaa_oc_board_collapsed_pills',          'args' => 1, 'desc' => 'Append extra pills in compact area. ($ctx)'],
		['hook' => 'aaa_oc_board_collapsed_meta',           'args' => 2, 'desc' => 'Middle meta column (time, type, city). Return truthy to skip defaults. ($handled,$ctx)'],
		['hook' => 'aaa_oc_board_collapsed_summary_right',  'args' => 1, 'desc' => 'Right summary (method/driver). ($ctx)'],
		['hook' => 'aaa_oc_board_collapsed_controls_right', 'args' => 1, 'desc' => 'Expand + prev/next. ($ctx)'],
	];

	/* INFO (3) */
	$map['info'] = [
		['hook' => 'aaa_oc_board_info_left',          'args' => 1, 'desc' => 'Warnings + Special Needs. ($ctx)'],
		['hook' => 'aaa_oc_board_info_right',         'args' => 1, 'desc' => 'Delivery summary + address link. ($ctx)'],
	];

	/* TABLE (4) */
	$map['table'] = [
		['hook' => 'aaa_oc_board_products_table',        'args' => 1, 'desc' => 'Items table. ($ctx)'],
		['hook' => 'aaa_oc_board_product_cell_{colKey}', 'args' => 3, 'desc' => 'Per-cell override. ($handled,$item,$ctx)'],
	];

	/* BOTTOM LEFT (5, 5.1, 5.2) */
	$map['bottom_left'] = [
		['hook' => 'aaa_oc_board_bottom_left_before', 'args' => 1, 'desc' => 'Before left column. ($ctx)'],
		['hook' => 'aaa_oc_board_bottom_left',        'args' => 1, 'desc' => 'Print Totals (5) + Tip Info (5.1) + Account Info (5.2). ($ctx)'],
		['hook' => 'aaa_oc_board_totals_before',      'args' => 1, 'desc' => 'Inject above Totals rows. ($ctx)'],
		['hook' => 'aaa_oc_board_total_value_{key}',  'args' => 2, 'desc' => 'Filter Totals value. ($value,$ctx)'],
		['hook' => 'aaa_oc_board_totals_after',       'args' => 1, 'desc' => 'Append below Totals rows. ($ctx)'],
		['hook' => 'aaa_oc_board_account_info_extra', 'args' => 1, 'desc' => 'Append extra account lines. ($ctx)'],
		['hook' => 'aaa_oc_board_bottom_left_after',  'args' => 1, 'desc' => 'After left column. ($ctx)'],
	];

	/* BOTTOM RIGHT (6, 6.1, 6.2, 6.3) */
	$map['bottom_right'] = [
		['hook' => 'aaa_oc_board_bottom_right_before', 'args' => 1, 'desc' => 'Before right column. ($ctx)'],
		['hook' => 'aaa_oc_board_notes_render',        'args' => 1, 'desc' => 'Existing notes list. (6)'],
		['hook' => 'aaa_oc_board_notes_entry',         'args' => 1, 'desc' => 'Add Note UI. (6)'],
		['hook' => 'aaa_oc_board_driver_box',          'args' => 1, 'desc' => 'Driver select + save. (6.1)'],
		['hook' => 'aaa_oc_board_delivery_box',        'args' => 1, 'desc' => 'Date / From / To. (6.2)'],
		['hook' => 'aaa_oc_board_action_buttons',      'args' => 1, 'desc' => 'Receipt/Picklist/Add Payment/etc. (6.3)'],
		['hook' => 'aaa_oc_board_bottom_right_after',  'args' => 1, 'desc' => 'After right column. ($ctx)'],
	];

	/* SIGNALS */
	$map['signals'] = [
		['hook' => 'aaa_oc_core_tables_ready',  'args' => 0, 'desc' => 'Core base tables ready (extenders may install).'],
		['hook' => 'aaa_oc_board_card_context', 'args' => 1, 'desc' => 'Augment final $ctx before render. ($ctx)'],
	];

	return $map;
});
