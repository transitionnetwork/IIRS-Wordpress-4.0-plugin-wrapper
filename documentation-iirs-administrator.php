<?php
wp_enqueue_style( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ) );
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
    #iirs-settings-form ul {
      list-style:disc;
      padding-left:15px;
    }
    #iirs-settings-form img {
      float:right;
    }
  </style>

  <h2><?php print( IIRS_PLUGIN_NAME ); ?></h2>

  <form id="iirs-settings-form" method="post" action="options.php">
    <?php settings_fields( IIRS_PLUGIN_NAME ); ?>
    <?php do_settings_sections( IIRS_PLUGIN_NAME ); ?>

    <?php include( 'IIRS_common/documentation/index.php' ); ?>

    <?php if ( ! IIRS_0_language_is_supported() ) { ?>
      <a name="translation">&nbsp;</a>
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

    <!-- h3>integration with other plugins</h3>
    <p>
      IIRS uses, and has free organisation licenses for other plugins.
      Please send an email to <?php print( IIRS_EMAIL_TEAM_LINK ); ?> for other Plugin recommendations!
    </p>
    <ul>
      <li><a taget="_blank" href="http://wpml.org/">WPML (WordPress MultiLingual)</a>: we have a free license and you can translate this plugin with it.</li>
      <li><a taget="_blank" href="http://akismet.com/">A.kis.met (anti-spam)</a>: IIRS has it's own implementation of this plugin and checks all registrations against the Akismet service for you.</li>
      <li>We are intending to integrate with Location plugins soon. Please email <?php print( IIRS_EMAIL_TEAM_LINK ); ?> with your recommendations.</li>
    </ul -->

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

    <h3>options <i>(currently disabled in this version)</i></h3>
    <p>
      The following options are <strong>DISABLED</strong>. Option control will be included in the next version.
    </p>
    <table id="form-table-1" class="form-table">
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('offer_buy_domains') ); ?>" /></td>
        <th scope="row"><label>offer_buy_domains</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('add_projects') ); ?>" /></td>
        <th scope="row"><label>add_projects</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('advanced_settings') ); ?>" /></td>
        <th scope="row"><label>advanced_settings</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('image_entry') ); ?>" /></td>
        <th scope="row"><label>image_entry</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('lang_code') ); ?>" /></td>
        <th scope="row"><label>lang_code</label></th>
      </tr>
    </table>

    <table id="form-table-2" class="form-table">
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('server_country') ); ?>" /></td>
        <th scope="row"><label>server_country</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_display') ); ?>" /></td>
        <th scope="row"><label>override_TI_display</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_editing') ); ?>" /></td>
        <th scope="row"><label>override_TI_editing</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_content_template') ); ?>" /></td>
        <th scope="row"><label>override_TI_content_template</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('initiatives_visibility') ); ?>" /></td>
        <th scope="row"><label>initiatives_visibility</label></th>
      </tr>
      <tr valign="top">
        <td><input type="checkbox" name="new_option_name" value="<?php echo esc_attr( get_option('language_selector') ); ?>" /></td>
        <th scope="row"><label>language_selector</label></th>
      </tr>
    </table>

    <table id="form-table-2" class="form-table">
      <tr valign="top">
        <th scope="row"><label>thankyou_for_registering_url</label></th>
        <td><input name="new_option_name" value="<?php echo esc_attr( get_option('thankyou_for_registering_url') ); ?>" /></td>
      </tr>
    </table>

    <br class="clear" />

    <?php submit_button(); ?>
  </form>
</div>