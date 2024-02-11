$(document).ready(function(e) {
  'use strict';
 
var url = $('#fileuploader').data('url');

  /**
   * File uploader
   * @requires https://github.com/pqina/filepond
  */
var fileUploader = function () {
    var fileInput = document.querySelector('#fileuploader');
    if (fileInput.length === 0) return;

    if (typeof FilePondPluginFileValidateType !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginFileValidateType);
    }

    if (typeof FilePondPluginFileValidateSize !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginFileValidateSize);
    }

    if (typeof FilePondPluginImagePreview !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginImagePreview);
    }

    if (typeof FilePondPluginImageCrop !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginImageCrop);
    }

    if (typeof FilePondPluginImageResize !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginImageResize);
    }

    if (typeof FilePondPluginImageTransform !== 'undefined') {
      FilePond.registerPlugin(FilePondPluginImageTransform);
    }
    
    FilePond.setOptions({
    server: {
    	  url: url,
        process: { 
        	      onload: (msg) => {
                  console.log(msg);
        	      	 var json = JSON.parse(msg);
       
                   if (json.status == "success") {

                    $(".alert.alert-success").toggleClass('d-none d-block');
                    $(".alert.alert-success").html(json.message+"!");
                    location.reload();
                    }
                   
                   if (json.status == "error") {

                    $(".alert.alert-danger").toggleClass('d-none d-block');
                    $(".alert.alert-danger").html(json.message+"!");
                    }                    
                    serverResponse = JSON.parse(msg);
                  },
                  ondata: (form) => {
                  form.append('csrf_name',$('input[name="csrf_name"]').val());
                  form.append('csrf_value',$('input[name="csrf_value"]').val());
                  return form;
                  },
                  onerror: (response) => {
                  var json = JSON.parse(response);
                  $(".alert.alert-danger").toggleClass('d-none d-block');
                  $(".alert.alert-danger").html(json.message+"!");
                  serverResponse = JSON.parse(response);
                   }    
                },
        fetch: null,
        revert: null,
        instantUpload: true,
    },
    labelTapToUndo: 'tap to close',
    allowRevert: false,
    allowRemove: false,
    allowImagePreview: true,
    labelFileProcessingError: () => {
    return serverResponse.message;
    },
    labelFileProcessingComplete: () => {
    return serverResponse.message;
    }
  });
    
    
     FilePond.create(fileInput);
    }();
    
    
  });