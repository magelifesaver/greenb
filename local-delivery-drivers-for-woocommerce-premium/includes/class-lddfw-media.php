<?php
/**
 * Media class
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

/**
 * Media class
 *
 * All media functions.
 *
 * @link  http://www.powerfulwp.com
 * @since      1.5.0
 * @package    LDDFW
 * @subpackage LDDFW/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class LDDFW_Media {
	/**
	 * Add image to media.
	 *
	 * @param string $image image.
	 * @param string $type type.
	 * @since 1.3.0
	 * @return obj
	 */
	public function lddfw_add_image_to_media( $image, $type ) {

		$pos  = strpos( $image, ';' );
		$mime = explode( ':', substr( $image, 0, $pos ) )[1];

		if ( 'image/png' === $mime ) {
			$image    = str_replace( 'data:image/png;base64,', '', $image );
			$filename = $type . '.png';
		}
		if ( 'image/jpeg' === $mime ) {
			$image    = str_replace( 'data:image/jpeg;base64,', '', $image );
			$filename = $type . '.jpg';

		}
		if ( 'image/gif' === $mime ) {
			$image    = str_replace( 'data:image/gif;base64,', '', $image );
			$filename = $type . '.gif';
		}

		$upload_dir      = wp_upload_dir();
		$upload_path     = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
		$image           = str_replace( ' ', '+', $image );
		$decoded         = base64_decode( $image );
		$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
		$file_path       = $upload_path . $hashed_filename;
		$wp_filetype     = wp_check_filetype( $filename, null );

		$image_upload = file_put_contents( $upload_path . $hashed_filename, $decoded );

		// Handle uploaded file.
		if ( ! function_exists( 'wp_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file             = array();
		$file['error']    = '';
		$file['tmp_name'] = $upload_path . $hashed_filename;
		$file['name']     = $hashed_filename;
		$file['type']     = 'image/png';
		$file['size']     = filesize( $upload_path . $hashed_filename );

		// upload file to server.
		$file_return = wp_handle_sideload( $file, array( 'test_form' => false ) );

		$filename   = $file_return['file'];
		$attachment = array(
			'post_mime_type' => $file_return['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
		);

		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[ $s ]           = array(
				'width'  => '',
				'height' => '',
			);
			$sizes[ $s ]['width']  = get_option( "{$s}_size_w" );
			$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
		}

		if ( has_filter( 'intermediate_image_sizes_advanced' ) ){	
			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes );
		}
		foreach ( $sizes as $size => $size_data ) {
			$resized = image_make_intermediate_size( $filename, $size_data['width'], $size_data['height'] );
			if ( $resized ) {
				$metadata['sizes'][ $size ] = $resized;
			}
		}

		$attach_id = wp_insert_attachment( $attachment, $filename );
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

}
