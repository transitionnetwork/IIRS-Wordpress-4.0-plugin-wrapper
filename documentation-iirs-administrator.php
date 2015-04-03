<?php
/* Copyright 2015, 2016 Transition Network ltd
 * This program is distributed under the terms of the GNU General Public License
 * as detailed in the COPYING file included in the root of this plugin
 */

// http://codex.wordpress.org/Creating_Options_Pages

wp_enqueue_style( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ) );

global $IIRS_host_TLD;
require_once( IIRS__COMMON_DIR . 'iso-3166-country.php' );

// -------------------------------------------------- partner plugin detection
$WPML_main_active     = IIRS::plugin_active( 'sitepress-multilingual-cms/sitepress.php' );
$WPML_st_active       = IIRS::plugin_active( 'wpml-string-translation/plugin.php' );
$WPML_ready           = ( $WPML_main_active && $WPML_st_active );
$WPML_disabled        = ( $WPML_ready ? '' : ' class="IIRS_0_disabled" disabled="1" onclick="alert(\'WPML required\');return false;" ' );
$WPML_disabled_colour = ( $WPML_ready ? '' : ' class="IIRS_0_disabled" ' );

// -------------------------------------------------- custom template detection
global $post;
$post = new stdClass();
$post->ID         = -42;
$post->post_type  = IIRS_0_CONTENT_TYPE;
$custom_templates = array();
// custom single.php
$single_template_name = apply_filters( 'template_include', get_single_template() );
if ( FALSE !== strstr( $single_template_name, IIRS_0_CONTENT_TYPE ) ) {
  array_push( $custom_templates, $single_template_name );
}
// custom content.php
if ( $theme_custom_content_template = locate_template( array( 'content-' . IIRS_0_CONTENT_TYPE . '.php' ) ) ) {
  array_push( $custom_templates, $theme_custom_content_template );
}
?>

