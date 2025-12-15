<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\User\Meta;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Language extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = 'locale';

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    public function get_column_type(): string
    {
        return 'column-user_default_language';
    }

    public function get_label(): string
    {
        return __('Language');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Meta(self::META_KEY),
            new ACP\Value\Formatter\LanguageNativeName(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\LanguageRemote();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Languages();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta(self::META_KEY);
    }

}