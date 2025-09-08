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
    let total_products_estimate = 0;
    
    $('#product_fetch_and_sync').on('click', function (e) {
        e.preventDefault();

        if (!confirm("⚠️ Are you sure, you want to start product sync?")) {
            return;
        }

        var button = $(this);
        button.prop('disabled', true).text('Syncing...');
        
        // Show progress indicator
        $('#sync-progress').show();
        updateSyncProgress('🔄 Fetching products from API...', 10);

        $.post(ajaxurl, {
            action: 'fetch_and_store_product_data'
        }, function(response) {
            if (response.success) {
                //console.log(response.data);
                console.log('Data saved. Starting batch processing...');
                
                // Update button text to show batch processing phase
                button.text('Processing batches...');
                
                // Get actual total products from server response
                if (response.data && response.data.total_products) {
                    total_products_estimate = response.data.total_products;
                } else {
                    total_products_estimate = 1000; // Default fallback
                }
                
                updateSyncProgress(`✅ Products fetched successfully (${total_products_estimate} products). Starting batch processing...`, 20);
                
                num_of_products = 0;
                //product_batch_count = 1;
                
                runBatch(button); // Pass button reference to runBatch function
            } else {
                alert('Error: ' + response.data);
                // Only re-enable button if there was an error
                button.prop('disabled', false).text('Product Sync');
                hideSyncProgress();
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert('Error occurred during file writing phase. Please try again.');
            button.prop('disabled', false).text('Product Sync');
            hideSyncProgress();
        });
    });

    function runBatch(button) {
        console.log("Batch running started!");
        
        // Update button text to show current progress
        const batchNumber = Math.floor(num_of_products / 50) + 1;
        button.text(`Processing batch ${batchNumber} (${num_of_products} products processed)...`);
        
        // Update progress indicator (calculate after adding current batch)
        const nextBatchProgress = Math.min(95, 20 + ((num_of_products + Math.min(50, total_products_estimate - num_of_products)) / total_products_estimate) * 75);
        updateSyncProgress(
            `🔄 Processing batch ${batchNumber}...`, 
            nextBatchProgress, 
            `${num_of_products} products processed so far`
        );
        
        $.post(ajaxurl, {
            action: 'run_product_batch_processing'
        }, function(response) {
            console.log(response.data);
            
            // Check if we have structured response data
            if (response.data && typeof response.data === 'object') {
                // Use the structured response
                const batchProcessed = response.data.processed || 0;
                const remaining = response.data.remaining || 0;
                
                num_of_products = num_of_products + batchProcessed;
                
                console.log(`Batch processed: ${batchProcessed}, Total so far: ${num_of_products}, Remaining: ${remaining}`);
                
                // Check if processing is complete
                if (response.data.completed || remaining === 0) {
                    // All batches completed - re-enable button
                    button.prop('disabled', false).text('Product Sync');
                    updateSyncProgress('✅ Sync completed successfully!', 100, `${num_of_products} products processed`);
                    
                    // Hide progress after 3 seconds
                    setTimeout(function() {
                        hideSyncProgress();
                    }, 3000);
                    
                    alert('✅ All products processed successfully! Total products = ' + num_of_products );
                } else {
                    // Continue processing next batch
                    setTimeout(function() {
                        runBatch(button);
                    }, 250); // call next batch after 500ms
                }
            } else {
                // Fallback for old string-based response format
                let actualBatchCount = 0;
                if (response.data && typeof response.data === 'string' && response.data.includes('Processed batch of')) {
                    const matches = response.data.match(/Processed batch of (\d+)/);
                    if (matches && matches[1]) {
                        actualBatchCount = parseInt(matches[1]);
                        num_of_products = num_of_products + actualBatchCount;
                    }
                }
                
                if (response.data !== 'Done. File deleted.') {
                    // Continue processing next batch
                    setTimeout(function() {
                        runBatch(button);
                    }, 500); // call next batch after 500ms
                } else {
                    // All batches completed - re-enable button
                    button.prop('disabled', false).text('Product Sync');
                    updateSyncProgress('✅ Sync completed successfully!', 100, `${num_of_products} products processed`);
                    
                    // Hide progress after 3 seconds
                    setTimeout(function() {
                        hideSyncProgress();
                    }, 3000);
                    
                    alert('✅ All products processed successfully! Total products = ' + num_of_products );
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('Batch processing error:', error);
            button.prop('disabled', false).text('Product Sync');
            updateSyncProgress('❌ Error during batch processing', 0, 'Some products may not have been processed');
            
            setTimeout(function() {
                hideSyncProgress();
            }, 5000);
            
            alert('❌ Error occurred during batch processing. Some products may not have been processed.');
        });
    }
    
    // Helper functions for progress indicator
    function updateSyncProgress(status, percentage, details = '') {
        $('#sync-status').text(status);
        $('#sync-progress-bar').css('width', percentage + '%');
        $('#sync-details').text(details);
    }
    
    function hideSyncProgress() {
        $('#sync-progress').hide();
        $('#sync-progress-bar').css('width', '0%');
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
