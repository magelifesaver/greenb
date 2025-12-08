<div class="wrap">
	<h2><?php esc_html_e( "Developer's Toolkit for Paid Memberships Pro", 'pmpro-toolkit' ); ?></h2>
	<?php
		if ( isset( $_REQUEST[ 'page' ] ) ) {
			$view = sanitize_text_field( $_REQUEST[ 'page' ] );
		} else {
			$view = '';
		}
	?>
	<nav class="nav-tab-wrapper">
		<a href="<?php echo admin_url( 'options-general.php?page=pmprodev' );?>" class="nav-tab<?php if($view == 'pmprodev') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Toolkit Options', 'pmpro-toolkit' );?></a>
		<a href="<?php echo admin_url( 'tools.php?page=pmprodev-database-scripts' );?>" class="nav-tab<?php if($view == 'pmprodev-database-scripts') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Database Scripts', 'pmpro-toolkit' );?></a>
		<a href="<?php echo admin_url( 'tools.php?page=pmprodev-migration-assistant' );?>" class="nav-tab<?php if($view == 'pmprodev-migration-assistant') { ?> nav-tab-active<?php } ?>"><?php esc_html_e('Migration Assistant', 'pmpro-toolkit' );?></a>
	</nav>
	<?php
		// Check if the user submitted an import file.
		if (
			isset( $_FILES['pmprodev-import-file'] ) &&
			$_FILES['pmprodev-import-file']['error'] == UPLOAD_ERR_OK // Checks for errors.
      		&& is_uploaded_file( $_FILES['pmprodev-import-file']['tmp_name'] )
		) {
			// Verify the nonce.
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'pmprodev-import' ) ) {
				// Verification failed.
				echo '<div class="notice notice-large notice-error inline"><p>' . esc_html__( 'Nonce verification failed.', 'pmpro-toolkit' ) . '</p></div>';
			} else {
				// Verification succeeded. Import the file.
				$error = PMProDev_Migration_Assistant::import( $_FILES['pmprodev-import-file']['tmp_name'] );
				if ( is_string( $error ) ) {
					// There was an error during the import.
					echo '<div class="notice notice-large notice-error inline"><p>' . esc_html( $error ) . '</p></div>';
				} else {
					// Import successful.
					echo '<div class="notice notice-large notice-success inline"><p>' . esc_html__( 'Import successful.', 'pmpro-toolkit' ) . '</p></div>';
				}
			}
		}  elseif ( isset( $_POST['_wpnonce'] ) ) {
			echo '<div class="notice notice-large notice-error inline"><p>' . esc_html__( 'No Import file found. Please try importing again with a valid JSON file.', 'pmpro-toolkit' ) . '</p></div>';
		}
	?>
	<h2><?php esc_html_e( 'Export PMPro Data', 'pmpro-toolkit' ) ?></h2>
	<p><?php esc_html_e( 'Select the data that you would like to export:', 'pmpro-toolkit' ); ?></p>
	<button id="pmprodev-export-select-all"><?php esc_html_e( 'Select All', 'pmpro-toolkit' ); ?></button>
	<?php
		$export_options = array(
			'levels'          => __( 'Membership Levels', 'pmpro-toolkit' ),
			'email_templates' => __( 'Email Templates', 'pmpro-toolkit' ),
			'payment'		  => __( 'Payment & SSL Settings', 'pmpro-toolkit' ),
			'advanced'        => __( 'Advanced Settings', 'pmpro-toolkit' ),
		);
		foreach ( $export_options as $key => $label ) {
			echo '<p><input type="checkbox" name="pmprodev_export_options[]" value="' . esc_attr( $key ) . '" id="pmprodev_export_options_' . esc_attr( $key ) . '" /> <label for="pmprodev_export_options_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></p>';
		}
	?>
	<p class="submit"><input type="submit" id="pmprodev-export-data" class="button button-primary" onsubmit="return false;" value="<?php esc_html_e( 'Export PMPro Data', 'pmpro-toolkit' ); ?>"></p>
	<hr/>
	<h2><?php esc_html_e( 'Import PMPro Data', 'pmpro-toolkit' ) ?></h2>
	<form method="post" enctype="multipart/form-data">
		<label for="pmprodev-import-file"><?php esc_html_e( 'Choose a file to import', 'pmpro-toolkit' ); ?>:</label>
		<input type="file" name="pmprodev-import-file" accept="application/JSON">
		<?php wp_nonce_field( 'pmprodev-import' ); ?>
		<p class="submit"><input type="submit" class="button button-primary" value="<?php esc_html_e( 'Import PMPro Data', 'pmpro-toolkit' ); ?>" onclick="return confirm('<?php esc_html_e( 'This import will permanently overwrite site data. Are you sure that you would like to continue? ', 'pmpro-toolkit' ); ?>')"></p>
	</form>
	
	<script>
		jQuery(document).ready(function() {
			// Handle "Select All" button clicks.
			jQuery('#pmprodev-export-select-all').click(function(e) {
				// Check all the checkboxes.
				jQuery( 'input[name="pmprodev_export_options[]"]' ).each(function() {
					jQuery(this).prop('checked', true);
				});
			});

			// Handle export button clicks.
			jQuery( '#pmprodev-export-data' ).click( function( event ) {
				// Get all checked export options.
				var export_options = jQuery( 'input[name="pmprodev_export_options[]"]:checked' );
				if ( export_options.length == 0 ) {
					alert( '<?php esc_html_e( 'Please select at least one export option.', 'pmpro-toolkit' ); ?>' );
					return;
				}

				// Download export file.
				window.open( '<?php echo admin_url( '/tools.php?page=pmprodev-migration-assistant' ); ?>&' + export_options.serialize() );
			});
		});
	</script>
</div>
