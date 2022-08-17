"use strict";

jQuery(function ($) {
  var $form = $('.js-mto-credentials');
  var $statusBar = $('.js-status-bar');
  var $submitButton = $(".mto-credentials").find(':submit');
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

  $form.find("#toggle_marketing_optin_settings").on('click', function (e) {
    $form.find("#marketing_optin_options").toggle();
  });
  $form.on('submit', function (e) {
    e.preventDefault();
    $submitButton.prop('disabled', true);
    var oldValue = $submitButton.prop('value');
    $submitButton.prop('value', 'Saving...');
    resetStatusBar();
    $.ajax({
      method: 'POST',
      url: ajaxUrl,
      data: {
        action: 'mto_save_options',
        username: $form.find('#login').val(),
        pass: $form.find('#password').val(),
        url: $form.find('#url').val(),
        birthday: $form.find('#birthday').is(':checked'),
        marketing: $form.find('#marketing').is(':checked'),
        marketing_checked: $form.find('#marketing_checked').is(':checked'),
        marketing_cta: $form.find('#marketing_cta').val(),
        marketing_position: $form.find('#marketing_position').val(),
        product_image_sync_quality: $form.find('#product_image_sync_quality').val()
      },
      dataType: 'json'
    }).done(function (response) {
      $submitButton.prop('disabled', false);
      $submitButton.prop('value', oldValue);
      successMsg(response);
    }).fail(function (response) {
      errorMsg(response);
      $submitButton.prop('disabled', false);
      $submitButton.prop('value', oldValue);
    });
  });
});