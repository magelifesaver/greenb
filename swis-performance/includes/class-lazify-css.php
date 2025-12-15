<?php
/**
 * Class and methods to lazify CSS.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to filter CSS tags and lazy load images contained in external stylesheets.
 * Can be enabled by EWWW IO or Easy IO.
 */
final class Lazify_CSS extends Page_Parser {

	/**
	 * A list of images found in external CSS with variables and CSS selectors.
	 *
	 * @access public
	 * @var array $lazified_images
	 */
	public $lazified_images = array();

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * A list of CSS files we have already processed.
	 *
	 * @access protected
	 * @var array $processed_css
	 */
	protected $processed_css = array();

	/**
	 * Setup the class, and register the init function to complete the initialization later.
	 */
	public function __construct() {
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		// Register the init function to run at the same time as the parsers in Easy IO and EWWW IO.
		// Since plugins generally load in alphabetical order, SWIS should run slightly later.
		\add_action( 'init', array( $this, 'init' ), 99 );
	}

	/**
	 * Initialize the class and register hooks. Depends on eio_lazify_external_css filter returning true.
	 */
	public function init() {
		$uri = \add_query_arg( '', '' );

		/**
		 * Allow pre-empting Lazy Load by page, but rely on Easy IO/EWWW IO for the page-level exclusions.
		 *
		 * @param bool Whether to parse the page for images to lazy load, default true.
		 * @param string The URL of the page.
		 */
		if ( ! \apply_filters( 'eio_do_lazyload', true, $uri ) ) {
			return;
		}

		/**
		 * Only proceed if EWWW IO or Easy IO is active and toggles the filter on.
		 *
		 * @param bool Whether to lazify images in external CSS.
		 */
		if ( ! \apply_filters( 'eio_lazify_external_css', false ) ) {
			$this->debug_message( 'eio_lazify_external_css filter is off' );
			return;
		}
		$this->debug_message( 'eio_lazify_external_css filter is enabled' );

		$this->cache_dir = $this->content_dir . 'cache/css/';
		if ( ! \is_dir( $this->cache_dir ) ) {
			if ( ! \wp_mkdir_p( $this->cache_dir ) ) {
				\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
				return;
			}
		}
		if ( ! \is_writable( $this->cache_dir ) ) {
			\add_action( 'admin_notices', array( $this, 'requirements_failed' ) );
			return;
		}
		$this->cache_dir_url = $this->content_url . 'cache/css/';

		// Overrides for user exclusions.
		\add_filter( 'swis_skip_css_lazify', array( $this, 'skip_css_lazify' ), 10, 2 );

		// Get all the stylesheet URLs and lazify them (if necessary).
		\add_filter( 'style_loader_src', array( $this, 'lazify_external_css_images' ), 11 );
		\add_filter( 'swis_elements_link_href', array( $this, 'lazify_external_css_images' ), 11 );

		// Lazify any custom CSS.
		\add_filter( 'wp_get_custom_css', array( $this, 'lazify_internal_css_images' ), 20 );

		// Hook onto the main output buffer filter.
		add_filter( $this->prefix . 'filter_page_output', array( $this, 'insert_lazy_images' ) );
	}

	/**
	 * Validate the user-defined exclusions.
	 */
	public function validate_user_exclusions() {
		$this->user_exclusions = array(
			'admin-ajax.php',
			'/wp-includes/',
			'googleapis.com',
		);

		$user_exclusions = \apply_filters( 'eio_get_lazy_bg_image_exclusions', $this->user_exclusions );

		if ( ! empty( $user_exclusions ) ) {
			if ( \is_string( $user_exclusions ) ) {
				$user_exclusions = array( $user_exclusions );
			}
			if ( \is_array( $user_exclusions ) ) {
				$this->user_exclusions = $user_exclusions;
			}
		}
	}

