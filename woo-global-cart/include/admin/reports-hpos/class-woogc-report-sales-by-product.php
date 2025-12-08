<?php
/**
 * Sales By Product Reporting
 *
 * @package WooCommerce/Admin/Reporting
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Report_Sales_By_Product
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WooGC_Report_Sales_By_Product extends WooGC_Admin_Report {

	/**
	 * Chart colors.
	 *
	 * @var array
	 */
	public $chart_colours = array();

	/**
	 * Product ids.
	 *
	 * @var array
	 */
	public $product_ids = array();
    public $blog_id = array();

	/**
	 * Product ids with titles.
	 *
	 * @var array
	 */
	public $product_ids_titles = array();
    
    public $network_sites;

	/**
	 * Constructor.
	 */
	public function __construct() {
		
        global $WooGC;
        
        if ( isset( $_GET['product_ids'] ) ) 
        {
			$this->product_ids = array_filter( array( absint( $_GET['product_ids'] ) ) );
            $this->blog_id      = absint( $_GET['blog_id'] ) ;
            
            $this->network_sites[]    =   get_blog_details( $this->blog_id );
            
		}
        else
        {
            $this->network_sites    =   $WooGC->functions->get_gc_sites( TRUE, 'global_reports' );
        }
	}

	/**
	 * Get the legend for the main chart sidebar.
	 *
	 * @return array
	 */
	public function get_chart_legend() {

		if ( empty( $this->product_ids ) ) {
			return array();
		}

		$legend = array();
        
		$total_sales = $this->get_order_report_data(
			array(
				'data'         => array(
					'_line_total' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_amount',
					),
				),
				'where_meta'   => array(
					'relation' => 'OR',
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => array( '_product_id', '_variation_id' ),
						'meta_value' => $this->product_ids,
						'operator'   => 'IN',
					),
				),
				'query_type'   => 'get_var',
				'filter_range' => true,
                
                'network_sites' =>  $this->network_sites
			)
		);

		$total_items = absint(
			$this->get_order_report_data(
				array(
					'data'         => array(
						'_qty' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_count',
						),
					),
					'where_meta'   => array(
						'relation' => 'OR',
						array(
							'type'       => 'order_item_meta',
							'meta_key'   => array( '_product_id', '_variation_id' ),
							'meta_value' => $this->product_ids,
							'operator'   => 'IN',
						),
					),
					'query_type'   => 'get_var',
					'filter_range' => true,
                    
                    'network_sites' =>  $this->network_sites
				)
			)
		);

		$legend[] = array(
			/* translators: %s: total items sold */
			'title'            => sprintf( __( '%s sales for the selected items', 'woocommerce' ), '<strong>' . wc_price( $total_sales ) . '</strong>' ),
			'color'            => $this->chart_colours['sales_amount'],
			'highlight_series' => 1,
		);

		$legend[] = array(
			/* translators: %s: total items purchased */
			'title'            => sprintf( __( '%s purchases for the selected items', 'woocommerce' ), '<strong>' . ( $total_items ) . '</strong>' ),
			'color'            => $this->chart_colours['item_count'],
			'highlight_series' => 0,
		);

		return $legend;
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$ranges = array(
			'year'       => __( 'Year', 'woocommerce' ),
			'last_month' => __( 'Last month', 'woocommerce' ),
			'month'      => __( 'This month', 'woocommerce' ),
			'7day'       => __( 'Last 7 days', 'woocommerce' ),
		);

		$this->chart_colours = array(
			'sales_amount' => '#3498db',
			'item_count'   => '#d4d9dc',
		);

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = '7day';
		}

		$this->check_current_range_nonce( $current_range );
		$this->calculate_current_range( $current_range );

		include WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php';
	}

	/**
	 * Get chart widgets.
	 *
	 * @return array
	 */
	public function get_chart_widgets() {

		$widgets = array();

		if ( ! empty( $this->product_ids ) ) {
			$widgets[] = array(
				'title'    => __( 'Showing reports for:', 'woocommerce' ),
				'callback' => array( $this, 'current_filters' ),
			);
		}

		$widgets[] = array(
			'title'    => '',
			'callback' => array( $this, 'products_widget' ),
		);

		return $widgets;
	}

	/**
	 * Output current filters.
	 */
	public function current_filters() {

		$this->product_ids_titles = array();

		foreach ( $this->product_ids as $product_id ) {

			$product = wc_get_product( $product_id );

			if ( $product ) {
				$this->product_ids_titles[] = $product->get_formatted_name();
			} else {
				$this->product_ids_titles[] = '#' . $product_id;
			}
		}

		echo '<p><strong>' . wp_kses_post( implode( ', ', $this->product_ids_titles ) ) . '</strong></p>';
		echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'product_ids' ) ) . '">' . esc_html__( 'Reset', 'woocommerce' ) . '</a></p>';
	}

	/**
	 * Output products widget.
	 */
	public function products_widget() {
		
        global $WooGC;
        
        ?>
	
		<h4 class="section_title"><span><?php esc_html_e( 'Top sellers', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_sellers = $this->get_order_report_data(
					array(
						'data'         => array(
							'_product_id' => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => '',
								'name'            => 'product_id',
							),
							'_qty'        => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => 'SUM',
								'name'            => 'order_item_qty',
							),
						),
						'order_by'     => 'order_item_qty DESC',
						'group_by'     => 'product_id',
						'limit'        => 12,
						'query_type'   => 'get_results',
						'filter_range' => true,
                        
                        'network_sites' =>  $WooGC->functions->get_gc_sites( TRUE )
					)
				);

				if ( $top_sellers ) {
					// @codingStandardsIgnoreStart
					foreach ( $top_sellers as $product ) {
                        
                        switch_to_blog( $product->blog_id );
                        
						echo '<tr class="' . ( in_array( $product->product_id, $this->product_ids ) ? 'active' : '' ) . '">
							<td class="count">' . esc_html( $product->order_item_qty ) . '</td>
							<td class="name"><a href="' . esc_url( add_query_arg( array('product_ids' => $product->product_id, 'blog_id'    =>  $product->blog_id )) ) . '">' . esc_html( get_the_title( $product->product_id ) ) . '</a></td>
							<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 7, 'count' ) . '</td>
						</tr>';
                        
                        restore_current_blog();
					}
					// @codingStandardsIgnoreEnd
				} else {
					echo '<tr><td colspan="3">' . esc_html__( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<h4 class="section_title"><span><?php esc_html_e( 'Top freebies', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_freebies = $this->get_order_report_data(
					array(
						'data'         => array(
							'_product_id' => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => '',
								'name'            => 'product_id',
							),
							'_qty'        => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => 'SUM',
								'name'            => 'order_item_qty',
							),
						),
						'where_meta'   => array(
							array(
								'type'       => 'order_item_meta',
								'meta_key'   => '_line_subtotal',
								'meta_value' => '0',
								'operator'   => '=',
							),
						),
						'order_by'     => 'order_item_qty DESC',
						'group_by'     => 'product_id',
						'limit'        => 12,
						'query_type'   => 'get_results',
						'filter_range' => true,
                        
                        'network_sites' =>  $WooGC->functions->get_gc_sites( TRUE )
					)
				);

				if ( $top_freebies ) {
					// @codingStandardsIgnoreStart
					foreach ( $top_freebies as $product ) {
                        
                        switch_to_blog( $product->blog_id );
                        
						echo '<tr class="' . ( in_array( $product->product_id, $this->product_ids ) ? 'active' : '' ) . '">
							<td class="count">' . esc_html( $product->order_item_qty ) . '</td>
							<td class="name"><a href="' . esc_url( add_query_arg( 'product_ids', $product->product_id ) ) . '">' . esc_html( get_the_title( $product->product_id ) ) . '</a></td>
							<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 7, 'count' ) . '</td>
						</tr>';
                        
                        restore_current_blog();
					}
					// @codingStandardsIgnoreEnd
				} else {
					echo '<tr><td colspan="3">' . esc_html__( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<h4 class="section_title"><span><?php esc_html_e( 'Top earners', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_earners = $this->get_order_report_data(
					array(
						'data'         => array(
							'_product_id' => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => '',
								'name'            => 'product_id',
							),
							'_line_total' => array(
								'type'            => 'order_item_meta',
								'order_item_type' => 'line_item',
								'function'        => 'SUM',
								'name'            => 'order_item_total',
							),
						),
						'order_by'     => 'order_item_total DESC',
						'group_by'     => 'product_id',
						'limit'        => 12,
						'query_type'   => 'get_results',
						'filter_range' => true,
                        
                        'network_sites' =>  $WooGC->functions->get_gc_sites( TRUE )
					)
				);

				if ( $top_earners ) {
					// @codingStandardsIgnoreStart
					foreach ( $top_earners as $product ) {
                        
                        switch_to_blog( $product->blog_id );
                        
						echo '<tr class="' . ( in_array( $product->product_id, $this->product_ids ) ? 'active' : '' ) . '">
							<td class="count">' . wc_price( $product->order_item_total ) . '</td>
							<td class="name"><a href="' . esc_url( add_query_arg( 'product_ids', $product->product_id ) ) . '">' . esc_html( get_the_title( $product->product_id ) ) . '</a></td>
							<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 7, 'sales' ) . '</td>
						</tr>';
                        
                        restore_current_blog();
					}
					// @codingStandardsIgnoreEnd
				} else {
					echo '<tr><td colspan="3">' . esc_html__( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<script type="text/javascript">
			jQuery('.section_title').click(function(){
				var next_section = jQuery(this).next('.section');

				if ( jQuery(next_section).is(':visible') )
					return false;

				jQuery('.section:visible').slideUp();
				jQuery('.section_title').removeClass('open');
				jQuery(this).addClass('open').next('.section').slideDown();

				return false;
			});
			jQuery('.section').slideUp( 100, function() {
				<?php if ( empty( $this->product_ids ) ) : ?>
					jQuery('.section_title:eq(0)').click();
				<?php endif; ?>
			});
		</script>
		<?php
	}

	/**
	 * Output an export link.
	 */
	public function get_export_button() {

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo esc_html( date_i18n( 'Y-m-d', current_time( 'timestamp' ) ) ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
			data-groupby="<?php echo $this->chart_groupby; ?>"<?php // @codingStandardsIgnoreLine ?>
		>
			<?php esc_html_e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Get the main chart.
	 */
	public function get_main_chart() {
		global $wp_locale, $WooGC;

		if ( empty( $this->product_ids ) ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php esc_html_e( 'Choose a product to view stats', 'woocommerce' ); ?></p>
			</div>
			<?php
		} else {
			// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date.
			$order_item_counts = $this->get_order_report_data(
				array(
					'data'         => array(
						'_qty'        => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_count',
						),
						'date_created_gmt'   => array(
							'type'     => 'post_data',
							'function' => '',
							'name'     => 'date_created_gmt',
						),
						'_product_id' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => '',
							'name'            => 'product_id',
						),
					),
					'where_meta'   => array(
						'relation' => 'OR',
						array(
							'type'       => 'order_item_meta',
							'meta_key'   => array( '_product_id', '_variation_id' ),
							'meta_value' => $this->product_ids,
							'operator'   => 'IN',
						),
					),
					'group_by'     => 'product_id,' . $this->group_by_query,
					'order_by'     => 'date_created_gmt ASC',
					'query_type'   => 'get_results',
					'filter_range' => true,
                    
                    'network_sites' =>  $this->network_sites
				)
			);

			$order_item_amounts = $this->get_order_report_data(
				array(
					'data'         => array(
						'_line_total' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_amount',
						),
						'date_created_gmt'   => array(
							'type'     => 'post_data',
							'function' => '',
							'name'     => 'date_created_gmt',
						),
						'_product_id' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => '',
							'name'            => 'product_id',
						),
					),
					'where_meta'   => array(
						'relation' => 'OR',
						array(
							'type'       => 'order_item_meta',
							'meta_key'   => array( '_product_id', '_variation_id' ),
							'meta_value' => $this->product_ids,
							'operator'   => 'IN',
						),
					),
					'group_by'     => 'product_id, ' . $this->group_by_query,
					'order_by'     => 'date_created_gmt ASC',
					'query_type'   => 'get_results',
					'filter_range' => true,
                    
                    'network_sites' =>  $this->network_sites
				)
			);

			// Prepare data for report.
			$order_item_counts  = $this->prepare_chart_data( $order_item_counts, 'date_created_gmt', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
			$order_item_amounts = $this->prepare_chart_data( $order_item_amounts, 'date_created_gmt', 'order_item_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );

			// Encode in json format.
			$chart_data = json_encode(
				array(
					'order_item_counts'  => array_values( $order_item_counts ),
					'order_item_amounts' => array_values( $order_item_amounts ),
				)
			);
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<?php // @codingStandardsIgnoreStart ?>
			<script type="text/javascript">
				var main_chart;

				jQuery(function(){
					var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

					var drawGraph = function( highlight ) {

						var series = [
							{
								label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
								data: order_data.order_item_counts,
								color: '<?php echo $this->chart_colours['item_count']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								hoverable: false
							},
							{
								label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
								data: order_data.order_item_amounts,
								yaxis: 2,
								color: '<?php echo $this->chart_colours['sales_amount']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								<?php echo $this->get_currency_tooltip(); ?>
							}
						];

						if ( highlight !== 'undefined' && series[ highlight ] ) {
							highlight_series = series[ highlight ];

							highlight_series.color = '#9c5d90';

							if ( highlight_series.bars )
								highlight_series.bars.fillColor = '#9c5d90';

							if ( highlight_series.lines ) {
								highlight_series.lines.lineWidth = 5;
							}
						}

						main_chart = jQuery.plot(
							jQuery('.chart-placeholder.main'),
							series,
							{
								legend: {
									show: false
								},
								grid: {
									color: '#aaa',
									borderColor: 'transparent',
									borderWidth: 0,
									hoverable: true
								},
								xaxes: [ {
									color: '#aaa',
									position: "bottom",
									tickColor: 'transparent',
									mode: "time",
									timeformat: "<?php echo ( 'day' === $this->chart_groupby ) ? '%d %b' : '%b'; ?>",
									monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
									tickLength: 1,
									minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
									font: {
										color: "#aaa"
									}
								} ],
								yaxes: [
									{
										min: 0,
										minTickSize: 1,
										tickDecimals: 0,
										color: '#ecf0f1',
										font: { color: "#aaa" }
									},
									{
										position: "right",
										min: 0,
										tickDecimals: 2,
										alignTicksWithAxis: 1,
										color: 'transparent',
										font: { color: "#aaa" }
									}
								],
							}
						);

						jQuery('.chart-placeholder').resize();
					}

					drawGraph();

					jQuery('.highlight_series').hover(
						function() {
							drawGraph( jQuery(this).data('series') );
						},
						function() {
							drawGraph();
						}
					);
				});
			</script>
			<?php
			// @codingStandardsIgnoreEnd
		}
	}
}
