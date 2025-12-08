<?php
/**
 * Plugin Name: AAA Catalog Attribute Pill
 * Description: Shows multiple attribute pills under product titles (supports taxonomy + custom attributes + meta for THC). Verbose logging for debugging.
 * Version: 1.1.5
 * Author: Webmaster Workflow
 * File: wp-content/plugins/aaa-catalog-attribute-pill/aaa-catalog-attribute-pill.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ==== Settings ==== */
define( 'AAA_CAP_DEBUG', false );          // toggle logging for this file
define( 'AAA_CAP_FIRST_ONLY', true );     // show only first term when multi-valued

/* Pills in order */
function aaa_cap_specs() {
	return [
		['type'=>'attr','name'=>'classification'],
		['type'=>'attr','name'=>'lkd-flower-weight'],
		['type'=>'attr','name'=>'preroll-count'],
		['type'=>'thc'], // THC special
		['type'=>'attr','name'=>'is-infused'],
		['type'=>'attr','name'=>'disposable'],
		['type'=>'attr','name'=>'gummy-count'],
		['type'=>'attr','name'=>'flower-size'],
		['type'=>'attr','name'=>'flavor'],
		['type'=>'attr','name'=>'effects'],
	];
}

/* ==== Utils ==== */
function aaa_cap_log($m,$c=[]){ if(!AAA_CAP_DEBUG)return; if(!is_string($m))$m=wp_json_encode($m); if($c)$m.=' '.wp_json_encode($c); error_log('[AAA-CAP] '.$m); }
function aaa_cap_norm($s){ $s=strtolower(trim((string)$s)); return trim(preg_replace('/[^a-z0-9]+/','_',$s),'_'); }
function aaa_cap_pct($raw){ $n=preg_replace('/[^0-9.]+/','',(string)$raw); if($n==='')return''; $n=rtrim(rtrim($n,'0'),'.'); return $n.'%'; }

/* Resolve attribute by slug from taxonomy OR custom attribute */
function aaa_cap_get_attr(WC_Product $p, $slug){
	$pid=$p->get_id(); $norm=aaa_cap_norm($slug); $tax='pa_'.str_replace('_','-',$norm);

	// taxonomy first
	$tax_exists = taxonomy_exists($tax);
	aaa_cap_log('attr check start', ['pid'=>$pid,'slug'=>$slug,'tax'=>$tax,'tax_exists'=>$tax_exists]);

	if( $tax_exists ){
		$terms = wc_get_product_terms($pid,$tax,['fields'=>'names']);
		aaa_cap_log('attr tax terms', ['pid'=>$pid,'tax'=>$tax,'terms'=>$terms]);
		if(!is_wp_error($terms) && !empty($terms)){
			$val = AAA_CAP_FIRST_ONLY ? $terms[0] : implode(', ',$terms);
			return $val;
		}
	}

	// custom attribute (non-taxonomy) shown in "Additional information"
	$attrs = $p->get_attributes();
	$names = [];
	foreach($attrs as $a){ if($a instanceof WC_Product_Attribute){ $names[] = $a->get_name(); } }
	aaa_cap_log('attr custom scan', ['pid'=>$pid,'available_names'=>$names]);

	foreach( $attrs as $attr ){
		if( !($attr instanceof WC_Product_Attribute) ) continue;
		$name = $attr->get_name();
		// skip taxonomy here
		if( taxonomy_exists($name) ) continue;
		if( aaa_cap_norm($name) !== $norm ) continue;

		$opts = $attr->get_options();
		aaa_cap_log('attr custom hit', ['pid'=>$pid,'name'=>$name,'options'=>$opts]);
		if( empty($opts) ) continue;
		$val = is_array($opts) ? reset($opts) : $opts;
		return (string)$val;
	}
	aaa_cap_log('attr not found', ['pid'=>$pid,'slug'=>$slug]);
	return '';
}

