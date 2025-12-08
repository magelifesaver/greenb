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

use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WP_List_Table;

/**
 * Table for displaying reward configurations.
 *
 * @since 4.0.0
 */
final class Rewards_Table extends WP_List_Table {

	/** @var Reward_Type */
	protected Reward_Type $reward_type;

	/**
	 * Constructor for the credit configuration table.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 */
	public function __construct( $args = [] ) {

		if ( ! isset( $args['reward_type'] ) || ! $args['reward_type'] instanceof Reward_Type ) {
			$args['reward_type'] = Reward_Type::make( $args['reward_type'] ?: null );
		}

		$this->reward_type = $args['reward_type'];

		parent::__construct( $args );
	}

	/**
	 * Returns the list of columns to display.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {

		return [
			'cb'          => '',
			/* translators: Context: Store credit configuration name */
			'name'        => __( 'Name', 'woocommerce-account-funds' ),
			/* translators: Context: Store credit configuration status */
			'status'      => __( 'Status', 'woocommerce-account-funds' ),
			/* translators: Context: Store credit financial amount (e.g. milestone, reward...) or percentage (e.g. cashback) */
			'amount'      => __( 'Amount', 'woocommerce-account-funds' ),
			/* translators: Context: Event name upon which store credit is awarded to a customer */
			'trigger'     => __( 'Awarded upon', 'woocommerce-account-funds' ),
			/* translators: Context: Number of times a store credit has been awarded to customers */
			'award_count' => __( 'Awarded times', 'woocommerce-account-funds' ),
			/* translators: Context: Total amount of store credit awarded to customers */
			'award_total' => __( 'Awarded total', 'woocommerce-account-funds' ),
		];
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function prepare_items() {

		$this->_column_headers = [ $this->get_columns(), [], []];

		$args   = ['type' => $this->reward_type->value(), 'status' => null ];
		$status = $this->get_queried_status();

		if ( ! in_array( $status, [ 'all', 'any', 'trash' ], true ) ) {
			$args['status']  = $this->get_queried_status();
			$args['deleted'] = false;
		} elseif ( 'trash' === $status ) {
			$args['deleted'] = true;
		} else {
			$args['deleted'] = false;
		}

		$this->items = Reward::find_many( $args )->get_items();

		$this->set_pagination_args( [
			'total_items' => count( $this->items ),
			'per_page'    => 0,
		] );
	}

	/**
	 * Gets the column for the checkbox of this reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed|Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_cb( $item ) {

		if ( ! $item instanceof Reward ) :
			return '';

		endif;

		ob_start();

		?>
		<input type="hidden" name="bulk_action[]" value="<?php echo esc_attr( (string) $item->get_id() ); ?>" />
		<?php

		return ob_get_clean();
	}

	/**
	 * Gets the column for the reward configuration name.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_name( Reward $item ) : string {

		ob_start();

		$actions = [];

		if ( ! $item->is_trashed() ) :

			/* translators: Context: Button label to edit a credit configuration */
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( $item->edit_item_url() ), esc_html__( 'Edit', 'woocommerce-account-funds' ) );

			/* translators: Context: Button label to soft-delete a credit configuration */
			$actions['trash'] = sprintf( '<span class="trash"></span><a href="%s" class="submitdelete">%s</a></span>', esc_url( $item->trash_url() ), esc_html__( 'Trash', 'woocommerce-account-funds' ) );

			?>
			<a href="<?php echo esc_url( $item->edit_item_url() ); ?>" class="row-title" aria-label="<?php esc_attr_e( 'Credit configuration name', 'woocommerce-account-funds' ); ?>"><?php echo esc_html( $item->get_label() ); ?></a>
			<?php

		else :

			/* translators: Context: Button label to restore a credit configuration from the trash */
			$actions['restore'] = sprintf( '<span class="untrash"><a href="%s">%s</a></span>', esc_url( $item->restore_item_url() ), esc_html__( 'Restore', 'woocommerce-account-funds' ) );
			/* translators: Context: Button label to permanently delete a credit configuration */
			$actions['delete'] = sprintf( '<span class="delete"></span><a href="%s" class="submitdelete">%s</a></span>', esc_url( $item->delete_item_url() ), esc_html__( 'Permanently delete', 'woocommerce-account-funds' ) );

			echo '<strong><span>' . esc_html( $item->get_label() ) . '</span></strong>';

		endif;

		?>
		<br>
		<div class="row-actions">
			<?php foreach ( $actions as $action => $link ) : ?>

				<span class="<?php
				echo esc_attr( $action );

				?>
