$(document).ready(function(e) {
  'use strict';


var cookieSettings = new BootstrapCookieConsentSettings({
    contentURL: "/node_modules/bootstrap-cookie-consent-settings/cookie-consent-content",
    privacyPolicyUrl: "/privacy-policy",
    legalNoticeUrl: "/disclaimer",
    postSelectionCallback: function () {
        location.reload() // reload after selection
    }
})


$('#cookieconsent').on('click', function(e) { 
cookieSettings.showDialog();
});



});