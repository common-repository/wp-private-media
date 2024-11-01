jQuery(function($) {
    $(document).ready(function(){
        $('.insert-private-media').click(open_private_media_window);
    });

    var customFileFrame;
    function open_private_media_window() {
        if (customFileFrame === undefined) {
                    //Create WP media frame.
                    customFileFrame = wp.media.frames.customHeader = wp.media({
                        //Title of media manager frame
                        title: "Inset Private Media",
                        library: { 
                            wppmedia_private: 1
                        },

                        button: {
                            text: "Insert"
                        },
                        multiple: false
                    });


                    //Sending of a custom parameters to the server
                    customFileFrame.uploader.options.uploader.params.wppmedia_private = 'yes';
                    //callback for selected image
                    wp.Uploader.queue.on( 'reset', function() {
                        if (wp.media.frame.content.get()!==null) {
                           wp.media.frame.content.get().collection.props.set({ignore: (+ new Date())});
                           wp.media.frame.content.get().options.selection.reset();
                        }
                        else{
                           wp.media.frame.library.props.set({ignore: (+ new Date())});
                        }
                    });

                    customFileFrame.on('select', function () {
                        var files = customFileFrame.state().get('selection').toArray();

                        //media-edit.js on('insert')
                        var state = customFileFrame.state();

                        files = files || state.get('selection');

                        if ( ! files )
                            returnfiles
                        $.when.apply( $, files.map( function( attachment ) {
                            var display = state.display( attachment ).toJSON();
                            return wp.media.editor.send.attachment( display, attachment.toJSON() );
                        }, wp.media.editor ) ).done( function() {
                            wp.media.editor.insert( _.toArray( arguments ).join('\n\n') );
                        });

                    });
        }

        customFileFrame.open();
        return false;
    }
});