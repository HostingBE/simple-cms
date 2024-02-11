$(document).ready(function(e) {
        'use strict';

$('.btn.btn-secondary').attr('disabled','disabled');

$("input[name='q']").keyup(function() {
                var enable = false;
                if ($(this).val().length >= 3) {
                    enable = true;
                }
                if (enable) {
                    $('.btn.btn-secondary').removeAttr('disabled');
                } else{
                    $('.btn.btn-secondary').attr('disabled','disabled');
                }
            });


// get your select element and listen for a change event on it
$("select#category").change(function() {
  // set the window's location property to the value of the option the user has selected
  window.location = "/seo-blog/"+$(this).val()+"-"+$('#category option:selected').text().replace(/\s+/g, '-').toLowerCase() +"/";
  });

$("select#sortby").change(function() {
  // set the window's location property to the value of the option the user has selected
  window.location = "?sortby="+$(this).val();
  });

$(".btn.btn-secondary").click(function(e) {
    $('form[name="search"]').submit();
   });
});