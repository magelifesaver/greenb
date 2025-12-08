<?php
/**
 * File: plugins/ddd-atum-reader/includes/admin/section/section-content.php
 * Purpose: Run the query using DDD_ATUM_Logs and render the DataTable.
 * Dependencies: DDD_ATUM_Logs class; header form to supply product_name.
 * Needed by: class-ddd-atum-page.php::render_page()
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$product_name = isset( $_POST['ddd_product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ddd_product_name'] ) ) : '';

if ( $product_name ) :
    $rows = DDD_ATUM_Logs::get_logs_by_product_name( $product_name );
    if ( ! empty( $rows ) ) : ?>
        <table id="ddd-atum-table" class="display" style="width:100%">
          <thead>
            <tr>
              <th>Log ID</th>
              <th>Date/Time</th>
              <th>Event</th>
              <th>User</th>
              <th>Order</th>
              <th>Product ID</th>
              <th>Old Stock</th>
              <th>New Stock</th>
              <th>Qty</th>
              <th>Movement</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ( $rows as $r ) :
              $user_text  = $r->display_name ? $r->display_name : 'System/Auto';
              $user_link  = $r->user_id ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $r->user_id ) ) . '">' . esc_html( $user_text ) . '</a>' : esc_html( $user_text );
              $order_link = $r->order_id ? '<a href="' . esc_url( admin_url( 'post.php?post=' . (int) $r->order_id . '&action=edit' ) ) . '">#' . (int) $r->order_id . '</a>' : '';
              $movement   = ( $r->movement !== null ) ? (int) $r->movement : '';
              $m_class    = '';
              if ( $movement !== '' && $movement > 0 ) { $m_class = 'movement-pos'; }
              elseif ( $movement !== '' && $movement < 0 ) { $m_class = 'movement-neg'; }
              elseif ( $movement !== '' && 0 === $movement ) { $m_class = 'movement-zero'; }
              $m_title = ( $r->movement !== null ) ? ' title="' . esc_attr( $r->old_stock . ' → ' . $r->new_stock . ' (Δ ' . $movement . ')' ) . '"' : '';
              ?>
              <tr>
                <td><?php echo esc_html( $r->log_id ); ?></td>
                <td data-ts="<?php echo esc_attr( (int) $r->unix_ts ); ?>"><?php echo esc_html( $r->log_time ); ?></td>
                <td><?php echo esc_html( $r->event_label ); ?></td>
                <td data-user="<?php echo esc_attr( $user_text ); ?>"><?php echo $user_link; // phpcs:ignore ?></td>
                <td><?php echo $order_link; // phpcs:ignore ?></td>
                <td><?php echo esc_html( $r->product_id ); ?></td>
                <td><?php echo esc_html( $r->old_stock ); ?></td>
                <td><?php echo esc_html( $r->new_stock ); ?></td>
                <td><?php echo esc_html( $r->qty ); ?></td>
                <td class="<?php echo esc_attr( $m_class ); ?>"<?php echo $m_title; // phpcs:ignore ?>><?php echo esc_html( $movement ); ?></td>
              </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
    <?php else : ?>
        <p>No log entries found for product name: <strong><?php echo esc_html( $product_name ); ?></strong></p>
    <?php endif; ?>
<?php endif; ?>