	/**
	 * Exclude CSS from being processed based on user specified list.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $url The script URL.
	 * @return boolean True to skip the resource, unchanged otherwise.
	 */
	public function skip_css_lazify( $skip, $url ) {
		if ( $this->test_mode_active() ) {
			return true;
		}
		if ( empty( $this->user_exclusions ) ) {
			$this->validate_user_exclusions();
		}
		if ( $this->user_exclusions ) {
			foreach ( $this->user_exclusions as $exclusion ) {
				if ( \str_contains( $url, $exclusion ) ) {
					$this->debug_message( __METHOD__ . "(); excluded $url via $exclusion" );
					return true;
				}
			}
		}
		return $skip;
	}

	/**
	 * Purge CSS cache.
	 */
	public function purge_cache() {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( empty( $this->cache_dir ) ) {
			return;
		}
		$this->clear_dir( $this->cache_dir );
	}

	/**
	 * Validate/absolutize background image URLs found in CSS.
	 *
	 * @param array  $url_matches List of image URLs.
	 * @param string $css_url URL of the external CSS file. Optional if parsing inline CSS.
	 * @return array The original image URLs along with the validated and resolved URLs.
	 */
	public function check_background_image_urls( $url_matches, $css_url = '' ) {

		$valid_urls = array();

		if ( ! $this->is_iterable( $url_matches ) || empty( $url_matches['url'] ) ) {
			return $valid_urls;
		}

		foreach ( $url_matches['url'] as $index => $url_match ) {
			$url = trim( $url_match, " \n\r\t\v\0'\"" );
			if ( empty( $url_matches['wrapper'][ $index ] ) ) {
				$this->debug_message( "skipped background image $url due to missing url() wrapper" );
				continue;
			}
			$original_url = $url_matches['wrapper'][ $index ]; // We need the full url(...) section to do the replacement.

			if ( ! $url ) {
				$this->debug_message( 'skipped empty background image URL' );
				continue;
			}

			$url = \trim( \html_entity_decode( $url, ENT_QUOTES | ENT_HTML401 ), "'\"\t\n\r " );
			$url = $this->absolutize_url( $url, $css_url );

			if ( ! $url ) {
				continue;
			}

			$this->debug_message( "validated and resolved $original_url to $url" );
			$valid_urls[] = array(
				'url'      => $url,
				'original' => $original_url,
			);
		}

		return $valid_urls;
	}

