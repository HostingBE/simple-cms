$(document).ready(function(e) {
        'use strict';


$(document).on("click", ".fa-solid.fa-copy.fa-lg", function(event) {
    event.preventDefault();

    $('input[name="partner-link"]').select();
    document.execCommand('copy');
    $(".alert.alert-success").toggleClass('d-none d-block');
    $(".alert.alert-success").html('link copied to your clipboard use ctrl-v to paste!');   

    });
});