<?php
if( class_exists('BeRocket_admin_bar_plugin_data') ) {
    class BeRocket_lmp_admin_bar_debug extends BeRocket_admin_bar_plugin_data {
        function __construct() {
            $BeRocket_LMP = BeRocket_LMP::getInstance();
            $this->slug = $BeRocket_LMP->info['plugin_name'];
            $this->name = $BeRocket_LMP->info['norm_name'];
            parent::__construct();
        }
		function get_html() {
			$html = '<div class="brlmp_adminbar_status">';
			$html .= '</div>';
			return $html;
		}
		function get_js() {
			$html = '<script>
            var brlmp_admin_inited = false;
            jQuery(document).ready(function() {
				if( ! brlmp_admin_inited && typeof(the_lmp_js_data) != "undefined" ) {
                    brlmp_admin_inited = true;
					var html = "<h2>STATUS</h2>";
					
					html += "<div class=\'brlmp_adminbar_status_element\'>Products";
					try {
						var products_elements = jQuery(the_lmp_js_data.products).length;
						var error = false;
						if( products_elements == 0 ) {
							error = "Products element not detected on page";
						} else if( products_elements > 1 ) {
							error = "Multiple Products element detected on page("+products_elements+"). Plugin do not work with multiple products list";
						}
						if( error === false ) {
							html += "<span class=\'dashicons dashicons-yes\' title=\'Products element detected on page\'></span>";
						} else {
							html += "<span class=\'dashicons dashicons-no\' title=\'"+error+"\'></span>";
						}
					} catch(e) {
						html = +"<strong>ERROR</strong>";
						console.log(e);
					}
					html += "</div>";
                    if( products_elements == 1 ) {
                        html += "<div class=\'brlmp_adminbar_status_element\'>Pagination";
                        var pagination_elements = 0;
                        try {
                            pagination_elements = jQuery(the_lmp_js_data.pagination).length;
                            var error = false;
                            if( pagination_elements == 0 ) {
                                error = "Pagination element not detected. If page has pagination or infinite scroll/load more button, then Please check that selectors setuped correct";
                            } else if( pagination_elements > 1 ) {
                                error = "Multiple Pagination element detected on page("+pagination_elements+"). It can cause issue if pagination from different products list";
                            }
                            if( error === false ) {
                                html += "<span class=\'dashicons dashicons-yes\' title=\'Pagination element detected on page\'></span>";
                            } else {
                                html += "<span class=\'dashicons dashicons-no\' title=\'"+error+"\'></span>";
                            }
                        } catch(e) {
                            html = +"<strong>ERROR</strong>";
                            console.log(e);
                        }
                        html += "</div>";
                        
                        if( pagination_elements != 0 ) {
                            html += "<div class=\'brlmp_adminbar_status_element\'>Next Page";
                            try {
                                var $pagination = jQuery(the_lmp_js_data.pagination);
                                if( $pagination.find(the_lmp_js_data.next_page).length > 0 ) {
                                    $next_page = $pagination.find(the_lmp_js_data.next_page);
                                } else {
                                    $next_page = $( the_lmp_js_data.next_page );
                                }
                                var next_page = $next_page.length;
                                var error = false;
                                if( next_page == 0 ) {
                                    error = "Next page not detected. It is OK if products do not have more pages";
                                } else if( next_page != pagination_elements ) {
                                    error = "Page has different pagination elements with same selectors, but different next page elements";
                                }
                                if( error === false ) {
                                    html += "<span class=\'dashicons dashicons-yes\' title=\'Pagination element detected on page\'></span>";
                                } else {
                                    html += "<span class=\'dashicons dashicons-no\' title=\'"+error+"\'></span>";
                                }
                            } catch(e) {
                                html = +"<strong>ERROR</strong>";
                                console.log(e);
                            }
                            html += "</div>";
                        }
                    }
					jQuery(".brlmp_adminbar_status").html(html);
				}
			});</script>';
			return $html;
		}
		function get_css() {
			$html = '<style>
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP {width: 100%;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status{border-top: 1px solid #999; width: 100%; position: relative;margin-bottom: 12px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status{padding-top:4px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status h2{position: absolute; top: -10px; left: 0; background: #2c3338;}

            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status .dashicons{display:inline-block;}

			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status{text-align:center;}
			#wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status_element {line-height:2em;display:inline-block;text-align:center; padding:3px;}
            #wp-admin-bar-berocket_debug_bar .ab-submenu .ab-item .berocket_admin_bar_plugin_block_BeRocket_LMP .brlmp_adminbar_status_element
			</style>';
			return $html;
		}
    }
    new BeRocket_lmp_admin_bar_debug();
}