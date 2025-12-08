<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds\Admin\Screens\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Admin\Screens\Traits\Loads_WooCommerce_Scripts;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Reward_Configured;
use Kestrel\Account_Funds\Scoped\Carbon\CarbonImmutable;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Redirect;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Screens\Screen;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress;
use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Edit screen for editing reward configuration types.
 *
 * @since 4.0.0
 *
 * @method get_page_title( bool $editing = false )
 */
abstract class Reward_Screen extends Screen {
	use Loads_WooCommerce_Scripts;

	/** @var string default reward type, should be overwritten by concrete types */
	protected const REWARD_TYPE = Reward_Type::REWARD;

	/** @var Reward_Type */
	protected Reward_Type $reward_type;

	/** @var bool */
	protected bool $editing = false;

	/** @var string */
	protected string $save_nonce_action;

	/** @var string */
	protected string $save_nonce_name;

	/** @var Notice[] */
	protected array $notices = [];

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param string $id
	 * @param string $title
	 */
	protected function __construct( string $id = '', string $title = '' ) {

		parent::__construct( $id, $title );

		$this->reward_type       = Reward_Type::make( static::REWARD_TYPE );
		$this->save_nonce_action = sprintf( 'save_store_credit_%s_configuration', $this->reward_type->value() );
		$this->save_nonce_name   = sprintf( 'save_store_credit_%s_configuration_nonce', $this->reward_type->value() );

		static::add_filter( 'woocommerce_screen_ids', [ $this, 'load_woocommerce_scripts' ] );
	}

	/**
	 * Returns a description for the edit screen.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	abstract protected function get_edit_screen_description() : string;

	/**
	 * Returns a list of award triggers for the reward configuration type.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, string> value-label pairs
	 */
	protected function get_reward_triggers() : array {

		$triggers = [];

		foreach ( $this->reward_type->awarded_on() as $trigger ) {
			$triggers[ $trigger->value() ] = $trigger->label();
		}

		return $triggers;
	}

	/**
	 * Returns explanations about each trigger the reward can be configured with.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return string
	 */
	protected function get_reward_triggers_description( Reward $reward ) : string {

		// reward implementations of this class can provide explanations about each trigger they offer
		return '';
	}

	/**
	 * Returns explanations about the limits the reward can be configured with.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return string
	 */
	protected function get_award_limits_description( Reward $reward ) : string {

		$cases = [];
		$value = $reward->is_unique() ? 'once_per_customer' : 'unlimited';

		if ( $reward instanceof Milestone ) :
			$value = $reward->is_limited_to_once_per_product() ? 'once_per_product' : $value;

			$cases = [
				'unlimited'         => __( 'A customer will receive store credit every time they review a product.', 'woocommerce-account-funds' ),
				'once_per_customer' => __( 'A customer will receive store credit whe they review any product once only.', 'woocommerce-account-funds' ),
				'once_per_product'  => __( 'A customer will receive store credit only once per product they review.', 'woocommerce-account-funds' ),
			];
		elseif ( $reward instanceof Cashback ) :
			$cases = [
				'unlimited'         => __( 'A customer will receive store credit every time they pay for an eligible order.', 'woocommerce-account-funds' ),
				'once_per_customer' => __( 'A customer will receive store credit only once for an eligible order they have paid for.', 'woocommerce-account-funds' ),
				'once_per_product'  => __( 'A customer will receive store credit only once for each eligible product they purchase in any order.', 'woocommerce-account-funds' ),
			];
		endif;

		ob_start();

		foreach ( $cases as $option => $description ) :

			$style = 'display: block; clear: both; font-size: 12px; line-height: 24px; padding: 9px 0 0;';

			$style = $value === $option ? $style : $style . ' display: none;';

			?>
			<span class="award-limit-description"
				data-award-limit="<?php echo esc_attr( $option ); ?>"
				style="<?php echo esc_attr( $style ); ?>">
				<?php echo esc_html( $description ); ?>
			</span>
			<?php

		endforeach;

		return ob_get_clean();
	}

