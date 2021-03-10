jQuery(($) => {
  const $form = $('.js-mto-credentials')
  const $statusBar = $('.js-status-bar')
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
    resetStatusBar()
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
        successMsg(response)
      })
      .fail(function (response) {
        errorMsg(response)
      })
  })

  $('.js-run-sync-products').on('click', (e) => {
    $(e.currentTarget).attr('disabled', 'disabled')
    mtoRunSync('mto_run_product_sync')
  })

  $('.js-run-sync-orders').on('click', (e) => {
    $(e.currentTarget).attr('disabled', 'disabled')
    mtoRunSync('mto_run_order_sync')
  })

  const mtoRunSync = (action) => {
    resetStatusBar()
    $.ajax({
      method: 'POST',
      url: ajaxUrl,
      data: {
        action: action
      },
      dataType: 'json'
    })
      .done(function (response) {
        successMsg(response)
      })
      .fail(function (response) {
        errorMsg(response)
      })
  }
})
