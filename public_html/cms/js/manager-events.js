import {ajaxPost} from './functions/ajax-request.js';


$(document).ready(function(e) {
  'use strict';

var time = "";
var data = "";
var thisRegex = new RegExp('/manager/');
var url = '/manager/overview-events';

/**
 * Play the chime sound
 */
function playSound() {
  var audio = new Audio('https://'+window.location.hostname+'/uploads/chat_request.mp3');
  audio.autoplay = true;
  var result = audio.play();
  if (result !== undefined) {
    console.log("Autoplay sound disabled!");
    result.then(_ => {
        }).catch(error => {
    });
  }
}

function getEvents() {
         if(!thisRegex.test(window.location.pathname)) {
          console.log("Javascript events manager does not meet the terms to run!");
          } else {


ajaxPost(url, data).done(function(data) {

if (data.status == "error") {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html(data.message);
}
if (data.status == "success") {

    if (data.events.length > 0) {
            $.toast().reset('all');
            for (let i = 0;i < data.events.length;i++) {	

           	$.toast({
  				    text : data.events[i].text,
    			    heading: data.events[i].heading, // Optional heading to be shown on the toast
    			    icon: data.events[i].icon, // Type of toast icon
    			    showHideTransition: 'slide', // fade, slide or plain
    			    allowToastClose: true, // Boolean value true or false
    			    hideAfter: false, // false to make it sticky or number representing the miliseconds as time after which toast needs to be hidden
    			    stack: 5, // false if there should be only one toast at a time or a number representing the maximum number of toasts to be shown at a time
    			    position: 'bottom-right'
				      });

              }
	                  playSound();
            }
   }
}).fail(function (xhr, status, error) {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html("Status: " + status + " Error: " +  error);
        });
    }
}

setInterval(getEvents, 30000);
getEvents();
});