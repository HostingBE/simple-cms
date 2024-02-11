$(document).ready(function(e) {
  'use strict';
   var reload = false;
   var url = $("#ajax-form").attr('action');

$("<input>").attr({
                name: "antispam",
                id: "spam",
                type: "hidden",
                value: btoa(url)
}).appendTo("#ajax-form"); 

    $('#submit-ajax').on('click', function(e) { 
        e.preventDefault(); // preventing default click action
        var data = $("#ajax-form :input").serializeArray();
        var reload = $("#ajax-form").data('reload');
        
        clear_div();

         $.ajax({
            url: url,
            type: 'POST',
            datatype: "json",
            data: data,
            success: function(msg) {
            var obj = JSON.parse(msg);  
            console.log("status" + obj.status + " message " + obj.message + "!");
            if (obj.status == "error") {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html(obj.message);
            return false;
            } 
            if (obj.status == "success") { 
            $('.alert.alert-danger').addClass('d-none');       
            $('.alert.alert-success').toggleClass('d-none d-block');
            $('.alert.alert-success').html(obj.message);
            if (reload === true) {
            location.reload();
            } else {
            $("#ajax-form").trigger("reset");
                                 }
                       }
            }, error: function(xhr, status, error) {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
            }
        });
    });
var clear_div = function() {



                    if ($('.alert.alert-danger').hasClass("d-block")) {
                    $('.alert.alert-danger').toggleClass('d-block d-none');
                    }

                    if ($('.alert.alert-success').hasClass("d-block")) {
                    $('.alert.alert-success').toggleClass('d-block d-none');
                    }

                    if ($('.alert.alert-info').hasClass("d-block")) {
                    $('.alert.alert-info').toggleClass('d-block d-none');
                    }
        }

});