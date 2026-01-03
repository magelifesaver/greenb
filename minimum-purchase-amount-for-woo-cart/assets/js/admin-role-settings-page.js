jQuery( document ).ready(function() {
	jQuery('#ct-mpac-role-based-order-limits-table').DataTable({"paging": false,});
	
	jQuery('#ct-mpac-save-role-limits').click(function(e) {
		jQuery('input[type="search"]').val('').trigger('keyup');
		let roleBasedData = ct_mpac_get_data_entered_for_roles();
		let settingNonce  = jQuery('#ct-mpac-nonce').attr('data-nonce'); 

		jQuery('#ct-mpac-save-role-limits').text('Saving Role Based Settings').css('cursor','loading').prop('disabled', true);
		data = {
			action:'save_role_based_settings',
			roleBasedLimits:roleBasedData,
			nonce:settingNonce,
		}
		jQuery.post(ct_mpac_role_table.ajax_url, data, function(response) {
			if("saved"==response.status) {
				alert(ct_mpac_role_table.success_message);
				location.reload();
			} else {
				alert(ct_mpac_role_table.failure_message);
			}
		});
	});


});

function ct_mpac_get_data_entered_for_roles() {
	let roleDataRow = jQuery('#ct-mpac-role-based-order-limits-table').find('tr.role-limit-row');
	let dataToSave  = [];
	roleDataRow.each(function (i, row) {
		let $row 	  = jQuery(row);
		let role 	  = $row.attr('name');
		let minAmount = $row.find('input.min-amount').val();
		let status    = $row.find('input.status:checked').length;
		let data 	  = {
						'role':role,
						'minAmount':(minAmount>=0)?minAmount:0,
						'status':status,
						}
		dataToSave.push(data);
	});
	return dataToSave;
}