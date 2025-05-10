$(document).ready(function(e) {
        'use strict';

$("select#sortby").change(function() {
  // set the window's location property to the value of the option the user has selected
  window.location = "?sortby="+$(this).val();
  });


$("select#category").change(function() {
  if ($(this).val() == "") {
  window.location = "/forum";
  } else {
  window.location = "/forum/"+$('#category option:selected').text().replace(/\s+/g, '-').toLowerCase() + "/" + $(this).val() + "/";
     }
  });

});