<div class="mbv-modal hidden" data-modal="field-post" data-type="post">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'Post settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-post-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-post-output">
					<option value="tag"><?php esc_html_e( 'Post link tag', 'mb-views' ) ?></option>
					<option value="ID"><?php esc_html_e( 'Post ID', 'mb-views' ) ?></option>
					<option value="title"><?php esc_html_e( 'Post title', 'mb-views' ) ?></option>
					<option value="excerpt"><?php esc_html_e( 'Post excerpt', 'mb-views' ) ?></option>
					<option value="content"><?php esc_html_e( 'Post content', 'mb-views' ) ?></option>
					<option value="url"><?php esc_html_e( 'Post URL', 'mb-views' ) ?></option>
					<option value="slug"><?php esc_html_e( 'Post slug', 'mb-views' ) ?></option>
					<option value="date"><?php esc_html_e( 'Post date', 'mb-views' ) ?></option>
					<option value="modified_date"><?php esc_html_e( 'Post modified date', 'mb-views' ) ?></option>
					<option value="thumbnail"><?php esc_html_e( 'Post thumbnail', 'mb-views' ) ?></option>
					<option value="terms"><?php esc_html_e( 'Post term list', 'mb-views' ) ?></option>
				</select>
			</div>
			<?php
			$prefix = 'mbv-field-post-date';
			require dirname( __DIR__ ) . '/Date/settings.php';

			$prefix = 'mbv-field-post-thumbnail';
			require dirname( __DIR__ ) . '/Image/settings.php';

			$prefix = 'mbv-field-post-terms';
			require dirname( __DIR__ ) . '/Terms/settings.php';
			?>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>
