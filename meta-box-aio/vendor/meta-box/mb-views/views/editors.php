<div class="mbv-tabs__pane mbv-is-visible" data-tab="template-editor">
	<div class="mbv-editor" id="mbv-post-content" style="height: 600px;"></div>
	<input type="hidden" name="post_content" value="<?= esc_attr( get_post()->post_content ) ?>">
</div>

<div class="mbv-tabs__pane" data-tab="css-editor">
	<div class="mbv-editor" id="mbv-post-excerpt"></div>
	<input type="hidden" name="post_excerpt" value="<?= esc_attr( get_post()->post_excerpt ) ?>">
</div>

<div class="mbv-tabs__pane" data-tab="js-editor">
	<div class="mbv-editor" id="mbv-post-content-filtered"></div>
	<input type="hidden" name="post_content_filtered" value="<?= esc_attr( get_post()->post_content_filtered ) ?>">
</div>
