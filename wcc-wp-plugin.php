<?php
/*
Plugin Name: WatchCount.com WordPress Plugin
Plugin URI: http://www.WatchCount.com/wp/
Version: 1.0.0
Author: WatchCount.com
Author URI: http://www.WatchCount.com/
Description: The WatchCount.com WordPress Plugin (WCCWPPI) displays Most Popular/Watched eBay items in real-time, as a blog sidebar widget or within individual blog posts (or both). <strong>-- New Install? Get Going in 4 Easy Steps --</strong> <strong>(1)</strong> Click the 'Activate' link to the left. <strong>(2)</strong> Join our <a href="http://www.WatchCount.com/go/?link=wp_i_pi_alerts" title="Get notified about important WCCWPPI information..." target="_blank">WCCWPPI Notification Alerts list</a> so we can email you about critical upgrades/info. <strong>(3)</strong> Find your WordPress 'Widgets' page and drag the eBay Items widget into your sidebar. <strong>(4)</strong> Embed the WCCWPPI within your blog posts: just include <strong>[eBay]</strong> as you type, or get more specific: <strong>[eBay keywords="free shipping"]</strong> . . . Full <a href="http://www.WatchCount.com/go/?link=wp_i_pi_qs" title="WCCWPPI Quick Start Instructions..." target="_blank">Quick Start Instructions</a> are availble on our <a href="http://www.WatchCount.com/go/?link=wp_i_pi" title="Information about WCCWPPI..." target="_blank">WCCWPPI Information page</a>, and community support is available on our <a href="http://www.WatchCount.com/go/?link=wp_gcwccwppi" title="WCCWPPI 'mini-forum'..." target="_blank">Global Conversations page</a>. (If needed, you can <a href="http://www.WatchCount.com/go/?link=wp_i_contact" title="Contact WatchCount.com..." target="_blank">contact us directly</a>.)
*/

/**
 * Copyright © 2009 WatchCount.com - All Rights Reserved  [ contact: http://www.WatchCount.com/contact.php ]
 *
 * This program is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General
 * Public License (GNU GPL) version 3, as published by the
 * Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU GPL v3 for more details.
 *
 * You should have received a copy of the GNU General
 * Public License v3 along with this program. If not,
 * please see <http://www.GNU.org/licenses/>.
 *
 * Please note that this plugin communicates with our servers
 * to obtain live eBay data for display on your blog. Our server
 * software (which is proprietary and not governed by the license
 * mentioned above (like the popular Akismet WP plugin)) is designed
 * to work strictly with this plugin script, unaltered and unmodified.
 * While you are free, pursuant to the above license, to do things
 * with this plugin like borrow helpful functions for your own plugin
 * development/use, or modify it to retrieve data from your own
 * servers, we discourage modifying this plugin if you intend to
 * continue using it for its current, intended purpose (the more
 * you tweak this plugin, the more likely it'll cease functioning
 * properly, with regards to retrieving eBay data from our servers).
 * Instead, if you'd like to request features or modifications to this
 * plugin, please contact us with your request, and we'll work to
 * satisfy it in the next plugin release.
 *
 * Comprehensive information about this plugin is available at the
 * 'Plugin URI' at the top of this file.
 */


/**
 * ---------------------------------------------------------------
 * Global Constants for WCCWPPI Functions
 * ---------------------------------------------------------------
 */
define( 'WCCWPPI_CLIENT_VERSION' , '1.0.0' , FALSE ) ;   // current version of this WatchCount.com WordPress Plugin client
define( 'WCCWPPI_CALLBACK_PRIORITY' , 6 , FALSE ) ;   // priority level to use for wccwppi callback functions
define( 'WCCWPPI_OPTION_NAME' , 'wccwppi_option_params' , FALSE ) ;   // option name for WPDB storage that contains array of WCCWPPI settings/parameters
define( 'WCCWPPI_ADMINPAGE_HANDLE' , 'wccwppi-settings' , FALSE ) ;   // internal handle/name for admin-options page
define( 'WCCWPPI_WIDGET_NAME' , "eBay's Most Popular Items" , FALSE ) ;   // widget title-name, as appears on Widgets drag-and-drop screen (also used as unique ID)
define( 'WCCWPPI_WIDGET_CALLBACK' , 'wccwppi_execute_display_sidebar' , FALSE ) ;   // unique function (name) used to display sidebar widget
define( 'WCCWPPI_WIDGET_NAV' , 'eBay Items' , FALSE ) ;   // widget navigation title, as appears in Settings/Options menu bar pop-up


/**
 * ---------------------------------------------------------------
 * Register WordPress Hooks
 * ---------------------------------------------------------------
 */
add_action( 'admin_menu' , 'wccwppi_adminpage' , constant('WCCWPPI_CALLBACK_PRIORITY') , 0 ) ;
add_action( 'plugins_loaded' , 'wccwppi_widget_reg' , constant('WCCWPPI_CALLBACK_PRIORITY') , 0 ) ;
add_action( 'admin_print_scripts-post.php' , 'wccwppi_quicktags' , constant('WCCWPPI_CALLBACK_PRIORITY') , 0 ) ;
add_shortcode(     'ebay' , 'wccwppi_execute_post' ) ;
add_shortcode(     'EBAY' , 'wccwppi_execute_post' ) ;
add_shortcode(     'eBay' , 'wccwppi_execute_post' ) ;
add_shortcode(     'Ebay' , 'wccwppi_execute_post' ) ;
add_shortcode(      'wcc' , 'wccwppi_execute_post' ) ;
add_shortcode(      'WCC' , 'wccwppi_execute_post' ) ;
add_shortcode(  'wccwppi' , 'wccwppi_execute_post' ) ;
add_shortcode(  'WCCWPPI' , 'wccwppi_execute_post' ) ;

if ( function_exists('register_uninstall_hook') ) {
   register_uninstall_hook( __FILE__ , 'wccwppi_uninstall' ) ;
} // endif: register_uninstall_hook() exists


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_uninstall() - cleanup function runs after plugin deletion/uninstall
 * ---------------------------------------------------------------
 */
function wccwppi_uninstall () {

   $c_wccwppi_option_name = constant('WCCWPPI_OPTION_NAME') ;

   delete_option($c_wccwppi_option_name) ;

} // end function: wccwppi_uninstall()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_quicktags() - adds Quicktags to posts editor
 * ---------------------------------------------------------------
 * adapted from:  http://scribu.net/wordpress/right-way-to-add-custom-quicktags.html
 * ---------------------------------------------------------------
 */
function wccwppi_quicktags () {

   $wccwppi_url_quicktags = '' ;
   $c_wccwppi_file_quicktags = 'wcc-wp-plugin_quicktags.js' ;   // filename of JS file containing additional Quicktags [base: this-plugin folder]

   if (!(function_exists(plugin_dir_url))) {
      // define substitute function for plugin_dir_url()
      function plugin_dir_url($In_File) {
         $Out = '' ;
         if (function_exists(plugins_url)) {
            $Out = ( trailingslashit( plugins_url( plugin_basename( dirname($In_File) ) ) ) ) ;
         } // endif: for WP version 2.6 - 2.7
         else {
            $Out = ( trailingslashit( get_option('siteurl') . '/wp-content/plugins/' . plugin_basename( dirname($In_File) ) ) ) ;
         } // endif: for WP version < 2.6
         return($Out) ;
      } // end function: plugin_dir_url()
   } // endif: defined substitute function for plugin_dir_url()

   if ( file_exists( trailingslashit(dirname(__FILE__)) . $c_wccwppi_file_quicktags ) ) {
      $wccwppi_url_quicktags = ( plugin_dir_url(__FILE__) . $c_wccwppi_file_quicktags ) ;
      wp_enqueue_script( 'wccwppi_quicktags' , $wccwppi_url_quicktags , array('quicktags') ) ;
   } // endif: quicktags file exists

} // end function: wccwppi_quicktags()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_widget_reg() - registers WCCWPPI as a widget
 * ---------------------------------------------------------------
 */
function wccwppi_widget_reg () {

   $c_wccwppi_widget_name = constant('WCCWPPI_WIDGET_NAME') ;
   $c_wccwppi_widget_callback = constant('WCCWPPI_WIDGET_CALLBACK') ;

   if (function_exists('register_sidebar_widget')) {
      register_sidebar_widget( $c_wccwppi_widget_name , $c_wccwppi_widget_callback ) ;
      register_widget_control( $c_wccwppi_widget_name , 'wccwppi_widget_control' ) ;
   }
   else {
      return ;
   }

} // end function: wccwppi_widget_reg()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_widget_control() - outputs widget control HTML to browser
 * ---------------------------------------------------------------
 */
