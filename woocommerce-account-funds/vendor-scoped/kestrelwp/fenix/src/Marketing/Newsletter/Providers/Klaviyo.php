<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter\Providers;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Exceptions\Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Request;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Http\Url;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Marketing\Newsletter\Newsletter_Provider;
use WP_User;
/**
 * Klaviyo newsletter provider.
 *
 * Subscribes users to a Klaviyo list using the public Subscribe API.
 *
 * @link https://developers.klaviyo.com/en/reference/create_client_subscription
 *
 * @since 1.8.0
 */
final class Klaviyo extends Newsletter_Provider
{
    /** @var string Klaviyo API endpoint for list subscriptions */
    private const SUBSCRIBE_ENDPOINT = 'https://a.klaviyo.com/client/subscriptions';
    /**
     * Constructor.
     *
     * @since 1.8.0
     *
     * @param array<string, mixed> $config
     *
     * @phpstan-param array{
     *     company_id: string,
     *     list_id: string,
     * } $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->config = $config;
    }
    /**
     * Checks if the provider is properly configured.
     *
     * @since 1.8.0
     *
     * @return bool
     */
    public function is_configured(): bool
    {
        return !empty($this->config['company_id']) && !empty($this->config['list_id']);
    }
    /**
     * Returns the signup endpoint URL.
     *
     * @since 1.8.0
     *
     * @return string
     */
    private function signup_endpoint_url(): string
    {
        // appends the public API key as query arg to the request
        return add_query_arg(['company_id' => $this->config['company_id']], self::SUBSCRIBE_ENDPOINT);
    }
    /**
     * Subscribes an email address to the Klaviyo newsletter list.
     *
     * @since 1.8.0
     *
     * @param WP_User $user user to subscribe
     * @param array<string, mixed> $metadata optional metadata to include with the subscription
     * @return bool
     * @throws Exception
     */
    public function subscribe(WP_User $user, array $metadata = []): bool
    {
        if (!$this->is_configured()) {
            throw new Exception('Newsletter provider not properly configured.');
        }
        if (!is_email($user->user_email)) {
            throw new Exception('The user email address to subscribe to the newsletter is invalid.');
        }
        /**
         * @see https://developers.klaviyo.com/en/reference/create_client_subscription for expected payload
         */
        $payload = ['data' => ['type' => 'subscription', 'attributes' => ['profile' => ['data' => ['type' => 'profile', 'attributes' => ['email' => $user->user_email, 'first_name' => $user->first_name, 'last_name' => $user->last_name, 'properties' => $metadata, 'subscriptions' => ['email' => ['marketing' => ['consent' => 'SUBSCRIBED']]]]]]], 'relationships' => ['list' => ['data' => ['type' => 'list', 'id' => $this->config['list_id'] ?: '']]]]];
        $data = $payload;
        try {
            $response = Request::POST($this->signup_endpoint_url())->set_body(wp_json_encode($payload))->set_headers(['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json', 'revision' => '2025-10-15'])->send();
            // Klaviyo returns 202 (Accepted) on successful subscription
            if (202 !== $response->get_status()) {
                $data = json_decode($response->get_body(), \true);
                if (is_array($data) && !empty($data['errors'][0]['detail'])) {
                    $error = $data['errors'][0]['detail'];
                } else {
                    $error = sprintf('Klaviyo API returned status code %d', $response->get_status());
                }
                throw new Exception(esc_html($error));
            }
        } catch (Exception $exception) {
            if (empty($error)) {
                $error = $exception->get_message();
            }
            Logger::warning(sprintf('Could not process newsletter signup for admin #%d (%s): %s', $user->ID, $user->user_email, $error), null, $data);
            throw new Exception(esc_html($exception->get_message()), $exception);
            // phpcs:ignore
        }
        return \true;
    }
}
