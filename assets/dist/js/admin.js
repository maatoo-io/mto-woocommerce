"use strict";

jQuery(($) => {
  const $form = $('.js-mto-credentials')
  const $statusBar = $('.js-status-bar')
  const $submitButton = $(".mto-credentials").find(':submit')
  const ajaxUrl = window.ajaxurl

  const resetStatusBar = () => {
    $statusBar.find('.success').addClass('hidden')
    $statusBar.find('.error').addClass('hidden')
  }

  const successMsg = (response) => {
    let $holder = $statusBar.find('.success')
    if (response.isError) {
      $holder = $statusBar.find('.error')
    }
    $holder.html(response.body).removeClass('hidden')
  }

  const errorMsg = (response) => {
    $statusBar.find('.error').html(response.body).removeClass('hidden')
  }

  $form.on('submit', (e) => {
    e.preventDefault()
    $submitButton.prop('disabled', true)
    var oldValue = $submitButton.prop('value')
    $submitButton.prop('value', 'Saving...')
    resetStatusBar()
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
        marketing_cta: $form.find('#marketing_cta').val()
      },
      dataType: 'json'
    })
      .done(function (response) {
        $submitButton.prop('disabled', false)
        $submitButton.prop('value', oldValue)
        successMsg(response)
      })
      .fail(function (response) {
        errorMsg(response)
        $submitButton.prop('disabled', false)
        $submitButton.prop('value', oldValue)
      })
  })

})
