<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\Setting\ComponentCollection;
use AC\Setting\FormatterCollection;

trait FormatterSettingsTrait
{

    private function get_formatters_from_settings(ComponentCollection $settings): FormatterCollection
    {
        $formatters = new FormatterCollection();

        foreach ($settings as $setting) {
            foreach ($setting->get_formatters() as $formatter) {
                $formatters->add($formatter);
            }
        }

        return $formatters;
    }

}