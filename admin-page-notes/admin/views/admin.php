<?php
/**
 * Represents the view for the admin addition of a page note
 */

$screen = get_current_screen();

$value = get_post_meta( $post->ID, 'gb_admin_note', true );

?>
<label for="admin_add_note"><?php _e( 'Add a note for editors to see when editing this.  Update the post to save the admin notes.', 'gb-page-notes' ); ?> </label><br><br>

<textarea id="admin_add_note" name="admin_add_note" class="widefat" ><?php echo esc_textarea(html_entity_decode( $value )); ?></textarea>