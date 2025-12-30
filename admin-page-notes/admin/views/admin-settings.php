<?php
$display_notes_pages = get_option('display_notes_pages');
$display_notes_posts=get_option('display_notes_posts');
?>

<div class="wrap">
    <h1>Admin Page Notes</h1>
    <form method="post" action="options.php">
        <?php settings_fields('admin_notes_option_groups'); ?>
        <?php do_settings_sections('admin-notes'); ?>
        <label class="notes-settings-label" for="display_notes_pages">Display Notes on the Pages List:</label>
        <select name="display_notes_pages">
            <option value="none"  <?php selected($display_notes_pages, 'none'); ?>>None</option>
            <option value="full-note" <?php selected($display_notes_pages, 'full-note'); ?>>Full Note</option>
            <option value="checkbox-note" <?php selected($display_notes_pages, 'checkbox-note'); ?>>Checkbox Only</option>
        </select>
        <br/>
        <label class="notes-settings-label"  for="display_notes_posts">Display Notes on the Posts List:</label>
        <select name="display_notes_posts">
            <option value="none" <?php selected($display_notes_posts, 'none'); ?>>None</option>
            <option value="full-note" <?php selected($display_notes_posts, 'full-note'); ?>>Full Note</option>
            <option value="checkbox-note" <?php selected($display_notes_posts, 'checkbox-note'); ?>>Checkbox Only</option>
        </select>
        <?php submit_button(); ?>
    </form>
</div>