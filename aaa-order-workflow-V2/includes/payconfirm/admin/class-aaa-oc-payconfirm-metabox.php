<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/admin/class-aaa-oc-payconfirm-metabox.php
 * Purpose: Editable PayConfirm fields + "Save & Re-run Match" (no nested form) + candidates list with Force link.
 * Version: 1.1.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AAA_OC_PayConfirm_Metabox {
	public static function init() { add_action( 'add_meta_boxes', [ __CLASS__, 'add_box' ] ); }

	public static function add_box() {
		add_meta_box(
			'aaa_oc_payconfirm_box',
			'PayConfirm: Parsed Fields & Match',
			[ __CLASS__, 'render' ],
			'payment-confirmation',
			'normal',
			'default'
		);
	}

	public static function render( $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) return;

		// Current values
		$pm   = get_post_meta( $post->ID, '_pc_payment_method', true );
		$acct = get_post_meta( $post->ID, '_pc_account_name', true );
		$amt  = get_post_meta( $post->ID, '_pc_amount', true );
		$sent = get_post_meta( $post->ID, '_pc_sent_on', true );
		$txn  = get_post_meta( $post->ID, '_pc_txn', true );
		$memo = get_post_meta( $post->ID, '_pc_memo', true );

		$status  = get_post_meta( $post->ID, '_pc_match_status', true );
		$reason  = get_post_meta( $post->ID, '_pc_match_reason', true );
		$conf    = get_post_meta( $post->ID, '_pc_match_confidence', true );
		$matched = (int) get_post_meta( $post->ID, '_pc_matched_order_id', true );

		$cand = get_post_meta( $post->ID, '_pc_candidate_orders', true );
		$cand = is_string( $cand ) ? json_decode( $cand, true ) : ( is_array( $cand ) ? $cand : [] );

		$save_nonce  = wp_create_nonce( 'aaa_oc_pc_save' );
		$force_nonce = wp_create_nonce( 'aaa_oc_pc_force_' . (int) $post->ID );
		$post_id     = (int) $post->ID;
		?>
		<style>
			.aaa-pc-grid{display:grid;grid-template-columns:160px 1fr;gap:8px;max-width:820px}
			.pc-cands{margin-top:12px}
			.pc-cands table{width:100%;border-collapse:collapse}
			.pc-cands th,.pc-cands td{border-bottom:1px solid #eee;padding:6px;text-align:left}
			.pc-cands .small{font-size:12px;color:#777}
			#aaa-oc-pc-save-msg{vertical-align:middle}
		</style>

		<div class="aaa-pc-grid">
			<label>Payment Method</label><input type="text" name="pc_payment_method" value="<?php echo esc_attr( $pm ); ?>" class="regular-text">
			<label>Account Name</label><input type="text" name="pc_account_name"  value="<?php echo esc_attr( $acct ); ?>" class="regular-text">
			<label>Amount</label><input type="text" name="pc_amount" value="<?php echo esc_attr( $amt ); ?>" class="regular-text">
			<label>Sent On</label><input type="text" name="pc_sent_on" value="<?php echo esc_attr( $sent ); ?>" class="regular-text" placeholder="YYYY-mm-dd HH:ii:ss">
			<label>Transaction #</label><input type="text" name="pc_txn" value="<?php echo esc_attr( $txn ); ?>" class="regular-text">
			<label>Memo</label><input type="text" name="pc_memo" value="<?php echo esc_attr( $memo ); ?>" class="regular-text">
		</div>

		<p style="margin-top:10px">
			<?php if ( $matched ) : ?>
				<strong>Matched Order:</strong> <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $matched . '&action=edit' ) ); ?>">#<?php echo (int) $matched; ?></a><br>
			<?php else : ?>
				<strong>Matched Order:</strong> <em>None</em><br>
			<?php endif; ?>
			<strong>Status:</strong> <?php echo esc_html( $status ?: '—' ); ?> |
			<strong>Reason:</strong> <?php echo esc_html( $reason ?: '—' ); ?> |
			<strong>Confidence:</strong> <?php echo esc_html( $conf !== '' ? $conf : '—' ); ?>
		</p>

		<p>
			<button type="button" class="button button-primary" id="aaa-oc-pc-save-match">Save &amp; Re-run Match</button>
			<span id="aaa-oc-pc-save-msg"></span>
		</p>

		<?php if ( ! empty( $cand ) ) : ?>
			<div class="pc-cands">
				<div style="font-weight:700;margin:6px 0">Qualified Orders</div>
				<table>
					<thead><tr><th>Order</th><th>Score</th><th>Reasons</th><th class="small">Actions</th></tr></thead>
					<tbody>
					<?php foreach ( $cand as $row ) :
						$oid    = (int) ( $row['order_id'] ?? 0 );
						$score  = (float) ( $row['score'] ?? 0 );
						$reasons= esc_html( $row['reasons'] ?? '' );
						$admin  = admin_url( 'post.php?post=' . $oid . '&action=edit' );
						$force  = wp_nonce_url(
							admin_url( 'admin-post.php?action=aaa_oc_payconfirm_force&post_id=' . $post_id . '&order_id=' . $oid ),
							'aaa_oc_pc_force_' . $post_id
						);
						?>
						<tr>
							<td>#<?php echo $oid; ?></td>
							<td><?php echo number_format( $score, 0 ); ?></td>
							<td><?php echo $reasons; ?></td>
							<td class="small">
								<a href="<?php echo esc_url( $force ); ?>" class="button button-small">Force</a>
								<a href="<?php echo esc_url( $admin ); ?>">Admin</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<script>
		(function(){
		  const btn  = document.getElementById('aaa-oc-pc-save-match');
		  if(!btn) return;
		  const box  = btn.closest('#aaa_oc_payconfirm_box') || document;
		  const msg  = document.getElementById('aaa-oc-pc-save-msg');
		  const url  = <?php echo wp_json_encode( admin_url('admin-post.php') ); ?>;
		  const pid  = <?php echo (int) $post_id; ?>;
		  const nonce= <?php echo wp_json_encode( $save_nonce ); ?>;

		  function val(n){ const el = box.querySelector('[name="'+n+'"]'); return el ? el.value : ''; }

		  btn.addEventListener('click', async function(e){
		    e.preventDefault();
		    const fd = new FormData();
		    fd.append('action','aaa_oc_payconfirm_update_and_match');
		    fd.append('post_id', String(pid));
		    fd.append('aaa_oc_pc_nonce', nonce);
		    fd.append('pc_payment_method', val('pc_payment_method'));
		    fd.append('pc_account_name',  val('pc_account_name'));
		    fd.append('pc_amount',        val('pc_amount'));
		    fd.append('pc_sent_on',       val('pc_sent_on'));
		    fd.append('pc_txn',           val('pc_txn'));
		    fd.append('pc_memo',          val('pc_memo'));

		    const label = btn.textContent;
		    btn.disabled = true; btn.textContent = 'Saving…';
		    msg.textContent = '';

		    try{
		      await fetch(url, { method:'POST', credentials:'same-origin', body: fd });
		      window.location.reload();
		    }catch(err){
		      btn.disabled = false; btn.textContent = label;
		      msg.textContent = 'Failed to save. Check Network tab / debug.log';
		    }
		  });
		})();
		</script>
		<?php
	}
}
