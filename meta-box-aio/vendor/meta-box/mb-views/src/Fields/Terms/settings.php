<div class="mbv-control <?= esc_attr( $prefix ) ?>-taxonomy">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-taxonomy"><?php esc_html_e( 'Taxonomy', 'mb-views' ) ?></label>
	<select class="mbv-control__input" id="<?= esc_attr( $prefix ) ?>-taxonomy">
		<?php $taxonomies = get_taxonomies( [], 'objects' ); ?>
		<?php foreach ( $taxonomies as $slug => $t ) : ?>
			<option value="<?= esc_attr( $slug ) ?>"><?= esc_html( $t->labels->singular_name ) ?></option>
		<?php endforeach ?>
	</select>
</div>
<div class="mbv-control <?= esc_attr( $prefix ) ?>-before">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-before"><?php esc_html_e( 'Leading text', 'mb-views' ) ?></label>
	<input class="mbv-control__input" type="text" id="<?= esc_attr( $prefix ) ?>-before">
</div>
<div class="mbv-control <?= esc_attr( $prefix ) ?>-separator">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-separator"><?php esc_html_e( 'Separator', 'mb-views' ) ?></label>
	<input class="mbv-control__input" type="text" id="<?= esc_attr( $prefix ) ?>-separator" value=", ">
</div>
<div class="mbv-control <?= esc_attr( $prefix ) ?>-after">
	<label class="mbv-control__label" for="<?= esc_attr( $prefix ) ?>-after"><?php esc_html_e( 'Trailing text', 'mb-views' ) ?></label>
	<input class="mbv-control__input" type="text" id="<?= esc_attr( $prefix ) ?>-after">
</div>
