<?php

declare(strict_types=1);

namespace Tmeister\JWTAuthPro\Actions\Admin\Token;

use Tmeister\JWTAuthPro\Services\TokenService;
use WP_Error;

class ListTokensAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function execute(array $params): array|WP_Error
    {
        $result = $this->tokenService->listTokens($params);

        if (is_wp_error($result)) {
            return $result;
        }

        $items = array_map(function ($token) {
            $tokenArray = $token->jsonSerialize();
            $user = get_user_by('id', $tokenArray['user_id']);

            if ($user) {
                $tokenArray['user_nicename'] = $user->display_name;
                $tokenArray['user_link'] = get_edit_user_link($user->ID);
            }

            return $tokenArray;
        }, $result['items']);

        return [
            'items' => $items,
            'pagination' => $result['pagination'],
        ];
    }
}
