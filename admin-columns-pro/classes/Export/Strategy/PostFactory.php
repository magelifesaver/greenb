<?php

declare(strict_types=1);

namespace ACP\Export\Strategy;

use AC\PostType;
use AC\Setting\ContextFactory;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class PostFactory implements StrategyFactory
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
        if ( ! $table_screen instanceof PostType || ! $table_screen instanceof ListTable) {
            return null;
        }

        return new Post(
            new TableDataFactory($this->context_factory, $table_screen),
            $this->response_factory
        );
    }

}