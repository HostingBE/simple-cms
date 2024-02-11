$(document).ready(function(e) {
  'use strict';

    $('.btn.btn-icon.btn-default').on('click', function(e) { 
        e.preventDefault(); // preventing default click action
        var id = $(this).data('id');
        var data = "&id="+id+"&csrf_name="+$('input[name="csrf_name"]').val()+"&csrf_value="+encodeURIComponent($('input[name="csrf_value"]').val());

          $.ajax({
            url: '/support-like',
            type: 'POST',
            datatype: "html",
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
            location.reload();
                                 }
            }, error: function(xhr, status, error) {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
            }
        });

     });
});