<?php
/**
 * View for the Purchase Orders' email body (No template)
 *
 * @since 0.9.11
 *
 * @var string $body
 * @var string $subject
 * @var string $footer
 */

defined( 'ABSPATH' ) || die;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo esc_html( $subject ); ?></title>
	</head>

	<body>
		<?php echo wp_kses_post( html_entity_decode( $body, ENT_COMPAT, 'UTF-8' ) ); ?>

		<?php require 'footer.php' ?>
	</body>
</html>
