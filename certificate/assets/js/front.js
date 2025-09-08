jQuery(function($){  
    $(document).on( 'click', '.coschool-dm-certificate-btn.download', function (e) {
        e.preventDefault();
        var course_id   = $(this).data('course_id');

        $.ajax({
            url: COSCHOOL.ajaxurl,
            data: { action: 'get-certificate', course_id: course_id, _wpnonce: COSCHOOL.nonce },
            type: 'POST',
            dataType: 'json',
            success: function(resp) {
                var a = $("<a />");
                a.attr("download", resp.name);
                a.attr("href", resp.pdf);
                $("body").append(a);
                a[0].click();
                $("body").remove(a);
                console.log(resp.pdf);
            },
            error: function(err) {
                console.log(err);
            }
        });
    });
});
