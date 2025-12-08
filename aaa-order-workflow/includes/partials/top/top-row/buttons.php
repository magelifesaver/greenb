<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

	<div class="expanded-only" style="display:none;">
	<div style="display:flex; flex-wrap:wrap; gap:0.2rem;width:100%">
		<div class="aaa-nav2-buttons" style="display:none; gap:0.5rem;width: 100%;justify-content: flex-end;">
		    <div style="display:flex; width: 100%; justify-content:flex-start; gap:0.5rem;">
		        <?php
		        if ($has_rec) {
		            echo '<a href="' . esc_url($row->lkd_upload_med) . '" target="_blank"
		                   style="background:purple; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none;">REC</a>';
		        }
		        if ($has_selfie) {
		            echo '<a href="' . esc_url($row->lkd_upload_selfie) . '" target="_blank"
		                   style="background:purple; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none;">SELFIE</a>';
		        }
		        if ($has_idfile) {
		            echo '<a href="' . esc_url($row->lkd_upload_id) . '" target="_blank"
		                   style="background:purple; color:#fff; padding:5px 10px; border-radius:4px; text-decoration:none;">ID</a>';
		        }
		        ?>
		    </div>

		    <button class="button button-secondary open-order button-modern" onclick="window.open('<?php echo esc_url(admin_url('post.php?post='.$order_id.'&action=edit')); ?>','_blank');">
		        Open Order
		    </button>
                    
		    <!-- EXPANDED CONTENT - Next Prev Buttons -->
		    <?php echo AAA_Render_Next_Prev_Icons::render_next_prev_icons( $order_id, $row->status, true ); ?>
		</div>
	</div>
</div>
</div>