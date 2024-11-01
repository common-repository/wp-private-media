<?php

/**
 * Return all roles
 */
function wppmedia_roles()
{
    return apply_filters( 'wppmedia_roles', get_editable_roles() );
}

/**
 * Return true if the request is part of this plugin.
 */
function wppmedia_is_wppmedia_url()
{
    $current_relative_url = add_query_arg();
    $is_wppmedia_url = strpos( $current_relative_url, wp_make_link_relative( wppmedia_url() ) ) === 0;
    return apply_filters( 'wppmedia_is_wppmedia_url', $is_wppmedia_url );
}

/**
 * The url files will be redirected to.
 */
function wppmedia_url()
{
    return apply_filters( 'wppmedia_url', trailingslashit( get_home_url( null, WPPMEDIA_SLUG ) ) );
}

/**
 * Return the private media uploads directory.
 */
function wppmedia_get_private_uploads_directory()
{
    $upload_dir = wp_upload_dir();
    return apply_filters( 'wppmedia_private_uploads_directory', trailingslashit( trailingslashit( $upload_dir['basedir'] ) . WPPMEDIA_PRIVATE_UPLOADS_DIR ), $upload_dir );
}

/**
 * Return the private media uploads url.
 */
function wppmedia_get_private_uploads_url()
{
    $upload_dir = wp_upload_dir();
    return apply_filters( 'wppmedia_private_uploads_url', trailingslashit( trailingslashit( $upload_dir['baseurl'] ) . WPPMEDIA_PRIVATE_UPLOADS_DIR ), $upload_dir );
}

/**
 * Write the htaccess files for the chosen directories.
 * If return_only is true, will return the content and not write.
 */
function wppmedia_write_htaccess( $directory, $remove = false, $return_only = false )
{
    $mime_types = wppmedia_supported_mimetypes();
    $extensions = array_keys( $mime_types );
    $wppmedia_url = wppmedia_url();
    
    if ( iis7_supports_permalinks() ) {
        //rewrite not working with url, do redirect for now.
        $CONTENT_TEMPLATE = '
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="WP Content Listener rules" stopProcessing="true">
                    <match url="^(.*\\.(' . implode( '|', $extensions ) . '))$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" />
                    </conditions>
                    <action type="Redirect" url="' . $wppmedia_url . '?f={R:0}" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
';
        $start_content = "\n<!-- BEGIN WPPMEDIA -->";
        $end_content = "<!-- END WPPMEDIA -->\n";
    } else {
        $CONTENT_TEMPLATE = "\n\n<IfModule mod_rewrite.c>\n  RewriteEngine On\n  RewriteBase /\n  RewriteCond %{REQUEST_FILENAME} -f\n  RewriteRule ^(.*\\.(" . implode( '|', $extensions ) . "))\$ " . $wppmedia_url . "?f=\$1 [L]\n</IfModule>\n\n";
        $start_content = "\n### BEGIN WPPMEDIA ###";
        $end_content = "### END WPPMEDIA ###\n";
    }
    
    //write .htaccess
    $matches = array();
    $file = trailingslashit( $directory ) . '.htaccess';
    $current = '';
    $remove = $remove || empty($directory);
    if ( !$remove && !file_exists( $directory ) ) {
        wp_mkdir_p( $directory );
    }
    
    if ( file_exists( $file ) ) {
        $current = file_get_contents( $file );
        if ( $current === FALSE ) {
            $current = '';
        }
    }
    
    
    if ( !$remove ) {
        preg_match( '/(' . $start_content . ')(.*)(' . $end_content . ')/si', $current, $matches );
        $new_content = $start_content . $CONTENT_TEMPLATE . $end_content;
        //clean it first
        if ( !empty($matches) ) {
            $current = preg_replace( '/(' . $start_content . ')(.*)(' . $end_content . ')/si', '', $current );
        }
        $current .= $new_content;
        if ( $return_only ) {
            return array(
                'extensions'       => $extensions,
                'htaccess_content' => $current,
            );
        }
        // Write the contents back to the file
        
        if ( wppmedia_is_writable( $directory ) ) {
            file_put_contents( $file, $current );
        } else {
            return false;
        }
    
    } else {
        
        if ( !empty($current) ) {
            //remove previous directory
            preg_match( '/(' . $start_content . ')(.*)(' . $end_content . ')/si', $current, $matches );
            
            if ( !empty($matches) ) {
                $current = preg_replace( '/(' . $start_content . ')(.*)(' . $end_content . ')/si', '', $current );
                
                if ( empty($current) ) {
                    //only had our stuff, so remove the file.
                    unlink( $file );
                } else {
                    // Write the contents back to the file
                    
                    if ( wppmedia_is_writable( $file ) ) {
                        file_put_contents( $file, $current );
                    } else {
                        // add error
                        return false;
                    }
                
                }
            
            }
        
        }
    
    }
    
    return true;
}

/**
 * Return true if the file or directory is writable.
 */
function wppmedia_is_writable( $path, $filename = '.htaccess' )
{
    return (!file_exists( $path . $filename ) && is_writable( $path ) || is_writable( $path . $filename )) && !wppmedia_server_will_redirect();
}

/**
 * List of supported mimetypes.
 */
function wppmedia_supported_mimetypes()
{
    return apply_filters( 'wppmedia_supported_mime_types', wp_get_mime_types() );
}

/**
 * Get option
 */
function wppmedia_option( $name, $default = '', $options = false )
{
    if ( empty($options) ) {
        $options = get_option( WPPMEDIA_OPTIONS_NAME );
    }
    
    if ( !empty($options) && !empty($options[$name]) ) {
        $ret = $options[$name];
    } else {
        $ret = $default;
    }
    
    return $ret;
}

/**
 * Restrict private files when editing
 */
function wppmedia_user_can_edit_attachment_cap( $allcaps, $cap, $args )
{
    // Bail out if we're not asking about a post:
    if ( 'edit_post' != $args[0] && 'delete_post' != $args[0] ) {
        return $allcaps;
    }
    $user_id = $args[1];
    // Bail out if the user is the post author:
    $post = get_post( $args[2] );
    if ( $user_id == $post->post_author ) {
        return $allcaps;
    }
    $user = new WP_User( $user_id );
    $user_roles = $user->roles;
    $allowed_roles = wppmedia_option( 'upload_roles', array( 'administrator' ) );
    $allcaps[$cap[0]] = array_intersect( $allowed_roles, $user_roles );
    return $allcaps;
}

/**
 * Allow roles the upload cap if it's set.
 */
function wppmedia_role_to_upload_cap( $allcaps, $cap, $args )
{
    // Bail out if we're not asking about the wppmedia upload cap:
    if ( WPPMEDIA_CAP_UPLOAD != $args[0] ) {
        return $allcaps;
    }
    $user_id = $args[1];
    $user = new WP_User( $user_id );
    $user_roles = $user->roles;
    $allowed_roles = wppmedia_option( 'upload_roles', array( 'administrator' ) );
    $allcaps[$cap[0]] = array_intersect( $allowed_roles, $user_roles );
    return $allcaps;
}
