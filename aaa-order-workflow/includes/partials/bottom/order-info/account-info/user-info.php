<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="aaa-oc-my-account-info" style="margin-top: 15px; border-left: 1px solid; padding:0.5rem; line-height: normal;">
	<div style="font-weight:bold; margin-bottom:0.5rem;">Account Info</div>
		    <?php
		    // Retrieve the customer user ID stored in _customer_user
		    $customer_user_id = $row->_customer_user;
		    if ( ! empty($customer_user_id) ) {
		        $user_info = get_userdata( $customer_user_id );
		        if ( $user_info ) {
		            // Link to the user's profile in the admin area
		            echo '<div><a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $customer_user_id ) ) . '">'
		                 . esc_html( $user_info->display_name ) . '</a></div>';
		        }
		    }

		    // Birthday
		    if ( ! empty($row->lkd_birthday) ) {
		        $bday_ts = strtotime($row->lkd_birthday);
		        if ( $bday_ts ) {
		            $bday_md   = date('m-d', $bday_ts);
		            $today_md  = date('m-d', current_time('timestamp'));
		            if ( $bday_md === $today_md ) {
		                echo '<div style="border:2px solid red; padding:4px;">&#x1F382; ' . date('m/d/Y', $bday_ts) . '</div>';
		            } else {
		                echo '<div>&#x1F382; ' . date('m/d/Y', $bday_ts) . '</div>';
		            }
		        }
		    }

		    // ID Exp
		    if (! empty($row->lkd_dl_exp)) {
		        echo '<div>ID Exp: ' . esc_html($row->lkd_dl_exp) . '</div>';
		    }

		    // Email
		    if (! empty($customer_email)) {
		        echo '<div>Email: ' . esc_html($customer_email) . '</div>';
		    }

		    // Phone
		    if (! empty($customer_phone)) {
		        echo '<div>Phone: ' . esc_html($customer_phone) . '</div>';
		    }

		    // Billing Address
		    $billing_verified = !empty($row->billing_verified) && (int)$row->billing_verified === 1;
		    if ( ! empty($billing_data) && ! empty($billing_data['address_1']) ) {
		        echo '<div style="margin-top:10px; font-weight:bold;">Billing Address '
		           . ( $billing_verified
		               ? '<span title="Verified" style="color:#2e7d32; font-weight:bold;">✔</span>'
		               : '<span title="Not verified" style="color:#c62828; font-weight:bold;">✖</span>'
		             )
		           . '</div>';
		        echo '<div>'
		           . esc_html($billing_data['address_1'] . ' ' . $billing_data['address_2']) . '<br>'
		           . esc_html($billing_data['city'] . ', ' . $billing_data['state'] . ' ' . $billing_data['postcode']) . '<br>'
		           . esc_html($billing_data['country'])
		           . '</div>';
		    }
		    ?>
	</div>
	</div>
