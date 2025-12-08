<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\v1;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Telemetry\Persona;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Onboarding;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\API\REST\Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
/**
 * Onboarding controller.
 *
 * @since 1.0.0
 */
final class Onboarding_Controller extends Controller
{
    /** @var string rest base */
    protected $rest_base = 'onboarding';
    /**
     * Registers the routes to handle the onboarding from the REST API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route($this->get_namespace(), $this->build_route(), [['methods' => WP_REST_Server::READABLE, 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'get_items_permissions_check']], ['methods' => WP_REST_Server::EDITABLE, 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'update_item_permissions_check'], 'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE)], ['methods' => WP_REST_Server::DELETABLE, 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'delete_item_permissions_check']], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Prepares the onboarding data for the REST response.
     *
     * @since 1.0.0
     *
     * @param Onboarding|null $onboarding
     * @return array<string, mixed>
     */
    protected function prepare_onboarding_response(?Onboarding $onboarding = null): array
    {
        if (!$onboarding) {
            $onboarding = Onboarding::instance();
        }
        return $onboarding->to_array();
    }
    /**
     * Gets the onboarding data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request request object
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        /** @var WP_Error|WP_REST_Response $response */
        $response = rest_ensure_response($this->prepare_onboarding_response());
        return $response;
    }
    /**
     * Checks whether the user has permissions to read the onboarding data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request request object
     *
     * @phpstan-param  WP_REST_Request<array<mixed>> $request
     *
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::READABLE, Onboarding::get_capability(), $request);
    }
    /**
     * Updates the onboarding data from a request.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request request object
     *
     * @phpstan-param  WP_REST_Request<array<mixed>> $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {
        $onboarding = Onboarding::instance();
        foreach ($request->get_params() as $key => $value) {
            if (!property_exists($onboarding, $key)) {
                continue;
            }
            // read-only properties
            if (in_array($key, ['last_updated', 'version'], \true)) {
                continue;
            }
            if ('persona' === $key) {
                $value = Persona::seed(Strings::is_json($value) ? json_decode($value, \true) : (array) $value);
            } elseif ('progress' === $key) {
                $value = is_string($value) ? json_decode($value, \true) : (array) $value;
            } elseif (!is_string($value)) {
                continue;
            }
            $method = 'set_' . $key;
            $onboarding->{$method}($value);
        }
        $onboarding->update($onboarding->get_status());
        /** @var WP_Error|WP_REST_Response $response */
        $response = rest_ensure_response($this->prepare_onboarding_response($onboarding));
        return $response;
    }
    /**
     * Checks whether the user has permissions to update the onboarding data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request request object
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::EDITABLE, Onboarding::get_capability(), $request);
    }
    /**
     * Resets the onboarding data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item($request)
    {
        Onboarding::instance()->reset();
        /** @var WP_Error|WP_REST_Response $response */
        $response = rest_ensure_response($this->prepare_onboarding_response());
        return $response;
    }
    /**
     * Checks whether the user has permissions to delete the onboarding data.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request
     *
     * @phpstan-param WP_REST_Request<array<mixed>> $request
     *
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request)
    {
        return $this->permissions_check(WP_REST_Server::DELETABLE, Onboarding::get_capability(), $request);
    }
    /**
     * Retrieves the item's schema, conforming to JSON Schema.
     *
     * @link https://json-schema.org/ JSON Schema
     *
     * @since 1.0.0
     *
     * @return array<string, mixed>
     */
    public function get_item_schema(): array
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => $this->rest_base, 'type' => 'object', 'properties' => ['status' => ['type' => 'string', 'enum' => Onboarding\Status::values(), 'description' => __('The onboarding status.', self::plugin()->textdomain())], 'step' => ['type' => 'string', 'description' => __('The current onboarding step.', self::plugin()->textdomain()), 'required' => \true], 'progress' => ['type' => ['object', 'null'], 'description' => __('The current onboarding progress.', self::plugin()->textdomain()), 'required' => \true], 'persona' => ['type' => ['object', 'null'], 'description' => __('The onboarding persona.', self::plugin()->textdomain())], 'version' => ['type' => 'string', 'description' => __('The onboarded version.', self::plugin()->textdomain()), 'readonly' => \true], 'last_updated' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => __('The last updated date or null if never updated.', self::plugin()->textdomain()), 'readonly' => \true]]];
        return $this->add_additional_fields_schema($schema);
    }
}
