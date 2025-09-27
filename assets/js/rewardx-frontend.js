(function ($) {
    'use strict';

    if (typeof rewardxFrontend === 'undefined') {
        return;
    }

    function showToast(message) {
        var $toast = $('.rewardx-toast');

        if (!$toast.length) {
            $toast = $('<div class="rewardx-toast" role="status"></div>').appendTo('body');
        }

        $toast.text(message).addClass('show');

        setTimeout(function () {
            $toast.removeClass('show');
        }, 4000);
    }

    function openModal(html) {
        var $modal = $('#rewardx-modal');
        $modal.find('.rewardx-modal-body').html(html);
        $modal.addClass('show').attr('aria-hidden', 'false');
    }

    function closeModal() {
        $('#rewardx-modal').removeClass('show').attr('aria-hidden', 'true');
    }

    function updateBalance(balance) {
        $('.rewardx-points').text(new Intl.NumberFormat().format(balance));
    }

    $(document).on('click', '.rewardx-modal-close', function () {
        closeModal();
    });

    $(document).on('click', '#rewardx-modal', function (e) {
        if ($(e.target).is('#rewardx-modal')) {
            closeModal();
        }
    });

    $('.rewardx-account').on('click', '.rewardx-redeem', function (e) {
        e.preventDefault();

        var $button = $(this);
        var card = $button.closest('.rewardx-card');
        var rewardId = card.data('reward-id');
        var type = $button.data('action');
        var cost = parseInt(card.data('cost'), 10);

        if ($button.prop('disabled')) {
            return;
        }

        if (type === 'voucher' || type === 'physical') {
            if (!confirm(rewardxFrontend.i18n.confirm)) {
                return;
            }
        }

        $button.prop('disabled', true).text(rewardxFrontend.i18n.processing);

        $.post(rewardxFrontend.ajaxUrl, {
            action: type === 'voucher' ? 'rewardx_redeem_voucher' : 'rewardx_redeem_physical',
            reward_id: rewardId,
            nonce: rewardxFrontend.nonce
        })
            .done(function (response) {
                if (response.success) {
                    updateBalance(response.data.balance);

                    if (response.data.order_url) {
                        openModal('<p>' + response.data.message + '</p><p><a class="button" href="' + response.data.order_url + '">' + rewardxFrontend.i18n.viewOrder + '</a></p>');
                    } else if (response.data.code) {
                        openModal('<h3>' + rewardxFrontend.i18n.voucherCode + '</h3><p class="rewardx-code">' + response.data.code + '</p><p>' + rewardxFrontend.i18n.expiry + ': ' + response.data.expiry + '</p>');
                    }

                    showToast(response.data.message);
                } else {
                    showToast(response.data && response.data.message ? response.data.message : 'Error');
                }
            })
            .fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error';
                showToast(message);
            })
            .always(function () {
                $button.prop('disabled', false).text(type === 'voucher' ? rewardxFrontend.i18n.redeemVoucher : rewardxFrontend.i18n.redeemPhysical);
            });
    });
})(jQuery);
