<?php


add_action('woocommerce_product_after_variable_attributes', 'plugify_add_variation_ai_buttons', 10, 3);

function plugify_add_variation_ai_buttons( $loop, $variation_data, $variation ) {
	?>
	<div class="plugify-variation-ai-buttons" style="margin: 10px 0;">
		<button type="button" class="button button-primary plugify-generate-variation-desc" 
		data-variation-id="<?php echo esc_attr($variation->ID); ?>" 
		data-loop="<?php echo esc_attr($loop); ?>">Generate Variation Description</button>
		<button type="button" class="button button-secondary plugify-enhance-variation-desc" 
		data-variation-id="<?php echo esc_attr($variation->ID); ?>" 
		data-loop="<?php echo esc_attr($loop); ?>">Enhance Variation Description</button>
	</div>

	<div id="generate_variation_description_modal" class="custom-modal">
		<div class="custom-modal-content">
			<span class="close-modal">&times;</span>
			<h2 style="padding: unset;"><b>Generate Variation Description</b></h2>
			<input type="hidden" id="variation_id" value="" />
			<input type="hidden" id="variation_loop" value="" />

			<label>Product Name:</label>
			<input type="text" id="variation_product_name" class="widefat" />

			<label>Key Features:</label>
			<textarea id="variation_key_features" rows="4" class="widefat"></textarea>

			<label>Limit Type:</label>
			<select id="variation_limit_type" class="widefat">
				<option value="characters">Max Characters</option>
				<option value="words">Max Words</option>
				<option value="tokens">Max Tokens</option>
			</select>

			<label id="variation_limit_label">Max Characters:</label>
			<input type="number" id="variation_max_limit" class="widefat" min="50" max="1000" value="200" />

			<hr>
			<button type="button" class="button button-primary" id="generate_variation_description">Generate</button>
		</div>
	</div>

	<script>
		jQuery(document).ready(function($) {
			$('#variation_limit_type').on('change', function() {
				var selected = $(this).val();
				var label = $('#variation_limit_label');

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


	<div id="enhance_variation_description_modal" class="custom-modal">
		<div class="custom-modal-content">
			<span class="close-modal">&times;</span>
			<h2 style="padding: unset;"><b>Enhance Variation Description</b></h2>
			<input type="hidden" id="enhance_variation_id" value="" />
			<input type="hidden" id="enhance_variation_loop" value="" />

			<label>Current Description:</label>
			<textarea id="variation_current_description" rows="4" class="widefat"></textarea>

			<label>Enhanced Description:</label>
			<textarea id="variation_enhanced_description" rows="4" class="widefat"></textarea>
			<hr>

			<button type="button" class="button button-primary" id="enhance_variation_description">Enhance</button> 
			<button type="button" class="button button-primary" id="variation_done" style="display: none;">Update</button> 
		</div>
	</div>
	<?php
}



