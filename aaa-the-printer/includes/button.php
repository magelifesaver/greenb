<?php
/**
 * Button logic for AAA The Printer
 * 
 * This file sets up the meta box and the callback that 
 * outputs the "Preview HTML", "Preview PDF", and multiple "Print" buttons.
 */

//-------------------------------------------
// Add a meta box with “Preview” & “Print”
//-------------------------------------------
function aaa_lpm_add_meta_box() {
    add_meta_box(
        'aaa-lpm-buttons',         // Unique ID
        'AAA Printer',             // Box title
        'aaa_lpm_render_meta_box', // Content callback
        'shop_order',              // Post type
        'side',                    // Context (side meta box)
        'default'                  // Priority
    );
}
add_action( 'add_meta_boxes', 'aaa_lpm_add_meta_box' );

function aaa_lpm_render_meta_box( $post ) {
    // Create a nonce for secure AJAX calls.
    $nonce = wp_create_nonce( 'aaa_lpm_nonce' );
    ?>
    <div class="aaa-lpm-meta-box">
        <!-- Receipt Template Buttons -->
        <h4>Receipt Template</h4>
        <button type="button" class="button aaa-lpm-preview-html-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="receipt" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>">
            HTML Preview
        </button>
        <button type="button" class="button aaa-lpm-preview-pdf-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="receipt" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>">
            PDF Preview
        </button>
        <button type="button" class="button aaa-lpm-print-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="receipt" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>" 
            data-printer="dispatch">
            Print (Dispatch)
        </button>
        <button type="button" class="button aaa-lpm-print-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="receipt" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>" 
            data-printer="inventory">
            Print (Inventory)
        </button>

        <!-- Picklist Template Buttons -->
        <h4 style="margin-top:20px;">Picklist Template</h4>
        <button type="button" class="button aaa-lpm-preview-html-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="picklist" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>">
            HTML Preview
        </button>
        <button type="button" class="button aaa-lpm-preview-pdf-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="picklist" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>">
            PDF Preview
        </button>
        <button type="button" class="button aaa-lpm-print-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="picklist" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>" 
            data-printer="dispatch">
            Print (Dispatch)
        </button>
        <button type="button" class="button aaa-lpm-print-btn" 
            data-order-id="<?php echo esc_attr( $post->ID ); ?>" 
            data-template="picklist" 
            data-nonce="<?php echo esc_attr( $nonce ); ?>" 
            data-printer="inventory">
            Print (Inventory)
        </button>
    </div>
    <?php
}
