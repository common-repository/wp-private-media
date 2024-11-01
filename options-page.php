<?php

/*********************************
 * Options page
 *********************************/
// don't load directly
if ( !defined( 'ABSPATH' ) ) {
    die( '-1' );
}
/**
 *  Add menu page
 */
function wppmedia_options_add_page()
{
    $wppmedia_hook = add_options_page(
        'WP Private Media',
        // Page title
        'WP Private Media',
        // Label in sub-menu
        'manage_options',
        // capability
        'wppmedia-options',
        // page identifier
        'wppmedia_options_do_page'
    );
    // call back function name
    add_action( "admin_enqueue_scripts-" . $wppmedia_hook, 'wppmedia_admin_scripts' );
}

add_action( 'admin_menu', 'wppmedia_options_add_page' );
/**
 * Init plugin options to white list our options
 */
function wppmedia_options_init()
{
    global  $plugins_dir_name ;
    $plugins_dir_name = basename( dirname( plugin_dir_path( __FILE__ ) ) );
    register_setting( 'wppmedia_options_options', 'wppmedia_options', 'wppmedia_options_validate' );
    if ( wppmedia_server_will_redirect() && isset( $_GET['page'] ) && $_GET['page'] == 'wppmedia-options' ) {
        add_action( 'admin_notices', 'wppmedia_admin_notice_htaccess' );
    }
}

add_action( 'admin_init', 'wppmedia_options_init' );
function wppmedia_admin_notice_htaccess()
{
    echo  '<div class="update-nag" id="messages"><p>WP Private Media will not write to the .htaccess file.  Redirect rules will need to be manually setup in the server configuration to redirect to ' . wppmedia_url() . '?<i>protectedFilename</i>' . '. </p>' ;
    $options = get_option( 'wppmedia_options' );
    
    if ( !empty($options['directories']) && is_array( $options['directories'] ) ) {
        $first_dir = current( $options['directories'] );
        $uploads_dir = wp_uploads_dir();
        $abspath = $uploads_dir['base_dir'];
        $relative_url = str_replace( $abspath, '', $first_dir );
        echo  '<p>For example, at WPEngine on the Redirect rules screen, the Source regex would be: <br><strong>' . trailingslashit( $relative_url ) . '(.*)</strong></p>' ;
        echo  '<p>and the Destination would be: <br><strong>' . wppmedia_url() . '?$1' . '</strong></p>' ;
        echo  '<p>You would need a rule for each selected directory or use some fancy regex to catch everything.</p>' ;
    }
    
    echo  '</div>' ;
}

/**
 * Recursive function to store all subdirectories of $directory.
 * originally from http://pastebin.com/qvyF1VWX
 */
function wppmedia_get_all_subdirectories( $directory )
{
    global  $plugins_dir_name ;
    $dirs = array_map( 'trailingslashit', preg_grep( '#/' . $plugins_dir_name . '|mu-plugins#', glob( $directory . '*', GLOB_ONLYDIR ), PREG_GREP_INVERT ) );
    //http://stackoverflow.com/questions/1877524/does-glob-have-negation
    $dir_array = array_fill_keys( $dirs, array() );
    foreach ( $dir_array as $dir => $subdirs ) {
        $dir_array[$dir] = array_merge( $subdirs, wppmedia_get_all_subdirectories( $dir ) );
    }
    return $dir_array;
}

/**
 * Draw the menu page itself
 */
