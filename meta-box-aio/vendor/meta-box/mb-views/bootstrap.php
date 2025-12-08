<?php
namespace MBViews;

define( 'MBV_VER', '1.13.4' );
define( 'MBV_DIR', __DIR__ );
list( , $url ) = \RWMB_Loader::get_path( __DIR__ );
define( 'MBV_URL', $url );

// Show Meta Box admin menu.
add_filter( 'rwmb_admin_menu', '__return_true' );

load_plugin_textdomain( 'mv-views', false, plugin_basename( __DIR__ ) . '/languages/' );

new PostType;

new Data;
new Location\Data;

$meta_box_renderer = new Renderer\MetaBox;
$renderer = new Renderer( $meta_box_renderer );
new Shortcode( $renderer );

if ( is_admin() ) {
	$location = new Location\Settings;
	new Editor( $location );
	new ConditionalLogic;
	new AdminColumns;
	new Category;
	new Import;
	new Export;
} else {
	new ActionLoader( $renderer );

	new TemplateLoader( 'singular' );
	new TemplateLoader( 'archive' );
	new TemplateLoader( 'code' );

	new ContentLoader( $renderer, 'singular' );
	new ContentLoader( $renderer, 'archive' );
	new ContentLoader( $renderer, 'code' );
}

new Block( $renderer );
new Blocks\View( $renderer );