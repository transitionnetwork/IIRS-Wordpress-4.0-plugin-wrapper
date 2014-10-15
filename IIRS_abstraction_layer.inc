<?php
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// ------------------------------------------------------------------------
// --------------------------------------------------- IIRS_common abstraction layer
// ------------------------------------------------------------------------
function IIRS_0_translation( $string_to_translate ) {
  return IIRS::translate( $string_to_translate );
}

function IIRS_0_current_path() {
  global $wp;
  return $wp->request;
}

function IIRS_0_available_languages() {
  return array( 'en', 'es', '( dev! )' );
}

function IIRS_0_setting( $setting ) {
  // TODO: need to replace this IIRS_0_setting() with get_option() when the defauls are registered
  switch ($setting) {
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
    default: return false;
  }
}

function IIRS_0_http_request( $url, $timeout = 2.0, $var_dump_non_200 = FALSE ) {
  global $wp_version;

  $args = array(
    'timeout'     => $timeout,
    'redirection' => 0,
    'httpversion' => '1.0',
    'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
    'blocking'    => true,
    'headers'     => array(),
    'cookies'     => array(),
    'body'        => null,
    'compress'    => false,
    'decompress'  => true,
    'sslverify'   => true,
    'stream'      => false,
    'filename'    => null
  );
  $response = wp_remote_get( $url, $args );
  $body     = wp_remote_retrieve_body( $response );

  return $body;
}

function IIRS_0_set_message( $message, $IIRS_widget_mode = true ) {
  if ( $IIRS_widget_mode ) {
    print( "<div class=\"IIRS_0_message\">$message</div>" );
  } else {
    // needs to be a function! add_action( 'user_admin_notices', $message );
  }
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- create and update user and TI
// ------------------------------------------------------------------------
function IIRS_0_TI_add_user( $name, $email, $pass, $phone ) {
  return IIRS::add_user( $name, $email, $pass, $phone );
}

function IIRS_0_delete_user( $user_ID ) {
  return IIRS::delete_user( $user_ID );
}

function IIRS_0_TI_add_TI( $user_ID, $registering_server, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address = '',  $location_granuality = '', $location_bounds = '', $domain = '' ) {
  return IIRS::add_TI( $user_ID, $registering_server, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address,  $location_granuality, $location_bounds, $domain );
}

function IIRS_0_next_initnumber() {
  print( 'IIRS_0_next_initnumber() not supported' );
  exit( 0 );
}

function IIRS_0_TI_update_TI( $values ) {
  return IIRS::update_TI( $values );
}

function IIRS_0_TI_update_user( $values ) {
  return IIRS::update_user( $values );
}

function IIRS_0_URL_view_TI($TI_ID = null) {
  return IIRS::URL_view_TI($TI_ID);
}

function IIRS_0_URL_edit_TI($TI_ID = null) {
  return IIRS::URL_edit_TI($TI_ID);
}

function IIRS_0_HTML_editor($content, $HTML_ID) {
  wp_editor( $content, $HTML_ID );
}


// ------------------------------------------------------------------------
// ------------------------------------------------------- querying the TIs
// ------------------------------------------------------------------------
function IIRS_0_TIs_all( $page_size = 0, $page_offset = 0 ) {
  return IIRS::TI_all( $page_size, $page_offset );
}

function IIRS_0_TIs_viewport() {
  print( 'IIRS_0_TIs_viewport() function not supported yet' );
  exit( 0 );
}

function IIRS_0_TIs_nearby( $latitude, $longitude, $location_description = '', $max_TIs = IIRS_0_MAX_NEARBY ) {
  print( "IIRS_0_TIs_nearby() function not supported yet\n" );
}

function IIRS_0_details_user() {
  return IIRS::details_user();
}

function IIRS_0_details_TI_page() {
  return IIRS::TI_from_current_post();
}

function IIRS_0_details_TI_user() {
  return IIRS::TI_from_current_user();
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- authentication
// ------------------------------------------------------------------------
function IIRS_0_logged_in() {
  return IIRS::logged_in();
}

function IIRS_0_login( $name, $pass ) {
  return IIRS::login();
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- misc
// ------------------------------------------------------------------------
function IIRS_0_framework() {
  return 'WordPress';
}

function IIRS_0_locale() {
  return get_locale();
}

function IIRS_0_languages() {
  // $language_filenames will have partial filenames like IIRS-en_EN in it
  // we sanitize this to the language code, e.g. en_EN
  $language_filenames = get_available_languages( dirname( __FILE__ ) . '/languages' );
  $language_codes     = array();
  foreach ( $language_filenames as $filename ) {
    array_push( $language_codes, substr( $filename, 5 ) );
  }
  return $language_codes;
}
?>