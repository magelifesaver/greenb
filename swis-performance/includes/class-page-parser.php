<?php
/**
 * Implements basic page parsing functions.
 *
 * @link https://ewww.io
 * @package EIO
 */

namespace SWIS;
use MatthiasMullie\Minify;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML element and attribute parsing, replacing, etc.
 */
class Page_Parser extends Base {

	/**
	 * Allowed image extensions.
	 *
	 * @access protected
	 * @var array $extensions
	 */
	protected $extensions = array(
		'gif',
		'jpg',
		'jpeg',
		'jpe',
		'png',
		'svg',
		'webp',
	);

	/**
	 * The default exclusions for inline scripts.
	 *
	 * @access protected
	 * @var array $inline_exclusions
	 */
	protected $inline_exclusions = array(
		'AFFWP.referral_var',
		'anr_captcha_field_div',
		'checkImageSizes',
		'customize-support',
		'document.body.classList.remove(',
		'document.documentElement.className.replace(',
		'document.documentElement.className =',
		'document.write',
		'DOMContentLoaded',
		'ewww_webp_supported',
		'eio_lazy_vars',
		'FB3D_CLIENT_LOCALE',
		'real-cookie-banner',
		'N.N2_',
		'swisperformance_vars',
		'window.lazyLoadOptions',
	);

	/**
	 * Request URI.
	 *
	 * @var string $request_uri
	 */
	protected $request_uri = '';

	/**
	 * List/map of CSS comments found during parsing a CSS file/block.
	 *
	 * @access protected
	 * @var array $comments_map
	 */
	protected $comments_map = array();

