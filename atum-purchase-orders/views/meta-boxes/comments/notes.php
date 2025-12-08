<?php
/**
 * View for the PO notes' meta box
 *
 * @since 0.3.0
 *
 * @var \AtumPO\Models\POExtended $atum_order
 */

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumComments;
use Atum\Inc\Helpers as AtumHelpers;

global $post;

$args = array(
	'post_id' => $post->ID,
	'orderby' => 'comment_ID',
	'order'   => 'DESC',
	'type'    => AtumComments::NOTES_KEY,
);

// Bypass the AtumComments filter to get rid of ATUM Order notes comments from queries.
$atum_comments = AtumComments::get_instance();

remove_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );
$notes = get_comments( $args );
add_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );

$is_editable = $atum_order->is_editable();
?>
<div class="atum-meta-box">

	<?php AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/comments/actions', compact( 'is_editable' ) ); ?>

	<ul class="atum_order_notes">

		<?php if ( ! empty( $notes ) ) :

			foreach ( $notes as $note_comment ) :
				include 'note.php';
			endforeach; ?>

		<?php endif; ?>

		<li class="no-comments <?php echo $notes ? esc_attr( 'hide' ) : ''; ?>"><?php esc_html_e( 'No Comments Added', ATUM_PO_TEXT_DOMAIN ) ?></li>
	</ul>

	<?php if ( $is_editable ) : ?>
		<div class="add_note">
			<label for="add_atum_order_note"><?php esc_html_e( 'Add Comments', ATUM_PO_TEXT_DOMAIN ); ?> <span class="atum-help-tip atum-tooltip" title="<?php esc_attr_e( 'Add any comment for your reference. You can mention to any other user in your personal comments by writing @ followed by the username.', ATUM_PO_TEXT_DOMAIN ) ?>"></span></label>
			<textarea type="text" name="atum_order_note" id="add_atum_order_note" class="input-text" cols="20" rows="5"></textarea>
			<br>
			<button type="button" class="add_note btn btn-primary btn-sm"><?php esc_html_e( 'Add', ATUM_PO_TEXT_DOMAIN ); ?></button>
		</div>

		<script type="text/template" id="tmpl-atum-comments-users-list">
			<div class="wrapper-po-comments-users-list">
				<div class="po-comments-user-line po-user-line-everyone">
					<span class="po-everyone-avatar"></span>
					<span>
						<span class="user_name"><?php esc_html_e( 'everyone', ATUM_PO_TEXT_DOMAIN ); ?></span>
						<small><?php esc_html_e( 'Notify to everyone', ATUM_PO_TEXT_DOMAIN ); ?></small>
					</span>
				</div>
			</div>
		</script>
	<?php endif ?>

</div>
