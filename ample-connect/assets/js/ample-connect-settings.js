jQuery(document).ready(function($) {
    function toggleProductSyncTimeField() {
        if ($('#ample_connect_product_sync_enabled').is(':checked')) {
            $('#product_sync_time_field').show();
        } else {
            $('#product_sync_time_field').hide();
        }
    }

    // Initial check
    toggleProductSyncTimeField();

    // Check on change
    $('#ample_connect_product_sync_enabled').change(function() {
        toggleProductSyncTimeField();
    });
});
