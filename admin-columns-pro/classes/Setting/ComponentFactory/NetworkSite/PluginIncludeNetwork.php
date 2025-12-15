<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\Builder;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class PluginIncludeNetwork extends Builder
{

    protected function get_label(Config $config): ?string
    {
        return __('Include network plugins', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_toggle(
            'include_network',
            null,
            $config->get('include_network', 'on')
        );
    }

}