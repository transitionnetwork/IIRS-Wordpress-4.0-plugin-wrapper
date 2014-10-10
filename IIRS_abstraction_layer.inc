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

function IIRS_0_TI_add_TI( $user_ID, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address = '',  $location_granuality = '', $location_bounds = '', $domain = '' ) {
  return IIRS::add_TI( $user_ID, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address,  $location_granuality, $location_bounds, $domain );
}

function IIRS_0_next_initnumber() {
  print( 'IIRS_0_next_initnumber() not supported' );
  exit( 0 );
}

function IIRS_0_TI_update_TI( $values ) {
  return IIRS::update_ti( $values );
}

function IIRS_0_TI_update_user( $values ) {
  return IIRS::update_user( $values );
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- querying the TIs
// ------------------------------------------------------------------------
function IIRS_0_TIs_all( $page_size = 0, $page_offset = 0 ) {
  return IIRS::ti_all( $page_size, $page_offset );
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
?>