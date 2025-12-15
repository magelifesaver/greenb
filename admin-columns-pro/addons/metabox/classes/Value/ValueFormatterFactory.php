<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Value;

class ValueFormatterFactory
{

    public function create(FormatterCollection $formatters, Field\Field $field, Config $config): FormatterCollection
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::AUTOCOMPLETE:
                $formatters->prepend(new Formatter\Aggregate(new FormatterCollection([
                        new Value\Formatter\ArrayValues(),
                        new Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : []),
                    ])
                ));
                break;
            case MetaboxFieldTypes::COLORPICKER:
                $formatters->add(new Formatter\Color());
                break;
            case MetaboxFieldTypes::CHECKBOX:
                $formatters->prepend(new Formatter\YesNoIcon());
                break;
            case MetaboxFieldTypes::CHECKBOX_LIST:
                $formatter = new Formatter\Aggregate(new FormatterCollection([
                        new Value\Formatter\ArrayValues(),
                        new Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : []),
                        new Formatter\Collection\Separator(', '),
                    ])
                );
                $formatters->prepend($formatter);

                break;
            case MetaboxFieldTypes::DATETIME:
            case MetaboxFieldTypes::DATE:
                $formatters->prepend(new Value\Formatter\GroupDateFix());
                break;
            case MetaboxFieldTypes::TEXT_LIST:
                $formatters->add(
                    new Value\Formatter\TextList($field instanceof Field\Choices ? $field->get_choices() : [])
                );
                break;
            case MetaboxFieldTypes::FIELDSET_TEXT:
                $formatters->add(new Value\Formatter\FieldsetValues());
                break;
            case MetaboxFieldTypes::GOOGLE_MAPS:
                $formatters->add(new Value\Formatter\Maps());
                break;
            case MetaboxFieldTypes::RADIO:
                $formatters->prepend(
                    new Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : [])
                );
                break;
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::SELECT_ADVANCED:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $formatters->prepend(new Formatter\Aggregate(new FormatterCollection([
                            new Value\Formatter\ArrayValues(),
                            new Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : []),
                        ])
                    ));
                } else {
                    $formatters->prepend(
                        new Formatter\MapOptionLabel($field instanceof Field\Choices ? $field->get_choices() : [])
                    );
                }
                break;
            case MetaboxFieldTypes::OEMBED:
            case MetaboxFieldTypes::URL:
                $formatters->add(new Value\Formatter\LinkableUrlDecode());
                break;
            case MetaboxFieldTypes::POST:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Formatter\RelationIdsCollection());

                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $aggregate->add(Formatter\Collection\Separator::create_from_config($config));
                }

                return new FormatterCollection([new Formatter\Aggregate($aggregate)]);
            case MetaboxFieldTypes::TAXONOMY:
            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Value\Formatter\TermIds());

                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $aggregate->add(Formatter\Collection\Separator::create_from_config($config));
                }

                return new FormatterCollection([new Formatter\Aggregate($aggregate)]);

            case MetaboxFieldTypes::USER:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Formatter\RelationIdsCollection());

                return new FormatterCollection([new Formatter\Aggregate($aggregate)]);

            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_UPLOAD:
                $formatters->prepend(new Formatter\Aggregate(new FormatterCollection([
                        new Value\Formatter\ArrayValues(),
                        new Value\Formatter\FileDownload(),
                    ])
                ));
                break;
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
                $aggregate = $this->create_aggregate_formatter_collection($formatters);
                $aggregate->prepend(new Value\Formatter\ImageIds());
                $aggregate->add(new Formatter\Collection\Separator(''));

                return new FormatterCollection([new Formatter\Aggregate($aggregate)]);
            case MetaboxFieldTypes::SINGLE_IMAGE:
                $formatters->prepend(new Value\Formatter\ImageId());
                break;
            case MetaboxFieldTypes::VIDEO:
                $formatters->prepend(new Formatter\Aggregate(new FormatterCollection([
                        new Value\Formatter\ArrayValues(),
                        new Value\Formatter\VideoLink(),
                    ])
                ));

                break;
        }

        return $formatters;
    }

    private function create_aggregate_formatter_collection(FormatterCollection $component_formatters
    ): FormatterCollection {
        $collection = new FormatterCollection();

        foreach ($component_formatters as $formatter) {
            $collection->add($formatter);
        }

        return $collection;
    }

}