	/**
	 * Get full background image data from CSS code.
	 *
	 * @param string $css_content The CSS code to be parsed/searched.
	 * @param string $css_url The URL to the CSS file. Optional, needed for relative URL resolution with external CSS files.
	 * @return array The selectors, URLs, and full CSS blocks containing background/background-image properties.
	 */
	public function get_background_image_data( $css_content, $css_url = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		$results = array();

		$this->comments_map = array();
		$comment_regex      = '#/\*[^*]*\*+([^/][^*]*\*+)*/#';

		$css_content = preg_replace_callback(
			$comment_regex,
			function ( $matches ) {
				$placeholder                        = '/*' . uniqid( 'swis_css_comment' ) . '*/';
				$this->comments_map[ $placeholder ] = $matches[0];
				return $placeholder;
			},
			$css_content
		);

		$background_regex = '/background(?:-image)?:\s*?(?<css_value>[^;}]*?url\([^)]+\)[^;}]*)[^}]*}/';
		$content_reversed = strrev( $css_content );

		preg_match_all( $background_regex, $css_content, $background_matches, PREG_OFFSET_CAPTURE );
		$background_value_matches = ! empty( $background_matches['css_value'] ) ? $background_matches['css_value'] : array();

		if ( empty( $background_value_matches ) ) {
			$this->debug_message( 'no background images found' );
			return $results;
		}

		if ( empty( $this->user_exclusions ) ) {
			$this->validate_user_exclusions();
		}

		$css_length = strlen( $css_content );
		foreach ( $background_value_matches as $index => $match ) {
			$original_offset  = $match[1];
			$css_value_length = strlen( $match[0] );
			$reversed_offset  = $css_length - $original_offset - $css_value_length;

			$reversed_selector_regex = '/dnuorgkcab[^{}]*{\s?(?<reversed_selector>[^}]+)/';

			preg_match( $reversed_selector_regex, $content_reversed, $reversed_selector_matches, PREG_OFFSET_CAPTURE, $reversed_offset );

			if ( empty( $reversed_selector_matches ) ) {
				continue;
			}

			$reversed_selector = $reversed_selector_matches['reversed_selector'][0];
			$selector_offset   = $reversed_selector_matches['reversed_selector'][1];
			$selector_length   = strlen( $reversed_selector );

			$selector  = trim( strrev( $reversed_selector ) );
			$css_value = trim( $match[0] );

			$this->debug_message( "found selector '$selector' with background image(s)" );

			$block_start_position = $css_length - $selector_offset - $selector_length;
			$block_end_position   = $background_matches[0][ $index ][1] + strlen( $background_matches[0][ $index ][0] );
			$block_length         = $block_end_position - $block_start_position;

			$this->debug_message( 'pulling image URLs from:' );
			$this->debug_message( $css_value );
			$url_matches = $this->get_background_image_urls( $css_value );
			if ( empty( $url_matches['url'] ) ) {
				$this->debug_message( 'strange, no URLs found in background image property!' );
				continue;
			}

			$this->debug_message( 'found URL(s), checking' );
			// Then check for exclusions, and get absolute URLs.
			if ( $this->is_iterable( $this->user_exclusions ) ) {
				foreach ( $this->user_exclusions as $bg_exclusion ) {
					if ( \str_contains( $selector, $bg_exclusion ) || \str_contains( $css_value, $bg_exclusion ) ) {
						$this->debug_message( "skipped background image(s) in selector $selector via exclusion $bg_exclusion" );
						continue 2;
					}
				}
			}
			$urls = $this->check_background_image_urls( $url_matches, $css_url );
			if ( empty( $urls ) ) {
				continue;
			}

			$css_block = trim( substr( $css_content, $block_start_position, $block_length ) );

			// Restore comments in the CSS block.
			if ( ! empty( $this->comments_map ) ) {
				foreach ( $this->comments_map as $placeholder => $comment ) {
					$css_block = \str_replace( $placeholder, $comment, $css_block );
				}
			}

			$selector_hash = \md5( $selector );
			foreach ( $urls as $url ) {
				$parsed_path = $this->parse_url( $url['url'], PHP_URL_PATH );
				$sample      = '';
				if ( ! empty( $parsed_path ) ) {
					$parsed_path = preg_replace( '/[^A-Za-z0-9]/', '', \pathinfo( $parsed_path, PATHINFO_FILENAME ) );
					$sample      = strlen( $parsed_path ) > 5 ? substr( $parsed_path, -5 ) : $parsed_path;
				}

				$webp_url = \apply_filters( 'eio_image_url_to_webp', $url['url'] );

				list( $real_width, $real_height ) = $this->get_image_dimensions_by_url( $url['url'] );

				$hash = \uniqid( 's' . $sample, true );
				$hash = \str_replace( '.', '-', $hash ); // Strip out dots to make sure the CSS variable name is valid.

				$this->debug_message( "stashed background image {$url['url']} - {$url['original']} for selector $selector ($hash)" );
				$results[ $selector_hash ][] = array(
					'selector' => $this->clean_css_selector( $selector ),
					'url'      => \apply_filters( 'exactdn_local_to_cdn_url', $url['url'] ),
					'webp_url' => $webp_url !== $url['url'] ? $webp_url : '',
					'original' => $url['original'],
					'block'    => $css_block,
					'hash'     => $hash,
					'rwidth'   => $real_width,
					'rheight'  => $real_height,
				);
			}
		}

		return $results;
	}

