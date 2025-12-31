<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/modules/board/views/board-card-layout-shell.php
 * Purpose: Board card layout (shell only) for the core OWB module.
 *          Uses ONLY the hooks defined in board-hooks-map.php.
 *          All default content comes from hook owners in the core board module (no module deps).
 *
 * Collapsed mode:
 *   #1  Top pills (100% width) → aaa_oc_board_top_left
 *   #2  Main row LEFT  (via aaa_oc_board_collapsed_left [+ optional collapsed_meta])
 *   #2.1 Main row RIGHT: Summary (shipping/driver/city) → aaa_oc_board_collapsed_summary_right
 *                        Expand button (here)
 *                        Prev/Next icons → aaa_oc_board_collapsed_controls_right
 *
 * Expanded mode:
 *   Row A: TOP (two columns)
 *          L: aaa_oc_board_top_left (pills)
 *          R: aaa_oc_board_top_right (Open Order + Prev/Next; no “View”)
 *   Row B: Collapsed main row (same as collapsed #2/#2.1), visible BEFORE the products table
 *   Row C: INFO (warnings / delivery details) → aaa_oc_board_info_left / aaa_oc_board_info_right
 *   Row D: PRODUCTS TABLE → aaa_oc_board_products_table
 *   Row E: BOTTOM (totals + notes/driver/delivery/actions)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$order_id = isset($order_id) ? (int) $order_id : 0;
$ctx      = (isset($ctx) && is_array($ctx)) ? $ctx : [];
$ctx['order_id'] = $order_id;
?>
<script type="application/json" class="aaa-oc-card-ctx" data-order-id="<?php echo (int) $order_id; ?>">
<?php echo wp_json_encode( $ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>
</script>

<style>
  /* structural helpers for dev; no new class names beyond existing .aaa-oc-* */
  .aaa-oc-order-card .aaa-oc-shell-row { display:flex; gap:10px; margin:8px 10px; }
  .aaa-oc-order-card .aaa-oc-shell-cell{flex:1 1 50%;border:1px dashed rgba(0,0,0,.12);background:rgba(0,0,0,.01);border-radius:6px;min-height:60px;padding:8px;min-width:0;overflow:hidden}
  .aaa-oc-order-card.collapsed .collapsed-only { display:block; }
  .aaa-oc-order-card.collapsed .expanded-only  { display:none;  }
  .aaa-oc-order-card.expanded .collapsed-only  { display:block; }  /* show collapsed strip inside expanded, per spec */
  .aaa-oc-order-card.expanded .expanded-only   { display:block;  }
</style>

<div class="aaa-oc-order-card collapsed"
     <?php do_action('aaa_oc_board_card_borders', $ctx); ?>
     data-expanded="false"
     data-order-id="<?php echo esc_attr($order_id); ?>"
     data-order-status="<?php
         // prefer order_index values; normalize: lower, strip wc-
         $st = (string)($ctx['oi']->order_status ?? $ctx['oi']->status ?? '');
         $st = strtolower($st);
         if (strpos($st,'wc-')===0) $st = substr($st,3);
         echo esc_attr($st);
     ?>"
     data-fulfillment-status="<?php
         $fs = (string)($ctx['oi']->fulfillment_status ?? 'not_picked');
         echo esc_attr(strtolower($fs));
     ?>">

  <!-- ===== Collapsed mode: Row 1 (Section #1: TOP PILLS full-width) ===== -->
  <div class="collapsed-only" style="padding:0;">
    <div class="aaa-oc-shell-row" data-section="collapsed-top">
      <div class="aaa-oc-shell-cell" style="flex:1 1 100%;">
        <?php /* Section #1 — same pill bar as expanded top-left */ ?>
	<?php do_action('aaa_oc_board_top_left', $ctx); ?>
      </div>
    </div>

    <!-- Collapsed main row: two columns (Section #2 and #2.1) -->
    <div class="aaa-oc-shell-row" data-section="collapsed-main">
      <!-- Section #2: LEFT — order number, name, amount, time, today badge, etc. -->
      <div class="aaa-oc-shell-cell" data-area="collapsed_left">
        <?php
          // NEW: prepend hook for modules (same as collapsed)
          do_action('aaa_oc_board_row2_col1_before_core', $ctx);

          // Core left renderer “claims” the slot
          $handled = apply_filters('aaa_oc_board_collapsed_left', false, $ctx);

          if ( ! $handled ) {
            apply_filters('aaa_oc_board_collapsed_meta', false, $ctx);
          }

          // NEW: append hook for modules (Delivery, etc.)
          do_action('aaa_oc_board_row2_col1_after_core', $ctx);

          // Legacy/fallback after-left
          do_action('aaa_oc_board_collapsed_left_after', $ctx);
        ?>
      </div>

      <!-- Section #2.1: RIGHT — shipping method, driver (blue), city, then Expand & Prev/Next (stacked) -->
      <div class="aaa-oc-shell-cell" data-area="collapsed_right" style="text-align:right; display:flex; flex-direction:column; gap:.35rem; align-items:flex-end;">
        <?php
          // shipping method / driver / city (your default hook owner prints these from index-only data)
          do_action('aaa_oc_board_collapsed_summary_right', $ctx);
        ?>
        <div>
          <button class="button-modern aaa-oc-view-edit" data-order-id="<?php echo esc_attr($order_id); ?>">Expand</button>
        </div>
	<div class="aaa-oc-status-icons" style="display:inline-flex; gap:6px; align-items:center;">
	  <?php do_action('aaa_oc_board_collapsed_controls_right', $ctx); ?>
	</div>
      </div>
    </div>
  </div><!-- /.collapsed-only -->

  <!-- ===== Expanded area ===== -->
  <div class="expanded-only" style="display:none;">

    <!-- Row A: TOP — two columns (keep live layout) -->
    <div class="aaa-oc-shell-row" data-section="top">
      <div class="aaa-oc-shell-cell" data-area="top_left"  style="flex:1 1 70%;">
	<?php do_action('aaa_oc_board_top_left', $ctx); ?>
      </div>
      <div class="aaa-oc-shell-cell" data-area="top_right" style="flex:1 1 30%; text-align:right;">
        <?php do_action('aaa_oc_board_top_right', $ctx); /* Open Order + Prev/Next (no View) */ ?>
      </div>
    </div>

    <!-- Row B: Collapsed main row ALSO visible in expanded, before products -->
    <div class="aaa-oc-shell-row" data-section="collapsed-main-expanded">
      <div class="aaa-oc-shell-cell" data-area="collapsed_left">
        <?php
          $handled = apply_filters('aaa_oc_board_collapsed_left', false, $ctx);
          if ( ! $handled ) {
            apply_filters('aaa_oc_board_collapsed_meta', false, $ctx);
          }
        ?>
      </div>
      <div class="aaa-oc-shell-cell" data-area="collapsed_right" style="text-align:right; display:flex; flex-direction:column; gap:.35rem; align-items:flex-end;">
        <?php do_action('aaa_oc_board_collapsed_summary_right', $ctx); ?>
        <?php /* No Expand here in expanded mode */ ?>
        <div class="aaa-oc-status-icons" style="display:inline-flex; gap:6px; align-items:center;">
          <?php do_action('aaa_oc_board_collapsed_controls_right', $ctx); ?>
        </div>
      </div>
    </div>

    <!-- Row C: INFO -->
    <div class="aaa-oc-shirt-info aaa-oc-shell-row" data-section="info">
      <div class="aaa-oc-shirt-info-l aaa-rosa aaa-oc-shell-cell" data-area="info_left">
        <?php do_action('aaa_oc_board_info_left',  $ctx); ?>
      </div>
      <div class="aaa-oc-shirt-info-r aaa-oc-shell-cell" data-area="info_right">
        <?php do_action('aaa_oc_board_info_right', $ctx); ?>
      </div>
    </div>

    <!-- Row D: PRODUCTS TABLE -->
    <div class="aaa-oc-shell-box" data-section="table">
      <?php do_action('aaa_oc_board_products_table', $ctx); ?>
    </div>

    <!-- Row E: BOTTOM (totals | notes/driver/delivery/actions) -->
    <div class="aaa-oc-shell-row" data-section="bottom">
      <div class="aaa-oc-shell-cell" data-area="bottom_left">
        <?php
          do_action('aaa_oc_board_bottom_left_before', $ctx);
          do_action('aaa_oc_board_bottom_left',        $ctx);
          do_action('aaa_oc_board_bottom_left_after',  $ctx);
        ?>
      </div>
      <div class="aaa-oc-shell-cell" data-area="bottom_right">
        <?php
          do_action('aaa_oc_board_bottom_right_before', $ctx);
          do_action('aaa_oc_board_notes_render',        $ctx);
          do_action('aaa_oc_board_notes_entry',         $ctx);
          do_action('aaa_oc_board_driver_box',          $ctx);
          do_action('aaa_oc_board_delivery_box',        $ctx);
          do_action('aaa_oc_board_action_buttons',      $ctx);
          do_action('aaa_oc_board_bottom_right_after',  $ctx);
        ?>
      </div>
    </div>

  </div><!-- /.expanded-only -->
</div><!-- /.aaa-oc-order-card -->
