<?php
namespace MBViews\Fields\Post;

use MBViews\Fields\BaseRenderer;
use MBViews\Fields\Image\Renderer as Image;

class Renderer extends BaseRenderer {
	public static function get_single_value( $value ) {
		if ( ! $value ) {
			return null;
		}
		$post = get_post( $value );
		if ( ! $post ) {
			return null;
		}
		return array_merge( (array) $post, [
			'ID'            => $post->ID,
			'title'         => $post->post_title,
			'excerpt'       => $post->post_excerpt,
			'content'       => $post->post_content,
			'url'           => get_permalink( $post ),
			'slug'          => $post->post_name,
			'date'          => $post->post_date,
			'modified_date' => $post->post_modified,
			'thumbnail'     => Image::get_post_thumbnail( $post ),
		] );
	}
}
