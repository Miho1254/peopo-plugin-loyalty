(function ($) {
    'use strict';

    if (typeof rewardxFrontend === 'undefined') {
        return;
    }

    var $account = $('.rewardx-account');

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

    function getCurrentBalance() {
        var current = parseInt($account.attr('data-current-points'), 10);
        return isNaN(current) ? 0 : current;
    }

    function refreshCardStates() {
        if (!$account.length) {
            return;
        }

        var currentBalance = getCurrentBalance();

        $account.find('.rewardx-card').each(function () {
            var $card = $(this);
            var cost = parseInt($card.data('cost'), 10) || 0;
            var isOutOfStock = $card.attr('data-state') === 'out_of_stock';
            var hasEnoughPoints = currentBalance >= cost;

            if (!isOutOfStock) {
                $card.attr('data-state', hasEnoughPoints ? 'available' : 'missing_points');
                $card.find('.rewardx-redeem').prop('disabled', !hasEnoughPoints);
            }

            $card.attr('data-has-points', hasEnoughPoints ? 'yes' : 'no');
        });
    }

    function applyRewardFilters() {
        if (!$account.length) {
            return;
        }

        refreshCardStates();

        var showAvailableOnly = $account.find('.rewardx-filter-toggle').is(':checked');
        var keywordField = $account.find('.rewardx-search-input');
        var keyword = keywordField.length ? (keywordField.val() || '').toString().trim().toLowerCase() : '';

        $account.find('.rewardx-section[data-section]').each(function () {
            var $section = $(this);
            var isHiddenSection = $section.hasClass('rewardx-section--hidden');
            var visibleCount = 0;
            var $cards = $section.find('.rewardx-card');

            $cards.each(function () {
                var $card = $(this);
                var hasPoints = $card.attr('data-has-points') === 'yes';
                var textContent = ($card.find('.rewardx-card-title').text() + ' ' + $card.find('.rewardx-card-description').text()).toLowerCase();
                var matchesKeyword = !keyword || textContent.indexOf(keyword) !== -1;
                var shouldShow = (!showAvailableOnly || hasPoints) && matchesKeyword;

                $card.toggleClass('rewardx-card--hidden', !shouldShow);

                if (shouldShow) {
                    visibleCount++;
                }
            });

            var $emptyState = $section.find('.rewardx-empty-filter');

            if ($emptyState.length) {
                if (!isHiddenSection && showAvailableOnly && visibleCount === 0 && $cards.length) {
                    $emptyState.removeAttr('hidden');
                } else {
                    $emptyState.attr('hidden', 'hidden');
                }
            }
        });

    }

    function activateTab(target) {
        if (!target || !$account.length) {
            return;
        }

        var $tabs = $account.find('.rewardx-tab[data-target]');

        if (!$tabs.length) {
            return;
        }

        $tabs.each(function () {
            var $tab = $(this);
            var isActive = $tab.data('target') === target;
            $tab.toggleClass('is-active', isActive).attr('aria-selected', isActive ? 'true' : 'false');
        });

        $account.find('.rewardx-section[data-section]').each(function () {
            var $section = $(this);
            var matches = $section.data('section') === target;

            if (matches) {
                $section.removeClass('rewardx-section--hidden').removeAttr('hidden');
            } else {
                $section.addClass('rewardx-section--hidden').attr('hidden', 'hidden');
            }
        });

        applyRewardFilters();
    }

    function updateBalance(balance) {
        $('.rewardx-points').text(new Intl.NumberFormat().format(balance));

        if ($account.length) {
            $account.attr('data-current-points', balance);
            applyRewardFilters();
        }
    }

    $(document).on('click', '.rewardx-modal-close', function () {
        closeModal();
    });

    $(document).on('click', '#rewardx-modal', function (e) {
        if ($(e.target).is('#rewardx-modal')) {
            closeModal();
        }
    });

    $account.on('click', '.rewardx-tab[data-target]', function (e) {
        e.preventDefault();
        activateTab($(this).data('target'));
    });

    $account.on('change', '.rewardx-filter-toggle', function () {
        applyRewardFilters();
    });

    $account.on('click', '.rewardx-redeem', function (e) {
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

    if ($account.length) {
        applyRewardFilters();
    }
})(jQuery);
