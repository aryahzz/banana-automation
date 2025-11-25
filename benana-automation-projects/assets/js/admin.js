jQuery(document).ready(function($){
    $('#benana-add-gf').on('click', function(){
        var lastRow = $('#benana-gf-table tbody tr:last');
        var clone = lastRow.clone();
        var newKey = 'row_' + Date.now();

        clone.attr('data-row-key', newKey);
        clone.find('input').each(function(){
            var name = $(this).attr('name');
            name = name.replace(/\[gravity_forms\]\[[^\]]+\]/, '[gravity_forms][' + newKey + ']');
            $(this).attr('name', name).val('');
        });

        $('#benana-gf-table tbody').append(clone);
    });

    $('#benana-gf-table').on('click', '.benana-remove-gf', function(){
        var rows = $('#benana-gf-table tbody tr');
        if ( rows.length > 1 ) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input').val('');
        }
    });
});
