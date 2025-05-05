$(document).ready(function() {

$(document).on("click", ".btn.btn-danger.btn-sm", function(event) {
event.preventDefault();
var reload = true;
var url = $(this).data('url');
reload = $(this).data('reload');


  swal.fire({
    title: "Are you really sure?",
    html: "Deleting this record is permanent, gone is gone! " + "<br />" + "<strong>" + url + "</strong>",
    showCancelButton: true,
    confirmButtonColor: "#DD6B55",
    confirmButtonText: "Yes, delete record!",
    cancelButtonText: "No, cancel!",
  }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          type: 'GET',
          url: url,
          success: function (data) {
          swal.fire("Deleted!", "This record is deleted!", "success");
          },
          error: function (data) {
            swal.fire("NOT Deleted!", "Something blew up.", "warning");
          }
        });
                  if (reload === true) {
          location.reload();
              }  
      } else {
        swal.fire("Cancelled", "deletion of selected record is cancelled by user!", "error");
      }

    });

  });
});