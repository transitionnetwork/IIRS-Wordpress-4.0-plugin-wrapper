<?php
/* Copyright 2015, 2016 Transition Network ltd
 * This program is distributed under the terms of the GNU General Public License
 * as detailed in the COPYING file included in the root of this plugin
 */

/**
 * @package IIRS
 */
/*
Plugin Name: IIRS
Plugin URI: http:// transitionnetwork.org/
Description: IIRS allows Transition Initiatives to register themselves on your website
Version: 1.0.0
Author: Annesley Newholm
Author URI: http:// transitionnetwork.org/
License: GPLv2 or later
Text Domain: IIRS
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or ( at your option ) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

define( 'IIRS_PLUGIN_NAME',         'IIRS' );
define( 'IIRS__MINIMUM_WP_VERSION', '1.0' );
define( 'IIRS__PLUGIN_URL',         plugin_dir_url(  __FILE__ ) );
define( 'IIRS__PLUGIN_DIR',         plugin_dir_path( __FILE__ ) );
define( 'IIRS__COMMON_DIR',         IIRS__PLUGIN_DIR . 'IIRS_common/' );
define( 'IIRS_0_CONTENT_TYPE',      'initiative_profile' ); // Wordpress limit varchar( 20 )
define( 'IIRS_0_USER_ROLE_NAME',    'initiative_facilitator' );
define( 'IIRS_0_POST_CATEGORY',     'Initiatives' );
define( 'IIRS_0_CONTENT_TYPE_SLUG', 'initiatives' ); // initiatives/bedford-transition-town
define( 'IIRS_0_WORDPRESS_ROOT',    WP_CONTENT_DIR . '/..' );
define( 'IIRS_WP_OPTIONS_ENABLED',  TRUE );

register_activation_hook(   __FILE__, array( IIRS_PLUGIN_NAME, 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( IIRS_PLUGIN_NAME, 'plugin_deactivation' ) );

set_include_path( get_include_path() . PATH_SEPARATOR );
require_once( IIRS__PLUGIN_DIR . 'IIRS_abstraction_layer.inc' );
require_once( IIRS__COMMON_DIR . 'define.php' );     // no direct action or includes, just defines
require_once( IIRS__COMMON_DIR . 'utility.php' );    // no direct action or includes, just functions
require_once( IIRS__COMMON_DIR . 'IIRS_Error.php' ); // no direct action or includes, just the IIRS_Error class
require_once( IIRS__PLUGIN_DIR . 'class-iirs.php' );
require_once( IIRS_0_WORDPRESS_ROOT . '/wp-admin/includes/taxonomy.php' );
require_once( IIRS_0_WORDPRESS_ROOT . '/wp-admin/includes/post.php' );

add_action( 'init', array( IIRS_PLUGIN_NAME, 'init' ) );
