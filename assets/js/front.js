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

	    const ariaLabel 	= $(this).attr('aria-label');

	    const orderNumber 	= ariaLabel.match(/order number (\d+)/)?.[1];

	    
	});


})