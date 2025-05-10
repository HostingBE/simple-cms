$(document).ready(function(e) {
    'use strict';

    
var genPass = function (len, upper, nums, special) {
    const lower = "abcdefghijklmnopqrstuvwxyz";
    const upperChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numChars = "0123456789";
    const specialChars = "!@#$%^&*()-_=+[]{}|;:,.?";
    let chars = lower;

    if (upper) chars += upperChars;
    if (nums) chars += numChars;
    if (special) chars += specialChars;

    let pass = "";

    for (let i = 0; i < len; i++) {
        const randIdx = Math.floor(Math.random() * chars.length);
        pass += chars[randIdx];
    }

    return pass;
}


$(document).on("click", ".btn.btn-primary.view", function(event) {
    event.preventDefault();
    if ($('#account-password-new').attr('type') == "text") {
        $('#account-password-new').attr('type', 'password')
    } else {
        $('#account-password-new').attr('type', 'text');
    }
});

$(document).on("click", ".btn.btn-primary.generate", function(event) {
        event.preventDefault();
        var passwd = genPass(32,true,true,true);
        $('#account-password-new').val(passwd);
        $('#account-password-confirm').val(passwd);
    });

});