function wccwppi_widget_control () {

   $Out = '' ;

   $c_wccwppi_widget_nav = constant('WCCWPPI_WIDGET_NAV') ;
   $Link = ( get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=' . constant('WCCWPPI_ADMINPAGE_HANDLE') ) ;

   $Out .= ( "\r\nPlease configure this widget via the <strong>" . $c_wccwppi_widget_nav . "</strong> <a href=\"" . $Link . "\" target=\"_top\" title=\"'" . $c_wccwppi_widget_nav . "' Settings Page\">Settings/Options page</a>, often located in the left admin sidebar (depending on your version of WordPress and applied Theme).<br />\r\n<br />\r\nThanks!<br />\r\n<br />\r\n\r\n" ) ;

   echo($Out) ;

} // end function: wccwppi_widget_control()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_curly() checks if cURL library is installed
 * ---------------------------------------------------------------
 */
function wccwppi_curly () {

   $Out = FALSE ;

   $Out = ( (function_exists('curl_init')) && (function_exists('curl_setopt')) && (function_exists('curl_exec')) ) ;

   return($Out) ;

} // end function: wccwppi_curly()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_hapi() checks if WP HTTP API is installed
 * ---------------------------------------------------------------
 */
function wccwppi_hapi () {

   $Out = FALSE ;

   $Out = ( (function_exists('wp_remote_post')) && (function_exists('wp_remote_retrieve_body')) ) ;

   return($Out) ;

} // end function: wccwppi_hapi()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_myurl() obtains current URL
 * ---------------------------------------------------------------
 */
function wccwppi_myurl () {

   $Out  = ( 'http' ) ;
   $Out .= ( ( (!(isset($_SERVER['HTTPS']))) || (empty($_SERVER['HTTPS'])) || ('off' == $_SERVER['HTTPS']) ) ? ('') : ('s') ) ;   // add 's' for https URL
   $Out .= ( '://' . $_SERVER['SERVER_NAME'] ) ;
   $Out .= ( ($_SERVER['SERVER_PORT'] != '80') ? (':' . $_SERVER['SERVER_PORT']) : ('') ) ;
   $Out .= ( $_SERVER['REQUEST_URI'] ) ;

   return($Out) ;

} // end function: wccwppi_myurl()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_sanitize() cleans data element(s) before sending to WCCAPI
 * ---------------------------------------------------------------
 * $In_Data: incoming data to clean
 * $In_Length: truncation length
 * ---------------------------------------------------------------
 */
function wccwppi_sanitize ( $In_Data='' , $In_Length=0 ) {

   $MaxLength = 200 ;
   $Out = $In_Data ;

   // New max length?
   $MaxLength = ( ( ($In_Length) && (is_numeric($In_Length)) ) ? (round(abs($In_Length))) : ($MaxLength) ) ;

   // Remove backslashes
   if (get_magic_quotes_gpc()) {
      $Out = stripslashes($Out) ;
   }

   // Strip unprintable characters, then tags
   $Out = strip_tags( filter_var( $Out , FILTER_SANITIZE_STRING , ( FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES ) ) ) ;

   // Remove invalid characters (>) from string, then unduplicate spaces, then trim
   $Out = trim( preg_replace( '{( )\1+}' , '$1' , (preg_replace( "|[>]|" , "" , $Out )) ) ) ;

   // Truncate
   $Out = mb_substr( $Out , 0 , $MaxLength , 'UTF-8' ) ;

   return($Out);

} // end function: wccwppi_sanitize()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_adminpage() - creates Options/Settings/Admin page for WCCWPPI admin settings
 * ---------------------------------------------------------------
 */
function wccwppi_adminpage () {

   $adminpagehandle = add_options_page( "eBay's Most Popular Items - WatchCount.com WordPress Plugin" , constant('WCCWPPI_WIDGET_NAV') , 'administrator' , constant('WCCWPPI_ADMINPAGE_HANDLE') , 'wccwppi_adminpage_display' ) ;
   add_action( ('admin_head-' . $adminpagehandle) , 'wccwppi_adminpage_header' , constant('WCCWPPI_CALLBACK_PRIORITY') ) ;

} // end function: wccwppi_adminpage()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_adminpage_header() - outputs HTML <head> content (styles) for WCCWPPI admin settings page
 * ---------------------------------------------------------------
 */
function wccwppi_adminpage_header () {

   $Out = '' ;

   $Out .= ( "\r\n   <!-- Begin: WCCWPPI Admin/Settings Page Head Content -->\r\n" ) ;
   $Out .= ( "\r\n   <style media=\"all\" type=\"text/css\">\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-msg { width: auto; height: auto; text-align: left; overflow: hidden; display: block; clear: none; float: none; vertical-align: middle; padding: 3px 6px 4px 6px; margin: 4px 4px 5px 4px; font-family: Verdana,Arial,Geneva,Tahoma; font-weight: normal; font-size: 8pt; font-style: normal; text-decoration: none; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-notice { border: 1px solid #E2D540; background: #FFFBCC; color: #1A1A1A; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-alert { border: 1px solid #0000CC; background: #FF3333; color: #FFFFFF; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links:link { color: #0033CC; text-decoration: none; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links:visited { color: #0033CC; text-decoration: none; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links:hover { color: #002699; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links:active { color: #002699; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links2:link { color: #FFFFFF; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links2:visited { color: #FFFFFF; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links2:hover { color: #33CCFF; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      a.wccwppi-links2:active { color: #33CCFF; text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-frame { width: 100%; height: auto; margin: 4px 4px 20px 0px; clear: right; float: none; table-layout: fixed; border-collapse: collapse; empty-cells: hide; border-spacing: 0px 0px; border: 0px none; background: #FFFFFF; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-frame { width: auto; height: auto; overflow: hidden; vertical-align: top; background: transparent; padding: 0px; text-align: center; border: 0px none; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-box { width: 97%; height: auto; margin-left: auto; margin-right: auto; margin-top: 2px; margin-bottom: 11px; clear: both; float: none; table-layout: fixed; border-collapse: collapse; empty-cells: hide; border-spacing: 0px 0px; border: 0px none; background: transparent; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-header { width: auto; height: auto; vertical-align: top; overflow: hidden; border: 1px solid #CCCCFF; background: url('http://www.WatchCount.net/images/fade-blue.jpg') repeat-x scroll top left; padding: 3px 6px 2px 6px; text-align: left; font-family: Arial,Geneva,Tahoma; font-size: 11pt; color: #1A1A1A; font-weight: bold; font-style: normal; text-decoration: none; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-content { width: auto; height: auto; vertical-align: top; overflow: hidden; border: 1px solid #CCCCFF; background: transparent; padding: 8px 8px 8px 8px; text-align: center; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-text-style1 { font-family: Verdana,Arial,Geneva,Tahoma; font-size: 8pt; color: #0D0D0D; font-weight: normal; font-style: normal; text-decoration: none; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-fineprint { font-size: 7pt; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-gray1 { color: #808080; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-nobold { font-weight: normal; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-underline { text-decoration: underline; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-highlight1 { font-weight: bold; color: #4D4D4D; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-highlight2 { font-weight: bold; }\r\n" ) ;
   $Out .= ( "\r\n      .wccwppi-labelsuffix { font-size: 7pt; font-weight: normal; color: #808080; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-text { width: auto; text-align: left; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-margintopper1 { margin-top: 5px; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-margintopper2 { margin-top: 12px; }\r\n" ) ;
   $Out .= ( "\r\n      p.wccwppi-p { margin: 0px; padding: 2px 2px 3px 2px; }\r\n" ) ;
   $Out .= ( "\r\n      ul.wccwppi-list { list-style: disc outside; margin: 0px; padding: 0px 0px 0px 15px; }\r\n" ) ;
   $Out .= ( "\r\n      li.wccwppi-list { margin: 0px; padding: 0px 0px 5px 0px; text-indent: 0px; }\r\n" ) ;
   $Out .= ( "\r\n      img.wccwppi-wcclogo { float: right; margin: -1px 3px -4px 5px; padding: 0px; vertical-align: text-bottom; border: 0px none; width: auto; height: auto; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-controls { width: 98%; height: auto; margin-left: auto; margin-right: auto; margin-top: 7px; margin-bottom: 0px; clear: both; float: none; table-layout: auto; border-collapse: separate; border-spacing: 4px; border: 1px solid #D4D4D4; -moz-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-controls-bg1 { background: #EDEDED; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-controls-bg2 { background: #C7C7D4; }\r\n" ) ;
   $Out .= ( "\r\n      table.wccwppi-controls-bg3 { background: #F2FFCC; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls { background: transparent; height: auto; vertical-align: middle; overflow: hidden; border: 0px none; }\r\n" ) ;
   $Out .= ( "\r\n      div.wccwppi-controls-header { float: left; background: #C7C7D4; padding: 1px 4px 2px 4px; margin: 0px; border: 0px none; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls-header { padding: 1px 0px 2px 3px; width: auto; text-align: left; font-weight: bold; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls-left { padding: 0px 3px 0px 0px; width: 33%; text-align: right; font-size: 7.5pt; font-weight: bold; color: #353550; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls-right { padding: 0px 0px 0px 3px; width: auto; text-align: left; font-size: 7.5pt; font-weight: bold; color: #4E4E74; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls-text { padding: 3px 0px 0px 0px; width: auto; text-align: left; font-weight: normal; font-size: 7pt; color: #3E3E51; }\r\n" ) ;
   $Out .= ( "\r\n      td.wccwppi-controls-submit { padding: 7px 2px 5px 2px; width: auto; text-align: center; }\r\n" ) ;
   $Out .= ( "\r\n      form.wccwppi-settings { margin: 0px; padding: 0px; width: auto; height: auto; border: 0px none; overflow: hidden; float: none; clear: none; background: transparent; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      select.wccwppi-settings { width: auto !important; height: 17px !important; text-align: left !important; vertical-align: middle !important; overflow: hidden; float: none; clear: none; margin: 0px 0px 0px 0px !important; padding: 0px 2px 1px 0px !important; border: 1px solid #737373 !important; background: #CCFFFF !important; font-size: 7pt !important; font-family: Verdana,Arial,Tahoma !important; font-weight: bold !important; color: #1A1A1A !important; font-style: normal !important; text-decoration: none; line-height: normal !important; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; }\r\n" ) ;
   $Out .= ( "\r\n      input.wccwppi-settings-text { margin: 0px 0px 0px 0px; padding: 1px 2px 1px 2px; width: auto; height: auto; text-align: left; float: none; clear: none; border: 1px solid #737373; background: #CCFFFF; font-size: 7pt; font-family: Verdana,Arial,Tahoma; font-weight: bold; color: #1A1A1A; font-style: normal; text-decoration: none; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      input.wccwppi-settings-submit { width: auto; height: auto; text-align: center; margin-left: auto; margin-right: auto; margin-top: auto; margin-bottom: auto; padding: 0px 4px 0px 4px; overflow: visible; float: none; clear: none; vertical-align: middle; font-family: Verdana,Arial,Tahoma; font-size: 10pt; font-weight: bold; color: #1A1A1A; white-space: nowrap; word-wrap: normal; font-style: normal; text-decoration: none; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n      input.wccwppi-settings-checkbox { margin: 1px 0px 0px 0px; padding: 0px; width: auto; height: auto; text-align: right; float: none; clear: none; border: 0px none; background: transparent; vertical-align: middle; line-height: normal; }\r\n" ) ;
   $Out .= ( "\r\n   </style>\r\n" ) ;
   $Out .= ( "\r\n   <!-- End: WCCWPPI Admin/Settings Page Head Content -->\r\n\r\n" ) ;

   echo($Out) ;

} // end function: wccwppi_adminpage_header()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_adminpage_display() - displays HTML of WCCWPPI admin settings page
 * ---------------------------------------------------------------
 */
function wccwppi_adminpage_display () {

   // -----------------------------------
   // Definitions + Nonce
   // -----------------------------------
   $c_wccwppi_version = constant('WCCWPPI_CLIENT_VERSION') ;
   $c_wccwppi_option_name = constant('WCCWPPI_OPTION_NAME') ;
   $c_wccwppi_widget_callback = constant('WCCWPPI_WIDGET_CALLBACK') ;
   $c_wccwppi_widget_nav = constant('WCCWPPI_WIDGET_NAV') ;
   $c_wccwppi_nonce_field = 'wpnonce_wccwppi-settings' ;
   $c_wccwppi_nonce_action = 'wccwppi-settings' ;
   $c_wccwppi_nonce_unique = wp_create_nonce($c_wccwppi_nonce_action) ;
   $c_blog_url = trailingslashit(get_bloginfo('url')) ;
   $c_blog_wpurl = get_bloginfo('wpurl') ;
   $c_wccdf_url = 'http://www.WatchCount.net/data.php' ;   // URL of WatchCount.com Data Feed (WCCDF) endpoint  [if root/index file: needs trailing slash]
   $c_wccdf_tips = '?asrt=' ;   // parameter suffix for WCCDF to acquire tips/news
   $c_wccdf_retries = 1 ;   // number of times to re-call WCCDF on connection failure
   $c_wccdf_timeout = 4 ;   // WCCDF connection timeout duration (seconds)


   // -----------------------------------
   // Initialize Other Variables
   // -----------------------------------
   $v_html_output = '' ;
   $v_html_tips = '' ;
   $v_wccdf_url_tips = '' ;
   $a_wccdf_call = array() ;   // for making the WCCDF call
   $h_wccdf_wp_remote = '' ;   // handle, if using WP HTTP API
   $index = '' ;
   $value = '' ;
   $a_wccwppi_option_params = array() ;   // working set of WCCWPPI admin settings
   $a_wccwppi_wpdb = array() ;   // WPDB access (via Options)
   $f_post = FALSE ;   // Do we have an incoming POST with nonce field present?
   $f_valid_post = FALSE ;   // Do we have a valid incoming POST?
   $f_post_caller_installed = FALSE ;   // either cURL or WP HTTP API installed?
   $f_sidebar_widget_installed = FALSE ;   // is WCCWPPI sidebar widget installed?


   // -----------------------------------
   // Define Settings Selection Options
   // -----------------------------------
   // notes: | order matters | ['internal parameter'] => 'displayed option' |
   // -----------------------------------
   $a_options_revlev = array( 'high'=>'High/100% (WCC Tagline Displayed)' , 'low'=>'Low/50% (WCC Tagline Suppressed)' ) ;
   $a_options_edd = array( '0'=>'(no)' , 'US'=>'eBay.com (US)' , 'UK'=>'eBay.co.uk (UK)' , 'DE'=>'eBay.de (DE)' , 'AU'=>'eBay.com.au (AU)' ) ;
   $a_options_insearch = array( 'default'=>'(default)' , 'eBay'=>'eBay (visitor taken to eBay)' , 'self'=>'Self (search results displayed in-plugin)' , 'hidden'=>'Hidden (show info msg instead)' ) ;
   $a_options_align = array( 'default'=>'(default)' , 'left'=>'left' , 'right'=>'right' , 'none'=>'none' ) ;

   $a_options_skins = array( 'default'=>'(default)' ,
                             'simple'=>'Simple' ,
                             'panel-frame'=>'Panel-Frame' ,
                             'shiny'=>'Shiny' ,
                             'blue'=>'Blue' ,
                             'no-skin'=>'(no-skin)' ) ;

   $a_options_countries = array( 'US'=>'eBay.com (US)' ,
                                 'UK'=>'eBay.co.uk (UK)' ,
                                 'AU'=>'eBay.com.au (AU)' ,
                                 'CA'=>'eBay.ca (CA)' ,
                                 'IE'=>'eBay.ie (IE)' ,
                                 'DE'=>'eBay.de (DE)' ,
                                 'FR'=>'eBay.fr (FR)' ,
                                 'ES'=>'eBay.es (ES)' ,
                                 'IT'=>'eBay.it (IT)' ,
                                 'BENL'=>'benl.eBay.be (BENL)' ,
                                 'BEFR'=>'befr.eBay.be (BEFR)' ,
                                 'IN'=>'eBay.in (IN)' ,
                                 'SG'=>'eBay.com.sg (SG)' ,
                                 'Motors'=>'motors.eBay.com (Motors) [US]' ) ;


   // -----------------------------------
   // Define Main Data Set + Setup Default Values
   // -----------------------------------
   // note: these are stored in WPDB
   // -----------------------------------
   $a_wccwppi_params_checks = array( 'wccwppi_titlehide' , 'wccwppi_titleund' , 'wccwppi_titledocolbg' , 'wccwppi_kwnottags' , 'wccwppi_rssdisable' ) ;   // data fields that are checkboxes

   $a_wccwppi_option_params = array( 'wccwppi_cc'=>'US' ,
                                     'wccwppi_ccedd'=>'0' ,
                                     'wccwppi_kw'=>'' ,
                                     'wccwppi_cats'=>'' ,
                                     'wccwppi_revshare'=>'high' ,
                                     'wccwppi_campid'=>'' ,
                                     'wccwppi_customid'=>'' ,
                                     'wccwppi_insearch'=>'default' ,
                                     'wccwppi_divcol'=>'' ,
                                     'wccwppi_colorbg'=>'' ,
                                     'wccwppi_climit'=>0 ,
                                     'wccwppi_sbuttontxt'=>'' ,
                                     'wccwppi_infomsg'=>'' ,
                                     'wccwppi_titlename'=>'' ,
                                     'wccwppi_titlehide'=>'' ,
                                     'wccwppi_titleund'=>'' ,
                                     'wccwppi_titlecolbg'=>'' ,
                                     'wccwppi_titledocolbg'=>'' ,
                                     'wccwppi_titlecoltxt'=>'' ,
                                     'wccwppi_titlefont'=>'' ,
                                     'wccwppi_titlesize'=>'' ,
                                     'wccwppi_titley'=>'' ,
                                     'wccwppi_skin'=>'default' ,
                                     'wccwppi_deffloat'=>'default' ,
                                     'wccwppi_kwnottags'=>'' ,
                                     'wccwppi_rssdisable'=>'' ,
                                     'wccwppi_fc'=>''  ) ;


   // -----------------------------------
   // Incoming POST Validation
   // -----------------------------------
   if (isset($_POST[$c_wccwppi_nonce_field])) {
      $f_post = TRUE ;
      $f_valid_post = ( (wp_verify_nonce(($_POST[$c_wccwppi_nonce_field]),($c_wccwppi_nonce_action))) && (is_admin()) ) ;
   }
   else {
      $f_post = FALSE ;
      $f_valid_post = FALSE ;
   }


   // -----------------------------------
   // Check if cURL Library or WP HTTP API is Installed
   // -----------------------------------
   $f_post_caller_installed = ( (wccwppi_curly()) || (wccwppi_hapi()) ) ;


   // -----------------------------------
   // Check if WCCWPPI Sidebar Widget is Installed
   // -----------------------------------
   $f_sidebar_widget_installed = is_active_widget($c_wccwppi_widget_callback) ;


   // -----------------------------------
   // Attempt Data Retrieval from WPDB
   // -----------------------------------
   $a_wccwppi_wpdb = get_option( $c_wccwppi_option_name , '' ) ;

   if ($a_wccwppi_wpdb) {

      // traverse main data set array, populating with WPDB-retrieved options
      foreach ($a_wccwppi_option_params as $index => $value) {
         if (isset($a_wccwppi_wpdb[$index])) {
            $a_wccwppi_option_params[$index] = wccwppi_sanitize($a_wccwppi_wpdb[$index]) ;
         }
      } // end: foreach-looper

   } // endif: Options retrieved from WPDB


   // -----------------------------------
   // If POST, Populate Main Data Set Array
   // -----------------------------------
   if ($f_valid_post) {

      // traverse main data set array, populating with POSTed data
      foreach ($a_wccwppi_option_params as $index => $value) {

         if ( isset($_POST[$index]) ) {
            $a_wccwppi_option_params[$index] = wccwppi_sanitize($_POST[$index]) ;
         } // endif: field was POSTed

         else {
            if ( in_array( $index , $a_wccwppi_params_checks ) ) {
               $a_wccwppi_option_params[$index] = NULL ;
            } // endif: field is a registered checkbox (that is unchecked)
         } // endif: field was *NOT* POSTed (probably a checkbox)

      } // end: foreach-looper

   } // endif: Options retrieved from POST


   // -----------------------------------
   // Write Options to WPDB if 1st Run or Valid Incoming POST
   // -----------------------------------
   if ( (!($a_wccwppi_wpdb)) || ($f_valid_post) ) {
      update_option( $c_wccwppi_option_name , $a_wccwppi_option_params ) ;
   }


   // -------------------------------
   // Get WCC WP Plugin Tip/News (via cURL or WP HTTP API)
   // -------------------------------
   $v_wccdf_url_tips = ($c_wccdf_url . $c_wccdf_tips . $c_wccwppi_version) ;
   if (wccwppi_curly()) {
      $a_wccdf_call = wccwppi_cURLfriend( $v_wccdf_url_tips , NULL , NULL , $c_wccdf_retries , $c_wccdf_timeout ) ;
      if ($a_wccdf_call[1] != 'n') {
         $v_html_tips = ( "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCDF Call Failure (cURL method) -->\r\n\r\n" ) ;
      } // endif: some kind of WCCDF Call problem
      else {
         $v_html_tips = $a_wccdf_call[0] ;
      } // endif: WCCDF Call seemed to complete OK
   } // endif: make call via cURL
   else {
      if (wccwppi_hapi()) {
         $h_wccdf_wp_remote = wp_remote_get( $v_wccdf_url_tips , array( 'method'=>'GET' , 'timeout'=>$c_wccdf_timeout , 'user-agent'=>('WatchCount.com WordPress Plugin client (WP HTTP API) version: ' . $c_wccwppi_version) , 'body'=>NULL ) ) ;
         $a_wccdf_call[0] = wp_remote_retrieve_body($h_wccdf_wp_remote) ;
         if (is_wp_error($h_wccdf_wp_remote)) {
            $v_html_tips = ( "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCDF Call Failure (WP HTTP API method) -->\r\n\r\n" ) ;
         } // endif: some kind of WCCDF Call problem
         else {
            $v_html_tips = $a_wccdf_call[0] ;
         } // endif: WCCDF Call seemed to complete OK
      } // endif: make call via WP HTTP API
      else {
         $v_html_tips = ( "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCDF Call Failure (cURL Absent + WP HTTP API Absent) -->\r\n\r\n" ) ;
      } // endif: WP HTTP API not present
   } // endif: cURL not installed


   // -----------------------------------
   // Build Form/Page Display
   // -----------------------------------
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page Content -->\r\n" ) ;
   $v_html_output .= ( "<div class=\"wrap\">\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-frame\"><tr>\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Frame-Cell Left -->\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-frame\" style=\"width: 500px;\">\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: Info -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">\r\n" ) ;
   $v_html_output .= ( "<a href=\"http://www.WatchCount.com/go/?link=wp_i_wcc\" class=\"\" target=\"_blank\"><img src=\"http://www.WatchCount.net/images/WCClogo2.png\" class=\"wccwppi-wcclogo\" title=\"WatchCount.com...\" alt=\"WatchCount.com\" /></a>\r\n" ) ;
   $v_html_output .= ( "WatchCount.com WordPress Plugin (WCCWPPI)\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n" ) ;
   $v_html_output .= ( "<div class=\"wccwppi-text\" style=\"margin-bottom: -4px;\"><ul class=\"wccwppi-list wccwppi-text-style1\">\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Here you'll specify settings for the WCCWPPI that can appear as a blog sidebar widget or within your posts. <span class=\"wccwppi-highlight2\">All fields below are optional.</span></li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">You are <span class=\"wccwppi-underline\">highly encouraged</span> to <a href=\"http://www.WatchCount.com/go/?link=wp_i_pi_alerts\" class=\"wccwppi-links\" target=\"_blank\" title=\"WCCWPPI Notification List for Important Alerts...\">join our WCCWPPI Notification List</a> so we can alert/email you of critical, or recommended, upgrades. <span class=\"wccwppi-gray1\">(No spam!)</span></li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Comment on this plugin, or ask for help, on <a href=\"http://www.WatchCount.com/go/?link=wp_gcwccwppi\" class=\"wccwppi-links\" target=\"_blank\" title=\"Global Conversations page for WCCWPPI...\">our Global Conversations page</a>. <a href=\"http://www.WatchCount.com/go/?link=wp_i_pi\" class=\"wccwppi-links\" target=\"_blank\" title=\"About WCCWPPI (@ WatchCount.com)...\">On our website</a> is comprehensive information about the WCCWPPI. You can also <a href=\"http://www.WatchCount.com/go/?link=wp_i_contact\" class=\"wccwppi-links\" target=\"_blank\" title=\"Email WatchCount.com...\">email us privately</a> with any pressing questions.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Save/Update your settings by clicking the button down below.</li>\r\n" ) ;
   $v_html_output .= ( "</ul></div>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: Info -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: Admin/Settings -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">Settings</td></tr>\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: Large Content Cell -->\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: Submission Form -->\r\n" ) ;
   $v_html_output .= ( "<form action=\"\" target=\"_self\" method=\"post\" accept-charset=\"UTF-8\" class=\"wccwppi-settings\" name=\"wccwppi-settings-form\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"hidden\" name=\"" . $c_wccwppi_nonce_field . "\" id=\"" . $c_wccwppi_nonce_field . "\" value=\"" . $c_wccwppi_nonce_unique . "\" />\r\n\r\n" ) ;
   if ($f_valid_post) {
      $v_html_output .= ( "\r\n<div class=\"wccwppi-msg wccwppi-notice\">Your settings have been saved.</div>\r\n\r\n" ) ;
   }
   if (($f_post) && (!($f_valid_post))) {
      $v_html_output .= ( "\r\n<div class=\"wccwppi-msg wccwppi-alert\"><b>Error 3:</b> Authorization failure; settings <b>not</b> updated/saved. (Possible solutions: Logging out and back in to WordPress. Or, uninstalling/reinstalling this plugin. This error is rare, so <a href=\"http://www.WatchCount.com/go/?link=wp_i_contact\" class=\"wccwppi-links2\" target=\"_blank\" title=\"Email WatchCount.com...\">we'd love to hear about</a> it and offer our assistance.)</div>\r\n\r\n" ) ;
   }
   if (!($f_post_caller_installed)) {
      $v_html_output .= ( "\r\n<div class=\"wccwppi-msg wccwppi-alert\"><b>Error 9:</b> No HTTP transports [cURL + HTTP API] available. (Probable solution: Upgrade your WordPress installation. You're also welcome to <a href=\"http://www.WatchCount.com/go/?link=wp_i_contact\" class=\"wccwppi-links2\" target=\"_blank\" title=\"Email WatchCount.com...\">contact us</a> for help with this error.)</div>\r\n\r\n" ) ;
   }
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (Keywords) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg1\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\" colspan=\"2\"><div class=\"wccwppi-controls-header\">Keywords/Content Selection</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">eBay Country/Site:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_cc\" id=\"wccwppi_cc\" size=\"1\" title=\"Select an eBay country-site...\" tabindex=\"1\">\r\n" ) ;
   foreach ($a_options_countries as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_cc'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Keywords:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_kw\" id=\"wccwppi_kw\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_kw'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"120\" size=\"48\" title=\"Enter search terms for Most Popular eBay items...\" tabindex=\"2\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Categories:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_cats\" id=\"wccwppi_cats\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_cats'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"90\" size=\"48\" title=\"Enter eBay category numbers to restrict your search...\" tabindex=\"3\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"checkbox\" class=\"wccwppi-settings-checkbox\" name=\"wccwppi_kwnottags\" id=\"wccwppi_kwnottags\" value=\"1\" " . ( ($a_wccwppi_option_params['wccwppi_kwnottags']) ? ("checked=\"checked\"") : ('') ) . " title=\"Checked: Use specified keywords/categories instead of post tags\" tabindex=\"4\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">Keywords Keep Priority Over Post Tags</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Show eBay Daily Deals:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_ccedd\" id=\"wccwppi_ccedd\" size=\"1\" title=\"Show eBay Daily Deals...\" tabindex=\"5\">\r\n" ) ;
   foreach ($a_options_edd as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_ccedd'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-text\" colspan=\"2\">\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">Select the eBay country-site to display items from (note: US eBay Motors is a distinct site). Don't see your country listed? <a href=\"http://www.WatchCount.com/go/?link=wp_i_contact\" class=\"wccwppi-links\" target=\"_blank\" title=\"Email WatchCount.com...\">Submit a support request</a> and we'll prioritize its inclusion in the next WCCWPPI release.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">When selecting global/default keywords, you can comma-separate multiple terms. You can also use <a href=\"http://www.WatchCount.com/go/?link=wp_i_pi_faq_syntax\" class=\"wccwppi-links\" target=\"_blank\" title=\"About eBay's advanced search syntax...\">advanced eBay search syntax</a> for more options. (To display 1 specific eBay item, simply enter its listing number into the Keywords box.) Categories to restrict your search results to should be entered as 1 or more comma-separated category numbers. To see category number lists, go to our <a href=\"http://www.WatchCount.com/go/?link=wp_i_advsearch\" class=\"wccwppi-links\" target=\"_blank\" title=\"WatchCount.com Advanced Search page...\">advanced search page</a>, select your eBay country-site, then click the 'eBay category numbers' link.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">You can also showcase <a href=\"http://www.WatchCount.com/go/?link=wp_i_edd\" class=\"wccwppi-links\" target=\"_blank\" title=\"eBay Daily Deals...\">eBay Daily Deals</a> &#8212; specially discounted offers that eBay hand-picks from trusted sellers each day. These Daily Deals sell fast, are always for a fixed-price, and are offered with free shipping/delivery.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">You can specify that your keyword selections maintain priority over post tags (otherwise the tags you choose for your blog posts determine what's displayed in the WCCWPPI).</p>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (Keywords) -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (Skins) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg1\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\" colspan=\"2\"><div class=\"wccwppi-controls-header\">Skins</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Skin:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_skin\" id=\"wccwppi_skin\" size=\"1\" title=\"Select a background/skin...\" tabindex=\"6\">\r\n" ) ;
   foreach ($a_options_skins as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_skin'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(<a href=\"http://www.WatchCount.com/go/?link=wp_i_pi_docs_skins\" class=\"wccwppi-links\" target=\"_blank\" title=\"WCCWPPI Skin Designs...\">view</a> available skin designs)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Inline Search:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_insearch\" id=\"wccwppi_insearch\" size=\"1\" title=\"Allow visitors to search eBay items...\" tabindex=\"7\">\r\n" ) ;
   foreach ($a_options_insearch as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_insearch'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Background Color:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_colorbg\" id=\"wccwppi_colorbg\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_colorbg'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"6\" size=\"6\" title=\"Background color, if using no skin (enter 6-digit color code)...\" tabindex=\"8\" />&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(<a href=\"http://www.WatchCount.com/go/?link=wp_colorchart\" class=\"wccwppi-links\" target=\"_blank\" title=\"Online color chart...\">lookup</a> 6-digit hexadecimal color code)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-text\" colspan=\"2\">\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">Choose a skin/background for the plugin display, or leave the default skin. If you choose 'no skin', you can specify a background color.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">'Inline Search' shows a small search box so the visitor can enter their own keywords. You can have results re-displayed within the plugin, or send the visitor directly to eBay. You can also hide the search box and display a message instead.</p>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (Skins) -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (Save Settings) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg3\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\"><div class=\"wccwppi-controls-header\">Save/Update Settings</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-submit\" >\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Form Submit Button -->\r\n" ) ;
   $v_html_output .= ( "   <input type=\"submit\" class=\"wccwppi-settings-submit\" name=\"wccwppi-settings-submit\" id=\"wccwppi-settings-submit\" value=\"Save My Settings\" title=\"Update and Save your settings . . .\" tabindex=\"12\" />\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Form Submit Button -->\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (Save Settings) -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (ePN) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg1\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\" colspan=\"2\"><div class=\"wccwppi-controls-header\">ePN/Logo</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Campaign ID:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_campid\" id=\"wccwppi_campid\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_campid'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"14\" size=\"15\" title=\"Enter your newly-generated ePN Campaign ID...\" tabindex=\"9\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Custom ID:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_customid\" id=\"wccwppi_customid\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_customid'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"50\" size=\"48\" title=\"Optional: Enter Custom ID text...\" tabindex=\"10\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Your RevShare Level:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_revshare\" id=\"wccwppi_revshare\" size=\"1\" title=\"Your overall portion of impressions and ePN commissions...\" tabindex=\"11\">\r\n" ) ;
   foreach ($a_options_revlev as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_revshare'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-text\" colspan=\"2\">\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">If you have an ePN account and a blog on your own domain, you can potentially generate commissions when your visitors click through to eBay then make purchases. Enter your newly-generated 10-digit ePN Campaign ID code above.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">If you entered your ePN Campaign ID above, you can opt between 2 impression sharing levels whereby your eBay/ePN affiliate links will be shown in a percentage of plugin impressions/displays (random rotation). 'High/100%' puts your affiliate links in the plugin all the time, but displays a small WatchCount.com logo/backlink. 'Low/50%' shares impressions with us, and so we hide the promo tagline/backlink.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">If you left the ePN CampID field above blank, you can still control the placement or suppression of the WatchCount.com logo/tagline/backlink.</p>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (ePN) -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (Title) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg1\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\" colspan=\"2\"><div class=\"wccwppi-controls-header\">Advanced Customization: Title</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Title:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titlename\" id=\"wccwppi_titlename\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titlename'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"35\" size=\"40\" title=\"Enter custom title text...\" tabindex=\"13\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Font:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titlefont\" id=\"wccwppi_titlefont\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titlefont'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"20\" size=\"20\" title=\"Specify an alternate font to use for your title text...\" tabindex=\"14\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Size:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titlesize\" id=\"wccwppi_titlesize\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titlesize'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"2\" size=\"2\" title=\"Specify an alternate point size for your title text...\" tabindex=\"15\" /> <span class=\"wccwppi-labelsuffix\">(pt)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Text Color:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titlecoltxt\" id=\"wccwppi_titlecoltxt\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titlecoltxt'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"6\" size=\"6\" title=\"Title text color (enter 6-digit color code)...\" tabindex=\"16\" />&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(<a href=\"http://www.WatchCount.com/go/?link=wp_colorchart\" class=\"wccwppi-links\" target=\"_blank\" title=\"Online color chart...\">lookup</a> 6-digit hexadecimal color code)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Background Color:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titlecolbg\" id=\"wccwppi_titlecolbg\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titlecolbg'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"6\" size=\"6\" title=\"Title background color (enter 6-digit color code)...\" tabindex=\"17\" />&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(<a href=\"http://www.WatchCount.com/go/?link=wp_colorchart\" class=\"wccwppi-links\" target=\"_blank\" title=\"Online color chart...\">lookup</a> 6-digit hexadecimal color code)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"checkbox\" class=\"wccwppi-settings-checkbox\" name=\"wccwppi_titledocolbg\" id=\"wccwppi_titledocolbg\" value=\"1\" " . ( ($a_wccwppi_option_params['wccwppi_titledocolbg']) ? ("checked=\"checked\"") : ('') ) . " title=\"Checked: Use background color specified above\" tabindex=\"18\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">Use Background Color</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"checkbox\" class=\"wccwppi-settings-checkbox\" name=\"wccwppi_titlehide\" id=\"wccwppi_titlehide\" value=\"1\" " . ( ($a_wccwppi_option_params['wccwppi_titlehide']) ? ("checked=\"checked\"") : ('') ) . " title=\"Checked: Hide title\" tabindex=\"19\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">Hide Title</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"checkbox\" class=\"wccwppi-settings-checkbox\" name=\"wccwppi_titleund\" id=\"wccwppi_titleund\" value=\"1\" " . ( ($a_wccwppi_option_params['wccwppi_titleund']) ? ("checked=\"checked\"") : ('') ) . " title=\"Checked: Underline title\" tabindex=\"20\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">Underline</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Vertical Offset:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_titley\" id=\"wccwppi_titley\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_titley'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"2\" size=\"2\" title=\"Number of pixels to adjust title vertically...\" tabindex=\"21\" /> <span class=\"wccwppi-labelsuffix\">(px)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-text\" colspan=\"2\">\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">You can override the default WCCWPPI title with your own text, as well as customize its appearance with a variety of formatting options.</p>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (Title) -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Start: WCCWPPI Admin/Settings Page - Control Block (Other) -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-controls wccwppi-controls-bg1\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-header\" colspan=\"2\"><div class=\"wccwppi-controls-header\">Advanced Customization: Other</div></td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Search Button Text:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_sbuttontxt\" id=\"wccwppi_sbuttontxt\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_sbuttontxt'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"17\" size=\"19\" title=\"Specify alternate text for the Inline Search button...\" tabindex=\"22\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Info Message:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_infomsg\" id=\"wccwppi_infomsg\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_infomsg'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"100\" size=\"48\" title=\"Specify a custom informational message to display in place of the Inline Search box...\" tabindex=\"23\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Default Alignment:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <select class=\"wccwppi-settings\" name=\"wccwppi_deffloat\" id=\"wccwppi_deffloat\" size=\"1\" title=\"Specify how in-post plugin displays should be aligned...\" tabindex=\"24\">\r\n" ) ;
   foreach ($a_options_align as $index => $value) {
      $v_html_output .= ( "      <option value=\"" . $index . "\"" . ( ($a_wccwppi_option_params['wccwppi_deffloat'] == $index) ? (" selected=\"selected\"") : ('') ) . ">" . $value . "</option>\r\n" ) ;
   }
   $v_html_output .= ( "   </select>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Max Results:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_climit\" id=\"wccwppi_climit\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_climit'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"2\" size=\"2\" title=\"Maximum number of eBay items to display...\" tabindex=\"25\" />&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(0 = default values)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Divider Color:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_divcol\" id=\"wccwppi_divcol\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_divcol'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"6\" size=\"6\" title=\"Item divider color (enter 6-digit color code)...\" tabindex=\"26\" />&#160;&#160;&#160;<span class=\"wccwppi-labelsuffix\">(<a href=\"http://www.WatchCount.com/go/?link=wp_colorchart\" class=\"wccwppi-links\" target=\"_blank\" title=\"Online color chart...\">lookup</a> 6-digit hexadecimal color code)</span>\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"checkbox\" class=\"wccwppi-settings-checkbox\" name=\"wccwppi_rssdisable\" id=\"wccwppi_rssdisable\" value=\"1\" " . ( ($a_wccwppi_option_params['wccwppi_rssdisable']) ? ("checked=\"checked\"") : ('') ) . " title=\"Checked: Entire WCCWPPI display disabled inside RSS/feeds.\" tabindex=\"27\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">RSS/Feeds: Disable Entire WCCWPPI Display</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-left\">Admin/Beta Use:</td>\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-controls wccwppi-controls-right\">\r\n" ) ;
   $v_html_output .= ( "   <input type=\"text\" class=\"wccwppi-settings-text\" name=\"wccwppi_fc\" id=\"wccwppi_fc\" value=\"" . htmlentities( $a_wccwppi_option_params['wccwppi_fc'] , ENT_QUOTES , 'UTF-8' ) . "\" maxlength=\"30\" size=\"32\" title=\"(for admin use only)\" />\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "</tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-controls wccwppi-controls-text\" colspan=\"2\">\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">You can specify the search button text, or if you hid the Inline Search box you can display a custom information message instead.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">'Default Alignment' places in-post WCCWPPI displays to the left or right. 'Max Results' limits the number of search results displayed. You can also specify the divider color that separates items.</p>\r\n" ) ;
   $v_html_output .= ( "<p class=\"wccwppi-p wccwppi-text-style1\">To comply with ePN ToS, we disable all eBay affiliate links inside WCCWPPI displays embedded within your blog's RSS feeds. You can also opt to take this a step further and disable the entire WCCWPPI display within feeds.</p>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Control Block (Other) -->\r\n\r\n" ) ;
   $v_html_output .= ( "</form>\r\n" ) ;
   $v_html_output .= ( "<!-- End: Submission Form -->\r\n\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "<!-- End: Large Content Cell -->\r\n\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: Admin/Settings -->\r\n\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Frame-Cell Left -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Frame-Cell Middle -->\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-frame\" style=\"width: 276px;\">\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: News -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">WCCWPPI News/Info</td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n" ) ;
   $v_html_output .= ( $v_html_tips ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: News -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: Live Preview -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">Live Preview</td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n" ) ;
   $v_html_output .= ( wccwppi_execute('admin') ) ;
   $v_html_output .= ( "<div class=\"wccwppi-text wccwppi-fineprint wccwppi-gray1 wccwppi-margintopper2\">The WordPress Theme you use may slightly alter the appearance of the WCCWPPI on this page and/or in your blog's posts or sidebar. While the above is a live preview, you'll also want to review its actual public appearance." . ( ($f_sidebar_widget_installed) ? (" (<a href=\"" . $c_blog_url . "\" class=\"wccwppi-links\" target=\"_blank\" title=\"Your blog's homepage...\">blog homepage</a>)") : ('') ) . "</div>\r\n" ) ;
   $v_html_output .= ( "<div class=\"wccwppi-text wccwppi-fineprint wccwppi-margintopper1\">Click the 'Save My Settings' button to the left to update both your saved settings and the live preview above.</div>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: Live Preview -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: Quick Tips -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">More Quick Tips</td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n" ) ;
   if (!($f_sidebar_widget_installed)) {
      $v_html_output .= ( "\r\n<div class=\"wccwppi-msg wccwppi-notice\">Don't forget to visit your WordPress '<a href=\"" . $c_blog_wpurl . "/wp-admin/widgets.php\" class=\"wccwppi-links\" target=\"_top\" title=\"Your blog's Widgets settings screen...\">Widgets</a>' screen and put/drag the " . $c_wccwppi_widget_nav . " widget into the sidebar.</div>\r\n\r\n" ) ;
   }
   $v_html_output .= ( "<div class=\"wccwppi-text\" style=\"margin-bottom: -4px;\"><ul class=\"wccwppi-list wccwppi-text-style1\">\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Decide if you want the WCCWPPI to feed off the keywords/categories you specify up above, or to <span class=\"wccwppi-highlight1\">automatically read the post tags</span> that WordPress asks for in each post. Then, check the 'Keywords/Tags Priority' box on this page accordingly.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Be sure to <a href=\"http://www.WatchCount.com/go/?link=wp_i_pi_alerts\" class=\"wccwppi-links\" target=\"_blank\" title=\"WCCWPPI Notification List for Important Alerts...\">join our WCCWPPI Notification List</a> so we can alert you to any important WCCWPPI information (like critical upgrades).</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">When blogging, try to select at least <span class=\"wccwppi-highlight1\">1 or 2 post tags</span> that are termed generally enough to reliably produce eBay search results.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Include the WCCWPPI within your blog posts simply by typing an 'anchor tag' within your text: <span class=\"wccwppi-highlight1\">[eBay]</span> . To display a specific item: <span class=\"wccwppi-highlight1\">[eBay 1234567890]</span> .</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">When specifying keywords, use a comma to <span class=\"wccwppi-highlight1\">separate multiple phrases</span>. This helps to ensure the retrieval and display of popular eBay items.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">While this page is (currently) only in English, the WCCWPPI displays with localized language based on the eBay site you select. You can also <span class=\"wccwppi-highlight1\">customize much of the text</span> phrases within it.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">You can <span class=\"wccwppi-highlight1\">refresh this page</span> by clicking the '" . $c_wccwppi_widget_nav . "' link, often located in the left sidebar (depending on your version of WordPress and applied Theme).</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">We're happy to create <span class=\"wccwppi-highlight1\">new/custom skins</span> in response to user requests. Our email contact link is up top.</li>\r\n" ) ;
   $v_html_output .= ( "   <li class=\"wccwppi-list wccwppi-text-style1\">Learn more about the <a href=\"http://www.WatchCount.com/go/?link=wp_i_pi\" class=\"wccwppi-links\" target=\"_blank\" title=\"About WCCWPPI (@ WatchCount.com)...\">WCCWPPI at WatchCount.com</a>.</li>\r\n" ) ;
   $v_html_output .= ( "</ul></div>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: Quick Tips -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Box: Current Version -->\r\n" ) ;
   $v_html_output .= ( "<table class=\"wccwppi-box\">\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-header\">WCCWPPI Version</td></tr>\r\n" ) ;
   $v_html_output .= ( "<tr><td class=\"wccwppi-content wccwppi-text-style1\" align=\"center\">\r\n" ) ;
   $v_html_output .= ( "<div class=\"wccwppi-text wccwppi-text-style1\">You are currently running version <span class=\"wccwppi-highlight2\">" . $c_wccwppi_version . "</span> of the WatchCount.com WordPress Plugin.</div>\r\n" ) ;
   $v_html_output .= ( "</td></tr>\r\n" ) ;
   $v_html_output .= ( "</table>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Box: Current Version -->\r\n\r\n" ) ;
   $v_html_output .= ( "</td>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Frame-Cell Middle -->\r\n\r\n" ) ;
   $v_html_output .= ( "<!-- Begin: WCCWPPI Admin/Settings Page - Frame-Cell Right -->\r\n" ) ;
   $v_html_output .= ( "<td class=\"wccwppi-frame\"><br /></td>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page - Frame-Cell Right -->\r\n\r\n" ) ;
   $v_html_output .= ( "</tr></table>\r\n" ) ;
   $v_html_output .= ( "</div>\r\n" ) ;
   $v_html_output .= ( "<!-- End: WCCWPPI Admin/Settings Page Content -->\r\n" ) ;

   echo($v_html_output) ;

   return(TRUE) ;

} // end function: wccwppi_adminpage_display()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_cURLfriend() makes WCCAPI call using cURL
 * ---------------------------------------------------------------
 * adapted from:  http://www.Helios825.org/cURLfriend.php  [that's me - same author]
 * ---------------------------------------------------------------
 */
function wccwppi_cURLfriend ($In_URL='', $In_POST='', $In_Headers=array(), $In_RetryCount=2, $In_Timeout=40) {

   // -----------------------------------
   // Initialize Internal Variables
   // -----------------------------------
   $Out = array() ;
   $StatusCode = 'x' ;
   $cURLresponse_data = '' ;
   $cURLresponse_errorNumber = 0 ;
   $cURLresponse_errorString = '' ;
   $cURLresponse_info = array() ;
   $cURLresponse_info_HTTPcode = '' ;
   $cURLresponse_info_TotalTime = '' ;
   $cURLresponse_info_DLsize = '' ;
   $f_DoPOST = FALSE ;
   $cURLcall_LoopsMax = 0 ;
   $cURLcall_LoopCounter = 0 ;
   $cURLcall_Loops = 0 ;
   $cURLcall_f_LoopAbort = FALSE ;


   // -----------------------------------
   // Constants
   // -----------------------------------
   $c_wccwppi_version = constant('WCCWPPI_CLIENT_VERSION') ;
   $c_RetryCount_HardMax = 4 ;   // hard internal maximum on retries to API
   $c_cURLopt_MaxPersConn = 12 ;   // maximum simultaneous persistent connections
   $c_cURLopt_MaxRedirs = 4 ;   // maximum number of chained 301/2 redirects to follow before cURLfriend (rightfully) gets fed up with incessant excuses
   $c_cURLopt_ConnectTimeout = 3 ;   // while attempting to connect, number of seconds before cURLfriend gives up (short attention span?)
   $c_cURLopt_UserAgent = ( 'WatchCount.com WordPress Plugin client (cURL) version: ' . ((isset($In_POST['wccwppi_ver'])) ? ($In_POST['wccwppi_ver']) : ($c_wccwppi_version)) ) ;   // cURLfriend has a full name, just like the rest of us


   // -----------------------------------
   // Prep/Clean
   // -----------------------------------
   $f_DoPOST = (!(empty($In_POST))) ;   // make call as HTTP POST?
   $In_Headers = ((empty($In_Headers)) ? (array()) : ($In_Headers)) ;   // $In_Headers needs to be an array, whether present or not
   $In_RetryCount = (($In_RetryCount < 0) ? (0) : ($In_RetryCount)) ;   // $In_RetryCount needs to be >=0
   $In_Timeout = (($In_Timeout < 0) ? (0) : ($In_Timeout)) ;   // $In_Timeout needs to be >=0


   // -----------------------------------
   // A Few Simple Preliminary Checks
   // -----------------------------------
   // * cURL library must be installed
   // * endpoint URL must start with 'http' ('https' OK)
   // * HTTP headers, if used, must be an array
   // -----------------------------------
   if (!(wccwppi_curly())) {
      $StatusCode = 'c' ;   // error: cURL not installed [invalid]
   }
   else {
      if ( (stripos($In_URL,"http") === 0) && (is_array($In_Headers)) ) {
         $StatusCode = 'v' ;   // none of the above checks failed [valid]
      }
      else {
         $StatusCode = 'i' ;   // one of the above checks failed [invalid]
      }
   }


   // -----------------------------------
   // Proceed With cURL Call
   // -----------------------------------
   if ('v' == $StatusCode) {

      // -----------------------------------
      // Setup cURL Session
      // -----------------------------------
      $cURLhandle = curl_init() ;
      curl_setopt($cURLhandle, CURLOPT_URL, $In_URL) ;
      curl_setopt($cURLhandle, CURLOPT_FOLLOWLOCATION, TRUE) ;
      curl_setopt($cURLhandle, CURLOPT_MAXREDIRS, $c_cURLopt_MaxRedirs) ;
      curl_setopt($cURLhandle, CURLOPT_USERAGENT, $c_cURLopt_UserAgent) ;
      curl_setopt($cURLhandle, CURLOPT_NOBODY, FALSE) ;
      curl_setopt($cURLhandle, CURLOPT_POST, $f_DoPOST) ;
      curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYPEER, FALSE) ;
      curl_setopt($cURLhandle, CURLOPT_SSL_VERIFYHOST, 0) ;
      curl_setopt($cURLhandle, CURLOPT_MAXCONNECTS, $c_cURLopt_MaxPersConn) ;
      curl_setopt($cURLhandle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1) ;
      curl_setopt($cURLhandle, CURLOPT_CLOSEPOLICY, CURLCLOSEPOLICY_LEAST_RECENTLY_USED) ;
      curl_setopt($cURLhandle, CURLOPT_TIMEOUT, $In_Timeout) ;
      curl_setopt($cURLhandle, CURLOPT_CONNECTTIMEOUT, $c_cURLopt_ConnectTimeout) ;
      curl_setopt($cURLhandle, CURLOPT_FAILONERROR, TRUE) ;
      curl_setopt($cURLhandle, CURLOPT_HTTPHEADER, $In_Headers) ;
      if ($f_DoPOST) {
         curl_setopt($cURLhandle, CURLOPT_POSTFIELDS, $In_POST) ;
      }
      curl_setopt($cURLhandle, CURLOPT_HEADER, FALSE) ;
      curl_setopt($cURLhandle, CURLOPT_VERBOSE, FALSE) ;
      curl_setopt($cURLhandle, CURLOPT_RETURNTRANSFER, TRUE) ;


      // -----------------------------------
      // Make cURL Call With Retries
      // -----------------------------------
      $cURLcall_LoopsMax = ( min($c_RetryCount_HardMax, $In_RetryCount) + 1 ) ;
      $cURLcall_LoopCounter = $cURLcall_LoopsMax ;
      $cURLcall_f_LoopAbort = FALSE ;

      while (($cURLcall_LoopCounter) && (!($cURLcall_f_LoopAbort))) {

         $cURLresponse_data = curl_exec($cURLhandle) ;
         $cURLresponse_errorNumber = curl_errno($cURLhandle) ;

         if ( (empty($cURLresponse_data)) || ($cURLresponse_errorNumber != 0) ) {
            $StatusCode = "r" ;
            $cURLresponse_data = "" ;
            $cURLcall_f_LoopAbort = FALSE ;
         } // some problem acquiring proper response [no response | cURL error]
         else {
            $StatusCode = "n" ;
            $cURLcall_f_LoopAbort = TRUE ;
         } // response probably acquired OK

         $cURLcall_LoopCounter-- ;

      } // while-looper making cURL calls

      $cURLcall_Loops = ($cURLcall_LoopsMax - $cURLcall_LoopCounter) ;


      // -----------------------------------
      // Acquire More Info About Last cURL Call
      // -----------------------------------
      $cURLresponse_errorString = curl_error($cURLhandle) ;
      $cURLresponse_info = curl_getinfo($cURLhandle) ;
      $cURLresponse_info_HTTPcode =  (string) ((isset($cURLresponse_info["http_code"])) ? ($cURLresponse_info["http_code"]) : ("")) ;
      $cURLresponse_info_TotalTime = (string) ((isset($cURLresponse_info["total_time"])) ? ($cURLresponse_info["total_time"]) : ("")) ;
      $cURLresponse_info_DLsize =    (string) ((isset($cURLresponse_info["size_download"])) ? ($cURLresponse_info["size_download"]) : ("")) ;


      // -----------------------------------
      // Close cURL Session
      // -----------------------------------
      curl_close($cURLhandle) ;


   } // end: cURL call was made

   else {
      // [nothing here]
   } // end: cURL call was not made


   // -----------------------------------
   // Build Returned Array
   // -----------------------------------
   $Out[0] = $cURLresponse_data ;   // [0] = cURL response from API in XML (from last attempt)
   $Out[1] = $StatusCode ;   // [1] = cURLfriend Status Code
   $Out[2] = $cURLresponse_errorNumber ;   // [2] = cURL error number (of last attempt) [ see: http://curl.haxx.se/libcurl/c/libcurl-errors.html ]
   $Out[3] = $cURLresponse_errorString ;   // [3] = cURL error string (of last attempt)
   $Out[4] = $cURLresponse_info_HTTPcode ;   // [4] = cURL call info: last received HTTP code (of last attempt)
   $Out[5] = $cURLresponse_info_TotalTime ;   // [5] = cURL call info: total time (of last attempt)
   $Out[6] = $cURLresponse_info_DLsize ;   // [6] = cURL call info: size of download/response (of last attempt)
   $Out[7] = $f_DoPOST ;   // [7] = cURL call (if made) was done via HTTP POST ('false' means via HTTP GET)
   $Out[8] = $cURLcall_Loops ;   // [8] = number of cURL call tries

   return($Out) ;


   // -----------------------------------
   // cURLfriend Status Codes
   // -----------------------------------
   // x = initial status [intermediary code] [fail]
   // v = initial checks passed [intermediary code]
   // i = initial check(s) failed, cURL call not made [fail]
   // c = cURL not installed, call not made [fail]
   // r = cURL call failed (in some way) on last attempt [fail]
   // n = normal (cURL call made; no problems identified) [pass]
   //


} // end function: wccwppi_cURLfriend()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_execute_display_sidebar() - outputs WCCWPPI display to browser for sidebar widget
 * ---------------------------------------------------------------
 * $In_Widget: widget registration pass-back arguments (array)
 * ---------------------------------------------------------------
 * note: this function must be unique (only used for WCCWPPI display as sidebar widget)
 * note: this function name must be copied into global constant 'WCCWPPI_WIDGET_CALLBACK'
 * ---------------------------------------------------------------
 */
function wccwppi_execute_display_sidebar ( $In_Widget=array() ) {

   $Out = '' ;

   $Out_Passback = ( (is_array($In_Widget)) ? ($In_Widget) : (array()) ) ;
   $Out .= ( wccwppi_execute( 'sidebar' , $Out_Passback ) ) ;

   echo($Out) ;

} // end function: wccwppi_execute_display_sidebar()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_execute_post() - returns WCCWPPI display to shortcode processor for in-post display
 * ---------------------------------------------------------------
 * $In_ShortcodeAtts: shortcode attributes passed back (array)
 * $In_Content: shortcode content string passed back [not used]
 * $In_Code: shortcode name passed back [not used]
 * ---------------------------------------------------------------
 */
function wccwppi_execute_post ( $In_ShortcodeAtts=array() , $In_Content=NULL , $In_Code='' ) {

   $Out = '' ;

   $In_ShortcodeAtts = ( (empty($In_ShortcodeAtts)) ? (array()) : ($In_ShortcodeAtts) ) ;   // $In_ShortcodeAtts needs to be an array
   $Out .= ( wccwppi_execute( 'post' , array() , $In_ShortcodeAtts ) ) ;

   return($Out) ;

} // end function: wccwppi_execute_post()


/**
 * ---------------------------------------------------------------
 * Function: wccwppi_execute() - obtains WCCWPPI parameters from WPDB then calls WCCAPI to retrieve HTML display
 * ---------------------------------------------------------------
 * $In_Loc: location of plugin display's appearance
 * $In_Widget: array passed back from widget registration containing flanking HTML: ['before_widget'], ['after_widget'], ['before_title'], ['after_title']
 * $In_ShortcodeAtts: shortcode attributes from blogger for in-post parameter override
 * ---------------------------------------------------------------
 */
function wccwppi_execute ( $In_Loc='' , $In_Widget=array() , $In_ShortcodeAtts=array() ) {

   // -----------------------------------
   // Get Globals + Environment Variables
   // -----------------------------------
   global $wp_version ;
   $c_wccwppi_version = constant('WCCWPPI_CLIENT_VERSION') ;
   $c_wpver = ( (isset($wp_version)) ? ($wp_version) : (get_bloginfo('version')) ) ;
   $c_1post = is_single() ;
   $c_isfeed = is_feed() ;
   $c_postid = get_the_ID() ;
   $c_phpver = phpversion() ;
   $c_userip = getenv('REMOTE_ADDR') ;
   $c_blogip = getenv('SERVER_ADDR') ;
   $c_userref = getenv('HTTP_REFERER') ;
   $c_userbrow = getenv('HTTP_USER_AGENT') ;
   $c_serverurl = wccwppi_myurl() ;


   // -----------------------------------
   // Definitions
   // -----------------------------------
   $c_wccapi_retries = 2 ;   // number of times to re-call WCCAPI on connection failure
   $c_wccapi_timeout = 8 ;   // WCCAPI connection timeout duration (seconds)
   $c_wccapi_url = 'http://www.WatchCount.net/ws.php' ;   // URL of "WatchCount.com Plugins API" (WCCAPI) endpoint  [if root/index file: needs trailing slash]
   $c_wccapi_anchor_prefix = 'wccwppi_anchor_' ;   // parameter array index prefix for anchor values
   $c_wccapi_anchor_maxcount = 25 ;   // hard max for number of anchor fields that can be passed to WCCAPI
   $c_wccapi_anchor_maxlenname = 20 ;   // max length of anchor field names
   $c_wccapi_anchor_maxlenval = 90 ;   // max length of anchor field values
   $f_wccapi_diags = TRUE ;   // append WCCAPI call diagnostics details to HTML output?
   $a_wccapi_unstored = array( 'wccwppi_isfeed' , 'wccwppi_posttags' , 'wccwppi_postid' , 'wccwppi_1post' , 'wccwppi_flags' , 'wccwppi_kwuser' , 'wccwppi_loc' , 'wccwppi_ver' , 'wccwppi_verwp' , 'wccwppi_verphp' , 'wccwppi_userip' , 'wccwppi_userref' , 'wccwppi_userbrow' , 'wccwppi_serverurl' ) ;   // data fields unstored in WPDB


   // -----------------------------------
   // Define Main Plugin Data Set [POST fields for WCCAPI call]
   // -----------------------------------
   $a_wccwppi_params = array(  'wccwppi_ver'=>($c_wccwppi_version) ,
                               'wccwppi_verwp'=>($c_wpver) ,
                               'wccwppi_verphp'=>($c_phpver) ,
                               'wccwppi_1post'=>($c_1post) ,
                               'wccwppi_isfeed'=>($c_isfeed) ,
                               'wccwppi_postid'=>($c_postid) ,
                               'wccwppi_userip'=>($c_userip) ,
                               'wccwppi_userref'=>($c_userref) ,
                               'wccwppi_userbrow'=>($c_userbrow) ,
                               'wccwppi_serverurl'=>($c_serverurl) ,
                               'wccwppi_kwuser'=>'' ,
                               'wccwppi_loc'=>'' ,
                               'wccwppi_flags'=>'' ,
                               'wccwppi_posttags'=>'' ,

                               'wccwppi_cc'=>'' ,
                               'wccwppi_ccedd'=>'' ,
                               'wccwppi_kw'=>'' ,
                               'wccwppi_cats'=>'' ,
                               'wccwppi_revshare'=>'' ,
                               'wccwppi_campid'=>'' ,
                               'wccwppi_customid'=>'' ,
                               'wccwppi_insearch'=>'' ,
                               'wccwppi_divcol'=>'' ,
                               'wccwppi_colorbg'=>'' ,
                               'wccwppi_climit'=>'' ,
                               'wccwppi_sbuttontxt'=>'' ,
                               'wccwppi_infomsg'=>'' ,
                               'wccwppi_titlename'=>'' ,
                               'wccwppi_titlehide'=>'' ,
                               'wccwppi_titleund'=>'' ,
                               'wccwppi_titlecolbg'=>'' ,
                               'wccwppi_titledocolbg'=>'' ,
                               'wccwppi_titlecoltxt'=>'' ,
                               'wccwppi_titlefont'=>'' ,
                               'wccwppi_titlesize'=>'' ,
                               'wccwppi_titley'=>'' ,
                               'wccwppi_skin'=>'' ,
                               'wccwppi_deffloat'=>'' ,
                               'wccwppi_kwnottags'=>'' ,
                               'wccwppi_rssdisable'=>'' ,
                               'wccwppi_fc'=>''  ) ;


   // -----------------------------------
   // Initialize Other Variables
   // -----------------------------------
   $index = '' ;
   $value = '' ;
   $index_cleaned = '' ;
   $value_cleaned = '' ;
   $newindex = '' ;
   $counter = 0 ;
   $a_wccapi_call = array() ;   // for making the WCCAPI call
   $h_wccapi_wp_remote = '' ;   // handle, if using WP HTTP API
   $a_wccwppi_option_params = array() ;   // for fetching options from WPDB
   $a_get_the_tags = array() ;   // for fetching post tags
   $v_tag_list = '' ;   // default=NULL - we have no tag list yet
   $v_html_output = '' ;   // HTML output that this plugin/function will display

   $In_Widget = ( (empty($In_Widget)) ? (array()) : ($In_Widget) ) ;   // $In_Widget needs to be an array
   $In_ShortcodeAtts = ( (empty($In_ShortcodeAtts)) ? (array()) : ($In_ShortcodeAtts) ) ;   // $In_ShortcodeAtts needs to be an array


   // -----------------------------------
   // Get Post Tags
   // -----------------------------------
   $a_get_the_tags = get_the_tags($c_postid) ;

   if ($a_get_the_tags) {
      $value = NULL ;
      foreach ( $a_get_the_tags as $value ) {
         $v_tag_list .= ( $value->name . ',' ) ;
      } // end: foreach-looper
      $v_tag_list = trim( (wccwppi_sanitize($v_tag_list)) , ' ,' ) ;
   } // endif: tag list is present

   else {
      $v_tag_list = '' ;
   } // endif: no tag list

   $a_wccwppi_params['wccwppi_posttags'] = $v_tag_list ;


   // -----------------------------------
   // Acquire Visitor's Chosen Keywords
   // -----------------------------------
   $a_wccwppi_params['wccwppi_kwuser'] = ( (isset($_POST['wccwppi_kwuser'])) ? ( wccwppi_sanitize($_POST['wccwppi_kwuser']) ) : ($a_wccwppi_params['wccwppi_kwuser']) ) ;


   // -----------------------------------
   // Where Is This Plugin Display Appearing?
   // -----------------------------------
   if ( ('sidebar' == $In_Loc) || ('admin' == $In_Loc) ) {
      $a_wccwppi_params['wccwppi_loc'] = $In_Loc ;
   }
   else {
      if ( ('post' == $In_Loc) || (in_the_loop()) ) {
         $a_wccwppi_params['wccwppi_loc'] = 'post' ;
      }
      else {
         $a_wccwppi_params['wccwppi_loc'] = (wccwppi_sanitize($In_Loc)) ;
      }
   }


   // -----------------------------------
   // Obtain ~All Parameters from WPDB
   // -----------------------------------
   $a_wccwppi_option_params = get_option( constant('WCCWPPI_OPTION_NAME') , '' ) ;

   if ($a_wccwppi_option_params) {

      // traverse main data array, populating with WPDB-retrieved options
      foreach ($a_wccwppi_params as $index => $value) {

         if ( (!(in_array( $index , $a_wccapi_unstored ))) && (!(stristr( $index , $c_wccapi_anchor_prefix ))) && (isset($a_wccwppi_option_params[$index])) ) {
            $a_wccwppi_params[$index] = $a_wccwppi_option_params[$index] ;   // (not bothering to sanitize on DB read)
         }

      } // end: foreach-looper

   } // endif: options retrieved from WPDB

   else {
      $a_wccwppi_params['wccwppi_flags'] .= ( 'd_' ) ;
   } // endif: options not in WPDB


   // -------------------------------
   // Append Shortcode/Anchor Fields to WCCAPI Call
   // -------------------------------
   if ( (!(empty($In_ShortcodeAtts))) && ('post' == $a_wccwppi_params['wccwppi_loc']) ) {

      $counter = 0 ;
      foreach ($In_ShortcodeAtts as $index => $value) {

         if ($counter >= $c_wccapi_anchor_maxcount) {
            break ;
         } // endif: max number of anchor fields hit -> break loop

         else {

            $index_cleaned = ( wccwppi_sanitize( (strtolower((string) ($index))) , $c_wccapi_anchor_maxlenname ) ) ;
            $value_cleaned = ( wccwppi_sanitize( (string) ($value) , $c_wccapi_anchor_maxlenval ) ) ;

            if (($index_cleaned) || ($index_cleaned === '0')) {

               $newindex = ($c_wccapi_anchor_prefix . $index_cleaned) ;
               $a_wccwppi_params[$newindex] = ($value_cleaned) ;
               $counter++ ;

            } // endif: $index_cleaned is valid

         } // endif: we are still under loop maxcount

      } // end: foreach-looper

      $a_wccwppi_params['wccwppi_flags'] .= ( ($counter) ? ( 'a' . $counter . '_' ) : ('') ) ;

   } // endif: anchor fields are present (and location=post) -> loop through 'em


   // -------------------------------
   // Make WCCAPI Call (via cURL or WP HTTP API)
   // -------------------------------
   if (wccwppi_curly()) {

      $a_wccwppi_params['wccwppi_flags'] .= ('curly_') ;
      $a_wccapi_call = wccwppi_cURLfriend($c_wccapi_url, $a_wccwppi_params, NULL, $c_wccapi_retries, $c_wccapi_timeout) ;

      if ($a_wccapi_call[1] != 'n') {
         $v_html_output = ( "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCAPI Call Failure (cURL method) -->\r\n" ) ;
      } // endif: some kind of WCCAPI Call problem

      else {
         $v_html_output  = ( (isset($In_Widget['before_widget'])) ? ($In_Widget['before_widget']) : ('') ) ;
         $v_html_output .= ( $a_wccapi_call[0] ) ;
         $v_html_output .= ( (isset($In_Widget['after_widget'])) ? ($In_Widget['after_widget']) : ('') ) ;
      } // endif: WCCAPI Call seemed to complete OK

   } // endif: make call via cURL

   else {

      if (wccwppi_hapi()) {

         $a_wccwppi_params['wccwppi_flags'] .= ('hapi_') ;
         $h_wccapi_wp_remote = (wp_remote_post( $c_wccapi_url , array( 'method'=>'POST' , 'timeout'=>$c_wccapi_timeout , 'body'=>$a_wccwppi_params , 'user-agent'=>('WatchCount.com WordPress Plugin client (WP HTTP API) version: ' . $a_wccwppi_params['wccwppi_ver']) ) )) ;
         $a_wccapi_call[0] = (wp_remote_retrieve_body($h_wccapi_wp_remote)) ;
         $a_wccapi_call[4] = (wp_remote_retrieve_response_code($h_wccapi_wp_remote)) ;
         $a_wccapi_call[3] = (wp_remote_retrieve_response_message($h_wccapi_wp_remote)) ;
         $a_wccapi_call[7] = TRUE ;
         $a_wccapi_call[8] = '1' ;

         if (is_wp_error($h_wccapi_wp_remote)) {
            $v_html_output = ( "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCAPI Call Failure (WP HTTP API method) -->\r\n" ) ;
         } // endif: some kind of WCCAPI Call problem

         else {
            $v_html_output  = ( (isset($In_Widget['before_widget'])) ? ($In_Widget['before_widget']) : ('') ) ;
            $v_html_output .= ( $a_wccapi_call[0] ) ;
            $v_html_output .= ( (isset($In_Widget['after_widget'])) ? ($In_Widget['after_widget']) : ('') ) ;
         } // endif: WCCAPI Call seemed to complete OK

      } // endif: make call via WP HTTP API

      else {
         $v_html_output = "\r\n\r\n<!-- WatchCount.com WordPress Plugin: WCCAPI Call Failure (cURL Absent + WP HTTP API Absent) -->\r\n" ;
      } // endif: WP HTTP API not present

   } // endif: cURL not installed


   // -------------------------------
   // Append WCCAPI Call Diagnostics
   // -------------------------------
   if ($f_wccapi_diags) {
      $v_html_output .= ( "\r\n\r\n<!-- (m1) -->\r\n" ) ;
      $v_html_output .= ( "<!-- WCCAPI Call Diagnostics:\r\n" ) ;
      $v_html_output .= ( "Plugin Name : WatchCount.com WordPress Plugin\r\n" ) ;
      $v_html_output .= ( 'Plugin Client Version : ' . $a_wccwppi_params['wccwppi_ver'] . "\r\n" ) ;
      $v_html_output .= ( 'Endpoint : ' . $c_wccapi_url . "\r\n" ) ;
      $v_html_output .= ( 'curly? : ' . wccwppi_curly() . "\r\n" ) ;
      $v_html_output .= ( 'hapi? : ' . wccwppi_hapi() . "\r\n" ) ;
      $v_html_output .= ( 'Blog IP : ' . $c_blogip . "\r\n" ) ;
      $v_html_output .= ( 'Timestamp : ' . time() . "\r\n" ) ;
      $v_html_output .= ( 'Code : ' . ( (isset($a_wccapi_call[1])) ? ($a_wccapi_call[1]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'ErrNum : ' . ( (isset($a_wccapi_call[2])) ? ($a_wccapi_call[2]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'ErrStr : ' . ( (isset($a_wccapi_call[3])) ? ($a_wccapi_call[3]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'HTTPcode : ' . ( (isset($a_wccapi_call[4])) ? ($a_wccapi_call[4]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'Time : ' . ( (isset($a_wccapi_call[5])) ? ($a_wccapi_call[5]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'Size : ' . ( (isset($a_wccapi_call[6])) ? ($a_wccapi_call[6]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'Post : ' . ( (isset($a_wccapi_call[7])) ? ($a_wccapi_call[7]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( 'Loops : ' . ( (isset($a_wccapi_call[8])) ? ($a_wccapi_call[8]) : ('') ) . "\r\n" ) ;
      $v_html_output .= ( "//-->\r\n" ) ;
      $v_html_output .= ( "<!-- (m2) -->\r\n\r\n" ) ;
   } // endif: WCCAPI Call diags


   // -------------------------------
   // Return HTML to Display
   // -------------------------------
   return($v_html_output) ;


} // end function: wccwppi_execute()


?>