	/**
	 * Match all images and any relevant <a> tags in a block of HTML.
	 *
	 * The hyperlinks param implies that the src attribute is required, but not the other way around.
	 *
	 * @param string $content Some HTML.
	 * @param bool   $hyperlinks Default true. Should we include encasing hyperlinks in our search.
	 * @param bool   $src_required Default true. Should we look only for images with src attributes.
	 * @return array An array of $images matches, where $images[0] is
	 *         an array of full matches, and the link_url, img_tag,
	 *         and img_url keys are arrays of those matches.
	 */
	public function get_images_from_html( $content, $hyperlinks = true, $src_required = true ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$images          = array();
		$unquoted_images = array();

		if ( empty( $content ) ) {
			return $images;
		}
		$unquoted_pattern = '';
		$search_pattern   = '#(?P<img_tag><img\s[^\\\\>]*?>)#is';
		if ( $hyperlinks ) {
			$this->debug_message( 'using figure+hyperlink(a) patterns with src required' );
			$search_pattern   = '#(?:<figure[^>]*?\s+?class\s*=\s*["\'](?P<figure_class>[\w\s-]+?)["\'][^>]*?>\s*)?(?:<a[^>]*?\s+?href\s*=\s*["\'](?P<link_url>[^\s]+?)["\'][^>]*?>\s*)?(?P<img_tag><img[^>]*?\s+?src\s*=\s*("|\')(?P<img_url>(?!\4)[^\\\\]+?)\4[^>]*?>){1}(?:\s*</a>)?#is';
			$unquoted_pattern = '#(?:<figure[^>]*?\s+?class\s*=\s*(?P<figure_class>[\w-]+)[^>]*?>\s*)?(?:<a[^>]*?\s+?href\s*=\s*(?P<link_url>[^"\'\\\\<>][^\s<>]+)[^>]*?>\s*)?(?P<img_tag><img[^>]*?\s+?src\s*=\s*(?P<img_url>[^"\'\\\\<>][^\s\\\\<>]+)(?:\s[^>]*?)?>){1}(?:\s*</a>)?#is';
		} elseif ( $src_required ) {
			$this->debug_message( 'using plain img pattern, src still required' );
			$search_pattern   = '#(?P<img_tag><img[^>]*?\s+?src\s*=\s*("|\')(?P<img_url>(?!\2)[^\\\\]+?)\2[^>]*?>)#is';
			$unquoted_pattern = '#(?P<img_tag><img[^>]*?\s+?src\s*=\s*(?P<img_url>[^"\'\\\\<>][^\s\\\\<>]+)(?:\s[^>]*?)?>)#is';
		}
		if ( preg_match_all( $search_pattern, $content, $images ) ) {
			$this->debug_message( 'found ' . count( $images[0] ) . ' image elements with quoted pattern' );
			foreach ( $images as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $images[ $key ] );
				}
			}
			/* $this->debug_message( print_r( $images, true ) ); */
		}
		$images = array_filter( $images );
		if ( $unquoted_pattern && preg_match_all( $unquoted_pattern, $content, $unquoted_images ) ) {
			$this->debug_message( 'found ' . count( $unquoted_images[0] ) . ' image elements with unquoted pattern' );
			foreach ( $unquoted_images as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $unquoted_images[ $key ] );
				}
			}
			/* $this->debug_message( print_r( $unquoted_images, true ) ); */
		}
		$unquoted_images = array_filter( $unquoted_images );
		if ( ! empty( $images ) && ! empty( $unquoted_images ) ) {
			$this->debug_message( 'both patterns found results, merging' );
			/* $this->debug_message( print_r( $images, true ) ); */
			$images = array_merge_recursive( $images, $unquoted_images );
			/* $this->debug_message( print_r( $images, true ) ); */
			if ( ! empty( $images[0] ) && ! empty( $images[1] ) ) {
				$images[0] = array_merge( $images[0], $images[1] );
				unset( $images[1] );
			}
		} elseif ( empty( $images ) && ! empty( $unquoted_images ) ) {
			$this->debug_message( 'unquoted results only, subbing in' );
			$images = $unquoted_images;
		}
		/* $this->debug_message( print_r( $images, true ) ); */
		return $images;
	}

	/**
	 * Match all images wrapped in <noscript> tags in a block of HTML.
	 *
	 * @param string $content Some HTML.
	 * @return array An array of $images matches, where $images[0] is
	 *         an array of full matches, and the noscript_tag, img_tag,
	 *         and img_url keys are arrays of those matches.
	 */
	public function get_noscript_images_from_html( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$images = array();

		if ( ! empty( $content ) && \preg_match_all( '#(?P<noscript_tag><noscript[^>]*?>\s*)(?P<img_tag><img[^>]*?\s+?src\s*=\s*["\'](?P<img_url>[^\s]+?)["\'][^>]*?>){1}(?:\s*</noscript>)?#is', $content, $images ) ) {
			foreach ( $images as $key => $unused ) {
				// Simplify the output as much as possible, mostly for confirming test results.
				if ( is_numeric( $key ) && $key > 0 ) {
					unset( $images[ $key ] );
				}
			}
			return $images;
		}
		return array();
	}

	/**
	 * Match all sources wrapped in <picture> tags in a block of HTML.
	 *
	 * @param string $content Some HTML.
	 * @return array An array of $pictures matches, containing full elements with ending tags.
	 */
	public function get_picture_tags_from_html( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$pictures = array();
		if ( ! empty( $content ) && \preg_match_all( '#(?:<picture[^>]*?>\s*)(?:<source[^>]*?>)+(?:.*?</picture>)?#is', $content, $pictures ) ) {
			return $pictures[0];
		}
		return array();
	}

	/**
	 * Match all <style> elements in a block of HTML.
	 *
	 * @param string $content Some HTML.
	 * @return array An array of $styles matches, containing full elements with ending tags.
	 */
	public function get_style_elements_from_html( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$styles = array();
		if ( ! empty( $content ) && \preg_match_all( '#<style[^>]*?>.*?</style>#is', $content, $styles ) ) {
			return $styles[0];
		}
		return array();
	}

	/**
	 * Match all <script> elements in a block of HTML.
	 *
	 * @param string $content Some HTML.
	 * @return array An array of $scripts matches, containing full elements with ending tags.
	 */
	public function get_script_elements_from_html( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$scripts = array();
		if ( ! empty( $content ) && \preg_match_all( '#<script[^>]*?>.*?</script>#is', $content, $scripts ) ) {
			return $scripts[0];
		}
		return array();
	}

	/**
	 * Split out the parts of a <script> tag.
	 *
	 * @param string $element The full <script>...</script> element.
	 * @return array An array of $scripts matches, containing full elements with ending tags.
	 */
	public function script_parts( $element ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! empty( $element ) && \preg_match( '#(?P<open><script[^>]*?>)(?P<content>.*?)(?P<close></script>)#is', $element, $script_parts ) ) {
			foreach ( $script_parts as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( \is_numeric( $key ) ) {
					unset( $script_parts[ $key ] );
				}
			}
			return $script_parts;
		}
		return false;
	}


	/**
	 * Match all elements by tag name in a block of HTML. Does not retrieve contents or closing tags.
	 *
	 * @param string $content Some HTML.
	 * @param string $tag_name The name of the elements to retrieve.
	 * @return array An array of $elements.
	 */
	public function get_elements_from_html( $content, $tag_name ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! \ctype_alpha( \str_replace( '-', '', $tag_name ) ) ) {
			return array();
		}
		if ( ! empty( $content ) && \preg_match_all( '#<' . $tag_name . '\s[^\\\\>]+?>#is', $content, $elements ) ) {
			return $elements[0];
		}
		return array();
	}

	/**
	 * Try to determine height and width from strings WP appends to resized image filenames.
	 *
	 * @param string $src The image URL.
	 * @return array An array consisting of width and height.
	 */
	public function get_dimensions_from_filename( $src ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$width_height_string = array();
		$this->debug_message( "looking for dimensions in $src" );
		$width  = false;
		$height = false;
		if ( \preg_match( '#-(\d+)x(\d+)(@2x)?\.(?:' . \implode( '|', $this->extensions ) . '){1}(?:\?.+)?$#i', $src, $width_height_string ) ) {
			$width  = (int) $width_height_string[1];
			$height = (int) $width_height_string[2];

			if ( \strpos( $src, '@2x' ) ) {
				$width  = 2 * $width;
				$height = 2 * $height;
			}
			if ( $width && $height ) {
				$this->debug_message( "found w$width h$height" );
				return array( $width, $height );
			}
		}
		return array( $width, $height );
	}

	/**
	 * Get dimensions of a file from the URL.
	 *
	 * @param string $url The URL of the image.
	 * @return array The width and height, in pixels.
	 */
	public function get_image_dimensions_by_url( $url ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$this->debug_message( "getting dimensions for $url" );

		list( $width, $height ) = $this->get_dimensions_from_filename( $url, ! empty( $this->parsing_exactdn ) );
		if ( empty( $width ) || empty( $height ) ) {
			// Couldn't get it from the URL directly, see if we can get the actual filename.
			$file = $this->get_local_path( $url );
			if ( $file && $this->is_file( $file ) ) {
				list( $width, $height ) = \wp_getimagesize( $file );
			}
		}
		$width  = $width && \is_numeric( $width ) ? (int) $width : false;
		$height = $height && \is_numeric( $height ) ? (int) $height : false;

		return array( $width, $height );
	}

	/**
	 * Get an attribute from an HTML element.
	 *
	 * @param string $element The HTML element to parse.
	 * @param string $name The name of the attribute to search for.
	 * @return string The value of the attribute, or an empty string if not found.
	 */
	public function get_attribute( $element, $name ) {
		// Don't forget, back references cannot be used in character classes.
		if ( preg_match( '#\s' . $name . '\s*=\s*("|\')((?!\1).+?)\1#is', $element, $attr_matches ) ) {
			if ( ! empty( $attr_matches[2] ) ) {
				return $attr_matches[2];
			}
		}
		// If there were not any matches with quotes, look for unquoted attributes, no spaces or quotes allowed.
		if ( preg_match( '#\s' . $name . '\s*=\s*([^"\'][^\s>]+)#is', $element, $attr_matches ) ) {
			if ( ! empty( $attr_matches[1] ) ) {
				return $attr_matches[1];
			}
		}
		return '';
	}

	/**
	 * Check if a URL is relative.
	 *
	 * @param string $url URL to check.
	 * @return bool True if the URL is relative, false if absolute.
	 */
	protected function is_url_relative( $url ) {
		return \str_starts_with( '.', $url ) || empty( \wp_parse_url( $url, PHP_URL_HOST ) );
	}

	/**
	 * Make a URL absolute.
	 *
	 * @param string $url The URL to make absolute.
	 * @param string $base_url Optional. The base URL to use for relative URLs. If not provided, the site URL will be used.
	 * @return string The absolute URL, or an empty string on failure.
	 */
	public function absolutize_url( $url, $base_url = '' ) {
		// If the URL is already absolute, return it as-is.
		if ( ! $this->is_url_relative( $url ) ) {
			$this->debug_message( "url $url is already absolute" );
			return $url;
		}

		if ( empty( $base_url ) && str_starts_with( $url, '.' ) ) {
			$this->debug_message( "cannot resolve relative URL $url without a base URL" );
			return '';
		}

		if ( ! empty( $base_url ) ) {
			$this->debug_message( "resolving relative URL $url against base $base_url" );
			$parsed_base = $this->parse_url( $base_url );

			// Handle root-relative URLs.
			if ( \str_starts_with( $url, '/' ) ) {
				if ( ! empty( $parsed_base['scheme'] ) && ! empty( $parsed_base['host'] ) ) {
					return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
				}
				return rtrim( $base_url, '/' ) . $url;
			}

			// Handle relative URLs.
			if ( ! empty( $parsed_base['scheme'] ) && ! empty( $parsed_base['host'] ) ) {
				$path = ! empty( $parsed_base['path'] ) ? trailingslashit( dirname( $parsed_base['path'] ) ) : '/';
				if ( str_starts_with( $url, './' ) ) {
					$url = substr( $url, 2 );
				}
				while ( str_starts_with( $url, '../' ) ) {
					$path = trailingslashit( dirname( $path ) );
					$url  = substr( $url, 3 );
				}
				return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $path . ltrim( $url, './' );
			}
		}
		return \trailingslashit( \home_url() ) . ltrim( $url, './' );
	}

	/**
	 * Get a CSS background-image URL.
	 *
	 * @param string $attribute An element's style attribute. Do not pass a full HTML element.
	 * @return string The URL from the background/background-image property.
	 */
	public function get_background_image_url( $attribute ) {
		if ( ( false !== strpos( $attribute, 'background:' ) || false !== strpos( $attribute, 'background-image:' ) ) && false !== strpos( $attribute, 'url(' ) ) {
			if ( \preg_match( '#url\(([^)]+)\)#', $attribute, $prop_match ) ) {
				return \trim( \html_entity_decode( $prop_match[1], ENT_QUOTES | ENT_HTML401 ), "'\"\t\n\r " );
			}
		}
		return '';
	}

	/**
	 * Get one (or more) CSS background-image URLs.
	 *
	 * @param string $css_section An element's style attribute, or a CSS rule/section. Do not pass a full HTML element or CSS file.
	 * @return array The URLs from the background/background-image property.
	 */
	public function get_background_image_urls( $css_section ) {
		if ( ( \str_contains( $css_section, 'background:' ) || \str_contains( $css_section, 'background-image:' ) ) && \str_contains( $css_section, 'url(' ) ) {
			if ( \preg_match_all( '#(?<wrapper>url\([\s\'"]*(?<url>[^)]+)[\s\'"]*\))#', $css_section, $url_matches ) ) {
				if ( ! empty( $url_matches['url'] ) ) {
					return $url_matches;
				}
			}
		} elseif ( \str_contains( $css_section, 'url(' ) ) {
			if ( \preg_match_all( '#(?<wrapper>url\([\s\'"]*(?<url>[^)]+)[\s\'"]*\))#', $css_section, $url_matches ) ) {
				if ( ! empty( $url_matches['url'] ) ) {
					return $url_matches;
				}
			}
		}
		return array();
	}

	/**
	 * Get CSS background-image rules from HTML.
	 *
	 * @param string $html The code containing potential background images.
	 * @return array The URLs with background/background-image properties.
	 */
	public function get_background_images( $html ) {
		if ( ( false !== \strpos( $html, 'background:' ) || false !== \strpos( $html, 'background-image:' ) ) && false !== \strpos( $html, 'url(' ) ) {
			if ( \preg_match_all( '#background(-image)?:\s*?[^;}]*?url\([^)]+\)#', $html, $matches ) ) {
				return $matches[0];
			}
		}
		return array();
	}

	/**
	 * Clean a CSS selector, removing pseudo-classes and pseudo-elements.
	 *
	 * @param string $selector The CSS selector to clean.
	 * @return string The cleaned selector.
	 */
	public function clean_css_selector( $selector ) {
		// Because there might actually be multiple selectors in a single rule, we need to split them out.
		$selectors = explode( ',', $selector );

		// The longer focus-* variants need to be first, so that they match before the shorter :focus.
		$pseudo_classes = array(
			':focus-within',
			':focus-visible',
			':active',
			':after',
			':before',
			':first-letter',
			':first-line',
			':focus',
			':hover',
			':visited',
		);

		// Combinators need to be suffixed with an '*' if there is nothing following the combinator.
		$combinators = array( '>', '+', '~', '&' );

		foreach ( $selectors as $index => $single_selector ) {
			$single_selector = preg_replace( '/::[\w-]+/', '', $single_selector ); // Remove pseudo-elements.
			foreach ( $pseudo_classes as $pseudo_class ) {
				$single_selector = str_replace( $pseudo_class, '', $single_selector );
			}
			if ( in_array( substr( trim( $single_selector ), -1 ), $combinators, true ) ) {
				$single_selector .= '*';
			}
			$selectors[ $index ] = $single_selector;
		}

		return implode( ',', $selectors );
	}

	/**
	 * Set an attribute on an HTML element.
	 *
	 * @param string $element The HTML element to modify. Passed by reference.
	 * @param string $name The name of the attribute to set.
	 * @param string $value The value of the attribute to set.
	 * @param bool   $replace Default false. True to replace, false to append.
	 */
	public function set_attribute( &$element, $name, $value, $replace = false ) {
		if ( 'class' === $name ) {
			$element = preg_replace( "#\s$name\s+([^=])#", ' $1', $element );
		}
		// Remove empty attributes first.
		$element = preg_replace( "#\s$name=\"\"#", ' ', $element );
		// Remove/escape double-quotes with the encoded version, so that we can safely enclose the value in double-quotes.
		$value = str_replace( '"', '&#34;', $value );
		$value = trim( $value );
		if ( $replace ) {
			// Don't forget, back references cannot be used in character classes.
			$new_element = preg_replace( '#\s' . $name . '\s*=\s*("|\')(?!\1).*?\1#is', ' ' . $name . '="' . $value . '"', $element );
			if ( strpos( $new_element, "$name=" ) && $new_element !== $element ) {
				$element = $new_element;
				return;
			}
			// Purge un-quoted attribute patterns, so the new value can be inserted further down.
			$new_element = preg_replace( '#\s' . $name . '\s*=\s*[^"\'][^\s>]+#is', ' ', $element );
			// But if we couldn't purge the attribute, then bail out.
			if ( preg_match( '#\s' . $name . '\s*=\s*#', $new_element ) && $new_element === $element ) {
				$this->debug_message( "$name replacement failed, still exists in $element" );
				return;
			}
			$element = $new_element;
		}
		$closing = ' />';
		if ( false === strpos( $element, '/>' ) ) {
			$closing = '>';
		}
		if ( false === strpos( $value, '"' ) ) { // This should always be true, since we escape double-quotes above.
			$element = rtrim( $element, $closing ) . " $name=\"$value\"$closing";
			return;
		}
		// If we get here, something is kind of weird, since double-quotes were supposed to be escaped.
		$element = rtrim( $element, $closing ) . " $name='$value'$closing";
	}

	/**
	 * Remove an attribute from an HTML element.
	 *
	 * @param string $element The HTML element to modify. Passed by reference.
	 * @param string $name The name of the attribute to remove.
	 */
	public function remove_attribute( &$element, $name ) {
		// Don't forget, back references cannot be used in character classes.
		$element = preg_replace( '#\s' . $name . '\s*=\s*("|\')(?!\1).+?\1#is', ' ', $element );
		$element = preg_replace( '#\s' . $name . '\s*=\s*[^"\'][^\s>]+#is', ' ', $element );
	}

	/**
	 * Remove the background image URL from a style attribute.
	 *
	 * @param string $attribute The element's style attribute to modify.
	 * @return string The style attribute with any image url removed.
	 */
	public function remove_background_image( $attribute ) {
		if ( false !== strpos( $attribute, 'background:' ) && false !== strpos( $attribute, 'url(' ) ) {
			$attribute = preg_replace( '#\s?url\([^)]+\)#', '', $attribute );
		}
		if ( false !== strpos( $attribute, 'background-image:' ) && false !== strpos( $attribute, 'url(' ) ) {
			$attribute = preg_replace( '#background-image:\s*url\([^)]+\);?#', '', $attribute );
		}
		return $attribute;
	}

	/**
	 * Remove any unexpected characters from a given HTTP request header.
	 *
	 * @param string $header The value of an HTTP request header.
	 * @return string The sanitized and unslashed header value.
	 */
	public function sanitize_header( $header ) {
		$header = trim( preg_replace( '#[^\w\s/=;+,:\*\.\(\)-]#', '', stripslashes( $header ) ) );
		return $header;
	}

	/**
	 * Get needed HTTP request headers from current request.
	 *
	 * @return array A list of HTTP request headers from this request.
	 */
	protected function get_request_headers() {
		$request_headers = ( function_exists( 'apache_request_headers' ) ) ? apache_request_headers() : array();

		$request_headers = array(
			'Accept'             => ( isset( $request_headers['Accept'] ) ) ? $request_headers['Accept'] : ( ( isset( $_SERVER['HTTP_ACCEPT'] ) ) ? $_SERVER['HTTP_ACCEPT'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'Accept-Encoding'    => ( isset( $request_headers['Accept-Encoding'] ) ) ? $request_headers['Accept-Encoding'] : ( ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'Host'               => ( isset( $request_headers['Host'] ) ) ? $request_headers['Host'] : ( ( isset( $_SERVER['HTTP_HOST'] ) ) ? $_SERVER['HTTP_HOST'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'If-Modified-Since'  => ( isset( $request_headers['If-Modified-Since'] ) ) ? $request_headers['If-Modified-Since'] : ( ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'User-Agent'         => ( isset( $request_headers['User-Agent'] ) ) ? $request_headers['User-Agent'] : ( ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'X-Forwarded-Proto'  => ( isset( $request_headers['X-Forwarded-Proto'] ) ) ? $request_headers['X-Forwarded-Proto'] : ( ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'X-Forwarded-Scheme' => ( isset( $request_headers['X-Forwarded-Scheme'] ) ) ? $request_headers['X-Forwarded-Scheme'] : ( ( isset( $_SERVER['HTTP_X_FORWARDED_SCHEME'] ) ) ? $_SERVER['HTTP_X_FORWARDED_SCHEME'] : '' ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		);

		// Clean things up just to be safe, runs the headers through stripslashes and a very restrictive whitelist of characters.
		array_walk( $request_headers, array( $this, 'sanitize_header' ) );
		return $request_headers;
	}

	/**
	 * Get request URI.
	 */
	protected function get_request_uri() {
		if ( ! empty( $this->request_uri ) ) {
			return;
		}
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$this->request_uri = trim( stripslashes( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
	}

	/**
	 * Encode any reserved characters in a data URI.
	 *
	 * This is only for script elements currently.
	 *
	 * @param string $uri The data that needs to be encoded for a data URI. This is NOT a URL.
	 * @return string The percent-encoded data URI.
	 */
	public function encode_data_uri( $uri ) {
		$reserved_characters = array( '%', '#', '<', '>' );
		$encoded_characters  = array( '%25', '%23', '%3C', '%3E' );
		if ( $uri ) {
			$uri = str_replace(
				$reserved_characters,
				$encoded_characters,
				$uri
			);
			// Minify the inline JS, to strip out comments that cause trouble sometimes.
			$minifier = new Minify\JS( $uri );
			$uri      = $minifier->minify();
		}
		return $uri;
	}
}
