<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1;

defined('ABSPATH') or exit;
use Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Error\Client_Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\Controller;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\Response_Error;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Setting;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Settings_Registry;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types\Credential;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Extension;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
/**
 * Settings controller.
 *
 * @since 1.1.0
 */
class Settings_Controller extends Controller
{
    /** @var string rest base */
    protected $rest_base = 'settings';
    /**
     * Registers the routes to handle the onboarding from the REST API.
     *
     * @since 1.1.0
     *
     * @return void
     */
    public function register_routes(): void
    {
        register_rest_route($this->get_namespace(), $this->build_route(), [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'get_items_permissions_check']], 'schema' => [$this, 'get_collection_item_schema']]);
        register_rest_route($this->get_namespace(), $this->build_route() . '(?P<name>[\w-]+)', [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'get_item_permissions_check']], ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'update_item_permissions_check']], ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'delete_item_permissions_check']], 'schema' => [$this, 'get_item_schema']]);
    }
    /**
     * Gets the capability required to handle the settings.
     *
     * @since 1.1.0
     *
     * @return string
     */
    protected function get_capability(): string
    {
        return self::plugin() instanceof Extension ? 'manage_woocommerce' : 'manage_options';
    }
    /**
     * Obfuscates the credential value from the payload.
     *
     * @since 1.1.0
     *
     * @param Setting $setting setting instance
     * @return array<string, mixed>
     */
    protected function obfuscate_credential_value_from_payload(Setting $setting): array
    {
        $data = $setting->to_array();
        // obfuscates credential values from API payloads
        if ($setting->get_type() instanceof Credential && isset($data['formatted_value']) && is_string($data['formatted_value'])) {
            $data['formatted_value'] = str_repeat('*', strlen($data['formatted_value']));
        }
        return $data;
    }
    /**
     * Gets the settings data as the response.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        return rest_ensure_response(array_map(function (Setting $setting) {
            return $this->obfuscate_credential_value_from_payload($setting);
        }, array_values(Settings_Registry::get_settings())));
    }
    /**
     * Checks whether the user has permissions to read all the settings.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::READABLE, $this->get_capability(), $request);
    }
    /**
     * Gets the individual setting data as the response.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $setting = $this->get_setting_for_request($request);
        if (!$setting) {
            return $this->error_response(Response_Error::NOT_FOUND);
        }
        return rest_ensure_response($this->obfuscate_credential_value_from_payload($setting));
    }
    /**
     * Checks whether the user has permissions to read a setting.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::READABLE, $this->get_capability(), $request);
    }
    /**
     * Gets a setting for the request.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>>|mixed $request
     *
     * @param mixed|WP_REST_Request $request request object
     * @return Setting|null
     */
    protected function get_setting_for_request($request): ?Setting
    {
        if (!$request instanceof WP_REST_Request) {
            return null;
        }
        $setting_name = $request->get_param('name');
        return is_string($setting_name) ? Settings_Registry::get_setting($setting_name) : null;
    }
    /**
     * Updates a setting value.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {
        $setting = $this->get_setting_for_request($request);
        if (!$setting) {
            return $this->error_response(Response_Error::NOT_FOUND);
        }
        if (!$request->has_param('value')) {
            return $this->error_response(Response_Error::CANNOT_EDIT, 'Invalid or missing setting value.', Client_Error::BAD_REQUEST);
        }
        $setting_value = $request->get_param('value');
        $setting->set_value($setting_value);
        try {
            $setting->save();
        } catch (Exception $exception) {
            $status_code = $exception->getCode();
            return $this->error_response(Response_Error::CANNOT_EDIT, $exception->getMessage(), is_int($status_code) ? $status_code : null);
        }
        return rest_ensure_response($this->obfuscate_credential_value_from_payload($setting));
    }
    /**
     * Checks whether the user has permissions to update a setting.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::EDITABLE, $this->get_capability(), $request);
    }
    /**
     * Deletes a setting value.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item($request)
    {
        $setting = $this->get_setting_for_request($request);
        if (!$setting) {
            return $this->error_response(Response_Error::NOT_FOUND);
        }
        try {
            $setting->delete();
        } catch (Exception $exception) {
            $status_code = $exception->getCode();
            return $this->error_response(Response_Error::CANNOT_DELETE, $exception->getMessage(), is_int($status_code) ? $status_code : null);
        }
        return rest_ensure_response($this->obfuscate_credential_value_from_payload($setting));
    }
    /**
     * Checks whether the user has permissions to update a setting.
     *
     * @since 1.1.0
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @param WP_REST_Request $request request object
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::DELETABLE, $this->get_capability(), $request);
    }
    /**
     * Gets the public item schema for a collection of settings.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function get_collection_item_schema(): array
    {
        return ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => $this->rest_base, 'type' => 'object', 'properties' => ['type' => 'array', 'items' => ['type' => 'object', 'items' => $this->get_setting_schema()]]];
    }
    /**
     * Gets the public item schema for an individual setting.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    public function get_item_schema(): array
    {
        return ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => rtrim($this->rest_base, 's'), 'type' => 'object', 'properties' => $this->get_setting_schema()];
    }
    /**
     * Gets the setting schema.
     *
     * @since 1.1.0
     *
     * @return array<string, mixed>
     */
    protected function get_setting_schema(): array
    {
        return ['name' => ['type' => 'string', 'description' => __('Setting name.', static::plugin()->textdomain()), 'required' => \true, 'readonly' => \true], 'type' => ['type' => 'string', 'description' => __('Setting type.', static::plugin()->textdomain()), 'required' => \true, 'readonly' => \true], 'attributes' => ['type' => ['object', 'null'], 'description' => __('Setting attributes.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'required' => ['type' => 'boolean', 'description' => __('Whether the setting is required.', static::plugin()->textdomain()), 'required' => \false, 'default' => \false, 'readonly' => \true], 'default' => ['type' => ['integer', 'float', 'string', 'array', 'object', 'boolean', 'null'], 'description' => __('Default setting value.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'description' => ['type' => 'string', 'description' => __('Setting description.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'instructions' => ['type' => 'string', 'description' => __('Setting instructions.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'placeholder' => ['type' => 'string', 'description' => __('Setting placeholder.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'title' => ['type' => 'string', 'description' => __('Setting title.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'value' => ['type' => ['integer', 'float', 'string', 'array', 'object', 'boolean', 'null'], 'description' => __('Setting value.', static::plugin()->textdomain()), 'required' => \false], 'formatted_value' => ['type' => ['integer', 'float', 'string', 'array', 'object', 'boolean', 'null'], 'description' => __('Formatted setting value.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true], 'store' => ['type' => 'object', 'description' => __('Setting store.', static::plugin()->textdomain()), 'required' => \false, 'readonly' => \true]];
    }
}
