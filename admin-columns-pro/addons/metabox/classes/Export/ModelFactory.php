<?php

namespace ACA\MetaBox\Export;

use AC\Setting\FormatterCollection;
use AC\Value\Formatter\StripNewLine;
use ACA\MetaBox;
use ACP;
use ACP\Export\Service;

final class ModelFactory
{

    public function create(
        MetaBox\Field\Field $field,
        FormatterCollection $base_formatters,
        FormatterCollection $formatters
    ): ?Service {
        switch ($field->get_type()) {
            case MetaBox\MetaboxFieldTypes::CHECKBOX:
                return new ACP\Export\Model\FormatterCollection(
                    $base_formatters
                );
            case MetaBox\MetaboxFieldTypes::FILE_UPLOAD:
            case MetaBox\MetaboxFieldTypes::IMAGE:
            case MetaBox\MetaboxFieldTypes::IMAGE_ADVANCED:
                return new ACP\Export\Model\FormatterCollection(
                    $base_formatters->with_formatter(
                        new MetaBox\Value\Formatter\FileNames()
                    )
                );
            default:
                return new ACP\Export\Model\FormatterCollection(
                    $formatters->with_formatter(new StripNewLine())
                );
        }
    }

}