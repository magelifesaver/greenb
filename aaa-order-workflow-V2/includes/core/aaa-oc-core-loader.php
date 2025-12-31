<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/aaa-oc-core-loader.php
 * Purpose: CORE loader — options subsystem, modules loader, and always-on Board (PHP + assets).
 * Version: 1.4.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Idempotent guard */
if ( defined( 'AAA_OC_CORE_LOADER_READY' ) ) return;
define( 'AAA_OC_CORE_LOADER_READY', true );

/** Load shared util early so all modules can use it without shims */
require_once __DIR__ . '/options/helpers/class-aaa-oc-loader-util.php';

/**
 * Load order (deterministic):
 * 1) Core hooks (global Woo/Index hooks)  → then init immediately
 * 2) Options loader                       → table + wrappers + settings tabs scaffold
 * 3) Modules loader                       → auto-discovery + load enabled modules (WFCP)
 * 4) Board (always on)                    → menu/page, partials, AJAX, assets
 */
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/hooks/class-aaa-oc-core-hooks.php', false, 'core');

AAA_OC_Loader_Util::require_or_log(__DIR__ . '/options/class-aaa-oc-options-loader.php', false, 'core');
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/modules/aaa-oc-modules-loader.php', false, 'core');
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/modules/board/aaa-oc-core-board-loader.php', false, 'core');
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/modules/board/aaa-oc-core-board-assets-loader.php', false, 'core');
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/modules/board/aaa-oc-core-board-frontend.php', false, 'core');
AAA_OC_Loader_Util::require_or_log(__DIR__ . '/indexing-overrides/class-aaa-oc-indexing-governance.php', false, 'core');
