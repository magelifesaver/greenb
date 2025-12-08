<?php
namespace MBViews\Location;

use Error;

class Validator {
	private $view;
	private $type;

	public function __construct( $type ) {
		$this->type = $type;
	}

	public function set_view( $view ) {
		$this->view = $view;
	}

	public function is_template_type() {
		if ( 'code' === $this->type ) {
			return true;
		}
		return 'singular' === $this->type ? is_singular() : ! is_singular();
	}

	public function validate() {
		$result = 'code' === $this->type ? $this->validate_code() : $this->validate_groups();
		return apply_filters( 'mbv_location_validate', $result, $this->view, $this->type );
	}

	private function validate_code() {
		$code = trim( get_post_meta( $this->view->ID, 'code', true ) );

		if ( '' === $code ) {
			return true;
		}

		if ( false === stristr( $code, 'return' ) ) {
			$code = "return ( $code );";
		}

		$result = false;
		try {
			$result = eval( $code ); // @codingStandardsIgnoreLine.
		} catch ( Error $e ) {
			trigger_error( $e->getMessage(), E_USER_WARNING ); // @codingStandardsIgnoreLine.
		}
		return $result;
	}

	private function validate_groups() {
		$groups = get_post_meta( $this->view->ID, "{$this->type}_locations", true );
		if ( empty( $groups ) ) {
			return false;
		}
		foreach ( $groups as $group ) {
			if ( ! $this->validate_group( $group ) ) {
				return false;
			}
		}
		return true;
	}

	private function validate_group( $group ) {
		$method = "validate_rule_{$this->type}";
		foreach ( $group as $rule ) {
			if ( $this->{$method}( $rule ) ) {
				return true;
			}
		}
		return false;
	}

	private function validate_rule_archive( $rule ) {
		list ( $type, $subtype ) = explode( ':', $rule['name'] );
		if ( 'general' === $type ) {
			if ( 'author' === $subtype ) {
				return is_author();
			}
			if ( 'date' === $subtype ) {
				return is_year() || is_month() || is_day();
			}
			if ( 'search' === $subtype ) {
				return is_search();
			}
			return true;
		}
		if ( $type !== get_post_type() ) {
			return false;
		}
		if ( 'archive' === $subtype ) {
			return is_post_type_archive( $type );
		}

		return ( is_tax( $subtype ) && 'all' === $rule['value'] )
			|| ( 'category' === $subtype && 'all' === $rule['value'] )
			|| ( 'category' === $subtype && is_category( $rule['value'] ) )
			|| ( 'post_tag' === $subtype && 'all' === $rule['value'] )
			|| ( 'post_tag' === $subtype && is_tag( $rule['value'] ) )
			|| is_tax( $subtype, (int) $rule['value'] );
	}

	private function validate_rule_singular( $rule ) {
		list ( $type, $subtype ) = explode( ':', $rule['name'] );
		if ( 'general' === $type ) {
			return true;
		}
		if ( $type !== get_post_type() ) {
			return false;
		}
		if ( 'post' === $subtype ) {
			return in_array( $rule['value'], [ 'all', get_the_ID() ] ); // @codingStandardsIgnoreLine.
		}
		return has_term( $rule['value'], $subtype, null );
	}
}
