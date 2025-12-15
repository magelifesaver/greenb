<?php
class WPSunshine_Address_Autocomplete_Options {

	protected static $notices = array();
	protected static $errors  = array();
	private $tabs;
	private $tab;
	protected $active_instance;

	public function __construct() {

		$this->includes();
		$this->init_hooks();

	}

	protected function includes() { }

	protected function init_hooks() {

		// Add settings page
		add_action( 'admin_menu', array( $this, 'settings_page_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Tabs
		add_action( 'admin_init', array( $this, 'set_tabs' ) );

		// Show settings tab
		add_action( 'wps_aa_options_tab_settings', array( $this, 'settings_tab' ) );

		// Save settings
		add_action( 'admin_init', array( $this, 'save_options' ) );
		add_action( 'admin_notices', array( $this, 'show_notices' ), 999 );

		// Header links
		add_action( 'wps_aa_header_links', array( $this, 'header_links' ) );

	}

	public function settings_page_menu() {
		add_options_page( __( 'Address Autocomplete', 'address-autocomplete-anything' ), __( 'Address Autocomplete', 'address-autocomplete-anything' ), 'manage_options', 'wps_aa', array( $this, 'options_page' ) );
	}

	public function admin_enqueue_scripts() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wps_aa' ) {
			wp_enqueue_style( 'wps-aa-admin', WPS_AA_URL . 'assets/css/admin.css' );
			wp_enqueue_script( 'select2', WPS_AA_URL . 'assets/js/select2/select2.min.js', array( 'jquery' ), '4.0.13' );
			wp_enqueue_style( 'select2', WPS_AA_URL . 'assets/js/select2/select2.min.css', '4.0.13' );
		}

	}

	public function set_tabs() {
		$this->tabs = apply_filters( 'wps_aa_tabs', array( 'settings' => __( 'Settings', 'confetti' ) ) );
		$this->tab  = array_key_first( $this->tabs );
		if ( isset( $_GET['tab'] ) ) {
			$this->tab = sanitize_key( $_GET['tab'] );
		}
	}

	public function header_links( $links ) {
		$links = array(
			'documentation' => array(
				'url'   => 'https://wpsunshine.com/support/',
				'label' => 'Documentation',
			),
			'review'        => array(
				'url'   => 'https://wordpress.org/support/plugin/address-autocomplete-anything/reviews/#new-post',
				'label' => 'Write a Review',
			),
			'feedback'      => array(
				'url'   => 'https://wpsunshine.com/feedback',
				'label' => 'Feedback',
			),
			'upgrade'       => array(
				'url'   => 'https://wpsunshine.com/plugins/address-autocomplete/?utm_source=plugin&utm_medium=banner&utm_content=upgrade&utm_campaign=aa_upgrade',
				'label' => 'Upgrade',
			),
		);
		return $links;
	}

