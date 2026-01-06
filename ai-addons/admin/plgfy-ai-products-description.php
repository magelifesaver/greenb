<?php

add_action('edit_form_after_editor', 'plugify_add_ai_description_generator_buttons');

function plugify_add_ai_description_generator_buttons ( $post ) {
	if ( 'product' == $post->post_type ) {
		?>
		<button type="button" class="button button-primary" style="margin-top:3px;" id="generate_description_btn">Generate Description</button>
		<button type="button" class="button button-secondary" style="margin-top:3px;" id="enhance_description_btn">Enhance Description</button>

		<div id="generate_description_modal" class="custom-modal">
			<div class="custom-modal-content">
				<span class="close-modal">&times;</span>
				<h2 style="padding: unset;">Generate Product Description</h2>

				<label>Product Name:</label>
				<input type="text" id="product_name" value="<?php echo esc_attr($post->post_title); ?>" class="widefat" />

				<label>Key Features:</label>
				<textarea id="key_features" rows="4" class="widefat"></textarea>

				<label>Limit Type:</label>
				<select id="limit_type" class="widefat" style="width: 100%; min-width: 100%;">
					<option value="characters">Max Characters</option>
					<option value="words">Max Words</option>
					<option value="tokens">Max Tokens</option>
				</select>

				<label id="limit_label">Max Characters:</label>
				<input type="number" id="max_limit" class="widefat" min="50" max="1000" value="200" />

				<hr>

				<button type="button" class="button button-primary" id="generate_description">Generate</button>
			</div>
		</div>


		<div id="enhance_description_modal" class="custom-modal">
			<div class="custom-modal-content">
				<span class="close-modal">&times;</span>
				<h2 style="padding: unset;">Enhance Product Description</h2>
				<label>Current Description:</label>
				<textarea id="current_description" rows="4" class="widefat"><?php echo esc_textarea($post->post_content); ?></textarea>

				<label>Enhanced Description:</label>
				<textarea id="enhanced_description" rows="4" class="widefat"></textarea>
				<hr>

				<button type="button" class="button button-primary" id="enhance_descriptionn">Enhance</button> 
				<button type="button" class="button button-primary" id="donee" style="display: none;">Update</button> 
			</div>
		</div>

		<script type="text/javascript">
			
			jQuery(document).ready(function($) {
				$('#limit_type').on('change', function() {
					var selected = $(this).val();
					var label = $('#limit_label');

					if (selected === 'characters') {
						label.text('Max Characters:');
					} else if (selected === 'words') {
						label.text('Max Words:');
					} else if (selected === 'tokens') {
						label.text('Max Tokens:');
					}
				});
			});
		</script>

		<style type="text/css">
			.custom-modal {
				display: none;
				position: fixed;
				z-index: 1000;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(0, 0, 0, 0.5);
				backdrop-filter: blur(5px);
			}

			.custom-modal-content {
				background: white;
				margin: 8% auto;
				padding: 25px;
				border-radius: 12px;
				width: 50%;
				max-width: 600px;
				box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
				position: relative;
				animation: fadeIn 0.3s ease-in-out;
			}

			.custom-modal h2 {
				margin-top: 0;
				font-size: 22px;
				color: #333;
				border-bottom: 2px solid #eee;
				padding-bottom: 10px;
			}

			.custom-modal label {
				font-weight: 600;
				margin-top: 10px;
				display: block;
				color: #444;
			}

			.custom-modal #product_name,
			.custom-modal #max_chars,
			.custom-modal textarea {
				width: 100%;
				padding: 10px;
				margin-top: 5px;
				border: 1px solid #ccc;
				border-radius: 6px;
				font-size: 14px;
			}

			.custom-modal textarea {
				resize: vertical;
				min-height: 80px;
			}

			.custom-modal .radio-group {
				display: flex;
				gap: 20px;
				margin-top: 8px;
			}

			.custom-modal .radio-group label {
				font-weight: normal;
			}

			.close-modal {
				position: absolute;
				top: 12px;
				right: 15px;
				font-size: 22px;
				font-weight: bold;
				cursor: pointer;
				color: #777;
				transition: color 0.3s ease;
			}

			.close-modal:hover {
				color: #000;
			}

			.button-primary {
				background: #007cba;
				color: white;
				border: none;
				padding: 12px 18px;
				cursor: pointer;
				border-radius: 6px;
				margin-top: 15px;
				font-size: 14px;
				font-weight: bold;
				transition: 0.3s ease;
			}

			.button-primary:hover {
				background: #005b96;
			}

			.button-secondary {
				background: #555;
				color: white;
				padding: 12px 18px;
				border-radius: 6px;
				font-size: 14px;
				margin-left: 10px;
				transition: 0.3s ease;
			}

			.button-secondary:hover {
				background: #333;
			}

			@keyframes fadeIn {
				from {
					opacity: 0;
					transform: translateY(-10px);
				}
				to {
					opacity: 1;
					transform: translateY(0);
				}
			}
		</style>


		<?php
	}
}

add_action('edit_form_after_editor', 'plugify_add_short_description_generator_buttons');
function plugify_add_short_description_generator_buttons($post) {
	if ('product' == $post->post_type) {
		?>
		<script>
			jQuery(document).ready(function($) {
				let shortDescBox = $('#postexcerpt');
				if (shortDescBox.length) {
					let buttonHtml = `
					<div style="margin:10px;">
					<button type="button" class="button button-primary" id="generate_short">Generate Short Description</button>
					</div>

					<div id="short_des_modal" class="custom-modal">
					<div class="custom-modal-content">
					<span class="close-modal">&times;</span>
					<h2 style="padding: unset;">Generate Product Description</h2>

					<label>Current Description:</label>
					<textarea id="current_description_1" rows="4" class="widefat"><?php echo esc_textarea($post->post_content); ?></textarea>

					<label>New Short Description:</label>
					<textarea id="new_short_description" rows="4" class="widefat"></textarea>

					<label>Limit Type:</label>
					<select id="short_limit_type" class="widefat">
					<option value="characters">Max Characters</option>
					<option value="words">Max Words</option>
					<option value="tokens">Max Tokens</option>
					</select>

					<label id="short_limit_label">Max Characters:</label>
					<input type="number" id="short_max_limit" class="widefat" min="50" max="1000" value="200" />

					<hr>
					<button type="button" class="button button-primary" id="generate_short_descriptionnn">Generate</button> 
					<button type="button" class="button button-primary" id="doneeeee" style="display: none;">Update</button> 
					</div>
					</div>
					`;

					shortDescBox.append(buttonHtml);

					$(document).on('change', '#short_limit_type', function() {
						let selected = $(this).val();
						let label = $('#short_limit_label');

						if (selected === 'characters') {
							label.text('Max Characters:');
						} else if (selected === 'words') {
							label.text('Max Words:');
						} else if (selected === 'tokens') {
							label.text('Max Tokens:');
						}
					});
				}
			});
		</script>
		<?php
	}
}
