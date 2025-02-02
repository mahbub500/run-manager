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

    $('#billing_doc').on('change', function() {
        var selectedValue = $(this).val();
        
    });





})