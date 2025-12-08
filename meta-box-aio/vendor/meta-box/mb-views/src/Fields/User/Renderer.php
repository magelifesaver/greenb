<?php
namespace MBViews\Fields\User;

use MBViews\Fields\BaseRenderer;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		if ( ! $value ) {
			return null;
		}
		$user = get_userdata( $value );
		if ( ! $user ) {
			return null;
		}
		return [
			'ID'            => $user->ID,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'display_name'  => $user->display_name,
			'login'         => $user->user_login,
			'user_login'    => $user->user_login,
			'nickname'      => $user->nickname,
			'email'         => $user->user_email,
			'user_email'    => $user->user_email,
			'url'           => $user->user_url,
			'user_url'      => $user->user_url,
			'nicename'      => $user->user_nicename,
			'user_nicename' => $user->user_nicename,
			'description'   => $user->description,
			'posts_url'     => get_author_posts_url( $user->ID ),
		];
	}
}
