<?php
/* Copyright 2015, 2016 Transition Network ltd
 * This program is distributed under the terms of the GNU General Public License
 * as detailed in the COPYING file included in the root of this plugin
 */
?>

<?php
// http://codex.wordpress.org/Creating_Options_Pages

wp_enqueue_style( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ) );

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
      width:300px;
      clear:none;
      float:left;
    }
    body .form-table th {
      font-weight:normal;
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
      Please send an email to <?php print( IIRS_EMAIL_TEAM_LINK ); ?> for other Plugin recommendations!
    </p>
    <ul>
      <li><a taget="_blank" href="http://wpml.org/">WPML (WordPress MultiLingual)</a>: we have a free license and you can translate this plugin with it.</li>
      <li><a taget="_blank" href="http://akismet.com/">A.kis.met (anti-spam)</a>: IIRS has it's own implementation of this plugin and checks all registrations against the Akismet service for you.</li>
      <li>We are intending to integrate with Location plugins soon. Please email <?php print( IIRS_EMAIL_TEAM_LINK ); ?> with your recommendations.</li>
    </ul>

    <h3>shortcodes</h3>
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

    <div class="IIRS_0_hidden">
      <h3>options <i>(currently disabled)</i></h3>
      <table id="form-table-localisation" class="form-table">
        <tr valign="top">
          <th scope="row"><label for="lang_code">language code</label></th>
          <td><select name="lang_code" id="lang_code" <?php if ( IIRS_0_setting('lang_code') ) print( 'checked="1"' ); ?> /></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="server_country">server country</label></th>
          <td><select name="server_country" id="server_country" <?php if ( IIRS_0_setting('server_country') ) print( 'checked="1"' ); ?> /></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="server_country">region bias</label></th>
          <td><select name="region_bias" id="region_bias" <?php if ( IIRS_0_setting('region_bias') ) print( 'checked="1"' ); ?> /></td>
        </tr>
        <tr valign="top">
          <td><input disabled="1" type="checkbox" name="language_selector" id="language_selector" value="1" <?php if ( IIRS_0_setting('language_selector') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label class="IIRS_0_disabled" for="language_selector">show language selector</label></th>
        </tr>
      </table>

      <table id="form-table-display-overide" class="form-table">
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_display" id="override_TI_display" value="1" <?php if ( IIRS_0_setting('override_TI_display') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_display">override Initiative display</label></th>
        </tr>
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_editing" id="override_TI_editing" value="1" <?php if ( IIRS_0_setting('override_TI_editing') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_editing">override Initiative editing</label></th>
        </tr>
        <tr valign="top">
          <td><input type="checkbox" name="override_TI_content_template" id="override_TI_content_template" value="1" <?php if ( IIRS_0_setting('override_TI_content_template') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="override_TI_content_template">override Initiative content display</label></th>
        </tr>

        <tr valign="top">
          <td><input type="checkbox" name="initiatives_visibility" id="initiatives_visibility" value="1" <?php if ( IIRS_0_setting('initiatives_visibility') ) print( 'checked="1"' ); ?> /></td>
          <th scope="row"><label for="initiatives_visibility">initiatives visibility</label></th>
        </tr>
      </table>

      <table id="form-table-registration-components" class="form-table">
        <tr valign="top">
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
        </tr>
        <tr valign="top">
          <th scope="row"><label for="thankyou_for_registering_url">thankyou for registering url</label></th>
          <td><input name="thankyou_for_registering_url" id="thankyou_for_registering_url" value="<?php print( esc_attr( IIRS_0_setting('thankyou_for_registering_url') ) ); ?>" /></td>
        </tr>
      </table>
      <br class="clear" />

      <?php submit_button(); ?>
    </div>

  </form>
</div>