">
					<?php echo wp_kses_post( $link ); ?>
				</span>

				<?php if ( 'delete' !== $action && 'trash' !== $action ) : ?>
					<span class="separator">|</span>
					<?php
				endif;

				?>

			<?php endforeach; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Gets the column for the status of the reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_status( Reward $item ) : string {

		$status = Reward_Status::make( $item->get_status() );

		return '<mark class="reward-status status-' . sanitize_html_class( $status->value() ) . ' tips" data-tip="' . esc_attr( $status->description( $this->reward_type->label_singular() ) ) . '"><span>' . esc_html( $status->label() ) . '</span></mark>';
	}

	/**
	 * Gets the column for the amount of store credit awarded.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_amount( Reward $item ) : string {

		$is_percentage = $item->is_percentage();

		return wp_kses_post( sprintf( '%1$s%2$s', wc_price( $item->get_amount(), [ 'in_span' => false, 'currency' => $is_percentage ? 'false' : $item->get_currency() ] ), $item->is_percentage() ? '%' : '' ) );
	}

	/**
	 * Gets the column for the corresponding trigger event that will reward the store credit to a customer.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_trigger( Reward $item ) : string {

		$trigger = esc_html( Transaction_Event::make( $item->get_trigger() )->label() );

		if ( $item->is_unique() ) {
			$trigger .= '<br><small>' . esc_html__( 'Limited to once per customer', 'woocommerce-account-funds' ) . '</small>';
		} elseif ( $item instanceof Milestone && $item->is_limited_to_once_per_product() ) {
			$trigger .= '<br><small>' . esc_html__( 'Limited to once per product', 'woocommerce-account-funds' ) . '</small>';
		}

		return $trigger;
	}

	/**
	 * Gets the column for the total number of times store credit has been awarded to customers for this configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_award_count( Reward $item ) : string {

		if ( $item->has_award_limit() ) :

			/* translators: Placeholders: %1$s - current count of awarded store credit across customers, %2$s - remaining available redemptions */
			return esc_html( sprintf( __( '%1$s out of %2$s', 'woocommerce-account-funds' ), $item->get_award_count(), $item->get_award_limit() ) );

		else :

			return esc_html( (string) $item->get_award_count() );

		endif;
	}

	/**
	 * Gets the column for the total amount of store credit awarded to customers for this configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $item the credit configuration object in row
	 * @return string HTML
	 */
	protected function column_award_total( Reward $item ) : string {

		return wc_price( floatval( $item->get_award_total() ), [ 'in_span' => false, 'currency' => $item->get_currency() ] );
	}

	/**
	 * Returns the queried reward status from the request.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private function get_queried_status() : string {

		if ( ! isset( $_GET['status'] ) || ! is_string( $_GET['status'] ) || 'all' === $_GET['status'] ) {
			return 'all';
		}

		if ( 'trash' === $_GET['status'] ) {
			return 'trash';
		}

		return Reward_Status::make( sanitize_text_field( wp_unslash( $_GET['status'] ) ) )->value();
	}

	/**
	 * Outputs HTML for the reward configuration filters.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @param 'bottom'|'top'|mixed $which either 'top' or 'bottom'
	 * @return void
	 */
	public function extra_tablenav( $which ) {

		if ( 'top' !== $which ) :

			if ( $this->get_queried_status() !== 'trash' ) :
				return;

			endif;

			?>
			<input type="submit" name="delete_all" id="delete_all" class="button apply" value="<?php esc_attr_e( 'Empty trash', 'woocommerce-account-funds' ); ?>" />
			<?php

			return;

		endif;

		echo '<ul class="subsubsub">';

		$current_status = $this->get_queried_status();

		/* translators: Placeholder: %d - count of all credit configuration tyes */
		$statuses = [ 'all' => sprintf( __( 'All (%d)', 'woocommerce-account-funds' ), Reward::count( ['type' => $this->reward_type->value(), 'deleted' => false ] ) ) ];

		foreach ( Reward_Status::values() as $status ) :

			$status = Reward_Status::make( $status );

			$statuses[ $status->value() ] = sprintf( '%s (%d)', $status->label(), Reward::count( [
				'type'    => $this->reward_type->value(),
				'status'  => $status->value(),
				'deleted' => false,
			] ) );

		endforeach;

		/* translators: Placeholder: %d - count of trashed credit configurations */
		$statuses['trash'] = sprintf( __( 'Trash (%d)', 'woocommerce-account-funds' ), Reward::count( ['type' => $this->reward_type->value(), 'deleted' => true ] ) );

		foreach ( $statuses as $value => $label ) :

			$current = $current_status === $value ? 'current' : '';

			echo '<li class="' . esc_attr( $value ) . '">';
			echo '<a href="' . esc_url( Reward::manage_items_url( $this->reward_type->value(), ['status' => $value ] ) ) . '" class="' . esc_attr( $current ) . '">' . esc_html( $label ) . '</a>';
			echo 'trash' !== $value ? '&nbsp; | &nbsp;' : '';
			echo '</li>';

		endforeach;

		echo '</ul>';
	}

	/**
	 * Checks the current user's permissions.
	 *
	 * Implements parent method.
	 *
	 * @since 4.0.0
	 * @internal
	 *
	 * @return bool
	 */
	public function ajax_user_can() {

		return current_user_can( 'manage_woocommerce' );
	}

}
