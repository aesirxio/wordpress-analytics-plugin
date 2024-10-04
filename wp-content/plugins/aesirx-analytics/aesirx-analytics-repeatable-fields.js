jQuery(document).ready(function($) {
    $('#aesirx-analytics-add-cookies-row').on('click', function(e) {
        e.preventDefault();
        var row = $('table#aesirx-analytics-blocking-cookies tr:last').clone();
        row.find('input').val('');
        $('table#aesirx-analytics-blocking-cookies').append(row);
    });

    $(document).on('click', '.aesirx-analytics-remove-cookies-row', function(e) {
        e.preventDefault();
        $(this).parents('tr.aesirx-analytics-cookie-row').remove();
    });
});