	public function options_page() {
		$options = WPS_AA()->get_options( true );
		?>
		<div id="wps-aa-admin">

			<div class="wps-header">

				<a href="https://www.wpsunshine.com" target="_blank" class="wps-logo"><img src="<?php echo WPS_AA_URL; ?>/assets/images/logo.svg" alt="Address Autocomplete Anything by WP Sunshine" /></a>

				<?php
				$header_links = apply_filters( 'wps_aa_header_links', array() );
				if ( ! empty( $header_links ) ) {
					echo '<div id="wps-header-links">';
					foreach ( $header_links as $key => $link ) {
						echo '<a href="' . esc_url( $link['url'] ) . '" target="_blank" class="wps-header-link--' . esc_attr( $key ) . '">' . esc_html( $link['label'] ) . '</a>';
					}
					echo '</div>';
				}
				?>

				<?php if ( count( $this->tabs ) > 1 ) { ?>
				<nav class="wps-options-menu">
					<ul>
						<?php foreach ( $this->tabs as $key => $label ) { ?>
							<li
							<?php
							if ( $this->tab == $key ) {
								?>
  class="wps-options-active"<?php } ?>><a href="<?php echo admin_url( 'options-general.php?page=wps_aa&tab=' . $key ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php } ?>
					</ul>
				</nav>
				<?php } ?>

			</div>

			<div class="wrap wps-wrap">
				<h2></h2>
				<?php
				$form_url = admin_url( 'options-general.php?page=wps_aa&tab=' . $this->tab );
				if ( isset( $_GET['instance'] ) ) {
					$form_url = add_query_arg( 'instance', $_GET['instance'] );
				}
				?>
				<form method="post" action="<?php echo esc_url( $form_url ); ?>">
				<?php wp_nonce_field( 'wps_aa_options', 'wps_aa_options' ); ?>

				<?php do_action( 'wps_aa_options_before', $options, $this->tab ); ?>

				<?php do_action( 'wps_aa_options_tab_' . $this->tab ); ?>

				<?php do_action( 'wps_aa_options_after', $options, $this->tab ); ?>

				<p id="wps-options-submit">
					<input type="submit" value="<?php _e( 'Save Changes', 'confetti' ); ?>" class="button button-primary" />
					<?php do_action( 'wps_aa_options_submit' ); ?>
				</p>

				</form>
				<script>
				jQuery( document ).ready(function($) {

					$( 'select[name*="page"]' ).select2({
						placeholder: "<?php echo esc_js( 'Select a page', 'address-autocomplete-anything' ); ?>",
						allowClear: true
					});
					$( 'select[name*="countries"]' ).select2({
						placeholder: "<?php echo esc_js( 'Select up to 5 countries', 'address-autocomplete-anything' ); ?>",
						allowClear: true
					});

				});
				</script>
			</div>

		</div>
		<?php
	}

	public function settings_tab() {
		$lang = get_option( 'wps_aa_language' );
		?>

		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Google Maps API Key', 'address-autocomplete-anything' ); ?></th>
				<td>
					<input type="text" name="google_api_key" size="50" value="<?php echo esc_attr( get_option( 'wps_aa_google_api_key' ) ); ?>" />
					<span class="wps-description"><a href="https://wpsunshine.com/documentation/google-maps-api-key/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank"><?php _e( 'Learn how to get a Google Maps API key here', 'address-autocomplete-anything' ); ?></a></span>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Results Language', 'address-autocomplete-anything' ); ?></th>
				<td>
					<select name="language">
						<option value=""><?php _e( 'Default (No language specified)', 'address-autocomplete-anything' ); ?></option>
						<option value="ar" <?php selected( $lang, 'ar' ); ?>><?php _e( 'Arabic', 'address-autocomplete-anything' ); ?></option>
						<option value="bg" <?php selected( $lang, 'bg' ); ?>><?php _e( 'Bulgarian', 'address-autocomplete-anything' ); ?></option>
						<option value="bn" <?php selected( $lang, 'bn' ); ?>><?php _e( 'Bengali', 'address-autocomplete-anything' ); ?></option>
						<option value="ca" <?php selected( $lang, 'ca' ); ?>><?php _e( 'Catalan', 'address-autocomplete-anything' ); ?></option>
						<option value="cs" <?php selected( $lang, 'cs' ); ?>><?php _e( 'Czech', 'address-autocomplete-anything' ); ?></option>
						<option value="da" <?php selected( $lang, 'da' ); ?>><?php _e( 'Danish', 'address-autocomplete-anything' ); ?></option>
						<option value="de" <?php selected( $lang, 'de' ); ?>><?php _e( 'German', 'address-autocomplete-anything' ); ?></option>
						<option value="el" <?php selected( $lang, 'el' ); ?>><?php _e( 'Greek', 'address-autocomplete-anything' ); ?></option>
						<option value="en" <?php selected( $lang, 'en' ); ?>><?php _e( 'English', 'address-autocomplete-anything' ); ?></option>
						<option value="en-AU" <?php selected( $lang, 'en-AU' ); ?>><?php _e( 'English (Australian)', 'address-autocomplete-anything' ); ?></option>
						<option value="en-GB" <?php selected( $lang, 'en-GB' ); ?>><?php _e( 'English (Great Britain)', 'address-autocomplete-anything' ); ?></option>
						<option value="es" <?php selected( $lang, 'es' ); ?>><?php _e( 'Spanish', 'address-autocomplete-anything' ); ?></option>
						<option value="eu" <?php selected( $lang, 'eu' ); ?>><?php _e( 'Basque', 'address-autocomplete-anything' ); ?></option>
						<option value="fa" <?php selected( $lang, 'fa' ); ?>><?php _e( 'Farsi', 'address-autocomplete-anything' ); ?></option>
						<option value="fi" <?php selected( $lang, 'fi' ); ?>><?php _e( 'Finnish', 'address-autocomplete-anything' ); ?></option>
						<option value="fil" <?php selected( $lang, 'fil' ); ?>><?php _e( 'Filipino', 'address-autocomplete-anything' ); ?></option>
						<option value="fr" <?php selected( $lang, 'fr' ); ?>><?php _e( 'French', 'address-autocomplete-anything' ); ?></option>
						<option value="gl" <?php selected( $lang, 'gl' ); ?>><?php _e( 'Galician', 'address-autocomplete-anything' ); ?></option>
						<option value="gu" <?php selected( $lang, 'gu' ); ?>><?php _e( 'Gujarati', 'address-autocomplete-anything' ); ?></option>
						<option value="hi" <?php selected( $lang, 'hi' ); ?>><?php _e( 'Hindi', 'address-autocomplete-anything' ); ?></option>
						<option value="hr" <?php selected( $lang, 'hr' ); ?>><?php _e( 'Croatian', 'address-autocomplete-anything' ); ?></option>
						<option value="hu" <?php selected( $lang, 'hu' ); ?>><?php _e( 'Hungarian', 'address-autocomplete-anything' ); ?></option>
						<option value="id" <?php selected( $lang, 'id' ); ?>><?php _e( 'Indonesian', 'address-autocomplete-anything' ); ?></option>
						<option value="it" <?php selected( $lang, 'it' ); ?>><?php _e( 'Italian', 'address-autocomplete-anything' ); ?></option>
						<option value="iw" <?php selected( $lang, 'iw' ); ?>><?php _e( 'Hebrew', 'address-autocomplete-anything' ); ?></option>
						<option value="ja" <?php selected( $lang, 'ja' ); ?>><?php _e( 'Japanese', 'address-autocomplete-anything' ); ?></option>
						<option value="kn" <?php selected( $lang, 'kn' ); ?>><?php _e( 'Kannada', 'address-autocomplete-anything' ); ?></option>
						<option value="ko" <?php selected( $lang, 'ko' ); ?>><?php _e( 'Korean', 'address-autocomplete-anything' ); ?></option>
						<option value="lt" <?php selected( $lang, 'lt' ); ?>><?php _e( 'Lithuanian', 'address-autocomplete-anything' ); ?></option>
						<option value="lv" <?php selected( $lang, 'lv' ); ?>><?php _e( 'Latvian', 'address-autocomplete-anything' ); ?></option>
						<option value="ml" <?php selected( $lang, 'ml' ); ?>><?php _e( 'Malayalam', 'address-autocomplete-anything' ); ?></option>
						<option value="mr" <?php selected( $lang, 'mr' ); ?>><?php _e( 'Marathi', 'address-autocomplete-anything' ); ?></option>
						<option value="nl" <?php selected( $lang, 'nl' ); ?>><?php _e( 'Dutch', 'address-autocomplete-anything' ); ?></option>
						<option value="no" <?php selected( $lang, 'no' ); ?>><?php _e( 'Norwegian', 'address-autocomplete-anything' ); ?></option>
						<option value="pl" <?php selected( $lang, 'pl' ); ?>><?php _e( 'Polish', 'address-autocomplete-anything' ); ?></option>
						<option value="pt" <?php selected( $lang, 'pt' ); ?>><?php _e( 'Portuguese', 'address-autocomplete-anything' ); ?></option>
						<option value="pt-BR" <?php selected( $lang, 'pt-BR' ); ?>><?php _e( 'Portuguese (Brazil)', 'address-autocomplete-anything' ); ?></option>
						<option value="pt-PT" <?php selected( $lang, 'pt-PT' ); ?>><?php _e( 'Portuguese (Portugal)', 'address-autocomplete-anything' ); ?></option>
						<option value="ro" <?php selected( $lang, 'ro' ); ?>><?php _e( 'Romanian', 'address-autocomplete-anything' ); ?></option>
						<option value="ru" <?php selected( $lang, 'ru' ); ?>><?php _e( 'Russian', 'address-autocomplete-anything' ); ?></option>
						<option value="sk" <?php selected( $lang, 'sk' ); ?>><?php _e( 'Slovak', 'address-autocomplete-anything' ); ?></option>
						<option value="sl" <?php selected( $lang, 'sl' ); ?>><?php _e( 'Slovenian', 'address-autocomplete-anything' ); ?></option>
						<option value="sr" <?php selected( $lang, 'sr' ); ?>><?php _e( 'Serbian', 'address-autocomplete-anything' ); ?></option>
						<option value="sv" <?php selected( $lang, 'sv' ); ?>><?php _e( 'Swedish', 'address-autocomplete-anything' ); ?></option>
						<option value="ta" <?php selected( $lang, 'ta' ); ?>><?php _e( 'Tamil', 'address-autocomplete-anything' ); ?></option>
						<option value="te" <?php selected( $lang, 'te' ); ?>><?php _e( 'Telugu', 'address-autocomplete-anything' ); ?></option>
						<option value="th" <?php selected( $lang, 'th' ); ?>><?php _e( 'Thai', 'address-autocomplete-anything' ); ?></option>
						<option value="tl" <?php selected( $lang, 'tl' ); ?>><?php _e( 'Tagalog', 'address-autocomplete-anything' ); ?></option>
						<option value="tr" <?php selected( $lang, 'tr' ); ?>><?php _e( 'Turkish', 'address-autocomplete-anything' ); ?></option>
						<option value="uk" <?php selected( $lang, 'uk' ); ?>><?php _e( 'Ukrainian', 'address-autocomplete-anything' ); ?></option>
						<option value="vi" <?php selected( $lang, 'vi' ); ?>><?php _e( 'Vietnamese', 'address-autocomplete-anything' ); ?></option>
						<option value="zh-CN" <?php selected( $lang, 'zh-CN' ); ?>><?php _e( 'Chinese (Simplified)', 'address-autocomplete-anything' ); ?></option>
						<option value="zh-TW" <?php selected( $lang, 'zh-TW' ); ?>><?php _e( 'Chinese (Traditional)', 'address-autocomplete-anything' ); ?></option>
					</select>
					<span class="wps-description"><?php _e( 'Select a language for address results. If not specified, address results will be returned in the default language of the user\'s browser.', 'address-autocomplete-anything' ); ?></span>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Results Dropdown Title', 'address-autocomplete-anything' ); ?></th>
				<td>
					<input type="text" name="results_title" size="50" value="<?php echo esc_attr( get_option( 'wps_aa_results_title' ) ); ?>" />
					<span class="wps-description"><a href="https://wpsunshine.com/documentation/address-autocomplete-results-title/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank"><?php _e( 'What is this?', 'address-autocomplete-anything' ); ?></a></span>
				</td>
			</tr>

		</table>

		<?php
		$instances = WPS_AA()->get_instances();

		do_action( 'wps_aa_instances_nav', $instances );

		if ( empty( $this->active_instance ) ) {
			$this->active_instance = isset( $_GET['instance'] ) ? $_GET['instance'] : array_key_first( $instances );
		}

		foreach ( $instances as $key => $instance ) {
			if ( $this->active_instance != $key ) {
				continue;
			}
			?>

		<div class="wps-aa-instance" id="instance<?php echo esc_attr( $key ); ?>">
			<input type="hidden" name="instance" value="<?php echo esc_attr( $key ); ?>" />
			<table class="form-table">
				<tr>
					<th><?php _e( 'Label', 'address-autocomplete-anything' ); ?></th>
					<td>
						<input type="text" name="label" value="<?php echo esc_attr( $instance['label'] ); ?>" />
						<span class="wps-description"><?php _e( 'Label or description of where this is being used. For admin purposes only.', 'address-autocomplete-anything' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Initial field selector', 'address-autocomplete-anything' ); ?></th>
					<td>
						<input type="text" name="init" value="<?php echo esc_attr( $instance['init'] ); ?>"  placeholder="<?php echo esc_attr( __( 'CSS selector, e.g. #address1 or .city', 'address-autocomplete-anything' ) ); ?>" />
						<span class="wps-description"><a href="https://wpsunshine.com/documentation/finding-your-css-selectors/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank"><?php _e( 'Learn how to determine selector', 'address-autocomplete-anything' ); ?></a></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Data population', 'address-autocomplete-anything' ); ?></th>
					<td>
						<div id="wps-aa-fields">
							<?php
							$i = 1;
							if ( ! empty( $instance['fields'] ) ) {
								foreach ( $instance['fields'] as $key => $field ) {
									if ( empty( $field['selector'] ) ) {
										continue;
									}
									?>
								<div class="wps-aaa-field">
									<input type="text" name="fields[<?php echo $i; ?>][selector]" value="<?php echo esc_attr( $field['selector'] ); ?>" placeholder="<?php echo esc_attr( __( 'CSS selector, e.g. #address1 or .city', 'address-autocomplete-anything' ) ); ?>" />
									<input type="text" name="fields[<?php echo $i; ?>][data]" value="<?php echo ( ! empty( $field['data'] ) ) ? esc_attr( $field['data'] ) : ''; ?>" />
									<input type="number" step=".5" name="fields[<?php echo $i; ?>][delay]" value="<?php echo ( ! empty( $field['delay'] ) ) ? esc_attr( $field['delay'] ) : ''; ?>" placeholder="<?php echo esc_attr( __( 'Delay (ms)', 'address-autocomplete-anything' ) ); ?>" style="width: 80px;" />
									<span class="wps-aa-place-components">
										<a href="#" class="wps-aa-select-place-components"><?php _e( 'Select data', 'address-autocomplete-anything' ); ?></a>
									</span>
								</div>
									<?php
									$i++;
								}
							}
							?>
						</div>
						<p><a class="button" id="wps-aa-add-field"><?php _e( 'Add field', 'address-autocomplete-anything' ); ?></a></p>
						<div class="wps-description">
							<a href="https://wpsunshine.com/documentation/finding-your-css-selectors/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank"><?php _e( 'Learn what the different fields are', 'address-autocomplete-anything' ); ?></a>
							or <a href="https://wpsunshine.com/documentation/finding-your-css-selectors/?utm_source=plugin&utm_medium=link&utm_campaign=doc" target="_blank"><?php _e( 'Learn how to determine selector', 'address-autocomplete-anything' ); ?></a>
						</div>

						<div id="wps-aa-new-field" style="display: none;">
							<div class="wps-aaa-field">
								<input type="text" name="fields[%ID%][selector]" placeholder="<?php echo esc_attr( __( 'CSS selector, e.g. #address1 or .city', 'address-autocomplete-anything' ) ); ?>" /></label>
								<input type="text" name="fields[%ID%][data]" /></label>
								<input type="number" step=".5" name="fields[%ID%][delay]" placeholder="<?php echo esc_attr( __( 'Delay (ms)', 'address-autocomplete-anything' ) ); ?>" style="width: 80px;" />
								<span class="wps-aa-place-components">
									<a href="#" class="wps-aa-select-place-components"><?php _e( 'Select data', 'address-autocomplete-anything' ); ?></a>
								</span>
							</div>
						</div>

						<div id="wps-aa-place-components-options-container" style="display: none;">
							<div class="wps-aa-place-components-options">
								<?php
								$place_components = WPS_AA()->get_available_place_components();
								foreach ( $place_components as $component ) {
									echo '<div class="select-place-component">';
									if ( ! isset( $component['short_long'] ) ) {
										echo '<span>' . esc_html( $component['label'] ) . '</span>';
										echo '<a href="#" data-tag="{' . esc_js( $component['key'] ) . ':short_name}">' . __( 'Short', 'address-autocomplete-anything' ) . '</a>';
										echo '<a href="#" data-tag="{' . esc_js( $component['key'] ) . ':long_name}">' . __( 'Long', 'address-autocomplete-anything' ) . '</a>';
									} else {
										echo '<span>' . esc_html( $component['label'] ) . '</span>';
										echo '<span><a href="#" data-tag="{' . esc_js( $component['key'] ) . '}">' . __( 'Value', 'address-autocomplete-anything' ) . '</a></span>';
									}
									echo '</div>' . "\r\n";
								}
								?>
							</div>
						</div>
						<script>
						var field_count = <?php echo $i; ?>;
						// Det the default HTML for the tag selector window
						const new_field = jQuery( '#wps-aa-new-field' ).html();

						jQuery( document ).on( 'click', '#wps-aa-add-field', function(){
							jQuery( '#wps-aa-fields' ).append( new_field.replace( /%ID%/g, field_count ) );
							field_count++;
						});

						// Det the default HTML for the tag selector window
						const place_components_select = jQuery( '#wps-aa-place-components-options-container' ).html();

						// Toggle the tag selector window
						jQuery( document ).on( 'click', 'a.wps-aa-select-place-components', function() {
							jQuery( '.wps-aa-place-components-options' ).remove();
							if ( jQuery( this ).hasClass( 'open' ) ) {
								jQuery( '.wps-aa-place-components-options' ).remove();
							} else {
								let target = jQuery( this ).closest( '.wps-aa-place-components' );
								target.append( place_components_select );
							}
							jQuery( this ).toggleClass( 'open' );
							return false;
						});

						// Populate the input field with the selected tag
						jQuery( document ).on( 'click', '.select-place-component a', function(){
							let tag = jQuery( this ).data( 'tag' );
							let target_field = jQuery( this ).closest( '.wps-aaa-field' );
							let target_value = jQuery( 'input[name*="data"]', target_field ).val();
							target_value += tag;
							jQuery( 'input[name*="data"]', target_field ).val( target_value );
							jQuery( '.wps-aa-place-components-options' ).remove();
							return false;
						});

						// Remove any open tag selector windows if clicked outside the link
						jQuery( document ).on( 'click', function( event ) {
							jQuery( '.wps-aa-place-components-options' ).remove();
							jQuery( 'a.wps-aa-select-place-components' ).removeClass( 'open' );
						});
						</script>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Restrict to specific countries', 'address-autocomplete-anything' ); ?></th>
					<td>
						<select class="countries" name="allowed_countries[]" multiple="multiple">
							<?php
							$selected_countries = ( empty( $instance['allowed_countries'] ) ) ? array() : $instance['allowed_countries'];
							foreach ( $this->countries() as $code => $name ) {
								echo '<option value="' . esc_attr( $code ) . '" ' . selected( true, in_array( $code, $selected_countries ), false ) . '>' . esc_html( $name ) . '</option>';
							}
							?>
						</select>
						<span class="wps-description"><?php _e( 'Choose which countries to restrict addresses within', 'address-autocomplete-anything' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Restrict to page', 'address-autocomplete-anything' ); ?></th>
					<td>
						<?php
						$pages = get_pages();
						echo '<select name="allowed_page[]" class="pages-select2" multiple="multiple" style="width:100%;">';
						echo '<option value="">' . __( 'Do not restrict to a page', 'address-autocomplete-anything' ) . '</option>';
						foreach ( $pages as $page ) {
							$selected = in_array( $page->ID, (array) $instance['page'] ) ? ' selected="selected"' : '';
							echo '<option value="' . esc_attr( $page->ID ) . '"' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
						}
						echo '</select>';
						?>
						<span class="wps-description"><?php _e( 'Output necessary Javascript only on these pages, otherwise JavaScript is unnecessarily output on every page of your site', 'address-autocomplete-anything' ); ?></span>
						<script type="text/javascript">
						jQuery(document).ready(function($) {
							$(".pages-select2").select2();
						});
						</script>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Load delay', 'address-autocomplete-anything' ); ?></th>
					<td>
						<input type="number" step=".5" size="8" name="delay" value="<?php echo ( ! empty( $instance['delay'] ) ) ? esc_attr( $instance['delay'] ) : ''; ?>" />
						<span class="wps-description"><?php _e( 'In seconds, how long to delay loading of the address autocomplete.', 'address-autocomplete-anything' ); ?> <a href="https://wpsunshine.com/documentation/address-autocomplete-load-delay-option/" target="_blank">Learn more</a></span>
					</td>
				</tr>
			</table>
		</div>

			<?php
		}
	}

	public function save_options() {
		if ( ! isset( $_POST['wps_aa_options'] ) || ! wp_verify_nonce( $_POST['wps_aa_options'], 'wps_aa_options' ) ) {
			return;
		}

		$available_instances = WPS_AA()->get_instances();
		$instances           = array();
		foreach ( $available_instances as $key => $instance ) {

			// If no data posted, then let's just keep as is
			if ( empty( $_POST['instance'] ) || $_POST['instance'] != $key ) {
				$instances[ $key ] = $instance;
				continue;
			}

			$fields = array();
			foreach ( $_POST['fields'] as $field ) {
				if ( empty( $field['selector'] ) ) {
					continue;
				}
				$fields[] = array(
					'selector' => isset( $field['selector'] ) ? sanitize_text_field( stripslashes( $field['selector'] ) ) : '',
					'data'     => isset( $field['data'] ) ? sanitize_text_field( stripslashes( $field['data'] ) ) : '',
					'delay'    => isset( $field['delay'] ) && $field['delay'] !== '' ? floatval( $field['delay'] ) : '',
				);
			}
			$instances[ $key ] = array(
				'label'             => isset( $_POST['label'] ) ? sanitize_text_field( $_POST['label'] ) : '',
				'page'              => isset( $_POST['allowed_page'] ) ? array_map( 'intval', $_POST['allowed_page'] ) : '',
				'init'              => isset( $_POST['init'] ) ? sanitize_text_field( stripslashes( $_POST['init'] ) ) : '',
				'allowed_countries' => ( ! empty( $_POST['allowed_countries'] ) ) ? array_map( 'sanitize_text_field', $_POST['allowed_countries'] ) : '',
				'delay'             => isset( $_POST['delay'] ) ? sanitize_text_field( $_POST['delay'] ) : '',
				'fields'            => $fields,
			);

			$this->active_instance = $key;

		}

		$instances = apply_filters( 'wps_aa_save_instances', $instances );

		do_action( 'wps_aa_save_options' );

		// If all valid
		if ( count( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				$this->add_notice( $error, 'error' );
			}
		}

		update_option( 'wps_aa_instances', $instances );

		if ( isset( $_POST['google_api_key'] ) ) {
			update_option( 'wps_aa_google_api_key', sanitize_text_field( $_POST['google_api_key'] ) );
		}

		if ( isset( $_POST['language'] ) ) {
			update_option( 'wps_aa_language', sanitize_text_field( $_POST['language'] ) );
		}

		if ( isset( $_POST['results_title'] ) ) {
			update_option( 'wps_aa_results_title', sanitize_text_field( $_POST['results_title'] ) );
		}

		$this->add_notice( __( 'Settings saved!', 'address-autocomplete-anything' ) );

	}

	public static function add_notice( $text, $type = 'success' ) {
		self::$notices[] = array(
			'text' => $text,
			'type' => $type,
		);
	}

	public static function show_notices() {
		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . '"><p>' . wp_kses_post( $notice['text'] ) . '</p></div>';
			}
		}
	}

	private function countries() {
		return array(
			'AF' => 'Afghanistan',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, the Democratic Republic of the',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'CI' => "Cote D'Ivoire",
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Malvinas)',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and Mcdonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KP' => "Korea, Democratic People's Republic of",
			'KR' => 'Korea, Republic of',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => "Lao People's Democratic Republic",
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia, the Former Yugoslav Republic of',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia, Federated States of',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territory, Occupied',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'CS' => 'Serbia and Montenegro',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia and the South Sandwich Islands',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan, Province of China',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania, United Republic of',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Minor Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.s.',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe',
		);

	}

}

$wps_aa_options = new WPSunshine_Address_Autocomplete_Options();
