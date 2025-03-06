jQuery(document).ready(function ($) {
  $(document).on('click', '.aesirx-analytics-notice .notice-dismiss', function () {
    $.post(ajaxurl, {
      action: 'aesirx_dismiss_crontrol_notice',
    });
  });
});
