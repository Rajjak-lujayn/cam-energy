'use strict';
jQuery(document).ready(function ($) {

    $('.vi-ui.accordion').vi_accordion();
    // $('.vi-ui.accordion').accordion();

    jQuery('.vi-ui.tabular.menu .item').vi_tab({
        history: true,
        historyType: 'hash'
    });

    /*Setup tab*/
    // var tabs,
    //     tabEvent = false,
    //     initialTab = 'general',
    //     navSelector = '.vi-ui.menu',
    //     navFilter = function (el) {
    //         return jQuery(el).attr('href').replace(/^#/, '');
    //     },
    //     panelSelector = '.vi-ui.tab',
    //     panelFilter = function () {
    //         jQuery(panelSelector + ' a').filter(function () {
    //             return jQuery(navSelector + ' a[title=' + jQuery(this).attr('title') + ']').size() != 0;
    //         }).each(function (event) {
    //             jQuery(this).attr('href', '#' + $(this).attr('title').replace(/ /g, '_'));
    //         });
    //     };
    //
    // // Initializes plugin features
    // jQuery.address.strict(false).wrap(true);
    //
    // if (jQuery.address.value() == '') {
    //     jQuery.address.history(false).value(initialTab).history(true);
    // }

    // Address handler
    // jQuery.address.init(function (event) {
    //
    //     // Adds the ID in a lazy manner to prevent scrolling
    //     jQuery(panelSelector).attr('id', initialTab);
    //
    //     // Enables the plugin for all the content links
    //     jQuery(panelSelector + ' a').address(function () {
    //         return navFilter(this);
    //     });
    //
    //     panelFilter();
    //
    //     // Tabs setup
    //     tabs = jQuery('.vi-ui.menu')
    //         .vi_tab({
    //             history: true,
    //             historyType: 'hash'
    //         });
    //
    //     // Enables the plugin for all the tabs
    //     jQuery(navSelector + ' a').click(function (event) {
    //         tabEvent = true;
    //         jQuery.address.value(navFilter(event.target));
    //         tabEvent = false;
    //         return false;
    //     });
    // });


    /*Init JS input*/
    jQuery('.vi-ui.checkbox').checkbox();
    jQuery('select.vi-ui.dropdown').dropdown();
    jQuery('.select2').select2();
    /*Select all and Remove all countries in Currency by country*/
    jQuery('.wmc-select-all-countries').on('click', function () {
        var selectedItems = [];
        var allOptions = jQuery(this).closest('tr').find('select');
        allOptions.find('option').each(function () {
            jQuery(this).attr('selected', true);
        });
        allOptions.trigger("change");
    });

    jQuery('.wmc-remove-all-countries').on('click', function () {
        if (confirm("Would you want to remove all countries?")) {
            var selectedItems = [];
            var allOptions = jQuery(this).closest('tr').find('select');
            allOptions.find('option').each(function () {
                jQuery(this).removeAttr('selected', true);
            });
            allOptions.trigger("change");
        }
    });

    // jQuery("#IncludeFieldsMulti").select2("val", selectedItems);


    /*Save Submit button*/
    jQuery('.wmc-submit').one('click', function () {
        jQuery(this).addClass('loading');
    });
    jQuery('.select2-multiple').select2({
        width: '100%' // need to override the changed default
    });
    /*Color picker*/
    jQuery('.color-picker').iris({
        change: function (event, ui) {
            jQuery(this).parent().find('.color-picker').css({backgroundColor: ui.color.toString()});
            var ele = jQuery(this).data('ele');
            if (ele == 'highlight') {
                jQuery('#message-purchased').find('a').css({'color': ui.color.toString()});
            } else if (ele == 'textcolor') {
                jQuery('#message-purchased').css({'color': ui.color.toString()});
            } else {
                jQuery('#message-purchased').css({backgroundColor: ui.color.toString()});
            }
        },
        hide: true,
        border: true
    }).click(function () {
        jQuery('.iris-picker').hide();
        jQuery(this).closest('td').find('.iris-picker').show();
    });

    jQuery('body').click(function () {
        jQuery('.iris-picker').hide();
    });
    jQuery('.color-picker').click(function (event) {
        event.stopPropagation();
    });
    /*Update all rates*/
    jQuery('.wmc-update-rates').on('click', function () {
        var original_currency = jQuery('.wmc-currency-data input[name="woo_multi_currency_params[currency_default]"]:checked').val();
        var other_currencies = [];
        jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_default]"]').each(function () {
            if (original_currency != jQuery(this).val()) {
                other_currencies.push(jQuery(this).val());
            }
        });
        jQuery(this).addClass('loading');
        exchange_rate(original_currency, other_currencies);
    });

    /*Process Currency Options*/
    remove_currency();

    function insert_currency() {
        jQuery('.vi-ui.checkbox').unbind();
        jQuery('.vi-ui.checkbox').checkbox();

        jQuery('.wmc-add-currency').unbind();
        jQuery('.wmc-add-currency').on('click', function () {
            jQuery('.wmc-currency-data').last().find('select.select2').select2('destroy');
            var new_row = jQuery('.wmc-currency-data').last().clone();
            jQuery('.wmc-currency-data').last().find('select.select2').select2();
            new_row.find('input[name="woo_multi_currency_params[currency_default]"]').attr('checked', false);
            jQuery(new_row).appendTo('.wmc-currency-options tbody');
            remove_currency();
            jQuery('.wmc-currency-data').last().find('select.select2').select2().change();

        });

        jQuery('select[name="woo_multi_currency_params[currency][]"]').on('change', function () {
            var val = jQuery(this).val();
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_default]"]').val(val);
            jQuery(this).closest('tr').removeAttr('class').addClass('wmc-currency-data ' + val + '-currency');
        });

        jQuery('.wmc-currency-options tbody').sortable();

        /*Change currency default*/
        jQuery('input[name="woo_multi_currency_params[currency_default]"]').unbind('change');
        jQuery('input[name="woo_multi_currency_params[currency_default]"]').on('change', function () {
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_rate][]"]').removeAttr('readonly');
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_rate_fee][]"]').removeAttr('readonly');
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_rate][]"]').val(1).attr('readonly', true);
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_rate_fee][]"]').val(0).attr('readonly', true);
            var original_currency = jQuery(this).val();
            var other_currencies = [];
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_default]"]').each(function () {
                if (original_currency != jQuery(this).val()) {
                    other_currencies.push(jQuery(this).val());
                }
            });
            exchange_rate(original_currency, other_currencies);
        });

        /*Update single rate*/
        jQuery('.wmc-update-rate').on('click', function () {

            var original_currency = jQuery('.wmc-currency-data input[name="woo_multi_currency_params[currency_default]"]:checked').val();
            var other_currencies = jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_default]"]').val();

            if (original_currency != other_currencies) {
                jQuery(this).addClass('loading');
                exchange_rate(original_currency, other_currencies);
            }
        });

    }

    function remove_currency() {
        jQuery('.wmc-remove-currency').unbind();
        insert_currency();
        jQuery('.wmc-remove-currency').on('click', function () {
            if (confirm("Would you want to remove this currency?")) {
                if (jQuery('.wmc-currency-options tbody tr').length > 1) {
                    var tr = jQuery(this).closest('tr').remove();
                }
            } else {

            }
        });
    }

    function exchange_rate(original_currency, other_currencies) {
        if (original_currency && other_currencies) {
            var str_data = 'original_price=' + original_currency + '&other_currencies=' + other_currencies;

            jQuery.ajax({
                type: 'POST',
                data: 'action=woomulticurrency_exchange&' + str_data,
                url: ajaxurl,
                success: function (obj) {
                    jQuery.each(obj, function (currency, rate) {
                        if (jQuery('tr.' + currency + '-currency').length > 0) {
                            jQuery('tr.' + currency + '-currency').find('input[name="woo_multi_currency_params[currency_rate][]"]').val(rate);
                        }
                        jQuery('.woocommerce-multi-currency').find('.loading').removeClass('loading');
                    });
                },
                error: function (html) {
                }
            })
        } else {
            return false;
        }

    }


    /*Checkout currency*/
    jQuery('input[name="woo_multi_currency_params[checkout_currency]"]').on('change', function () {
        jQuery('select[name="woo_multi_currency_params[checkout_currency_args][]"]').removeAttr('disabled');
        jQuery(this).closest('tr').find('select[name="woo_multi_currency_params[checkout_currency_args][]"]').attr('disabled', 'disabled').find('option').removeAttr('selected').last().attr('selected', true);
    });
    /*
     * Start Get download key
     */
    jQuery('.villatheme-get-key-button').one('click', function (e) {
        let v_button = jQuery(this);
        v_button.addClass('loading');
        let data = v_button.data();
        let item_id = data.id;
        let app_url = data.href;
        let main_domain = window.location.hostname;
        main_domain = main_domain.toLowerCase();
        let popup_frame;
        e.preventDefault();
        let download_url = v_button.attr('data-download');
        popup_frame = window.open(app_url, "myWindow", "width=380,height=600");
        window.addEventListener('message', function (event) {
            /*Callback when data send from child popup*/
            let obj = jQuery.parseJSON(event.data);
            let update_key = '';
            let message = obj.message;
            let support_until = '';
            let check_key = '';
            if (obj['data'].length > 0) {
                for (let i = 0; i < obj['data'].length; i++) {
                    if (obj['data'][i].id == item_id && (obj['data'][i].domain == main_domain || obj['data'][i].domain == '' || obj['data'][i].domain == null)) {
                        if (update_key == '') {
                            update_key = obj['data'][i].download_key;
                            support_until = obj['data'][i].support_until;
                        } else if (support_until < obj['data'][i].support_until) {
                            update_key = obj['data'][i].download_key;
                            support_until = obj['data'][i].support_until;
                        }
                        if (obj['data'][i].domain == main_domain) {
                            update_key = obj['data'][i].download_key;
                            break;
                        }
                    }
                }
                if (update_key) {
                    check_key = 1;
                    jQuery('.villatheme-autoupdate-key-field').val(update_key);
                }
            }
            v_button.removeClass('loading');
            if (check_key) {
                jQuery('<p><strong>' + message + '</strong></p>').insertAfter(".villatheme-autoupdate-key-field");
                jQuery(v_button).closest('form').submit();
            } else {
                jQuery('<p><strong> Your key is not found. Please contact support@villatheme.com </strong></p>').insertAfter(".villatheme-autoupdate-key-field");
            }
        });
    });
    /**
     * End get download key
     */

    jQuery('.wmc-status-to-yes').on('click', function () {
        let listSelect = jQuery('select.wmc-checkout-currency-status');
        for (let i = 0; i < listSelect.length; i++) {
            let value = listSelect[i][1].value;
            jQuery(listSelect[i]).val(value);
        }
    });

    jQuery('.wmc-status-to-no').on('click', function () {
        let listSelect = jQuery('select.wmc-checkout-currency-status');
        for (let i = 0; i < listSelect.length; i++) {
            jQuery(listSelect[i]).val(0);
        }
    });

    //Price rule
    $('body').on('click', '.wmc-remove-price-rule', function () {
        $(this).closest('tr').remove();
    });

    $('.wmc-add-price-rule').on('click', function () {
        let currencyOpt = '';
        for (let i = 0; i < wmcParams.currencies.length; i++) {
            currencyOpt += `<option value="${wmcParams.currencies[i]}">${wmcParams.currencies[i]}</option>`;
        }
        let row = $('.wmc-price-rule-rows tr').last(), index = row.data('index') !== undefined ? row.data('index') + 1 : 0;
        let rowHtml = `<tr data-index="${index}">
                    <td>
                        <input type="text" required name="woo_multi_currency_params[beauty_price_from][]" class="wmc-beauty-from">
                    </td>
                    <td>
                        <input type="text" required name="woo_multi_currency_params[beauty_price_to][]" class="wmc-beauty-to">
                    </td>
                    <td>
                        <input type="text" name="woo_multi_currency_params[beauty_price_value][]" class="wmc-beauty-value">
                    </td>
                    <td>
                       <select name="woo_multi_currency_params[beauty_price_part][]" class="wmc-beauty-part">
                            <option value="integer">Integer</option>
                            <option value="fraction">Fraction</option>
                        </select>
                    </td>
                    <td>
                       <input type="hidden" name="woo_multi_currency_params[beauty_price_currencies][${index}][]">
                       <select multiple class="wmc-select-2" name="woo_multi_currency_params[beauty_price_currencies][${index}][]" >
                           ${currencyOpt}
                       </select>
                    </td>
                    <td>
                        <div class="vi-ui small icon red button wmc-remove-price-rule"><i class="trash icon"></i></div>
                    </td>
                </tr>`;

        row.after(rowHtml);
        $('.wmc-select-2').select2();
    });

    $('.wmc-select-2').select2();

    $('body').on('change', '.wmc-beauty-from, .wmc-beauty-to, .wmc-beauty-value, .wmc-beauty-part', function () {
        let row = $(this).closest('tr');
        let inFrac = row.find('.wmc-beauty-part').val(),
            fromEl = row.find('.wmc-beauty-from'),
            toEl = row.find('.wmc-beauty-to'),
            valueEl = row.find('.wmc-beauty-value'),
            messageBox = $('.wmc-beauty-price-message'),
            errClass = 'wmc-border-red';

        messageBox.removeClass('message').text('');
        fromEl.removeClass(errClass);
        toEl.removeClass(errClass);
        valueEl.removeClass(errClass);
        valueEl.attr('required', false);

        if (inFrac === 'fraction') {
            let error = false;
            if (fromEl.val() > 1) {
                fromEl.addClass(errClass);
                error = true;
            }
            if (toEl.val() > 1) {
                toEl.addClass(errClass);
                error = true;
            }
            if (valueEl.val() > 1) {
                valueEl.addClass(errClass);
                error = true;
            }

            if (error) {
                messageBox.addClass('message').text('Please enter value equal or smaller than 1');
            }
        } else if (inFrac === 'integer') {
            valueEl.attr('required', true);
            let fromLen = fromEl.val().length, toLen = toEl.val().length, valueLen = valueEl.val().length;
            if (fromLen !== toLen || fromLen !== valueLen || toLen !== valueLen) {
                fromEl.addClass(errClass);
                toEl.addClass(errClass);
                valueEl.addClass(errClass);
                messageBox.addClass('message').text('Please enter value has same length');
            }
        }
    });

    $('body').on('keypress', 'input[type=text]', function (e) {
        if (e.keyCode === 13 || e.keyCode === 10) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });


    const positionSticky = function (val) {
        switch (val) {
            case 'top-left':
                $('.wmc-distance.top, .wmc-distance.left, .wmc-distance-row').show();
                $('.wmc-distance.bottom, .wmc-distance.right').hide();
                break;
            case 'top-right':
                $('.wmc-distance.top, .wmc-distance.right, .wmc-distance-row').show();
                $('.wmc-distance.bottom, .wmc-distance.left').hide();
                break;
            case 'bottom-right':
                $('.wmc-distance.bottom, .wmc-distance.right, .wmc-distance-row').show();
                $('.wmc-distance.top, .wmc-distance.left').hide();
                break;
            case 'bottom-left':
                $('.wmc-distance.bottom, .wmc-distance.left, .wmc-distance-row').show();
                $('.wmc-distance.top, .wmc-distance.right').hide();
                break;
            default:
                $('.wmc-distance-row').hide();
        }
    };

    positionSticky($('.wmc-position-to-sticky').val());

    $('.wmc-position-to-sticky').on('change', function () {
        positionSticky($(this).val());
    });

    $('.wmc-auto-detect').on('change', function () {
        if ($(this).val() === '2') {
            $('.wmc-order-preview-row').show();
        } else {
            $('.wmc-order-preview-row').hide();
        }
    });

    $('.wmc-order-preview').select2({width: '100%', placeholder: 'Default: After product price'});
});
