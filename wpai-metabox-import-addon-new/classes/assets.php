<?php

namespace Wpai\Metabox;

class PMMI_Assets {
    use \Wpai\AddonAPI\Singleton;

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_filter('script_loader_tag', [$this, 'add_type_attribute'], 10, 3);
    }

    public function enqueue() {
        wp_enqueue_style('pmmi-admin-style', PMMI_ROOT_URL . '/static/css/admin.css');
        wp_enqueue_script('pmmi-admin-script', PMMI_ROOT_URL . '/static/js/admin.js');
    }

    public function add_type_attribute($tag, $handle, $src) {
        if ('pmmi-admin-script' !== $handle) {
            return $tag;
        }
        // Change the script tag by adding type="module" and return it.
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        return $tag;
    }
}

PMMI_Assets::getInstance();
