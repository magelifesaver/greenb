<div class="mbv-inserter mbv-panel" data-panel="inserter">
	<header class="mbv-panel__header">
		<div class="mbv-panel__title"><?php esc_html_e( 'Insert a field', 'mb-views' ) ?></div>
		<button class="mbv-panel__close">&times;</button>
	</header>
	<div class="mbv-inserter__search">
		<input type="search" placeholder="<?php esc_attr_e( 'Search for a field', 'mb-views' ) ?>">
		<div class="mbv-inserter__no-results hidden"><?php esc_html_e( 'Sorry, no fields found.', 'mb-views' ) ?></div>
	</div>
	<ul class="mbv-tabs__nav">
		<li class="mbv-tabs__tab mbv-is-visible mbv-is-active" data-tab="post"><?php esc_html_e( 'Post', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="term"><?php esc_html_e( 'Term', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="user"><?php esc_html_e( 'User', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="site"><?php esc_html_e( 'Site', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="query"><?php esc_html_e( 'Query', 'mb-views' ) ?></li>
		<li class="mbv-tabs__tab mbv-is-visible" data-tab="view"><?php esc_html_e( 'View', 'mb-views' ) ?></li>
	</ul>
	<div class="mbv-tabs__panes">
		<div class="mbv-tabs__pane mbv-is-visible" data-tab="post">
			<div class="mbv-inserter__heading"><?php esc_html_e( 'Post Fields' ) ?></div>
			<ul class="mbv-inserter__fields">
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "ID"}'><label><?php esc_html_e( 'Post ID', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "title"}'><label><?php esc_html_e( 'Post title', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "excerpt"}'><label><?php esc_html_e( 'Post excerpt', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "content"}'><label><?php esc_html_e( 'Post content', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "url"}'><label><?php esc_html_e( 'Post URL', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="post" data-field='{"id": "slug"}'><label><?php esc_html_e( 'Post slug', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="post" data-modal="field-date" data-field='{"id": "date"}'><label><?php esc_html_e( 'Post date', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="post" data-modal="field-date" data-field='{"id": "modified_date"}'><label><?php esc_html_e( 'Post modified date', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="post" data-modal="field-image" data-field='{"id": "thumbnail"}'><label><?php esc_html_e( 'Post thumbnail', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="post" data-modal="field-terms" data-field='{"id": "terms"}'><label><?php esc_html_e( 'Post term list', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-insert-post-navigation"><label><?php esc_html_e( 'Post navigation', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-insert-post-comments"><label><?php esc_html_e( 'Post comments', 'mb-views' ) ?></label></li>
			</ul>
			<div id="mbv-post-fields"></div>
		</div>
		<div class="mbv-tabs__pane mbv-is-visible" data-tab="term">
			<div class="mbv-inserter__heading"><?php esc_html_e( 'Term Fields' ) ?></div>
			<ul class="mbv-inserter__fields">
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "id"}'><label><?php esc_html_e( 'Term ID', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "name"}'><label><?php esc_html_e( 'Term name', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "slug"}'><label><?php esc_html_e( 'Term slug', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "taxonomy"}'><label><?php esc_html_e( 'Term taxonomy', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "description"}'><label><?php esc_html_e( 'Term description', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="term" data-field='{"id": "url"}'><label><?php esc_html_e( 'Term URL', 'mb-views' ) ?></label></li>
			</ul>
			<div id="mbv-term-fields"></div>
		</div>
		<div class="mbv-tabs__pane" data-tab="user">
			<div class="mbv-control">
				<label class="mbv-control__label" for="mbv-user"><?php esc_html_e( 'User', 'mb-views' ) ?></label>
				<select class="mbv-control__input" id="mbv-user">
					<option value="user"><?php esc_html_e( 'Current user', 'mb-views' ) ?></option>
					<option value="author"><?php esc_html_e( 'Post author', 'mb-views' ) ?></option>
				</select>
			</div>
			<div class="mbv-inserter__heading"><?php esc_html_e( 'User Fields' ) ?></div>
			<ul class="mbv-mbv-inserter__fields">
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "ID"}'><label><?php esc_html_e( 'User ID', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "first_name"}'><label><?php esc_html_e( 'User first name', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "last_name"}'><label><?php esc_html_e( 'User last name', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "display_name"}'><label><?php esc_html_e( 'User display name', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "login"}'><label><?php esc_html_e( 'User login (username)', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "nickname"}'><label><?php esc_html_e( 'User nickname', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "email"}'><label><?php esc_html_e( 'User email', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "url"}'><label><?php esc_html_e( 'User website URL', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "nicename"}'><label><?php esc_html_e( 'User nicename', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "description"}'><label><?php esc_html_e( 'User description', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="user" data-field='{"id": "posts_url"}'><label><?php esc_html_e( 'User posts URL', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="user" data-modal="field-avatar" data-field='{"id": "avatar"}'><label><?php esc_html_e( 'User avatar', 'mb-views' ) ?></label></li>
			</ul>
			<div id="mbv-user-fields"></div>
		</div>
		<div class="mbv-tabs__pane" data-tab="site">
			<div class="mbv-inserter__heading"><?php esc_html_e( 'Site Fields' ) ?></div>
			<ul class="mbv-mbv-inserter__fields">
				<li class="mbv-inserter__field" data-type="site" data-field='{"id": "title"}'><label><?php esc_html_e( 'Site title', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field" data-type="site" data-field='{"id": "description"}'><label><?php esc_html_e( 'Site description', 'mb-views' ) ?></label></li>
			</ul>
			<div id="mbv-site-fields"></div>
		</div>
		<div class="mbv-tabs__pane" data-tab="query">
			<div class="mbv-inserter__heading"><?php esc_html_e( 'Main Query' ) ?></div>
			<ul class="mbv-mbv-inserter__fields">
				<li class="mbv-inserter__field mbv-insert-query-loop"><label><?php esc_html_e( 'Loop', 'mb-views' ) ?></label></li>
				<li class="mbv-inserter__field mbv-modal-trigger" data-type="query" data-modal="field-pagination" data-field='{"id": "pagination"}'><label><?php esc_html_e( 'Pagination', 'mb-views' ) ?></label></li>
			</ul>
			<div id="mbv-query-fields"></div>
			<div id="mbv-relationships-fields"></div>
		</div>
		<div class="mbv-tabs__pane" data-tab="view">
			<div class="mbv-inserter__heading"><?php esc_html_e( 'Views' ) ?></div>
			<div id="mbv-view-fields"></div>
		</div>
	</div>
</div>
