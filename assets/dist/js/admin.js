"use strict";

jQuery(function ($) {
  var $form = $('.js-mto-credentials');
  var $statusBar = $('.js-status-bar');
  var ajaxUrl = window.ajaxurl;

  var resetStatusBar = function resetStatusBar() {
    $statusBar.find('.success').addClass('hidden');
    $statusBar.find('.error').addClass('hidden');
  };

  var successMsg = function successMsg(response) {
    var $holder = $statusBar.find('.success');

    if (response.isError) {
      $holder = $statusBar.find('.error');
    }

    $holder.html(response.body).removeClass('hidden');
  };

  var errorMsg = function errorMsg(response) {
    $statusBar.find('.error').html(response.body).removeClass('hidden');
  };

  $form.on('submit', function (e) {
    e.preventDefault();
    resetStatusBar();
    $.ajax({
      method: 'POST',
      url: ajaxUrl,
      data: {
        action: 'mto_save_options',
        username: $form.find('#login').val(),
        pass: $form.find('#password').val(),
        url: $form.find('#url').val(),
        birthday: $form.find('#birthday').is(':checked')
      },
      dataType: 'json'
    }).done(function (response) {
      successMsg(response);
    }).fail(function (response) {
      errorMsg(response);
    });
  });
});