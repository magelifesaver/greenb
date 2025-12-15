<?php

declare(strict_types=1);

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\Builder;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollectionFactory\ToggleOptionCollection;

class CreateTerms extends Builder
{

    public const NAME = 'enable_term_creation';

    protected function get_label(Config $config): ?string
    {
        return __('Allow new terms', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_toggle(
            self::NAME,
            (new ToggleOptionCollection())->create(),
            $config->get(self::NAME, 'off')
        );
    }

}