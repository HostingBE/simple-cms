$(document).ready(function(e) {
        'use strict';

$('#search-button').attr('disabled','disabled');

$("input[name='q']").keyup(function() {
                var enable = false;
                if ($(this).val().length >= 3) {
                    enable = true;
                }
                if (enable) {
                    $('#search-button').removeAttr('disabled');
                } else{
                    $('#search-button').attr('disabled','disabled');
                }
            });

$('#search-button').click(function(e) {
    $('form[name="search"]').submit();
   });
});