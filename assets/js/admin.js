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
                    type: 'text/csv;charset=utf-8;'
                });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'orders_export.csv'; // <-- CSV instead of XLSX
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

        // Get selected event value
        let product_id = $('#rm-select-product').val();

        if (!product_id) {
            runm_modal(false);
            alert('⚠️ Please select an event before generating the report.');
            return;
        }

        $.ajax({
            url: RUN_MANAGER.ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'generate_tshirt_size', // Custom action for server
                _wpnonce: RUN_MANAGER._wpnonce, // Nonce for security
                id: product_id        // Send selected event
            },
            success: function (response) {
                runm_modal(false); // Hide loader/modal

                if (response.success && response.data.url) {
                    const a = document.createElement("a");
                    a.href = response.data.url; // URL of generated PDF
                    a.download = "tshirt_report_" + product_id + ".pdf"; // Set filename
                    document.body.appendChild(a);
                    a.click(); // Trigger download
                    document.body.removeChild(a); // Clean up
                } else {
                    alert(response.data.message || "Something went wrong.");
                }
            },
            error: function () {
                runm_modal(false); // Hide loader/modal on error
                alert('File generation failed.');
            }
        });
    });


    const STORAGE_KEY = 'wph_last_active_tab';
const SMS_TAB = 'wph-tab-run-manager_basic-run-manager_sms_manager';
const SAVE_TAB = 'wph-tab-run-manager_basic-run-manager_save_message';

// Hide sections initially
$('.wrap.notify-wrap, #wph-tab-run-manager_basic-run-manager_save_message').hide();

function toggleSections(target) {
    // Hide all sections first
    $('.wrap.notify-wrap').hide();
    $('#wph-tab-run-manager_basic-run-manager_save_message').hide();

    if (target === SMS_TAB) {
        $('.wrap.notify-wrap').show();
    } else if (target === SAVE_TAB) {
        $('#wph-tab-run-manager_basic-run-manager_save_message').show();
    }
}

// Tab click
$('.wph-tabs .wph-tab').on('click', function() {
    var target = $(this).data('target');

    // Save to localStorage
    localStorage.setItem(STORAGE_KEY, target);

    // Handle section visibility
    toggleSections(target);

    // Active tab styling
    $(this).addClass('is-active').siblings().removeClass('is-active');
});

// Restore last active tab after reload
function restoreTab() {
    var savedTab = localStorage.getItem(STORAGE_KEY);
    if (!savedTab) return;

    var $tab = $('.wph-tabs .wph-tab[data-target="' + savedTab + '"]');
    if (!$tab.length) return;

    $tab.get(0).click(); // trigger tab click
    $tab.addClass('is-active').siblings().removeClass('is-active');
    toggleSections(savedTab);
}

