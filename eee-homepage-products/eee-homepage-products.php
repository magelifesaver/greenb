<?php
/**
 * Plugin Name: EEE Homepage Products
 * Description: Homepage product carousels (best-sellers, brands, per-brand limits, attributes). Supports hide flags for attribute terms.
 * Version:     1.8.0
 * Author:      Webmaster Workflow
 * Text Domain: eee-homepage-products
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shortcode: [home_carousel category="flower" title="Popular Flowers" limit="8" per_brand="2"]
 */
function eee_home_carousel_shortcode( $atts ) {
	$atts = shortcode_atts( [
		'category'  => '',
		'title'     => 'Popular Products',
		'limit'     => 8,
		'per_brand' => 2,
	], $atts );

	$cache_key = 'eee_home_carousel_' . md5( json_encode( $atts ) );
	if ( $cached = get_transient( $cache_key ) ) {
		return $cached;
	}

	$products = wc_get_products( [
		'status'   	=> 'publish',
		'limit'    	=> intval( $atts['limit'] ) * 5,
		'category' 	=> [ $atts['category'] ],
		'orderby'  	=> 'meta_value_num',
		'meta_key' 	=> 'total_sales',
		'order'    	=> 'DESC',
		'return'   	=> 'ids',
		'stock_status'	=> 'instock',
	] );

	$per_brand         = max( 1, intval( $atts['per_brand'] ) );
	$brand_counts      = [];
	$filtered_products = [];

	foreach ( $products as $pid ) {
		$p = wc_get_product( $pid );
		if ( ! $p || ! $p->is_type( 'simple' ) ) { continue; }

		$brand_slugs = wc_get_product_terms( $pid, 'berocket_brand', [ 'fields' => 'slugs' ] );
		$brand_key   = $brand_slugs ? $brand_slugs[0] : 'no-brand';
		$count       = isset( $brand_counts[ $brand_key ] ) ? $brand_counts[ $brand_key ] : 0;

		if ( $count >= $per_brand ) { continue; }

		$filtered_products[]        = $pid;
		$brand_counts[ $brand_key ] = $count + 1;

		if ( count( $filtered_products ) >= intval( $atts['limit'] ) ) { break; }
	}

	ob_start(); ?>
	<div class="eee-home-carousel" data-ehp>
		<div class="carousel-header">
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>
			<?php if ( $atts['category'] ) : ?>
				<a class="view-all" href="<?php echo esc_url( get_term_link( $atts['category'], 'product_cat' ) ); ?>">View All</a>
			<?php endif; ?>
		</div>

		<div class="carousel-track" data-ehp-track>
			<?php foreach ( $filtered_products as $pid ) :
				$p = wc_get_product( $pid ); ?>
			<div class="carousel-item">

				<?php
				// Brand
				$brand_names = wc_get_product_terms( $pid, 'berocket_brand', [ 'fields' => 'names' ] );
				$brand_name  = $brand_names ? $brand_names[0] : '';
				if ( $brand_name ) {
					echo '<div class="eee-brand">' . esc_html( $brand_name ) . '</div>';
				}
				?>

				<a href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
					<div class="eee-image-wrap">
						<?php echo $p->get_image( 'woocommerce_thumbnail', [ 'loading' => 'lazy' ] ); ?>
					</div>
					<h3><?php echo esc_html( $p->get_name() ); ?></h3>
				</a>

				<?php
				// Price
				echo '<div class="eee-price">';
				if ( $p->is_on_sale() ) {
					echo '<span class="regular">' . wc_price( $p->get_regular_price() ) . '</span>';
					echo '<span class="sale">'    . wc_price( $p->get_sale_price() )    . '</span>';
				} else {
					echo '<span class="regular only">' . wc_price( $p->get_price() ) . '</span>';
				}
				echo '</div>'; ?>

				<div class="eee-bottom">
					<a href="<?php echo esc_url( '?add-to-cart=' . $pid ); ?>"
						class="button add_to_cart_button ajax_add_to_cart"
						data-product_id="<?php echo esc_attr( $pid ); ?>">Add to Cart</a>

					<?php
					// Attributes (skip hidden terms)
					$attrs = $p->get_attributes();
					if ( $attrs ) {
						$all_terms = [];
						foreach ( $attrs as $attr_name => $attr_obj ) {
							$terms = wc_get_product_terms( $pid, $attr_name, [ 'fields' => 'all' ] );
							foreach ( $terms as $term ) {
								$term_hidden = get_term_meta( $term->term_id, '_eee_hide_term_from_carousel', true );
								if ( $term_hidden === 'yes' ) continue;
								$all_terms[] = $term->name;
							}
						}
						if ( $all_terms ) {
							$max_pills = 4;
							$visible   = array_slice( $all_terms, 0, $max_pills );
							$extra_cnt = max( 0, count( $all_terms ) - $max_pills );
							echo '<div class="eee-attributes">';
							foreach ( $visible as $t ) {
								echo '<span class="attr-label">' . esc_html( $t ) . '</span>';
							}
							if ( $extra_cnt > 0 ) {
								echo '<span class="attr-label attr-more">+' . intval( $extra_cnt ) . '</span>';
							}
							echo '</div>';
						}
					}
					?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	$out = ob_get_clean();
	set_transient( $cache_key, $out, HOUR_IN_SECONDS );
	return $out;
}
add_shortcode( 'home_carousel', 'eee_home_carousel_shortcode' );
function eee_brand_carousel_shortcode( $atts ) {
    if ( ! is_product() ) return ''; // only on product pages

    global $product;
    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) return '';

    $product_id = $product->get_id();

    // Get brand terms
    $brands = wc_get_product_terms( $product_id, 'berocket_brand', [ 'fields' => 'ids' ] );
    if ( empty( $brands ) ) return '';

    $brand_id = $brands[0];

    // Query products of this brand
    $args = [
        'status'       => 'publish',
        'limit'        => 8,
        'exclude'      => [ $product_id ],
        'type'         => 'simple',
        'stock_status' => 'instock',
        'tax_query'    => [
            [
                'taxonomy' => 'berocket_brand',
                'field'    => 'term_id',
                'terms'    => $brand_id,
            ],
        ],
        'return'       => 'ids',
    ];

    $products = wc_get_products( $args );
    if ( empty( $products ) ) return '';

    ob_start(); ?>
    <div class="eee-home-carousel eee-brand-carousel">
        <div class="carousel-header">
            <h2><?php echo esc_html__( 'More from this brand', 'eee-homepage-products' ); ?></h2>
        </div>
        <div class="carousel-track" data-ehp-track>
            <?php foreach ( $products as $pid ) :
                $p = wc_get_product( $pid ); ?>
                <div class="carousel-item">
                    <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
                        <div class="eee-image-wrap">
                            <?php echo $p->get_image( 'woocommerce_thumbnail', [ 'loading' => 'lazy' ] ); ?>
                        </div>
                        <h3><?php echo esc_html( $p->get_name() ); ?></h3>
                    </a>
                    <div class="eee-price">
                        <?php if ( $p->is_on_sale() ) {
                            echo '<span class="regular">' . wc_price( $p->get_regular_price() ) . '</span>';
                            echo '<span class="sale">' . wc_price( $p->get_sale_price() ) . '</span>';
                        } else {
                            echo '<span class="regular only">' . wc_price( $p->get_price() ) . '</span>';
                        } ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'brand_carousel', 'eee_brand_carousel_shortcode' );


