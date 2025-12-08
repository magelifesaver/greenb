<?php
/**
 * File: plugins/ddd-atum-reader/includes/admin/section/section-header.php
 * Purpose: Render the top controls: product name input, date range, event/user filter containers.
 * Dependencies: Enqueued scripts/styles; content section prints the table.
 * Needed by: class-ddd-atum-page.php::render_page()
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$product_name = isset( $_POST['ddd_product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddd_product_name'] ) ) : '';
?>
<div class="wrap">
  <h1>ATUM Log Viewer</h1>

  <form method="post" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <label>Product Name:</label>
    <input type="text" name="ddd_product_name" value="<?php echo esc_attr( $product_name ); ?>" required style="width:300px;" />
    <button type="submit" class="button button-primary">Search</button>

    <span id="ddd-date-range">
      <label>From:</label>
      <input type="date" id="ddd-date-from" />
      <label>To:</label>
      <input type="date" id="ddd-date-to" />
    </span>
  </form>

  <div id="ddd-controls">
    <span id="ddd-event-filter" title="Toggle event types"></span>
    <span id="ddd-user-filter"  title="Filter by user"></span>
  </div>
  <hr>
