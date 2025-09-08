jQuery(function($){
	$(document).on('click','#coschool-certificate-download', function(e){
		e.preventDefault();

		var fileName = 'pdf2.pdf';

		$.ajax({
			url: COSCHOOL_CERTIFICATE.ajaxurl,
			data: { action: 'download-certificate', _wpnonce: COSCHOOL_CERTIFICATE._wpnonce, certificate_id: $(this).data( 'id' ) },
			type: 'POST',
			cache: false,
			success: function(resp) {
				console.log(resp.pdf);
                var a = $("<a />");
                a.attr("download", resp.name);
                a.attr("href", resp.pdf);
                $("body").append(a);
                a[0].click();
                $("body").remove(a);
			},
			error: function(err) {
				console.log(err);
			}
		});
	});
})