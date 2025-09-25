$(document).ready(function(e) {
    'use strict';

    var myInterval;
    var url = window.location;

  $("<input>").attr({
                  name: "antispam",
                  id: "spam",
                  type: "hidden",
                  value: btoa(window.location.pathname)
  }).appendTo("#form-chat");
  
  
function returnUrl(url) {
  return  'https://' + new URL(url).host;
}


  function checkLogin() {

    $.ajax({
      type: 'GET',
      datatype: 'json',
      url:  returnUrl(url) + '/chat-check-login',
      success: function(data) {
        console.log("checkLogin: " + data);
      var json = JSON.parse(data);
      if (json.status == "success") {
            $("#form-chat").attr('action', 'https://' + new URL(url).host + '/add-chat-message');
            $('.modal-footer').html(`<div class="input-group"><input type="text" name="message" class="form-control" placeholder="type your message .."><button type="submit" id="submit-chat" class="btn btn-info">send</button></div>`);
            getMessages();
            } else {
            return false;  
            }
      }, 
      error: function(xhr, status, error) {
                              $('.alert.alert-danger').toggleClass('d-none d-block');
                              $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
                              }
  
      });
  }  
  
  
  function getMessages() {
  
  $.ajax({
                  type: 'GET',
                  datatype: 'json',
                  url: returnUrl(url) + '/chat-overview',
  
  
                  success: function (data) {
                  console.log("messages reload executed!");
                  var json = JSON.parse(data);
                       if (json.status == "error") {
                                  $('#chatmessage').toggleClass('d-none d-block');
                                  $('#chatmessage').html(json.message);
                                  return false;
                                  }
                       if (json.status == "success") {
                             if (json.messages.length > 0) {
                              $('.modal-body.chat-messages').html(''); 
                               
                             for (let i = 0;i < json.messages.length;i++) {
                       
                             $('.modal-body.chat-messages').html($('.modal-body.chat-messages').html() + `<div class="chat-message-${json.messages[i].align} mb-4">
                  <div class="row">
                    <div class="col-sm-2">
                    <img src="${json.messages[i].avatar}" class="rounded-circle" alt="${json.messages[i].name}" width="40" height="40">
                    <div class="text-muted small text-nowrap">${json.messages[i].time}</div>
                      </div>
                  <div class="col-sm-10">
                  <div class="flex-shrink-1 bg-${json.messages[i].background} text-${json.messages[i].text} rounded p-2">
                    <div class="fw-bold fs-xs">${json.messages[i].name}</div>
                    ${json.messages[i].message}
                      </div>
                    </div>
                </div>`);
  
                                      } 
                             $('.modal-body.chat-messages').html($('.modal-body.chat-messages').html() + `<span class="alert alert-danger d-none p-1" id="chatmessage"></span>`);
                             $('.modal-body.chat-messages').animate({ scrollTop: $('.modal-body.chat-messages').prop("scrollHeight")}, 1000);
                                     }
                                 }
                              }, error: function(xhr, status, error) {
                              $('.alert.alert-danger').toggleClass('d-none d-block');
                              $('.alert.alert-danger').html("Status: " + status + " Foutmelding: " +  error);
                              }
                    });
  }
  
  
  
      $('#form-chat').on('click', '#submit-chat',  function(e) { 
          e.preventDefault(); // preventing default click action
          var data = $("#form-chat :input").serializeArray();
          var action = $("#form-chat").attr('action');
  
          $.ajax({
              url: action,
              type: 'POST',
              datatype: "json",
              data: data,
              success: function(msg) {
              var obj = JSON.parse(msg);  
              console.log("status" + obj.status + " message " + obj.message + "!");
              if (obj.status == "error") {
              $('#chatmessage').toggleClass('d-none d-block');
              $('#chatmessage').html(obj.message);
              return false;
              } 

              if (obj.status == "success") {
                  if (new URL(action).pathname == "/chat-signin") { 
                   $('.modal-body.chat-messages').html("Your chat request is generated and the messages are loaded!");
                   $("#form-chat").attr('action','https://' + returnUrl(action) + '/add-chat-message');
                   $('.modal-footer').html(`<div class="input-group"><input type="text" name="message" class="form-control" placeholder="enter your message"><button type="submit" id="submit-chat" class="btn btn-info">send</button></div>`);
                   getMessages();
                   myInterval = setInterval(getMessages, 3000);
                   } else {
                   $("#form-chat").trigger("reset");
                   getMessages();
                   }
                }
              }, error: function(xhr, status, error) {
              $('#chatmessage').toggleClass('d-none d-block');
              $('#chatmessage').html("Status: " + status + " Foutmelding: " +  error);
              }
          });
  });
  
  $(document).on('hidden.bs.modal', '#chatModal', function (e) {
      clearInterval(myInterval);
  });
  
  $(document).on('show.bs.modal', '#chatModal', function (e) {
      checkLogin();
      });
  });
  
  
  