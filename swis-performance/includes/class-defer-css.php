<?php
/**
 * Class and methods to defer CSS.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enables plugin to filter CSS tags and defer them.
 */
final class Defer_CSS extends Page_Parser {

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * Register actions and filters for CSS Defer.
	 */
	public function __construct() {
		if ( \str_contains( add_query_arg( '', '' ), 'swis_ccss' ) ) {
			return;
		}
		if ( \str_contains( add_query_arg( '', '' ), 'swis_test_ccss' ) ) {
			return;
		}
		if ( ! $this->get_option( 'defer_css' ) ) {
			return;
		}
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		$this->validate_user_exclusions();
		$uri = \add_query_arg( '', '' );
		$this->debug_message( "request uri is $uri" );

		\add_filter( 'swis_skip_css_defer_by_page', array( $this, 'skip_page' ), 10, 2 );

		/**
		 * Allow pre-empting CSS defer by page.
		 *
		 * @param bool Whether to skip parsing the page.
		 * @param string $uri The URL of the page.
		 */
		if ( \apply_filters( 'swis_skip_css_defer_by_page', false, $uri ) ) {
			return;
		}

		// Overrides for user exclusions.
		\add_filter( 'swis_skip_css_defer', array( $this, 'skip_css_defer' ), 10, 2 );

		// Get all the script/css urls and rewrite them (if enabled).
		\add_filter( 'style_loader_tag', array( $this, 'defer_css' ), 20, 2 );
		\add_filter( 'swis_elements_link_tag', array( $this, 'defer_css' ) );
	}

	/**
	 * Validate the user-defined exclusions.
	 */
	public function validate_user_exclusions() {
		$user_exclusions = $this->get_option( 'defer_css_exclude' );
		if ( ! empty( $user_exclusions ) ) {
			if ( \is_string( $user_exclusions ) ) {
				$user_exclusions = array( $user_exclusions );
			}
			if ( \is_array( $user_exclusions ) ) {
				foreach ( $user_exclusions as $exclusion ) {
					if ( ! \is_string( $exclusion ) ) {
						continue;
					}
					$exclusion = \trim( $exclusion );
					if ( \str_starts_with( $exclusion, 'page:' ) ) {
						$this->user_page_exclusions[] = \str_replace( 'page:', '', $exclusion );
						continue;
					}
					$this->user_exclusions[] = $exclusion;
				}
			}
		}
	}

	/**
	 * Exclude CSS from being processed based on user specified list.
	 *
	 * @param boolean $skip Whether SWIS should skip processing.
	 * @param string  $tag The CSS link tag HTML.
	 * @return boolean True to skip the resource, unchanged otherwise.
	 */
	public function skip_css_defer( $skip, $tag ) {
		if ( function_exists( 'affwp_is_affiliate_portal' ) && affwp_is_affiliate_portal() ) {
			return true;
		}
		if ( $this->test_mode_active() ) {
			return true;
		}
		if ( $this->user_exclusions ) {
			foreach ( $this->user_exclusions as $exclusion ) {
				if ( \str_contains( $tag, $exclusion ) ) {
					$this->debug_message( __METHOD__ . "(); user excluded $tag via $exclusion" );
					return true;
				}
			}
		}
		if ( \str_contains( $tag, 'dashicons' ) && is_admin_bar_showing() ) {
			return true;
		}
		return $skip;
	}

