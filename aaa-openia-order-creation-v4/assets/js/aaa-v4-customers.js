/**
 * File: customers.js
 * Serves as a “shared” module for anything that updates the customer‐status display.
 */

jQuery(function($){

  /**
   * updateCustomerStatus(data)
   *
   *   data = {
   *     matched_by:   'email'  or 'phone',
   *     profile_url:  'https://…/wp-admin/user-edit.php?user_id=…'
   *   }
   *
   * Renders the “Status: Existing Customer (Matched by …) | View Profile” line.
   * Exposed as a global so that relookup.js or other modules can call it.
   */
  function updateCustomerStatus(data) {
    let status_html = '<p><strong>Status:</strong> ' +
      '<span style="color:green;">Existing Customer (Matched by ' +
      ( data.matched_by || '' ) +
      ')</span>';

    if ( data.profile_url ) {
      status_html += ' | <a href="' + data.profile_url + '" target="_blank">View Profile</a>';
    }
    status_html += '</p>';

    $('#customer-status-display').html(status_html);
  }

  // Expose to the global namespace:
  window.AAA_V4_updateCustomerStatus = updateCustomerStatus;
});