/* THC resolver: attr 'thc_percentage' (tax or custom) → parent meta → alt tax → any custom attr containing "thc" → variation meta */
function aaa_cap_get_thc(WC_Product $p){
	$pid=$p->get_id();
	aaa_cap_log('THC start', ['pid'=>$pid,'title'=>get_the_title($pid)]);

	// 1) exact attribute thc_percentage
	$attr = aaa_cap_get_attr($p,'thc_percentage');
	aaa_cap_log('THC from attribute thc_percentage (raw)', ['pid'=>$pid,'value'=>$attr]);
	if($attr!==''){ $val=aaa_cap_pct($attr); if($val!==''){ aaa_cap_log('THC resolved from attribute', ['pid'=>$pid,'val'=>$val]); return $val; } }

	// 2) parent meta
	$m = get_post_meta($pid,'thc_percentage',true);
	aaa_cap_log('THC parent meta (raw)', ['pid'=>$pid,'meta'=>$m]);
	if($m!=='' && $m!==null){ $val=aaa_cap_pct($m); if($val!==''){ aaa_cap_log('THC resolved from parent meta', ['pid'=>$pid,'val'=>$val]); return $val; } }

	// 3) alternate taxonomies
	foreach(['pa_thc-percentage','pa_thc'] as $tax){
		$exists = taxonomy_exists($tax);
		$terms = $exists ? wc_get_product_terms($pid,$tax,['fields'=>'names']) : [];
		aaa_cap_log('THC alt tax check', ['pid'=>$pid,'tax'=>$tax,'exists'=>$exists,'terms'=>$terms]);
		if($exists && !is_wp_error($terms) && !empty($terms)){
			$pick = AAA_CAP_FIRST_ONLY ? $terms[0] : implode(', ',$terms);
			$val = aaa_cap_pct($pick); if($val!==''){ aaa_cap_log('THC resolved from alt tax', ['pid'=>$pid,'tax'=>$tax,'val'=>$val]); return $val; }
		}
	}

	// 4) any custom attr containing 'thc'
	$attrs = $p->get_attributes();
	foreach($attrs as $attr_obj){
		if(!($attr_obj instanceof WC_Product_Attribute)) continue;
		$name = $attr_obj->get_name();
		if( taxonomy_exists($name) ) continue;
		$norm = aaa_cap_norm($name);
		if( strpos($norm,'thc') === false ) continue;
		$opts = $attr_obj->get_options();
		aaa_cap_log('THC custom candidate', ['pid'=>$pid,'name'=>$name,'options'=>$opts]);
		if(empty($opts)) continue;
		$val = aaa_cap_pct(is_array($opts)?reset($opts):$opts);
		if($val!==''){ aaa_cap_log('THC resolved from custom attr', ['pid'=>$pid,'name'=>$name,'val'=>$val]); return $val; }
	}

	// 5) variation meta
	if($p->is_type('variable')){
		$ids = $p->get_children();
		$def = method_exists($p,'get_default_variation_id') ? $p->get_default_variation_id() : 0;
		$try = $def ? array_merge([$def], array_diff($ids,[$def])) : $ids;
		aaa_cap_log('THC variation scan', ['pid'=>$pid,'default'=>$def,'children'=>$ids]);
		foreach($try as $vid){
			$vm = get_post_meta($vid,'thc_percentage',true);
			aaa_cap_log('THC variation meta (raw)', ['pid'=>$pid,'vid'=>$vid,'meta'=>$vm]);
			if($vm!=='' && $vm!==null){
				$val = aaa_cap_pct($vm);
				if($val!==''){ aaa_cap_log('THC resolved from variation meta', ['pid'=>$pid,'vid'=>$vid,'val'=>$val]); return $val; }
			}
		}
	}

	aaa_cap_log('THC not found', ['pid'=>$pid]);
	return '';
}

/* ==== Render ==== */
add_action('woocommerce_after_shop_loop_item_title', function(){
	if(is_admin() && !wp_doing_ajax()) return;
	global $product; if(!$product instanceof WC_Product) return;

	$pills=[];
	aaa_cap_log('Render start', ['pid'=>$product->get_id(),'title'=>get_the_title($product->get_id())]);

	foreach( aaa_cap_specs() as $spec ){
		$label='';
		if($spec['type']==='thc'){
			$label = aaa_cap_get_thc($product);
		}elseif($spec['type']==='attr'){
			$val = aaa_cap_get_attr($product,$spec['name']);
			aaa_cap_log('Attr resolved', ['pid'=>$product->get_id(),'name'=>$spec['name'],'val'=>$val]);
			if($val==='') continue;
			$raw = strtolower(trim((string)$val));
			if(!empty($spec['map_yes'])){
				if($raw!=='yes'){ aaa_cap_log('Attr skip (not yes)', ['pid'=>$product->get_id(),'name'=>$spec['name'],'raw'=>$raw]); continue; }
				$label = $spec['map_yes'];
			}else{
				$label = $val;
			}
		}
		$label = trim((string)$label);
		if($label!==''){ $pills[]=$label; }
	}

	aaa_cap_log('Pills result', ['pid'=>$product->get_id(),'pills'=>$pills]);

	if(empty($pills)) return;

	echo '<div class="aaa-cat-attr-wrap" aria-label="product-attributes">';
	foreach($pills as $t){ echo '<span class="aaa-cat-attr-pill">'.esc_html($t).'</span>'; }
	echo '</div>';
},6);

/* ==== Styles (scoped) ==== */
add_action('wp_enqueue_scripts',function(){
	if(!(is_shop()||is_product_taxonomy()||is_product_category()||is_product_tag())) return;
	$css='.aaa-cat-attr-wrap{margin:.2rem 0 .4rem 0;display:flex;flex-wrap:wrap;gap:.25rem .35rem}
.aaa-cat-attr-pill{display:inline-block;padding:.22rem .5rem;border-radius:999px;font-size:.8rem;background:#eef6ff;border:1px solid #cfe6ff;font-weight:600;line-height:1}
ul.products li.product:hover .aaa-cat-attr-pill{background:#e8f2ff}';
	wp_register_style('aaa-cap-css',false); wp_enqueue_style('aaa-cap-css'); wp_add_inline_style('aaa-cap-css',$css);
});
