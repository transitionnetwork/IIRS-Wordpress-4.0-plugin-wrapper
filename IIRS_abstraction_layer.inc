<?php
defined('ABSPATH') or die("No script kiddies please!");

//------------------------------------------------------------------------
//--------------------------------------------------- IIRS_common abstraction layer
//------------------------------------------------------------------------
function IIRS_0_translation($sString) {
  return IIRS::__($sString);
}

function IIRS_0_current_path() {
  global $wp;
  return $wp->request;
}

function IIRS_0_availableLanguages() {
  return array('en', 'es', '(dev!)');
}

function IIRS_0_http_request($url, $timeout = 2.0, $var_dump_non_200 = FALSE) {
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
  $response = wp_remote_get($url, $args);
  $body     = wp_remote_retrieve_body($response);

  return $body;
}

function IIRS_0_set_message($message, $IIRS_widget_mode = true) {
  if ($IIRS_widget_mode) {
    print("<div class=\"IIRS_0_message\">$message</div>");
  } else {
    //needs to be a function! add_action('user_admin_notices', $message);
  }
}

//------------------------------------------------------------------------
//------------------------------------------------------- create and update user and TI
//------------------------------------------------------------------------
function IIRS_0_TI_addUser($name, $email, $pass, $phone) {
  return IIRS::add_user($name, $email, $pass, $phone);
}

function IIRS_0_TI_addTI($userID, $initiative_name, $townname, $place_centre_lat, $place_centre_lng, $place_description, $place_country, $domain) {
  return IIRS::add_ti($userID, $initiative_name, $townname, $place_centre_lat, $place_centre_lng, $place_description, $place_country, $domain);
}

function IIRS_0_next_initnumber() {
  print('IIRS_0_next_initnumber() not supported');
  exit(0);
}

function _IIRS_0_TI_translateUserFields($aValues) {
  //private function
  //change standard fields definition to Drupal TN.org fields
  $aTranslatedValues = array();
  foreach ($aValues as $key => $value) {
    //translate the standard TI field names to the Drupal TN.org ones
    if (!empty($value)) {
      switch ($key) {
        case 'name':     {$key = 'user_login'; break;}
        case 'email':    {$key = 'user_email'; break;}
        case 'password': {$key = 'user_pass';  break;}
      }
    }
    //re-write the value
    $aTranslatedValues[$key] = $value;
  }

  return $aTranslatedValues;
}

function _IIRS_0_TI_translateTIFields($aValues) {
  //private function
  //change standard fields definition to Drupal TN.org fields
  $aTranslatedValues = array('_meta' => array());
  foreach ($aValues as $key => $value) {
    //translate the standard TI field names to the Drupal TN.org ones
    if (!empty($value)) {
      switch ($key) {
        case 'type':
        case 'language': {break;}
        case 'published': {$aTranslatedValues['status'] = $value; break;}

        //base node fields title, body
        case 'name':
        case 'initiative_name':
        case 'title':   {$aTranslatedValues['post_title'] = $value; break;}
        case 'summary': {$aTranslatedValues['content']    = $value; break;}

        //meta data: these fields are updated separately
        //weird Wordpress uses a meta id for edit_post() updates
        case 'townname':          {$aTranslatedValues['_meta']['location_townname']    = $value; break;}
        case 'place_centre_lat':  {$aTranslatedValues['_meta']['location_latitude']    = $value; break;}
        case 'place_centre_lng':  {$aTranslatedValues['_meta']['location_longitude']   = $value; break;}
        case 'place_description': {$aTranslatedValues['_meta']['location_description'] = $value; break;}
        case 'place_country':     {$aTranslatedValues['_meta']['location_country']     = $value; break;}
        case 'website':
        case 'domain':            {$aTranslatedValues['_meta']['domain'] = $value; break;}
      }
    }
  }

  return $aTranslatedValues;
}

function IIRS_0_TI_updateTI($aValues) {
  return IIRS::update_ti($aValues);
}

function IIRS_0_TI_updateUser($aValues) {
  return IIRS::update_user($aValues);
}

//------------------------------------------------------------------------
//------------------------------------------------------- querying the TIs
//------------------------------------------------------------------------
function IIRS_0_TIs_all($page_size = 0, $page_offset = 0) {
  return IIRS::ti_all($page_size, $page_offset);
}

function IIRS_0_TIs_viewport() {
  print('IIRS_0_TIs_viewport() function not supported yet');
  exit(0);
}

function IIRS_0_TIs_nearby($centre_lat, $centre_lng, $place_description = '', $maxTIs = IIRS_0_MAX_NEARBY) {
  print("IIRS_0_TIs_nearby() function not supported yet\n");
}

function IIRS_0_detailsUser() {
  return IIRS::details_user();
}

function IIRS_0_detailsTI_page() {
  return IIRS::details_ti_page();
}

function IIRS_0_detailsTI_user() {
  return IIRS::details_ti_user();
}

//------------------------------------------------------------------------
//------------------------------------------------------- authentication
//------------------------------------------------------------------------
function IIRS_0_logged_in() {
  return IIRS::logged_in();
}

function IIRS_0_login($name, $pass) {
  return IIRS::login();
}
?>