	/**
	 * Lazifies CSS images.
	 *
	 * @param string $url URL to the stylesheet.
	 * @return string The lazified URL for the resource, if it was allowed.
	 */
	public function lazify_external_css_images( $url ) {
		if ( empty( $url ) ) {
			return $url;
		}
		if ( ! $this->is_frontend() ) {
			return $url;
		}
		if ( apply_filters( 'swis_skip_css_lazify', false, $url ) ) {
			return $url;
		}
		if ( ! $this->function_exists( 'filemtime' ) ) {
			return $url;
		}
		$this->debug_message( "looking for background images in $url" );
		$file = $this->get_local_path( $url );
		if ( ! $file ) {
			return $url;
		}
		if ( $this->already_processed( $file ) ) {
			return $url;
		}

		$mod_time         = filemtime( $file );
		$cache_file       = $this->get_cache_path( $file, $mod_time, 'css', 'lz' );
		$cache_url        = $this->get_cache_url( $file, $mod_time, $this->parse_url( $url, PHP_URL_QUERY ), 'css', 'lz' );
		$cache_parts      = pathinfo( $cache_file );
		$cached_lazy_file = \trailingslashit( $cache_parts['dirname'] ) . $cache_parts['filename'] . '.lazy';
		if ( $cache_file && ! $this->is_file( $cache_file ) ) {
			$css     = \file_get_contents( $file );
			$new_css = $this->lazify_raw_css_images( $css, $url, $cached_lazy_file );
			if ( ! empty( $new_css ) ) {
				\file_put_contents( $cache_file, $new_css );
				$this->debug_message( "created lazified CSS cache file $cache_file" );
			}
		} elseif ( $cache_file && $cached_lazy_file ) {
			$this->load_cached_lazy_images( $cached_lazy_file );
		}
		$this->mark_processed( $file );
		if ( $this->is_file( $cache_file ) && ! empty( $cache_url ) ) {
			\do_action( 'swis_replace_preload_url', $url, $cache_url );
			return $cache_url;
		}

		return $url;
	}

	/**
	 * Load lazified images from a cached .lazy file.
	 *
	 * @param string $cached_lazy_file The path to the cached .lazy file.
	 */
	protected function load_cached_lazy_images( $cached_lazy_file ) {
		if ( $this->is_file( $cached_lazy_file ) ) {
			$contents      = \file_get_contents( $cached_lazy_file );
			$cached_images = \json_decode( $contents, true );
			if ( ! empty( $cached_images ) && \is_array( $cached_images ) ) {
				$added = 0;
				foreach ( $cached_images as $selector_hash => $background_images ) {
					foreach ( $background_images as $image ) {
						if ( ! \is_array( $image ) ) {
							continue;
						}
						if ( empty( $image['hash'] ) || empty( $image['url'] ) || empty( $image['original'] ) || empty( $image['block'] ) || empty( $image['selector'] ) ) {
							continue;
						}
						$this->lazified_images[ $selector_hash ][] = $image;
						++$added;
					}
				}
				$this->debug_message( "loaded $added lazified images from cache file $cached_lazy_file" );
			}
		}
	}

	/**
	 * Lazifies images in internal CSS/style tags.
	 *
	 * @param string $css CSS to lazify. May contain <style> tags.
	 * @return string The lazified CSS.
	 */
	public function lazify_internal_css_images( $css ) {
		if ( ! $this->is_frontend() ) {
			return $css;
		}
		if ( ! empty( $css ) ) {
			$new_css = $this->lazify_raw_css_images( $css );
			if ( ! empty( $new_css ) ) {
				return $new_css;
			}
		}
		return $css;
	}

	/**
	 * Replace background images in CSS with placeholders and add them to the lazified_images array.
	 *
	 * @param string $css The CSS to parse.
	 * @param string $url The URL to the CSS file. Optional, used to resolve relative image URLs.
	 * @param string $cached_lazy_file The path to the cached .lazy file. Optional, used to store lazified images.
	 * @return string The modified CSS with lazified images, or empty if none found.
	 */
	protected function lazify_raw_css_images( $css, $url = '', $cached_lazy_file = '' ) {
		$new_css = '';
		if ( empty( $css ) ) {
			return $new_css;
		}
		$lazified_images = $this->get_background_image_data( $css, $url );
		if ( ! empty( $lazified_images ) ) {
			$this->debug_message( 'found ' . count( $lazified_images ) . ' images' );
			$new_css               = $this->replace_background_images( $css, $lazified_images );
			$this->lazified_images = array_merge( $this->lazified_images, $lazified_images );
			if ( $cached_lazy_file ) {
				\file_put_contents( $cached_lazy_file, \wp_json_encode( $lazified_images ) );
				$this->debug_message( 'cached ' . count( $lazified_images ) . " lazified images to $cached_lazy_file" );
			}
		}
		return $new_css;
	}

