<?php

declare(strict_types=1);

namespace ACP\Value\ExtendedValue\NetworkSites;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;

class Plugins implements ExtendedValue
{

    private const NAME = 'post-plugins';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link(int $id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME);
    }

    public function render(int $id, array $params, Column $column, ListScreen $list_screen): string
    {
        $plugins = $this->get_plugin_items($id);

        if (empty($plugins)) {
            return __('No plugins found');
        }

        $view = new View([
            'title' => is_multisite() ? (string) get_blog_option($id, 'blogname') : get_bloginfo('name'),
            'amount' => count($plugins),
            'items' => $plugins,
        ]);

        return $view->set_template('modal-value/plugins')->render();
    }

    private function get_plugin_items(int $id): array
    {
        // Site plugins
        $active_plugins = maybe_unserialize(ac_helper()->network->get_site_option($id, 'active_plugins'));

        // Network plugins
        $network_plugins = get_site_option('active_sitewide_plugins');
        if (!empty($network_plugins) && is_array($network_plugins)) {
            $active_plugins = array_merge($active_plugins, array_keys($network_plugins));
        }

        if (empty($active_plugins)) {
            $active_plugins = [];
        }

        $all_plugins = get_plugins();

        foreach ($active_plugins as $plugin_file) {

            $plugin_data = isset($all_plugins[$plugin_file]) ? $all_plugins[$plugin_file] : [];
            $is_network_active = isset($network_plugins[$plugin_file]) ? 'Network Active' : false;

            $items[] = [
                'name'        => $plugin_data['Name'],
                'version'     => $plugin_data['Version'],
                'is_network_active'  => $is_network_active
            ];
        }

        return $items;
    }
}
