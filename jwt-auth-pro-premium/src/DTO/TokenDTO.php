<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\DTO;

use JsonSerializable;

class TokenDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $user_login,
        public readonly string $user_nicename,
        public readonly string $user_display_name,
        public readonly string $hash,
        public readonly ?string $refresh_token_hash,
        public readonly ?string $token_family,
        public readonly string $issued_at,
        public readonly string $expires_at,
        public readonly ?string $last_used_at,
        public readonly ?string $revoked_at,
        public readonly ?string $refresh_token_expires_at,
        public readonly ?string $user_agent,
        public readonly ?string $ip_address,
        public readonly ?string $metadata = null,
        public readonly int $blog_id = 1,
    ) {}

    public static function fromObject(object|array $data): self
    {
        if (is_array($data)) {
            $data = (object) $data;
        }

        $user = get_user_by('ID', $data->user_id);

        return new self(
            id: (int) $data->id,
            user_id: (int) $data->user_id,
            user_login: $user ? $user->user_login : '',
            user_nicename: $user ? $user->user_nicename : '',
            user_display_name: $user ? $user->display_name : '',
            hash: $data->hash,
            refresh_token_hash: $data->refresh_token_hash ?? null,
            token_family: $data->token_family ?? null,
            issued_at: self::formatDate($data->issued_at),
            expires_at: self::formatDate($data->expires_at),
            last_used_at: $data->last_used_at ? self::formatDate($data->last_used_at) : null,
            revoked_at: $data->revoked_at ? self::formatDate($data->revoked_at) : null,
            refresh_token_expires_at: $data->refresh_token_expires_at ? self::formatDate($data->refresh_token_expires_at) : null,
            user_agent: $data->user_agent ?? null,
            ip_address: $data->ip_address ?? null,
            metadata: $data->metadata ?? null,
            blog_id: (int) ($data->blog_id ?? 1)
        );
    }

    private static function formatDate(?string $date): string
    {
        if (!$date) {
            return '';
        }

        // Keep UTC timezone but use WordPress date format
        $timestamp = strtotime($date . ' UTC');
        return gmdate(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }

    public function jsonSerialize(): array
    {
        $user_link = null;
        if ($this->user_id) {
            $user_link = get_edit_user_link($this->user_id);
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_login' => $this->user_login,
            'user_nicename' => $this->user_nicename,
            'user_display_name' => $this->user_display_name,
            'user_link' => $user_link,
            'hash' => $this->hash,
            'refresh_token_hash' => $this->refresh_token_hash,
            'token_family' => $this->token_family,
            'issued_at' => $this->issued_at,
            'expires_at' => $this->expires_at,
            'last_used_at' => $this->last_used_at,
            'revoked_at' => $this->revoked_at,
            'refresh_token_expires_at' => $this->refresh_token_expires_at,
            'user_agent' => $this->user_agent,
            'ip_address' => $this->ip_address,
            'metadata' => $this->metadata,
            'blog_id' => $this->blog_id,
            'status' => $this->getStatus(),
        ];
    }

    private function getStatus(): string
    {
        if ($this->revoked_at) {
            return 'Revoked';
        }

        // Convert the formatted date back to UTC for comparison
        $expires_at_utc = strtotime($this->expires_at . ' UTC');
        $now_utc = current_time('timestamp', true); // Get current time in UTC

        if ($expires_at_utc <= $now_utc) {
            return 'Expired';
        }

        return 'Active';
    }
}
