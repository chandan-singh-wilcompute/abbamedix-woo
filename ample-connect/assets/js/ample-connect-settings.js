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

    $('#product_fetch_and_sync').on('click', function () {
        $.post(ajaxurl, {
            action: 'fetch_and_store_product_data'
        }, function(response) {
            if (response.success) {
                // console.log(response.data);
                console.log('Data saved. Starting batch processing...');
                runBatch(); // this function calls the batch processor via AJAX
            } else {
                alert('Error: ' + response.data);
            }
        });
    });

    function runBatch() {
        console.log("Batch running started!");
        let num_of_products = 0;
        $.post(ajaxurl, {
            action: 'run_product_batch_processing'
        }, function(response) {
            console.log(response.data);
            num_of_products = num_of_products + 50;
            if (response.data !== 'Done. File deleted.') {
                setTimeout(runBatch, 1000); // call next batch after 1 second
            } else {
                alert('All products processed! Total products = ' + num_of_products );
            }
        });
    }

});
