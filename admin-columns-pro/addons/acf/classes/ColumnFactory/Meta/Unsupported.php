<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactory\Meta;

use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\Message;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use AC\Type\TableScreenContext;
use ACA\ACF\Field;
use ACA\ACF\Setting\FieldComponentFactory;
use ACA\ACF\Value\Formatter;
use ACP\Column\FeatureSettingBuilderFactory;

class Unsupported extends AdvancedColumnFieldFactory
{

	public function __construct(
			FeatureSettingBuilderFactory $feature_settings_builder_factory,
			DefaultSettingsBuilder $default_settings_builder,
			string $column_type,
			string $label,
			Field $field,
			TableScreenContext $table_context,
			FieldComponentFactory $component_factory
	) {
		parent::__construct(
				$feature_settings_builder_factory,
				$default_settings_builder,
				$column_type,
				$label,
				$field,
				$table_context,
				$component_factory,
		);
	}

	protected function get_settings(Config $config): ComponentCollection
	{
		return new ComponentCollection([
				(new Message('', $this->get_message()))->create($config),
		]);
	}

	protected function get_formatters(Config $config): FormatterCollection
	{
		return new FormatterCollection([
				new Formatter\GetFieldRaw($this->table_context, $this->field->get_meta_key()),
				new Formatter\Unsupported(),
		]);
	}

	private function get_message()
	{
		ob_start();
		?>
		<div class="msg acu-p-4" style="display: block;background-color:#ffba002e">
			<p>
				<strong><?php
					_e('This ACF field is not supported', 'codepress-admin-columns'); ?></strong>
			</p>

			<p>
				<?php
				_e(
						'This specific ACF field type is not supported in this integration. Although the column may work, it could lead to unexpected behavior. Sorting, Filtering and Inline Editing are disabled for this field.',
						'codepress-admin-columns'
				); ?>
			</p>

		</div>
		<?php
		return ob_get_clean();
	}

}