<?php
/**
 * View for the Purchase Orders' email footer (No template)
 *
 * @since 0.9.11
 *
 * @var string $footer
 */

defined( 'ABSPATH' ) || die;

?>
<br>
<p>
	<?php echo wp_kses_post( html_entity_decode( $footer, ENT_COMPAT, 'UTF-8' ) ); ?>
</p>
