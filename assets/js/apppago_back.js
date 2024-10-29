/**
 * Plugin Name: Nexi XPay
 * Plugin URI:
 * Description: New Nexi Payments gateway. Official Nexi XPay plugin.
 * Version: 3.2.0
 * Author: Nexi SpA
 * Author URI: https://www.nexi.it
 * Text Domain: woocommerce-gateway-nexi-xpay
 * Domain Path: /lang
 *
 * Copyright: © 2017-2018, Nexi SpA
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
jQuery(function ($) {
    $(document).ready(function () {
        $('.info-helper').each(function (index, element) {
            sistemaDefault($(element), false);
        });

        $('.categories-select2').select2({
            closeOnSelect: false,
            scrollAfterSelect: true,
            templateSelection: formatState
        });

        function formatState(state) {
            var temp = state.text.split('->');

            return temp[temp.length - 1];
        }

        var tr = $('select[id$="ap_incomplete_status"]').closest('tr');

        tr.height(parseInt(tr.height()) + 40 + "px");

        tr.children('th, td').css("vertical-align", "bottom");
    });


    $('.info-helper').on('click', function () {
        sistemaDefault($(this), true);
    });

    function sistemaDefault(element, click) {
        var id = element.data('id');

        if (id) {
            if (click || !$('#' + id).val()) {
                $('#' + id).val(element.data('default'));
                $('#' + id).trigger('change');
            }
        }
    }
});