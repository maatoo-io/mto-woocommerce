"use strict";

jQuery(function ($) {
  var $form = $('.js-mto-credentials');
  var $statusBar = $('.js-status-bar');
  var ajaxUrl = window.ajaxurl;
  $form.on('submit', function (e) {
    e.preventDefault();
    $statusBar.find('.success').addClass('hidden');
    $statusBar.find('.error').addClass('hidden');
    $.ajax({
      method: 'POST',
      url: ajaxUrl,
      data: {
        action: 'mto_save_options',
        username: $form.find('#login').val(),
        pass: $form.find('#password').val(),
        url: $form.find('#url').val()
      },
      dataType: 'json'
    }).done(function (response) {
      var $holder = $statusBar.find('.success');

      if (response.isError) {
        $holder = $statusBar.find('.error');
      }

      $holder.html(response.body).removeClass('hidden');
    }).fail(function (response) {
      $statusBar.find('.error').html(response.body).removeClass('hidden');
    });
  });
  $('.js-run-sync-products').on('click', function (e) {
    $(e.currentTarget).attr('disabled', 'disabled');
    mtoRunSync('mto_run_product_sync');
  });
  $('.js-run-sync-orders').on('click', function (e) {
    $(e.currentTarget).attr('disabled', 'disabled');
    mtoRunSync('mto_run_order_sync');
  });

  var mtoRunSync = function mtoRunSync(action) {
    $.ajax({
      method: 'POST',
      url: ajaxUrl,
      data: {
        action: action
      },
      dataType: 'json'
    }).done(function (response) {
      console.log(response);
    }).fail(function (response) {
      console.log(response);
    });
  };
});