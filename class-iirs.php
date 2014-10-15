<?php
// Class definition for the IIRS project
// conforming to Code Standards in:
// https:// make.wordpress.org/core/handbook/coding-standards/php/
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// --------------------------------------------------------------------------
// add filters directly here to make sure they are all run
add_action( 'wp',               array( IIRS_PLUGIN_NAME, 'wp' ) );
add_filter( 'plugin_action_links', array( IIRS_PLUGIN_NAME, 'plugin_action_links' ) );
add_action( 'admin_notices',    array( IIRS_PLUGIN_NAME, 'admin_notices' ) );
// add_action( 'init',             array( IIRS_PLUGIN_NAME, 'plugins_loaded' ) ); // plugins_loaded didn't work!
add_filter( 'format_strings',   array( IIRS_PLUGIN_NAME, 'format_strings' ) );
add_action( 'pre_get_posts',    array( IIRS_PLUGIN_NAME, 'pre_get_posts' ) );
add_action( 'admin_menu',       array( IIRS_PLUGIN_NAME, 'admin_menu' ) );
add_action( 'admin_init',       array( IIRS_PLUGIN_NAME, 'admin_init_option_settings' ) );
// register_nav_menu( 'iirs', IIRS_PLUGIN_NAME );
add_action( 'user_register',    array( IIRS_PLUGIN_NAME, 'user_register' ) );
// add_action( 'after_setup_theme',array( IIRS_PLUGIN_NAME, 'after_setup_theme' ) );
// using the content filter, not a full separate template here
// however, an example template is included for copying in to a theme
add_filter( 'template_include', array( IIRS_PLUGIN_NAME, 'template_inlcude' ) );

// not going to override display of TIs for the main framework anymore
// the framework itself can display things
// we provide standard platform depenedent templates for WordPress and Drupal
// and recommend standard plugins, e.g. Drupal Panels, or Wordpress twig post_formats
add_filter( 'the_content',      array( IIRS_PLUGIN_NAME, 'the_content' ) );

add_action( 'admin_init',       array( IIRS_PLUGIN_NAME, 'admin_init_restrict_access' ) );

// --------------------------------------------------------------------------
class IIRS {
  private static $initiated = false;
  private static $wp_category_id = NULL;

  public static function init() {
    if ( ! self::$initiated ) {
      self::$initiated = true;

      load_plugin_textdomain( IIRS_PLUGIN_NAME, FALSE,  IIRS_PLUGIN_NAME . '/languages/' );
      // print( get_locale() );exit( 0 ); // = it_IT, en_US etc.

      // Model
      self::create_post_categories();              // optional
      self::create_initiative_profile_post_type(); // done in init, live creates the post-type
      self::create_deployment_post_type();         // network of registered plugins
      self::setup_user_roles_and_capabilities();   // TODO: this should be in plugin activation, but doesn't work well

      // additional Wordpress framework integration
      add_shortcode( 'iirs-registration', array( IIRS_PLUGIN_NAME, 'shortcode_iirs_registration' ));
      add_shortcode( 'iirs-mapping',      array( IIRS_PLUGIN_NAME, 'shortcode_iirs_mapping' ));
      add_shortcode( 'iirs-edit-link',    array( IIRS_PLUGIN_NAME, 'shortcode_iirs_edit_link' ));
      add_shortcode( 'iirs-view-link',    array( IIRS_PLUGIN_NAME, 'shortcode_iirs_view_link' ));
      wp_register_sidebar_widget( 'iirs-registration', 'Register your Initiative', array( IIRS_PLUGIN_NAME, 'widget_iirs_registration' ));
    }
  }
  public static function plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=iirs-administrator.php">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  public static function admin_init_restrict_access() {
    global $current_user;
    get_currentuserinfo();

