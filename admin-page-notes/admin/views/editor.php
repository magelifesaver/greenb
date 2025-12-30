<?php
/**
 * Represents the view for the admin addition of a page note
 */

$value = get_post_meta( $post->ID, 'gb_admin_note', true );

// Render the HTML content within the meta box
if ( $value ) {
	echo '<div>' . wp_kses_post( $value ) . '</div>';
} else {
	echo '<p>' . __( 'No notes available.', 'gb-page-notes' ) . '</p>';
}
?>