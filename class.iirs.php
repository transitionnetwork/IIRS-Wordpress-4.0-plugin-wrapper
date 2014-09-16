<?php
defined('ABSPATH') or die("No script kiddies please!");

//--------------------------------------------------------------------------
//add filters directly to make sure they are all caught
add_action('wp',               array(IIRS_PLUGIN_NAME, 'wp'));
add_action('init',             array(IIRS_PLUGIN_NAME, 'plugins_loaded')); //plugins_loaded didn't work!
add_filter('format_strings',   array(IIRS_PLUGIN_NAME, 'format_strings'));
add_action('pre_get_posts',    array(IIRS_PLUGIN_NAME, 'pre_get_posts'));
add_action('admin_menu',       array(IIRS_PLUGIN_NAME, 'admin_menu'));
//register_nav_menu( 'iirs', IIRS_PLUGIN_NAME );
add_action('user_register',    array(IIRS_PLUGIN_NAME, 'user_register'));
//add_action('after_setup_theme',array(IIRS_PLUGIN_NAME, 'after_setup_theme'));
//using the content filter, not a full separate template here
//add_filter('template_include', array(IIRS_PLUGIN_NAME, 'template_inlcude'));
add_filter('the_content',      array(IIRS_PLUGIN_NAME, 'the_content'));

//--------------------------------------------------------------------------
class IIRS {
  private static $initiated = false;
  private static $wp_category_id = NULL;

  public static function init() {
    if ( ! self::$initiated ) {
      self::$initiated = true;

      //print(get_locale());exit(0); // = it_IT, en_US etc.
      load_plugin_textdomain(IIRS_PLUGIN_NAME, FALSE,  IIRS_PLUGIN_NAME . '/languages/');
      self::create_post_type();
      //Wordpress framework integration
      add_shortcode('iirs-registration', array(IIRS_PLUGIN_NAME, 'shortcode_iirs_registration'));
      add_shortcode('iirs-mapping',      array(IIRS_PLUGIN_NAME, 'shortcode_iirs_mapping'));
      wp_register_sidebar_widget('iirs-registration', 'Register your Initiative', array(IIRS_PLUGIN_NAME, 'widget_iirs_registration'));
    }
  }

  public static function format_strings($strings) {
    //add a post-format to the list
    //requires overridden core to add the apply_filters() to post
    $strings['test'] = _x( 'Test',    'Post format' );
    return $strings;
  }

  public static function __($sString) {
    return "q:" . __($sString, IIRS_PLUGIN_NAME);
  }

  public static function user_register($user_id) {
    $user_info = get_userdata( $user_id );
    $email     = $user_info->data->user_email;
    if (strstr($email, 'annesley') !== FALSE) $email = 'annesley_newholm@yahoo.it';
    wp_mail($email, 'your new WordPress account', sprintf('username: %s password: %s', $user_info->data->user_login, $user_info->data->user_pass));
  }

  public static function shortcode_iirs_registration($atts) {
    return self::content('registration', 'index', $atts);
  }
  public static function shortcode_iirs_mapping($atts) {
    return self::content('mapping', 'index', $atts);
  }
  public static function widget_iirs_registration($args) {
    print(self::content('registration', 'index', $args));
  }

  public static function content($widget_folder, $page_stem, $atts = array()) {
    if (!$page_stem) $page_stem = "index.php";
    $page_extension = (!pathinfo($page_stem, PATHINFO_EXTENSION) ? '.php' : ''); //pathinfo() PHP 4 >= 4.0.3, PHP 5
    $page_path      = "$widget_folder/$page_stem$page_extension";

    //static JavaScript and CSS
    //javascript: do not add popup.php because it will override the form submits and show the popups
    //javascript: need to add all JS to every page here because each one is new
    wp_enqueue_script('jquery');
    if (file_exists(IIRS__PLUGIN_DIR . "/IIRS_common/$widget_folder/general_interaction.js"))
      wp_enqueue_script('IIRS_widget_folder_custom', plugins_url("IIRS/IIRS_common/$widget_folder/general_interaction.js"));
    wp_enqueue_script('IIRS_general', plugins_url('IIRS/IIRS_common/general_interaction.js'));
    wp_enqueue_style( 'IIRS_general', plugins_url('IIRS/IIRS_common/general.css'));

    //build page into the ob stream to trap the contents
    $hide_errors = true;
    ob_start(); //ob_start() PHP 4, PHP 5
    //PHP driven translations for js
    if ($hide_errors) print('<script type="text/javascript">');
    require_once('translations_js.php');
    require_once('global_js.php');
    if ($hide_errors) print('</script>');
    //primary template
    require_once($page_path);
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }

