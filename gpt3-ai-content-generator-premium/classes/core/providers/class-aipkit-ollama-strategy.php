<?php

namespace WPAICG\Core\Providers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Backward-compatibility shim: delegate to new bootstrap which loads lib-based strategy.
// No class definitions here to avoid duplicate declarations.
$bootstrap = __DIR__ . '/ollama/bootstrap-provider-strategy.php';
if (file_exists($bootstrap)) {
    require_once $bootstrap;
}
