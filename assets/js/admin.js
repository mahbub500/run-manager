let runm_modal = ( show = true ) => {
	if(show) {
		jQuery('#run-manager-modal').show();
	}
	else {
		jQuery('#run-manager-modal').hide();
	}
}

jQuery(document).ready(function ($) {
    $("#run-manager-export-button").click(function (e) {
      e.preventDefault();

      $.ajax({
        url: RUN_MANAGER.ajaxurl, 
        type: "POST",
        data: {
        	nonce : RUN_MANAGER._wpnonce,
        	action: "export_woocommerce_orders", 
        },
        success: function (response) {
          console.log( 'test' );
        	runm_modal();
          if (response.success) {
            // Trigger file download
            const blob = new Blob([response.data], { type: "application/vnd.ms-excel" });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = "woocommerce-orders.xlsx"; 
            link.click();

            // Cleanup
            URL.revokeObjectURL(link.href);
          } else {
            alert("Failed to export orders: " + response.data.message);
          }

          runm_modal(false);
        },
        error: function () {
          alert("An error occurred while exporting orders.");
        },
      });
    });
  });
