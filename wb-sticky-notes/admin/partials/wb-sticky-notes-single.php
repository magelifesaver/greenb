<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Provide the HTML of a single note
 *
 *
 * @link       https://wordpress.org/plugins/wb-sticky-notes
 * @since      1.0.0
 *
 * @package    Wb_Sticky_Notes
 * @subpackage Wb_Sticky_Notes/admin/partials
 */
$theme='wb_stn_'.Wb_Sticky_Notes::$themes[$settings['theme']];
$content='';
$font_size='font-size:'.$settings['font_size'].'px; ';
$font_family='wb_stn_font_'.Wb_Sticky_Notes::$fonts[$settings['font_family']];
$width='';
$height='';
$postop='';
$posleft='';
$z_index='';
$width_vl=$settings['width'];
$height_vl=$settings['height'];
$top_vl=$settings['postop'];
$left_vl=$settings['posleft'];
$zindex_vl=$settings['z_index'];
$data_id=0;
$state='';
if(isset($theme_data) && is_array($theme_data))
{
	$theme_id=isset($theme_data['theme']) ? $theme_data['theme'] : 0;
	$theme='wb_stn_'.(isset(Wb_Sticky_Notes::$themes[$theme_id]) ? Wb_Sticky_Notes::$themes[$theme_id] : Wb_Sticky_Notes::$themes[0]);
	$state_vl=isset($theme_data['state']) ? $theme_data['state'] : 1;
	$state=$state_vl==0 ? 'display:none; ' : '';
	$status=isset($theme_data['status']) ? $theme_data['status'] : 1;
	
	$font_size='font-size:'.(isset($theme_data['font_size']) ? $theme_data['font_size'] : $settings['font_size']).'px; ';
	$font_family_id=(isset($theme_data['font_family']) ? $theme_data['font_family'] : $settings['font_family']);
	$font_family='wb_stn_font_'.(isset(Wb_Sticky_Notes::$fonts[$font_family_id]) ? Wb_Sticky_Notes::$fonts[$font_family_id] : Wb_Sticky_Notes::$fonts[0]);
	$width='width:'.(isset($theme_data['width']) ? $theme_data['width'] : $width_vl).'px; ';
	$height='height:'.(isset($theme_data['height']) ? $theme_data['height'] : $height_vl).'px; ';
	$postop='top:'.(isset($theme_data['postop']) ? $theme_data['postop'] : $top_vl).'px; ';
	$posleft='left:'.(isset($theme_data['posleft']) ? $theme_data['posleft'] : $left_vl).'px; ';
	$zindex_vl=(isset($theme_data['z_index']) ? $theme_data['z_index'] : $zindex_vl);
	$z_index='z-index:'.$zindex_vl.'; ';
	$content=isset($theme_data['content']) ? $theme_data['content'] : '';
	$data_id=(isset($theme_data['id_wb_stn_notes']) ? $theme_data['id_wb_stn_notes'] : $data_id);
}
?>
<div class="wb_stn_note <?php echo esc_attr($theme);?> <?php echo esc_attr($font_family);?>" style="<?php echo esc_attr($font_size.$width.$height.$postop.$posleft.$z_index.$state);?>" data-wb_stn_left="<?php echo esc_attr($left_vl);?>" data-wb_stn_top="<?php echo esc_attr($top_vl);?>" data-wb_stn_width="<?php echo esc_attr($width_vl);?>" data-wb_stn_height="<?php echo esc_attr($height_vl);?>" data-wb_stn_theme="<?php echo esc_attr($theme);?>" data-wb_stn_font="<?php echo esc_attr($font_family);?>" data-wb_stn_zindex="<?php echo esc_attr($zindex_vl);?>" data-wb_stn_id="<?php echo esc_attr($data_id);?>">
	<div class="wb_stn_note_hd">
		<div class="wb_stn_menu_btn wb_stn_note_options_menu">
			<span class="dashicons dashicons-menu"></span>
		</div>
		<div class="wb_stn_menu_right">
			<div class="wb_stn_menu_btn wb_stn_note_remove">
				<span class="dashicons dashicons-no-alt"></span>
			</div>
		</div>
		<?php echo $note_dropdown_menu_html;?>
	</div>	
	<div class="wb_stn_note_body">
		<div class="wb_stn_note_body_inner" contenteditable="true">
			<?php 
			$content=wp_kses_no_null($content);
			$content=wp_kses_normalize_entities($content);
			echo wp_kses_post(html_entity_decode(stripslashes($content)));?>
		</div>	
	</div>	
</div>