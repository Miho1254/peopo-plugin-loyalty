(function ($) {
    'use strict';

    $('.rewardx-adjust-button').on('click', function () {
        var $wrap = $(this).closest('.rewardx-adjust-points');
        var delta = parseInt($wrap.find('.rewardx-delta').val(), 10);
        var reason = $wrap.find('.rewardx-reason').val();
        var userId = $wrap.data('user-id');

        if (!delta || !reason) {
            alert(rewardxAdmin.i18n.missing);
            return;
        }

        if (!window.confirm(rewardxAdmin.i18n.confirm)) {
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true);

        $.post(rewardxAdmin.ajaxUrl, {
            action: 'rewardx_adjust_points',
            nonce: rewardxAdmin.nonce,
            user_id: userId,
            delta: delta,
            reason: reason
        })
            .done(function (response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#rewardx_points').val(response.data.balance);
                    $wrap.find('.rewardx-delta').val('');
                    $wrap.find('.rewardx-reason').val('');
                } else {
                    alert(response.data && response.data.message ? response.data.message : 'Error');
                }
            })
            .fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error';
                alert(message);
            })
            .always(function () {
                $button.prop('disabled', false);
            });
    });
})(jQuery);
