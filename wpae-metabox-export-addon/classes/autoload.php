<?php

namespace Wpae\Metabox;

class PMME_Autoloader {
    use \Wpae\AddonAPI\Singleton;

    public function __construct() {
        require PMME_ROOT_DIR . '/classes/registry.php';
        require PMME_ROOT_DIR . '/classes/transformers.php';
        require PMME_ROOT_DIR . '/classes/addon.php';

        spl_autoload_register([$this, 'autoload']);
    }

    public function loadIfFound(string $path) {
        $path = PMME_ROOT_DIR . '/' . $path . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }

    public function autoload($class) {
        if (!str_contains($class, 'PMME_')) return;


        $parts = explode('\\', $class);
        $className = end($parts);
        $className = str_replace('PMME_', '', $className);
        $className = str_replace('_', '-', $className);
        $className = strtolower($className);
        $className = str_replace('-field', '', $className); // E.g. Rename "text-field" to "text"

        $this->loadIfFound('custom-fields/' . $className);
    }
}

PMME_Autoloader::getInstance();
