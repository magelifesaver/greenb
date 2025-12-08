<?php
	// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

	/**
	 * Register scripts and css files
	 */
	wp_register_script( 'lddfw-jquery-validate', plugin_dir_url( __FILE__ ) . 'public/js/jquery.validate.min.js', array( 'jquery', 'jquery-ui-core' ), LDDFW_VERSION, true );
	wp_register_script( 'lddfw-bootstrap', plugin_dir_url( __FILE__ ) . 'public/js/bootstrap.min.js', array(), LDDFW_VERSION, false );
if ( lddfw_fs()->is__premium_only() ) {
	if ( lddfw_fs()->is_plan( 'premium', true ) ) {
		wp_register_script( 'lddfw-signature', plugin_dir_url( __FILE__ ) . 'public/js/signature_pad.min.js', array(), LDDFW_VERSION, false );
	}
}
	wp_register_script( 'lddfw-public', plugin_dir_url( __FILE__ ) . 'public/js/lddfw-public.js', array(), LDDFW_VERSION, false );

	wp_register_style( 'lddfw-bootstrap', plugin_dir_url( __FILE__ ) . 'public/css/bootstrap.min.css', array(), LDDFW_VERSION, 'all' );
	wp_register_style( 'lddfw-fonts', 'https://fonts.googleapis.com/css?family=Open+Sans|Roboto&display=swap', array(), LDDFW_VERSION, 'all' );
	wp_register_style( 'lddfw-public', plugin_dir_url( __FILE__ ) . 'public/css/lddfw-public.css', array(), LDDFW_VERSION, 'all' );

	$tracking      = new LDDFW_Tracking();
	$lddfw_content = $tracking->tracking_page();


?>
<!DOCTYPE html>
<html>
<head>
<?php
	echo '<title>' . esc_js( __( 'Tracking', 'lddfw' ) ) . '</title>';
?>
<meta name="robots" content="noindex" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="icon" href="<?php echo get_site_icon_url( 32, esc_url( plugin_dir_url( __FILE__ ) . 'public/images/favicon-32x32.png?ver=' . LDDFW_VERSION ) ); ?>" >
<?php
	wp_print_styles( array( 'lddfw-fonts', 'lddfw-bootstrap', 'lddfw-public' ) );

if ( is_rtl() === true ) {
	wp_register_style( 'lddfw-public-rtl', plugin_dir_url( __FILE__ ) . 'public/css/lddfw-public-rtl.css', array(), LDDFW_VERSION, 'all' );
	wp_print_styles( array( 'lddfw-public-rtl' ) );
}

	wp_print_scripts( array( 'lddfw-jquery-validate' ) );
?>
</head>
<body class="lddfw_tracking_page">
	
	<div id="lddfw_tracking_page" >
		<?php echo $lddfw_content; ?>
	</div>
<?php
	wp_print_scripts( array( 'lddfw-bootstrap', 'lddfw-public' ) );
?>
</body>
</html>