	/**
	 * Marks a CSS file as processed so we don't do it again.
	 *
	 * @param string $file The local path to the CSS file.
	 */
	public function mark_processed( $file ) {
		if ( ! empty( $file ) ) {
			$css     = \file_get_contents( $file );
			$snippet = \substr( $css, 0, 100 );
			$size    = \filesize( $file );
			// Store the snippet and size, since the filename/URL might change.
			$this->processed_css[] = array(
				'snippet' => $snippet,
				'size'    => $size,
			);
		}
	}

	/**
	 * Checks if a CSS file has already been processed.
	 *
	 * @param string $file The local path to the CSS file.
	 * @return boolean True if the file has already been processed, false otherwise.
	 */
	protected function already_processed( $file ) {
		if ( ! empty( $file ) && $this->is_file( $file ) ) {
			$css     = \file_get_contents( $file );
			$snippet = \substr( $css, 0, 100 );
			$size    = \filesize( $file );
			foreach ( $this->processed_css as $processed ) {
				if ( $processed['size'] === $size && $processed['snippet'] === $snippet ) {
					$this->debug_message( "already processed $file" );
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Replace background images in CSS with placeholders.
	 *
	 * @param string $css The CSS to parse.
	 * @param array  $lazified_images The list of images to lazify.
	 * @return string The modified CSS with lazified images.
	 */
	protected function replace_background_images( $css, $lazified_images ) {
		if ( empty( $css ) || ! $this->is_iterable( $lazified_images ) ) {
			return $css;
		}
		// Each item in $lazified images is an array with the selector as the index, containing 1 or more arrays with block, url, original, hash, and selector.
		foreach ( $lazified_images as $background_images ) {
			$new_block = '';
			foreach ( $background_images as $image ) {
				$original_block = $image['block'];
				$new_block      = $new_block ? $new_block : $original_block;
				$placeholder    = "var(--swis-bg-{$image['hash']})";
				$new_block      = \str_replace( $image['original'], $placeholder, $new_block );
				if ( $original_block === $new_block ) {
					$this->debug_message( "failed to replace {$image['original']} in CSS block" );
					continue;
				}
			}
			if ( ! empty( $new_block ) && ! empty( $original_block ) && $original_block !== $new_block ) {
				$this->debug_message( 'replaced background image(s) in CSS block' );
				$css = \str_replace( $original_block, $new_block, $css );
			}
		}
		return $css;
	}

	/**
	 * Insert lazified images into the page output and parse internal <style> blocks.
	 *
	 * @param string $content The page/post HTML.
	 * @return string The HTML with lazified images inserted into a <script> block as JSON.
	 */
	public function insert_lazy_images( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( empty( $content ) || $this->is_json( $content ) ) {
			return $content;
		}
		// Parse any internal <style> blocks.
		$styles = $this->get_style_elements_from_html( $content );
		if ( $this->is_iterable( $styles ) ) {
			foreach ( $styles as $style_block ) {
				$new_style = $this->lazify_internal_css_images( $style_block );
				if ( ! empty( $new_style ) ) {
					$content = \str_replace( $style_block, $new_style, $content );
				}
			}
		}
		if ( ! empty( $this->lazified_images ) ) {
			$this->debug_message( 'inserting ' . count( $this->lazified_images ) . ' lazified CSS images into page output' );
			$images_json = \wp_json_encode( $this->lazified_images );
			if ( ! empty( $images_json ) ) {
				$style  = "<style id='swis-lazy-css-styles'></style>";
				$script = "<script id='swis-lazy-css-images' data-cfasync='false' data-no-optimize='1' data-no-defer='1' data-no-minify='1'>var swis_lazy_css_images = $images_json;</script>";
				// Insert just after the closing </title> tag.
				$pos = strpos( $content, '</title>' );
				if ( false !== $pos ) {
					$content = substr_replace( $content, "</title>\n$style\n$script", $pos, strlen( '</title>' ) );
				}
			}
		}
		return $content;
	}
}
