$(document).ready(function(e) {
  'use strict';

function activateTooltip() {   
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
      
    });
  }

activateTooltip();
});