	/**
	 * Returns a formatted date string for an item.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable $source_date
	 * @return string
	 */
	protected function format_date( CarbonImmutable $source_date ) : string {

		return sprintf(
			/* translators: Placeholders: %1$s - date, %2$s - time */
			__( '%1$s at %2$s', 'woocommerce-account-funds' ),
			wp_date( wc_date_format(), $source_date->getTimestamp() ),
			wp_date( wc_time_format(), $source_date->getTimestamp() )
		);
	}

	/**
	 * Determines if this is an edit screen.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	protected function is_edit_screen() : bool {

		return ! empty( $_GET['edit'] ) || ! empty( $_GET['add_new'] );
	}

	/**
	 * Determines if this is a list screen.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	protected function is_list_screen() : bool {

		return ! $this->is_edit_screen();
	}

	/**
	 * Outputs the screen content.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function output() : void {

		wp_enqueue_script( self::plugin()->handle( 'store-credit-rewards-admin' ), self::plugin()->assets_url( 'js/admin/store-credit-rewards.min.js' ), [ 'jquery' ], self::plugin()->version(), [ 'in_footer' => true ] );
		wp_enqueue_style( self::plugin()->handle( 'store-credit-rewards-admin' ), self::plugin()->assets_url( 'css/admin/store-credit-rewards.min.css' ), [], self::plugin()->version() );

		if ( $this->is_list_screen() ) {
			$this->output_list_screen();
		} elseif ( $this->is_edit_screen() ) {
			$this->output_edit_screen();
		}
	}

	/**
	 * Outputs the list screen content.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function output_list_screen() : void {

		if ( isset( $_GET['delete'], $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'delete' ) ) { // phpcs:ignore
			$this->delete_item( intval( $_GET['delete'] ) );
		} elseif ( isset( $_GET['trash'], $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'trash' ) ) { // phpcs:ignore
			$this->trash_item( intval( $_GET['trash'] ) );
		} elseif ( isset( $_GET['restore'], $_GET['_wpnonce'] ) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'restore' ) ) { // phpcs:ignore
			$this->restore_item( intval( $_GET['restore'] ) );
		} elseif ( isset( $_GET['delete_all'], $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'bulk-' . sanitize_key( $this->reward_type->label_plural() ) ) ) { // phpcs:ignore
			$this->empty_trash();
		}

		$list_table = new Rewards_Table( [
			'singular'    => $this->reward_type->label_singular(),
			'plural'      => $this->reward_type->label_plural(),
			'ajax'        => false,
			'screen'      => WordPress\Admin::current_screen(),
			'reward_type' => $this->reward_type,
		] );

		$add_new_item = $this->get_page_title();

		?>
		<div class="wrap woocommerce store-credit-rewards <?php echo sanitize_html_class( $this->reward_type->label_plural() ); ?>">
			<form method="get" id="mainform" action="">
				<h1 class="wp-heading-inline"><?php echo esc_html( $this->reward_type->label_plural() ); ?></h1>
				<a href="<?php echo esc_url( Reward::add_new_item_url( $this->reward_type->value() ) ); ?>" class="page-title-action"><?php echo esc_html( $add_new_item ); ?></a>
				<hr class="wp-header-end">
				<input type="hidden" name="page" value="<?php echo esc_attr( $this->get_id() ); ?>" />
				<?php $list_table->prepare_items(); ?>
				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sends a credit configuration item to the trash.
	 *
	 * @since 4.0.0
	 *
	 * @param int $reward_id ID of the reward configuration to delete
	 * @return void
	 */
	private function trash_item( int $reward_id ) : void {

		$reward = Reward::find( $reward_id );

		if ( ! $reward || $reward->get_type() !== $this->reward_type->value() ) {
			return;
		}

		$reward = $reward->trash();

		if ( $reward->is_trashed() ) {
			/* translators: Placeholder: %1$s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.), %2$s - opening <a> link tag, %3$s closning </a> link tag */
			$notice = Notice::success( sprintf( __( '1 %1$s moved to trash. %2$sUndo%3$s', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_singular() ), '<a href="' . esc_url( $reward->restore_item_url() ) . '" class="undo">', '</a>' ) );
		} else {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::error( sprintf( __( 'Could not move %s to trash.', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_singular() ) ) );
		}

		$notice->print();
	}

