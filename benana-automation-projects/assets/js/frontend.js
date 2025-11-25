jQuery(document).ready(function($){
    $('.benana-inbox').on('click', '.benana-upload-btn', function(e){
        var link = $(this).data('upload');
        window.location.href = link;
    });
});
