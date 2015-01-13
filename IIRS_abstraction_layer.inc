<?php
/* Copyright 2015, 2016 Transition Network ltd
 * This program is distributed under the terms of the GNU General Public License
 * as detailed in the COPYING file included in the root of this plugin
 */
?>

<?php
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

// ------------------------------------------------------------------------
// --------------------------------------------------- IIRS_common abstraction layer
// ------------------------------------------------------------------------
function IIRS_0_translation( $string_to_translate, $reason = FALSE ) {
  return IIRS::translate( $string_to_translate );
}

function IIRS_0_current_path() {
  global $wp;
  return $wp->request;
}

function IIRS_0_setting( $setting ) {
  return IIRS::setting( $setting );
}

function IIRS_0_http_request( $url, $post_array = null, $timeout = 2.0, $ua = null ) {
  return IIRS::http_request( $url, $post_array, $timeout, $ua );
}

function IIRS_0_set_message( $mess_no, $message, $message_detail = null, $level = IIRS_MESSAGE_USER_INFORMATION, $user_action = null, $args = null ) {
  return IIRS::set_message( $mess_no, $message, $message_detail, $level, $user_action, $args );
}

function IIRS_0_send_email( $email_address, $subject, $body, $add_headers = array() ) {
  return IIRS::send_email( $email_address, $subject, $body, $add_headers );
}

function IIRS_0_input( $key, $raw = FALSE ) {
  // use IIRS_RAW_USER_INPUT constant to indicate TRUE for $raw
  // this function needs to return the escaped value, unless asked otherwise
  // PHP may have already escaped it with get_magic_quotes_gpc
  // the framework may have re-written the super-globals $_POST
  //   e.g. Wordpress load.php does this
  // IIRS_0_input(town_name) = o\'brien
  // IIRS_0_input(town_name, true) = o'brien

  // Wordpress applies wp_magic_quotes(); in wp-settings.php and does not tell anyone
  // see wp-includes/load.php to see how it does this
  // TODO: cache look to see if wp_magic_quotes() is actually in wp-settings.php
  $value_with_slashes = ( isset( $_POST[$key] ) ? $_POST[$key] : ( isset($_GET[$key] ) ? $_GET[$key] : NULL ) );
  $final_value        = NULL;
  if     ( $raw ) $final_value = stripslashes( $value_with_slashes );
  else            $final_value = $value_with_slashes;

  return $final_value;
}

function IIRS_0_debug() {
  return (defined('WP_DEBUG') && WP_DEBUG) || isset($_GET['debug-IIRS']);
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- create and update user and TI
// ------------------------------------------------------------------------
function IIRS_0_TI_add_user( $name, $email, $pass, $phone = null ) {
  return IIRS::add_user( $name, $email, $pass, $phone );
}

function IIRS_0_generate_password( $name = null ) {
  return IIRS::generate_password( $name );
}

function IIRS_0_delete_current_user() {
  return IIRS::delete_current_user();
}

function IIRS_0_TI_add_TI( $user_ID, $registering_server, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address = '',  $location_granuality = '', $location_bounds = '', $domain = '' ) {
  return IIRS::add_TI( $user_ID, $registering_server, $initiative_name, $town_name, $location_latitude, $location_longitude, $location_description, $location_country, $location_full_address,  $location_granuality, $location_bounds, $domain );
}

function IIRS_0_next_initnumber() {
  IIRS_0_set_not_supported_message( 'IIRS_0_next_initnumber' );
  return NULL;
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

function IIRS_0_TIs_nearby( $latitude, $longitude, $location_description = '', $max_TIs = IIRS_0_MAX_NEARBY ) {
  return IIRS::TIs_nearby( $latitude, $longitude, $location_description, $max_TIs );
}

function IIRS_0_TI_same_name( $initiative_name ) {
  return IIRS::TI_same_name( $initiative_name );
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
  return IIRS::login( $name, $pass );
}

// ------------------------------------------------------------------------
// ------------------------------------------------------- misc
// ------------------------------------------------------------------------
function IIRS_0_framework_name() {
  return 'WordPress';
}

function IIRS_0_locale() {
  return IIRS::locale();
}

function IIRS_0_available_languages() {
  return IIRS::available_languages();
}
?>