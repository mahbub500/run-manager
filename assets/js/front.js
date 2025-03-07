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

    // Function to handle field visibility and required attribute
    function handleFieldVisibility(selectedValue) {
        // Hide all fields and remove required attribute
        $('#billing_passport_field, #billing_nid_field, #billing_birth_registration_field')
            .hide()
            .find('input')
            .val('')
            .removeAttr('required');

        // Show the appropriate field and add required attribute
        if (selectedValue === 'nid') {
            $('#billing_nid_field').show().find('input').attr('required', 'required');
        } else if (selectedValue === 'passport') {
            $('#billing_passport_field').show().find('input').attr('required', 'required');
        } else if (selectedValue === 'birth_reg') {
            $('#billing_birth_registration_field').show().find('input').attr('required', 'required');
        }
    }

    // Get initial value, apply visibility rules
    var initialValue = $('#billing_doc').val();
    handleFieldVisibility(initialValue);

    // On change event for #billing_doc
    $('#billing_doc').on('change', function() {
        var selectedValue = $(this).val();
        handleFieldVisibility(selectedValue);
    });

    $(document).on('click', '#place_order', function(e) {
        var selectedValue = $('#billing_doc').val();
        var selectedField = null;
        var fieldLabel = '';

        if (selectedValue === 'nid') {
            selectedField = $('#billing_nid_field input');
            fieldLabel = 'NID Number';
        } else if (selectedValue === 'passport') {
            selectedField = $('#billing_passport_field input');
            fieldLabel = 'Passport Number';
        } else if (selectedValue === 'birth_reg') {
            selectedField = $('#billing_birth_registration_field input');
            fieldLabel = 'Birth Registration Number';
        }

        // Check if the selected field is empty
        if (selectedField && selectedField.val().trim() === '') {
            e.preventDefault();
            alert('Please fill in the ' + fieldLabel + ' before placing the order.');
        }
    });
});

jQuery(document).ready(function($){
    $("#verify_bib_form").on("submit", function(e){
        e.preventDefault();
        
        var bib_id = $("#bib_id").val();
        var verification_code = $("#verification_code").val();

        $.ajax({
            url: RUN_MANAGER.ajaxurl,
            type: "POST",
            data: {
                nonce: RUN_MANAGER._wpnonce,
                action: 'verify_bib_action',
                bib_id: bib_id,
                verification_code: verification_code
            },
            success: function (response) {
                if(response.success) {
                    $("#verification_message").html(response.data.message);
                } else {
                    $("#verification_message").html(response.data.message);
                }
            },
            error: function () {
                alert('Something went wrong!');
            }
        });
    });
});







