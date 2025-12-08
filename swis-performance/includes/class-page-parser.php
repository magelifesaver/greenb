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
	 * The directory in which to store cache files.
	 *
	 * @access protected
	 * @var string $cache_dir
	 */
	protected $cache_dir = '';

	/**
	 * The URL to the directory where cache files are stored.
	 *
	 * @access protected
	 * @var string $cache_dir_url
	 */
	protected $cache_dir_url = '';

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

		if ( preg_match_all( '#(?P<noscript_tag><noscript[^>]*?>\s*)(?P<img_tag><img[^>]*?\s+?src\s*=\s*["\'](?P<img_url>[^\s]+?)["\'][^>]*?>){1}(?:\s*</noscript>)?#is', $content, $images ) ) {
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
		if ( preg_match_all( '#(?:<picture[^>]*?>\s*)(?:<source[^>]*?>)+(?:.*?</picture>)?#is', $content, $pictures ) ) {
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
		if ( preg_match_all( '#<style[^>]*?>.*?</style>#is', $content, $styles ) ) {
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
		if ( preg_match_all( '#<script[^>]*?>.*?</script>#is', $content, $scripts ) ) {
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
		if ( preg_match( '#(?P<open><script[^>]*?>)(?P<content>.*?)(?P<close></script>)#is', $element, $script_parts ) ) {
			foreach ( $script_parts as $key => $unused ) {
				// Simplify the output as much as possible.
				if ( is_numeric( $key ) ) {
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
		if ( ! ctype_alpha( $tag_name ) ) {
			return array();
		}
		if ( preg_match_all( '#<' . $tag_name . '\s[^\\\\>]+?>#is', $content, $elements ) ) {
			return $elements[0];
		}
		return array();
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
	 * Get a CSS background-image URL.
	 *
	 * @param string $attribute An element's style attribute. Do not pass a full HTML element.
	 * @return string The URL from the background/background-image property.
	 */
	public function get_background_image_url( $attribute ) {
		if ( ( false !== strpos( $attribute, 'background:' ) || false !== strpos( $attribute, 'background-image:' ) ) && false !== strpos( $attribute, 'url(' ) ) {
			if ( preg_match( '#url\(([^)]+)\)#', $attribute, $prop_match ) ) {
				return trim( html_entity_decode( $prop_match[1], ENT_QUOTES | ENT_HTML401 ), "'\"\t\n\r " );
			}
		}
		return '';
	}

	/**
	 * Get CSS background-image rules from HTML.
	 *
	 * @param string $html The code containing potential background images.
	 * @return array The URLs with background/background-image properties.
	 */
	public function get_background_images( $html ) {
		if ( ( false !== strpos( $html, 'background:' ) || false !== strpos( $html, 'background-image:' ) ) && false !== strpos( $html, 'url(' ) ) {
			if ( preg_match_all( '#background(-image)?:\s*?[^;}]*?url\([^)]+\)#', $html, $matches ) ) {
				return $matches[0];
			}
		}
		return array();
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
