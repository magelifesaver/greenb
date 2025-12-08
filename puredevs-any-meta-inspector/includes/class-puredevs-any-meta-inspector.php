<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Puredevs_Any_Meta_Inspector
 * @subpackage Puredevs_Any_Meta_Inspector/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Puredevs_Any_Meta_Inspector
 * @subpackage Puredevs_Any_Meta_Inspector/includes
 * @author     puredevs <#>
 */
class Puredevs_Any_Meta_Inspector {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Puredevs_Any_Meta_Inspector_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PUREDEVS_ANY_META_INSPECTOR_VERSION' ) ) {
			$this->version = PUREDEVS_ANY_META_INSPECTOR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'puredevs-any-meta-inspector';

		$this->pdami_load_dependencies();
		$this->pdami_set_locale();
		$this->pdami_define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Puredevs_Any_Meta_Inspector_Loader. Orchestrates the hooks of the plugin.
	 * - Puredevs_Any_Meta_Inspector_i18n. Defines internationalization functionality.
	 * - Puredevs_Any_Meta_Inspector_Admin. Defines all hooks for the admin area.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdami_load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-puredevs-any-meta-inspector-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-puredevs-any-meta-inspector-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-puredevs-any-meta-inspector-admin.php';

		$this->loader = new Puredevs_Any_Meta_Inspector_Loader();
		
