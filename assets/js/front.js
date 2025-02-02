let runm_modal = ( show = true ) => {
	if(show) {
		jQuery('#run-manager-modal').show();
	}
	else {
		jQuery('#run-manager-modal').hide();
	}
}

jQuery(function($){
	$(document).on('click', '.download_certificate', function (e) {
	    e.preventDefault();

	    const ariaLabel = $(this).attr('aria-label');
	    const orderNumber = ariaLabel.match(/order number (\d+)/)?.[1];

	    if (!orderNumber) {
	        alert('Order number is invalid.');
	        return;
	    }

	    $.ajax({
	        url: RUN_MANAGER.ajaxurl,
	        type: "POST",
	        data: {
	            nonce: RUN_MANAGER._wpnonce,
	            action: 'create_certificate',
	            order_number: orderNumber,
	        },
	        success: function (response) {
	            if (response.success) {
	                // Redirect to the download link
	                window.location.href = response.data.download_link;
	            } else {
	                alert(response.data.message || 'An error occurred.');
	            }
	        },
	        error: function () {
	            alert('Something went wrong!');
	        }
	    });
	});

})

jQuery(document).ready(function($) {
    // Initially show #billing_birth_registration
    $('#billing_birth_registration_field').show();

    function handleFieldVisibility(selectedValue) {
        // Hide all fields except #billing_birth_registration_field
        $('#billing_passport_field, #billing_nid_field').hide();

        if (selectedValue === 'nid') {
            $('#billing_nid_field').show();
            $('#billing_birth_registration_field').hide();
        } else if (selectedValue === 'passport') {
            $('#billing_passport_field').show();
            $('#billing_birth_registration_field').hide();
        } else if (selectedValue === 'birth_reg') {
            $('#billing_birth_registration_field').show(); // Ensure it's visible when 'birth_reg' is selected
        }
    }

    // Get initial value and apply conditions
    var initialValue = $('#billing_doc').val();
    console.log('Initial Value:', initialValue);
    handleFieldVisibility(initialValue); // Apply visibility rules on page load

    // On change event
    $('#billing_doc').on('change', function() {
        var selectedValue = $(this).val();
        console.log('Selected Value:', selectedValue);
        handleFieldVisibility(selectedValue);
    });
});