	/**
	 * Rewrites a CSS link tag to be deferred.
	 *
	 * @param string $tag HTML for the CSS resource.
	 * @param string $handle The CSS/JS handle. Optional.
	 * @return string The deferred version of the resource, if it was allowed.
	 */
	public function defer_css( $tag, $handle = '' ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		if ( ! $this->is_frontend() ) {
			return $tag;
		}
		if ( \str_contains( $tag, 'admin-bar.min.css' ) ) {
			return $tag;
		}
		if ( \str_contains( $tag, 'async' ) ) {
			return $tag;
		}
		if ( \str_contains( $tag, 'defer' ) ) {
			return $tag;
		}
		if ( \str_contains( $tag, 'asset-clean' ) ) {
			return $tag;
		}
		if ( apply_filters( 'swis_skip_css_defer', false, $tag, $handle ) ) {
			return $tag;
		}
		if ( ! \str_contains( $tag, 'preload' ) && ! \str_contains( $tag, 'data-swis' ) ) {
			$this->debug_message( trim( $tag ) );
			$this->debug_message( 'valid tag, attempting to replace media=all' );
			$async_tag = str_replace( " media='all'", " media='print' data-swis='loading' onload='this.media=\"all\";this.dataset.swis=\"loaded\"'", $tag );
			if ( $tag === $async_tag ) {
				$this->debug_message( 'media=all not found, trying empty media' );
				$async_tag = str_replace( " media=''", " media='print' data-swis='loading' onload='this.media=\"all\";this.dataset.swis=\"loaded\"'", $tag );
			}
			// If we got a new tag, let's go!
			if ( $async_tag && $tag !== $async_tag ) {
				// Run it through for preloading if possible.
				$async_tag = $this->preload_css( $async_tag, $tag );
				$this->debug_message( 'tag altered, replacing with deferred version:' );
				$this->debug_message( trim( $async_tag ) );
				return $async_tag . '<noscript>' . trim( $tag ) . "</noscript>\n";
			}
		}
		return $tag;
	}

	/**
	 * Modify an async link/CSS tag for preloading.
	 *
	 * @param string $async_tag The async version of a <link...> tag.
	 * @param string $tag The original version of a <link...> tag.
	 * @return string The tag with a preloader added, if applicable.
	 */
	public function preload_css( $async_tag, $tag ) {
		if ( \str_contains( $tag, "rel='stylesheet'" ) ) {
			$allowed_to_preload = array(
				'avada-styles/',              // Avada dynamic CSS.
				'bb-plugin/cache/',           // Beaver Builder dynamic CSS.
				'bb-plugin/css/',             // Beaver Builder plugin CSS.
				'brizy/public/',              // Brizy dynamic CSS.
				'brizy-pro/public/',          // Brizy Pro dynamic CSS.
				'dist/block-library/',        // Gutenberg (stock WP) CSS.
				'build/block-library/',       // Gutenberg (plugin) CSS.
				'elementor/assets/css',       // Elementor plugin stock CSS.
				'elementor/css',              // Elementor dynamic CSS.
				'elementor-pro/assets/css',   // Elementor Pro stock CSS.
				'fusion-builder/',            // Avada Builder stock CSS.
				'fusion-core/',               // Avada Core stock CSS.
				'fusion-styles/',             // Avada dynamic CSS.
				'generateblocks/style',       // GenerateBlocks dynamic CSS.
				'/js_composer/assets/',       // WPBakery Page Builder CSS.
				'component-framework/oxygen', // Oxygen plugin CSS.
				'/oxygen/css/',               // Oxygen dynamic CSS.
				'siteorigin-panels/css/',     // SiteOrigin plugin CSS.
				'td-composer/assets',         // TagDiv Composer (builder from Newspaper Theme).
				'visualcomposer/public/',     // Visual Composer CSS.
				'wp-content/themes/',         // Theme CSS.
				'/zionbuilder/cache/',        // Zion Builder dynamic CSS.
			);
			$allowed_to_preload = \apply_filters( 'swis_defer_css_preload_list', $allowed_to_preload );
			foreach ( $allowed_to_preload as $allowed ) {
				if ( empty( $allowed ) ) {
					continue;
				}
				if ( \str_contains( $tag, $allowed ) ) {
					$href = $this->get_attribute( $tag, 'href' );
					\do_action( 'swis_replace_preload_url', $href, '' );
					$async_tag = \str_replace( array( " rel='stylesheet'", " id='" ), array( " rel='preload' as='style'", " data-id='" ), $tag ) . $async_tag;
					break;
				}
			}
		}
		return $async_tag;
	}
}
