<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-ai.php
 * Purpose: Run AI to fill PAIM attributes using provided source URLs.
 * Version: 0.5.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_AI' ) ) { define( 'AAA_PAIM_DEBUG_AI', true ); }

class AAA_Paim_AI {

	public static function run_for_product_set( int $product_id, int $set_id, array $ai_items ) {
		$key = (string) AAA_Paim_Options::get( 'openai_api_key', '' );
		if ( $key === '' ) {
			return new WP_Error( 'no_key', 'OpenAI API key is not configured.' );
		}

		// Collect context
		$title = get_the_title( $product_id );
		$cat_id = (int) get_post_meta( $product_id, '_paim_attribute_set_id', true ); // may be same as $set_id after save
		$set_items = AAA_Paim_Product::get_set_items( $set_id );

		// Load source URLs (one per line) from meta
		$sources = '';
		if ( (int) AAA_Paim_Options::get( 'web_search_enabled', 1 ) === 1 ) {
			// Compose query: Product Title + Brand (if available)
			$query = get_the_title( $product_id );
			$brands = wp_get_object_terms( $product_id, 'berocket_brand', [ 'fields' => 'names' ] );
			if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
				$query .= ' ' . implode( ' ', $brands );
			}
			$allow = (string) AAA_Paim_Options::get( 'web_search_allow', 'weedmaps.com, leafly.com' );
			$allow_domains = array_filter( array_map( 'trim', explode( ',', $allow ) ) );
			$sources = AAA_Paim_Search::run_and_fetch( $query, $allow_domains, 3, 20000 );
		} else {
			// Fallback to stored manual URLs (legacy)
			$raw = (string) get_post_meta( $product_id, '_paim_source_urls', true );
			$urls = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) );
			$sources = self::fetch_sources( $urls, 20000 );
		}
		$schema  = self::build_schema( $ai_items, $set_items );

		$prompt = self::build_prompt( $title, $schema, $sources );

		$response = self::call_openai( $key, $prompt );
		if ( is_wp_error( $response ) ) { return $response; }

		$parsed = json_decode( $response, true );
		if ( ! is_array( $parsed ) ) {
			return new WP_Error( 'bad_json', 'AI reply was not valid JSON.' );
		}

		// Apply results
		$tax_to_apply  = [];
		$meta_to_apply = [];
		foreach ( $schema as $key_slug => $def ) {
			if ( ! array_key_exists( $key_slug, $parsed ) ) { continue; }
			if ( 'taxonomy' === $def['type'] ) {
				$terms = (array) $parsed[ $key_slug ];
				$created = AAA_Paim_Product::ensure_terms( $key_slug, $terms );
				$tax_to_apply[ $key_slug ] = $created;
			} else {
				$val = $parsed[ $key_slug ];
				$meta_to_apply[ $key_slug ] = is_scalar( $val ) ? (string) $val : wp_json_encode( $val );
			}
		}

		$payload = [ 'tax' => $tax_to_apply, 'meta' => $meta_to_apply, 'ai' => [] ];
		$res = AAA_Paim_Product::save_submission( $product_id, $set_id, $payload, false );
		return $res instanceof WP_Error ? $res : [ 'applied' => array_keys( $schema ) ];
	}

	private static function fetch_sources( array $urls, int $max_chars ) : string {
		$out = '';
		foreach ( $urls as $u ) {
			$r = wp_remote_get( $u, [ 'timeout' => 15 ] );
			if ( is_wp_error( $r ) ) { continue; }
			$body = wp_remote_retrieve_body( $r );
			if ( ! $body ) { continue; }
			$text = wp_strip_all_tags( $body );
			$out .= "\n\n===== SOURCE: {$u} =====\n" . $text;
			if ( strlen( $out ) > $max_chars ) {
				$out = substr( $out, 0, $max_chars );
				break;
			}
		}
		return $out;
	}

	private static function build_schema( array $ai_items, array $set_items ) : array {
		$wanted = [];
		$map = [];
		foreach ( $set_items as $it ) {
			$map[ $it['object_key'] ] = $it['object_type'];
		}
		foreach ( $ai_items as $combo ) {
			list( $type, $key ) = explode( ':', $combo, 2 );
			// trust client combo, but fallback to set definition
			$kind = $map[ $key ] ?? $type;
			$wanted[ $key ] = [ 'type' => $kind ];
		}
		return $wanted; // [ 'pa_flavor' => ['type'=>'taxonomy'], 'net_weight'=>['type'=>'meta'] ]
	}

	private static function build_prompt( string $title, array $schema, string $sources ) : string {
		$keys = [];
		foreach ( $schema as $k => $def ) {
			$keys[] = ( 'taxonomy' === $def['type'] )
				? "\"{$k}\": [\"Term A\", \"Term B\"]"
				: "\"{$k}\": \"value\"";
		}
		$shape = "{\n  " . implode( ",\n  ", $keys ) . "\n}";
		return
"Task: Extract or infer product attributes for WooCommerce.

Product Name: {$title}

Rules:
- Use ONLY the information from the sources below. If unknown, return an empty list [] for taxonomy fields or empty string \"\" for meta fields.
- Output STRICT JSON only. Do not include commentary.
- For taxonomy fields, return an array of term NAMES (not IDs).
- For meta fields, return strings.
- If multiple plausible terms exist, include them all.

Return JSON with EXACT keys:

{$shape}

Sources:
{$sources}";
	}

	private static function call_openai( string $key, string $prompt ) {
		$model = 'gpt-4o-mini'; // lightweight & capable
		$resp = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				],
				'timeout' => 40,
				'body' => wp_json_encode( [
					'model' => $model,
					'messages' => [
						[ 'role' => 'system', 'content' => 'You are a precise extraction engine for ecommerce product data.' ],
						[ 'role' => 'user',   'content' => $prompt ],
					],
					'temperature' => 0.1,
					'response_format' => [ 'type' => 'json_object' ],
				] ),
			]
		);
		if ( is_wp_error( $resp ) ) { return $resp; }
		$code = wp_remote_retrieve_response_code( $resp );
		$body = json_decode( wp_remote_retrieve_body( $resp ), true );
		if ( 200 !== (int) $code || empty( $body['choices'][0]['message']['content'] ) ) {
			$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'OpenAI error.';
			return new WP_Error( 'openai_error', $msg );
		}
		return $body['choices'][0]['message']['content'];
	}
}
