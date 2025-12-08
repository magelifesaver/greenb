<div class="mbv-toolbar">
	<ul class="mbv-tabs__nav mbv-tabs__nav--editors">
		<li class="mbv-tabs__tab mbv-is-visible mbv-is-active" data-tab="template-editor"><?php esc_html_e( 'Template', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="css-editor"><?php esc_html_e( 'CSS', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="js-editor"><?php esc_html_e( 'JavaScript', 'mb-views' ) ?></li>
	</ul>
	<div class="mbv-controls">
		<button type="button" class="mbv-icon-button mbv-wordwrap-toggle" title="<?php esc_attr_e( 'Toggle word wrap', 'mb-views' ) ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
				<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
				<line x1="4" y1="6" x2="20" y2="6" />
				<line x1="4" y1="18" x2="9" y2="18" />
				<path d="M4 12h13a3 3 0 0 1 0 6h-4l2 -2m0 4l-2 -2" />
			</svg>
		</button>
		<button type="button" class="mbv-icon-button mbv-fullscreen-toggle" title="<?php esc_attr_e( 'Toggle fullscreen', 'mb-views' ) ?>"><span class="dashicons dashicons-fullscreen-alt"></span></button>
		<button type="button" class="button mbv-panel-trigger" data-panel="inserter"><?php esc_html_e( 'Insert Field', 'mb-views' ) ?></button>
	</div>
</div>