/** Styles */
add_action( 'wp_head', function () { ?>
<style>
.eee-home-carousel{margin:30px 0}
.eee-home-carousel .carousel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.eee-home-carousel .carousel-header h2{font-size:20px;margin:0}
.eee-home-carousel .view-all{font-size:14px;text-decoration:underline}
.eee-home-carousel .view-all:hover {color: #ffdb3c;text-decoration:none}

.eee-home-carousel .carousel-track{display:flex;gap:16px;overflow-x:auto;-webkit-overflow-scrolling:touch;align-items:stretch;padding-bottom:6px;scrollbar-width:none}
.eee-home-carousel:hover .carousel-track{scrollbar-width:none}
.eee-home-carousel .carousel-track::-webkit-scrollbar{height:0}
.eee-home-carousel:hover .carousel-track::-webkit-scrollbar{height:6px}
.eee-home-carousel .carousel-track::-webkit-scrollbar-thumb{background:#bbb;border-radius:3px}
.eee-home-carousel .carousel-track::-webkit-scrollbar-track{background:transparent}
.eee-home-carousel .carousel-item{position:relative;flex:0 0 220px;display:flex;flex-direction:column;background:#fff;border:1px solid #eee;border-radius:10px;padding:16px}
.eee-home-carousel .eee-brand{margin:0 0 6px;font-size:13px;font-weight:700;color:#1f2937;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.eee-home-carousel .eee-badge{position:absolute;top:10px;left:10px;background:#111;color:#fff;padding:2px 6px;font-size:12px;border-radius:4px}
.eee-home-carousel .eee-image-wrap{width:100%;height:190px;display:flex;align-items:center;justify-content:center;margin-bottom:10px}
.eee-home-carousel .eee-image-wrap img{max-height:100%;max-width:100%;object-fit:contain}
.eee-home-carousel h3{font-size:15px;margin:6px 0 0;line-height:1.3;min-height:40px;max-height:40px;overflow:hidden}
.eee-home-carousel .eee-price{margin:10px 0 6px}
.eee-home-carousel .eee-price .regular{color:#8a8a8a;text-decoration:line-through;margin-right:6px;font-size:13px}
.eee-home-carousel .eee-price .regular.only{text-decoration:none;color:#111}
.eee-home-carousel .eee-price .sale{color:#e11d48;font-size:16px;font-weight:700}
.eee-home-carousel .eee-bottom{margin-top:auto}
.eee-home-carousel .button{display:block;margin:10px auto 8px;padding:3px 3px;background:#ffdb3c;color:#000;font-weight:800;border-radius:8px;text-align:center;text-transform: uppercase;}
.eee-home-carousel .eee-attributes{display:flex;flex-wrap:wrap;gap:4px;justify-content:center;min-height:40px;max-height:48px;}
.eee-home-carousel .attr-label{background:#f3f4f6;border:1px solid #e5e7eb;border-radius:14px;padding:2px 6px;font-size:12px;line-height:18px;color:#374151;white-space:nowrap;max-height: 24px}
.eee-home-carousel .attr-label.attr-more{background:#eef6ff;border-color:#cfe0ff;color:#1d4ed8;font-weight:600}
/* === Responsive carousel: prioritize height scaling === */
@media (max-width: 768px) {
  .eee-home-carousel .carousel-item {
    flex: 0 0 180px;   /* keep a bit wider */
    padding: 10px;
  }
  .eee-home-carousel .eee-image-wrap {
    height: 120px;     /* much shorter image box */
  }
  .eee-home-carousel h3 {
    font-size: 14px;
    line-height: 1.2;
    min-height: auto;
    max-height: 32px;
  }
  .eee-home-carousel .eee-price {
    margin: 6px 0;
  }
  .eee-home-carousel .eee-price .sale {
    font-size: 14px;
  }
  .eee-home-carousel .button {
    font-size: 12px;
    padding: 4px 6px;
  }
}

@media (max-width: 480px) {
  .eee-home-carousel .carousel-item {
    flex: 0 0 160px;   /* a bit wider than before */
    padding: 8px;
  }
  .eee-home-carousel .eee-image-wrap {
    height: 100px;     /* short image box */
  }
  .eee-home-carousel h3 {
    font-size: 13px;
    max-height: 28px;
  }
  .eee-home-carousel .button {
    font-size: 11px;
    padding: 3px 5px;
  }
}
</style>
<?php });

/** Minimal JS: drag scroll only (no wheel hijack) */
add_action( 'wp_footer', function () { ?>
<script>
(function(){
  document.querySelectorAll('[data-ehp-track]').forEach(function(track){
    let isDown=false,startX,scrollLeft;
    track.addEventListener('mousedown',function(e){
      isDown=true;startX=e.pageX-track.offsetLeft;scrollLeft=track.scrollLeft;e.preventDefault();
    });
    ['mouseleave','mouseup'].forEach(ev=>track.addEventListener(ev,()=>isDown=false));
    track.addEventListener('mousemove',function(e){
      if(!isDown)return;const x=e.pageX-track.offsetLeft;track.scrollLeft=scrollLeft-(x-startX);
    });
  });
})();
</script>
<?php });

/* =========================
 * ADMIN META: Term-level only
 * ========================= */
add_action( 'init', function() {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) return;
	foreach ( wc_get_attribute_taxonomies() as $a ) {
		$tax = wc_attribute_taxonomy_name( $a->attribute_name );

		// Add New Term
		add_action( "{$tax}_add_form_fields", function() {
			?>
			<div class="form-field">
				<label>
					<input type="checkbox" name="eee_hide_term_from_carousel" value="yes">
					<?php esc_html_e( 'Hide in Homepage Carousel', 'eee-homepage-products' ); ?>
				</label>
			</div>
			<?php
		});

		// Edit Term
		add_action( "{$tax}_edit_form_fields", function( $term ) {
			$val = get_term_meta( $term->term_id, '_eee_hide_term_from_carousel', true );
			?>
			<tr class="form-field">
				<th scope="row"><?php esc_html_e( 'Hide in Homepage Carousel', 'eee-homepage-products' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="eee_hide_term_from_carousel" value="yes" <?php checked( $val, 'yes' ); ?>>
						<?php esc_html_e( 'Do not display this term in homepage carousels', 'eee-homepage-products' ); ?>
					</label>
				</td>
			</tr>
			<?php
		});

		// Save Add + Edit
		$save_cb = function( $term_id ) {
			$val = isset( $_POST['eee_hide_term_from_carousel'] ) ? 'yes' : 'no';
			update_term_meta( $term_id, '_eee_hide_term_from_carousel', $val );
		};
		add_action( "created_{$tax}", $save_cb );
		add_action( "edited_{$tax}",  $save_cb );
	}
});