	/**
	 * Restores a credit configuration item from the trash..
	 *
	 * @since 4.0.0
	 *
	 * @param int $reward_id ID of the credit resource to restore
	 * @return void
	 */
	private function restore_item( int $reward_id ) : void {

		$reward = Reward::find( $reward_id );

		if ( ! $reward || $reward->get_type() !== $this->reward_type->value() ) {
			return;
		}

		$reward = $reward->restore();

		if ( ! $reward->is_trashed() ) {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::success( sprintf( __( '1 %s restored from trash.', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_singular() ) ) );
		} else {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::error( sprintf( __( 'Could not restore %s from trash.', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_singular() ) ) );
		}

		$notice->print();
	}

	/**
	 * Sends a credit configuration item to the trash.
	 *
	 * @since 4.0.0
	 *
	 * @param int $reward_id ID of the reward configuration to delete
	 * @return void
	 */
	private function delete_item( int $reward_id ) : void {

		$reward = Reward::find( $reward_id );

		if ( ! $reward || $reward->get_type() !== $this->reward_type->value() ) {
			return;
		}

		$deleted = $reward->delete();

		if ( $deleted ) {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::success( sprintf( __( '%s permanently deleted', 'woocommerce-account-funds' ), $this->reward_type->label_singular() ) );
		} else {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::error( sprintf( __( 'Could not delete %s permanently.', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_singular() ) ) );
		}

		$notice->print();
	}

	/**
	 * Empties the trash and permanently deletes all trashed rewards of the current type.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function empty_trash() : void {

		$rewards = Reward::find_many( ['type' => $this->reward_type->value(), 'deleted' => true ] );
		$deleted = 0;

		foreach ( $rewards as $reward ) {
			$deleted += (int) $reward->delete();
		}

		if ( 0 === $deleted ) {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$notice = Notice::warning( sprintf( __( 'No %s permanently deleted.', 'woocommerce-account-funds' ), lcfirst( $this->reward_type->label_plural() ) ) );
		} else {
			$notice = Notice::success( sprintf(
				/* translators: Placeholder: %d - number of deleted items, %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
				_n( '%1$d %2$s permanently deleted.', '%1$d %2$s permanently deleted.', $deleted, 'woocommerce-account-funds' ),
				$deleted,
				lcfirst( $deleted > 1 ? $this->reward_type->label_plural() : $this->reward_type->label_singular() )
			) );
		}

		$notice->without_title()->print();
	}

	/**
	 * Outputs the edit screen content for editing a configuration or creating a new one.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function output_edit_screen() : void {

		$reward_id = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
		$reward    = $reward_id ? Reward::find( $reward_id ) : null;

		if ( $reward ) {
			$this->editing = true;
		} else {
			$this->editing = false;

			$reward = $this->reward_type->seed();
		}

		$reward->set_type( $this->reward_type->value() );

		$configuration_data_hook   = self::plugin()->hook( 'store_credit_reward_data' );
		$configuration_status_hook = self::plugin()->hook( 'store_credit_reward_status' );

		add_meta_box(
			'store-credit-reward-data',
			__( 'Configuration', 'woocommerce-account-funds' ),
			fn() => $this->output_configuration_metabox( $reward ),
			$configuration_data_hook,
			'normal'
		);

		add_meta_box(
			'store-credit-reward-status',
			__( 'Details', 'woocommerce-account-funds' ),
			fn() => $this->output_status_metabox( $reward ),
			$configuration_status_hook,
			'side'
		);

		$this->save_item( $reward );
		$this->validate_item( $reward );

		?>
		<div class="wrap woocommerce store-credit-reward <?php echo sanitize_html_class( $this->reward_type->label_singular() ); ?>">
			<form method="post" id="mainform" action="" enctype="multipart/form-data">

				<h1 class="wp-heading-inline"><?php echo esc_html( $this->get_page_title( $this->editing ) ); ?></h1>

				<?php if ( $this->editing ) : ?>
					<a class="page-title-action" href="<?php
					echo esc_url( Reward::add_new_item_url( $this->reward_type->value() ) );

					?>
">
						<?php echo esc_html( $this->get_page_title() ); ?>
					</a>
				<?php else : ?>
					<div class="notice notice-info"><?php echo wp_kses_post( wpautop( '<span class="dashicons dashicons-editor-help"></span> &nbsp; ' . $this->get_edit_screen_description() ) ); ?></div>
				<?php endif; ?>

				<hr class="wp-header-end">

				<?php wp_nonce_field( $this->save_nonce_action, $this->save_nonce_name ); ?>

				<input type="hidden" name="id" value="<?php echo esc_attr( $reward->get_id() ?: '' ); ?>" />

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content" style="position: relative;">
							<div id="titlediv">
								<div id="titlewrap">
									<?php

									/* translators: Placeholder: %s - singular name of the store credit type being created */
									$name_placeholder = sprintf( __( '%s name', 'woocommerce-account-funds' ), $this->reward_type->label_singular() );

