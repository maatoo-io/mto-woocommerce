"use strict";

jQuery(function ($) {
  var getCookie = function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
  };

  $(document).ready(function () {
    var mtoId = getCookie('mtc_id');

    if (mtoId) {
      $.ajax({
        method: 'POST',
        url: window.mto.ajaxUrl,
        data: {
          action: 'mto_get_contact_id',
          id: mtoId
        },
        dataType: 'json'
      }).done(function (response) {
        console.log(response.body);
      }).fail(function (response) {
        console.log(response.body);
      });
    }
  });
});