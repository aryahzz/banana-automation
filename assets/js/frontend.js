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

    function initDatePicker() {
        if ( window.jalaliDatepicker && $('.benana-jdp-input').length ) {
            jalaliDatepicker.startWatch({
                selector: '.benana-jdp-input',
                time: true,
                hasSecond: false,
                autoHide: true,
                autoShow: true,
                persianDigits: true
            });
        }
    }

    $(document).on('change', '.benana-availability-form select[name="user_province_id"]', function(){
        $(this).closest('form').find('.benana-city-select').each(function(){
            $(this).data('selected', '');
            renderCities($(this));
        });
    });

    $(document).on('change', '.benana-availability-form input[name="user_is_active"]', function(){
        if ( $(this).val() === '0' ) {
            $(this).closest('form').find('.benana-inactive-options').slideDown();
        } else {
            $(this).closest('form').find('.benana-inactive-options').slideUp();
        }
    });

    initDatePicker();
});