									/* translators: Context: Label for the store credit label input field (indicates how the store credit will be displayed in the frontend) */
									$label = __( 'Store credit label', 'woocommerce-account-funds' );

									?>
									<label id="title-prompt-text" for="title" style="display: none;"><?php echo esc_html( $label ); ?></label>
									<input type="text" name="label" size="30" value="<?php echo esc_attr( $reward->get_label() ?: '' ); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php echo esc_attr( $name_placeholder ); ?>" required="required"/>
								</div>
							</div>
							<div class="inside">
							</div>
						</div>

						<div id="postbox-container-1" class="postbox-container">
							<?php do_meta_boxes( $configuration_status_hook, 'side', $reward ); ?>
						</div>

						<div id="postbox-container-2" class="postbox-container">
							<?php do_meta_boxes( $configuration_data_hook, 'normal', $reward ); ?>
						</div>

					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Returns the configuration tabs for the reward configuration screen metabox.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, array{
	 *     label: string,
	 *     callback: callable,
	 * }>
	 */
	protected function get_configuration_tabs() : array {

		return [
			'general' => [
				/* translators: Context: General settings */
				'label'    => $this->reward_type->label_singular(),
				'callback' => [ $this, 'output_configuration_general_panel' ],
			],
			'rules'   => [
				/* translators: Context: Store credit configuration rules */
				'label'    => __( 'Eligibility', 'woocommerce-account-funds' ),
				'callback' => [ $this, 'output_configuration_rules_panel' ],
			],
		];
	}

