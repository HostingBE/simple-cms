$(document).ready(function(e) {
  'use strict';

var storage = sessionStorage.getItem("ajax-form");
if (storage !== null) {
var json = JSON.parse(storage);
    $('input[name="title"]').val(json.title);
    $('textarea[name="message"]').val(json.message);
    $('input[name="tags"]').val(json.tags);
    $('select[name="category"]').val(json.category);
   }


$('input[type="text"], textarea').focusout(function() {
console.log("focus out on area" + $('#ajax-form').serialize());
const value = { 'title': $('input[name="title"]').val(), 'category': $('select[name="category"]').val(), 'message': $('textarea[name="message"]').val(),'tags': $('input[name="tags"]').val() };
sessionStorage.setItem('ajax-form',JSON.stringify(value));
});


      $.ajax({
            url: '/ask-question-files',
            type: 'GET',
            datatype: "html",
            success: function(response) {
            $('#files').html(response);
            }, error: function(xhr, status, error) {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
            }
      });

  $('#files').on('click','a.link-default', function(e) {
   e.preventDefault(); // preventing default click action
   var filename = $(this).data('filename');
 

   $.ajax({
            url: '/topic-delete-file/'+filename,
            type: 'GET',
            datatype: "html",
            success: function(response) {
            location.reload();

            }, error: function(xhr, status, error) {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
            }
      });






   });

$("#submit-ajax").click(function(e) {
e.preventDefault();
sessionStorage.clear();
return true;
});

});
