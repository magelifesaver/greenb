<?php

namespace ACA\WC\Subscriptions\Export\Strategy;

use AC\Setting\ContextFactory;
use AC\TableScreen;
use ACA\WC;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class OrderSubscriptionFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    private ContextFactory $context_factory;

    public function __construct(ResponseFactory $response_factory, ContextFactory $context_factory)
    {
        $this->response_factory = $response_factory;
        $this->context_factory = $context_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof WC\Subscriptions\TableScreen\OrderSubscription) {
            return null;
        }

        return new WC\Export\Strategy\Order(
            new TableDataFactory($this->context_factory, $table_screen),
            $this->response_factory,
            'shop_subscription'
        );
    }

}