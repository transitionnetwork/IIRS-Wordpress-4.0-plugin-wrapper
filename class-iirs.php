<?php
/* Copyright 2015, 2016 Transition Network ltd
 * This program is distributed under the terms of the GNU General Public License
 * as detailed in the COPYING file included in the root of this plugin
 */

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
add_filter( 'plugin_locale',    array( IIRS_PLUGIN_NAME, 'plugin_locale' ) , 10, 2 );
add_action( 'pre_get_posts',    array( IIRS_PLUGIN_NAME, 'pre_get_posts' ) );
add_action( 'admin_menu',       array( IIRS_PLUGIN_NAME, 'admin_menu' ) );
add_action( 'admin_init',       array( IIRS_PLUGIN_NAME, 'admin_init_option_settings' ) );
// register_nav_menu( 'iirs', IIRS_PLUGIN_NAME );
// user_register is DISABLED because we do not want to force email sending
// the admin might add a user with the IIRS_0_USER_ROLE_NAME manually...
// add_action( 'user_register',    array( IIRS_PLUGIN_NAME, 'send_user_registration_email' ) );

// add_action( 'after_setup_theme',array( IIRS_PLUGIN_NAME, 'after_setup_theme' ) );
// using the content filter, not a full separate template here
// however, an example template is included for copying in to a theme
add_filter( 'template_include', array( IIRS_PLUGIN_NAME, 'template_include' ) );
// include the ability to get_posts by title
// to check for duplicates
add_filter( 'posts_where',      array( IIRS_PLUGIN_NAME, 'posts_where' ), 10, 2 );

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
    global $current_user;
    get_currentuserinfo();

    if ( ! self::$initiated ) {
      self::$initiated = true;

      load_plugin_textdomain( IIRS_PLUGIN_NAME, TRUE,  IIRS_PLUGIN_NAME . '/languages' );
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

      if ( $current_user
        && $current_user->roles
        && in_array( IIRS_0_USER_ROLE_NAME, $current_user->roles )
        && ! current_user_can( 'manage_options' )
        && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
      ) {
        //e.g. suppress the admin bar for initiative managers
        wp_enqueue_style( 'IIRS_wordpress', plugins_url( 'IIRS/IIRS_wordpress.css' ));
      }
    }
  }

  public static function plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=IIRS">Settings</a>';
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

  public static function setting( $setting ) {
    // options is go only in version 2
    if ( IIRS_WP_OPTIONS_ENABLED ) {
      return get_option( $setting );
    } else {
      // Defaults:
      switch ( $setting ) {
        case 'offer_buy_domains': return false;
        case 'add_projects': return false;
        case 'advanced_settings': return false;
        case 'image_entry': return false;
        case 'lang_code': return 'en';
        case 'server_country': return NULL;
        case 'override_TI_display': return false;
        case 'override_TI_editing': return true;
        case 'override_TI_content_template': return true;
        case 'language_selector': return false;
        case 'thankyou_for_registering_url': return null;
        case 'region_bias': return null;
        case 'additional_error_notification_email': return '';
        case 'registration_notification_email': return '';
        default: return false;
      }
    }
  }

  public static function http_request( $url, $post_array, $timeout = 2.0, $ua = null ) {
    global $wp_version;
    $body = null;

    $args = array(
      'timeout'     => $timeout,
      'method'      => ( $post_array ? 'POST' : 'GET' ),
      'redirection' => 0,
      'httpversion' => '1.0',
      'user-agent'  => ( $ua ? $ua : 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) ),
      'blocking'    => true,
      'headers'     => array(),
      'cookies'     => array(),
      'body'        => $post_array,
      'compress'    => false,
      'decompress'  => true,
      'sslverify'   => true,
      'stream'      => false,
      'filename'    => null
    );

    $response = ( $post_array ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args ) );
    if ( is_wp_error( $response ) ) {
      $body = new IIRS_Error(IIRS_HTTP_FAILED, "Oops, it seems that the our servers are not responding! The manager has been informed and is trying to solve the problem. Please come back here tomorrow :)", $response->get_error_message(), IIRS_MESSAGE_EXTERNAL_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION );
    } else {
      // note that parsing in to the array $response[] is already done
      $response_code = wp_remote_retrieve_response_code( $response ); // $response[response][code]
      if ( $response_code == 404 ) $body = new IIRS_Error(IIRS_HTTP_NOT_FOUND, "Page not found", '404 - Page not found', IIRS_MESSAGE_EXTERNAL_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION );
      else {
        $body = wp_remote_retrieve_body( $response );          // $response[body]
        // regard empty response as an error
        // because wp_remote_*() do not always report the error correctly it seems
        if ( $body === '' ) $body = new IIRS_Error(IIRS_HTTP_FAILED,    "Oops, it seems that the our servers are not responding! The manager has been informed and is trying to solve the problem. Please come back here tomorrow :)", 'Blank response', IIRS_MESSAGE_EXTERNAL_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION );
      }
    }

    return $body;
  }

  public static function plugin_locale( $locale, $domain ) {
    $locale_touse = $locale;
    if ( $domain == IIRS_PLUGIN_NAME ) {
      $force_locale = IIRS_0_setting( 'lang_code' );
      if ( ! empty( $force_locale ) ) $locale_touse = $force_locale;
    }
    return $locale_touse;
  }

  public static function set_message( $mess_no, $message, $message_detail = null, $level = IIRS_MESSAGE_USER_INFORMATION, $user_action = null, $args = null ) {
    // SECURITY: $message is text, NOT HTML. it will be pushed through IIRS_0_escape_for_HTML_text() -> htmlentities()
    // the caller should NOT escape the input
    // however, the caller SHOULD translate the input
    global $IIRS_widget_mode;

    $html = IIRS_0_message_html( $mess_no, $message, $message_detail, $level, $user_action, $args );
    if ( isset( $IIRS_widget_mode ) && true === $IIRS_widget_mode ) {
      // global $IIRS_widget_mode requires that the message is included in the HTML output
      // because the user is viewing the message through HTML transported in the widget on a *different* website
      // normal message display, that is through a plugin / module on *this* website can use the host framework function
      // e.g. Drupal uses drupal_set_message() which *indirectly* queues the message for display (once)
      IIRS_0_print_HTML( $html );
    } else {
      // TODO: actually Wordpress does not seem to have a drupal_set_message() function for the user front end?
      // so we will pump the message out directly as well here until further investigation (TODO)
      IIRS_0_print_HTML( $html );
    }
  }

  public static function locale() {
    return get_locale(); // e.g. en_EN
  }

  public static function available_languages() {
    // Wordpress plugins have a language directory that contains MO language translation files
    // one per language
    // $language_filenames will have partial filenames like IIRS-en_EN in it
    // we sanitize this to the language code, e.g. en_EN
    // get_available_languages( ... ) is a Wordpress core function
    // https://developer.wordpress.org/reference/functions/get_available_languages/
    $language_filenames = get_available_languages( dirname( __FILE__ ) . '/languages' );
    $language_codes     = array();
    foreach ( $language_filenames as $filename ) {
      array_push( $language_codes, substr( $filename, 5 ) );
    }
    return $language_codes;
  }

  public static function wp_mail_content_type() {
    // used by send_email( ... ) below
    return 'text/html';
  }

  public static function send_email( $email_address, $subject, $body, $add_headers = array() ) {
    // HTML body always: the add_filter( ... ) does not seem to work so the headers are also used
    // returns TRUE or an IIRS_Error
    $ret = FALSE;

    // testing email control system
    // replace the next line with your own details if needed
    // if it contains "annesley", send it to annesley
    if ( FALSE !== strstr( $email_address, 'annesley' )) {
      $email_address = 'annesley_newholm@yahoo.it';
      IIRS_0_debug_print( "morphed email to $email_address because contains annesley" );
    }

    IIRS_0_debug_print( "wordpress sending email to [$email_address]" );
    IIRS_0_debug_print( "  required php.ini settings:" );
    IIRS_0_debug_print( "  SMTP:" . ini_get('SMTP') . "" );
    IIRS_0_debug_print( "  smtp_port:" . ini_get('smtp_port') . ""  );

    add_filter( 'wp_mail_content_type', array( IIRS_PLUGIN_NAME, 'wp_mail_content_type' ) );
    // array_merge: this is a numeric key array so the 2 will be appended, not overwritten
    // http://php.net/manual/en/function.array-merge.php
    // TODO: make From address configurable in settings, not translation
    $from_name    = IIRS_0_translation( 'TransitionTowns registration service' );
    $from_address = IIRS_0_translation( 'registrar@transitionnetwork.org' );
    $all_headers  = array_merge( array(
      "From: \"$from_name\" <$from_address>",
      'Content-Type: text/html; charset=UTF-8',
    ), $add_headers );
    $wp_mail_result = wp_mail( $email_address, $subject, $body, $all_headers );
    remove_filter( 'wp_mail_content_type', array( IIRS_PLUGIN_NAME, 'wp_mail_content_type' ) );

    if ( is_wp_error( $wp_mail_result )) {
      IIRS_0_debug_var_dump( $wp_mail_result );
      $ret = new IIRS_error( IIRS_REGISTRATION_EMAIL_FAILED, "Failed to send you your registration details and password. Please contact us by email if you need to login.", $wp_mail_result->get_message(), IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, array( '$email_address' => $email_address ) );
    } else {
      IIRS_0_debug_print( "no email errors reported" );
      $ret = TRUE;
    }

    return $ret; // TRUE or IIRS_Error
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
    $full_path      = IIRS__COMMON_DIR . "/$page_path";

    if ( file_exists( $full_path ) ) {
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
      require_once( IIRS__COMMON_DIR . 'translations_js.php' );
      require_once( IIRS__COMMON_DIR . 'global_js.php' );
      if ( $hide_errors ) print( '</script>' );
      // primary template
      require_once( IIRS__COMMON_DIR . $page_path );
      $content = ob_get_contents();
      ob_end_clean();
    } else {
      $content = new IIRS_Error( IIRS_URL_404, 'File not found', 'File not found in IIRS_common', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, array( '$page_path' => $page_path ) );
    }

    return $content;
  }

  public static function template_include( $template ) {
    /* we are using the_content filter by default
     * this template suggestion is included for the host to override
     * an example single-initiative_profile.php is included in templates
     */
    global $post;

    if ( $post && IIRS_0_CONTENT_TYPE == $post->post_type ) {
      $custom_template_name = NULL;
      $always_use_single    = TRUE;

      // calculate desired template name
      if ( $always_use_single || is_single() ) {
        $custom_template_name = 'single-' . IIRS_0_CONTENT_TYPE . '.php';
      }
      // TODO: templates_include: 'archive-' . IIRS_0_CONTENT_TYPE . '.php';

      if ( $custom_template_name ) {
        // look for templates in the active theme
        if ( $theme_custom_template = locate_template( array( $custom_template_name ) ) ) {
          $template = $theme_custom_template;
        } else {
          // look for templates in the IIRS plugin (CURRENTLY_NOT_USED)
          $template_iirs_plugin = IIRS__PLUGIN_DIR . "templates/$custom_template_name";
          if ( file_exists( $template_iirs_plugin ) ) {
            $template = $template_iirs_plugin;
          }
        }
      }
    }

    return $template;
  }

  public static function admin_init_option_settings() {
    // standard IIRS settings
    // http://codex.wordpress.org/Creating_Options_Pages
    register_setting( IIRS_PLUGIN_NAME, 'lang_code' );
    register_setting( IIRS_PLUGIN_NAME, 'server_country' );
    register_setting( IIRS_PLUGIN_NAME, 'region_bias' );
    register_setting( IIRS_PLUGIN_NAME, 'additional_error_notification_email' );
    register_setting( IIRS_PLUGIN_NAME, 'registration_notification_email' );

    register_setting( IIRS_PLUGIN_NAME, 'override_TI_display' );
    register_setting( IIRS_PLUGIN_NAME, 'override_TI_editing' );
    register_setting( IIRS_PLUGIN_NAME, 'override_TI_content_template' );
    register_setting( IIRS_PLUGIN_NAME, 'language_selector' );

    register_setting( IIRS_PLUGIN_NAME, 'offer_buy_domains' );
    register_setting( IIRS_PLUGIN_NAME, 'add_projects' );
    register_setting( IIRS_PLUGIN_NAME, 'advanced_settings' );
    register_setting( IIRS_PLUGIN_NAME, 'image_entry' );
    register_setting( IIRS_PLUGIN_NAME, 'thankyou_for_registering_url' );

    // Wordpress specific settings
    register_setting( IIRS_PLUGIN_NAME, 'initiatives_visibility' );
  }

  public static function admin_menu() {
    add_options_page( IIRS_PLUGIN_NAME, IIRS_PLUGIN_NAME, 'manage_options', 'IIRS', array( IIRS_PLUGIN_NAME, 'options_iirs_administrator' ) );
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
        'description' => 'Transition Initiative',

        'public'      => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'hierarchical'       => false,
        'has_archive'        => true, // creates the /initiaives list page

        'supports'           => array(
          'title', 'editor', 'author', 'thumbnail', 'excerpt',
          'comments',
          'custom-fields', // domain, location data, etc.
          // 'post-formats'
          'page-attributes',
        ),

        'rewrite'            => array( // TODO: admin settings for TI slug + translation!
          'slug'       => IIRS_0_CONTENT_TYPE_SLUG,
          'with_front' => true,        // pre-pend /initiatives/ to the post-name URL (default=true)
        ),
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
    if ( get_option( IIRS_PLUGIN_NAME . '_flush_rewrite_rules' ) || isset($_GET['IIRS-flush_rewrite_rules']) ) {
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

  public static function generate_password( $name = null ) {
    return wp_generate_password();
  }

  public static function add_user( $name, $email, $pass, $phone = NULL ) {
    // adds the user, role, etc.
    // returns the new user id or IIRS_Error
    $new_user_id = FALSE;

    if ( username_exists( $name ) || email_exists( $email )) {
      $new_user_id = new IIRS_Error( IIRS_USER_ALREADY_REGISTERED, 'There is already a user with this email or username. Please try again.', 'User already exists', IIRS_MESSAGE_USER_WARNING, IIRS_MESSAGE_NO_USER_ACTION, array( '$name' => $name, '$email' => $email ) );
    } else {
      $new_user_id = wp_create_user( $name, $pass, $email );

      if ( is_wp_error( $new_user_id ) ) {
        $new_user_id = new IIRS_Error( IIRS_USER_ALREADY_REGISTERED, 'Oops, Could not create your user account because of a system error. The manager has been informed and is trying to solve the problem. Please try again tomorrow.', $new_user_id->get_error_message(), IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, array( '$name' => $name, '$email' => $email ) );
      } elseif ( ! $new_user_id ) {
        $new_user_id = new IIRS_Error( IIRS_USER_ALREADY_REGISTERED, 'Oops, Could not create your user account because of a system error. The manager has been informed and is trying to solve the problem. Please try again tomorrow.', 'User creation failed, no indication error', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, array( '$name' => $name, '$email' => $email ) );
      } else {
        // test the login, and actually login immediately
        $wp_user = wp_signon( array(
          'user_login'    => $name,
          'user_password' => $pass,
          'remember'      => true
        ) );
        // setup the user
        // TODO; wp_signon error control...?
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
          // populate global $current_user
          wp_set_current_user( $new_user_id );
        }
      }
    }

    return $new_user_id;
  }

  public static function delete_current_user() {
    // used when the recent add user works but the add TI fails
    require_once(ABSPATH.'wp-admin/includes/user.php' );
    global $current_user;
    $ret = TRUE;

    get_currentuserinfo();
    $user_id = $current_user->ID;
    if ( $user_id ) {
      IIRS_0_debug_print( "logging out and deleting user [$user_id]" );
      wp_logout();
      if ( ! wp_delete_user( $user_id, false ) ) { // false = do not Reassign posts and links to new User ID.
        // user deletion failed, no error report
        $ret = new IIRS_Error( IIRS_FAILED_USER_DELETE, 'Could not delete the recently added user to allow re-addtion', 'User delete failed', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, (array) $current_user );
      }
    } else {
      $ret = new IIRS_Error( IIRS_FAILED_USER_DELETE, 'Could not logout and delete the current user because no current user was found to allow re-addtion. This might cause problems when trying again', 'No current User', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION );
    }

    return $ret;
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
    add_post_meta( $post_id, 'location_town_name',     $town_name, false );
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
      IIRS_0_debug_print("translated values:");
      IIRS_0_debug_var_dump( $translated_values );
      edit_post( $translated_values );
      foreach ( $translated_values['_meta'] as $meta_key => $meta_value ) {
        update_post_meta( $post_id, $meta_key, $meta_value );
      }
    } else {
      IIRS_0_debug_print( "TI not found [$TI_ID]!" );
    }
  }

  public static function update_user( $new_values ) {
    // returns native user id on success
    // returns null on failure
    global $current_user, $wpdb;
    $ret = null;
    get_currentuserinfo();

    if ( $current_user && $current_user->ID ) {
      $user_id                 = $current_user->ID;
      $translated_values       = self::translate_user_fields( $new_values );
      $translated_values['ID'] = $user_id;
      IIRS_0_debug_var_dump( $translated_values );

      // a bug in line 194 in registration.php does not include user_login as an editable field
      // here we run a direct DB change to achieve this
      if ( isset( $translated_values[ 'user_login' ] ) ) {
        $user_login = $translated_values[ 'user_login' ];
        if ( ! empty( $user_login ) && $user_login != $current_user->user_login ) {
          IIRS_0_debug_print( '** updating user_login manually with database query due to bug in wordpress...' );
          // TODO: user_login update disabled at the moment because it causes a logout.
          // the input field should be disabled currently to prevent this update
          return new IIRS_Error( IIRS_FAILED_USER_UPDATE, 'Failed to update your user details. Please try again tomorrow.', 'user_login disabled because it logs the user out. next version!', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, array( '$user_login' => $user_login ) );
          // $wpdb->update( $wpdb->users, array( 'user_login' => $user_login ), array( 'ID' => $user_id ) );
        }
      }
      $user_id = wp_update_user( $translated_values );

      if ( is_wp_error( $user_id ) ) {
        $ret = new IIRS_Error( IIRS_FAILED_USER_UPDATE, 'Failed to update your user details. Please try again tomorrow.', $user_id->get_error_message(), IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, $translated_values );
        IIRS_0_debug_print( $ret );
      } else {
        // re-populate the global $current_user
        // it is the callers responsibility to re-populate any data containing user information

        // TODO: *********** this is a HACK. we cannot get Wordpress to update its cache of user data without a page refresh
        global $wp;
        wp_redirect( "/$wp->request" );
        exit;
        // the following do not work
        // get_userdata( $user_id );
        // wp_set_current_user( $user_id );

        IIRS_0_debug_print( "user_id: $user_id" );
        IIRS_0_debug_var_dump( $current_user );
        $ret = $user_id;
      }
    } else {
      $ret = new IIRS_Error( IIRS_FAILED_USER_UPDATE, 'Failed to update your user details. Please try again tomorrow.', 'No current user for update process.', IIRS_MESSAGE_SYSTEM_ERROR, IIRS_MESSAGE_NO_USER_ACTION, $new_values );
      IIRS_0_debug_print( $ret );
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
    if ( $current_user && $current_user->ID ) {
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

  public static function TIs_nearby( $latitude, $longitude, $location_description = '', $max_TIs = IIRS_0_MAX_NEARBY ) {
    IIRS_0_set_not_supported_message( "IIRS::TIs_nearby()" );
    return array();
  }

  public static function posts_where( $where, &$wp_query ) {
    global $wpdb;
    // wp_query / get_posts() call MUST include: 'suppress_filters' => false,
    if ( $post_title = $wp_query->get( 'post_title' ) ) {
      $where .= ' AND ' . $wpdb->posts . '.post_title = \'' . esc_sql( $post_title ) . '\'';
    }
    return $where;
  }

  public static function TI_same_name( $initiative_name ) {
    // the input $town_nameBase should already be sanitised.
    // that is, stripped of transition words like "Transition"
    // use: IIRS_0_remove_transition_words( ... )
    $TI = FALSE;

    // this get_posts() call takes advantage of the wp_query post_where filter above
    // enabling the post_title parameter
    // see the IIRS::posts_where( ... ) filter above
    $posts = get_posts( array(
      'post_type'        => IIRS_0_CONTENT_TYPE,
      'post_title'       => $initiative_name,  // see the IIRS::posts_where( ... ) filter above
      'posts_per_page'   => 1,
      'offset'           => 0,
      'suppress_filters' => false,             // see the IIRS::posts_where( ... ) filter above
    ) );
    if ( count( $posts ) ) $TI = self::TI_from_post( $posts[0] );

    return $TI;
  }

  public static function TI_all( $page_size, $page_offset ) {
    // NOTE: this function runs in a <script> tags so errors will be hidden
    $all_TIs = array();

    $posts = get_posts( array(
      'post_type'      => IIRS_0_CONTENT_TYPE,
      'posts_per_page' => ( $page_size ? $page_size : 1000000 ),
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

    if ( $current_user && $current_user->ID && $current_user->data ) {
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
        //TODO: will eventually include category knowledge here also
        //  categories: TI, project, person wants to start, etc.
        //  AND then the custom marker can also then change it
        'custom_marker'         => (isset($post_meta['custom_marker']) ? $post_meta['custom_marker'][0] : ''),

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
    // returns standard TI array if found
    // returns NULL if no TI: this is not an error
    // returns an IIRS_Error if a technical error occurs
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
    $ret = FALSE;

    $login_details = array(
      'user_login'    => $name,
      'user_password' => $pass,
      'remember'      => true,
    );
    $user = wp_signon( $login_details );

    if ( is_wp_error( $user ) ) {
      $ret = new IIRS_Error( IIRS_LOGIN_FAILED, 'Login Failed', $user->get_error_message(), IIRS_MESSAGE_USER_ERROR, IIRS_MESSAGE_NO_USER_ACTION, $login_details );
    } else {
      $ret = TRUE;

      // TODO: *********** this is a HACK. we cannot get Wordpress to update its cache of user data without a page refresh
      global $wp;
      wp_redirect( "/$wp->request" );
      exit;
      // the following do not work
      // get_userdata( $user_id );
      // wp_set_current_user( $user_id );
    }

    return $ret;
  }


  public static function current_user_can( $caps, $cap, $user_id, $args ) {
    global $post;
    $allowed = false;
      IIRS_0_debug_var_dump( $caps );
      IIRS_0_debug_var_dump( $cap );
      IIRS_0_debug_var_dump( $user_id );


    if ( $post && IIRS_0_CONTENT_TYPE == $post->post_type && 'edit_post' == $cap ) {
      IIRS_0_debug_var_dump( $post->post_author );
      // $allowed = ( $post->post_author == "$user_id" );
      // $caps['edit_post'] = true;
    }
    IIRS_0_debug_var_dump( $allowed );
    return true;
  }

  // ------------------------------------------------------------------------
  // ------------------------------------------------------- page, javascript, serving and utilities
  // ------------------------------------------------------------------------
  public static function wp( $wp ) {
    // take over all the /IIRS/* requests and serve the appropriate page
    // types include bare pages, widget systems, images and optional redirects
    global $wp_query, $wp;

    if ( $wp_query && $wp_query->is_404 && $wp && $wp->request ) {
      // TODO: this whole IIRS::wp() => fake_page() part can be moved in to the IIRS_common section
      // return content & title, IIRS_Error, or demand an exit
      $request_parts = explode( '/', $wp->request ); // explode() PHP 4
      if ( IIRS_PLUGIN_NAME == $request_parts[0] ) {
        // $request_parts    = IIRS/registration/widgetloader
        // $request_parts[0] = IIRS_PLUGIN_NAME: IIRS
        // $request_parts[1] = $widget_folder:   registration / mapping / edit / etc.
        // $request_parts[2] = $page_stem:       widgetloader or index / domain_selection / etc.
        $widget_folder = ( count( $request_parts ) > 1 ? $request_parts[1] : 'registration' );
        $page_stem     = ( count( $request_parts ) > 2 ? $request_parts[2] : '' );
        $widget_is_dir = is_dir( IIRS__COMMON_DIR . $widget_folder );

        // various path identifiers and defaults
        if ( $widget_is_dir && ! $page_stem ) $page_stem = 'index';
        // some servers will restrict direct access to PHP files so URLs do not have php extensions
        $page_extension = ( ! pathinfo( $page_stem, PATHINFO_EXTENSION ) ? '.php' : '' ); // pathinfo() PHP 4 >= 4.0.3, PHP 5
        $page_path      = ( $widget_is_dir ? "$widget_folder/$page_stem$page_extension" : "$widget_folder$page_extension" );

        // ---------------------------------------------------- direct request for the widgetloader.js
        if ( strstr( $page_stem, 'widgetloader' )) {
          // widgetloader javascript will examine the $widget_folder and request the appropriate file
          // and also requests all the appropriate JavaScript and CSS for the widget based interface
          ob_clean();  // any NOTICES they made their way through
          status_header( 200 );
          require_once( IIRS__COMMON_DIR . 'widgetloader.php' );
          exit( 0 );
        }

        // ---------------------------------------------------- image request
        elseif ( 'images' == $widget_folder ) {
          // /IIRS/images/<image_stem>
          $file_extension = pathinfo( $page_stem, PATHINFO_EXTENSION );
          $mime = NULL;
          if ( ! $file_extension ) { // pathinfo() PHP 4 >= 4.0.3, PHP 5
            if (     file_exists( __DIR__ . "/IIRS_common/images/$page_stem.png" )) $file_extension = 'png';
            elseif ( file_exists( __DIR__ . "/IIRS_common/images/$page_stem.gif" )) $file_extension = 'gif';
            // TODO: image extension not found?
            $page_stem .= ".$file_extension";
          }
          switch ( $file_extension ) {
            case 'png': { $mime = 'image/png'; break; }
            case 'png': { $mime = 'image/gif'; break; }
          }
          $image_path = __DIR__ . "/IIRS_common/images/$page_stem";

          ob_clean();  // any NOTICES they made their way through
          if ( file_exists( $image_path ) ) {
            status_header( 200 );
            header( "Content-type: $mime", true );
            print( file_get_contents( $image_path ) );
          } else {
            status_header( 404 );
            // print("can't find [$image_path]");
          }
          exit( 0 );
        }

        // ---------------------------------------------------- bare pages (no header / footer etc.)
        // do not allow the system to include header and footer here.
        // much like the widget functionality
        // this is not relevant to the widget function. only direct access to teh host website
        elseif ( 'export' == $widget_folder || 'import' == $widget_folder ) {
          ob_clean(); // any NOTICES they made their way through
          status_header( 200 );
          header( 'Content-type: text/xml', true );
          require_once( IIRS__COMMON_DIR . $page_path );
          exit( 0 );
        }

        // ---------------------------------------------------- foriegn Widget content request
        // this is a bare page content request, so show only the direct content
        elseif ( 'true' == self::input( 'IIRS_widget_mode' ) ) {
          // javascript: interaction.js translations_js.php and general_interaction.js are dynamically written in to widgetloader.php
          // javascript: these responses will be dynamically added in to the HTML on the client so no need to send the JS again
          ob_clean(); // any NOTICES they made their way through
          status_header( 200 );
          require_once( IIRS__COMMON_DIR . $page_path );
          exit( 0 );
        }

        // ---------------------------------------------------- non-widget conditional redirects
        // provide some easy links to access the users TI
        // host framework links for viewing and editing
        // the widget will always use the IIRS/* versions of course
        elseif ( 'view' == $widget_folder && ! IIRS_0_setting( 'override_TI_display' ) ) {
          // we are not overriding the TI display
          // so redirect to the host framework display
          wp_redirect( IIRS_0_URL_view_TI( self::input( 'ID' ) ) );
        }
        elseif ( 'edit' == $widget_folder && ! IIRS_0_setting( 'override_TI_editing' ) ) {
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
          //
          // manual translator hints. these are processed by /IIRS_common/read_translations.php
          // IIRS_0_translation(IGNORE_TRANSLATION, 'IIRS documentation'); //manual translation: page title
          // IIRS_0_translation('IIRS edit');          //manual translation: page title
          // IIRS_0_translation('IIRS export');        //manual translation: page title
          // IIRS_0_translation(IGNORE_TRANSLATION, 'IIRS import');        //manual translation: page title
          // IIRS_0_translation('IIRS list');          //manual translation: page title
          // IIRS_0_translation('IIRS mapping');       //manual translation: page title
          // IIRS_0_translation('IIRS registration');  //manual translation: page title
          // IIRS_0_translation(IGNORE_TRANSLATION, 'IIRS search');        //manual translation: page title
          // IIRS_0_translation('IIRS view');          //manual translation: page title
          $title   = IIRS_0_translation( "IIRS $widget_folder" );
          $content = self::content( $widget_folder, $page_stem );
          if ( IIRS_is_error( $content ) ) {
            // leave the 404 to show by itself
          } else {
            ob_clean(); // any NOTICES they made their way through
            status_header( 200 );
            self::fake_page( $title, $content );
          }
        }
      }
    }
  }

  private static function input( $sKey ) {
    // return value from $_POST and $_GET arrays
    return ( isset( $_POST[$sKey] ) ? $_POST[$sKey] : ( isset( $_GET[$sKey] ) ? $_GET[$sKey] : NULL ));
  }

  public static function body_class() {
    return array( IIRS_PLUGIN_NAME );
  }

  private static function fake_page( $title, $content ) {
    // this is not a real post in Wordpress
    // but we want to allow all the usual filters and display processes
    // so create a fake "post" with the content of our target page
    global $wp_query, $wp;

    // prevent encoding of our post content
    remove_all_filters( 'the_content', 'plugin_filters' );
    wp_enqueue_style( 'IIRS_fake_page_view', plugins_url( 'IIRS/fake_page.css' ));
    add_filter( 'body_class', array( IIRS_PLUGIN_NAME, 'body_class' ) );

    // now create the fake post with our contents on it
    $id=-42; // need an id
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
    //   there is no override display of TIs and the website is using Wordpress lists and views of TIs
    // Otherwise the /IIRS/list, /IIRS/view and /IIRS/edit are used
    // This function is similar to using a WordPress theme single-initiative_profile.php template
    //   with a get_template_part( 'content', 'initiative_profile' ) call
    //   this system however, links easily and directly in to the_content
    // TODO: maybe use the template system self::content() call and link in to the /IIRS/view from there.
    global $post;

    if ( $post && IIRS_0_CONTENT_TYPE == $post->post_type ) {
      // check for a custom content-initiative_profile.php in the theme
      $theme_custom_content_template = locate_template( array( 'content-' . IIRS_0_CONTENT_TYPE . '.php' ) );
      if (  ! $theme_custom_content_template && IIRS_0_setting( 'override_TI_content_template' ) ) {
        // ok, no custom content templates, so let us do our own stuff
        // so we must manually include css and javascript that we want
        wp_enqueue_style(  'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ));
        wp_enqueue_script( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general_interaction.js' ));

        $TI          = self::TI_from_post( $post );
        $list_mode   = ! is_single(); // is this the full page TI or in a list?

        ob_start(); // ob_start() PHP 4, PHP 5
        $hide_errors = true;
        if ( $hide_errors ) print( '<script type="text/javascript">' );
        require_once( IIRS__COMMON_DIR . 'translations_js.php' );
        require_once( IIRS__COMMON_DIR . 'global_js.php' );
        if ( $hide_errors ) print( '</script>' );
        include( 'IIRS_common/view/index.php' );
        $content = ob_get_contents();
        ob_end_clean();
      }
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
          case 'town_name':           {$translated_values['_meta']['location_town_name']     = $value; break;}
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

    // TODO: admin notice if wp-settings does not contain wp_magic_quotes()
    // if ( strstr( file_get_contents( ABSPATH . 'wp-settings.php' ), 'wp_magic_quotes()' )  === FALSE )

    // default option settings
    add_option( 'override_ti_editing', '1' );
    add_option( 'override_ti_content_template', '1' );

    // recommended plugins
    add_option( IIRS_PLUGIN_NAME . '_recommend_plugins', true );
  }

  public static function admin_notices() {
    if ( get_option( IIRS_PLUGIN_NAME . '_show_activation_message' ) ) {
      delete_option( IIRS_PLUGIN_NAME . '_show_activation_message' );
      print( '<div class="updated"><p>checkout the <a href="/wp-admin/options-general.php?page=IIRS">IIRS settings page</a> for contacts, documentation, options and setup</p></div>' );
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
      print( 'Please <a href="https://codex.wordpress.org/Upgrading_WordPress">upgrade WordPress</a> to a current version.</p></div>' );
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