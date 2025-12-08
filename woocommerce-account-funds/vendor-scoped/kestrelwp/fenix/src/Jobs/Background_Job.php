<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs\Background_Processing\Process;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Singleton;
/**
 * Abstract class for handling batch jobs in background.
 *
 * @NOTE Concrete implementations should override the {@see Background_Job::$action} property with a specific action name.
 *
 * @since 1.5.0
 */
abstract class Background_Job extends Process
{
    use Has_Plugin_Instance;
    use Is_Singleton;
    /** @var string override this property with a non-empty string */
    protected $action = '';
    /**
     * Initiate a new background process.
     *
     * @since 1.6.0
     *
     * @param WordPress_Plugin $plugin the plugin instance
     * @param array<class-string>|bool $allowed_batch_data_classes
     */
    protected function __construct(WordPress_Plugin $plugin, $allowed_batch_data_classes = \true)
    {
        static::$plugin = $plugin;
        $this->prefix = $plugin->id();
        // default prefix
        // if the action is not set or the default is used, we should warn the concrete implementation
        if (empty($this->action) || 'background_processing' === $this->action) {
            _doing_it_wrong(__METHOD__, sprintf('A specific action should be specified for the background job %s.', __CLASS__), '');
        }
        parent::__construct($allowed_batch_data_classes);
    }
    /**
     * Initializes the job in background.
     *
     * @since 1.6.0
     *
     * @param WordPress_Plugin $plugin the plugin instance
     * @param array<class-string>|bool $allowed_batch_data_classes Whether to allow batch data classes or an array of allowed classes
     * @return Background_Job
     */
    public static function initialize(WordPress_Plugin $plugin, $allowed_batch_data_classes = \true): Background_Job
    {
        return static::instance($plugin, $allowed_batch_data_classes);
        // @phpstan-ignore-line
    }
    /**
     * When the process has completed, delete any lingering options from the database.
     *
     * @since 1.7.0
     *
     * @return void
     */
    protected function completed()
    {
        global $wpdb;
        parent::completed();
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like($this->identifier . '_batch_') . '%'));
    }
}
