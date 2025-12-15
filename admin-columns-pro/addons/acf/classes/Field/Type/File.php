<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class File extends Field implements Field\File
{

    public function get_mime_types(): array
    {
        return isset($this->settings['mime_types'])
            ? explode(',', $this->settings['mime_types'])
            : [];
    }

}