<?php
class WPSunshine_Address_Autocomplete_GravityForms {

	private $instances = array();

	public function __construct() {

		if ( ! class_exists( 'GFForms' ) ) {
			return;
		}

		// add_filter( 'wps_aa_addons', array( $this, 'register_addon' ) );
		add_filter( 'gform_tooltips', array( $this, 'tooltips' ) );
		add_action( 'gform_field_advanced_settings', array( $this, 'address_field_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( $this, 'field_settings_js' ) );
		add_action( 'gform_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 2 );
		add_action( 'wps_aa_instances', array( $this, 'add_instances' ) );

	}

	public function register_addon( $addons ) {
		$addons['gravityforms'] = __( 'Gravity Forms', 'address-autocomplete-anything' );
		return $addons;
	}

	public function tooltips( $tooltips ) {
		$tooltips['wps_aa_gravityforms'] = __( 'Enables the WP Sunshine Google Address Autocomplete to this address field', 'address-autocomplete-anything' );
		return $tooltips;
	}

	public function address_field_settings( $position, $form_id ) {
		if ( $position == 100 ) {
			?>
		<li class="wps-aa-gravityforms field_setting">
			<input type="checkbox" id="field_enable_wps_aa" onchange="SetFieldProperty( 'wps_aa', this.checked );" />
			<label for="field_enable_wps_aa" class="inline"><?php esc_html_e( 'Enable WPSunshine Address Autocomplete', 'gravityforms' ); ?><?php gform_tooltip( 'wps_aa_gravityforms' ); ?></label>
		</li>
			<?php
		}
	}

	public function field_settings_js() {
		?>
		<script type="text/javascript">

		 (function($) {

		 $(document).bind( 'gform_load_field_settings', function( event, field, form ) {

		 // populates the stored value from the field back into the setting when the field settings are loaded
		 $( '#field_enable_wps_aa' ).attr( 'checked', field['wps_aa'] == true );

		 // if our desired condition is met, we show the field setting; otherwise, hide it
		 if( GetInputType( field ) == 'address' ) {
			 $( '.wps-aa-gravityforms' ).show();
		 } else {
			 $( '.wps-aa-gravityforms' ).hide();
		 }

		 } );

		 })(jQuery);

		</script>
		<?php
	}

	public function enqueue_scripts( $form, $is_ajax ) {

		$fields = array();

		foreach ( $form['fields'] as $field ) {
			if ( $field['type'] == 'address' ) {
				if ( ! empty( $field['wps_aa'] ) && $field['wps_aa'] == 1 ) {

					$fields = array();
					// Build instance data
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_1',
						'data'     => '{address1:long_name}',
					);
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_2',
						'data'     => '{address2:long_name}',
					);
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_3',
						'data'     => '{locality:long_name}',
					);
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_4',
						'data'     => '{administrative_area_level_1:long_name}',
					);
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_5',
						'data'     => '{postal_code:long_name}',
					);
					$fields[] = array(
						'selector' => '#input_' . $form['id'] . '_' . $field['id'] . '_6',
						'data'     => '{country:long_name}',
					);

					$this->instances[ 'gravityforms_' . $form['id'] . '_' . $field['id'] ] = array(
						'label'             => 'Gravity Forms',
						'init'              => '#input_' . $form['id'] . '_' . $field['id'] . '_1',
						'page'              => '',
						'allowed_countries' => '',
						'fields'            => $fields,
					);

				}
			}
		}

	}

	public function add_instances( $instances ) {

		if ( empty( $this->instances ) ) {
			return $instances;
		}

		$instances = array_merge( $instances, $this->instances );

		return $instances;

	}

}

$wps_aa_gravityforms = new WPSunshine_Address_Autocomplete_GravityForms();
