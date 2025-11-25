jQuery(document).ready(function($){
    var address = window.benanaAddress || {provinces:{},cities:{}};

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

    function renderCities($wrapper) {
        var province = $('#user_province_id, .benana-availability-form select[name="user_province_id"]').val();
        var selected = ($wrapper.data('selected') || '').toString().split(',');
        var list     = address.cities[province] || {};
        var $grid    = $wrapper.find('.benana-city-grid');
        $grid.empty();

        $.each(list, function(id, name){
            var isChecked = selected.indexOf(id) !== -1;
            var inputName = $wrapper.data('field') + '[]';
            var item = $('<label class="benana-city-item"></label>');
            var checkbox = $('<input type="checkbox" />').attr('name', inputName).attr('value', id);
            if (isChecked) {
                checkbox.prop('checked', true);
            }
            item.append(checkbox).append($('<span></span>').text(name));
            $grid.append(item);
        });
    }

    function initCitySelectors() {
        $('.benana-city-select').each(function(){
            renderCities($(this));
        });
    }

    $('#user_province_id, .benana-availability-form select[name="user_province_id"]').on('change', function(){
        $(this).closest('td, .benana-availability-form').find('.benana-city-select').each(function(){
            $(this).data('selected', '');
            renderCities($(this));
        });
    });

    initCitySelectors();

    $('input[name="user_is_active"]').on('change', function(){
        if ( $(this).val() === '0' ) {
            $('.benana-inactive-options').slideDown();
        } else {
            $('.benana-inactive-options').slideUp();
        }
    });
});
