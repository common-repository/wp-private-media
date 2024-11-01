(function($){
    var file_frame;

    $('.media-uploader-link').on('click', function( e ){

        e.preventDefault();

        button = $(this);

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
        file_frame.open();
        return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
        title: jQuery( this ).data( 'uploader_title' ),
        button: {
          text: jQuery( this ).data( 'uploader_button_text' ),
        },
        multiple: false  // Set to true to allow multiple files to be selected
        });

        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
        // We set multiple to false so only get one image from the uploader
        attachment = file_frame.state().get('selection').first().toJSON();
        var id = button.attr('id').replace('_button', '');
        $("#" + id).val(attachment.url);
        $("#" + id + "_thumb").html('<img src="' + attachment.url + '" class="logo_thumb" />').show();
        $("#" + id + "_remove").show();
        });

        // Finally, open the modal
        file_frame.open();
    });

    $(".uploader").on('click', '.remove-link', function(e){
        e.preventDefault();

        var button = $(this);
        var id = button.attr('id').replace('_remove', '');
        var img = $("#" + id + "_thumb img");
        img.attr('src', img.data('wp_logo'));
        $("#" + id).val('');
        button.hide();
    });

})(jQuery);