  public static function template_inlcude($template) {
    /* NOT_CURRENTLY_USED; we are using the_content filter instead */
    global $post;

    if ($post && is_single() && $post->post_type == IIRS_0_CONTENT_TYPE) {
      $template_name  = 'single-' . IIRS_0_CONTENT_TYPE . '.php';
      $template_theme = locate_template(array( 'plugin_template/' . $template_name));
      if (empty($template_theme)) {
        $template_iirs_plugin = IIRS__PLUGIN_DIR . "templates/$template_name";
        if (file_exists($template_iirs_plugin)) $template = $template_iirs_plugin;
      }
    }

    return $template;
  }

  public static function admin_menu() {
    add_option('initiatives visibility', 'everywhere', false, false);
    add_options_page(IIRS_PLUGIN_NAME, IIRS_PLUGIN_NAME, NULL, 'iirs', array(IIRS_PLUGIN_NAME, 'settings_page'));
  }

  public static function settings_page() {
    print('<h1>in progress...</h1>');
  }

  public static function pre_get_posts($query) {
    //add_transition_initiatives_to_query
    //http://codex.wordpress.org/Post_Types
    //this will add transition_initiatives in to the standard list of posts on the home page
    //TODO: admin settings to disable this
    //use is_home() to limit this to only the home page
    //NOTE: some queries want a singular post type
    //  in these cases post_type is already completed as a string
    //  NOTICEs will be thrown if we set an array where a string is expected
    if (get_option('initiatives visibility') == 'everywhere') {
      if ($query->is_main_query()) {
        $post_type = $query->get('post_type');
        if (empty($post_type)) {
          $query->set('post_type', array('post', 'movie', 'page', IIRS_0_CONTENT_TYPE));
        }
      }
    }

    return $query;
  }

