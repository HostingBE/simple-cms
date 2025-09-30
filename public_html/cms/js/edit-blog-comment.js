import {ajaxPost} from './functions/ajax-request.js';
var pathname = new URL(window.location.href).pathname;

var id = pathname.split('/')[3];
var code = pathname.split('/')[4];

$("div#action button").click(function(e) {

const data = "&status="+$(this).text();
ajaxPost('https://'+window.location.hostname+'/manager/edit-blog-comment/'+id+'/'+code+'/', data).done(function(data) {
          
if (data.status == "error") {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html(data.message);
}
if (data.status == "success") {
$('.alert.alert-success').toggleClass('d-none d-block');
$('.alert.alert-success').html(data.message);
        }
}).fail(function (xhr, status, error) {
$('.alert.alert-danger').toggleClass('d-none d-block');
$('.alert.alert-danger').html("Status: " + xhr.status + " Error: " +  xhr.statusText);
    });
});