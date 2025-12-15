<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder;

use AC;
use AC\Asset\Location\Absolute;
use AC\Registerable;
use AC\Services;
use AC\Table\ManageValue\ListScreenServiceFactory;
use AC\Type\Labels;
use AC\Vendor\DI;
use AC\Vendor\DI\DependencyException;
use AC\Vendor\DI\NotFoundException;
use ACA\BeaverBuilder\TableScreen\MenuGroupFactory;
use ACA\BeaverBuilder\TableScreen\TableIdsFactory;
use ACA\BeaverBuilder\TableScreen\TemplateFactory;

class BeaverBuilder implements Registerable
{

    private DI\Container $container;

    public function __construct(Absolute $location, DI\Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function register(): void
    {
        if ( ! class_exists('FLBuilderLoader')) {
            return;
        }

        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'layout',
                new Labels(
                    __('Template', 'fl-builder'),
                    __('Templates', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'row',
                new Labels(
                    __('Saved Row', 'fl-builder'),
                    __('Saved Rows', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'column',
                new Labels(
                    __('Saved Column', 'fl-builder'),
                    __('Saved Columns', 'fl-builder')
                )
            )
        );
        AC\TableScreenFactory\Aggregate::add(
            new TemplateFactory(
                'module',
                new Labels(
                    __('Saved Module', 'fl-builder'),
                    __('Saved Modules', 'fl-builder')
                )
            )
        );
        AC\TableIdsFactory\Aggregate::add(new TableIdsFactory());
        AC\Admin\MenuGroupFactory\Aggregate::add(new MenuGroupFactory());

        AC\Service\ManageValue::add(
            $this->container->make(
                ListScreenServiceFactory::class,
                ['factory' => $this->container->get(ListTable\ManageValue\TemplateFactory::class)]
            )
        );

        AC\Service\ManageHeadings::add($this->container->get(ListTable\ManageHeading\TemplateFactory::class));
        AC\Service\SaveHeadings::add($this->container->get(ListTable\SaveHeading\TemplateFactory::class));

        $this->create_services()->register();
    }

    private function create_services(): Services
    {
        return new Services([
            new Service\PostTypes(),
        ]);
    }

}