	/**
	 * Outputs the configuration metabox.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return void
	 */
	protected function output_configuration_metabox( Reward $reward ) : void {

		$tabs = $this->get_configuration_tabs();

		?>
		<div class="panel-wrap data">
			<ul class="wc-tabs">
				<?php

				foreach ( $tabs as $key => $tab ) :

					$classes = [
						sanitize_html_class( $key . '_options' ),
						sanitize_html_class( $key . '-tab' ),
						'general' === $key ? 'active' : '',
					];

					?>
					<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
						<a href="#store-credit-configuration-<?php echo esc_attr( $key ); ?>-panel"><span><?php echo esc_html( $tab['label'] ); ?></span></a>
					</li>
					<?php

				endforeach;

				?>
			</ul>
			<?php

			foreach ( $tabs as $key => $tab ) :

				$callback = $tab['callback'];

				?>
				<div id="store-credit-configuration-<?php echo esc_attr( $key ); ?>-panel" class="panel woocommerce_options_panel">
					<?php $callback( $reward ); ?>
				</div>
				<?php

			endforeach;

			?>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Outputs the status metabox.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return void
	 */
	protected function output_status_metabox( Reward $reward ) : void {

		?>
		<div class="submitbox" id="submitpost">
			<div id="minor-publishing-actions">
				<div class="clear"></div>
			</div>
			<div id="misc-publishing-actions">
				<?php

				$status_options = Reward_Status::options();

				if ( ! $reward->is_depleted() ) :
					unset( $status_options[ Reward_Status::DEPLETED ] );

					$status_tooltip = wc_help_tip( __( 'Customers will be awarded store credit as configured while its status is active.', 'woocommerce-account-funds' ) );
				else :
					$status_tooltip = wc_help_tip( __( 'This store credit resource is deplete and it cannot be awarded to customers because it has reached its maximum usage limit. Increase the limit to reactivate it.', 'woocommerce-account-funds' ) );
				endif;

				?>
				<label><?php esc_html_e( 'Status:', 'woocommerce-account-funds' ); ?>&nbsp;
					<select name="status">
						<?php foreach ( $status_options as $value => $label ) : ?>
							<option value="<?php
							echo esc_attr( $value );

							?>
"
								<?php

								selected( $reward->is_status( $value ) );
								disabled( Reward_Status::DEPLETED !== $value && $reward->is_depleted() );

								?>
								>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php echo wp_kses_post( $status_tooltip ); ?>
				</label>
				<?php

				if ( ! $reward->is_new() ) :

					if ( $reward->has_award_limit() ) :
						$usage_count = ( (string) $reward->get_award_count() ) . ' / ' . ( (string) $reward->get_award_limit() );

						$usage_tooltip = wc_help_tip( sprintf(
							/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
							__( 'The number of times this store credit %s has been awarded to customers, out of the maximum usage limit allowed by its configuration.', 'woocommerce-account-funds' ),
							strtolower( $this->reward_type->label_singular() )
						) );
					else :
						$usage_count   = (string) $reward->get_award_count();
						$usage_tooltip = wc_help_tip( sprintf(
							/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
							__( 'The number of times this store credit %s has been awarded to customers.', 'woocommerce-account-funds' ),
							strtolower( $this->reward_type->label_singular() )
						) );
					endif;

					$awarded_total   = floatval( $reward->get_award_total() );
					$awarded_tooltip = wc_help_tip( sprintf(
						/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
						__( 'The total amount of store credit awarded to customers from this %s.', 'woocommerce-account-funds' ),
						strtolower( $this->reward_type->label_singular() )
					) );

					$details = [
						/* translators: Context: Follows number of times store credit has been awarded to customers */
						__( 'Awarded times:', 'woocommerce-account-funds' ) => $usage_count . $usage_tooltip,
						__( 'Total awarded:', 'woocommerce-account-funds' ) => wc_price( $awarded_total, [ 'in_span' => false, 'currency' => WooCommerce::currency()->code() ] ) . $awarded_tooltip,
						__( 'Created on:', 'woocommerce-account-funds' )    => $this->format_date( $reward->get_created_at() ),
						__( 'Last modified:', 'woocommerce-account-funds' ) => $this->format_date( $reward->get_modified_at() ),
					];

					foreach ( $details as $label => $value ) :
						echo '<div class="misc-pub-section">' . esc_html( $label ) . ' <span><strong>' . wp_kses_post( $value ) . '</strong></span></div>';

					endforeach;

				endif;

				?>
				<div class="clear"></div>
			</div>
			<div id="major-publishing-actions">

				<?php if ( $this->editing && ! $reward->is_trashed() ) : ?>

					<div id="delete-action">
						<a class="submitdelete deletion" href="<?php
						echo esc_url( $reward->delete_item_url() );

						?>
">
							<?php esc_attr_e( 'Move to trash', 'woocommerce-account-funds' ); ?>
						</a>
					</div>

				<?php endif; ?>

				<div id="publishing-action">
					<?php

					/* translators: Context: Button to create or update a credit configuration */
					$button_label = $reward->is_new() ? __( 'Create', 'woocommerce-account-funds' ) : __( 'Update', 'woocommerce-account-funds' );

					?>
					<input type="submit" name="save" id="publish" class="button button-primary button-large" value="<?php echo esc_attr( $button_label ); ?>" />
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Outputs the configuration general options.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return void
	 */
	protected function output_configuration_general_panel( Reward $reward ) : void {

		?>
		<div class="options_group">

			<?php

			if ( $reward->is_new() ) :
				echo '<p><em>' . esc_html__( 'Set the value of the store credit and choose the event that will trigger the reward.', 'woocommerce-account-funds' ) . '</em></p>';

			endif;

			$currency_symbol = WooCommerce::currency()->symbol();

			woocommerce_wp_select( [
				'id'                => 'amount_type',
				'label'             => __( 'Amount type', 'woocommerce-account-funds' ),
				'class'             => 'wc-enhanced-select',
				'style'             => 'width: 80%; max-width: 370px;',
				'value'             => $reward->is_percentage() ? 'percentage' : 'fixed',
				'options'           => [
					/* translators: Placeholder: %s - currency symbol for the store credit amount to be specified afterwards */
					'fixed'      => sprintf( __( 'Fixed (%s)', 'woocommerce-account-funds' ), $currency_symbol ),
					/* translators: Context: percentage value of the store credit amount to be specified afterwards */
					'percentage' => __( 'Percentage (%)', 'woocommerce-account-funds' ),
				],
				'custom_attributes' => ! $this->reward_type->supports_percentage_amount() ? [
					'disabled' => 'disabled',
				] : [],
			] );

			woocommerce_wp_text_input( [
				'id'            => 'percentage_amount',
				/* translators: Context: Percentage amount of store credit awarded to customers */
				'label'         => __( 'Amount (%)', 'woocommerce-account-funds' ),
				'type'          => 'text',
				'data_type'     => 'decimal',
				'value'         => wc_format_localized_decimal( strval( max( 0, $reward->get_amount() ) ?: '10.00' ) ),
				'desc_tip'      => true,
				/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
				'description'   => sprintf( __( 'Percentage amount awarded as store credit to customers that meet the conditions defined by the %s eligibility rules.', 'woocommerce-account-funds' ), strtolower( $this->reward_type->label_singular() ) ),
				'wrapper_class' => $reward->is_percentage() ? '' : 'hidden',
			] );

			woocommerce_wp_text_input( [
				'id'            => 'fixed_amount',
				/* translators: Placeholder: %s - currency symbol, in the context of store credit amount awarded to customers */
				'label'         => sprintf( __( 'Amount (%s)', 'woocommerce-account-funds' ), $currency_symbol ),
				'type'          => 'text',
				'data_type'     => 'price',
				'value'         => wc_format_localized_price( strval( max( 0, $reward->get_amount() ) ?: '1.00' ) ),
				'desc_tip'      => true,
				/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
				'description'   => sprintf( __( 'Fixed amount awarded as store credit to customers that meet the conditions defined by the %s eligibility rules.', 'woocommerce-account-funds' ), strtolower( $this->reward_type->label_singular() ) ),
				'wrapper_class' => $reward->is_percentage() ? 'hidden' : '',
			] );

			woocommerce_wp_select( [
				'id'          => 'award_cap',
				'label'       => __( 'Award cap', 'woocommerce-account-funds' ),
				'class'       => 'wc-enhanced-select',
				'style'       => 'width: 80%; max-width: 370px;',
				'desc_tip'    => true,
				'description' => __( 'Choose whether to limit the number of times this store credit can be awarded to customers.', 'woocommerce-account-funds' ),
				'value'       => $reward->has_award_limit() ? 'award_limit' : 'unlimited',
				'options'     => [
					'unlimited'   => sprintf(
						/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
						__( 'Keep awarding this %s indefinitely', 'woocommerce-account-funds' ),
						strtolower( $this->reward_type->label_singular() )
					),
					'award_limit' => sprintf(
						/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
						__( 'Limit the number of times the %s will trigger in the shop', 'woocommerce-account-funds' ),
						strtolower( $this->reward_type->label_singular() )
					),
				],
			] );

			woocommerce_wp_text_input( [
				'id'                => 'award_limit',
				/* translators: Context: Limit to the number of times store credit can be awarded to customers */
				'label'             => __( 'Maximum awards', 'woocommerce-account-funds' ),
				'type'              => 'number',
				'value'             => $reward->has_award_limit() ? intval( $reward->get_award_limit() ) : '',
				'desc_tip'          => true,
				'description'       => __( 'The maximum number of times the store credit can be awarded across all customers until depletion.', 'woocommerce-account-funds' ),
				'custom_attributes' => [
					'min'  => '',
					'step' => '1',
				],
			] );

			woocommerce_wp_select( [
				'id'          => 'trigger',
				'label'       => __( 'Award trigger', 'woocommerce-account-funds' ),
				'class'       => 'wc-enhanced-select',
				'style'       => 'width: 80%; max-width: 370px;',
				'value'       => $reward->get_trigger(),
				'options'     => $this->get_reward_triggers(),
				'desc_tip'    => false,
				'description' => $this->get_reward_triggers_description( $reward ),
			] );

			?>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Validates the credit configuration item.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return void
	 */
	protected function validate_item( Reward $reward ) : void {

		if ( ! empty( $_GET['edited'] ) || ! empty( $_GET['created'] ) ) {
			/* translators: Placeholder: %s - label of the credit type (e.g. "Cashback", "Milestone", "Reward", etc.) */
			$this->notices[] = Notice::success( sprintf( __( '%s successfully saved.', 'woocommerce-account-funds' ), $this->reward_type->label_singular() ), ['title' => '' ] );
		}

		$this->output_notices();
	}

	/**
	 * Outputs any errors that occurred during the save process.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function output_notices() : void {

		if ( empty( $this->notices ) ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			$notice->print();
		}
	}

	/**
	 * Saves the credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return bool
	 */
	protected function process_item( Reward &$reward ) : bool {

		if ( empty( $_POST ) || ! isset( $_POST['save'], $_POST[ $this->save_nonce_name ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ $this->save_nonce_name ] ), $this->save_nonce_action ) ) {
			return false;
		}

		$status        = Reward_Status::make( isset( $_POST['status'] ) ? wc_clean( wp_unslash( $_POST['status'] ) ) : Reward_Status::default_value() );
		$trigger       = Transaction_Event::make( isset( $_POST['trigger'] ) ? wc_clean( wp_unslash( $_POST['trigger'] ) ) : key( $this->get_reward_triggers() ) );
		$award_cap     = isset( $_POST['award_cap'] ) ? wc_clean( wp_unslash( $_POST['award_cap'] ) ) : 'unlimited';
		$award_limit   = ! empty( $_POST['award_limit'] ) ? max( 1, intval( $_POST['award_limit'] ) ) : null;
		$is_percentage = wc_string_to_bool( isset( $_POST['amount_type'] ) && 'percentage' === $_POST['amount_type'] );
		$raw_amount    = $is_percentage ? wc_clean( wp_unslash( $_POST['percentage_amount'] ?: 0 ) ) : wc_clean( wp_unslash( $_POST['fixed_amount'] ?: 0 ) ); // phpcs:ignore
		$amount        = max( 0.0, floatval( wc_format_decimal( $raw_amount, wc_get_price_decimals() ) ) );
		$label         = sanitize_text_field( isset( $_POST['label'] ) ? wc_clean( wp_unslash( $_POST['label'] ) ) : '' );

		$reward->set_label( $label );
		$reward->set_status( $status->value() );
		$reward->set_trigger( $trigger->value() );
		$reward->set_amount( $amount );
		$reward->set_percentage( $is_percentage );

		if ( $award_limit && $award_cap === 'award_limit' ) {
			$reward->set_award_limit( $award_limit );
		} else {
			$reward->set_award_limit( null );
		}

		// depletes or restores the reward configuration based on the award limit set and award count
		if ( $award_limit && $award_limit <= $reward->get_award_count() ) {
			$reward->set_status( Reward_Status::DEPLETED );
		} elseif ( $reward->is_depleted() ) {
			$reward->set_status( $status->value() !== Reward_Status::DEPLETED ? $status->value() : Reward_Status::INACTIVE );
		}

		if ( $reward->is_status( Reward_Status::ACTIVE ) && ! $reward->is_trashed() ) {
			First_Store_Credit_Reward_Configured::trigger();
		}

		return true;
	}

	/**
	 * Saves the reward configuration item to storage.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $reward
	 * @return void
	 */
	protected function save_item( Reward &$reward ) : void {

		$success = $this->process_item( $reward );

		if ( ! $success ) {
			return;
		}

		$reward->save();

		try {
			Redirect::to( $reward->edit_item_url( [$this->editing ? 'edited' : 'created' => 'success' ] ) );
		} catch ( Exception $exception ) {
			return;
		}
	}

}