    // prevent users with initiative_facilitator accessing the wp-admin suite
    // redirect them to editing their own TI
    if ( $current_user
      && $current_user->roles
      && in_array( IIRS_0_USER_ROLE_NAME, $current_user->roles )
      && ! current_user_can( 'manage_options' )
      && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
    ) {
      // we have someone who has initiative_facilitator role
      // they are not suppossed to use the administration suite
      // unless somehow they have been granted other roles
      wp_redirect( site_url() . '/IIRS/edit' );
    }
  }

  public static function setup_user_roles_and_capabilities() {
    // http://codex.wordpress.org/Function_Reference/add_cap
    // N.B.: This setting is saved to the database ( in table wp_options, field wp_user_roles ), so it might be better to run this on theme/plugin activation
    // thus: important to run this at plugin activation and de-activation
    if ( null === add_role( IIRS_0_USER_ROLE_NAME, __('Initiative Facilitator'), array() )) {
      // null indicates that the role already exists
      // no bother, just add the capabilities below
    }

    // again, all these functions add settings to the database
    // update the subscriber role to control own posts
    $role = get_role( 'subscriber' );
    $role->add_cap( 'edit_published_' . IIRS_0_CONTENT_TYPE . 's' );
    $role->add_cap( 'edit_' . IIRS_0_CONTENT_TYPE );

    // update the IIRS_0_USER_ROLE_NAME role to control own posts
    $role = get_role( IIRS_0_USER_ROLE_NAME );
    $role->add_cap( 'edit_published_' . IIRS_0_CONTENT_TYPE . 's' );
    $role->add_cap( 'edit_' . IIRS_0_CONTENT_TYPE );
    $role->add_cap( 'read' );

    // allow administrator all capabilities on the new post-type
    $role = get_role( 'administrator' );
    $role->add_cap( 'edit_published_' . IIRS_0_CONTENT_TYPE . 's' );
    $role->add_cap( 'edit_' . IIRS_0_CONTENT_TYPE );
    $role->add_cap( 'edit_others_' . IIRS_0_CONTENT_TYPE . 's' );
  }

  public static function remove_user_roles() {
    // remove_role( IIRS_0_USER_ROLE_NAME );
  }

  public static function format_strings( $format_strings ) {
    // add a post-format to the list
    // requires overridden core to add the apply_filters() to post
    $format_strings['test'] = _x( 'Test',    'Post format' );
    return $format_strings;
  }

  public static function translate( $string_to_translate ) {
    return __( $string_to_translate, IIRS_PLUGIN_NAME );
  }

  public static function user_register( $user_id ) {
    //send a password details email to the new registered user
    //only for users in the IIRS_0_USER_ROLE_NAME role
    $user = get_userdata( $user_id );
    if ( $user && in_array( IIRS_0_USER_ROLE_NAME, (array) $user->roles ) ) {
      $email = $user->data->user_email;
      if ( FALSE !== strstr( $email, 'annesley' )) $email = 'annesley_newholm@yahoo.it';
      //var_dump($email); exit(0);
      wp_mail( $email, 'your new WordPress account', sprintf( 'username: %s password: %s', $user->data->user_login, $user->data->user_pass ));
    }
  }

  public static function shortcode_iirs_registration( $atts ) {
    return self::content( 'registration', 'index', $atts );
  }
  public static function shortcode_iirs_mapping( $atts ) {
    return self::content( 'mapping', 'index', $atts );
  }
  public static function shortcode_iirs_view_link( $args ) {
    return '<a href="/IIRS/view">' . __( 'view your transition initiative' ) . '</a>';
  }
  public static function shortcode_iirs_edit_link( $args ) {
    return '<a href="/IIRS/edit">' . __( 'edit your transition initiative' ) . '</a>';
  }
  public static function widget_iirs_registration( $args ) {
    print( self::content( 'registration', 'index', $args ) );
  }

  public static function content( $widget_folder, $page_stem, $atts = array() ) {
    if ( ! $page_stem ) $page_stem = "index.php";
    $page_extension = ( ! pathinfo( $page_stem, PATHINFO_EXTENSION ) ? '.php' : '' ); // pathinfo() PHP 4 >= 4.0.3, PHP 5
    $page_path      = "$widget_folder/$page_stem$page_extension";

    // static JavaScript and CSS
    // javascript: do not add popup.php because it will override the form submits and show the popups
    // javascript: need to add all JS to every page here because each one is new
    wp_enqueue_script( 'jquery' );
    if ( file_exists( IIRS__PLUGIN_DIR . "/IIRS_common/$widget_folder/general_interaction.js" ))
      wp_enqueue_script( 'IIRS_widget_folder_custom', plugins_url( "IIRS/IIRS_common/$widget_folder/general_interaction.js" ));
    wp_enqueue_script( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general_interaction.js' ));
    wp_enqueue_style(  'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ));

    // build page into the ob stream to trap the contents
    $hide_errors = true;
    if ($hide_errors) ob_start(); // ob_start() PHP 4, PHP 5
    // PHP driven translations for js
    if ( $hide_errors ) print( '<script type="text/javascript">' );
    require_once( 'translations_js.php' );
    require_once( 'global_js.php' );
    if ( $hide_errors ) print( '</script>' );
    // primary template
    require_once( $page_path );
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }

  public static function template_inlcude( $template ) {
    /* we are using the_content filter by default
     * this template suggestion is included for the host to override
     * an example single-initiative_profile.php is included in templates
     */
    global $post;

    // TODO: include a multi-summary template as well
    if ( $post && is_single() && IIRS_0_CONTENT_TYPE == $post->post_type ) {
      $template_name  = 'single-' . IIRS_0_CONTENT_TYPE . '.php';
      $template_theme = locate_template( array( 'plugin_template/' . $template_name ));
      if ( empty( $template_theme ) ) {
        $template_iirs_plugin = IIRS__PLUGIN_DIR . "templates/$template_name";
        if ( file_exists( $template_iirs_plugin ) ) $template = $template_iirs_plugin;
      }
    }

    return $template;
  }

  public static function admin_init_option_settings() {
    // standard IIRS settings
    register_setting( IIRS_PLUGIN_NAME, 'offer_buy_domains' );
    register_setting( IIRS_PLUGIN_NAME, 'add_projects' );
    register_setting( IIRS_PLUGIN_NAME, 'advanced_settings' );
    register_setting( IIRS_PLUGIN_NAME, 'image_entry' );
    register_setting( IIRS_PLUGIN_NAME, 'lang_code' );
    register_setting( IIRS_PLUGIN_NAME, 'server_country' );
    register_setting( IIRS_PLUGIN_NAME, 'override_TI_display' );
    register_setting( IIRS_PLUGIN_NAME, 'override_TI_editing' );
    register_setting( IIRS_PLUGIN_NAME, 'override_TI_content_template' );

    // Wordpress specific settings
    register_setting( IIRS_PLUGIN_NAME, 'initiatives_visibility' );
  }

  public static function admin_menu() {
    add_options_page( IIRS_PLUGIN_NAME, IIRS_PLUGIN_NAME, 'manage_options', 'iirs-administrator', array( IIRS_PLUGIN_NAME, 'options_iirs_administrator' ) );
    //add_submenu_page( 'options-general.php', 'iirs-developer', 'iirs-developer', 'manage_options', 'iirs-developer', array( IIRS_PLUGIN_NAME, 'options_iirs_developer' ) );
  }

  /*
  public static function options_iirs_developer() {
    require_once( 'documentation-iirs-administrator.php' );
  }
  */

  public static function options_iirs_administrator() {
    require_once( 'documentation-iirs-administrator.php' );
  }

  public static function pre_get_posts( $query ) {
    // add_transition_initiatives_to_query
    // http://codex.wordpress.org/Post_Types
    // this will add transition_initiatives in to the standard list of posts on the home page
    // TODO: admin settings to disable this
    // use is_home() to limit this to only the home page
    // NOTE: some queries want a singular post type
    //  in these cases post_type is already completed as a string
    //  NOTICEs will be thrown if we set an array where a string is expected
    if ( get_option( 'initiatives visibility' ) == 'everywhere' ) {
      if ( $query->is_main_query() ) {
        $post_type = $query->get( 'post_type' );
        if ( empty( $post_type )) {
          $query->set( 'post_type', array( 'post', 'movie', 'page', IIRS_0_CONTENT_TYPE ));
        }
      }
    }

    return $query;
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- create and update
  // ------------------------------------------------------------------------
  public static function URL_edit_TI($TI_ID = null) {
    // this returns the framework editing URL
    // NOT the IIRS editing URL /IIRS/edit
    // null $TI_ID indicates that we want the URL for the current users TI
    // TODO: permissions check for edit post URL?
    $post = null;

    if ( $TI_ID ) {
      // update a specific post
      // this function is not currently used by the IIRS system
      $post = get_post($TI_ID);
    } else {
      // update the singular current users TI registration
      $post = self::users_TI_post();
    }

    return ( $post ? get_edit_post_link( $post->ID ) : '' );
  }

  public static function URL_view_TI($TI_ID = null) {
    // this returns the framework editing URL
    // NOT the IIRS editing URL /IIRS/view
    // null $TI_ID indicates that we want the URL for the current users TI
    $post = null;

    if ( $TI_ID ) {
      // update a specific post
      // this function is not currently used by the IIRS system
      $post = get_post($TI_ID);
    } else {
      // update the singular current users TI registration
      $post = self::users_TI_post();
    }

    return ( $post ? get_permalink( $post->ID ) : '' );
  }

  public static function create_deployment_post_type() {
    // http://codex.wordpress.org/Post_Types
    global $IIRS_is_home_domain;

    // IIRS_deployments post-type is the list of foreign websites that are using
    //  plugins / modules and javascript widgets from this system
    // in reality only TN.org will be serving these
    // and all plugins / modules / javascript widgets will register on TN.org
    $primary_deployment_server = $IIRS_is_home_domain;
    if ( $primary_deployment_server ) {
      $post_type_name        = 'IIRS deployment';
      $post_type_name_plural = "{$post_type_name}s";
      $post_type_short       = 'deployments';
      register_post_type( 'iirs_deployment',
        array(
          'label'  => __( $post_type_name_plural ),
          'labels' => array(
            'name' => __( $post_type_name_plural ),
            'singular_name' => __( $post_type_name ),
            'menu_name'          => __( $post_type_name_plural ),
            'name_admin_bar'     => __( $post_type_name_plural, 'add new on admin bar' ),
            'add_new'            => __( 'Add New', $post_type_short ),
            'add_new_item'       => __( "Add New $post_type_name" ),
            'new_item'           => __( "New $post_type_name" ),
            'edit_item'          => __( "Edit $post_type_name" ),
            'view_item'          => __( "View $post_type_name" ),
            'all_items'          => __( "All $post_type_name_plural" ),
            'search_items'       => __( "Search $post_type_name_plural" ),
            'parent_item_colon'  => __( "Parent $post_type_name_plural:" ),
            'not_found'          => __( "No $post_type_short found." ),
            'not_found_in_trash' => __( "No $post_type_short found in Trash." ),
          ),
          'description' => 'IIRS deployment',

          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,

          'supports'           => array(
            'title', 'editor', 'author', 'comments', 'custom-fields'
          ),

          'menu_position'      => 20, // below Pages
          'menu_icon'          => 'dashicons-video-alt', // TODO: make one!
          'capability_type'    => 'post',
          'capabilities'       => array(
            'edit_post' => 'edit_iirs_deployment',
          ),
        )
      );
    }
  }

  public static function create_initiative_profile_post_type() {
    // http://codex.wordpress.org/Post_Types
    global $IIRS_is_home_domain, $wp_rewrite;

    // these are the actual TIs.
    // custom fields are added with the actual wp_insert_post()
    // custom fields are defined per-post, not with the post-type...
    $post_type_name        = 'Initiative';
    $post_type_name_plural = "{$post_type_name}s";
    register_post_type( IIRS_0_CONTENT_TYPE,
      array(
        'label'  => __( $post_type_name_plural ),
        'labels' => array(
          'name' => __( $post_type_name_plural ),
          'singular_name' => __( $post_type_name ),
          'menu_name'          => __( $post_type_name_plural ),
          'name_admin_bar'     => __( $post_type_name_plural, 'add new on admin bar' ),
          'add_new'            => __( 'Add New', 'initiative' ),
          'add_new_item'       => __( "Add New $post_type_name" ),
          'new_item'           => __( "New $post_type_name" ),
          'edit_item'          => __( "Edit $post_type_name" ),
          'view_item'          => __( "View $post_type_name" ),
          'all_items'          => __( "All $post_type_name_plural" ),
          'search_items'       => __( "Search $post_type_name_plural" ),
          'parent_item_colon'  => __( "Parent $post_type_name_plural:" ),
          'not_found'          => __( 'No initiatives found.' ),
          'not_found_in_trash' => __( 'No initiatives found in Trash.' ),
        ),
        'description' => 'Transition Town intiative',

        'public'      => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'hierarchical'       => false,
        'has_archive'        => false,

        'supports'           => array(
          'title', 'editor', 'author', 'thumbnail', 'excerpt',
          'comments',
          'custom-fields', // domain, location data, etc.
          // 'post-formats'
          'page-attributes',
        ),

        // 'rewrite'            => array( 'slug' => IIRS_0_CONTENT_TYPE_SLUG ), // TODO: admin settings for TI slug
        'menu_position'      => 20, // below Pages
        'menu_icon'          => 'dashicons-video-alt', // TODO: make menu_icon!
        'taxonomies'         => array( 'category', 'post_tag' ),

        'capability_type'    => 'post',
        'capabilities'       => array(
          'edit_published_posts' => 'edit_published_' . IIRS_0_CONTENT_TYPE . 's',
          'edit_post' => 'edit_' . IIRS_0_CONTENT_TYPE,
          'edit_others_posts' => 'edit_others_' . IIRS_0_CONTENT_TYPE . 's',
        ),
        'map_meta_cap' => true,
      )
    );
    // post editing fails without this!
    // 10 = default priority
    // 4  = parameter count for map_meta_cap
    // add_filter( 'map_meta_cap', array( IIRS_PLUGIN_NAME, 'current_user_can' ), 10, 4 );


    // flush the rewrite rules once to create the /initiative_profile/ URL
    if ( get_option( IIRS_PLUGIN_NAME . '_flush_rewrite_rules' ) ) {
      delete_option( IIRS_PLUGIN_NAME . '_flush_rewrite_rules' );
      if ( isset( $wp_rewrite ) ) $wp_rewrite->flush_rules( false );
    }
  }

  public static function create_post_categories() {
    // create a category for our posts
    // need to include the wp-admin/includes/taxonomy.php
    // TODO: admin settings to disable this
    self::$wp_category_id = wp_create_category( IIRS_0_POST_CATEGORY );
  }

  public static function add_user( $name, $email, $requested_password, $phone ) {
    // adds the user, role, etc.
    // $pass and $phone are optional
    // a random password will be created and emailed if not included
    // NULL return indicates failure
    $new_user_id = NULL;

    if ( username_exists( $name ) || email_exists( $email )) {
      // name or email already exists, return NULL
    } else {
      if ( $requested_password ) $password = $requested_password;
      else                       $password = wp_generate_password();
      $new_user_id = wp_create_user( $name, $password, $email );
      if ( $new_user_id ) {
        // test the login, and actually login immediately
        $wp_user = wp_signon( array(
          'user_login'    => $name,
          'user_password' => $password,
          'remember'      => true
        ) );
        // setup the user
        if ( $wp_user ) {
          // set role
          // IIRS_0_USER_ROLE_NAME is added during plugin activation
          $wp_role = get_role( IIRS_0_USER_ROLE_NAME );
          if ( $wp_role ) $wp_user->set_role( IIRS_0_USER_ROLE_NAME );
          else {
            // the role created in plugin activation below is missing
            // assume deleted by administrator
            // TODO: show a NOTICE for missing user role?
            $wp_user->set_role( 'subscriber' );
          }
        }
        // email and password to the user
        // this is carried out in user_register() above
      }
    }

    return $new_user_id;
  }

  public static function delete_user( $user_ID ) {
    // used when the recent add user works but the add TI fails
    require_once(ABSPATH.'wp-admin/includes/user.php' );
    wp_delete_user( $user_ID, false ); // false = do not Reassign posts and links to new User ID.
  }

  public static function add_TI( $user_ID, $registering_server, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address = '',  $location_granuality = '', $location_bounds = '', $domain = '' ) {
    // now create the post with our contents on it
    // post_type is the IIRS_0_CONTENT_TYPE of course
    $transition_initiative_guid = self::GUID();

    $post = new stdClass();
    $post->post_author   = $user_ID;
    // $post->post_date     = '2014-07-10 14:45:58';
    // $post->post_date_gmt = '2014-07-10 14:45:58';
    $post->post_content  = ''; // The full text of the post.
    $post->post_title    = $initiative_name; // The title of your post.
    //$post->post_excerpt  = ''; // "$initiative_name Transition Initiative"; // For all your post excerpt needs.
    $post->post_status   = 'publish'; // Set the status of the new post.
    $post->comment_status= 'closed'; // Set the status of the new post.
    $post->ping_status   = 'open'; // Set the status of the new post.
    $post->post_password = ''; // Set the status of the new post.
    $post->post_name     = "$initiative_name Transition Initiative"; // Set the status of the new post.
    $post->to_ping       = ''; // Set the status of the new post.
    $post->pinged        = ''; // Set the status of the new post.
    // $post->post_modified = '2014-07-10 14:45:58';
    // $post->post_modified_gmt = '2014-07-10 14:45:58';
    $post->post_content_filtered = '';
    $post->post_parent   = 0;
    $post->guid          = $transition_initiative_guid;
    $post->menu_order    = 0;
    $post->post_type     = IIRS_0_CONTENT_TYPE;
    $post->post_mime_type= '';
    $post->comment_count = '1';
    $post->filter        = 'raw';
    $post->post_category = array( self::$wp_category_id ); // Add some categories. an array()???

    $post_id = wp_insert_post( $post );

    // custom fields
    add_post_meta( $post_id, 'domain',                $domain, false );
    add_post_meta( $post_id, 'location_latitude',     $location_latitude, false );
    add_post_meta( $post_id, 'location_longitude',    $location_longitude, false );
    add_post_meta( $post_id, 'location_townname',     $town_name, false );
    add_post_meta( $post_id, 'location_description',  $location_description, false );
    add_post_meta( $post_id, 'location_country',      $location_country, false );
    add_post_meta( $post_id, 'location_full_address', $location_full_address, false);
    add_post_meta( $post_id, 'location_granuality',   $location_granuality, false);
    add_post_meta( $post_id, 'location_bounds',       $location_bounds, false);

    // meta data
    add_post_meta( $post_id, 'registering_server',    $registering_server, false );
    add_post_meta( $post_id, 'guid',                  $transition_initiative_guid, false );

    return $post_id;
  }

  public static function GUID() {
    $GUID = '';
    if ( true === function_exists('com_create_guid') ) $GUID = trim( com_create_guid(), '{}' );
    else $GUID = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    return $GUID;
  }

  public static function update_TI( $new_values, $TI_ID = null ) {
    // this function will ONLY update the users TI
    // it will NOT update any other TI
    // so do not use the edit screen for anything else
    $post = null;

    if ( $TI_ID ) {
      // update a specific post
      // this function is not currently used by the IIRS system
      $post = get_post($TI_ID);
    } else {
      // update the singular current users TI registration
      $post = self::users_TI_post();
    }

    if ( $post ) {
      $post_id = $post->ID;
      $translated_values = self::translate_TI_fields( $new_values );
      $translated_values['post_ID']   = $post_id;
      $translated_values['post_type'] = $post->post_type;
      print("translated values:\n");
      var_dump( $translated_values );
      edit_post( $translated_values );
      foreach ( $translated_values['_meta'] as $meta_key => $meta_value ) {
        update_post_meta( $post_id, $meta_key, $meta_value );
      }
    } else {
      print( "TI not found [$TI_ID]!\n" );
    }
  }

  public static function update_user( $new_values ) {
    // returns native user id on success
    // returns null on failure
    global $current_user;
    $ret = null;
    get_currentuserinfo();

    var_dump($current_user);

    $translated_values = self::translate_user_fields( $new_values );
    $translated_values['ID'] = $current_user->ID;
    // TODO: a bug in line 194 in registration.php does not include user_login as an editable field
    $user_id = wp_update_user( $translated_values );

    if ( is_wp_error( $user_id ) ) {
      var_dump( $user_id );
      exit(0);
      $ret = null;
    } else {
      // re-populate the global $current_user
      // it is the callers responsibility to re-populate any data containing user information
      get_currentuserinfo();
      $ret = $user_id;
    }

    return $ret;
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- query
  // ------------------------------------------------------------------------
  private static function users_TI_post() {
    global $current_user;
    $post = null;

    // get the singular current users TI registration
    get_currentuserinfo();
    if ( $current_user ) {
      $posts_array = get_posts( array(
        'post_type'      => IIRS_0_CONTENT_TYPE,
        'author'         => $current_user->ID,
        'posts_per_page' => 1,
        'offset'         => 0,
      ) );
      if ( count( $posts_array )) {
        $post = $posts_array[0];
      }
    }

    return $post;
  }

  public static function TI_all( $page_size, $page_offset ) {
    // NOTE: this function runs in a <script> tags so errors will be hidden
    $all_TIs = array();

    $posts = get_posts( array(
      'post_type'      => IIRS_0_CONTENT_TYPE,
      'posts_per_page' => $page_size,
      'offset'         => $page_offset,
    ) );

    foreach ( $posts as $post ) {
      array_push( $all_TIs, self::TI_from_post( $post ));
    }

    return $all_TIs;
  }

  public static function details_user() {
    global $current_user;
    get_currentuserinfo();
    $user = NULL;

    if ( $current_user ) {
      $user = array();
      $user['native_ID'] = $current_user->ID;
      $user['name']      = $current_user->data->user_login;
      $user['email']     = $current_user->data->user_email;
      $user['role']      = ( in_array( IIRS_0_USER_ROLE_NAME, (array) $current_user->roles ) ? IIRS_0_USER_ROLE_NAME : null );
    }

    return $user;
  }

  public static function TI_from_post( $post ) {
    $TI = NULL;

    if ( $post ) {
      $post_meta = get_post_meta( $post->ID ); // get all meta data in one query

      $TI = array(
        'native_ID' => $post->ID,
        'name'      => $post->post_title,
        'summary'   => $post->post_content,

        //meta fields have the same name, but in an array(value)
        'domain'                => (isset($post_meta['domain']) ? $post_meta['domain'][0] : ''),
        'location_latitude'     => (isset($post_meta['location_latitude']) ? $post_meta['location_latitude'][0] : ''),
        'location_longitude'    => (isset($post_meta['location_longitude']) ? $post_meta['location_longitude'][0] : ''),
        'location_description'  => (isset($post_meta['location_description']) ? $post_meta['location_description'][0] : ''),
        'location_country'      => (isset($post_meta['location_country']) ? $post_meta['location_country'][0] : ''),
        'location_full_address' => (isset($post_meta['location_full_address']) ? $post_meta['location_full_address'][0] : ''),
        'location_granuality'   => (isset($post_meta['location_granuality']) ? $post_meta['location_granuality'][0] : ''),
        'location_bounds'       => (isset($post_meta['location_bounds']) ? $post_meta['location_bounds'][0] : ''),

        'registering_server'    => (isset($post_meta['registering_server']) ? $post_meta['registering_server'][0] : ''),
        'guid'                  => (isset($post_meta['guid']) ? $post_meta['guid'][0] : ''),
        'registration_date'     => $post->post_date,

        'language'              => '',
        'status'                => '',
        'date'                  => $post->post_date, // used for sorting
      );
    }

    return $TI;
  }

  public static function TI_from_current_post() {
    global $post;
    return self::TI_from_post( $post );
  }

  public static function TI_from_current_user() {
    $post = self::users_TI_post();
    return self::TI_from_post( $post );
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- authentication
  // ------------------------------------------------------------------------
  public static function logged_in() {
    return is_user_logged_in();
  }

  public static function login( $name, $pass ) {
    $user = wp_signon( array(
      'user_login'    => $name,
      'user_password' => $pass,
      'remember'      => true,
    ) );
  }


  public static function current_user_can( $caps, $cap, $user_id, $args ) {
    global $post;
    $allowed = false;
      var_dump( $caps );
      var_dump( $cap );
      var_dump( $user_id );


    if ( $post && IIRS_0_CONTENT_TYPE == $post->post_type && 'edit_post' == $cap ) {
      var_dump( $post->post_author );
      // $allowed = ( $post->post_author == "$user_id" );
      // $caps['edit_post'] = true;
    }
    var_dump( $allowed );
    return true;
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- page, javascript, serving and utilities
  // ------------------------------------------------------------------------
  public static function wp( $wp ) {
    // take over all the /IIRS/* requests and serve the appropriate page
    // types include bare pages, widget systems, images and optional redirects
    global $wp_query, $wp;

    if ( $wp_query->is_404 ) {
      $request_parts = explode( '/', $wp->request ); // explode() PHP 4
      if ( count( $request_parts ) >= 2 && IIRS_PLUGIN_NAME == $request_parts[0] ) {
        status_header( 200 );
        $widget_folder = $request_parts[1];
        $page_stem     = ( count( $request_parts ) > 2 ? $request_parts[2] : '' );

        // various path identifiers and defaults
        if ( !$page_stem ) $page_stem = 'index';
        // some servers will restrict direct access to PHP files so URLs do not have php extensions
        $page_extension = ( !pathinfo( $page_stem, PATHINFO_EXTENSION ) ? '.php' : '' ); // pathinfo() PHP 4 >= 4.0.3, PHP 5
        $page_path      = "$widget_folder/$page_stem$page_extension";

        // ---------------------------------------------------- direct request for the widgetloader.js
        if ( strstr( $page_stem, 'widgetloader' )) {
          // widgetloader javascript will examine the $widget_folder and request the appropriate file
          require_once( 'IIRS_common/widgetloader.php' );
          exit( 0 );
        }

        // ---------------------------------------------------- image request
        elseif ( 'images' == $widget_folder ) {
          $file_extension = pathinfo( $page_stem, PATHINFO_EXTENSION );
          if ( ! $file_extension ) { // pathinfo() PHP 4 >= 4.0.3, PHP 5
            if (    file_exists( __DIR__ . "/IIRS_common/images/$page_stem.png" )) $file_extension = 'png';
            elseif ( file_exists( __DIR__ . "/IIRS_common/images/$page_stem.gif" )) $file_extension = 'gif';
            // TODO: image extension not found?
            $page_stem .= ".$file_extension";
          }
          switch ( $file_extension ) {
            case 'png': {$mime = 'image/png'; break;}
            case 'png': {$mime = 'image/gif'; break;}
          }
          $image_path = __DIR__ . "/IIRS_common/images/$page_stem";

          if (!file_exists($image_path)) {
            http_response_code(404);
            print("can't find [$image_path]");
            exit(0);
          }

          ob_clean();  // any NOTICES they made their way through
          header( "Content-type: $mime", true );
          print( file_get_contents( $image_path ));
          exit( 0 );
        }

        // ---------------------------------------------------- bare pages
        elseif ( 'export' == $widget_folder || 'import' == $widget_folder ) {
          ob_clean(); // any NOTICES they made their way through
          header( 'Content-type: text/xml', true );
          require_once( $page_path );
          exit( 0 );
        }

        // ---------------------------------------------------- foriegn Widget content request
        // this is a bare page content request, so show only the direct content
        elseif ( 'true' == self::input( 'IIRS_widget_mode' ) ) {
          // javascript: interaction.js translations_js.php and general_interaction.js are dynamically written in to widgetloader.php
          // javascript: these responses will be dynamically added in to the HTML on the client so no need to send the JS again
          require_once( $page_path );
          exit( 0 );
        }

        // ---------------------------------------------------- non-widget conditional redirects
        // provide some easy links to access the users TI
        // host framework links for viewing and editing
        // the widget will always use the IIRS/* versions of course
        elseif ( 'view' == $widget_folder && false == IIRS_0_setting( 'override_TI_display' ) ) {
          // we are not overriding the TI display
          // so redirect to the host framework display
          wp_redirect( IIRS_0_URL_view_TI( self::input( 'ID' ) ) );
        }
        elseif ( 'edit' == $widget_folder && false == IIRS_0_setting( 'override_TI_editing' ) ) {
          // we are not overriding the TI editing
          // so redirect to the host framework editing
          wp_redirect( IIRS_0_URL_edit_TI( self::input( 'ID' ) ) );
        }

        // ---------------------------------------------------- Wordpress full page request
        // TODO: feed this page display code layout back in to the IIRS Drupal Module
        // this will be a normal Wordpress page request, so return a fake post
        // curcumventing the template system here because we want to maintain similar markup
        else {
          // static JavaScript and CSS
          // javascript: do not add popup.php because it will override the form submits and show the popups
          // javascript: need to add all JS to every page here because each one is new
          // IIRS_0_translation('IIRS documentation');
          // IIRS_0_translation('IIRS edit');
          // IIRS_0_translation('IIRS export');
          // IIRS_0_translation('IIRS import');
          // IIRS_0_translation('IIRS list');
          // IIRS_0_translation('IIRS mapping');
          // IIRS_0_translation('IIRS registration');
          // IIRS_0_translation('IIRS search');
          // IIRS_0_translation('IIRS view');
          $title   = IIRS_0_translation( "IIRS $widget_folder" );
          $content = self::content( $widget_folder, $page_stem );
          self::fake_page( $title, $content );
        }
      }
    }
  }

  private static function input( $sKey ) {
    // return value from $_POST and $_GET arrays
    return ( isset( $_POST[$sKey] ) ? $_POST[$sKey] : ( isset( $_GET[$sKey] ) ? $_GET[$sKey] : NULL ));
  }

  private static function fake_page( $title, $content ) {
    // this is not a real post in Wordpress
    // but we want to allow all the usual filters and display processes
    // so create a fake "post" with the content of our target page
    global $wp_query, $wp;

    // prevent encoding of our post content
    remove_all_filters( 'the_content', 'plugin_filters' );
    wp_enqueue_style( 'IIRS_fake_page_view', plugins_url( 'IIRS/fake_page.css' ));

    // now create the fake post with our contents on it
    $id=-42; // need an id: TODO: is this reasonable?
    $post = new stdClass();
    $post->ID            = $id;
    $post->post_author   = '1';
    $post->post_date     = '2014-07-10 14:45:58';
    $post->post_date_gmt = '2014-07-10 14:45:58';
    $post->post_content  = $content; // The full text of the post.
    $post->post_title    = $title; // The title of your post.
    $post->post_excerpt  = "IIRS: $title"; // For all your post excerpt needs.
    $post->post_status   = 'publish'; // Set the status of the new post.
    $post->comment_status= 'closed'; // Set the status of the new post.
    $post->ping_status   = 'open'; // Set the status of the new post.
    $post->post_password = ''; // Set the status of the new post.
    $post->post_name     = $title; // Set the status of the new post.
    $post->to_ping       = ''; // Set the status of the new post.
    $post->pinged        = ''; // Set the status of the new post.
    $post->post_modified = '2014-07-10 14:45:58';
    $post->post_modified_gmt = '2014-07-10 14:45:58';
    $post->post_content_filtered = $content;
    $post->post_parent   = 0;
    $post->guid          = "http://wp39example.dev/?p=1";
    $post->menu_order    = 0;
    $post->post_type     = 'post';
    $post->post_mime_type= '';
    $post->comment_count = '1';
    $post->filter        = 'raw';

    // alter the wp_query
    $wp_query->queried_object = $post;
    $wp_query->post           = $post;
    $wp_query->found_posts    = 1;
    $wp_query->post_count     = 1;
    $wp_query->max_num_pages  = 1;
    $wp_query->is_single      = 1;
    $wp_query->is_404         = false;
    $wp_query->is_posts_page  = 1;
    $wp_query->posts          = array( $post );
    $wp_query->page           = false;
    $wp_query->is_post        = true;
  }

  public static function the_content( $content ) {
    // Wordpress specific override the content of full and summary TI view pages
    // this is within a display frame not controlled by IIRS
    // This function comes in to play when:
    //   the override display of TIs is off and the website is using Wordpress lists and views of TIs
    // Otherwise the /IIRS/list, /IIRS/view and /IIRS/edit are used
    // This function is similar to using a WordPress single-initiative_profile.php template
    //   which is included in templates and can be used.
    //   this system however, links easily and directly in to the_content
    // TODO: maybe use the template system and link in to the /IIRS/view from there.
    global $post;

    if ( true == IIRS_0_setting( 'override_TI_content_template' ) && $post && IIRS_0_CONTENT_TYPE == $post->post_type ) {
      // so we must manually include css and javascript that we want
      wp_enqueue_style(  'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ));
      wp_enqueue_script( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general_interaction.js' ));

      $TI          = self::TI_from_post( $post );
      $list_mode   = ! is_single(); // is this the full page TI or in a list?

      ob_start(); // ob_start() PHP 4, PHP 5
      $hide_errors = true;
      if ( $hide_errors ) print( '<script type="text/javascript">' );
      require_once( 'translations_js.php' );
      require_once( 'global_js.php' );
      if ( $hide_errors ) print( '</script>' );
      include( 'view/index.php' );
      $content = ob_get_contents();
      ob_end_clean();
    }

    return $content;
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- misc
  // ------------------------------------------------------------------------
  private static function translate_user_fields( $values ) {
    // private function (preceeded by _)
    // change standard fields definition to Drupal TN.org fields
    $translated_values = array();
    foreach ( $values as $key => $value ) {
      // translate the standard TI field names to the Drupal TN.org ones
      if ( !empty( $value )) {
        switch ( $key ) {
          case 'name':     {$key = 'user_login'; break;}
          case 'email':    {$key = 'user_email'; break;}
          case 'password': {$key = 'user_pass';  break;}
        }
      }
      // re-write the value
      $translated_values[$key] = $value;
    }

    return $translated_values;
  }

  private static function translate_TI_fields( $values ) {
    // private function (preceeded by _)
    // change standard fields definition to Drupal TN.org fields
    $translated_values = array( '_meta' => array() );
    foreach ( $values as $key => $value ) {
      // translate the standard TI field names to the Drupal TN.org ones
      if ( !empty( $value )) {
        switch ( $key ) {
          case 'type':
          case 'language': {break;}
          case 'published': {$translated_values['status'] = $value; break;}

          // base node fields title, body
          case 'name':
          case 'initiative_name':
          case 'title':   {$translated_values['post_title']   = $value; break;}
          case 'summary': {$translated_values['post_content'] = $value; break;}

          // meta data: these fields are updated separately
          // weird Wordpress uses a meta id for edit_post() updates
          case 'townname':           {$translated_values['_meta']['location_townname']     = $value; break;}
          case 'location_latitude':   {$translated_values['_meta']['location_latitude']     = $value; break;}
          case 'location_longitude':   {$translated_values['_meta']['location_longitude']    = $value; break;}
          case 'location_description':  {$translated_values['_meta']['location_description']  = $value; break;}
          case 'location_country':      {$translated_values['_meta']['location_country']      = $value; break;}
          case 'location_full_address': {$translated_values['_meta']['location_full_address'] = $value; break;}
          case 'location_granuality':   {$translated_values['_meta']['location_granuality']   = $value; break;}
          case 'location_bounds':       {$translated_values['_meta']['location_bounds']       = $value; break;}
          case 'website':
          case 'domain':             {$translated_values['_meta']['domain'] = $value; break;}
        }
      }
    }

    return $translated_values;
  }

  public static function view( $name, array $args = array() ) {
    // copied from the AKISMET plugin. nice bit of code, but not currently used
    $args = apply_filters( 'iirs_view_arguments', $args, $name );

    foreach ( $args AS $key => $val ) {
      $$key = $val;
    }

    load_plugin_textdomain( 'iirs' );

    $file = IIRS__PLUGIN_DIR . 'views/'. $name . '.php';

    include( $file );
  }

  public static function plugin_active( $plugin_file ) {
    return in_array( $plugin_file, get_option( 'active_plugins' ) );
  }

  public static function plugin_installed( $plugin_file ) {
    return in_array( $plugin_file, scandir( dirname( __FILE__ ) . '/..' ) );
  }

  /**
   * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
   * @static
   */
  public static function plugin_activation() {
    if ( version_compare( $GLOBALS['wp_version'], IIRS__MINIMUM_WP_VERSION, '<' ) ) {
      load_plugin_textdomain( 'iirs' );

      // add the user roles to the database permanently
      // so that users can be added to them automatically
      // see http://codex.wordpress.org/Function_Reference/add_role
      // TODO: setup_user_roles_and_capabilities() doesn't seem to reliably work here. needs to be investigated
      // self::setup_user_roles_and_capabilities();

      //TODO: how to get this message to display:
      add_option( IIRS_PLUGIN_NAME . '_show_activation_version_fail_message', true );
    } else {
      add_option( IIRS_PLUGIN_NAME . '_show_activation_message', true );
      add_option( IIRS_PLUGIN_NAME . '_flush_rewrite_rules', true );
    }

    // recommended plugins
    add_option( IIRS_PLUGIN_NAME . '_recommend_plugins', true );
  }

  public static function admin_notices() {
    if ( get_option( IIRS_PLUGIN_NAME . '_show_activation_message' ) ) {
      delete_option( IIRS_PLUGIN_NAME . '_show_activation_message' );
      print( '<div class="updated"><p>checkout the <a href="/wp-admin/options-general.php?page=iirs-administrator.php">IIRS settings page</a> for contacts, documentation, options and setup</p></div>' );
    }

    if ( get_option( IIRS_PLUGIN_NAME . '_recommend_plugins' ) ) {
      delete_option( IIRS_PLUGIN_NAME . '_recommend_plugins' );
      $WPML_main_active = self::plugin_active( 'sitepress-multilingual-cms/sitepress.php' );
      $WPML_st_active   = self::plugin_active( 'wpml-string-translation/plugin.php' );

      if ( ! $WPML_main_active || ! $WPML_st_active ) {
        print( '<div class="updated">' );
        if ( ! $WPML_main_active ) print( '<p>IIRS recommends <a href="http://wpml.org/">WPML</a> to manage languages on this website. We have a free license for you.</p>' );
        if ( ! $WPML_st_active )   print( '<p>IIRS recommends <a href="http://wpml.org/">WPML</a> string translation to translate the plugin strings</p>' );
        print( '</div>' );
      }
    }

    if ( get_option( IIRS_PLUGIN_NAME . '_show_activation_version_fail_message' ) ) {
      delete_option( IIRS_PLUGIN_NAME . '_show_activation_version_fail_message' );
      print( '<div class="error"><p><strong>IIRS ' . IIRS_VERSION . ' requires WordPress ' . IIRS__MINIMUM_WP_VERSION . ' or higher.</strong>' );
      print( 'Please <a href="https:// codex.wordpress.org/Upgrading_WordPress">upgrade WordPress</a> to a current version.</p></div>' );
    }

    if ( get_option('permalink_structure') == '' ) {
      print('<div class="error"><p>IIRS custom pages <strong>will not work</strong>. You need to <a href="/wp-admin/options-permalink.php">activate permalinks</a> first.</p></div>' );
    }
  }

  /**
   * Removes all connection options
   * @static
   */
  public static function plugin_deactivation( ) {
    // tidy up
    // self::remove_user_roles();
  }

  public static function log( $iirs_debug ) {
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
      error_log( print_r( compact( 'iirs_debug' ), 1 ) ); // send message to debug.log when in debug mode
  }
}