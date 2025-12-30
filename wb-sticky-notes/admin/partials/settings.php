<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
.wb_stn_header{ width:calc( 100% + 42px ); min-height:60px; height:auto; background:#fff; margin-left:-22px; margin-top:-10px; border-bottom: 1px solid #dcdcde; margin-bottom:3rem; text-align: center; position:relative; }
.wb_stn_header_logo{ position:absolute; top:0px; left:10px; width:100px; }
.wb_stn_heading{ font-size: 23px; font-weight: 400; margin: 0; padding: 9px 0 4px; line-height: 1.3; }
.wb_stn_menu_tab_nav{
	-ms-grid-columns: 1fr 1fr;
	display: -ms-inline-grid;
	display: inline-grid;
	grid-template-columns: 1fr 1fr;
	vertical-align: top; text-align: center; margin-top:2rem;
}
.wb_stn_menu_tab_nav a{ color: inherit;
	display: block;
	margin: 0 1rem;
	padding: .5rem 1rem 1rem;
	text-decoration: none;
	transition: box-shadow .5s ease-in-out; }
.wb_stn_menu_tab_nav a.active{ box-shadow: inset 0 -3px #007cba;
	font-weight: 600; }
.wb_stn_content{ max-width:800px; margin:0 auto; }
</style>
<div class="wrap">
	<div class="wb_stn_header">
			<img src="<?php echo esc_url( WB_STN_PLUGIN_URL . '/admin/images/logo-blue.png' ); ?>" class="wb_stn_header_logo">
			<div class="wb_stn_heading"><?php esc_html_e( 'Sticky notes', 'wb-sticky-notes' ); ?></div>

			<nav class="wb_stn_menu_tab_nav">
				<a href="<?php echo esc_url( $page_url . '&wb_stn_tab=settings' ); ?>" class="<?php echo esc_attr( 'settings' === $tab ? 'active' : '' ); ?>">
					<?php esc_html_e( 'Settings', 'wb-sticky-notes' ); ?>                      
				</a>
				<a href="<?php echo esc_url( $page_url . '&wb_stn_tab=help' ); ?>" class="<?php echo esc_attr( 'help' === $tab ? 'active' : '' ); ?>">
					<?php esc_html_e( 'Help', 'wb-sticky-notes' ); ?>                       
				</a>
			</nav>

	</div>
	<?php
	if ( 'settings' === $tab ) {
		include_once WB_STN_PLUGIN_PATH.'admin/partials/_settings_page.php';
	} elseif ( 'help' === $tab ) {
		include_once WB_STN_PLUGIN_PATH.'admin/partials/help.php';
	}
	?>
</div>
