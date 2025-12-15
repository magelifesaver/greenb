<?php

namespace ACP\Editing\RequestHandler;

use AC;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Response;
use AC\Setting\ContextFactory;
use AC\Storage\Repository\DefaultColumnsRepository;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP;
use ACP\Editing;
use ACP\Editing\ApplyFilter;
use ACP\Editing\RequestHandler;
use Exception;

class InlineSave implements RequestHandler
{

    private Storage $storage;

    private Editing\Strategy\AggregateFactory $aggregate_factory;

    private ContextFactory $context_factory;

    private DefaultColumnsRepository $default_columns_repository;

    private AC\Table\ManageValue\ListScreenRenderableFactory $renderable_factory;

    public function __construct(
        Storage $storage,
        ACP\Editing\Strategy\AggregateFactory $aggregate_factory,
        ContextFactory $context_factory,
        DefaultColumnsRepository $default_columns_repository,
        AC\Table\ManageValue\ListScreenRenderableFactory $renderable_factory
    ) {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
        $this->context_factory = $context_factory;
        $this->default_columns_repository = $default_columns_repository;
        $this->renderable_factory = $renderable_factory;
    }

    public function handle(Request $request)
    {
        $response = new Response\Json();

        $id = (int)$request->filter('id', null, FILTER_SANITIZE_NUMBER_INT);

        if ( ! $id) {
            $response->error();
        }

        $list_id = $request->get('layout');

        if ( ! ListScreenId::is_valid_id($list_id)) {
            $response->error();
        }

        $list_screen = $this->storage->find(new ListScreenId($list_id));

        if ( ! $list_screen || ! $list_screen->is_user_allowed(wp_get_current_user())) {
            $response->error();
        }

        $strategy = $this->aggregate_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy) {
            $response->error();
        }

        if ( ! $strategy->user_can_edit_item($id)) {
            $response->set_message(__("You don't have permissions to edit this item", 'codepress-admin-columns'))
                     ->error();
        }

        $column = $list_screen->get_column(new ColumnId((string)$request->get('column')));

        if ( ! $column instanceof ACP\Column) {
            $response->error();
        }

        $service = $column->editing();

        if ( ! $service) {
            $response->error();
        }

        $context = $this->context_factory->create($column, $list_screen->get_table_screen());

        $id = (int)$request->get('id');
        $form_data = $request->get('value');

        $filter = new ApplyFilter\SaveValue($id, $context, $list_screen->get_table_screen(), $list_screen->get_id());
        $form_data = $filter->apply_filters($form_data);

        $table_screen = $list_screen->get_table_screen();

        try {
            do_action('ac/editing/before_save', $context, $id, $form_data);

            $service->update(
                $id,
                $form_data
            );

            do_action('ac/editing/saved', $context, $id, $form_data, $list_screen->get_table_screen());
        } catch (Exception $e) {
            $response->set_message($e->getMessage())
                     ->error();
        }

        $filter = new ApplyFilter\EditValue($id, $context, $list_screen->get_table_screen(), $list_screen->get_id());

        try {
            $edit_value = $filter->apply_filters(
                $service->get_value($id)
            );
        } catch (Exception $e) {
            $response->set_message($e->getMessage())
                     ->error();
            exit;
        }

        $renderable = $this->renderable_factory->create($list_screen);

        $is_default_column = null !== $this->default_columns_repository
                ->find(
                    $table_screen->get_id(),
                    $column->get_id()
                );

        $response
            ->set_parameters([
                'id'           => $id,
                'value'        => $edit_value,
                'render_value' => $renderable->render_cell((string)$column->get_id(), $id),
                // This will perform another AJAX call to the current screen to ensure all hooks are loaded correctly.
                'fetch_remote' => $is_default_column,
            ])
            ->success();
    }

}