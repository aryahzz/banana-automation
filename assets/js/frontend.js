jQuery(document).ready(function($){
    var address = window.benanaAddress || {provinces:{},cities:{}};

    $('.benana-inbox').on('click', '.benana-upload-btn', function(e){
        var link = $(this).data('upload');
        window.location.href = link;
    });

    function renderCities($wrapper) {
        var province = $wrapper.closest('form').find('select[name="user_province_id"]').val();
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

    $('.benana-city-select').each(function(){
        renderCities($(this));
    });

    $(document).on('change', '.benana-availability-form select[name="user_province_id"]', function(){
        $(this).closest('form').find('.benana-city-select').each(function(){
            $(this).data('selected', '');
            renderCities($(this));
        });
    });
    // toggle handled natively توسط چک‌باکس
});
