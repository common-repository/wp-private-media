<?php

/**
 * Add private media button
 */
function wppmedia_add_private_media_button()
{
    if ( !current_user_can( WPPMEDIA_CAP_UPLOAD ) ) {
        return;
    }
    echo  '<style>.wp-core-ui .button.insert-private-media{margin-left: 5px;} .insert-private-media .dashicons {
    display: inline-block;
    width: 16px;
    height: 16px;
    vertical-align: text-top;
    margin-left: 2px;
    margin-top: -2px;
    color: #82878c;
    font-size: 18px;
    line-height: 18px;
}</style><button class="button add_media insert-media insert-private-media"><span class="dashicons dashicons-lock"></span> Add Private Media</button>' ;
}

add_action( 'media_buttons', 'wppmedia_add_private_media_button', 15 );
/**
 * Include js 
 */
function wppmedia_enqueue_media_scripts()
{
    wp_enqueue_script(
        'wppmedia_media_button',
        plugins_url( '/js/private-media-button.js', WPPMEDIA_PLUGIN ),
        array( 'jquery' ),
        WPPMEDIA_VERSION,
        true
    );
}

add_action( 'wp_enqueue_media', 'wppmedia_enqueue_media_scripts' );
/**
 * Set the upload directory based on what was sent through REQUEST.
 */
function wppmedia_wp_handle_upload_prefilter( $file )
{
    
    if ( !empty($_REQUEST['wppmedia_private']) ) {
        add_filter( 'upload_dir', 'wppmedia_uploads_dir' );
        add_action( 'add_attachment', 'wppmedia_set_private_meta' );
        add_filter( 'wp_handle_upload', 'wppmedia_wp_handle_upload', 99 );
    }
    
    return $file;
}

add_filter( 'wp_handle_upload_prefilter', 'wppmedia_wp_handle_upload_prefilter' );
/**
 * Set the post_meta _wppmedia_private to 1.
 */
function wppmedia_set_private_meta( $id )
{
    update_post_meta( $id, '_wppmedia_private', 1 );
}

/**
 * Set a system notification that a file is not private.
 */
function wppmedia_wp_handle_upload( $data )
{
    $response = wp_remote_get( $data['url'] );
    $response_code = wp_remote_retrieve_response_code( $response );
    // if 2xx it's not private, set a notice
    // if ( stripos( $response_code, '2' ) === 0 ) {
    // if it's not a 404, it's not private, set a notice
    
    if ( $response_code !== 404 ) {
        // set notice
        $notice = get_option( WPPMEDIA_OPTION_KEY_NOTICES );
        if ( empty($notice) ) {
            $notice = array();
        }
        $notice['not_private'] = $data['url'];
        update_option( WPPMEDIA_OPTION_KEY_NOTICES, $notice );
    }
    
    return $data;
}

/**
 * Limit query based on the args.
 */
function wppmedia_ajax_query_attachments_args( $query_args )
{
    if ( current_user_can( WPPMEDIA_CAP_UPLOAD ) ) {
        return $query_args;
    }
    $meta_query = array(
        'key' => '_wppmedia_private',
    );
    if ( !current_user_can( WPPMEDIA_CAP_UPLOAD ) ) {
        $meta_query['compare'] = 'NOT EXISTS';
    }
    if ( empty($query_args['meta_query']) ) {
        $query_args['meta_query'] = array();
    }
    $query_args['meta_query'][] = $meta_query;
    return $query_args;
}

add_filter( 'ajax_query_attachments_args', 'wppmedia_ajax_query_attachments_args', 100 );
/**
 * Upload to the private directory.
 */
function wppmedia_uploads_dir( $upload )
{
    if ( !current_user_can( WPPMEDIA_CAP_UPLOAD ) || empty($_REQUEST['wppmedia_private']) ) {
        return $upload;
    }
    $upload['subdir'] = '/' . WPPMEDIA_PRIVATE_UPLOADS_DIR . '/' . date( 'Y' ) . '/' . date( 'm' );
    $upload['path'] = $upload['basedir'] . $upload['subdir'];
    $upload['url'] = $upload['baseurl'] . $upload['subdir'];
    if ( !file_exists( $upload['path'] ) ) {
        wp_mkdir_p( $upload['path'] );
    }
    return $upload;
}

/**
 * Restrict private files in media library
 */
function wppmedia_media_library_join_filter( $join )
{
    global  $pagenow, $wpdb ;
    if ( $pagenow !== 'upload.php' || current_user_can( 'manage_options' ) || current_user_can( WPPMEDIA_CAP_UPLOAD ) ) {
        return $join;
    }
    $join .= "\n    left outer join {$wpdb->postmeta} as wppm1\n      on  ( wppm1.post_id = {$wpdb->posts}.ID )\n      and ( wppm1.meta_key = '_wppmedia_private' )";
    return $join;
}

add_filter( 'posts_join', 'wppmedia_media_library_join_filter' );
/**
 * Restrict private files in media library
 */
function wppmedia_media_library_where_filter( $where )
{
    global  $pagenow, $wpdb ;
    if ( $pagenow !== 'upload.php' || current_user_can( 'manage_options' ) || current_user_can( WPPMEDIA_CAP_UPLOAD ) ) {
        return $where;
    }
    if ( wppmedia_fs()->is_not_paying() ) {
        $where .= " and ( wppm1.meta_value is null ) ";
    }
    return $where;
}

add_filter( 'posts_where', 'wppmedia_media_library_where_filter' );