  //------------------------------------------------------------------------
  //------------------------------------------------------- create and update
  //------------------------------------------------------------------------
  public static function create_post_type() {
    //http://codex.wordpress.org/Post_Types
    global $is_home_domain;

    //create a category for our posts
    //need to include the wp-admin/includes/taxonomy.php
    //TODO: admin settings to disable this
    self::$wp_category_id = wp_create_category(IIRS_0_POST_CATEGORY);

    //IIRS_deployments post-type is the list of foreign websites that are using
    //  plugins / modules and javascript widgets from this system
    //in reality only TN.org will be serving these
    //and all plugins / modules / javascript widgets will register on TN.org
    $primary_deployment_server = $is_home_domain;
    if ($primary_deployment_server) {
      $post_type_name        = 'IIRS deployment';
      $post_type_name_plural = "{$post_type_name}s";
      $post_type_short       = 'deployments';
      register_post_type( 'iirs_deployment',
        array(
          'label'  => __($post_type_name_plural),
          'labels' => array(
            'name' => __($post_type_name_plural),
            'singular_name' => __($post_type_name),
            'menu_name'          => __($post_type_name_plural),
            'name_admin_bar'     => __($post_type_name_plural, 'add new on admin bar'),
            'add_new'            => __('Add New', $post_type_short),
            'add_new_item'       => __("Add New $post_type_name"),
            'new_item'           => __("New $post_type_name"),
            'edit_item'          => __("Edit $post_type_name"),
            'view_item'          => __("View $post_type_name"),
            'all_items'          => __("All $post_type_name_plural"),
            'search_items'       => __("Search $post_type_name_plural"),
            'parent_item_colon'  => __("Parent $post_type_name_plural:"),
            'not_found'          => __("No $post_type_short found."),
            'not_found_in_trash' => __("No $post_type_short found in Trash."),
          ),
          'description' => 'IIRS deployment',

          'show_ui'            => true,
          'show_in_menu'       => true,
          'query_var'          => true,

          'supports'           => array(
            'title', 'editor', 'author', 'comments', 'custom-fields'
          ),

          'menu_position'      => 20, //below Pages
          'menu_icon'          => 'dashicons-video-alt', //TODO: make one!
          'capability_type'    => 'post',
          'capabilities'       => array(
            'edit_post' => 'edit_iirs_deployment',
          ),
        )
      );
    }

    //these are the actual TIs.
    //custom fields are added with the actual wp_insert_post()
    //custom fields are defined per-post, not with the post-type...
    $post_type_name        = 'Initiative';
    $post_type_name_plural = "{$post_type_name}s";
    register_post_type( IIRS_0_CONTENT_TYPE,
      array(
        'label'  => __( $post_type_name_plural ),
        'labels' => array(
          'name' => __( $post_type_name_plural ),
          'singular_name' => __( $post_type_name ),
          'menu_name'          => __($post_type_name_plural),
          'name_admin_bar'     => __($post_type_name_plural, 'add new on admin bar'),
          'add_new'            => __('Add New', 'initiative'),
          'add_new_item'       => __("Add New $post_type_name"),
          'new_item'           => __("New $post_type_name"),
          'edit_item'          => __("Edit $post_type_name"),
          'view_item'          => __("View $post_type_name"),
          'all_items'          => __("All $post_type_name_plural"),
          'search_items'       => __("Search $post_type_name_plural"),
          'parent_item_colon'  => __("Parent $post_type_name_plural:"),
          'not_found'          => __('No initiatives found.'),
          'not_found_in_trash' => __('No initiatives found in Trash.'),
        ),
        'description' => 'Transition Town intiative',

        'public'      => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'hierarchical'       => false,
        'has_archive'        => true,

        'supports'           => array(
          'title', 'editor', 'author', 'thumbnail', 'excerpt',
          'comments',
          'custom-fields', //domain, location data, etc.
          //'post-formats'
          'page-attributes',
        ),

        //'rewrite'            => array('slug' => IIRS_0_CONTENT_TYPE_SLUG), //TODO: admin settings for TI slug
        'menu_position'      => 20, //below Pages
        'menu_icon'          => 'dashicons-video-alt', //TODO: make one!
        'taxonomies'         => array('category', 'post_tag'),

        'capability_type'    => 'post',
        'capabilities'       => array(
          'edit_published_posts' => 'edit_published_' . IIRS_0_CONTENT_TYPE . 's',
          'edit_post' => 'edit_' . IIRS_0_CONTENT_TYPE,
          'edit_others_posts' => 'edit_others_' . IIRS_0_CONTENT_TYPE . 's',
        ),
        'map_meta_cap' => true,
      )
    );
    //post editing fails without this!
    //10 = default priority
    //4  = parameter count for map_meta_cap
    //add_filter( 'map_meta_cap', array(IIRS_PLUGIN_NAME, 'current_user_can'), 10, 4 );

    //update the subscriber role to control own posts
    $role = get_role('subscriber');
    $role->add_cap('edit_published_' . IIRS_0_CONTENT_TYPE . 's');
    $role->add_cap('edit_' . IIRS_0_CONTENT_TYPE);

    //allow administrator all capabilities on the new post-type
    $role = get_role('administrator');
    $role->add_cap('edit_published_' . IIRS_0_CONTENT_TYPE . 's');
    $role->add_cap('edit_' . IIRS_0_CONTENT_TYPE);
    $role->add_cap('edit_others_' . IIRS_0_CONTENT_TYPE . 's');
  }

  public static function add_user($name, $email, $pass, $phone) {
    $user_id = username_exists($name);
    if ($user_id || email_exists($email)) {
      $user_id = NULL;
    } else {
      if (empty($pass)) $pass = wp_generate_password();
      if (empty($pass)) $pass = wp_generate_password();
      $user_id = wp_create_user($name, $pass, $email);
      $user    = wp_signon(array(
        'user_login' => $name,
        'user_password' => $pass,
        'remember' => true,
        'role' => 'subscriber'
      ));
    }

    return $user_id;
  }

