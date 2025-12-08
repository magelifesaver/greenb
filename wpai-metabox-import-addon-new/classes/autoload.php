<?php

namespace Wpai\Metabox;

class PMMI_Autoloader {
    use \Wpai\AddonAPI\Singleton;

    public function __construct() {
        require PMMI_ROOT_DIR . '/classes/registry.php';
        require PMMI_ROOT_DIR . '/classes/addon.php';
        require PMMI_ROOT_DIR . '/classes/assets.php';
        require PMMI_ROOT_DIR . '/classes/transformers.php';

        spl_autoload_register([$this, 'autoload']);
    }

    public function loadIfFound(string $path) {
        $path = PMMI_ROOT_DIR . '/' . $path . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }

    public function autoload($class) {
        if (!str_contains($class, 'PMMI_')) return;

        $parts = explode('\\', $class);
        $className = end($parts);
        $className = str_replace('PMMI_', '', $className);
        $className = str_replace('_', '-', $className);
        $className = strtolower($className);
        $className = str_replace('-field', '', $className); // E.g. Rename "text-field" to "text"

        $this->loadIfFound('custom-fields/' . $className . '/' . $className);
    }
}

PMMI_Autoloader::getInstance();
