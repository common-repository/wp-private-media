<?php

/*
Plugin Name: WP Private Media
Plugin URI: https://webheadcoder.com/wp-private-media
Description: Allows media to be uploaded and not be accessible to the public.
Author: Webhead LLC.
Author URI: https://webheadcoder.com
Version: 1.0.1
*/
// don't load directly
if ( !defined( 'ABSPATH' ) ) {
    die( '-1' );
}

if ( !function_exists( 'wppmedia_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wppmedia_fs()
    {
        global  $wppmedia_fs ;
        
        if ( !isset( $wppmedia_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $wppmedia_fs = fs_dynamic_init( array(
                'id'             => '2594',
                'slug'           => 'wp-private-media',
                'type'           => 'plugin',
                'public_key'     => 'pk_1a12e6f6519226e83676a4b056bff',
                'is_premium'     => false,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'    => 'wppmedia-options',
                'contact' => false,
                'support' => false,
                'parent'  => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $wppmedia_fs;
    }
    
    // Init Freemius.
    wppmedia_fs();
    // Signal that SDK was initiated.
    do_action( 'wppmedia_fs_loaded' );
    define( 'WPPMEDIA_VERSION', '1.0.1' );
    define( 'WPPMEDIA_PLUGIN', __FILE__ );
    define( 'WPPMEDIA_DIR', __DIR__ );
    define( 'WPPMEDIA_OPTIONS_NAME', 'wppmedia_options' );
    define( 'WPPMEDIA_OPTIONS_PAGE_ID', 'wppmedia-options' );
    define( 'WPPMEDIA_OPTION_KEY_NOTICES', '_wppmedia_admin_notice' );
    define( 'WPPMEDIA_SLUG', 'wppmedia-listen-slug' );
    define( 'WPPMEDIA_PRIVATE_UPLOADS_DIR', 'wp-private-media' );
    define( 'WPPMEDIA_CAP_UPLOAD', 'wppmedia_upload' );
    add_filter(
        'user_has_cap',
        'wppmedia_user_can_edit_attachment_cap',
        10,
        3
    );
    add_filter(
        'user_has_cap',
        'wppmedia_role_to_upload_cap',
        10,
        3
    );
    require_once WPPMEDIA_DIR . '/functions.php';
    require_once WPPMEDIA_DIR . '/upload-media.php';
    if ( is_admin() ) {
        require_once WPPMEDIA_DIR . '/options-page.php';
    }
    register_activation_hook( __FILE__, 'wppmedia_activation' );
    register_deactivation_hook( __FILE__, 'wppmedia_deactivation' );
    function wppmedia_activation()
    {
        wppmedia_write_htaccess( wppmedia_get_private_uploads_directory() );
        $admin_role = get_role( 'administrator' );
        $admin_role->add_cap( WPPMEDIA_CAP_UPLOAD );
    }
    
    function wppmedia_deactivation()
    {
        //remove htaccess rules
        wppmedia_write_htaccess( wppmedia_get_private_uploads_directory(), true );
    }
    
    /**
     * If true, htaccess will not be written to.
     */
    function wppmedia_server_will_redirect()
    {
        return apply_filters( 'wppmedia_simple_redirect', false );
    }
    
    /**
     * Initialize plugin
     */
    function wppmedia_init()
    {
        
        if ( wppmedia_is_wppmedia_url() ) {
            require_once 'ear.php';
            wppmedia_monitor();
            exit;
        }
    
    }
    
    add_action( 'init', 'wppmedia_init', 0 );
    /**
     * Add a menu item to the tools menu.
     */
    function wppmedia_add_menu()
    {
        add_media_page(
            'Add Private',
            'Add Private',
            WPPMEDIA_CAP_UPLOAD,
            'wppmedia_new',
            'wppmedia_new_output'
        );
    }
    
    add_action( 'admin_menu', 'wppmedia_add_menu' );
    function wppmedia_new_output()
    {
        $allowed_roles = wppmedia_option( 'upload_roles', array( 'administrator' ) );
        if ( !array_intersect( $allowed_roles, wp_get_current_user()->roles ) ) {
            wp_die( __( 'Sorry, you are not allowed to upload private files.' ) );
        }
        require_once WPPMEDIA_DIR . '/wppmedia-new.php';
    }
    
    /**
     * Display admin notice, if any is set.
     */
    function wppmedia_admin_notice()
    {
        global  $pagenow ;
        $notice = get_option( WPPMEDIA_OPTION_KEY_NOTICES );
        
        if ( $pagenow == 'options-general.php' && !empty($_GET['page']) && $_GET['page'] == WPPMEDIA_OPTIONS_PAGE_ID ) {
            $notice['visited_setup'] = true;
            update_option( WPPMEDIA_OPTION_KEY_NOTICES, $notice );
        }
        
        
        if ( !empty($notice['not_private']) ) {
            ?>
    <div class="notice notice-error is-dismissible">
        <p>Please check the following URL to make sure it is private for non-logged in users.  It appears to be viewable to the public (it does not return a HTTP status code of 403).<br><a href="<?php 
            echo  esc_url( $notice['not_private'] ) ;
            ?>" target="_blank"><?php 
            echo  esc_html( $notice['not_private'] ) ;
            ?></a>.  Please view the <a href="<?php 
            echo  admin_url( 'options-general.php?page=' . WPPMEDIA_OPTIONS_PAGE_ID ) ;
            ?>">WP Private Media Settings</a> page to see possible setup errors.</p>
    </div>
<?php 
            $notice['not_private'] = false;
            update_option( WPPMEDIA_OPTION_KEY_NOTICES, $notice );
        }
        
        
        if ( empty($notice['visited_setup']) ) {
            ?>
    <div class="notice notice-error">
        <p>Please visit the <a href="<?php 
            echo  admin_url( 'options-general.php?page=' . WPPMEDIA_OPTIONS_PAGE_ID ) ;
            ?>">WP Private Media setup page</a> to make sure your files are actually private!</p>
    </div>
<?php 
        }
    
    }
    
    add_action( 'admin_notices', 'wppmedia_admin_notice' );
    /**
     * Pop a confirm message before deleting or deactivating
     */
    function wppmedia_confirm_uninstall()
    {
        global  $pagenow ;
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }
        wp_enqueue_style( 'wp-pointer' );
        wp_enqueue_script( 'wp-pointer' );
        ?>
    <style>
    .wppmedia-pointer .wp-pointer-content h3:before {
        content: "\f115";
        color: #DC3232;
    }
    .wppmedia-pointer .wp-pointer-content h3 {
        background: #DC3232;
        border-color: #DC3232;
        font-size: 15px;
    }

    </style>
    <script type="text/javascript">
    (function($){
        var wppmedia_warning = function(e){
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            var deactivation_link = $(this);
            redirectLink = this.href;
            $(this).pointer({
                pointerClass: 'wppmedia-pointer',
                content: '<h3>Make all media public?</h3><p>Are you sure you want to make all the documents, images, and other media in <br><strong><?php 
        echo  wp_make_link_relative( wppmedia_get_private_uploads_url() ) ;
        ?></strong> accessible to the public?</p><p><a id="this" class="button wppmedia-pointer-continue" href="#">Yes, Deactivate</a> &nbsp; <a id="everything" class="wppmedia-dismiss-pointer button button-primary" href="#">Cancel</a></p>',
                position: {
                    edge: 'left',
                    align: 'left'
                },
                close: function() {
                    //
                }
            }).pointer('open');
            $("body").on("click", ".wppmedia-pointer a.wppmedia-pointer-continue", function(e){
                e.preventDefault();
                deactivation_link.off('click', wppmedia_warning);
                deactivation_link.pointer('close');
                deactivation_link.trigger('click');
            });
            $("body").on("click", ".wppmedia-pointer a.wppmedia-dismiss-pointer", function(e){
                e.preventDefault();
                $(this).closest(".wp-pointer-content").find(".wp-pointer-buttons a.close").trigger('click');               
            });

            return false;
        }
        $('table.plugins tr[data-slug="wp-private-media"] .deactivate > a').on('click', wppmedia_warning);
    })(jQuery);
    </script><?php 
    }
    
    add_action( 'admin_footer', 'wppmedia_confirm_uninstall', 9 );
}
