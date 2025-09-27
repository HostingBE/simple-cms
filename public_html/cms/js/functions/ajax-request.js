export function ajaxGet(url) {

  return $.ajax({
        cache:      false,
        url:        url,
        dataType:   "json",
        type:       "GET"
    });             
}

export function ajaxPost(url, data) {

var csrfName = $('input[name="csrf_name"]').val();
var csrfValue = encodeURIComponent($('input[name="csrf_value"]').val());
data = data + '&csrf_name='+csrfName+'&csrf_value='+csrfValue;

    return $.ajax({
        cache:      false,
        url:        url,
        dataType:   "json",
        type:       "POST",
        data:       data
    });             
}

export default function placebo() {
  return;
}