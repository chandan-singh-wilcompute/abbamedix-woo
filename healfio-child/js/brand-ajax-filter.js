jQuery(document).ready(function ($) {
    $('#brand-filter-form input[type=checkbox]').on('change', function () {
        var data = $('#brand-filter-form').serialize();

        $.ajax({
            url: brand_filter_params.ajax_url,
            data: data + '&action=filter_products_by_brand',
            type: 'GET',
            beforeSend: function () {
                $('#filtered-products').html('<p>Loading products...</p>');
            },
            success: function (response) {
                $('#filtered-products').html(response);
            }
        });
    });
});
