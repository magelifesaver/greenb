<div class="mbv-modal hidden" data-modal="field-user" data-type="user">
	<div class="mbv-modal__overlay"></div>
	<div class="mbv-modal__content">
		<div class="mbv-modal__header">
			<div class="mbv-modal__title"><?php esc_html_e( 'User settings', 'mb-views' ) ?></div>
			<button class="mbv-modal__close">&times;</button>
		</div>
		<div class="mbv-modal__body">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-field-user-output"><?php esc_html_e( 'Output', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-field-user-output">
					<option value="ID"><?php esc_html_e( 'User ID', 'mb-views' ) ?></option>
					<option value="first_name"><?php esc_html_e( 'User first name', 'mb-views' ) ?></option>
					<option value="last_name"><?php esc_html_e( 'User last name', 'mb-views' ) ?></option>
					<option value="display_name"><?php esc_html_e( 'User display name', 'mb-views' ) ?></option>
					<option value="login"><?php esc_html_e( 'User login (username)', 'mb-views' ) ?></option>
					<option value="nickname"><?php esc_html_e( 'User nickname', 'mb-views' ) ?></option>
					<option value="email"><?php esc_html_e( 'User email', 'mb-views' ) ?></option>
					<option value="url"><?php esc_html_e( 'User website URL', 'mb-views' ) ?></option>
					<option value="nicename"><?php esc_html_e( 'User nicename', 'mb-views' ) ?></option>
					<option value="description"><?php esc_html_e( 'User description', 'mb-views' ) ?></option>
					<option value="posts_url"><?php esc_html_e( 'User posts URL', 'mb-views' ) ?></option>
					<option value="avatar"><?php esc_html_e( 'User avatar', 'mb-views' ) ?></option>
				</select>
			</div>
			<?php
			$prefix = 'mbv-field-user-avatar';
			require dirname( __DIR__ ) . '/Avatar/settings.php';
			?>
		</div>
		<div class="mbv-modal__footer">
			<button class="mbv-insert button button-primary"><?php esc_html_e( 'Insert', 'mb-views' ) ?></button>
		</div>
	</div>
</div>
