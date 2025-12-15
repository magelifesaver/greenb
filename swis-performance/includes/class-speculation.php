<?php
/**
 * Class and methods to modify the Speculative Loading behavior of WordPress core.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

namespace SWIS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modifies the defaults for Speculative Loading, if available.
 */
final class Speculation extends Base {

	/**
	 * A list of user-defined exclusions, populated by validate_user_exclusions().
	 *
	 * @access protected
	 * @var array $user_exclusions
	 */
	protected $user_exclusions = array();

	/**
	 * Register actions and filters for DNS Prefetch.
	 */
	public function __construct() {
		parent::__construct();
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );

		if ( ! is_admin() ) {
			$this->validate_user_exclusions();
			add_filter( 'wp_speculation_rules_href_exclude_paths', array( $this, 'href_exclude_paths' ) );
			add_filter( 'wp_speculation_rules_configuration', array( $this, 'modify_rules_configuration' ) );
		}
	}

	/**
	 * Validate path exclusions specified by the user.
	 */
	public function validate_user_exclusions() {
		$user_exclusions = $this->get_option( 'speculation_exclude' );
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
					if ( str_contains( $exclusion, '//' ) ) {
						$exclusion_path = $this->parse_url( $exclusion, PHP_URL_PATH );
						$this->debug_message( "parsed $exclusion as a URL, got path $exclusion_path" );
						if ( ! empty( $exclusion_path ) ) {
							$exclusion = $exclusion_path;
						} else {
							continue;
						}
					}
					$this->user_exclusions[] = $exclusion;
				}
			}
		}
	}

	/**
	 * Exclude path from being prefetched/prerenderd by Speculative Loading.
	 *
	 * @param array $href_exclude_paths A list of paths which are already excluded from Speculative Loading.
	 * @return array The list of paths, with user-defined exclusions added.
	 */
	public function href_exclude_paths( $href_exclude_paths ) {
		foreach ( $this->user_exclusions as $exclusion ) {
			if ( is_string( $exclusion ) && ! empty( $exclusion ) ) {
				$href_exclude_paths[] = sanitize_text_field( $exclusion );
			}
		}
		return $href_exclude_paths;
	}

	/**
	 * Modify the Speculative Loading rules configuration.
	 *
	 * @param array $configuration The current configuration for Speculative Loading.
	 * @return array The modified configuration.
	 */
	public function modify_rules_configuration( $configuration ) {
		$this->debug_message( '<b>' . __METHOD__ . '()</b>' );
		$speculation_mode = $this->get_option( 'speculation_mode' );
		$valid_modes      = array( 'auto', 'prerender', 'prefetch', 'off' );
		if ( ! empty( $speculation_mode ) && 'off' === $speculation_mode ) {
			return null;
		}
		if ( ! empty( $speculation_mode ) && in_array( $speculation_mode, $valid_modes, true ) && 'auto' !== $speculation_mode ) {
			$this->debug_message( "setting speculation mode to $speculation_mode" );
			$configuration['mode'] = $speculation_mode;
		} else {
			$this->debug_message( 'using default speculation mode' );
		}
		$speculation_level = $this->get_option( 'speculation_level' );
		$valid_levels      = array( 'auto', 'conservative', 'moderate', 'eager' );
		if ( ! empty( $speculation_level ) && in_array( $speculation_level, $valid_levels, true ) && 'auto' !== $speculation_level ) {
			$this->debug_message( "setting speculation level to $speculation_level" );
			$configuration['eagerness'] = $speculation_level;
		} else {
			$this->debug_message( 'using default speculation level/eagerness' );
		}
		return $configuration;
	}
}
