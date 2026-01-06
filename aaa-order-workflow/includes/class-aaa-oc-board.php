<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/class-aaa-oc-board.php
 * Purpose: Registers the top-level "Workflow" admin menu and the main Workflow Board page.
 * Version: 1.4.0
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AAA_OC_Board' ) ) {

    class AAA_OC_Board {

        public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_board_submenu' ], 10 );
        }

public function add_board_submenu() {
	add_menu_page(
		__( 'Workflow', 'aaa-order-workflow' ),
		__( 'Workflow', 'aaa-order-workflow' ),
		'manage_woocommerce',
		'aaa-oc-workflow-board',
		[ $this, 'render_workflow_board' ],
		'dashicons-networking',
		49
	);

	add_submenu_page(
		'aaa-oc-workflow-board',
		__( 'Workflow Board', 'aaa-order-workflow' ),
		__( 'Workflow Board', 'aaa-order-workflow' ),
		'manage_woocommerce',
		'aaa-oc-workflow-board',
		[ $this, 'render_workflow_board' ]
	);
}

        /**
         * Renders an empty container for the board columns and the .
         * The actual orders are loaded by board.js calling get_latest_orders() on page load.
         */
        public function render_workflow_board() {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Workflow Board', 'aaa-order-workflow' ); ?></h1>
                
                <!-- Board Columns Wrapper -->
                <div id="aaa-oc-board-columns" style="display:flex; gap:1rem; overflow-x:auto; margin-top:1rem;">
                    <!-- Orders inserted via AJAX -->
                </div>
            </div>

            <!-- Modal for expanded order popup -->
            <div id="aaa-oc-modal"
                 style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%;
                        background:rgba(0,0,0,0.7);"
                 data-overlay="true">
                <div id="aaa-oc-modal-content"
                     style="background:#fff; width:1000px; max-width:95%; margin:50px auto; padding:10px; position:relative;border-radius:10px;">
        <button id="aaa-payrec-modal-close" style="position:absolute; top:10px; right:10px;">X</button>
                    <!-- Card details injected dynamically -->
                </div>
            </div>
	    
            <?php
        }
    }
}