  public static function add_ti($userID, $initiative_name, $townname, $place_centre_lat, $place_centre_lng, $place_description, $place_country, $domain) {
    //now create the fake post with our contents on it
    $post = new stdClass();
    $post->post_author   = $userID;
    //$post->post_date     = '2014-07-10 14:45:58';
    //$post->post_date_gmt = '2014-07-10 14:45:58';
    $post->post_content  = ''; //The full text of the post.
    $post->post_title    = $initiative_name; //The title of your post.
    $post->post_excerpt  = "$initiative_name Transition Town"; //For all your post excerpt needs.
    $post->post_status   = 'publish'; //Set the status of the new post.
    $post->comment_status= 'closed'; //Set the status of the new post.
    $post->ping_status   = 'open'; //Set the status of the new post.
    $post->post_password = ''; //Set the status of the new post.
    $post->post_name     = "$initiative_name Transition Town"; //Set the status of the new post.
    $post->to_ping       = ''; //Set the status of the new post.
    $post->pinged        = ''; //Set the status of the new post.
    //$post->post_modified = '2014-07-10 14:45:58';
    //$post->post_modified_gmt = '2014-07-10 14:45:58';
    $post->post_content_filtered = '';
    $post->post_parent   = 0;
    $post->guid          = 'http://wp39example.dev/?p=1';
    $post->menu_order    = 0;
    $post->post_type     = IIRS_0_CONTENT_TYPE;
    $post->post_mime_type= '';
    $post->comment_count = '1';
    $post->filter        = 'raw';
    $post->post_category = array(self::$wp_category_id); //Add some categories. an array()???

    $post_id = wp_insert_post($post);

    //custom fields
    add_post_meta($post_id, 'location_latitude',    $place_centre_lat, false);
    add_post_meta($post_id, 'location_longitude',   $place_centre_lng, false);
    add_post_meta($post_id, 'location_townname',    $townname, false);
    add_post_meta($post_id, 'location_description', $place_description, false);
    add_post_meta($post_id, 'location_country',     $place_country, false);
    add_post_meta($post_id, 'domain',               $domain, false);

    return $post_id;
  }

  public static function update_ti($aValues) {
    global $current_user;
    get_currentuserinfo();

    if ($current_user) {
      $posts_array = get_posts(array(
        'post_type'      => IIRS_0_CONTENT_TYPE,
        'author'         => $current_user->ID,
        'posts_per_page' => 1,
        'offset'         => 0,
      ));
      if (count($posts_array)) {
        $post    = $posts_array[0];
        $post_id = $post->ID;

        $aTranslatedValues = _IIRS_0_TI_translateTIFields($aValues);
        $aTranslatedValues['post_ID']   = $post_id;
        $aTranslatedValues['post_type'] = $post->post_type;
        var_dump($aTranslatedValues);
        edit_post($aTranslatedValues);
        foreach ($aTranslatedValues['_meta'] as $meta_key => $meta_value) {
          update_post_meta($post_id, $meta_key, $meta_value);
        }
      } else {
        print("TI not found for user [$current_user->ID]!\n");
      }
    } else {
      print("User not found!\n");
    }
  }

  public static function update_user($aValues) {
    global $current_user;
    get_currentuserinfo();
    $aTranslatedValues = _IIRS_0_TI_translateUserFields($aValues);
    $aTranslatedValues['ID'] = $current_user->ID;
    //TODO: a bug in line 194 in registration.php does not include user_login as an editable field
    wp_update_user($aTranslatedValues);
  }

  //------------------------------------------------------------------------
  //------------------------------------------------------- query
  //------------------------------------------------------------------------
  public static function ti_all($page_size, $page_offset) {
    //NOTE: this function runs in a <script> tags so errors will be hidden
    $aTIs = array();

    $posts = get_posts(array(
      'post_type'      => IIRS_0_CONTENT_TYPE,
      'posts_per_page' => $page_size,
      'offset'         => $page_offset,
    ));

    foreach ($posts as $TI) {
      array_push(
        $aTIs,
        array(
          'name'     => $TI->post_title,
          'summary'  => $TI->post_content,
          'domain'   => $TI->domain,
          'location_latitude'    => floatval($TI->location_latitude),
          'location_longitude'   => floatval($TI->location_longitude),
          'location_description' => $TI->location_description,
          'location_country'     => $TI->location_country,

          'language' => '',
          'status'   => '',
          'date'     => $TI->post_date,
        )
      );
    }

    return $aTIs;
  }

  public static function details_user() {
    global $current_user;
    get_currentuserinfo();
    $aUser = NULL;

    if ($current_user) {
      $user_id        = $current_user->ID;
      $aUser['name']  = $current_user->data->user_login;
      $aUser['email'] = $current_user->data->user_email;
    }

    return $aUser;
  }

  public static function details_ti($post) {
    $aTI = NULL;

    if ($post) {
      $meta_single = true;
      $aTI            = array();
      $post_id        = $post->ID;
      $aTI['name']    = $post->post_title;
      $aTI['summary'] = $post->post_content;
      $aTI['domain']               = get_post_meta($post_id, 'domain', $meta_single);
      $aTI['location_latitude']    = get_post_meta($post_id, 'location_latitude', $meta_single);
      $aTI['location_longitude']   = get_post_meta($post_id, 'location_longitude', $meta_single);
      $aTI['location_description'] = get_post_meta($post_id, 'location_description', $meta_single);
      $aTI['location_country']     = get_post_meta($post_id, 'location_country', $meta_single);
    }

    return $aTI;
  }

