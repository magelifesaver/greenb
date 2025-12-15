<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\ComponentFactory\Builder;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Setting\FormatterCollection;
use ACA\WC\Value\Formatter;

class OrderProperty extends Builder
{

    public const NAME = 'order_display';
    public const TYPE_DATE = 'date';
    public const TYPE_AMOUNT = 'order';
    public const TYPE_STATUS = 'status';

    protected function get_label(Config $config): ?string
    {
        return __('Order Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array(
                [
                    self::TYPE_DATE   => __('Date', 'codepress-admin-columns'),
                    self::TYPE_AMOUNT => __('Amount', 'codepress-admin-columns'),
                    self::TYPE_STATUS => __('Status', 'codepress-admin-columns'),
                ]
            ),
            $config->get(self::NAME, self::TYPE_DATE)
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get(self::NAME, '')) {
            case self::TYPE_AMOUNT:
                $formatters->add(new Formatter\Order\OrderTotal());
                break;
            case self::TYPE_STATUS:
                $formatters->add(new Formatter\Order\StatusLabel());
                break;
            case self::TYPE_DATE:
                $formatters->add(new Formatter\Order\DateCreated());
                break;
        }
    }

}