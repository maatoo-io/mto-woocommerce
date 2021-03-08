jQuery(($) => {
  const $form = $('.js-mto-credentials')
  const $statusBar = $('.js-status-bar')
  const ajaxUrl = window.ajaxurl
  $form.on('submit', (e) => {
    e.preventDefault()
    $statusBar.find('.success').addClass('hidden')
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
    })
      .done(function (response) {
        let $holder = $statusBar.find('.success')
        if (response.isError) {
          $holder = $statusBar.find('.error')
        }
        $holder.html(response.body).removeClass('hidden')
      })
      .fail(function (response) {
        $statusBar.find('.error').html(response.body).removeClass('hidden')
      })
  })
})
