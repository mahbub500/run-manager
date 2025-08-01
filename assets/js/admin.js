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

    $('#run-manager-import-button').prop('disabled', true);
   
    $('#excel_file').on('change', function () {
        if ($(this).val()) {
            $('#run-manager-import-button').prop('disabled', false);
        } else {
            $('#run-manager-import-button').prop('disabled', true);
        }
    });


    $("#run-manager-import-button").on('click', function (e) {
        e.preventDefault();

        var xl_file = $('#excel_file')[0].files[0];

        if (!xl_file) {
            $('#status').text('Please select a file before importing.');
            return;
        }

        var formData = new FormData();
        formData.append('excel_file', xl_file); 
        formData.append('nonce', RUN_MANAGER._wpnonce); 
        formData.append('action', 'import_woocommerce_orders');

        runm_modal();

        $.ajax({
            url: RUN_MANAGER.ajaxurl, 
            type: "POST",
            data: formData,
            processData: false, 
            contentType: false, 
            success: function (response) {
                if (response.success) {
                    // $('#status').text(response.message);
                    $('#excel_file').val('');
                    // toastr.success(text(response.message));
                } else {
                    $('#status').text(response.error || 'An error occurred during import.');
                }
                runm_modal(false); 
            },
            error: function () {
                $('#status').text('Something went wrong!');
            }
        });
    });

   // toastr.success('Have fun storming the castle!', 'Miracle Max Says');
    $('#run-manager-upload-race-data').click(function (e) {
        e.preventDefault();
        var fileInput = $('#race_excel_file')[0].files[0];
        if (!fileInput) {
            alert("Please select a file.");
            return;
        }

        var formData = new FormData();
        formData.append('nonce', RUN_MANAGER._wpnonce); 
        formData.append('action', 'upload_race_data'); 
        formData.append('race_excel_file', fileInput);

        runm_modal();

        $.ajax({
             url: RUN_MANAGER.ajaxurl, 
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    runm_modal(false);
                    alert(response.data.message);
                    $('#race_excel_file').val(''); 

                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('File upload failed.');
            }
        });
    });

   $('#generate-munual-certificate').click(function (e) {
    e.preventDefault();        

    let sheet_number = $('.certificate-number').val().trim();

    if (sheet_number === "") {
        alert("Please enter a sheet number.");
        return;
    }

    var formData = new FormData();
    formData.append('nonce', RUN_MANAGER._wpnonce); 
    formData.append('action', 'generate-munual-certificate'); 
    formData.append('sheet_number', sheet_number); 

    runm_modal();

    $.ajax({
        url: RUN_MANAGER.ajaxurl, 
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            runm_modal(false);

            if (response.success && Array.isArray(response.data.certificates)) {
                response.data.certificates.forEach(pdfUrl => {
                    const link = document.createElement('a');
                    link.href = pdfUrl;
                    link.download = pdfUrl.split('/').pop();
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            } else {
                alert(response.data?.message || 'Unexpected response structure');
            }
        },
        error: function () {
            alert('File upload failed.');
        }
    });
});



  
function toggleButtonState() {
    let inputVal = $(".certificate-number").val().trim();
    $("#generate-munual-certificate").prop("disabled", inputVal === "" || isNaN(inputVal));
}

toggleButtonState();

$(".certificate-number").on("input change", function(){
    toggleButtonState();
    console.log( 'Hello' );
} );

$('#run-manager-tshirt-chart').click(function (e) {
    e.preventDefault();
    runm_modal(true); // Show loader/modal

    $.ajax({
        url: RUN_MANAGER.ajaxurl, // This is the WordPress AJAX URL
        type: 'POST',
        data: {
            action: 'generate_tshirt_size', // Custom action for the server
            _wpnonce: RUN_MANAGER._wpnonce // Nonce for security
        },
        success: function (response) {
            runm_modal(false); // Hide loader/modal

            if (response.success) {
               

                if (response.data.url) {
                    const a = document.createElement("a");
                    a.href = response.data.url; // URL of the generated PDF
                    a.download = "tshirt_report.pdf"; // Set default file name
                    document.body.appendChild(a);
                    a.click(); // Trigger the download
                    document.body.removeChild(a); // Clean up
                }
            } else {
                alert(response.data.message || "Something went wrong.");
            }
        },
        error: function () {
            runm_modal(false); // Hide loader/modal in case of error
            alert('File generation failed.'); // Error message
        }
    });
});




   

});

