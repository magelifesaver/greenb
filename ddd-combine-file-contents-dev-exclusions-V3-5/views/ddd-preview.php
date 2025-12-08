<?php
/**
 * View: Preview Combined Contents
 *
 * Expects:
 *   - $view_error         (string)
 *   - $view_combined_full (string)
 */

defined( 'ABSPATH' ) || exit;

// Strip leading digits off each line
$clean = preg_replace( '/^\d+/m', '', $view_combined_full );
?>

<h2>Preview Combined Contents</h2>

<?php if ( $view_error ) : ?>
  <div style="color:red; margin-bottom:10px;">
    <?php echo esc_html( $view_error ); ?>
  </div>
<?php endif; ?>

<!-- JUST the textarea -- cfc-live-search.js will inject the bottom search / highlights -->
<textarea
  id="cfc-preview-textarea"
  rows="20"
  style="width:100%; font-family:monospace; box-sizing:border-box; resize:vertical;"
  readonly
><?php echo esc_textarea( $clean ); ?></textarea>