  public static function details_ti_page() {
    global $post;
    return self::details_ti($post);
  }

  public static function details_ti_user() {
    global $current_user;
    get_currentuserinfo();
    if ($current_user) {
      $posts_array = get_posts(array(
        'post_type'      => IIRS_0_CONTENT_TYPE,
        'author'         => $current_user->ID,
        'posts_per_page' => 1,
        'offset'         => 0,
      ));
      $post = $posts_array[0];
    }
    return self::details_ti($post);
  }

  //------------------------------------------------------------------------
  //------------------------------------------------------- authentication
  //------------------------------------------------------------------------
  public static function logged_in() {
    return is_user_logged_in();
  }

  public static function login($name, $pass) {
    $user = wp_signon(array('user_login' => $name, 'user_password' => $pass, 'remember' => true));
  }


  public static function current_user_can($caps, $cap, $user_id, $args) {
    global $post;
    $allowed = false;
      var_dump($caps);
      var_dump($cap);
      var_dump($user_id);


    if ($post && $post->post_type == IIRS_0_CONTENT_TYPE && $cap == 'edit_post') {
      var_dump($post->post_author);
      //$allowed = ($post->post_author == "$user_id");
      //$caps['edit_post'] = true;
    }
    var_dump($allowed);
    return true;
  }

  //------------------------------------------------------------------------
  //------------------------------------------------------- page, javascript, serving and utilities
  //------------------------------------------------------------------------
  public static function wp($wp) {
    global $wp_query, $wp;

    if ($wp_query->is_404) {
      $request_parts = explode('/', $wp->request); //explode() PHP 4
      if (count($request_parts) >= 2 && $request_parts[0] == IIRS_PLUGIN_NAME) {
        status_header(200);
        $widget_folder = $request_parts[1];
        $page_stem     = (count($request_parts) > 2 ? $request_parts[2] : '');

        //various path identifiers and defaults
        if (!$page_stem) $page_stem = 'index';
        //some servers will restrict direct access to PHP files so URLs do not have php extensions
        $page_extension = (!pathinfo($page_stem, PATHINFO_EXTENSION) ? '.php' : ''); //pathinfo() PHP 4 >= 4.0.3, PHP 5
        $page_path      = "$widget_folder/$page_stem$page_extension";

        //---------------------------------------------------- direct request for the widgetloader.js
        if (strstr($page_stem, 'widgetloader')) {
          //widgetloader javascript will examine the $widget_folder and request the appropriate file
          require_once('IIRS_common/widgetloader.php');
          exit(0);
        }

        //---------------------------------------------------- image request
        elseif ($widget_folder == 'images') {
          $file_extension = pathinfo($page_stem, PATHINFO_EXTENSION);
          if (!$file_extension) { //pathinfo() PHP 4 >= 4.0.3, PHP 5
            if (    file_exists(__DIR__ . "/IIRS_common/images/$page_stem.png")) $file_extension = 'png';
            elseif (file_exists(__DIR__ . "/IIRS_common/images/$page_stem.gif")) $file_extension = 'gif';
            //TODO: image extension not found?
            $page_stem .= ".$file_extension";
          }
          switch ($file_extension) {
            case 'png': {$mime = 'image/png'; break;}
            case 'png': {$mime = 'image/gif'; break;}
          }
          $image_path = __DIR__ . "/IIRS_common/images/$page_stem";

          header("Content-type: $mime", true);
          print(file_get_contents($image_path));
          exit(0);
        }

        //---------------------------------------------------- foriegn Widget content request
        //this is a bare page content request, so show only the direct content
        elseif (self::input('IIRS_widget_mode') == 'true') {
          //javascript: interaction.js translations_js.php and general_interaction.js are dynamically written in to widgetloader.php
          //javascript: these responses will be dynamically added in to the HTML on the client so no need to send the JS again
          require_once($page_path);
          exit(0);
        }

        //---------------------------------------------------- Wordpress full page request
        //TODO: feed this page display code layout back in to the IIRS Drupal Module
        //this will be a normal Wordpress page request, so return a fake post
        //curcumventing the template system here because we want to maintain similar markup
        else {
          //static JavaScript and CSS
          //javascript: do not add popup.php because it will override the form submits and show the popups
          //javascript: need to add all JS to every page here because each one is new
          $title   = IIRS_0_translation("IIRS $widget_folder");
          $content = self::content($widget_folder, $page_stem);
          self::fake_page($title, $content);
        }
      }
    }
  }

