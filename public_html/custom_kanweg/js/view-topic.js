  $(document).ready(function(e) {
  'use strict';                



$('#reply-btn').on('click', function(e) {

$("<input>").attr({
                name: "topic",
                id: "topic",
                type: "hidden",
                value: $('#reply-btn').data('id')
}).appendTo("#ajax-form"); 


                    $('#reply').toggleClass('d-block d-none');
                    
});


    $('.btn.btn-icon.btn-default').on('click', function(e) { 
        e.preventDefault(); // preventing default click action
        var id = $(this).data('id');
        var source = $(this).data('source');
        var data = "&id="+id+"&source="+source;

          $.ajax({
            url: '/forum-like',
            type: 'POST',
            datatype: "html",
            data: data,
            success: function(msg) {
            console.log(data);
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