// Delay restore to allow tabs to render
setTimeout(restoreTab, 1);


    
    
   function toggleEditors() {
        $('#email_editor_container').toggle($('#notify_email').is(':checked'));
        $('#sms_editor_container').toggle($('#notify_sms').is(':checked'));
        $('#test_mode_container').toggle($('#test_mode').is(':checked'));

        
    }

    toggleEditors();
    $('#notify_email, #notify_sms, #test_mode').on('change', toggleEditors);

    // AJAX Save
    $('#save_notify_data').on('click', function(){
        var email_content = tinyMCE.get('email_content') ? tinyMCE.get('email_content').getContent() : $('#email_content').val();
        var sms_content = tinyMCE.get('sms_content') ? tinyMCE.get('sms_content').getContent() : $('#sms_content').val();
        runm_modal();
        $.ajax({
            url: RUN_MANAGER.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_notify_data',
                _wpnonce: RUN_MANAGER._wpnonce,
                test_mode: $('#test_mode').is(':checked') ? 1 : 0,
                test_email: $('#test_email').val(),
                test_mobile: $('#test_mobile').val(),
                email_subject: $('#email_subject').val(),
                notify_email: $('#notify_email').is(':checked') ? 1 : 0,
                notify_sms: $('#notify_sms').is(':checked') ? 1 : 0,
                email_content: email_content,
                sms_content: sms_content
            },
            success: function(response){
                if(response.success){
                    $('#notify_save_msg').html('<span style="color:green;">'+response.data.message+'</span>');
                    toggleEditors();
                    setTimeout(function() {
                        $('#notify_save_msg').fadeOut('slow', function() {
                            $(this).html('').show(); // Clear content and reset display
                        });
                    }, 3000);
                    window.location.reload();
                } else {
                    alert(response.data.message || "Something went wrong.");
                }
                runm_modal(false);
            },
            error: function(){
                runm_modal(false);
                alert('Failed to save data.');
            }
        });
    });

    $(".notify-placeholders .placeholder").css({
        "cursor": "pointer",
        "color": "#0073aa"
    }).attr("title", "Click to copy");

    // On click, copy to clipboard
    $(".notify-placeholders .placeholder").on("click", function() {
        let text = $(this).text().trim();

        // Create temporary input to copy
        let tempInput = $("<input>");
        $("body").append(tempInput);
        tempInput.val(text).select();
        document.execCommand("copy");
        tempInput.remove();

        // Small feedback
        $(this).fadeOut(100).fadeIn(100);
    });

    $('#rm-select-product').select2();

    const $select = $('#rm-select-main-product');

    $select.select2({
        allowClear: true,
        width: '300px'
    });

    // Clear selection on page load
    $select.val(null).trigger('change');

    $('#rm-restriction-product').select2();$('#rm-product-restriction').on('click', function(e) {
        e.preventDefault();

        let selectedProducts = $('#rm-restriction-product').val(); // array of selected product IDs

        if (!selectedProducts || selectedProducts.length === 0) {
            alert('Please select at least one product.');
            return;
        }
        runm_modal();
        $.ajax({
            url: RUN_MANAGER.ajaxurl, // WordPress provides this in admin
            type: 'POST',
            data: {
                action: 'save_restriction_products',
                products: selectedProducts,
                _wpnonce: RUN_MANAGER._wpnonce // optional if you add nonce
            },
            success: function(response) {
                runm_modal(false);
                if (response.success) {
                    alert('Products saved successfully!');
                } else {
                    alert(response.data.message || 'Error saving products');
                }
            }
        });
    });

});

jQuery(document).ready(function($) {

    // Cache object to store all product data
    const productCache = {};

    // Flag to check if all products are loaded
    let allProductsLoaded = false;

    $('#rm-select-main-product').on('change', function() {
        const product_id = $(this).val();

        if (!product_id) {
            $('.rm-product-sales-count').html(''); // Clear table if no product
            return;
        }

        // If cache is already loaded, render directly
        if (allProductsLoaded) {
            renderTable(productCache[product_id]);
            return;
        }

        // Show loader/modal
        runm_modal();

        // AJAX call to get all product sales at once
        $.ajax({
            url: RUN_MANAGER.ajaxurl,
            type: "POST",
            dataType: "json",
            data: {
                action: "get_all_product_sales_count",
                _wpnonce: RUN_MANAGER._wpnonce
            },
            success: function(response) {
                // Hide loader/modal
                runm_modal(false);

                if (response.success && response.data.products) {
                    // Cache all product data
                    $.each(response.data.products, function(product_id_key, productData) {
                        productCache[product_id_key] = productData;
                    });

                    allProductsLoaded = true;

                    // Render table for the selected product
                    renderTable(productCache[product_id]);
                } else {
                    $('.rm-product-sales-count').html(
                        '<h3>Product Wise Product Count</h3>' +
                        '<p class="rm-no-products">No products found.</p>'
                    );
                }
            },
            error: function() {
                runm_modal(false);
                alert("Error fetching product sales count.");
            }
        });
    });

    // Helper function to render table
    function renderTable(products) {
        let html = '<h3>Product Wise Product Count</h3>';
        html += '<table class="widefat striped" style="width:100%; margin-top:10px;">';
        html += '<thead><tr><th>Product Name</th><th>Quantity Sold</th></tr></thead><tbody>';

        $.each(products, function(product, count) {
            html += '<tr><td>' + product + '</td><td>' + count + '</td></tr>';
        });

        html += '</tbody></table>';
        $('.rm-product-sales-count').html(html);
    }

});
