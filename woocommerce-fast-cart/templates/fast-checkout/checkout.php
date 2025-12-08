<?php
/**
 * WFC checkout  template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/wfc/fast-checkout/checkout.php.
 */

do_action( 'wfc_checkout_before_template' );

?>
 <!doctype html>
<html <?php language_attributes(); ?> style="margin-top:0!important;height:auto!important;background:white">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php do_action( 'wfc_pre_wp_head' ); ?>
<?php wp_head(); ?>
</head>

<body <?php do_action( 'wfc_checkout_body_tag' ); ?> <?php body_class( 'wfc-checkout' ); ?>>

<?php
wp_body_open();
the_post();
?>

<?php do_action( 'wfc_checkout_before_content' ); ?>

<?php do_action( 'wfc_checkout_the_content' ); ?>

<?php do_action( 'wfc_checkout_after_content' ); ?>

<?php wp_footer(); ?>

</body>
</html>
