<?php
/**
 * Class and methods to find various elements from the HTML and allow filtering by other classes.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to search the page content and filter various elements.
 */
final class Element_Filter extends Page_Parser {

	/**
	 * Register actions and filters for searching.
	 */
	public function __construct() {
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		// Hook onto the main output buffer filter.
		add_filter( $this->prefix . 'filter_page_output', array( $this, 'filter_page_output' ) );
	}

	/**
	 * Identify various elements in page content, and apply filters to them.
	 *
	 * @param string $content The page/post content.
	 * @return string The content, potentially altered.
	 */
	public function filter_page_output( $content ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( $this->is_json( $content ) ) {
			return $content;
		}

		$search_buffer = preg_replace( '/<noscript.*?\/noscript>/s', '', $content );
		// Look for link elements (stylesheets, not hyperlinks or anchors).
		$links = $this->get_elements_from_html( $search_buffer, 'link' );
		if ( $this->is_iterable( $links ) ) {
			$this->debug_message( 'found ' . count( $links ) . ' CSS links to run through filters' );
			foreach ( $links as $index => $link ) {
				if ( false === strpos( $link, 'stylesheet' ) && false === strpos( $link, '.css' ) ) {
					continue;
				}
				$handle = '';
				$href   = $this->get_attribute( $link, 'href' );
				// If there is an 'id' attribute, use that for the handle in the upcoming filter.
				$link_id = $this->get_attribute( $link, 'id' );
				if ( $link_id ) {
					$handle = $this->remove_from_end( $link_id, '-css' );
				}

				if ( ! empty( $href ) ) {
					if ( ! $handle && strpos( $href, 'fonts.googleapis.com' ) && strpos( $href, 'family=' ) ) {
						if ( preg_match( '/family=([+A-Za-z]+)/', $href, $possible_handle ) ) {
							if ( is_array( $possible_handle ) && ! empty( $possible_handle[1] ) ) {
								$handle = str_replace( '+', '', $possible_handle[1] );
							}
							$this->debug_message( "found google font, using $handle as handle" );
						}
						if ( ! $handle ) {
							// Don't bother with Google Fonts if we couldn't find a family/handle.
							continue;
						}
					} elseif ( ! $handle ) {
						$handle = $this->parse_url( $href, PHP_URL_PATH );
					}
					$this->debug_message( "running $href through filters with $handle" );
					$new_href = apply_filters( 'swis_elements_link_href', $href, $handle );
					if ( $new_href && $href !== $new_href ) {
						$this->debug_message( "changed to $new_href, updating" );
						$link = str_replace( $href, $new_href, $link );
					}
				}

				$this->debug_message( 'running link through filters:' );
				$this->debug_message( trim( $link ) );
				$link = apply_filters( 'swis_elements_link_tag', $link, $handle );
				if ( $link && $link !== $links[ $index ] ) {
					$this->debug_message( 'link modified:' );
					$this->debug_message( trim( $link ) );
					// Replace original element with modified version.
					$content = str_replace( $links[ $index ], $link, $content );
				} elseif ( ! $link ) {
					$this->debug_message( 'removing ' . $links[ $index ] );
					$content = str_replace( $links[ $index ], '', $content );
				}
			} // End foreach().
		} // End if();

		// Look for script elements (but we only want resources, not inline ones).
		$scripts = $this->get_script_elements_from_html( $search_buffer );
		if ( $this->is_iterable( $scripts ) ) {
			$this->debug_message( 'found ' . count( $scripts ) . ' script tags to run through filters' );
			foreach ( $scripts as $index => $script ) {
				$script_parts = $this->script_parts( $script );
				if ( ! $script_parts || empty( $script_parts['open'] ) ) {
					$this->debug_message( "could not parse $script, skipping" );
					continue;
				}
				$handle = '';
				// Used to ignore inline scripts, not any more...
				if ( false === strpos( $script_parts['open'], ' src' ) ) {
					/* continue; */
				}
				$src = $this->get_attribute( $script_parts['open'], 'src' );
				if ( ! empty( $src ) ) {
					$handle = $this->parse_url( $src, PHP_URL_PATH );
					$this->debug_message( "running $src through filters" );
					$new_src = apply_filters( 'swis_elements_script_src', $src );
					if ( $new_src && $src !== $new_src ) {
						$this->debug_message( "changed to $new_src, updating" );
						$script_open          = str_replace( $src, $new_src, $script_parts['open'] );
						$script               = str_replace( $script_parts['open'], $script_open, $script );
						$script_parts['open'] = $script_open;
					}
				}

				// If there is an 'id' attribute, use that for the handle in the upcoming filter.
				$script_id = $this->get_attribute( $script_parts['open'], 'id' );
				if ( $script_id ) {
					$handle = $this->remove_from_end( $script_id, '-js' );
				}

				// See if the element content is empty, or if this is an inline script.
				if ( ! empty( trim( $script_parts['content'] ) ) ) {
					$inline = true;
				}

				$this->debug_message( 'running script through filters:' );
				$this->debug_message( trim( $script ) );
				$script = apply_filters( 'swis_elements_script_tag', $script, $handle );
				if ( $script && $script !== $scripts[ $index ] ) {
					$this->debug_message( 'script modified:' );
					$this->debug_message( trim( $script ) );
					// Replace original element with modified version.
					$content = str_replace( $scripts[ $index ], $script, $content );
				} elseif ( ! $script ) {
					$this->debug_message( 'removing ' . substr( $scripts[ $index ], 0, 300 ) );
					$content = str_replace( $scripts[ $index ], '', $content );
				}
			} // End foreach().
		} // End if();
		return $content;
	}
}
