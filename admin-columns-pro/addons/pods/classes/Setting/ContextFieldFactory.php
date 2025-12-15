<?php

declare(strict_types=1);

namespace ACA\Pods\Setting;

use AC;
use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\Pods\Setting\Context\Field;
use ACP;
use Pods\Whatsit;

class ContextFieldFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    public function __construct(ConfigFactory $config_factory)
    {
        $this->config_factory = $config_factory;
    }

    private function get_pod_by_table_screen(TableScreen $table_screen): ?Whatsit\Pod
    {
        $pod_name = null;
        add_filter('pods_error_exception', '__return_true', 12); // otherwise pods_error() will throw an exit

        switch (true) {
            case $table_screen instanceof AC\TableScreen\Post :
                $pod_name = $table_screen->get_post_type();
                break;
            case $table_screen instanceof AC\TableScreen\Media :
                $pod_name = 'media';
                break;
            case $table_screen instanceof AC\TableScreen\User :
                $pod_name = 'user';
                break;
            case $table_screen instanceof AC\TableScreen\Comment :
                $pod_name = 'comment';
                break;
            case $table_screen instanceof ACP\TableScreen\Taxonomy :
                $pod_name = $pod_name->get_taxonomy();
                break;
        }

        remove_filter('pods_error_exception', '__return_true', 12);

        $pod = pods_api()->load_pod(['name' => $pod_name]);

        remove_filter('pods_error_exception', '__return_true', 12);

        return $pod instanceof Whatsit\Pod
            ? $pod
            : null;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Field(
            $this->config_factory->create($column),
            $this->get_field_by_column($column, $table_screen)
        );
    }

    private function get_field(TableScreen $table_screen, string $field_name): ?Whatsit\Field
    {
        $pod = $this->get_pod_by_table_screen($table_screen);

        if ( ! $pod) {
            return null;
        }

        $fields = $pod->get_fields();

        return $fields[$field_name] ?? null;
    }

    private function get_field_by_column(Column $column, TableScreen $table_screen): ?Whatsit\Field
    {
        return $this->get_field($table_screen, str_replace('column-pod_', '', $column->get_type()));
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if ( ! str_starts_with($column->get_type(), 'column-pod_')) {
            return false;
        }

        return $this->get_field_by_column($column, $table_screen) !== null;
    }

}