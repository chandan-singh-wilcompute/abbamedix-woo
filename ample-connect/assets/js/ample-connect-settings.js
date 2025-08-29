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

    //let product_batch_count = 1;
    let num_of_products = 0;
    $('#product_fetch_and_sync').on('click', function (e) {
        e.preventDefault();

        if (!confirm("⚠️ Are you sure, you want to start product sync?")) {
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text('Syncing...');

        $.post(ajaxurl, {
            action: 'fetch_and_store_product_data'
        }, function(response) {
            if (response.success) {
                //console.log(response.data);
                console.log('Data saved. Starting batch processing...');
                num_of_products = 0;
                //product_batch_count = 1;
                
                runBatch(); // this function calls the batch processor via AJAX
            } else {
                alert('Error: ' + response.data);
            }
            button.prop('disabled', false).text('Product Sync');
        });
    });

    function runBatch() {
        console.log("Batch running started!");
        $.post(ajaxurl, {
            action: 'run_product_batch_processing'
        }, function(response) {
            console.log(response.data);
            num_of_products = num_of_products + 50;
            // if(product_batch_count == 4) {
            //     alert('All products processed! Total products = ' + num_of_products );
            //     return;
            // } else {
            //     product_batch_count += 1;
            // }
            if (response.data !== 'Done. File deleted.') {
                setTimeout(runBatch, 500); // call next batch after 1 second
            } else {
                alert('All products processed! Total products = ' + num_of_products );
            }
        });
    }

    $('#delete-all-products').on('click', function(e) {
        e.preventDefault();

        if (!confirm("⚠️ Are you sure you want to delete ALL products and categories? This action cannot be undone!")) {
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text('Deleting...');

        $.post(ajaxurl, {
            action: 'delete_all_products'
        }, function(response) {
            if (response.success) {
                $('#delete-result').html('<div style="color:green;">' + response.data + '</div>');
            } else {
                $('#delete-result').html('<div style="color:red;">' + response.data + '</div>');
            }
            button.prop('disabled', false).text('Delete All Products');
        });
    });

});
