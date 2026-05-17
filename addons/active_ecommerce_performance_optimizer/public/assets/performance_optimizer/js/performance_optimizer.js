/* Performance Optimizer Addon — Backend JS */
(function () {
    'use strict';

    window.perfConvertOne = function (btn, relative, format) {
        if (!relative) return;
        var $btn = jQuery(btn);
        var url  = (typeof window.AIZ !== 'undefined' ? '' : '') + '/admin/performance-optimizer/images/single';
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        jQuery.ajax({
            url: '/admin/performance-optimizer/images/single',
            method: 'POST',
            data: {
                _token: (window.AIZ && AIZ.data ? AIZ.data.csrf : jQuery('meta[name=csrf-token]').attr('content')),
                relative: relative,
                format: format || 'webp'
            },
            dataType: 'json'
        }).done(function (res) {
            if (res.success) {
                $btn.closest('tr').fadeOut(300);
                if (window.AIZ) AIZ.plugins.notify('success', 'Converted — saved ' + (res.saved_bytes || 0) + ' bytes');
            } else {
                $btn.prop('disabled', false).text(format.toUpperCase());
                if (window.AIZ) AIZ.plugins.notify('danger', res.error || 'Failed');
            }
        }).fail(function () {
            $btn.prop('disabled', false).text(format.toUpperCase());
            if (window.AIZ) AIZ.plugins.notify('danger', 'Request failed');
        });
    };
})();
