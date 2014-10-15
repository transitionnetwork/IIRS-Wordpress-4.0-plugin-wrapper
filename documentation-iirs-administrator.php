<?php
wp_enqueue_style( 'IIRS_general', plugins_url( 'IIRS/IIRS_common/general.css' ) );
$WPML_main_active     = IIRS::plugin_active( 'sitepress-multilingual-cms/sitepress.php' );
$WPML_st_active       = IIRS::plugin_active( 'wpml-string-translation/plugin.php' );
$WPML_ready           = ( $WPML_main_active && $WPML_st_active );
$WPML_disabled        = ( $WPML_ready ? '' : ' class="IIRS_0_disabled" disabled="1" onclick="alert(\'WPML required\');return false;" ' );
$WPML_disabled_colour = ( $WPML_ready ? '' : ' class="IIRS_0_disabled" ' );
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

    <?php include( 'documentation/index.php' ); ?>

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
    <p>
      <a href="/wp-content/plugins/IIRS/templates/" target="_blank">Example templates</a> are included with this plugin.
      Copy them in to the theme templates directory and it will take over display.
    </p>

    <h3>options <i>(currently disabled in this version)</i></h3>
    <p>
      The following options are <strong>DISABLED</strong>. Option control will be included in the next version.
    </p>
    <table id="form-table-1" class="form-table">
      <tr valign="top">
        <th scope="row">offer_buy_domains</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('offer_buy_domains') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">add_projects</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('add_projects') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">advanced_settings</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('advanced_settings') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">image_entry</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('image_entry') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">lang_code</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('lang_code') ); ?>" /></td>
      </tr>
    </table>

    <table id="form-table-2" class="form-table">
      <tr valign="top">
        <th scope="row">server_country</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('server_country') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">override_TI_display</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_display') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">override_TI_editing</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_editing') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">override_TI_content_template</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('override_TI_content_template') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">initiatives_visibility</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('initiatives_visibility') ); ?>" /></td>
      </tr>
      <tr valign="top">
        <th scope="row">language_selector</th>
        <td><input type="checkbox" disabled="1" name="new_option_name" value="<?php echo esc_attr( get_option('language_selector') ); ?>" /></td>
      </tr>
    </table>

    <br class="clear" />

    <?php submit_button(); ?>
  </form>
</div>