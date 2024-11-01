<?php

// don't load directly
if ( !defined( 'ABSPATH' ) ) {
    die( '-1' );
}
/**
 * Deterimines if the folder is in our list to listen to.  if so, do the action.
 */
function wppmedia_monitor()
{
    $dir = wppmedia_get_private_uploads_directory();
    $path_to_file = ( wppmedia_server_will_redirect() ? $_SERVER['QUERY_STRING'] : $_GET['f'] );
    $filename = realpath( $dir . $path_to_file );
    if ( strpos( $filename, realpath( $dir ) ) === 0 ) {
        
        if ( file_exists( $filename ) ) {
            $url = (( is_ssl() ? 'https://' : 'http://' )) . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            
            if ( apply_filters( 'wppmedia_access_filter', is_user_logged_in(), $path_to_file ) ) {
                do_action( 'wppmedia_before_file_download', $filename, $url );
                wppmedia_get_file( $filename );
            } else {
                do_action( 'wppmedia_no_access', $filename, $url );
                global  $wp_query ;
                $wp_query->set_404();
                status_header( 404 );
                include get_query_template( '404' );
                exit;
            }
        
        }
    
    }
    do_action( 'wppmedia_not_monitoring', $filename, $url );
    global  $wp_query ;
    $wp_query->set_404();
    status_header( 404 );
    include get_query_template( '404' );
    exit;
}

/**
 * Shows a file.
 */
function wppmedia_get_file( $filename )
{
    $file_time = filemtime( $filename );
    $send_304 = false;
    
    if ( php_sapi_name() == 'apache' ) {
        // if our web server is apache
        // we get check HTTP
        // If-Modified-Since header
        // and do not send image
        // if there is a cached version
        $ar = apache_request_headers();
        if ( isset( $ar['If-Modified-Since'] ) && $ar['If-Modified-Since'] != '' && strtotime( $ar['If-Modified-Since'] ) >= $file_time ) {
            // and grater than file_time
            $send_304 = true;
        }
    }
    
    
    if ( $send_304 ) {
        // Sending 304 response to browser
        // "Browser, your cached version of image is OK
        // we're not sending anything new to you"
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $file_time ) . ' GMT', true, 304 );
    } else {
        // outputing Last-Modified header
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $file_time ) . ' GMT', true, 200 );
        // Set expiration time +1 year
        // We do not have any photo re-uploading
        // so, browser may cache this photo for quite a long time
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', $file_time + 86400 * 365 ) . ' GMT', true, 200 );
        // outputing HTTP headers
        header( 'Content-Length: ' . filesize( $filename ) );
        //Not all php setups support this, eg. dreamhost
        //$finfo = finfo_open(FILEINFO_MIME);
        //$ftype = finfo_file($finfo, $filename);
        //finfo_close($finfo);
        //$ftype = mime_content_type($filename);
        $ftype = wppmedia_mime_type( $filename );
        if ( empty($ftype) ) {
            exit;
        }
        header( "Content-type: " . $ftype );
        //$isImage = strpos($ftype,'image/') != '';
        //if (!$isImage){
        //  header('Content-Disposition: attachment; filename="'.$_SERVER['REQUEST_URI'].'"');
        //  header('Content-Transfer-Encoding: binary');
        //}
        ob_clean();
        flush();
        readfile( $filename );
        exit;
    }

}

function wppmedia_mime_type( $filename )
{
    $extension = wppmedia_get_extension( $filename );
    if ( !$extension ) {
        return false;
    }
    $mime_types = wppmedia_supported_mimetypes();
    $extensions = array_keys( $mime_types );
    foreach ( $extensions as $_extension ) {
        if ( preg_match( "/{$extension}/i", $_extension ) ) {
            return $mime_types[$_extension];
        }
    }
    return '';
}

function wppmedia_get_extension( $filename )
{
    $start = strrpos( $filename, '/' );
    if ( $start == '' ) {
        //no / found in file name, not in a folder
        $start = 0;
    }
    $justfile = substr( $filename, $start );
    $pos = strrpos( $justfile, '.' );
    return ( $pos != '' ? substr( $justfile, $pos + 1 ) : '' );
}
