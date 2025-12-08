<?php
/**
 * View for the PO's note
 *
 * @since 0.4.0
 *
 * @var \WP_Comment               $note_comment
 * @var array                     $unread
 * @var array                     $targeted
 * @var \AtumPO\Models\POExtended $atum_order
 * @var bool                      $is_editable
 */

defined( 'ABSPATH' ) || die;

$note_classes   = array( 'note' );
$note_classes[] = 'ATUM' === $note_comment->comment_author ? 'system-note' : '';
$note_classes   = apply_filters( 'atum/atum_order/note_class', array_filter( $note_classes ), $note_comment );

if ( FALSE !== in_array( $note_comment->comment_ID, $unread ) ) :
	$read_status  = 'unread';
	$atmi_icon    = 'atmi-read';
	$action_class = 'mark_read';
	$action_text  = __( 'Mark as read', ATUM_PO_TEXT_DOMAIN );
else :
	$read_status  = 'read';
	$atmi_icon    = 'atmi-bookmark';
	$action_class = 'mark_unread';
	$action_text  = __( 'Mark as unread', ATUM_PO_TEXT_DOMAIN );
endif;

$target_status = FALSE === in_array( $note_comment->comment_ID, $targeted ) ? 0 : 1;

?>
<li rel="<?php echo absint( $note_comment->comment_ID ) ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>"
	data-read="<?php echo esc_attr( $read_status ) ?>" data-target="<?php echo esc_attr( $target_status ) ?>"
>
	<?php if ( $is_editable ) : ?>
	<div class="note-block note-cb">
		<input type="checkbox">
	</div>
	<?php endif; ?>

	<div class="note-block note-contents">
		<div class="note_content">
			<?php echo wp_kses_post( wpautop( wptexturize( $note_comment->comment_content ) ) ) ?>
		</div>

		<p class="meta">

			<?php echo esc_html( $note_comment->comment_author ) ?> -

			<abbr class="exact-date" title="<?php echo esc_attr( $note_comment->comment_date ) ?>">
				<?php
				/* translators: first one is the date added and second is the time */
				printf( esc_html__( '%1$s at %2$s', ATUM_PO_TEXT_DOMAIN ), esc_html( date_i18n( wc_date_format(), strtotime( $note_comment->comment_date ) ) ), esc_html( date_i18n( wc_time_format(), strtotime( $note_comment->comment_date ) ) ) );
				?>
			</abbr>
		</p>
	</div>

	<?php if ( $is_editable ) : ?>
	<div class="note-block note-actions">
		<a href="#" class="delete_note atum-icon atmi-trash atum-tooltip" title="<?php esc_attr_e( 'Delete comment', ATUM_PO_TEXT_DOMAIN ) ?>"></a>
		<a href="#" class="<?php echo esc_attr( $action_class ); ?> atum-icon <?php echo esc_attr( $atmi_icon ); ?> atum-tooltip" title="<?php echo esc_attr( $action_text ); ?>" style="display: none;"></a>
	</div>
	<?php endif; ?>
</li>
