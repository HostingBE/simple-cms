import {ajaxPost} from './functions/ajax-request.js';


$(document).ready(function(e) {
'use strict';

$('body').on('focusout','input[name="alt-filename"]',function() {
var filename = $(this).val().replaceAll(' ','-');
var filenameold = $(this).data('name');

let data = "&filename="+filename+"&filenameold="+filenameold;

ajaxPost('/manager/change-filename', data).done(function(data) {

if (data.status == "error") {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html(data.message);
}
if (data.status == "success") {
$('.alert.alert-success').toggleClass('d-none d-block');
$('.alert.alert-success').html(data.message);
location.reload();
   }
}).fail(function (xhr, status, error) {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html("Status: " + status + " Error: " +  error);
     });
console.log("We renamed the file with an ajax call " + filename);
});

        
$("button.btn.btn-default.btn-sm").click(function () {
var filename = $(this).parent().text().trim();

var input = $("<input/>", {
  type: "text",
  name: "alt-filename",
  class: "form-control",
  id: "filename",
  "data-name": filename,
  placeholder: "Enter FileName",
  value: filename
     });
$(this).parent().html(input);
});

/*
* Get your select element and listen for a change event on it
*/ 
        $('input[name="alt-text"]').focusout(function() {
        var id = $(this).data('id');
        var alt = $(this).val();
        var csrfName = $('input[name="csrf_name"]').val();
        var csrfValue = encodeURIComponent($('input[name="csrf_value"]').val());

        var data = "id="+id+"&alt="+alt+"&csrf_name="+csrfName+"&csrf_value="+csrfValue;

          $.ajax({
            url: '/manager/alt-media',
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
            $("#ajax-form").trigger("reset");
                                 }
            }, error: function(xhr, status, error) {
            $('.alert.alert-danger').toggleClass('d-none d-block');
            $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
            }
        });
   });
});