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
    // Remove all '.optional' elements inside the fields
    $('#billing_birth_registration_field .optional, #billing_nid_field .optional, #billing_passport_field .optional').remove();

    // Function to handle field visibility based on selected value
    function handleFieldVisibility(selectedValue) {
        // Hide all fields initially
        $('#billing_passport_field, #billing_nid_field, #billing_birth_registration_field').hide();

        // Show the appropriate field
        if (selectedValue === 'nid') {
            $('#billing_nid_field').show();
        } else if (selectedValue === 'passport') {
            $('#billing_passport_field').show();
        } else if (selectedValue === 'birth_reg') {
            $('#billing_birth_registration_field').show();
        }
    }

    // Get initial value, apply visibility rules
    var initialValue = $('#billing_doc').val();
    console.log('Initial Value:', initialValue);
    handleFieldVisibility(initialValue);

    // On change event for #billing_doc
    $('#billing_doc').on('change', function() {
        var selectedValue = $(this).val();
        console.log('Selected Value:', selectedValue);
        handleFieldVisibility(selectedValue);
    });

    $(document).on('click', '#place_order', function(e) {
	    e.preventDefault(); 
	    
	});

});


