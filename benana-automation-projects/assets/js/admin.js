jQuery(document).ready(function($){
    $('#benana-add-gf').on('click', function(){
        var lastRow = $('#benana-gf-table tbody tr:last');
        var clone = lastRow.clone();
        clone.find('input').val('');
        $('#benana-gf-table tbody').append(clone);
    });
});
