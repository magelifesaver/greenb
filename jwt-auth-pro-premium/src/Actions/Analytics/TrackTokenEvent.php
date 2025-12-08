<?php

namespace Tmeister\JWTAuthPro\Actions\Analytics;

use Tmeister\JWTAuthPro\Database\AnalyticsRepository;
use Tmeister\JWTAuthPro\Enums\EventStatus;
use Tmeister\JWTAuthPro\Services\SettingsService;
use Tmeister\JWTAuthPro\Traits\HasGeoLocation;
use Tmeister\JWTAuthPro\Traits\HasRequestIP;

class TrackTokenEvent
{
    use HasRequestIP;
    use HasGeoLocation;
    private SettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = new SettingsService();
    }

    public function execute(array $data): int|false
    {
        // Get settings
        $settings = $this->settingsService->getSettings();
        $shouldAnonymizeIp = apply_filters('jwt_auth_anonymize_ip', $settings['data_management']['anonymize_ip'] ?? false);

        // Get the Request IP address
        $ip = $this->getRequestIP();

        $data['country_code'] = $this->getCountryByIP($ip);

        // Anonymize IP if setting is enabled
        $data['ip_address'] = $shouldAnonymizeIp ? wp_privacy_anonymize_ip($ip) : $ip;

        // Sanitize user agent
        $data['user_agent'] = isset($_SERVER['HTTP_USER_AGENT'])
            ? sanitize_text_field($_SERVER['HTTP_USER_AGENT'])
            : '';

        // Sanitize request path
        $data['request_path'] = isset($_SERVER['REQUEST_URI'])
            ? esc_url_raw($_SERVER['REQUEST_URI'])
            : '';

        // Sanitize request method (ensure it's a valid HTTP method)
        $valid_methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';
        $data['request_method'] = in_array($method, $valid_methods, true) ? $method : '';

        // Sanitize blog ID
        $data['blog_id'] = get_current_blog_id();

        // Ensure response time is a positive integer in milliseconds
        $response_time = $data['response_time'] ?? 0;
        $data['response_time'] = (int) ($response_time * 1000);

        // Ensure event type is a safe string
        $data['event_type'] = isset($data['event_type'])
            ? sanitize_text_field($data['event_type'])
            : 'unknown';

        // Convert and validate event status
        $event_status = isset($data['event_status'])
            ? $this->validateEventStatus($data['event_status'])
            : EventStatus::SUCCESS;
        $data['event_status'] = $event_status->value;

        // Sanitize failure reason if status is failure
        if ($event_status === EventStatus::FAILURE && isset($data['failure_reason'])) {
            $data['failure_reason'] = sanitize_text_field($data['failure_reason']);
        } else {
            $data['failure_reason'] = null;
        }

        // Sanitize user ID and token ID
        $data['user_id'] = isset($data['user_id']) ? absint($data['user_id']) : null;
        $data['token_id'] = isset($data['token_id']) ? absint($data['token_id']) : null;

        // Sanitize token family
        $data['token_family'] ??= '';

        $eventId = (new AnalyticsRepository())->insert($data);

        if ($eventId === false) {
            error_log(sprintf(
                'Failed to insert analytics event. Type: %s, Status: %s, Reason: %s',
                $data['event_type'],
                $event_status->toString(),
                $data['failure_reason'] ?? 'N/A'
            ));
        }

        return $eventId;
    }

    private function validateEventStatus(string|EventStatus $status): EventStatus
    {
        if ($status instanceof EventStatus) {
            return $status;
        }

        return EventStatus::fromString($status);
    }
}
