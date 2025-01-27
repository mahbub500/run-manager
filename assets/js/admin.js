let runm_modal = ( show = true ) => {
	if(show) {
		jQuery('#run-manager-modal').show();
	}
	else {
		jQuery('#run-manager-modal').hide();
	}
}

jQuery(document).ready(function ($) {
    $('#wph-form-run-manager_basic').attr('enctype', 'multipart/form-data');
    $("#run-manager-export-button").click(function (e) {
        e.preventDefault();

        runm_modal();

        $.ajax({
            url: RUN_MANAGER.ajaxurl,
            type: "POST",
            data: {
                nonce: RUN_MANAGER._wpnonce,
                action: "export_woocommerce_orders",
            },
            xhrFields: {
                responseType: 'blob', // Handle file as binary
            },
            success: function (response) {
                
                var blob = new Blob([response], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'orders_export.xlsx';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                runm_modal(false);
            },
            error: function () {
                alert("An error occurred while exporting orders.");
            },
        });
    });


    $("#run-manager-import-button").on( 'click', function(e){
        e.preventDefault();
        console.log( 'Hello' );
    } )
});

