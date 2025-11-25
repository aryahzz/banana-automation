jQuery(document).ready(function($){
    var $table    = $('#benana-gf-table');
    var $template = $table.find('.benana-gf-template');

    function addRow() {
        var newKey = 'row_' + Date.now();
        var $clone = $template.clone();

        $clone.removeClass('benana-gf-template').attr('style', '').attr('data-row-key', newKey);
        $clone.find('input').prop('disabled', false).each(function(){
            var name = $(this).attr('name');
            name = name.replace(/\[gravity_forms\]\[[^\]]+\]/, '[gravity_forms][' + newKey + ']');
            $(this).attr('name', name).val('');
        });
        $clone.find('.benana-remove-gf').prop('disabled', false);

        $table.find('tbody').append($clone);
    }

    if ( $table.find('tbody tr').length === 1 ) {
        addRow();
    }

    $('#benana-add-gf').on('click', function(){
        addRow();
    });

    $table.on('click', '.benana-remove-gf', function(){
        var $rows = $table.find('tbody tr').not('.benana-gf-template');

        if ( $rows.length > 1 ) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input').val('');
        }
    });
});