<div class="wrap">
  <style>
    body .form-table {
      max-width:200px;
      margin-right:50px;
      clear:none;
      float:left;
    }
    body .form-table th {
      font-weight:normal;
      padding:5px 0px;
    }
    body .form-table label {
      white-space:nowrap;
    }
    #iirs-settings-form ul {
      list-style:disc;
      padding-left:15px;
    }
    #iirs-settings-form img {
      float:right;
    }
    #iirs-settings-form .options-select {
      width:180px;
    }
    #iirs-settings-form .help {
      color:#666666;
      font-size:12px;
    }
    #iirs-settings-form .help:hover {
      font-weight:normal;
    }
  </style>

  <h2><?php print( IIRS_PLUGIN_NAME ); ?> settings</h2>

  <form id="iirs-settings-form" method="post" action="options.php">
    <?php settings_fields( IIRS_PLUGIN_NAME ); ?>

    <?php include( 'IIRS_common/documentation/index.php' ); ?>

    <?php if ( ! IIRS_0_language_is_supported() ) { ?>
      <a name="translation" id="translation">&nbsp;</a>
      <h3>translation in to <i><?php print( IIRS_0_locale() ); ?></i></h3>
      <p>
        IIRS recommends <a taget="_blank" href="http://wpml.org/">WPML (WordPress MultiLingual)</a> for translation.
        Please send us the .PO file when you have finished translating so we can provide it for others.
      </p>
      <ul>
        <?php if ( IIRS::plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) { ?><li>✓ <i>WPML already installed! :)</i></li>
        <?php } else { ?><li>Install <a taget="_blank" href="http://wpml.org/">WPML (WordPress MultiLingual)</a></li><?php } ?>

        <?php if ( IIRS::plugin_active( 'wpml-string-translation/plugin.php' ) ) { ?><li>✓ <i>WPML (string translation) already active! :)</i></li>
        <?php } else { ?><li>Install and Activate <a taget="_blank" href="http://wpml.org/">WPML (WordPress String Translation)</a></li><?php } ?>

        <li <?php print( $WPML_disabled_colour ); ?>><a <?php print( $WPML_disabled ); ?> href="/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/theme-localization.php">Import the strings</a> from the IIRS plugin in to WPML</li>
        <li <?php print( $WPML_disabled_colour ); ?>><a <?php print( $WPML_disabled ); ?> href="/wp-admin/admin.php?page=wpml-string-translation/menu/string-translation.php">Manually translate</a> all the strings</li>
        <li <?php print( $WPML_disabled_colour ); ?>><a <?php print( $WPML_disabled ); ?> href="/wp-admin/admin.php?page=sitepress-multilingual-cms/menu/theme-localization.php">Ask WPML to Produce</a> a <a target="_blank" href="https://www.gnu.org/savannah-checkouts/gnu/gettext/manual/html_node/PO-Files.html">.PO file</a></li>
        <li <?php print( $WPML_disabled_colour ); ?>>email <?php print( IIRS_EMAIL_TEAM_LINK ); ?> the <a target="_blank" href="https://www.gnu.org/savannah-checkouts/gnu/gettext/manual/html_node/PO-Files.html">.PO file</a></li>
        <li <?php print( $WPML_disabled_colour ); ?>>Eat a big cake</li>
      </ul>
    <?php } ?>

    <h3>integration with other plugins</h3>
    <p>
      IIRS uses, and has free organisation licenses for other plugins.
      Please send an email to <?php print( IIRS_EMAIL_TEAM_LINK ); ?> with your other Plugin recommendations!
      Did you know: Wordpress can do <a href="/wp-admin/plugin-install.php">one-click plugin installation</a>? You will need your <b>FTP details</b> the first time.
      Email <?php print( IIRS_EMAIL_TEAM_LINK ); ?> if you need help with this of course! :)
    </p>
    <ul>
      <li><a target="_blank" href="http://wpml.org/">WPML (WordPress MultiLingual)</a>: we have a free license and you can translate this plugin with it.</li>
      <li><a target="_blank" href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=akismet">A.kis.met (anti-spam)</a>: IIRS has it's own implementation of this plugin and checks all registrations against the Akismet service for you.</li>
      <li>Transition Network has also developed a free <b>Login plugin</b> which enables an easy Login popup for your users to use.</li>
      <li>we also recommend <b>regular backups</b> using <a target="_blank" href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=updraftplus">UpdraftPlus Backup and Restoration</a></li>
      <li>We are intending to integrate with Location plugins soon. Please email <?php print( IIRS_EMAIL_TEAM_LINK ); ?> with your recommendations.</li>
    </ul>

    <h3>putting the IIRS on your website: Wordpress "shortcodes" and "widgets"</h3>
    <p>This IIRS plugin provides a registration "widget" called "Register your Initiative". Go to your <a href="/wp-admin/widgets.php">Apperance Widget editor</a> to place it in one of your sidebars.</p>
    <p>The following <a href="http://codex.wordpress.org/Shortcode" target="_blank">shortcodes</a> can be used from the IIRS plugin:</p>
    <ul>
      <li><strong>[iirs-registration]</strong> show the registration widget town entry</li>
      <li><strong>[iirs-mapping]</strong> show a map of all the registered Transition Initiatives</li>
      <li><strong>[iirs-edit-link]</strong> show a link to editing your Transition Initiative (only relevant if you have registered one)</li>
      <li><strong>[iirs-view-link]</strong> show a link to viewing your Transition Initiative (only relevant if you have registered one)</li>
    </ul>

    <h3>theme templates</h3>
    <?php if ( count( $custom_templates ) ) { ?>
      <p>
        IIRS has detected custom templates in your current theme (<?php print( get_template() ); ?>).
        <?php if ( $theme_custom_content_template ) { ?>
          <br />If you want the <a href="#">content-initiative_profile.php</a> override to work you need to call the correct
          get_template_part( 'content', <b>'initiative_profile'</b> ); in the <a href="#">single-initiative_profile.php</a>.
        <?php } ?>
      </p>
    <?php
      print( '<ul>' );
      foreach ( $custom_templates as $template_name ) {
        print( "<li><a href=\"#\">$template_name</a></li>" );
      }
      print( '</ul>' );
    } else { ?>
      <p>
        If you would like to make your own Initiative display templates then
        copy the <a target="_blank" href="/wp-content/themes/<?php print( get_template() ); ?>/">active theme (<?php print( get_template() ); ?>)</a> single.php to
        <a href="#">single-initiative_profile.php</a> in the same directory and it will take over control of display.
        You can also additionally copy the themes content.php to <a href="#">content-initiative_profile.php</a> for specific content override only.
        When you have successfully copied the file(s), this settings page will auto-detect them and show your custom template(s) for you.
      </p>
    <?php } ?>

    <div>
      <h3>options</h3>
      <a name="IIRS_0_options_area" />
      <table id="form-table-localisation" class="form-table">
        <tr valign="top">
          <th scope="row"><label for="additional_error_notification_email">error notification<br/>email address</label><br/><span class="help">administrator's email</span></th>
          <td><input name="additional_error_notification_email" id="additional_error_notification_email" value="<?php print( esc_attr( IIRS_0_setting('additional_error_notification_email') ) ); ?>" /></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="registration_notification_email">registration<br/>notification email<br/>address</label><br/><span class="help">a copy of the new user email</span></th>
          <td><input name="registration_notification_email" id="registration_notification_email" value="<?php print( esc_attr( IIRS_0_setting('registration_notification_email') ) ); ?>" /></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="lang_code">plugin<br/>language code</label></th>
          <td><select class="options-select" name="lang_code" id="lang_code">
            <option value="">default administration suite setting (<?php print(IIRS_0_locale()); ?>)</option>
            <?php
              $available_languages = IIRS_0_available_languages();
              $current_lang_code   = IIRS_0_setting('lang_code');
              foreach ( $available_languages as $lang_code ) {
                $selected = '';
                if ( $lang_code == $current_lang_code ) $selected = 'selected="1"';
                print( "<option $selected value=\"$lang_code\">$lang_code</option>" );
              }
            ?>
          </select></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="server_country">location search<br/>region bias<br/><span class="help">default's to [<?php print( $IIRS_host_TLD ); ?>]</span></label></th>
          <td><select class="options-select" name="region_bias" id="region_bias">
            <option value="">use TLD [<?php print( $IIRS_host_TLD ); ?>]</option>
            <?php
              $current_ISO_3166_code = IIRS_0_setting('region_bias');
              foreach ( $iso_3166_country as $ISO_3166_code => $country ) {
                $selected = '';
                if ( $ISO_3166_code == $current_ISO_3166_code ) $selected = 'selected="1"';
                print( "<option $selected value=\"$ISO_3166_code\">$country ($ISO_3166_code)</option>" );
              }
            ?>
          </select></td>
        </tr>
      </table>

      <table id="form-table-display-overide" class="form-table">
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_display" id="override_TI_display" value="1" <?php if ( IIRS_0_setting('override_TI_display') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_display">override Initiative display</label><br/><span class="help">use the IIRS to display the full profile view page</span></th>
        </tr>
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_editing" id="override_TI_editing" value="1" <?php if ( IIRS_0_setting('override_TI_editing') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_editing">override Initiative editing</label><br/><span class="help">use the IIRS Initiative editor and prevent user access to the Wordpress administration suite</span></th>
        </tr>
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_content_template" id="override_TI_content_template" value="1" <?php if ( IIRS_0_setting('override_TI_content_template') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_content_template">override Initiative content<br/>display</label><br/><span class="help">use the IIRS to display the TI profiles</span></th>
        </tr>
        <tr valign="top">
          <td><input type="checkbox" name="initiatives_visibility" id="initiatives_visibility" value="1" <?php if ( IIRS_0_setting('initiatives_visibility') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="initiatives_visibility">initiatives visibility</label><br/><span class="help">show TI registrations in the normal post lists on the homepage</span></th>
        </tr>
        <!-- tr valign="top">
          <td><input disabled="1" type="checkbox" name="language_selector" id="language_selector" value="1" <?php if ( IIRS_0_setting('language_selector') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="language_selector">show language selector</label></th>
        </tr -->
      </table>

      <table id="form-table-registration-components" class="form-table">
        <tr valign="top">
          <th scope="row"><label for="thankyou_for_registering_url">thankyou for registering<br/>web address</label><br/><span class="help">redirect at the end of the registration e.g. /post/69</span></th>
          <td><input name="thankyou_for_registering_url" id="thankyou_for_registering_url" value="<?php print( esc_attr( IIRS_0_setting('thankyou_for_registering_url') ) ); ?>" /></td>
        </tr>
        <!-- tr valign="top">
          <td><input disabled="1" type="checkbox" name="offer_buy_domains" id="offer_buy_domains" value="1" <?php if ( IIRS_0_setting('offer_buy_domains') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="offer_buy_domains">offer to buy domain</label></th>
        </tr>
        <tr valign="top">
          <td><input disabled="1" type="checkbox" name="add_projects" id="add_projects" value="1" <?php if ( IIRS_0_setting('add_projects') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="add_projects">add projects</label></th>
        </tr>
        <tr valign="top">
          <td><input disabled="1" type="checkbox" name="advanced_settings" id="advanced_settings" value="1" <?php if ( IIRS_0_setting('advanced_settings') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="advanced_settings">advanced settings</label></th>
        </tr>
        <tr valign="top">
          <td><input disabled="1" type="checkbox" name="image_entry" id="image_entry" value="1" <?php if ( IIRS_0_setting('image_entry') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="image_entry">image entry</label></th>
        </tr -->
      </table>
      <?php do_settings_sections(IIRS_PLUGIN_NAME); ?>
      <br class="clear" />

      <?php submit_button(); ?>
    </div>

  </form>
</div>