		add_action( 'current_screen', array( $this, 'pdami_current_screen' ) );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Puredevs_Any_Meta_Inspector_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdami_set_locale() {

		$plugin_i18n = new Puredevs_Any_Meta_Inspector_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'pdami_load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdami_define_admin_hooks() {

		$plugin_admin = new Puredevs_Any_Meta_Inspector_Admin( $this->pdami_get_plugin_name(), $this->pdami_get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pdami_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pdami_enqueue_scripts' );
		
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function pdami_run() {
		$this->loader->pdami_loader_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function pdami_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Puredevs_Any_Meta_Inspector_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function pdami_get_version() {
		return $this->version;
	}
	
	/**
	 * Check what admin screen currently visited.
	 *
	 * @since     1.0.0
	 */
	public function pdami_current_screen() {
		global $pagenow;
		$currentScreen = get_current_screen();
		
		if( isset($pagenow) && $pagenow == 'post.php' ) {
			
			add_action( 'add_meta_boxes', array( $this, 'pdami_add_meta_boxes' ), 99, 2 );
			
		}
		
		if( isset( $pagenow ) && ( $pagenow == 'profile.php' || $pagenow == 'user-edit.php' ) ) {
			
			add_action( 'show_user_profile', array( $this, 'pdami_show_user_metadata' ), 99, 1 );
			
			add_action( 'edit_user_profile', array( $this, 'pdami_show_user_metadata' ), 99, 1 );
			
		}
		
		if( isset( $pagenow ) && $pagenow == 'term.php' ) {
			
			if( isset( $_GET[ 'taxonomy' ] ) ){
				
				$taxonomy = sanitize_text_field( $_GET[ 'taxonomy' ] );
				
				add_action( $taxonomy . '_edit_form', array( $this, 'pdami_show_term_metadata' ), 99, 1 );
				
			}
			
		}
		
		if( isset( $pagenow ) && $pagenow == 'comment.php' ) {
			
			add_action( 'add_meta_boxes_comment', array( $this, 'pdami_show_comment_metadata' ), 99, 1 );
			
		}
	}
	
	/**
	 * add meta box for post, page and custom post type.
	 *
	 * @since    1.0.0
	 */
	public function pdami_add_meta_boxes( $post_type, $post ) {

		if ( ! isset( $post->ID ) ) {

			return;
		}

		$show_meta_cap = apply_filters( 'pdami_show_metabox_capability', 'manage_options', $post );
		
		$can_show_meta = current_user_can( $show_meta_cap, $post->ID );

		if ( ! $can_show_meta ) {

			return;

		} elseif ( ! apply_filters( 'pdami_show_metabox_post_type', true, $post_type ) ) {

			return;
		}

		$metabox_id      = 'pdami';
		
		$metabox_title   = __( ''.ucfirst($post_type).' Metadata', 'puredevs-any-meta-inspector' );
		
		$metabox_screen  = $post_type;
		
		$metabox_context = 'normal';
		
		$metabox_priority = 'low';

		add_meta_box( $metabox_id, $metabox_title, array( $this, 'pdami_show_metabox' ), $metabox_screen, $metabox_context, $metabox_priority );
	}
	
	/**
	 * show meta data for post, page and custom post type.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_metabox( $post ) {
		
		if ( empty( $post->ID ) ) {
			return;
		}

		$post_meta = get_post_meta( $post->ID );
		if( !empty( $post_meta ) ):
			$this->pdami_show_meta_keys_and_values( $post_meta, 'post' );
		else:
		?>
			<p><?php _e( 'No meta data found.', 'puredevs-any-meta-inspector' ); ?></p>
		<?php
		endif;
	}
	
	/**
	 * show meta data for user.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_user_metadata( $user ) {
		
		if ( ! isset( $user->ID ) ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		$show_meta_cap = apply_filters( 'pdami_show_metabox_capability', 'manage_options', $current_user );
		
		$can_show_meta = current_user_can( $show_meta_cap, $current_user->ID );

		if ( ! $can_show_meta ) {
			return;
		}
		
		$metabox_id      = 'pdami';
		
		$metabox_title   = __( 'User Metadata', 'puredevs-any-meta-inspector' );
		
		$metabox_screen  = 'pdami-show-user-meta';
		
		$metabox_context = 'normal';
		
		$metabox_priority    = 'low';
		
		add_meta_box( $metabox_id, $metabox_title, array( $this, 'pdami_show_user_metabox' ), $metabox_screen, $metabox_context, $metabox_priority );

		echo '<div class="metabox-holder">' . "\n";

		do_meta_boxes( $metabox_screen, 'normal', $user );

		echo "\n" . '</div><!-- .metabox-holder -->' . "\n";
	}
	
	/**
	 * show meta data for user
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_user_metabox( $user ) {

		if ( empty( $user->ID ) ) {
			return;
		}

		$user_meta = get_user_meta( $user->ID );
		if( !empty( $user_meta ) ):
			$this->pdami_show_meta_keys_and_values( $user_meta, 'user' );
		else:
		?>
			<p><?php _e( 'No meta data found.', 'puredevs-any-meta-inspector' ); ?></p>
		<?php
		endif;
	}
	
	/**
	 * show meta data for taxonomy and custom taxonomy terms.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_term_metadata( $term ) {
		
		if ( ! isset( $term->term_id ) ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		$show_meta_cap = apply_filters( 'pdami_show_metabox_capability', 'manage_options', $current_user );
		
		$can_show_meta = current_user_can( $show_meta_cap, $current_user->ID );

		if ( ! $can_show_meta ) {
			return;
		}
		
		$metabox_id      = 'pdami';
		
		$metabox_title   = __( ''.$term->name.' Metadata', 'puredevs-any-meta-inspector' );
		
		$metabox_screen  = 'pdami-show-term-meta';
		
		$metabox_context = 'normal';
		
		$metabox_priority    = 'low';
		
		add_meta_box( $metabox_id, $metabox_title, array( $this, 'pdami_show_term_metabox' ), $metabox_screen, $metabox_context, $metabox_priority );

		echo '<div class="metabox-holder">' . "\n";

		do_meta_boxes( $metabox_screen, 'normal', $term );

		echo "\n" . '</div><!-- .metabox-holder -->' . "\n";
	}
	
	/**
	 * show meta data for taxonomy and custom taxonomy terms.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_term_metabox( $term ) {

		if ( empty( $term->term_id ) ) {
			return;
		}

		$term_meta   = get_term_meta( $term->term_id );
		if( !empty( $term_meta ) ):
			$this->pdami_show_meta_keys_and_values( $term_meta, 'term' );
		else:
		?>
			<p><?php _e( 'No meta data found.', 'puredevs-any-meta-inspector' ); ?></p>
		<?php
		endif;
	}
	
	/**
	 * show meta data for comments.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_comment_metadata( $comment ) {
		
		if ( ! isset( $comment->comment_ID ) ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		
		$show_meta_cap = apply_filters( 'pdami_show_metabox_capability', 'manage_options', $current_user );
		
		$can_show_meta = current_user_can( $show_meta_cap, $current_user->ID );

		if ( ! $can_show_meta ) {
			return;
		}
		
		$metabox_id      = 'pdami';
		
		$metabox_title   = __( 'Comment Metadata', 'puredevs-any-meta-inspector' );
		
		$metabox_screen  = 'comment';
		
		$metabox_context = 'normal';
		
		$metabox_priority    = 'low';
		
		add_meta_box( $metabox_id, $metabox_title, array( $this, 'pdami_show_comment_metabox' ), $metabox_screen, $metabox_context, $metabox_priority );

	}
	
	/**
	 * show meta data for comments.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_comment_metabox( $comment ) {

		if ( empty( $comment->comment_ID ) ) {

			return;
		}

		$comment_meta = get_comment_meta( $comment->comment_ID );
		if( !empty( $comment_meta ) ):
			$this->pdami_show_meta_keys_and_values( $comment_meta, 'comment' );
		else:
		?>
			<p><?php _e( 'No meta data found.', 'puredevs-any-meta-inspector' ); ?></p>
		<?php
		endif;
	}
	
	/**
	 * output function for the meta data.
	 *
	 * @since    1.0.0
	 */
	public function pdami_show_meta_keys_and_values( $meta_data, $type ){
		?>
		<table>
			<thead>
				<tr>
					<th class="h-key"><?php _e( 'Key', 'puredevs-any-meta-inspector' ); ?></th>
					<th class="h-value"><?php _e( 'Value', 'puredevs-any-meta-inspector' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php 
			foreach( $meta_data as $key => $values ) :
				if ( apply_filters( 'pdami_ignore_'.$type.'_meta_key', false, $key ) )
					continue;
			?>
				<?php foreach( $values as $value ) : ?>
				<?php
					$value = maybe_unserialize($value);
					$value = var_export( $value, true );
				?>
				<tr>
					<td class="h-key"><?php echo esc_html( $key ); ?></td>
					<td class="h-value"><?php echo esc_html( $value ); ?></td>
				</tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

}
