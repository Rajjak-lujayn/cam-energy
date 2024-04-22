var remaining = {};

jQuery(document).ready(function() {
    update_sync_counters();
});

function update_sync_counters() {
    var elements = jQuery('.wrap .notice').find('.opmc-odoo-sync-notices');

    elements.each(function() {
        var element = jQuery(this);
        var elementId = element.attr('id');

        if (!(elementId in remaining)) {
            remaining[elementId] = true;
        }
        // console.log('Notice ID: ' + elementId + '_notices');
        console.log('Update counter recursive');

        jQuery.ajax({
            type: "post",
            dataType: "JSON",
            url: odoo_admin_notices.ajax_url,
            data: { action: elementId + '_notices', security: odoo_admin_notices.ajax_nonce },
            success: function(response) {
                console.log(response);
                if (!response.remaining_items) {
                    jQuery('#' + elementId).html(response.message);
                    remaining[elementId] = response.remaining_items;
                } else {
                    jQuery('#' + elementId + '.opmc-odoo-sync-notices .' + elementId.replace(/_/g, "-") + '-status').html(response.message);
                    remaining[elementId] = response.remaining_items;
                    console.log(remaining);
                }
            },
            complete: function() {
                if (remaining[elementId]) {
                    console.log('call recursive : ');
                    console.log(remaining);
                    setTimeout(update_sync_counters, 3000);
                }
            }
        });
    });
}
