<?php

add_action('admin_footer', 'plugify_add_generate_reply_button_admin');

function plugify_add_generate_reply_button_admin() {

	$screen = get_current_screen();

	if ('product_page_product-reviews' != $screen->id && 'product' != $screen->id) {
		return;
	}

	?>
	<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function () {
			let reply_container = document.querySelector('#replysubmit .reply-submit-buttons');

			if (reply_container) {
				let generate_button = document.createElement("button");
				generate_button.type = "button";
				generate_button.id = "generate-reply-btn";
				generate_button.className = "button button-secondary";
				generate_button.innerText = "Generate Reply";

				jQuery('body').on('click', '#generate-reply-btn', function () {

					var comment_id = jQuery(this).parent().parent().parent().parent().parent().prev().attr('id');
					comment_id = comment_id.split('-')[1];
					var thissss = jQuery(this);
					jQuery('.comment-reply').block({
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						}
					});

					jQuery.ajax({
						url: '<?php echo filter_var(admin_url('admin-ajax.php')); ?>',
						type: 'post',
						data: {
							action: 'plugify_generate_ai_reply_to_review',
							comment_id: comment_id
						},
						success: function (response) {
							jQuery('.comment-reply').unblock();

							if (response.success) {
								jQuery('#replycontent').val(response.data.reply);
							} else {
								alert('Error: ' + (response.data?.message || 'Something went wrong.'));
							}
						},
						error: function (xhr, status, error) {
							jQuery('.comment-reply').unblock();
							alert('AJAX error: ' + error);
						}
					});



				})

				// generate_button.addEventListener("click", function () {
				// 	console.log(jQuery(this).parent().parent().parent().parent().parent().prev().attr('id'))
				// 	let reply_content = document.getElementById("replycontent");
				// 	if (reply_content) {
				// 		reply_content.value = "Thank you for your review! We appreciate your support.";
				// 	}
				// });

				reply_container.appendChild(generate_button);
			}
		});
	</script>
	<style type="text/css">
		#generate-reply-btn {
			margin-left: -23px !important;
		}
	</style>
	<?php
}
