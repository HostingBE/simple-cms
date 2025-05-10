$(document).ready(function(e) {
        'use strict';


$(document).on("click", ".btn.btn-default.copy", function(event) {
    event.preventDefault();

    $('input[name="partner-link"]').select();
    document.execCommand('copy');
    $(".alert.alert-success").toggleClass('d-none d-block');
    $(".alert.alert-success").html('link copied to your clipboard use ctrl-v to paste!');   

    });
});