<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class Wb_Sticky_Notes_Feedback {
	private $reasons = array();
	public function __construct() {

		$this->reasons = array(
			'not-working' => __('Not working', 'wb-sticky-notes'),
			'found-better' => __('Found better', 'wb-sticky-notes'),
			'not-meet-my-requirements' => __('It doesn\'t meet my requirements', 'wb-sticky-notes'),
            'too-complex' => __('Plugin too complex', 'wb-sticky-notes'),
            'conflicts-with-another' => __('Conflicts with another plugin', 'wb-sticky-notes'),
            'temporary' => __('Temporary deactivation', 'wb-sticky-notes'),
			'other' => __('Other', 'wb-sticky-notes'),
		);

        add_action( 'admin_footer', array($this, 'add_content') );
        add_action( 'wp_ajax_wb_stn_submit_feedback', array($this, "submit_feedback") );
    }

    public function add_content() {
    	global $pagenow;
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }

        ?>
        <style type="text/css">
        	.wb_stn_feedback_popupbg{ position:fixed; z-index:100000000; width:100%; height:100%; background-color:rgba(0, 0, 0,.8); left:0px; top:0px; display:none;}
        	.wb_stn_feedback_popup{ position:fixed; z-index:100000001; background:#fff; width:100%; max-width:600px; height:auto; left:50%; top:40%; transform:translate(-50%, -50%); box-sizing:border-box; box-shadow:0px 0px 2px #ccc; display:none;}
        	.wb_stn_feedback_popup_head{ width:100%; box-sizing:border-box; padding:0px 15px; min-height:40px; background:#f0f6fc; font-size:14px; font-weight:bold; line-height:40px; }
        	.wb_stn_feedback_popup_close{ float:right; min-width:40px; min-height:40px; margin-right:-15px; text-align:center; cursor:pointer; color:#d63638; }
        	.wb_stn_feedback_popup_content{ width:100%; box-sizing:border-box; padding:15px; height:auto; font-size:14px; }
        	.wb_stn_feedback_popup_content label{ width:100%; display:block; font-weight:bold; margin-top:20px; margin-bottom:5px;}
        	.wb_stn_feedback_popup_content textarea{ width:100%; display:block; }
            .wb_stn_feedback_notice { background:#fcf9e8; border:1px solid #c3c4c7; border-left-width:4px; border-left-color:#dba617; box-shadow:0 1px 1px rgba(0, 0, 0, 0.04); padding: 1px 12px; }
        </style>
        <script type="text/javascript">
        	jQuery(document).ready(function(){
        		jQuery(document).on('click', 'a#deactivate-wb-sticky-notes', function(e){
        			e.preventDefault();

        			if(!jQuery('.wb_stn_feedback_popupbg').length)
        			{
        				jQuery('body').prepend('<div class="wb_stn_feedback_popupbg"></div>');
        			}

        			jQuery('.wb_stn_feedback_popup, .wb_stn_feedback_popupbg').show();

        			jQuery('.wb-stn-skip-and-deactivate').attr({'href': jQuery(this).attr('href')});
        		});

        		jQuery(document).on('click', '.wb_stn_feedback_popup_close, .wb-stn-cancel-uninstall', function(e){
        			jQuery('.wb_stn_feedback_popup, .wb_stn_feedback_popupbg').hide();
        		});

        		jQuery(document).on('click', 'button.wb-stn-uninstall-submit', function(e){
        			e.preventDefault();
        			jQuery('.wb-stn-skip-and-deactivate, .wb-stn-uninstall-submit, .wb-stn-cancel-uninstall').prop('disabled', true).addClass('disabled');
        			jQuery('.wb-stn-uninstall-submit').html('<?php esc_html_e('Submitting...', 'wb-sticky-notes'); ?>');

        			jQuery.ajax({
                        url: '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',
                        type: 'POST',
                        data: {
                            action: 'wb_stn_submit_feedback',
                            reason: jQuery('[name="wb-stn-uninstall-reason"]').val(),
                            reason_brief: jQuery('[name="wb-stn-uninstall-reason-brief"]').val(),
                        },
                        complete:function() {
                            window.location.href = jQuery('.wb-stn-skip-and-deactivate').attr('href');
                        }
                    });

        		});
        	});
        </script>
        <div class="wb_stn_feedback_popup">
        	<div class="wb_stn_feedback_popup_head">
        		<?php esc_html_e('If you can take a moment, kindly share with us the reason for your deactivation', 'wb-sticky-notes'); ?>
        		<div class="wb_stn_feedback_popup_close" title="<?php esc_attr_e('Close', 'wb-sticky-notes'); ?>">X</div>	
        	</div>
        	<div class="wb_stn_feedback_popup_content">
        		<div>
                    <div class="wb_stn_feedback_notice"><p><?php 
                    // translators: %1$s: HTML a tag opening for FAQ page., %2$s: HTML a tag closing for FAQ page. 
                    echo wp_kses_post( sprintf( __('Before uninstalling, check our %1$sFAQ%2$s — your feature may already be included!', 'wb-sticky-notes'), '<a href="'.esc_url(admin_url('tools.php?page=wb-sticky-notes&wb_stn_tab=help') ).'" target="_blank">', '</a>' ) ); 
                    ?> 
                    </p></div>
                </div>
                <div>
        			<label><?php esc_html_e('Please choose a reason.', 'wb-sticky-notes'); ?></label>
        			<select name="wb-stn-uninstall-reason">
        				<option value=""><?php esc_html_e('Choose a reason.', 'wb-sticky-notes'); ?></option>
        				<?php
        				foreach( $this->reasons as $key => $value ) {
        					?>
        					<option value="<?php echo esc_html($key); ?>"><?php echo esc_html($value); ?></option>
        					<?php
        				}
        				?>	
        			</select>
        		</div>
        		<div>
        			<label><?php esc_html_e('Can you provide us with additional information?', 'wb-sticky-notes'); ?></label>
        			<textarea name="wb-stn-uninstall-reason-brief"></textarea>
        		</div>
        		<div>
        			<p><?php esc_html_e('No personal data is gathered when you submit this form.', 'wb-sticky-notes'); ?>
                    <?php esc_html_e( 'If you would like our support team to contact you, please include your email address in the additional information field along with your message.', 'wb-sticky-notes' ); ?>         
                    </p>
        		</div>
        		<div style="width:100%; margin-top:0px; padding:15px 0px; box-sizing:border-box; float:left;">
        			<a href="https://wordpress.org/support/plugin/wb-sticky-notes/" target="_blank"><?php esc_html_e( 'Help and Support.', 'wb-sticky-notes' ); ?></a>

                    <button class="button button-primary wb-stn-uninstall-submit" style="float:right;"><?php esc_html_e('Submit and deactivate', 'wb-sticky-notes'); ?></button>
        			<a class="button button-secondary wb-stn-skip-and-deactivate" style="float:right; margin-right:10px;"><?php esc_html_e('Skip and deactivate', 'wb-sticky-notes'); ?></a>
        			<a class="button button-secondary wb-stn-cancel-uninstall" style="float:right; margin-right:10px;"><?php esc_html_e('Cancel', 'wb-sticky-notes'); ?></a>
        		</div>
        	</div>
        </div>
        <?php
    }

    public function submit_feedback() {
    	global $wpdb;

        if (!isset($_POST['reason']) && 
        	(isset($_POST['reason']) && "" === trim($_POST['reason'])) 
        ) {
            return;
        }

        $data = array(
            'plugin' 			=> "wb_stn",
            'version' 			=> WB_STICKY_NOTES_VERSION,
            'date' 				=> gmdate("M d, Y h:i:s A"),
            'reason' 			=> sanitize_text_field($_POST['reason']),
            'reason_brief' 		=> isset($_REQUEST['reason_brief']) ? trim(stripslashes($_REQUEST['reason_brief'])) : '',
            'software' 			=> $_SERVER['SERVER_SOFTWARE'],
            'php_version' 		=> phpversion(),
            'mysql_version' 	=> $wpdb->db_version(),
            'wp_version' 		=> get_bloginfo('version'),
            'wc_version' 		=> (defined('WC_VERSION') ? WC_VERSION : ''),
            'locale' 			=> get_locale(),
            'multisite' 		=> is_multisite() ? 'Yes' : 'No',
        );
        

        $resp = wp_remote_post('https://feedback.webbuilder143.com/wp-json/feedback/v1', array(
            'method' 		=> 'POST',
            'timeout' 		=> 45,
            'redirection' 	=> 5,
            'httpversion' 	=> '1.0',
            'blocking' 		=> false,
            'body' 			=> $data,
            'cookies' 		=> array(),
            )
        );

        return;
    }
}

new Wb_Sticky_Notes_Feedback();