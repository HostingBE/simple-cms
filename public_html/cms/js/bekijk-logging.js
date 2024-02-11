$("select[name='log']").change(function(){

var csrfName = $('input[name="csrf_name"]').val();
var csrfValue = encodeURIComponent($('input[name="csrf_value"]').val());  
var file = $("select[name='log']").val();
  
       $.ajax({
          type: 'GET',
          url: '/manager/do-bekijk-logging/'+file+'/',
         
          success: function (data) {
          	  data = data.replace(/(?:\r\n|\r|\n)/g, '<br>'); 
             $(".overflow-auto").html(data);
             
          },
          error: function (data) {
            alert("NOT Deleted!", "Something blew up.", "error");
          }
        });





});
