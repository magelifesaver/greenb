<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Archives page (Ajax response)
 *
 * @since    1.1.1
 */

if(is_array($archives) && count($archives))
{
	$settings=Wb_Sticky_Notes::get_settings();
	foreach($archives as $archive)
	{
		$content=isset($archive['content']) ? $archive['content'] : '';
		$theme_id=isset($archive['theme']) ? $archive['theme'] : 0;
		$theme='wb_stn_'.(isset(Wb_Sticky_Notes::$themes[$theme_id]) ? Wb_Sticky_Notes::$themes[$theme_id] : Wb_Sticky_Notes::$themes[0]);
		$font_size='font-size:'.(isset($archive['font_size']) ? $archive['font_size'] : $settings['font_size']).'px; ';

		$font_family_id=(isset($archive['font_family']) ? $archive['font_family'] : $settings['font_family']);
		$font_family='wb_stn_font_'.(isset(Wb_Sticky_Notes::$fonts[$font_family_id]) ? Wb_Sticky_Notes::$fonts[$font_family_id] : Wb_Sticky_Notes::$fonts[0]);
		$data_id=(isset($archive['id_wb_stn_notes']) ? $archive['id_wb_stn_notes'] : 0);
		?>
		<div class="wb_stn_archive" data-wb_stn_id="<?php echo esc_attr($data_id);?>">
			<div class="wb_stn_archive_colorbox <?php echo esc_attr($theme);?>"></div>
			<div class="wb_stn_archive_textbox">
				<?php 
				$content=wp_kses_no_null($content);
				$content=wp_kses_normalize_entities($content);
				echo wp_kses_post(html_entity_decode(stripslashes($content)));?>
			</div>
			
			<?php 
			if(1==$settings['enable'])
			{
			?>
				<a class="wb_stn_archive_link wb_stn_unarchive_btn" title="<?php esc_attr_e("Unarchive the current note", "wb-sticky-notes");?>"><span class="dashicons dashicons-portfolio"></span> <?php _e("Unarchive", "wb-sticky-notes");?></a>
			<?php 
			}
			?>
		</div>
		<?php
	}
}else
{
	?>
	<div class="wb_stn_no_items"><?php _e("No data to display", "wb-sticky-notes");?></div>
	<?php
}
?>

<div class="wb_stn_pagination">
	<?php 
	if($offset>0)
	{
		$prev_offset = max(($offset - $limit), 0);
		?>
		<a class="button button-secondary wb_stn_pagination_btn wb_stn_pagination_prev" data-offset="<?php echo esc_attr($prev_offset);?>"><?php _e("Previous", "wb-sticky-notes");?></a>
		<?php
	}else
	{
        ?>
        <a class="button button-secondary wb_stn_btn_disabled"><?php _e("Previous", "wb-sticky-notes");?></a>
        <?php
    }

	$nxt_offset = ($offset+$limit);
	$nxt_archives=$this->get_user_archives($nxt_offset, 1);

	if(is_array($nxt_archives) && count($nxt_archives))
	{
		?>
		<a class="button button-secondary wb_stn_pagination_btn wb_stn_pagination_next" data-offset="<?php echo esc_attr($nxt_offset);?>"><?php _e("Next", "wb-sticky-notes");?></a>
		<?php
	}else
	{
        ?>
        <a class="button button-secondary wb_stn_btn_disabled"><?php _e("Next", "wb-sticky-notes");?></a>
        <?php
    }
	?>
</div>