  private static function input($sKey) {
    return (isset($_POST[$sKey]) ? $_POST[$sKey] : (isset($_GET[$sKey]) ? $_GET[$sKey] : NULL));
  }

  private static function fake_page($title, $content) {
    //this is not a real post in Wordpress
    //but we want to allow all the usual filters and display processes
    //so create a fake "post" with the content of our target page
    global $wp_query, $wp;

    //prevent encoding of our post content
    remove_all_filters('the_content', 'plugin_filters');
    wp_enqueue_style('IIRS_fake_page_view', plugins_url('IIRS/fake_page.css'));

    //now create the fake post with our contents on it
    $id=-42; // need an id: TODO: is this reasonable?
    $post = new stdClass();
    $post->ID            = $id;
    $post->post_author   = '1';
    $post->post_date     = '2014-07-10 14:45:58';
    $post->post_date_gmt = '2014-07-10 14:45:58';
    $post->post_content  = $content; //The full text of the post.
    $post->post_title    = $title; //The title of your post.
    $post->post_excerpt  = "IIRS: $title"; //For all your post excerpt needs.
    $post->post_status   = 'publish'; //Set the status of the new post.
    $post->comment_status= 'closed'; //Set the status of the new post.
    $post->ping_status   = 'open'; //Set the status of the new post.
    $post->post_password = ''; //Set the status of the new post.
    $post->post_name     = $title; //Set the status of the new post.
    $post->to_ping       = ''; //Set the status of the new post.
    $post->pinged        = ''; //Set the status of the new post.
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

    //alter the wp_query
    $wp_query->queried_object = $post;
    $wp_query->post           = $post;
    $wp_query->found_posts    = 1;
    $wp_query->post_count     = 1;
    $wp_query->max_num_pages  = 1;
    $wp_query->is_single      = 1;
    $wp_query->is_404         = false;
    $wp_query->is_posts_page  = 1;
    $wp_query->posts          = array($post);
    $wp_query->page           = false;
    $wp_query->is_post        = true;
  }

  public static function the_content($content) {
    //this is within a display frame not controlled by menu_links()
    //extra integration with WordPress :)
    //so we must manually include css and javascript that we want
    global $post;

    if ($post && $post->post_type == IIRS_0_CONTENT_TYPE) {
      wp_enqueue_style('IIRS_general', plugins_url('IIRS/IIRS_common/general.css'));
      //this additional css file alters the display slightly because WordPress will present the titles already
      wp_enqueue_style('IIRS_inline_view', plugins_url('IIRS/inline_view.css'));
      ob_start(); //ob_start() PHP 4, PHP 5
      include('view/index.php');
      $content = ob_get_contents();
      ob_end_clean();
    }

    return $content;
  }

  //------------------------------------------------------------------------
  //------------------------------------------------------- misc
  //------------------------------------------------------------------------
  public static function view( $name, array $args = array() ) {
    //copied from the AKISMET plugin. nice bit of code, but not currently used
    $args = apply_filters( 'iirs_view_arguments', $args, $name );

    foreach ( $args AS $key => $val ) {
      $$key = $val;
    }

    load_plugin_textdomain( 'iirs' );

    $file = IIRS__PLUGIN_DIR . 'views/'. $name . '.php';

    include( $file );
  }

  /**
   * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
   * @static
   */
  public static function plugin_activation() {
    if ( version_compare( $GLOBALS['wp_version'], IIRS__MINIMUM_WP_VERSION, '<' ) ) {
      load_plugin_textdomain( 'iirs' );

      $message = '<strong>'.sprintf(esc_html__( 'IIRS %s requires WordPress %s or higher.' , 'iirs'), IIRS_VERSION, IIRS__MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version.', 'iirs'), 'https://codex.wordpress.org/Upgrading_WordPress', 'http://wordpress.org/extend/plugins/akismet/download/');
    }
  }

  /**
   * Removes all connection options
   * @static
   */
  public static function plugin_deactivation( ) {
    //tidy up
  }

  public static function log( $iirs_debug ) {
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG )
      error_log( print_r( compact( 'iirs_debug' ), 1 ) ); //send message to debug.log when in debug mode
  }
}