function wppmedia_options_do_page()
{
    global  $is_nginx ;
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    ?>
    <div class="wrap">

            <div class="wppmedia-header">
                <div class="wppmedia-description">
                <h2>WP Private Media</h2>
                    <p class="intro">
                        WP Private Media lets you upload media to WordPress and restrict access to logged in users.
                    </p>
                </div>
            </div>
            <div class="clear"></div>
            <hr>
            <h3>Status</h3>
<?php 
    $home_path = wppmedia_get_private_uploads_directory();
    $iis7_permalinks = iis7_supports_permalinks();
    $htaccess_data = wppmedia_write_htaccess( $home_path, false, true );
    $is_wpengine = false;
    if ( function_exists( 'is_wpe' ) || function_exists( 'is_wpe_snapshot' ) ) {
        $is_wpengine = true;
    }
    
    if ( $iis7_permalinks ) {
        ?>
<p><?php 
        printf( __( 'This plugin currently does not support IIS servers.  The code below can be pasted into a file called <code>web.config</code> in the %s directory.  However, we cannot offer any help with this as we do not have any way to test on IIS servers.' ), $home_path );
        ?></p>
    <p><textarea rows="18" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php 
        echo  esc_textarea( $htaccess_data['htaccess_content'] ) ;
        ?></textarea></p>
<?php 
    } elseif ( $is_nginx || $is_wpengine ) {
        ?>
    <p><?php 
        printf( __( 'This plugin currently does not support nginx servers.  The code below can be used as reference to create your own redirects for the %s directory on nginx.' ), $home_path );
        ?></p>
    <?php 
        
        if ( $is_wpengine ) {
            ?>
    <p><?php 
            printf( __( 'You will need to <a href="https://wpengine.com/support/redirect/" target="_blank">set up redirects in WPEngine\'s platform</a>.' ), $home_path );
            ?></p>
    <?php 
        } else {
            ?>
    <p><?php 
            _e( 'Please see <a href="https://codex.wordpress.org/Nginx">Documentation on Nginx configuration</a> to set up a redirect.' );
            ?></p>
    <?php 
        }
        
        ?>
    <p>To protect your private files, make sure the following extensions in the <?php 
        echo  $home_path ;
        ?> directory will redirect to this url: <?php 
        echo  wppmedia_url() ;
        ?>?f=$1 where $1 is the private file's name.</p>
    <p><textarea rows="5" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php 
        echo  esc_textarea( implode( '|', $htaccess_data['extensions'] ) ) ;
        ?></textarea></p>
    
<?php 
    } else {
        if ( !file_exists( $home_path ) ) {
            wp_mkdir_p( $home_path );
        }
        
        if ( !file_exists( $home_path . '.htaccess' ) && is_writable( $home_path ) || is_writable( $home_path . '.htaccess' ) ) {
            $writable = true;
        } else {
            $writable = false;
        }
        
        
        if ( !$using_index_permalinks && !$writable ) {
            ?>
<p><?php 
            _e( 'If your <code>.htaccess</code> file were <a href="https://codex.wordpress.org/Changing_File_Permissions">writable</a>, we could do this automatically, but it isn&#8217;t so these are the mod_rewrite rules you should have in your <code>.htaccess</code> file. Click in the field and press <kbd>CTRL + a</kbd> to select all.' );
            ?></p>
    <p><textarea rows="16" class="large-text readonly" name="rules" id="rules" readonly="readonly"><?php 
            echo  esc_textarea( $htaccess_data['htaccess_content'] ) ;
            ?></textarea></p>
    <?php 
        } else {
            ?>
            <p>
                All good!  Your .htaccess file looks good.
            </p>

    <?php 
        }
    
    }
    
    ?>

        <hr>
        <form method="post" action="options.php">
            <?php 
    settings_fields( 'wppmedia_options_options' );
    ?>

            <!-- who can upload? -->
            <h3>Roles Allowed to Upload Private Media</h3>
            <p>Roles checked here will be able to see the "Add Private Media" link in the menu and in the editor and will be able to see private uploads in the Media Library.  
                <?php 
    ?>
            </p>
            <?php 
    $options = get_option( 'wppmedia_options' );
    $roles = wppmedia_roles();
    $upload_roles = ( !empty($options['upload_roles']) ? $options['upload_roles'] : array() );
    foreach ( $roles as $slug => $role ) {
        ?>
                <label style="display:block;" class="wppmedia-checkbox-row">
                    <input style="margin-top:0; margin-right; 2px;" type="checkbox" name="<?php 
        echo  WPPMEDIA_OPTIONS_NAME ;
        ?>[upload_roles][]" value="<?php 
        echo  esc_attr( $slug ) ;
        ?>" <?php 
        checked( in_array( $slug, $upload_roles ) || $slug == 'administrator', true );
        ?> ><?php 
        echo  $role['name'] ;
        ?> </label>
            <?php 
    }
    ?>

            <p>&nbsp;</p>


            <h3>Roles Allowed to View Private Media</h3>
            <p>Select the default roles that are able to view private media.  If none are selected any logged in user will be able to view the private media.  Each media file can have its own permissions, overriding this.  Administrator role will always have access.</p>
            <p>
<?php 
    ?>

<?php 
    if ( wppmedia_fs()->is_not_paying() ) {
        ?>
            <p><span class="dashicons dashicons-lock"></span> Pro version only <a href="https://webheadcoder.com/wp-private-media/" target="_blank">Learn More</a></p>
<?php 
    }
    ?>
            <p>&nbsp;</p>


            <h3>Track Private Media downloads in Google Analytics</h3>
            <p>Enter your Google Analytics Tracking ID and everytime* a private media file is accessed an Event will be sent to Google Analytics with the following:
                <br>The page the event occurred will be the private media file.
                <br><strong>Category:</strong> Private Media
                <br><strong>Action:</strong> Download
            </p>
            <p class="description">*Only users NOT allowed to Upload private media will be tracked.  This prevents tracking when accessed in the Media Library.</p>
            <p>
<?php 
    ?>

<?php 
    if ( wppmedia_fs()->is_not_paying() ) {
        ?>
            <p><span class="dashicons dashicons-lock"></span> Pro version only <a href="https://webheadcoder.com/wp-private-media/" target="_blank">Learn More</a></p>
<?php 
    }
    ?>
            <p>&nbsp;</p>

            <h3>Retry Writing to .htaccess file</h3>

            <p>
                <label><input type="checkbox" name="wppmedia_rewrite" value="1">Try to rewrite .htaccess file</label>
            </p>

            <p class="submit">
            <input type="submit" class="button-primary" value="Save" />
            </p>

        </form>
    </div>



    <?php 
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function wppmedia_options_validate( $input )
{
    global  $wp_settings_errors ;
    
    if ( empty($wp_settings_errors) && !empty($_REQUEST['wppmedia_rewrite']) ) {
        // $abspath = untrailingslashit( ABSPATH );
        $home_path = get_home_path();
        //add .htaccess files to all directories
        $directory = wppmedia_get_private_uploads_directory();
        
        if ( !wppmedia_is_writable( $directory ) && !wppmedia_server_will_redirect() ) {
            $dir_display = str_replace( $home_path, '', $directory );
            add_settings_error( 'wppmedia-htaccess-writeable', esc_attr( $directory ), 'Your .htaccess file in ' . $dir_display . ' is not writable.  This plugin cannot work without this file being writable.' );
        }
        
        if ( empty($wp_settings_errors) ) {
            wppmedia_write_htaccess( $directory );
        }
    }
    
    if ( empty($input['upload_roles']) ) {
        $input['upload_roles'] = array();
    }
    $input['upload_roles'] = array_merge( $input['upload_roles'], array( 'administrator' ) );
    return $input;
}

/**
 * Enqueue Scripts
 */
function wppmedia_admin_scripts()
{
    do_action( 'wppmedia_admin_scripts' );
}

/**
 * Enqueue scripts for the admin side.
 */
function wppmedia_enqueue_scripts( $hook )
{
    if ( 'settings_page_wppmedia-options' != $hook ) {
        return;
    }
    wp_enqueue_script(
        'wppmedia-options',
        plugins_url( '/js/options.js', WPPMEDIA_PLUGIN ),
        array( 'jquery' ),
        WPPMEDIA_VERSION
    );
    wp_enqueue_style(
        'wppmedia-options',
        plugins_url( '/css/options.css', WPPMEDIA_PLUGIN ),
        array(),
        WPPMEDIA_VERSION
    );
    wp_enqueue_style( 'font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
}

add_action( 'admin_enqueue_scripts', 'wppmedia_enqueue_scripts' );
/**
 * Return true if Google Analytics Measurement Protocol WP API is active.
 */
function wppmedia_is_gamp_active()
{
    //gamp is part of this plugin for now.
    return true;
}
