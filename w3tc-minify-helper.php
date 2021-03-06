<?php
/*
 * Plugin Name: W3TC Minify Helper
 * Description: better JavaScript minification for W3TC's auto minify mode
 * Version: 2
 * Plugin URI: http://magentacuda.com/an-alternate-way-to-set-w3tc-minify-file-order/
 * Author: Magenta Cuda
 * Author URI: http://magentacuda.com
 *
 * Copyright (c) 2019 Magenta Cuda
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * This plugin contains excerpts fron the plugin "W3 Total Cache" by BoldGrid.
 * See https://wordpress.org/plugins/w3-total-cache/.
 */

/*
 * This program runs either in version 2 mode or version 1 mode. You should use version 2 as version 1 will not
 * work under certain conditions which may be easily true on modern advanced websites. Version 1 is maintained
 * to support backward compatibility and should not be used for a new application. To enable version 2 just click
 * on the "Auto Minify:Xxx" link in this plugin's entry in the "Installed Plugins" admin page. This link toggles
 * "Off" and "On" this plugin's minifier for W3TC'S auto minification mode.
 */

/*
 * VERSION 2 (for use with W3TC's auto minification mode for JavaScript files)
 *
 * Version 2 of this plugin implements a monitor of W3TC's "minify auto js" processing. This monitor can provide
 * details on how the "auto mode" JavaScript minifier of W3TC works and optionally replace W3TC's minifier with
 * this plugin's minifier. This should reduce the number of minified files emitted.
 *
 * This plugin in "auto minify" mode assumes that only the WordPress API, i.e., only the following functions are
 * used to embed scripts into the HTML document.
 *
 *    wp_enqueue_script()
 *    wp_add_inline_script()
 *    wp_script_add_data()
 *    wp_localize_script()
 *    wp_set_script_translations()
 *
 * Using ad hoc methods to embed scripts into the HTML document may invalidate assumptions made by this plugin and
 * cause this plugin to malfunction.
 *
 * The following WP-CLI commands will enable/disable monitoring of W3TC's "minify auto js" filters:
 *
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_process_content", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_processed_content", TRUE );'
 *
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_script_tags", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_do_local_script_minification", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_do_tag_minification", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_do_flush_collected", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_step", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_step_script_to_embed", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_js_do_excluded_tag_script_minification", TRUE );'
 *
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_urls_for_minification_to_minify_filename", TRUE );'
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( "FILTER::w3tc_minify_file_handler_minify_options", TRUE );'
 *
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::clear_monitor_minify_autojs_options( );'
 *
 * To show the state of the monitor run the following WP-CLI command:
 *
 *     php wp-cli.phar eval 'print_r( get_option( MC_Alt_W3TC_Minify::OPTION_MONITOR_MINIFY_AUTOJS ) );'
 *
 * To replace W3TC's "auto mode" JavaScript minifier with the monitor's minifier run the following WP-CLI command:
 *
 *     php wp-cli.phar eval 'MC_Alt_W3TC_Minify::set_monitor_minify_autojs_options( MC_Alt_W3TC_Minify::AUTO_MINIFY_OPTION, TRUE );'
 *
 * To reset the monitor, i.e., turn everything off run the following MySQL command:
 *
 *     delete from wp_options where option_name = 'mc_alt_w3tc_minify_monitor_minify_autojs';
 */

/*
 * VERSION 1 (for use with W3TC's manual minification mode for JavaScript files)
 *
 * The following is a description of version 1 of this plugin. Although still
 * supported for backward compatibility I strongly recommend enabling version 2,
 * as I think the design of W3TC “manual minify” mode prevents W3TC JavaScript
 * minification in “manual minify” mode from being successful except under some
 * quite restrictive conditions which will not be true for many modern WordPress
 * web pages. These restrictions are described in "The original README" of
 * "https://github.com/magenta-cuda/alt-w3tc-minify/blob/master/README.md".
 *
 *
 * The W3 Total Cache auto minify mode does not work on my web site. The problem
 * is the order of the JavaScript files using the auto minify mode is different
 * from the order without minification. This results in undefined JavaScript
 * function errors. W3TC has a help tool for manually setting the order of
 * JavaScript files. I think this tool is tiresome to use. I just want to have
 * the same order as without minification. So, I wrote this plugin to do this.
 * 
 * Every view of a web page sets the ordered list of JavaScript files of the
 * template of that web page. If this ordered list has changed a new W3TC
 * configuration file is generated by modifying the value of the field
 * 'minify.js.groups' of the current W3TC configuration to be compatible with
 * the new ordered list of JavaScript files for that template. This process is
 * cumulative so after viewing a representative web page for each template a
 * complete W3TC configuration file will be built. Further, if the ordered list
 * of JavaScript files for a template changes then this will also build a new
 * compatible W3TC configuration file. N.B. - W3TC assumes that the ordered list
 * of JavaScript files for a template is fixed. If your web pages are dynamically 
 * computing the JavaScript files and two different web pages using the same
 * template compute different lists of JavaScript files then W3TC cannot be used
 * to minify those JavaScript files.
 * 
 * If in response to a warning you select the 'do not minify' option for a template
 * then this plugin will permanently ignore that template. However, you can force
 * this plugin to re-process that template by adding the query parameter 
 * "mc_ignore_do_not_minify_flag=1" to the URL of any page using that template.
 * Then you will receive the warning again and you can then select the
 * 'safe to minify' option.
 * 
 * This generated JSON configuration file can be downloaded and further edited to
 * fine tune the minify process and imported back into W3TC. The download link is
 * on the "Installed Plugins" admin page after the "Deactivate" link. But, before
 * you import this new configuration file into W3TC, you should download the
 * current configuration of W3TC so that you can restore the current configuration
 * if the new configuration does not work. Also, before importing the new
 * configuration file into W3TC you should diff the new configuration file against
 * the current configuration file of W3TC to verify that the new configuration file
 * has no obvious errors. You should also verify that you have a complete
 * configuration by viewing the 'minify.js.groups' section and checking that there
 * is an entry for each of your templates. N.B. - W3TC "Minify mode" must be set to
 * "Manual" for W3TC to use the ordered list of JavaScript files generated by this
 * plugin. 
 *
 * The following WP-CLI commands will display the "manual mode" database data of this plugin:
 *
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_log"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_skipped"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_theme_map"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_miscellaneous"));'
 *     php wp-cli.phar eval 'print_r(get_transient("mc_alt_w3tc_minify"));'
 *     php wp-cli.phar eval 'print_r(json_decode(get_option("w3tc_minify"),true));' 
 *
 * The second command is useful in verifying that a view of a representative web
 * page has been done for each of your templates. The last command dumps W3TC's
 * map of minified files to their component files.
 *
 * AJAX actions have been abused to return complete HTML pages so you can dump this plugin's "manual mode" database,
 * logs and notes by:
 *   
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_log
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_database
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_theme_map
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_the_diff&theme=ce894&basename=page
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_w3tc_minify_map
 *     http://localhost/wp-admin/admin-ajax.php?action=mc_alt_w3tc_minify_get_w3tc_minify_map&file=94796.js
 *
 * The following WP-CLI commands will clear the "manual mode" database data of this plugin:
 *
 *     php wp-cli.phar eval 'delete_transient("mc_alt_w3tc_minify");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_log");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_skipped");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_theme_map");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_miscellaneous");'
 *     php wp-cli.phar eval 'unlink( MC_Alt_W3TC_Minify::OUTPUT_DIR . "/" . MC_Alt_W3TC_Minify::CONF_FILE_NAME );'
 *
 */

#                                              1234567812345678
# if ( get_option( 'mc_alt_w3tc_minify_debug', 0x0000000000000000 ) || array_key_exists( 'mc_alt_w3tc_minify_debug', $_REQUEST ) ) {
if ( TRUE ) {   # TODO: for testing MC_AWM_191208_DEBUG_MINIFIER_EMIT_INLINE_MARKERS
    #                                                                        1234567812345678
    define( 'MC_AWM_191208_DEBUG_OFF',                                     0x0000000000000000 );
    define( 'MC_AWM_191208_DEBUG_WP_CLI_UNIT_TESTER',                      0x0000000000000001 );   # This enables WP-CLI unit testing
    define( 'MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER',            0x0000000000000002 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_UNIT_TEST',                      0x0000000000000004 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_INLINE_BEFORE_SCRIPT_TEST',      0x0000000000000010 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_INLINE_AFTER_SCRIPT_TEST',       0x0000000000000020 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_CONDITIONAL_SCRIPT_TEST'  ,      0x0000000000000040 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_LOCALIZE_SCRIPT_TEST',           0x0000000000000080 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_IN_FOOTER_SCRIPT_TEST',          0x0000000000000100 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_HEAD_SCRIPT_TEST',               0x0000000000000200 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_FOOTER_SCRIPT_TEST',             0x0000000000000400 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TEST',              0x0000000000000800 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TRANSLATIONS_TEST', 0x0000000000001000 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_ASYNC_SCRIPT_TEST',              0x0000000000002000 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_DEFER_SCRIPT_TEST',              0x0000000000004000 );
    define( 'MC_AWM_191208_DEBUG_MINIFIER_EMIT_INLINE_MARKERS',            0x0000000000010000 );
    define( 'MC_AWM_191208_DEBUG',   MC_AWM_191208_DEBUG_OFF
                                   # | MC_AWM_191208_DEBUG_WP_CLI_UNIT_TESTER
                                   # | MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER
                                   # | MC_AWM_191208_DEBUG_MINIFIER_UNIT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_INLINE_BEFORE_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_INLINE_AFTER_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_CONDITIONAL_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_LOCALIZE_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_IN_FOOTER_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_HEAD_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_FOOTER_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TRANSLATIONS_TEST
                                   | MC_AWM_191208_DEBUG_MINIFIER_ASYNC_SCRIPT_TEST
                                   | MC_AWM_191208_DEBUG_MINIFIER_DEFER_SCRIPT_TEST
                                   # | MC_AWM_191208_DEBUG_MINIFIER_EMIT_INLINE_MARKERS
                                   #                                           1234567812345678
                                   | get_option( 'mc_alt_w3tc_minify_debug', 0x0000000000000000 )
                                   | ( array_key_exists( 'mc_alt_w3tc_minify_debug', $_REQUEST )
                                       ? intval( $_REQUEST['mc_alt_w3tc_minify_debug'], 16 ) : 0x0000000000000000 )
    );
    # error_log( 'MC_AWM_191208_DEBUG=' . sprintf( '%016X', MC_AWM_191208_DEBUG ) );
}

class MC_Alt_W3TC_Minify {
    const PLUGIN_NAME                  = 'W3TC Minify Helper';
    const W3TC_FILE                    = 'w3-total-cache/w3-total-cache.php';
    const W3TC_VERSION                 = '0.15.0';                                     # tested against this version of W3TC
    const OPTION_NAME                  = 'mc_alt_w3tc_minify';
    const OPTION_LOG_NAME              = 'mc_alt_w3tc_minify_log';
    const OPTION_SKIPPED_NAME          = 'mc_alt_w3tc_minify_skipped';
    const OPTION_THEME_MAP             = 'mc_alt_w3tc_minify_theme_map';
    const OPTION_USE_INCLUDE           = 'mc_alt_w3tc_minify_use_include';
    const OPTION_MISCELLANEOUS         = 'mc_alt_w3tc_minify_miscellaneous';
    const OPTION_MONITOR_MINIFY_AUTOJS = 'mc_alt_w3tc_minify_monitor_minify_autojs';   # this array holds all Version 2 options
    const OPTION_DEBUG                 = 'mc_alt_w3tc_minify_debug';
    const TRANSIENT_NAME               = 'mc_alt_w3tc_minify';
    const OUTPUT_DIR                   = WP_CONTENT_DIR . '/mc-w3tcm-output';
    const MINIFY_FILENAME_PREFIX       = self::OUTPUT_DIR . '/mc-w3tcm-inline-';
    const CONF_FILE_NAME               = 'mc_alt_w3tc_minify.json';
    const NOTICE_ID                    = 'mc_alt_w3tc_minify_notice_id';
    const TEMPLATE_WARNINGS            = 'TEMPLATE-WARNINGS';
    const DO_NOT_MINIFY                = 'DO-NOT-MINIFY';
    const OVERRIDE_DO_NOT_MINIFY       = 'mc_ignore_do_not_minify_flag';               # query parameter to ignore 'do not minify' flag
    const AUTO_MINIFY_OPTION           = 'ENABLED::mc_w3tcm_auto_minify';              # enable Version 2 minification
    const AJAX_TOGGLE_AUTO_MINIFY      = 'mc_alt_w3tc_toggle_auto_minify';
    const AJAX_RESET                   = 'mc_alt_w3tc_minify_reset';
    const AJAX_SET_TEMPLATE_SKIP       = 'mc_alt_w3tc_minify_set_template_skip';
    const AJAX_GET_THEME_MAP           = 'mc_alt_w3tc_minify_get_theme_map';
    const AJAX_GET_LOG                 = 'mc_alt_w3tc_minify_get_log';
    const AJAX_GET_MISC                = 'mc_alt_w3tc_minify_get_misc';
    const AJAX_GET_THE_DIFF            = 'mc_alt_w3tc_minify_get_the_diff';
    const AJAX_GET_DATABASE            = 'mc_alt_w3tc_minify_get_database';
    const AJAX_GET_MINIFY_MAP          = 'mc_alt_w3tc_minify_get_w3tc_minify_map';
    const AJAX_GET_MINIFY_CACHE_LIST   = 'mc_alt_w3tc_minify_get_w3tc_minify_cache_list';
    const UNKNOWN_SCRIPT_TAG           = 'unknown';
    const INLINE_SCRIPT                = 'inline';
    const SKIPPED_SCRIPT               = 'skipped';
    const HEAD_END                     = 'head end';
    const AFTER_LAST_SCRIPT            = 'after last';
    const TABLE_STYLE                  = <<<EOD
<style>
    table {
        width: 100%;
        table-layout: fixed;
        border: 4px solid black;
        border-collapse: collapse;
    }
    tr {
        width: 100%;
    }
    tr.hide {
        display: none;
    }
    tr.exists_hide {
        display: none;
    }
    td, th {
        padding: 5px 15px;
        border: 2px solid black;
    }
    td.w5, th.w5 {
        width: 5%;
    }
    td.w10, th.w10 {
        width: 10%;
    }
    td.w20, th.w20 {
        width: 20%;
    }
    td.w30, th.w30 {
        width: 30%;
    }
    td.w50, th.w50 {
        width: 50%;
    }
    td.w65, th.w65 {
        width: 65%;
    }
    td.w70, th.w70 {
        width: 70%;
    }
    td.w85, th.w85 {
        width: 85%;
    }
    td.w90, th.w90 {
        width: 90%;
    }
    td.err {
        color: red;
    }
</style>
EOD;
    const TABLE_SCRIPT                 = <<<EOD
<script>
function addRemoveClass( selector, theClass, remove = false ) {
    const re = new RegExp( '(^|\\\\s+)' + theClass );
    document.querySelectorAll( selector ).forEach( function( elem ) {
        var theClasses = elem.className;
        if ( !remove ) {
            if ( !re.test( theClasses) ) {
                theClasses += ' ' + theClass;
                elem.className = theClasses;
            }
        } else {
            theClasses = theClasses.replace( re, '' );
            elem.className = theClasses;
        }
    } );
}
</script>
EOD;
    private static $debug              = NULL;
    private static $theme              = NULL;   # MD5 of the current theme
    private static $basename           = NULL;   # the basename of the current template in the current theme
    private static $the_data           = NULL;   # the database of this plugin
    private static $files              = [                                             # $files holds the ordered list of JavaScript
                                             'include'        => [ 'files' => [] ],    # files emitted for the current theme and
                                             'include-body'   => [ 'files' => [] ],    # template combination in the head, body and
                                             'include-footer' => [ 'files' => [] ]     # footer sections.
                                        ];
    # admin-bar.js is a problem because every time the logged in status changes the "Admin Bar" will be inserted
    # or removed causing admin-bar.js to be added or removed from the ordered list of JavaScript files. This will
    # trigger a rebuild of the W3TC configuration file. To solve this we will omit admin-bar.js from the ordered
    # list of JavaScript files. Other files that need to be omitted can be entered into $files_to_skip. "admin-bar.js"
    # is easy to handle since it has no dependencies. If you skip a file with dependencies you may also need to skip
    # the dependencies depending on whether or not the batch file is included before or after the skipped file. A
    # skipped file is emitted at its normal location. A "include" batch file is emitted just after the <head> tag,
    # a "include-body" batch file is emitted just after the <body> tag and a "include-footer" batch file is emitted
    # just before the </body> tag.
    private static $files_to_skip             = [
        '/wp-includes/js/admin-bar.js',
        '/wp-includes/js/admin-bar.min.js'
    ];
    # By default processing is skipped. The filter 'template_include' will conditionally enable processing.
    private static $skip                      = TRUE;
    # $use_include sets whether to use 'include' or 'include-body' for header scripts
    private static $use_include               = FALSE;
    # The following variables are used to control my monitor of Minify_AutoJs.
    # $auto_minify === TRUE - replaces the minification of Minify_AutoJs - this should reduce the number of minified files.
    private static $auto_minify               = FALSE;
    # $all_scripts also includes conditional scripts (<!--[if lte IE 8]><script... <![endif]-->) which W3TC ignores. 
    private static $all_scripts               = NULL;
    private static $conditional_scripts       = NULL;
    private static $skipped_scripts           = NULL;
    # W3TC collects the JavaScript files in the property $files_to_minify of an Minify_AutoJs object. However,
    # $files_to_minify of the class Minify_AutoJs is a private property ...
    # PHP Fatal error:  Uncaught Error: Cannot access private property W3TC\Minify_AutoJs::$files_to_minify
    # so will maintain a shadow of this property that we can modify.
    private static $files_to_minify           = [];
    private static $files_to_minify_extras    = [];
    # $last_script_tag_is is the $last_script_tag seen by the filter 'w3tc_minify_js_do_flush_collected'
    private static $last_script_tag_is        = self::UNKNOWN_SCRIPT_TAG;
    # $minify_filename is the index into the array $minify_filenames which is saved in the option 'w3tc_minify'.
    private static $minify_filename           = NULL;
    # For inline scripts save the tag position and whether the script was conditional or not.
    private static $end_head_tag_pos          = NULL;   # not NULL only when process_script_tag() is processing the </head> tag
    private static $inline_script_conditional = NULL;
    private static $first_inline_script_start_pos = NULL;
    private static $first_inline_script_end_pos   = NULL;
    private static $last_inline_script_start_pos  = NULL;
    private static $last_inline_script_end_pos    = NULL;
    private static $w3tc_minify_helpers       = NULL;
    private static $serve_options             = NULL;
    public static function init() {
        self::$debug = get_option( self::OPTION_DEBUG );
        if ( ! is_dir( self::OUTPUT_DIR ) || ! is_writable( self::OUTPUT_DIR ) ) {
            @mkdir( self::OUTPUT_DIR, 0755 );
            if ( ! is_dir( self::OUTPUT_DIR ) || ! is_writable( self::OUTPUT_DIR ) ) {
                error_log( 'MC_Alt_W3TC_Minify: Cannot create directory "' . self::OUTPUT_DIR . '", getmypid()=' . getmypid() );
            }
        }
        # If version 2 (auto minify mode) is enabled then monitor_minify_autojs() initializes version 2 and returns true.
        if ( self::monitor_minify_autojs() ) {
            # Auto minify is enabled so manual minify is disabled.
            # The remaining code is for version 1 (manual minify mode) and should be skipped.
            return;
        }
        # Entering version 1 (manual minify mode) code.
        # When W3TC is in manual minify mode its configuration file specifies the ordered list of JavaScript files to emit in the
        # head, body and footer sections for the current theme and template combination.
        # Get additional files to skip.
        $files_to_skip        = file( __DIR__ . '/files-to-omit.ini', FILE_IGNORE_NEW_LINES );
        $files_to_skip        = $files_to_skip === FALSE ? [] : $files_to_skip;
        $files_to_skip        = array_filter( $files_to_skip, function( $line ) {
            return $line[0] !== '#';
        } );
        $files_to_skip        = array_map( function( $file ) {
            return trim( $file );
        }, $files_to_skip );
        self::$files_to_skip  = array_merge( self::$files_to_skip, $files_to_skip );
        self::$use_include    = get_option( self::OPTION_USE_INCLUDE );
        $initial_template     = NULL;
        # Save the initial template so we can detect if the filter 'template_include' was used to change the template.
        add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
            $initial_template = $template;
            return $template;
        }, 0, 1 );
        # The filter 'template_include' is used only for side effects.
        # It is used to set the current theme - self::$theme - and the current template - self::$basename.
        # It conditionally enables processing by setting self::$skip = FALSE which is by default set to TRUE.
        # It detects if the template was selected by the filter 'template_include'.
        add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
            # self::$theme is a MD5 hash of the theme path, the template and the stylesheet.
            # N.B. get_template() returns a theme not a template, e.g. twentysixteen not single, archive, page, ...
            # N.B. get_stylesheet() returns the child theme, e.g. twentysixteen-child
            # self::$theme is a signature, i.e. identifier for the current theme
            self::$theme               = \W3TC\Util_Theme::get_theme_key( $map_theme_root = get_theme_root(), 
                                                                          $map_template   = get_template(),
                                                                          $map_stylesheet = get_stylesheet() );
            # Save the binding of the theme's MD5 hash to the theme path, the template and the stylesheet in the database. 
            $theme_map                 = get_option( self::OPTION_THEME_MAP, [] );
            $theme_map[ self::$theme ] = [
                'theme_root' => $map_theme_root,
                'template'   => $map_template,
                'stylesheet' => $map_stylesheet
            ];
            update_option( self::OPTION_THEME_MAP, $theme_map );
            self::$basename = basename( $template, '.php' );
            # If query parameter self::OVERRIDE_DO_NOT_MINIFY exists ignore the skip by 'do not minify' flag for the current template.
            if ( empty( $_REQUEST[ self::OVERRIDE_DO_NOT_MINIFY ] ) ) {
                # Check if 'do not minify' has been set for this template.
                $data = self::get_the_data();
                if ( array_key_exists( self::$theme, $data ) && array_key_exists( self::$basename, $data[ self::$theme ] ) ) {
                    if ( $data[ self::$theme ][ self::$basename ] === self::DO_NOT_MINIFY ) {
                        # self::$skip === TRUE so processing will be skipped.
                        return $template;
                    }
                }
            }
            # W3TC cannot handle templates included using the filter 'template_include' so log it and send an error notice.
            if ( $template !== $initial_template ) {
                self::set_database_to_skip_current_template( "Skipped because it is an override of $initial_template.", <<<EOD
: WARNING: Template "$template" cannot be minified because it was included using the filter 'template_include'
to override the template "$initial_template". W3TC cannot handle templates included using the filter 'template_include'.
EOD
                );
                # self::$skip === TRUE so processing will be skipped.
                // Uncomment the following to test templates loaded using the 'template_include' filter.
                // self::$skip = FALSE;
                return $template;
            }
            # If we get here then enable processing.
            self::$skip = FALSE;
            return $template;
        }, PHP_INT_MAX, 1 );   # add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
        # When each HTML <script> tag for a JavaScript file is emitted add an entry to the ordered list of JavaScript
        # files for the current theme and template. In addition to emitting the <script> tag WordPress may prepend
        # 'localize','translation' and 'before' inline <script> elements and append an 'after' inline <script> element.
        # WordPress may also bracket the above with a conditional HTML comment. In W3TC 'manual minify' mode the 
        # 'additional' prepended, appended HTML elements will be emitted as usual but the <script> tag for the
        # JavaScript file will not be emitted. Instead the contents of that file will be combined with other JavaScript
        # files and that combined file will be emitted somewhere else. This can change the order of execution of the
        # JavaScript code which can cause fatal errors. (I think this is a serious flaw in the design of W3TC 'manual
        # minify' mode and alternate design can avoid this problem but currently I don't see how to incorporate it into
        # the W3TC framework.) The best we can do for now is issue warnings if the relative order of a JavaScript file
        # and its 'additional' inline <script> elements is changed and let the user choose not to minify the current
        # template. A script tag with all additional HTML elements would look like:
        #
        # <!--[if lt IE 9]>
        # <script type='text/javascript'>/* localize script */</script>
        # <![endif]-->
        # <!--[if lt IE 9]>
        # <script type='text/javascript'>/* translation script */</script>
        # <script type='text/javascript'>/* before script */</script>
        # <script type='text/javascript' src='http://url/of/the/file.js'></script>
        # <script type='text/javascript'>/* after script */</script>
        # <![endif]-->
        #
        # The filter 'script_loader_tag' is used to record for the current theme and template combination the ordered list of
        # JavaScript files as they are emitted in the head, body and footer sections.
        add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
            if ( self::$skip ) {
                return $tag;
            }
            # Skip JavaScript files like admin-bar.js.
            foreach ( self::$files_to_skip as $file ) {
                if ( strpos( $src, $file ) !== FALSE ) {
                    return $tag;
                }
            }
            if ( doing_action( 'wp_print_footer_scripts' ) ) {
                self::$files['include-footer']['files'][] = $src;
            } else if ( doing_action( 'wp_head' ) ) {
                # check if there is a localize script for this script.
                if ( $localize_data = wp_scripts()->get_data( $handle, 'data' ) ) {
                    $has_localize_script = TRUE;
                    # error_log( "FILTER::script_loader_tag(): '$src' has a localize script." );
                }
                error_log( 'FILTER::script_loader_tag(): $tag=' . $tag );
                # Check if a conditional HTML comment exists.
                if ( preg_match( '#<!--(\[if\s.+\])>.+<!\[endif\]-->#s', $tag, $matches ) === 1 ) {
                    error_log( 'FILTER::script_loader_tag(): $matches=' . print_r( $matches, TRUE ) );
                    $has_conditional = TRUE;
                    $condition       = $matches[1];
                }
                # Check if there is a 'translation', 'before' or 'after' script for this script.
                if ( preg_match_all( '#<script.*?</script>#s', $tag, $matches,  PREG_SET_ORDER ) ) {
                    # error_log( 'FILTER::script_loader_tag(): $matches=' . print_r( $matches, TRUE ) );
                    foreach ( $matches as $index => $match ) {
                        if ( preg_match( '#\ssrc=(\'|").+?\1#', $match[0], $matches1 ) ) {
                            $src_index = $index;
                            # error_log( 'FILTER::script_loader_tag(): $src_index=' . $src_index );
                        } else {
                            if ( empty( $src_index ) ) {
                                $has_before_script = TRUE;
                                # error_log( "FILTER::script_loader_tag(): '$src' has a before script." );
                            } else {
                                $has_after_script  = TRUE;
                                # error_log( "FILTER::script_loader_tag(): '$src' has a after script." );
                            }
                        }
                    }
                }
                if ( self::$use_include ) {
                    # The problem with 'include' is the minified script file will be emitted immediately after
                    # the <head> tag and the inline scripts created wp_localize_script() will be emitted much
                    # later. However, WordPress normally emits the inline script created wp_localize_script()
                    # immediately before the script being localized.
                    self::$files['include']['files'][] = $src;
                } else {
                    # Using 'include-body' emits the minified script file after the <body> tag. Hence, the
                    # inline scripts created by wp_localize_script() which will be emitted in the <head>
                    # section will be emitted before the minified file.
                    self::$files['include-body']['files'][] = $src;
                }
                # Issue a warning if the script tag is bracketed by a HTML conditional comment.
                if ( ! empty( $has_conditional ) ) {
                    $notice_id = md5( self::$theme . self::$basename . $src . $condition );
                    $ajax_url  = admin_url( 'admin-ajax.php', 'relative' )
                                        . '?action='                  . self::AJAX_SET_TEMPLATE_SKIP 
                                        . '&theme='                   . self::$theme
                                        . '&basename='                . self::$basename
                                        . '&' . self::NOTICE_ID . '=' . $notice_id
                                        . '&_wpnonce='                . wp_create_nonce( self::AJAX_SET_TEMPLATE_SKIP );
                    # The action, theme and basename substring must match the regex
                    # '#(\?action=' . self::AJAX_SET_TEMPLATE_SKIP . '&theme=\w+&basename=\w+&)#'.
                    # error_log( 'FILTER::script_loader_tag(): $ajax_url=' . $ajax_url );
                    $theme     = self::$theme;
                    $basename  = self::$basename;
                    # Queue the template warning as these should only be emitted if the configuration actually changes.
                    self::manage_notice_queue( self::TEMPLATE_WARNINGS, 'add', self::PLUGIN_NAME . <<<EOD
: WARNING: In template "$theme.$basename" the script "$src" has a HTML conditional comment - "$condition".
An action is required to resolve this. Either
<a href="{$ajax_url}&skip=1">Do not minify this template.</a>
or
<a href="{$ajax_url}&skip=0">Safe to minify this template.</a>
EOD
                    );
                }
                # Localize, translation and before scripts should be emitted before their corresponding script.
                # After scripts should be emitted after their corresponding script. If this order is not preserved
                # issue a warning. Because the conditions have side effects the or must be a non short-circuit or.
                if ( self::non_short_circuit_or(
                    ( self::$use_include   && ! empty( $has_localize_script ) && ( $position = 'localize' ) && ( $order = 'after' ) ),
                    ( self::$use_include   && ! empty( $has_before_script )   && ( $position = 'before' )   && ( $order = 'after' ) ),
                    ( ! self::$use_include && ! empty( $has_after_script )    && ( $position = 'after' )    && ( $order = 'before' ) )
                ) ) {
                    $notice_id = md5( self::$theme . self::$basename . $src . $position . $order );
                    $ajax_url      = admin_url( 'admin-ajax.php', 'relative' )
                                         . '?action='                  . self::AJAX_SET_TEMPLATE_SKIP 
                                         . '&theme='                   . self::$theme
                                         . '&basename='                . self::$basename
                                         . '&' . self::NOTICE_ID . '=' . $notice_id
                                         . '&_wpnonce='                . wp_create_nonce( self::AJAX_SET_TEMPLATE_SKIP );
                    # The action, theme and basename substring must match the regex
                    # '#(\?action=' . self::AJAX_SET_TEMPLATE_SKIP . '&theme=\w+&basename=\w+&)#'.
                    $misc_ajax_url = admin_url( 'admin-ajax.php', 'relative' )
                                         . '?action='                  . self::AJAX_GET_MISC
                                         . '&key='                     . $notice_id;
                    # error_log( 'FILTER::script_loader_tag(): $ajax_url=' . $ajax_url );
                    $theme     = self::$theme;
                    $basename  = self::$basename;
                    # Queue the template warning as these should only be emitted if the configuration actually changes.
                    self::manage_notice_queue( self::TEMPLATE_WARNINGS, 'add', self::PLUGIN_NAME . <<<EOD
: WARNING: In template "$theme.$basename" the script "$src" has a <a href="{$misc_ajax_url}" target="_blank">$position</a>
script which will be emitted $order itself. An action is required to resolve this. Either
<a href="{$ajax_url}&skip=1">Do not minify this template.</a>
or
<a href="{$ajax_url}&skip=0">Safe to minify this template.</a>
EOD
                    );
                    if ( $position === 'localize' ) {
                        self::add_miscellaneous( $notice_id, $localize_data );
                    }
                    if ( $position === 'before' || $position === 'after' ) {
                        self::add_miscellaneous( $notice_id, $tag );
                    }
                }
            }
            return $tag;
        }, 10, 3 );   # add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
        # On shutdown update the ordered list of Javascript files for the current theme and template if it is
        # different from its previous value and rebuild the W3TC configuration file if neccessary.
        add_action( 'shutdown', function() {
            if ( ! self::$skip ) {
                self::update_database();            
            }
        } );
        self::delete_old_miscellaneous( 86400 * 10 );
    }   # public static function init() {
    public static function admin_init() {
        $w3tc_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::W3TC_FILE );
        if ( $w3tc_plugin_data['Version'] !== self::W3TC_VERSION ) {
            add_action( 'admin_notices', function() use ( $w3tc_plugin_data ) {
                ?>
                <div class="notice notice-warning is-dismissible">
                    The W3TC Minify Helper plugin may not be compatible with W3 Total Cache version
                    "<?php echo $w3tc_plugin_data['Version']; ?>". The W3TC Minify Helper plugin has only been tested with
                    W3 Total Cache version <?php echo self::W3TC_VERSION; ?>.
                </div>
                <?php
            } );
        }
        # This plugin doesn't require much user interactivity so it doesn't have a GUI.
        # Rather some non standard plugin action links are provided.
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
            $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [] );
            $enabled = ! empty( $options[ self::AUTO_MINIFY_OPTION ] );
            # Link to toggle the auto minify mode.
            array_push( $links,
                '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_TOGGLE_AUTO_MINIFY
                    . '&_wpnonce=' . wp_create_nonce( self::AJAX_TOGGLE_AUTO_MINIFY ) . '" title="Toggle Auto Minify Mode.">'
                    . 'Auto Minify:' . ( $enabled ? '<span style="color:green;">On</span>' : '<span style="color:red;">Off</span>' ) .
                '</a>'
            );
            if ( ! $enabled ) {
                # Only show manual mode links if auto minify mode is off.
                if ( file_exists( self::OUTPUT_DIR . '/' . self::CONF_FILE_NAME ) ) {
                    # Add the download link for the generated conf file after the "Deactivate" link.
                    array_push( $links,
                        '<a href="' . WP_CONTENT_URL . '/w3tc-config/' . self::CONF_FILE_NAME
                            . '">Download New W3TC Conf File</a>'
                    );
                }
                # Let the user remove everything created by this plugin by AJAX request.
                # This AJAX request is sent to admin-ajax.php not as XHR but as a normal HTTP request
                # and will require special handling in the AJAX handler to return a HTML page.
                array_push( $links,
                    '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_RESET
                        . '&_wpnonce=' . wp_create_nonce( self::AJAX_RESET ) . '" title="Clear the manual mode database.">Reset</a>'
                );
                # Another abusive use of AJAX (sent as a normal HTTP request not as XHR) to dump the log in a web page.
                array_push( $links,
                    '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_GET_LOG
                        . '" title="Dump the manual mode actions on templates in themes." target="_blank">Dump Log</a>'
                );
            } else {
                # Another abusive use of AJAX (sent as a normal HTTP request not as XHR) to dump W3TC's minify map.
                array_push( $links,
                    '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_GET_MINIFY_MAP
                        . '" title="Dump W3TC\'s minify map." target="_blank">Dump Minify Map</a>'
                );
            }
            return $links;
        } );
        # If the minify JavaScript configuration has changed display an admin notice.
        if ( is_admin() && ! wp_doing_ajax() && ( $notices = get_transient( self::TRANSIENT_NAME ) ) ) {
            # Some action notices may have expired nonces so renew those nonces.
            $set_template_skip_nonce = wp_create_nonce( self::AJAX_SET_TEMPLATE_SKIP );
            $notices = array_map( function( $notice ) use ( $set_template_skip_nonce ) {
                if ( strpos( $notice, 'action=' . self::AJAX_SET_TEMPLATE_SKIP . '&' ) !== FALSE ) {
                    return preg_replace( '#&_wpnonce=[a-f0-9]+&#', "&_wpnonce={$set_template_skip_nonce}&", $notice );
                } else {
                    return $notice;
                }
            }, $notices );
            $url = WP_CONTENT_URL . '/w3tc-config/' . self::CONF_FILE_NAME;
            add_action( 'admin_notices', function() use ( $notices, $url ) {
?>
<div class="notice notice-info is-dismissible">
<?php
                echo implode( '<br>', $notices );
                if ( array_reduce( $notices, function( $or, $notice ) {
                    return $or | strpos( $notice, 'updated' ) !== FALSE;
                }, FALSE ) ) {
?>
    <br><?php echo self::PLUGIN_NAME; ?>: The new configuration file can be downloaded from
        <a href="<?php echo $url; ?>"><?php echo $url; ?></a>.
<?php
                }
?>
</div>
<?php
            } );
            # Preserve notices that have a notice id as these require that an action be taken.
            $notices = array_filter( $notices, function( $notice ) {
                return strpos( $notice, self::NOTICE_ID ) !== FALSE;
            } );
            delete_transient( self::TRANSIENT_NAME );
            if ( $notices ) {
                set_transient( self::TRANSIENT_NAME, $notices );
            }
        }
        # AJAX request to toggle the auto minify mode.
        # N.B. This AJAX request was not sent by XHR but as a normal HTTP request
        # and will require special handling as a page needs to be returned.
        add_action( 'wp_ajax_' . self::AJAX_TOGGLE_AUTO_MINIFY, function( ) {
            check_ajax_referer( self::AJAX_TOGGLE_AUTO_MINIFY );
            if ( ! ( $enabled = self::toggle_auto_minify_option( ) ) ) {
                # Version 1 now enabled. W3TC's minify cache is no longer valid.
                $w3_minify = \W3TC\Dispatcher::component( 'CacheFlush' );
                $w3_minify->minifycache_flush();
                # TODO: Actually the minify cache is only partially invalidated so it only needs to be partially cleared.
            } else {
                # Version 2 now enabled. So clear version 1's persistent data.
                self::reset();
            }
            self::add_notice( self::PLUGIN_NAME .': Auto Minify is ' . ( $enabled ? 'on.' : 'off.' ) );
            # Since this AJAX request was not invoked as XHR but as a normal HTTP request
            # we need to redirect to return a page otherwise the browser will not have content.
            wp_redirect( admin_url( 'plugins.php' ) );
            exit();
        } );
        # Let the user remove everything created by this plugin by AJAX request.
        # N.B. This AJAX request was not sent by XHR but as a normal HTTP request
        # and will require special handling as a page needs to be returned.
        add_action( 'wp_ajax_' . self::AJAX_RESET, function() {
            check_ajax_referer( self::AJAX_RESET );
            self::reset( TRUE );
            self::add_notice( self::PLUGIN_NAME .': The database has been cleared.' );
            # Since this AJAX request was not invoked as XHR but as a normal HTTP request
            # we need to redirect to return a page otherwise the browser will not have content.
            wp_redirect( admin_url( 'plugins.php' ) );
            exit();
        } );
        add_action( 'wp_ajax_' . self::AJAX_SET_TEMPLATE_SKIP, function() {
            # error_log( 'ACTION::wp_ajax_' . self::AJAX_SET_TEMPLATE_SKIP . '():$_REQUEST=' . print_r( $_REQUEST, true ) );
            check_ajax_referer( self::AJAX_SET_TEMPLATE_SKIP );
            if ( ! empty( $_REQUEST['skip'] ) ) {
                # Restore the minify helper environment of the referring page.
                self::set_current_template( $_REQUEST['theme'], $_REQUEST['basename'] );
                self::set_database_to_skip_current_template(
                    "Skipped because a script has an out of order localize, translation, before or after script.",
                    ": The scripts of template \"{$_REQUEST['theme']}.{$_REQUEST['basename']}\" will not be minified."
                );
                # Update the database and rebuild the W3TC configuration file.
                self::$files = self::DO_NOT_MINIFY;
                self::update_database();
            } else {
                self::add_notice( self::PLUGIN_NAME
                    . ": The scrips of template \"{$_REQUEST['theme']}.{$_REQUEST['basename']}\" will be minified." );
            }
            # Remove the corresponding transient notice. 
            $notice_id = $_REQUEST[ self::NOTICE_ID ];
            $notices   = get_transient( self::TRANSIENT_NAME );
            $match     = NULL;
            foreach ( $notices as $i => $notice ) {
                if ( strpos( $notice, $notice_id ) !== FALSE ) {
                    # Extract the action, theme and basename substring.
                    if ( preg_match( '#(\?action=' . self::AJAX_SET_TEMPLATE_SKIP . '&theme=\w+&basename=\w+&)#', $notice, $matches ) === 1 ) {
                        $match = $matches[0];
                        error_log( 'ACTION::wp_ajax_' . self::AJAX_SET_TEMPLATE_SKIP .'():$match="' . $match . '"' );
                    } else {
                        # This is an error as the action, theme and basename substring must exists.
                        error_log( self::PLUGIN_NAME . ': Error 1' );
                    }
                    unset( $notices[ $i ] );
                    break;
                }
            }
            # Remove other notices for the same action, theme and basename as these notices are now invalid.
            if ( $match !== NULL ) {
                foreach ( $notices as $i => $notice ) {
                    if ( strpos( $notice, $match ) !== FALSE ) {
                        unset( $notices[ $i ] );
                    }
                }
            }
            delete_transient( self::TRANSIENT_NAME );
            if ( $notices ) {
                set_transient( self::TRANSIENT_NAME, $notices );
            }
            wp_redirect( $_SERVER['HTTP_REFERER'] );
            exit();
        } );
        # a quick hack to dump the theme map abusing wordpress AJAX
        add_action( 'wp_ajax_' . self::AJAX_GET_THEME_MAP, function() {
?>
<html>
<body><pre>
<?php
    print_r( get_option( self::OPTION_THEME_MAP, [] ) );
?>
</pre></body>
</html>
<?php
            exit();
        } );
        # a quick hack to dump the log abusing wordpress AJAX
        add_action( 'wp_ajax_' . self::AJAX_GET_LOG, function() {
?>
<html>
<body><pre>
<?php
    print_r( get_option( self::OPTION_LOG_NAME, [] ) );
?>
</pre></body>
</html>
<?php
            exit();
        } );
        # a quick hack to dump the note by key from miscellaneous abusing wordpress AJAX.
        add_action( 'wp_ajax_' . self::AJAX_GET_MISC, function() {
            $buffer = '';
            if ( ! empty( $_REQUEST['key'] ) ) {
                foreach ( self::get_miscellaneous( $_REQUEST['key'] ) as $note ) {
                    if ( $buffer ) {
                        $buffer .= '<hr>';
                    }
                    $buffer .= htmlspecialchars( $note, ENT_NOQUOTES );
                }
            } else {
                $buffer = 'Error: Action "' . self::AJAX_GET_MISC . '" requires query parameter "key".';
            }
?>
<html>
<body><pre>
<?php echo $buffer; ?>
</pre></body>
</html>
<?php
            exit();
        } );
        # a quick hack to dump the latest diff for a template abusing wordpress AJAX.
        add_action( 'wp_ajax_' . self::AJAX_GET_THE_DIFF, function() {
            if ( isset( $_REQUEST['theme'], $_REQUEST['basename'] ) ) {
                $diff = self::get_the_latest_diff( $_REQUEST['theme'], $_REQUEST['basename'] );
            } else {
                $diff = 'Error: Action "' . self::AJAX_GET_THE_DIFF . '" requires query parameters "theme" and "basename".';
            }
?>
<html>
<body><pre>
<?php print_r( $diff ); ?>
</pre></body>
</html>
<?php
            exit();
        } );
        # a quick hack to dump the database abusing wordpress AJAX.
        add_action( 'wp_ajax_' . self::AJAX_GET_DATABASE, function() {
?>
<html>
<body><pre>
<?php print_r( get_option( 'mc_alt_w3tc_minify' ) ); ?>
</pre></body>
</html>
<?php
            exit();
        } );
        # a quick hack to dump W3TC's map of minified files to their component files.
        add_action( 'wp_ajax_' . self::AJAX_GET_MINIFY_MAP, function() {
?>
<html>
<head>
    <?php echo self::TABLE_STYLE; ?>
    <?php echo self::TABLE_SCRIPT; ?>
</head>
<body>
    <div style="height:3ch; border-width: 4px 4px 0; border-style:solid; border-color: black; padding:0.25ch 2ch 0;">
        <div style="width:8ch; float:left;">
            <span style="">Filters:</span>
        </div>
        <div style="width:13ch; float:left;">
            <input type="checkbox" id="script" class="filter" checked>
            <label for="script">script</label>
        </div>
        <div style="width:13ch; float:left;">
            <input type="checkbox" id="css" class="filter" checked>
            <label for="script">css</label>
        </div>
        <div style="width:13ch; float:left;">
            <input type="checkbox" id="exists" class="filter">
            <label for="script">existing only</label>
        </div>
        <div style="width:25ch; float:right;">
            <a href="<?php echo admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_GET_MINIFY_CACHE_LIST; ?>" target="_blank">
                Show minify cache directory
            </a>
        </div>
    </div>
    <script>
        document.querySelectorAll( 'input.filter' ).forEach( function( elem ) {
            elem.addEventListener( 'change', ( e ) => {
                var remove = elem.checked;
                switch ( elem.id ) {
                case 'script':
                    addRemoveClass( 'tr.is_script:not(.head)', 'hide', remove );
                    break;
                case 'css':
                    addRemoveClass( 'tr.is_css:not(.head)', 'hide', remove );
                    break;
                case 'exists':
                    addRemoveClass( 'tr:not(.exists):not(.head)', 'exists_hide', !remove );
                    break;
                }
            } );
        } );
    </script>
    <table>
        <tr class="head"><th class="w10">combined file</th><th class="w5">index</th><th class="w85">component files</th></tr>
<?php
    $minify_map = json_decode( get_option( 'w3tc_minify', [] ) );
    if ( ! empty( $_REQUEST['file'] ) ) {
        $minify_map = [ $_REQUEST['file'] => $minify_map->{$_REQUEST['file']} ];
    } else {
        $minify_map = (array) $minify_map;
        ksort( $minify_map, SORT_STRING );
    }
    $dir = \W3TC\Util_Environment::cache_blog_minify_dir();
    foreach( $minify_map as $key => $value ) {
        if ( ! is_array( $value ) ) {
            // TODO: $minify_map has corrupt entries that are objects not arrays, why?
            // TODO: But the object has numeric keys that correspond to a continuation of the previous array!
            error_log( 'ACTION::wp_ajax_' . self::AJAX_GET_MINIFY_MAP . '():$minify_map has bad value for key=' . $key );
            if ( empty( $dumped ) ) {
                error_log( 'ACTION::wp_ajax_' . self::AJAX_GET_MINIFY_MAP . '():$minify_map=' . print_r( $minify_map, TRUE ) );
                $dumped = TRUE;
            }
            echo '<tr><td class="w10">' . $key . '</td><td class="w5"></td><td class="err w85">Error: Value is not an array.</td></tr>';
            continue;
        }
        foreach( $value as $index => $file ) {
            if ( $index === 0 ) {
                $script = substr_compare( $key, '.js', -3 ) === 0 ? 'is_script' : 'is_css';
                if ( $exists = ( file_exists( "$dir/$key" ) ? ' exists' : '' ) ) {
                    echo "<tr class=\"{$script}{$exists}\">";
                    echo '<td class="w10" rowspan="' . count( $value ) . '"><a href="' . \W3TC\Minify_Core::minified_url( $key )
                        . '" target="_blank">' . $key . '</a></td>';
                } else {
                    error_log( 'ACTION::wp_ajax_' . self::AJAX_GET_MINIFY_MAP . '(): File ' . "$dir/$key does not exists." );
                    echo "<tr class=\"{$script}\">";
                    echo '<td class="w10" rowspan="' . count( $value ) . '">' . $key . '</td>';
                }
            } else {
                echo "<tr class=\"{$script}{$exists}\">";
            }
            $url = '';
            if ( file_exists( ABSPATH . $file ) ) {
                if ( substr_compare( $file, 'wp-includes', 0, 11 ) === 0 ) {
                    $url = includes_url( substr( $file, 11 ) );
                } else if ( substr_compare( $file, 'wp-content', 0, 10 ) === 0 ) {
                    $url = content_url( substr( $file, 10 ) );
                }
            }
            echo '<td class="w5">' . $index . '</td><td class="w85">';
            if ( $url ) {
                echo '<a href="' . $url . '" target="_blank">' . $file . '</a>';
            } else {
                echo $file;
            }
            echo '</td></tr>' . "\n";
        }
    }
?>
    </table>
</body>
</html>
<?php
            exit();
        } );   # add_action( 'wp_ajax_' . self::AJAX_GET_MINIFY_MAP, function() {
        add_action( 'wp_ajax_' . self::AJAX_GET_MINIFY_CACHE_LIST, function() {
?>
<html>
<head>
    <?php echo self::TABLE_STYLE; ?>
</head>
<body>
<?php
            $dir   = \W3TC\Util_Environment::cache_blog_minify_dir();
            $files = scandir( $dir );
            $files = array_filter( $files, function( $v ) {
                  return preg_match( '#^\w+\.(js|css)$#', $v );
            } );
            if ( $files ) {
?>
    <table>
        <tr><th class="w20">file</th><th class="w50">mtime</th><th class="w30">size</th></tr>
<?php
                foreach ( $files as $file ) {
                    $file_path = $dir . '/' . $file;
                    $time = date( DATE_RFC850, filemtime( $file_path ) );
                    $size = (string) filesize( $file_path );
                    echo '<tr><td class="w20">' . $file . '</td>';
                    echo     '<td class="w50">' . $time . '</td>';
                    echo     '<td class="w30">' . $size . '</td></tr>';
                }
?>
    </table>
<?php
            } else {
?>
    There are no JavaScript or CSS files in the minify cache directory.
<?php
            }
?>
</body>
</html>
<?php
            exit();
        } );   # add_action( 'wp_ajax_' . self::AJAX_GET_MINIFY_CACHE_LIST, function() {
        # Make no_priv versions of above AJAX actions.
        foreach ( [ self::AJAX_GET_THEME_MAP, self::AJAX_GET_LOG, self::AJAX_GET_THE_DIFF, self::AJAX_GET_DATABASE,
            self::AJAX_GET_MINIFY_MAP, self::AJAX_GET_MINIFY_CACHE_LIST ] as $ajax_action ) {
            add_action( 'wp_ajax_nopriv_' . $ajax_action, function() use ( $ajax_action ) {
                do_action( "wp_ajax_{$ajax_action}" );
            } );
        }
        if ( ( $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [] ) ) && ! empty( $options[ self::AUTO_MINIFY_OPTION ] ) ) {
            # Purge my auto minify cache when W3TC purges its cache.
            add_action( 'w3tc_flush_minify', 'MC_Alt_W3TC_Minify::purge_auto_minify_cache' );
            add_action( 'load-performance_page_w3tc_general', function( ) {
                # The "Empty cache" button of the "Minify" section of the "General Settings" page will not do the action 'w3tc_flush_minify' so ...
                if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'w3tc_general'
                        && ! empty( $_GET['w3tc_note'] ) && $_GET['w3tc_note'] === 'flush_minify' ) {
                    # error_log( 'action::performance_page_w3tc_general():$_GET=' . print_r( $_GET, true ) );
                    MC_Alt_W3TC_Minify::purge_auto_minify_cache( );
                }
            } );
        }
        # On deactivation remove everything created by this plugin. 
        register_deactivation_hook( __FILE__, function() {
            self::reset( TRUE );
        } );
    }   # public static function admin_init() {
    public static function on_activate() {
        register_activation_hook(__FILE__, function() {
            if ( ! ( $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ ] ) ) ) {
                # Version 2 is not enabled.
                if ( self::get_the_data() ) {
                    # Version 1 data already exists so run version 1.
                    return;
                }
                # No version 1 data exists, so either this is new installation or version 1 settings have been cleared.
                # In this case default to running version 2.
                update_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ self::AUTO_MINIFY_OPTION => TRUE ] );
            }
        } );
    }   # public static function on_activate() {
    # reset() will remove everything created by this plugin.
    private static function reset( $include_version_2_data = FALSE ) {
        delete_transient( self::TRANSIENT_NAME );
        delete_option( self::OPTION_NAME );
        delete_option( self::OPTION_LOG_NAME );
        delete_option( self::OPTION_SKIPPED_NAME );
        delete_option( self::OPTION_THEME_MAP );
        delete_option( self::OPTION_MISCELLANEOUS );
        if ( $include_version_2_data ) {
            delete_option( self::OPTION_MONITOR_MINIFY_AUTOJS );
        }
        @unlink( self::OUTPUT_DIR . '/' . self::CONF_FILE_NAME );
    }
    private static function get_the_data() {
        if ( self::$the_data === NULL ) {
            self::$the_data = get_option( self::OPTION_NAME, [] );
        }
        return self::$the_data;
    }
    # Update the ordered list of Javascript files for the current theme and template if it is
    # different from its previous value and rebuild the W3TC configuration file if neccessary.
    private static function update_database() {
        # The option value is a two dimensional array indexed first by theme then by template.
        # The array values are arrays of JavaScript file names.
        # Deleting this option will force a rebuild of W3TC configuration file.
        # However, this will require again viewing a web page for all templates.
        $data = self::get_the_data();
        if ( ! array_key_exists( self::$theme, $data ) ) {
            $data[ self::$theme ] = [];
        }
        if ( ! array_key_exists( self::$basename, $data[ self::$theme ] ) ) {
            $data[ self::$theme ][ self::$basename ] = [
                                                           'include'        => [ 'files' => [] ],
                                                           'include-body'   => [ 'files' => [] ],
                                                           'include-footer' => [ 'files' => [] ]
                                                       ];
        }
        # Check if the ordered JavaScript file list has changed for the current theme and template.
        $datum =& $data[ self::$theme ][ self::$basename ];
        if ( ( ! empty( $_REQUEST[ self::OVERRIDE_DO_NOT_MINIFY ] ) || $datum !== self::DO_NOT_MINIFY )
            && self::$files !== $datum ) {
            # First flush the queued template warnings.
            self::manage_notice_queue( self::TEMPLATE_WARNINGS, 'flush' );
            # Record exactly how the new configuration differs from the previous configuration.
            self::log_the_diff( self::$files, $datum );
            # Update the array item for the current theme and template.
            if ( self::$files !== self::DO_NOT_MINIFY ) {
                $datum = self::$files;
            } else {
                # unset( $data[ self::$theme ][ self::$basename ] );
                # if ( empty( $data[ self::$theme ] ) ) {
                #     unset( $data[ self::$theme ] );
                # }
                # The above will not work as missing item will be interpreted as an uninitialized item.
                $datum = self::$files;
            }
            # error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::new $data=' . print_r( $data, TRUE ) );
            # The minify JavaScript configuration has changed so save the new configuration into the database.
            update_option( self::OPTION_NAME, $data );
            # Then generate a new W3TC configuration file using the new data.
            self::update_config_file( $data );
            # Update the history of changes to the ordered list of Javascript files for themes and templates.
            self::add_log_entry( ! empty( self::$files ) ? 'Updated.' : 'Removed.' );
            # Create or update the transient notices. 
            self::add_notice( self::PLUGIN_NAME
                                 . ': The ordered list of JavaScript files for the theme: "'
                                 . '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action='
                                 .     self::AJAX_GET_THEME_MAP . '" target="_blank">' . self::$theme . '</a>'
                                 . '" and the template: "' . self::$basename . '" has been '
                                 . '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action='
                                 .     self::AJAX_GET_THE_DIFF . '&theme=' . self::$theme . '&basename='
                                 .     self::$basename . '" target="_blank">updated</a>.' );
        } else {
        }
    }   # private static function update_database() {
    private static function update_config_file( $new_data ) {
        $config = \W3TC\Config::util_array_from_storage( 0, FALSE );
        # error_log( 'MC_Alt_W3TC_Minify::update_config_file():old $config=' . print_r( $config, TRUE ) );
        $config_minify_js_groups =& $config['minify.js.groups'];
        foreach ( $new_data as $theme => $data ) {
            if ( array_key_exists( $theme, $config_minify_js_groups ) ) {
                # Replace matching template items for this theme.
                $config_minify_js_groups[ $theme ] = array_merge( $config_minify_js_groups[ $theme ], $data );
            } else {
                $config_minify_js_groups[ $theme ] = $data;
            }
        }
        # Remove DO_NOT_MINIFY items from $config['minify.js.groups'] before saving new config file;
        foreach ( $config_minify_js_groups as &$theme ) {
            $theme = array_filter( $theme, function( $template ) {
                return $template !== self::DO_NOT_MINIFY;
            } );
        }
        $config_minify_js_groups = array_filter( $config_minify_js_groups, function( $theme ) {
            return $theme;
        } );
        # error_log( 'MC_Alt_W3TC_Minify::update_config_file():new $config=' . print_r( $config, TRUE ) );
        # Save the new configuration to a disk file which can be downloaded.
        if ( defined( 'JSON_PRETTY_PRINT' ) ) {
            $config = json_encode( $config, JSON_PRETTY_PRINT );
        } else {  // for older php versions
            $config = json_encode( $config );
        }
        \W3TC\Util_File::file_put_contents_atomic( self::OUTPUT_DIR . '/' . self::CONF_FILE_NAME, $config );
    }   # private static function update_config_file( $new_data ) {
    private static function set_database_to_skip_current_template( $log_entry = '', $notice = '' ) {
        $skipped = get_option( self::OPTION_SKIPPED_NAME, [] );
        if ( ! array_key_exists( self::$theme, $skipped ) ) {
            $skipped[ self::$theme ] = [];
        }
        if ( ! in_array( self::$basename, $skipped[ self::$theme ] ) ) {
            if ( $log_entry ) {
                self::add_log_entry( $log_entry );
            }
            if ( $notice ) {
                self::add_notice( self::PLUGIN_NAME . $notice );
            }
            $skipped[ self::$theme ][] = self::$basename;
            update_option( self::OPTION_SKIPPED_NAME, $skipped );
        }
    }
    # log_the_diff() compute exactly how the new conf differs from the old conf and saves the diff to the log.
    private static function log_the_diff( $new, $old ) {
        if ( $new === self::DO_NOT_MINIFY || $old === self::DO_NOT_MINIFY ) {
            return;
        }
        $diff = new stdClass(); 
        foreach ( [ 'include', 'include-body', 'include-footer' ] as $location ) {
            $diff->{$location}          = new stdClass();
            $diff->{$location}->added   = array_diff( $new[ $location ]['files'], $old[ $location ]['files'] );
            $diff->{$location}->removed = array_diff( $old[ $location ]['files'], $new[ $location ]['files'] );
        }
        self::add_log_entry( $diff );
    }
    # get_the_latest_diff() finds the most recent diff for the template $basename of $theme.
    private static function get_the_latest_diff( $theme, $basename ) {
        $log = get_option( self::OPTION_LOG_NAME, [] );
        if ( ! array_key_exists( $theme, $log ) ) {
            return NULL;
        }
        if ( ! array_key_exists( $basename, $log[ $theme ] ) ) {
            return NULL;
        }
        $data = $log[ $theme ][ $basename ];
        for ( end( $data ); ; prev( $data ) ) {
            if ( ( $datum = current( $data )->data ) === FALSE ) {
                return NULL;
            }
            if ( is_object( $datum ) && property_exists( $datum, 'include' )
                && is_object( $datum->include ) && property_exists( $datum->include, 'added' ) ) {
                return $datum;
            }
        }
        return NULL;
    }
    # In a non-frontend environment (AJAX) the current template must be manually set.
    private static function set_current_template( $theme, $basename ) {
        self::$theme    = $theme;
        self::$basename = $basename;
    }
    private static function add_log_entry( $entry ) {
        $log = get_option( self::OPTION_LOG_NAME, [] );
        if ( ! array_key_exists( self::$theme, $log ) ) {
            $log[ self::$theme ] = [];
        }
        if ( ! array_key_exists( self::$basename, $log[ self::$theme ] ) ) {
            $log[ self::$theme ][ self::$basename ] = [];
        }
        $log[ self::$theme ][ self::$basename ][] = (object) [ 'time' => current_time( 'mysql' ), 'data' => $entry ];
        update_option( self::OPTION_LOG_NAME, $log );
    }
    private static function add_notice( $notice, $no_duplicate = FALSE ) {
        $notices = get_transient( self::TRANSIENT_NAME );
        if ( $notices === FALSE ) {
            $notices = [ $notice ];
        } else if ( $no_duplicate === FALSE || ! in_array( $notice, $notices ) ) {
            $notices[] = $notice;
        } else {
            return FALSE;
        }
        set_transient( self::TRANSIENT_NAME, $notices );
        return TRUE;
    }
    # Some notices should only be added only if a later condition is true.
    # These notices are held in a temporary queue and will be added later if the required condition is true.
    private static function manage_notice_queue( $queue, $action, $notice = NULL ) {
        static $data = [];
        switch ( $action ) {
        case 'add':
            if ( ! array_key_exists( $queue, $data ) ) {
                $data[ $queue ]   = [ $notice ];
            } else {
                $data[ $queue ][] = $notice;
            }
            break;
        case 'flush':
            if ( ! empty( $data[ $queue ] ) ) {
                foreach ( $data[ $queue ] as $notice ) {
                    self::add_notice( $notice );
                }
            }
        case 'empty':
            unset( $data[ $queue ] );
            break;
        }
    }
    # Miscellaneous is currently used to hold meta data for notices using the notice id as the key.
    private static function add_miscellaneous( $key, $note ) {
        $notes = get_option( self::OPTION_MISCELLANEOUS, [] );
        # There may be multiple notes for a given key so $notes[ $key ] is an array.
        if ( ! array_key_exists( $key, $notes ) ) {
            $notes[ $key ] = [];
        }
        $notes[ $key ][] = [
                               'time' => time(),
                               'data' => $note
        ];
        update_option( self::OPTION_MISCELLANEOUS, $notes );
    }
    private static function get_miscellaneous( $key ) {
        $notes = get_option( self::OPTION_MISCELLANEOUS, [] );
        # Extract only the 'data' component before returning.
        return array_key_exists( $key, $notes ) ? array_map( function( $data ) {
            return $data['data'];
        }, $notes[ $key ] ) : [];
    }
    private static function delete_miscellaneous( $key ) {
        $notes = get_option( self::OPTION_MISCELLANEOUS, [] );
        unset( $notes[ $key ] );
        update_option( self::OPTION_MISCELLANEOUS, $notes );
    }
    private static function delete_old_miscellaneous( $limit = 86400 * 10 ) {
        $notes = get_option( self::OPTION_MISCELLANEOUS, [] );
        $now   = time();
        $notes = array_filter( array_map( function( $data ) use ( $now, $limit ) {
            return array_filter( $data, function( $value ) use ( $now, $limit ) {
                return $now - $value['time'] < $limit;
            } );
        }, $notes ) );
        update_option( self::OPTION_MISCELLANEOUS, $notes );
    }
    public static function minify( $sources ) {
        static $w3_minifier = NULL;
        if ( $w3_minifier === NULL ) {
            $w3_minifier = \W3TC\Dispatcher::component( 'Minify_ContentMinifier' );
        }
        if ( ! is_object( $w3_minifier ) ) {
            return NULL;
        }
        if ( ! is_array( $sources ) ) {
            $sources = [ $sources ];
        }
        # Get the default JavaScript minifier used by Minify0_Minify::serve() -> Minify0_Minify::_combineMinify() in Minify.php.
        # This minifier is not applied to already minified files - ".min.js" files.
        // $js_minifier = $w3_minifier->get_minifier( 'combinejs' );
        $js_minifier = $w3_minifier->get_minifier( 'js' );
        $js_options  = $w3_minifier->get_options( 'js' );
        error_log( '$js_minifier=' . print_r( $js_minifier, true ) );
        error_log( '$js_options=' . print_r( $js_options, true ) );
        $cache_id        = md5( serialize( [ $sources, $js_minifier, $js_options ] ) );
        # Minify_Core::urls_for_minification_to_minify_filename() actually uses substr( $cache_id, 0, 5 ).
        # But it also has a collision protection safeguard which I don't want to implement so I use the full md5.
        $cache_id       .= '.js';
        $original_length = 0;
        $content         = [];
        foreach ( $sources as $source ) {
            $original_length += strlen( $source );
            $content[]        = call_user_func( $js_minifier, $source, $js_options );
        }
        $content         = implode( "\n;", $content );
        $minify          = \W3TC\Dispatcher::component( 'Minify_MinifiedFileRequestHandler' );
        $cache           = $minify->_get_cache();
        $cache->store( $cache_id, [ 'originalLength' => $original_length, 'content' => $content ] );
        # TODO: The compression logic should be determined from Minify0_Minify::$_options['encodeMethod'],
        # Minify0_Minify::$_options['encodeOutput'], ... But I don't see how to access Minify0_Minify::$_options.
        # So for now just hardcode gzip.
        if ( function_exists( 'gzencode' ) ) {
            $compressed = gzencode( $content, 9 );
            $cache->store( $cache_id . '_gzip', [ 'originalLength' => $original_length, 'content' => $compressed ] );
        }
        return [
            'original_length' => $original_length,
            'content'         => $content,
            'cache_id'        => $cache_id
        ];
    }
    ########################################################################################################################################
    # Version 2 code starts here. Version 2 code adds actions and filters to monitor/modify the execution of W3TC in auto minify mode.
    # monitor_minify_autojs() can analyze the processing of Minify_AutoJs.php.
    # monitor_minify_autojs() optionally can replace the minify processing of W3TC's Minify_AutoJs.php.
    # If get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [] )[ self::AUTO_MINIFY_OPTION ] is TRUE then monitor_minify_autojs()
    # replaces the the minify processing of W3TC's Minify_AutoJs.php and also disables the manual minify processing of this plugin.
    public static function monitor_minify_autojs( ) {
        if ( ! ( $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ ] ) ) ) {
            return FALSE;
        }
        self::$w3tc_minify_helpers = new \W3TC\_W3_MinifyHelpers( \W3TC\Dispatcher::config() );
        # add_action( 'wp_head', function( ) {
        #     # This is a way to insert a tag as the last item in the HTML <head> section.
        #     echo '<meta name="mc_w3tcm" content="##### SHOULD BE LAST TAG IN HEAD SECTION #####">';
        # }, PHP_INT_MAX );
        # add_action( 'wp_footer', function( ) {
        #     # This is a way to insert a tag as the last item in the HTML <body> section.
        #     echo '<div style="display:none;">##### SHOULD BE LAST TAG IN BODY SECTION #####</div>';
        # }, PHP_INT_MAX );
        self::$auto_minify = ! empty( $options[ self::AUTO_MINIFY_OPTION ] );
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_process_content'] ) ) ) {
            add_filter( 'w3tc_process_content', function( $buffer ) use ( $monitor ) {
                if ( self::$auto_minify ) {
                    if ( $matches = self::check_for_conditional_html( $buffer ) ) {
                        # If the conditional html has a <script> element then this may change the order of <script> elements execution.
                        # However, it seems the conditionally included JavaScript is usually immune to changes in script order execution.
                        # W3TC removes all HTML comments before processing <script> elements - see Minify_AutoJs::execute().
                        # Unfortunately, this removes the <script> elements included inside HTML comments. In particular, W3TC is
                        # also not handling <script> elements embedded inside HTML comments correctly with respect to <script>
                        # order execution.
                        $conditional_scripts = [];
                        foreach ( $matches as $match ) {
                            # error_log( 'FILTER::w3tc_process_content():$match[0]=' . $match[0] );
                            # error_log( 'FILTER::w3tc_process_content():$match[1]=' . $match[1] );
                            # error_log( 'FILTER::w3tc_process_content():$match[2]=' . $match[2] . '####' );
                            if ( preg_match( '#<script.+?</script>#s', $match[2], $script_matches ) === 1 ) {
                                # $match[2] should always have a <script> element but may be padded with spaces or other text
                                # so extract exactly just the <script> element
                                # error_log( 'FILTER::w3tc_process_content():$script_matches[0]=' . $script_matches[0] . '####' );
                                $conditional_scripts[] = $script_matches[0];
                            }
                        }
                        self::$conditional_scripts = $conditional_scripts;
                        if ( preg_match_all( '~(<script\s*[^>]*>.*?</script>|</head>)~is', $buffer, $matches ) ) {
                            self::$all_scripts = $all_scripts = $matches[1];
                            # foreach ( $conditional_scripts as $conditional_script ) {
                            #     $conditional_script_index = array_search( $conditional_script, $all_scripts );
                            #     error_log( 'FILTER::w3tc_process_content():$conditional_script_index=' . $conditional_script_index );
                            # }
                        }
                    }   # if ( $matches = self::check_for_conditional_html( $buffer ) ) {
                }   # if ( self::$auto_minify ) {
                if ( $monitor ) {
                    \W3TC\Util_File::file_put_contents_atomic( self::OUTPUT_DIR . '/filter_w3tc_process_content_buffer', $buffer );
                }
                return $buffer;
            } );
        }
        # W3TC uses PHP's output buffering to rewrite the HTML sent to the browser. The filter 'w3tc_processed_content'
        # is applied after all the rewriting by W3TC is done so this is a good place to dump the final HTML that will be
        # sent to the browser.
        if ( ! empty( $options['FILTER::w3tc_processed_content'] ) ) {
            add_filter( 'w3tc_processed_content', function( $buffer ) {
                \W3TC\Util_File::file_put_contents_atomic( self::OUTPUT_DIR . '/filter_w3tc_processed_content_buffer', $buffer );
                return $buffer;
            } );
        }
        # Before scanning the HTML buffer for <script> elements W3TC removes all HTML comments - <!-- ... -->. See
        # Minify_AutoJs::execute().  Unfortunately, these comments may contain embedded <script> elements to be
        # conditionally included, e.g.
        # <!--[if lt IE 9]>
        #     <script type='text/javascript'>...</script>
        # <![endif]-->
        # In order to process ALL <script> elements in the order as they occur in the HTML buffer we need to replace the
        # list of <script> elements that the W3TC scan found with a list that also includes the conditionally included
        # <script> elements. The filter 'w3tc_minify_js_script_tags' can be used to do this.
        if ( self::non_short_circuit_or( self::$auto_minify, $monitor = ! empty( $options['FILTER::w3tc_minify_js_script_tags'] ) ) ) {
            add_filter( 'w3tc_minify_js_script_tags', function( $script_tags ) use ( $monitor ) {
                if ($monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_script_tags():' );
                    self::print_r( $script_tags, '$script_tags' );
                }
                if ( self::$auto_minify && self::$all_scripts ) {
                    # If there are HTML comment conditional inline scripts replace the $script_tags with self::$all_scripts
                    # which includes the HTML comment conditional inline scripts.
                    return self::$all_scripts;
                }
                return $script_tags;
            } );
        }
        # The filter 'w3tc_minify_js_do_local_script_minification' is called when W3TC encounters an inline <script>
        # element. When W3TC encounters an inline <script> element it flushes the combined contents of the currently
        # collected <script> elements as a new <script> element and starts a new collection. See
        # Minify_AutoJs::process_script_tag(). This is not ideal as this may result in multiple <script> elements.
        # Instead we will collect the inline <script> element by copying its content to a local file on the server and
        # then handling it as an external <script> element and continue collecting to the same collection.
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_minify_js_do_local_script_minification'] ) ) ) {
            add_filter( 'w3tc_minify_js_do_local_script_minification', function( $data ) use ( $monitor ) {
                # This is an inline <script> element.
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_do_local_script_minification():' );
                    # self::print_r( $data,                        '$data'                        );
                    self::print_r( $data['script_tag_original'], '$data["script_tag_original"]' );
                    self::print_r( $data['script_tag_number'],   '$data["script_tag_number"]'   );
                }
                if ( self::$auto_minify ) {
                    $script_tag_number = $data['script_tag_number'];
                    if ( strpos( ( $script_tag = $data['script_tag_original'] ), '</head>' ) === FALSE ) {
                        # Collect this inline <script> element.
                        # Is this a HTML comment conditional inline script?
                        $conditional = self::$conditional_scripts && in_array( $script_tag, self::$conditional_scripts );
                        # Remove the HTML start and end tags from $script_tag.
                        $content     = preg_replace( '#</?script(\s.*?>|>)#', '', $script_tag );
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_EMIT_INLINE_MARKERS ) {
                            static $counter = 0;
                            $content  = "console.log( '##### MC_W3TCM: inline script $counter starts here.' );\n" . $content;
                            $content .= "\nconsole.log( '##### MC_W3TCM: inline script $counter ends here.' );\n";
                            ++$counter;
                        }
                        # Bracket HTML comment conditional inline scripts with a matching JavaScript condition.
                        if ( $conditional ) {
                            $condition = 'w3tcmHtmlCond_' . md5( $script_tag );
                            # $condition will be true if and ony if the corresponding HTML comment conditional is true.
                            $content   = "if ( typeof {$condition} !== 'undefined' && {$condition} ) {\n{$content}\n}\n";
                        }
                        # Save the content of the inline <script> element in a file.
                        $filename          = self::MINIFY_FILENAME_PREFIX . md5( $content ) . '.js';
                        if ( ! file_exists( $filename ) ) {
                            \W3TC\Util_File::file_put_contents_atomic( $filename, $content );
                        }
                        # PHP Fatal error:  Uncaught Error: Cannot access private property W3TC\Minify_AutoJs::$files_to_minify
                        # Unfortunately we cannot access the private property $minify_auto_js->files_to_minify so modify its
                        # shadow instead. We will need to correct this later.
                        $extras = array_sum( self::$files_to_minify_extras );
                        if ( $script_tag_number !== count( self::$files_to_minify ) - $extras ) {
                            error_log( 'MC_Alt_W3TC_Minify Error: The shadow $files_to_minify is out of sync.[0]' );
                            error_log( 'MC_Alt_W3TC_Minify Error: $script_tag_number=' . $script_tag_number );
                            error_log( 'MC_Alt_W3TC_Minify Error: count( self::$files_to_minify )=' . count( self::$files_to_minify ) );
                        }
                        self::$files_to_minify[ $script_tag_number + $extras ] = substr( $filename, strlen( ABSPATH ) );
                        # Remove this inline <script> element.
                        $data['should_replace'] = TRUE;
                        if ( $conditional ) {
                            # Replace the inline script with a global JavaScript variable $condition that is set to true.
                            # $condition will be true if and ony if the corresponding HTML comment conditional is true.
                            $data['script_tag_new'] = "<script>\n// mc_w3tcm: inline replaced.\nvar {$condition} = true;\n</script>";
                        } else {
                            // $data['script_tag_new'] = "<!-- mc_w3tcm: inline start -->{$data['script_tag_original']}<!-- mc_w3tcm: inline end -->\n";
                            $data['script_tag_new'] = "<!-- mc_w3tcm: inline replaced. -->\n";
                        }
                        # Minify_AutoJs::process_script_tag() does not update embed_pos when processing inline scripts.
                        # So, track location of the first and the last inline script as these can be used to compute a better embed_pos.
                        if ( self::$first_inline_script_start_pos === NULL ) {
                            self::$first_inline_script_start_pos = $data['script_tag_pos'];
                            self::$first_inline_script_end_pos   = self::$first_inline_script_start_pos + strlen( $data['script_tag_new'] );
                        }
                        self::$last_inline_script_start_pos = $data['script_tag_pos'];
                        self::$last_inline_script_end_pos   = self::$last_inline_script_start_pos + strlen( $data['script_tag_new'] );
                        # There is a problem with the above.
                        # Conditional scripts are bracketed by HTML comments, e.g., "<!--[if lte IE 8]><script>...</script><![endif]-->"
                        # The offsets above should include these but do not.
                        if ( $conditional ) {
                            # Assuming the conditional ends like this "</script>\n<![endif]-->" and 12 === strlen( "<![endif]-->" )
                            self::$last_inline_script_end_pos += 1 + 12;
                            # What if other white spaces or no white space precede "<![endif]-->" e.g., "</script><![endif]-->"
                            # However, this is not a problem if the WordPress API i.e., the function
                            # wp_script_add_data( ..., 'conditional', ... ), is used to add the "conditional" script as the current WordPress
                            # implementation (Version 5.5.1) adds a single white space - a "\n" - between the '</script>' and '<![endif]-->'.
                            # See implementation of WP_Scripts::do_item() in file ".../wp-includes/class.wp-scripts.php".
                            # This dangerous implementation dependency is fixed by "version 3/w3tc-minify-helper-v3.php" which canonicalizes
                            # the output of WP_Scripts::do_item().
                            # The other offsets are also wrong but currently self::$last_inline_script_end_pos is the only offset that is used.
                        }
                        self::$inline_script_conditional    = $conditional;
                        if ( $monitor ) {
                            error_log( 'FILTER::w3tc_minify_js_do_local_script_minification():' );
                            self::print_r( self::$files_to_minify, 'self::$files_to_minify' );
                        }
                    } else {
                        # This is a </head>. Update the $files_to_minify shadow with NULL to keep synchronization.
                        self::$files_to_minify[ $script_tag_number + array_sum( self::$files_to_minify_extras ) ] = NULL;
                        self::$end_head_tag_pos = $data['script_tag_pos'];
                    }
                }
                return $data;
            } );
        }
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_minify_js_do_tag_minification'] ) ) ) {
            add_filter( 'w3tc_minify_js_do_tag_minification', function( $do_tag_minification, $script_tag, $file )
                    use ( $monitor ) {
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_do_tag_minification():' );
                    self::print_r( $do_tag_minification, '$do_tag_minification' );
                    self::print_r( $script_tag,          '$script_tag'          );
                    self::print_r( $file,                '$file'                );
                }
                if ( self::$auto_minify ) {
                    # All non-inline scripts should pass through this filter so this is a good place to track them.
                    # Is this a HTML comment conditional external script?
                    if ( self::$conditional_scripts && in_array( $script_tag, self::$conditional_scripts ) ) {
                        # This is a conditionally loaded script. Conditionally loaded scripts will be processed
                        # by the later filter 'w3tc_minify_js_do_excluded_tag_script_minification'.
                        # error_log( 'FILTER::w3tc_minify_js_do_tag_minification():HTML comment conditional script=' . $script_tag . '####' );
                        return false;
                    }
                    if ( preg_match( '#\s+(async|defer)(\s|>)#is', $script_tag, $matches ) ) {
                        $sync_type = strtolower( $matches[1] );
                    } else {
                        $sync_type = 'sync';
                    }
                    # error_log( 'FILTER::w3tc_minify_js_do_tag_minification():$sync_type=' . $sync_type );
                    # error_log( 'FILTER::w3tc_minify_js_do_tag_minification():$script_tag='
                    #     . substr( $script_tag, 0, 256 ) . ( strlen( $script_tag ) > 256 ? '...' : '' ) );
                    if ( $sync_type === 'sync' ) {
                        if ( $do_tag_minification ) {
                            # Update the $files_to_minify shadow.
                            self::$files_to_minify[] = $file;
                        } else {
                            # Update the $files_to_minify shadow with NULL to keep synchronization.
                            self::$files_to_minify[] = NULL;
                            #if $do_tag_minification == FALSE then this script is skipped
                            if ( $monitor ) {
                                error_log( "FILTER::w3tc_minify_js_do_tag_minification():\"{$filename}\" skipped " );
                            }
                        }
                    } else {
                        # Update the $files_to_minify shadow with NULL to keep synchronization.
                        self::$files_to_minify[] = NULL;
                        # Nothing to do here as W3TC will handle scripts with 'async' or 'defer' attributes.
                    }
                    if ( $monitor ) {
                        error_log( 'FILTER::w3tc_minify_js_do_tag_minification():' );
                        self::print_r( self::$files_to_minify, 'self::$files_to_minify' );
                    }
                }
                return $do_tag_minification;
            }, PHP_INT_MAX, 3 );
        }
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_minify_js_do_excluded_tag_script_minification'] ) ) ) {
            add_filter( 'w3tc_minify_js_do_excluded_tag_script_minification', function( $data ) use ( $monitor ) {
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():' );
                    self::print_r( $data, '$data' );
                }
                if ( self::$auto_minify ) {
                    $script_tag_original = $data['script_tag_original'];
                    if ( self::$conditional_scripts && in_array( $script_tag_original, self::$conditional_scripts ) ) {
                        # This is a conditionally loaded script.
                        $script_tag_number      = $data['script_tag_number'];
                        $condition              = 'w3tcmHtmlCond_' . md5( $script_tag_original );
                        if ( $monitor ) {
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():$condition=' . $condition );
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():$data["script_tag_original"]='
                                           . $script_tag_original );
                        }
                        $data['should_replace'] = TRUE;
                        $data['script_tag_new'] = "<script>\n// mc_w3tcm: HTML comment conditional replaced.\nvar {$condition} = true;\n</script>";
                        # The script content must be bracketed with the condition.
                        $content_pre            = "if ( typeof {$condition} !== 'undefined' && {$condition} ) {\n";
                        # Save the begin bracket of the <script> element in a file.
                        $filename_pre           = self::MINIFY_FILENAME_PREFIX . md5( $content_pre ) . '.js';
                        if ( ! file_exists( $filename_pre ) ) {
                            \W3TC\Util_File::file_put_contents_atomic( $filename_pre, $content_pre );
                        }
                        $content_post           = "\n}\n";
                        # Save the end bracket of the <script> element in a file.
                        $filename_post          = self::MINIFY_FILENAME_PREFIX . md5( $content_post ) . '.js';
                        if ( ! file_exists( $filename_post ) ) {
                            \W3TC\Util_File::file_put_contents_atomic( $filename_post, $content_post );
                        }
                        # PHP Fatal error:  Uncaught Error: Cannot access private property W3TC\Minify_AutoJs::$files_to_minify
                        # Unfortunately we cannot access the private property $minify_auto_js->files_to_minify so modify its
                        # shadow instead. We will need to correct this later.
                        $extras = array_sum( self::$files_to_minify_extras );
                        if ( $script_tag_number !== count( self::$files_to_minify ) - $extras ) {
                            error_log( 'MC_Alt_W3TC_Minify Error: The shadow $files_to_minify is out of sync.[0]' );
                            error_log( 'MC_Alt_W3TC_Minify Error: $script_tag_number=' . $script_tag_number );
                            error_log( 'MC_Alt_W3TC_Minify Error: count( self::$files_to_minify )=' . count( self::$files_to_minify ) );
                        }
                        $script_src = \W3TC\Util_Environment::url_relative_to_full( $data['script_src'] );
                        if ( ( $file = \W3TC\Util_Environment::url_to_docroot_filename( $script_src ) ) === NULL ) {
                            # The src URL is not from this website.
                            if ( self::$w3tc_minify_helpers->is_file_for_minification( $script_src, $file ) === 'url' ) {
                                $file = $script_src;
                            } else {
                                # We cannot handle this script so let W3TC handle it.
                                self::$files_to_minify[ $script_tag_number + $extras ] = NULL;
                                self::$skipped_scripts[]                               = $script_tag_original;
                                return $data;
                            }
                        }
                        self::$files_to_minify[ $script_tag_number + $extras     ] = substr( $filename_pre,  strlen( ABSPATH ) );
                        self::$files_to_minify[ $script_tag_number + $extras + 1 ] = $file;
                        self::$files_to_minify[ $script_tag_number + $extras + 2 ] = substr( $filename_post, strlen( ABSPATH ) );
                        # The shadow $files_to_minify will be out of sync so fix this.
                        self::$files_to_minify_extras[ $script_tag_number ] = 2;
                        if ( $monitor ) {
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():$files_to_minify['
                                           . ( $script_tag_number + $extras     ) . ']=' . substr( $filename_pre,  strlen( ABSPATH ) ) );
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():$files_to_minify['
                                           . ( $script_tag_number + $extras + 1 ) . ']=' . $file );
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():$files_to_minify['
                                           . ( $script_tag_number + $extras + 2 ) . ']=' . substr( $filename_post, strlen( ABSPATH ) ) );
                            error_log( 'FILTER::w3tc_minify_js_do_excluded_tag_script_minification():' );
                            self::print_r( $data, '$data' );
                        }
                    } else {
                        $data['should_replace'] = TRUE;
                        $data['script_tag_new'] = "<!-- mc_w3tcm -->{$script_tag_original}<!-- mc_w3tcm-->";
                    }
                }
                return $data;
            } );
        }
        # W3TC will flush the collected <script> elements when it encounters an inline <script> element. This is not
        # ideal as it may result in multiple <script> elements. We have collected inline <script> elements by copying
        # their contents to a local file on the server and handling it as another external JavaScript file. Hence, we
        # must prevent W3TC from flushing the collected <script> elements when it encounters an inline <script> element.
        # The filter 'w3tc_minify_js_do_flush_collected' is used to do this by returning FALSE.
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_minify_js_do_flush_collected'] ) ) ) {
            add_filter( 'w3tc_minify_js_do_flush_collected', function( $do_flush_collected, $last_script_tag, $minify_auto_js, $sync_type )
                    use ( $monitor ) {
                if ( $sync_type !== 'sync' ) {
                    return $do_flush_collected;
                }
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_do_flush_collected():' );
                    self::print_r( $do_flush_collected, '$do_flush_collected' );
                    self::print_r( $last_script_tag,    '$last_script_tag'    );
                    # self::print_r( $minify_auto_js,     '$minify_auto_js'     );
                }
                if ( self::$auto_minify ) {
                    # $last_script_tag  === '' means all scripts have been processed.
                    if ( ( $not_head_end_tag = strpos( $last_script_tag, '</head>' ) === FALSE ) && $last_script_tag  !== '' ) {
                        # Logic for determining inline <script> elements extracted from Minify_AutoJs::process_script_tag().
                        $match = NULL;
                        if ( !preg_match( '~<script\s+[^<>]*src=["\']?([^"\'> ]+)["\'> ]~is', $last_script_tag, $match ) ) {
                            $match = NULL;
                        }
                        if ( $monitor ) {
                            self::print_r( is_null( $match ), 'is_null( $match )' );
                        }
                        if ( is_null( $match ) ) {
                            # No src attribute so this is an inline <script> element.
                            self::$last_script_tag_is        = self::INLINE_SCRIPT;
                            // The following does not work because $minify_auto_js->buffer is private.
                            // PHP Fatal error:  Uncaught Error: Cannot access private property W3TC\Minify_AutoJs::$buffer
                            // if ( self::$inline_script_conditional ) {
                            //     self::$inline_script_embed_pos = strpos( $minify_auto_js->buffer, '<![endif]-->', self::$inline_script_tag_pos ) + 11;
                            // }
                            // Alternatively, instead of embedding the minified combined script file after the last script we
                            // can embed just before the </head> tag and escape the need to maintain the 'embed_pos' value.
                            self::$inline_script_conditional = NULL;
                            return FALSE;   # Prevent W3TC's Minify_AutoJs::flush_collected() from executing.
                        } else if ( self::$conditional_scripts && in_array( $last_script_tag, self::$conditional_scripts )
                            && ( self::$skipped_scripts === NULL || ! in_array( $last_script_tag, self::$skipped_scripts ) ) ) {
                            # This is a conditionally loaded script that was processed.
                            return FALSE;   # Prevent W3TC's Minify_AutoJs::flush_collected() from executing.
                        } else {
                            # This is a skipped <script> element.
                            self::$last_script_tag_is = self::SKIPPED_SCRIPT;
                            return TRUE;
                        }
                    } else {
                        if ( ! $not_head_end_tag ) {
                            # This is the </head> element.
                            self::$last_script_tag_is = self::HEAD_END;
                            return TRUE;
                        } else {
                            # We are just after the last <script> element.
                            self::$last_script_tag_is = self::AFTER_LAST_SCRIPT;
                            return TRUE;
                        }
                    }
                }
                return $do_flush_collected;
            }, 10, 4 );
        }
        if ( self::non_short_circuit_or( self::$auto_minify, $monitor = ! empty( $options['FILTER::w3tc_minify_js_step'] ) ) ) {
            add_filter( 'w3tc_minify_js_step', function( $data ) use ( $monitor ) {
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_step():' );
                    # self::print_r( $data,                    '$data'                    );
                    self::print_r( $data['files_to_minify'], '$data["files_to_minify"]' );
                }
                # FILTER 'w3tc_minify_js_step' is also called for scripts with async and defer attributes.
                # However, we only need to handle minification of 'sync' scripts.
                if ( $data['embed_type'] === 'nb-async' || $data['embed_type'] === 'nb-defer' ) {
                    return $data;
                }
                if ( self::$auto_minify ) {
                    if ( array_diff( $data['files_to_minify'], self::$files_to_minify ) ) {
                        error_log( 'MC_Alt_W3TC_Minify Error: The shadow $files_to_minify is out of sync.[1]' );
                        error_log( 'MC_Alt_W3TC_Minify Error: $data[\'files_to_minify\']=' . print_r( $data['files_to_minify'], true ) );
                        error_log( 'MC_Alt_W3TC_Minify Error: self::$files_to_minify=' . print_r( self::$files_to_minify, true ) );
                    }
                    if ( $monitor ) {
                        error_log( 'FILTER::w3tc_minify_js_step():' );
                        self::print_r( self::$last_script_tag_is , 'self::$last_script_tag_is' );
                        self::print_r( self::$files_to_minify,     'self::$files_to_minify'    );
                    }
                    # When this filter is called Minify_AutoJs::process_script_tag() has set $data['embed_pos'] to the position of the first
                    # external script if the <head> section is being processed or the position of the last external script if the <body>
                    # section is being processed.
                    # Set $data['files_to_minify'] to its shadow.
                    switch ( self::$last_script_tag_is ) {
                    # case self::INLINE_SCRIPT: should not happen because filter 'w3tc_minify_js_do_flush_collected' prevents it.
                    # case self::INLINE_SCRIPT:
                    #     $data['files_to_minify'] = [];
                    #     break;
                    case self::SKIPPED_SCRIPT:
                    case self::HEAD_END:
                    case self::AFTER_LAST_SCRIPT:
                        # If the <body> section contains only inline <script> elements then this will not be called.
                        # This is solved by inserting a dummy non-inline <script> into the <body> section.
                        $data['files_to_minify'] = array_merge( array_filter( self::$files_to_minify ) );
                        self::$files_to_minify   = array_map( function( $v ) { return NULL; }, self::$files_to_minify );
                        break;
                    }
                    self::$last_script_tag_is = self::UNKNOWN_SCRIPT_TAG;
                    if (self::$end_head_tag_pos !== NULL ) {
                        # flush_collected() is being called from process_script_tag() when it is processing the </head> tag.
                        # The head scripts needs to be emitted just before the </head> tag so trailing scripts bracketed by
                        # HTML comments are handled correctly.
                        $data['embed_pos']                   = self::$end_head_tag_pos;
                        self::$end_head_tag_pos              = NULL;
                        self::$first_inline_script_start_pos = NULL;
                        self::$first_inline_script_end_pos   = NULL;
                        self::$last_inline_script_start_pos  = NULL;
                        self::$last_inline_script_end_pos    = NULL;
                    } else {
                        if ( self::$last_inline_script_start_pos > $data['embed_pos'] ) {
                            $data['embed_pos'] = self::$last_inline_script_end_pos;
                        }
                    }
                }
                return $data;
            } );
        }
        if ( self::non_short_circuit_or( self::$auto_minify, $monitor = ! empty( $options['FILTER::w3tc_minify_js_step_script_to_embed'] ) ) ) {
            add_filter( 'w3tc_minify_js_step_script_to_embed', function( $data ) use ( $monitor ) {
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_js_step_script_to_embed():' );
                    self::print_r( $data, '$data' );
                }
                if ( self::$auto_minify ) {
                    # The embed position may be wrong if there are inline script elements as W3TC does not process these as files
                    # to be minified and does not update the embed position accordingly.
                    // Calculating the embed position considering the replaced inline scripts is difficult.
                    // $data['embed_pos'] = ?;
                    # Alternatively, $data['embed_pos'] can be fixed in the filter 'w3tc_minify_js_step' - currently this is the solution.
                }
                return $data;
            } );
        }
        if ( self::non_short_circuit_or( self::$auto_minify,
                $monitor = ! empty( $options['FILTER::w3tc_minify_urls_for_minification_to_minify_filename'] ) ) ) {
            add_filter( 'w3tc_minify_urls_for_minification_to_minify_filename', function( $minify_filename, $files, $type )
                    use ( $monitor ) {
                if ( $monitor ) {
                    error_log( 'FILTER::w3tc_minify_urls_for_minification_to_minify_filename():' );
                    self::print_r( $minify_filename, '$minify_filename' );
                    self::print_r( $files,           '$files'           );
                    self::print_r( $type,            '$type'            );
                    self::print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'backtrace' );
                }
                if ( self::$auto_minify ) {
                    # save $minify_filename - the index to the array $minify_filenames as depending on the final solution we
                    # may need to adjust that item in the array $minify_filenames.
                    self::$minify_filename = $minify_filename;
                }
                return $minify_filename;
            }, 10, 3 );
        }
        # Filter 'w3tc_minify_file_handler_minify_options' is new in W3TC version 0.12.0.
        if ( version_compare( W3TC_VERSION, '0.12.0', '>=' ) ) {
            if ( self::non_short_circuit_or( self::$auto_minify,
                    $monitor = ! empty( $options['FILTER::w3tc_minify_file_handler_minify_options'] ) ) ) {
                add_filter( 'w3tc_minify_file_handler_minify_options', function( $serve_options ) use ( $monitor ) {
                    if ( $monitor ) {
                        error_log( 'FILTER::w3tc_minify_file_handler_minify_options():' );
                        self::print_r( $serve_options, '$serve_options' );
                        self::print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'backtrace' );
                    }
                    if ( self::$auto_minify ) {
                        self::$serve_options = $serve_options;
                    }
                    return $serve_options;
                } );
            }
        }
        if ( ! self::$auto_minify ) {
            return FALSE;
        }
        # If the page contains only inline <script> elements then W3TC will not run its JavaScript minifier because it batches
        # only non inline <script> elements and since there are none of these it sees an empty batch. However, the monitor's
        # minifier has batched the inline <script> elements and it needs W3TC to run its minifier as the monitor's minifier
        # runs on a filter in W3TC's minifier code. To solve this we will emit dummy <head> and <body> <script> elements.
        add_action( 'wp_enqueue_scripts', function( ) {
            wp_enqueue_script( 'mc_w3tcm-dummy-fe-head', plugin_dir_url(__FILE__) . 'mc_w3tcm-dummy-fe-head.js', [], FALSE, FALSE );
            wp_enqueue_script( 'mc_w3tcm-dummy-fe-body', plugin_dir_url(__FILE__) . 'mc_w3tcm-dummy-fe-body.js', [], FALSE, TRUE  );
        } );
        # When the JavaScript minifier aborts W3TC throws an exception and returns a HTTP 500 response. It is possible to do
        # something better - i.e., just return the raw unminified files. We will do this by using PHP's output buffering
        # to completely replace the HTTP response emitted by W3TC.
        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
            $ob_status = ob_get_status( TRUE );
            error_log( 'MC_Alt_W3TC_Minify::monitor_minify_autojs():$ob_status=' . print_r( $ob_status, TRUE ) );
        }
        # Here we are only interested in a HTTP request for an auto minified JavaScript file.
        # The URL for an auto minified JavaScript file is resolved by Minify_Plugin::init() in file Minify_Minify_Plugin.php
        $url                              = \W3TC\Util_Environment::filename_to_url( W3TC_CACHE_MINIFY_DIR );
        $parsed                           = parse_url( $url );
        $w3tc_cache_minify_dir_prefix     = '/' . trim( $parsed['path'], '/' ) . '/';
        $w3tc_cache_minify_dir_prefix_len = strlen( $w3tc_cache_minify_dir_prefix );
        $w3tc_cache_minify_filename       = \W3TC\Util_Environment::remove_query_all( substr( $_SERVER['REQUEST_URI'], $w3tc_cache_minify_dir_prefix_len ) );
        # There are two ways the URL for a minified file is written, e.g.,
        #     www.example.com/wp-content/cache/minify/c7035.js
        # or
        #     www.example.com/?w3tc_minify=c70355.js
        # This is specified by the boolean value of W3TC's configuration parameter 'minify.rewrite'.
        # See Minify_Core::minified_url() in the file ...w3-total-cache/Minify_Core.php.
        # If 'minify.rewrite' is false then the second way is used and W3TC has written a customized .htaccess file that prevents calls
        # to the PHP server for JavaScript (and CSS) files that were not directly resolved by the server filesystem.
        # Hence, if the first way is used to write URLs for minified files when the customized .htaccess file exists the server will
        # return 404 HTTP responses. I don't know why but this scenario has occurred.
        # One way to fix this problem is to toggle the "Rewrite URL structure" checkbox in the "Performance/Minify" section of W3TC's admin page.
        if ( ( substr( $_SERVER['REQUEST_URI'], 0, $w3tc_cache_minify_dir_prefix_len ) === $w3tc_cache_minify_dir_prefix
                || ! empty( $_REQUEST['w3tc_minify'] ) )
            && ( $ob_level = ob_get_level() ) <= 2 && ( empty( $_SERVER['SCRIPT_NAME'] ) || $_SERVER['SCRIPT_NAME'] !== 'wp-cli.phar' ) ) {
            # error_log( 'MC_Alt_W3TC_Minify::monitor_minify_autojs():$ob_level=' . $ob_level );
            ob_start( function( $buffer ) use ( $w3tc_cache_minify_filename ) {
                $ob_status     = ob_get_status( TRUE );
                $response_code = http_response_code( );
                if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                    error_log( 'ob_start():callback():' );
                    self::print_r( $ob_status, '$ob_status' );
                    error_log( 'ob_start():callback():http_response_code()=' . $response_code );
                }
                if ( $response_code == 500 ) {
                    # This is a failed HTTP request.
                    # $_GET['ext'] is not part of the original HTTP request but is created by W3TC for a HTTP request for
                    # minified JavaScript file. It is not initially available so the following check must be in the callback.
                    # The minified file is built by Minify_MinifiedFileRequestHandler::process() in file Minify_MinifiedFileRequestHandler.php
                    # Minify_MinifiedFileRequestHandler::process() calls Minify0_Minify::serve() which calls Minify0_Minify::_combineMinify()
                    if ( array_key_exists( 'ext', $_GET ) && $_GET['ext'] === 'js' ) {
                        # This is a HTTP request for an auto minified JavaScript file.
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( 'ob_start():callback():$buffer=' . $buffer . '#####' );
                        }
                        http_response_code( 200 );
                        # We will completely replace the failed HTTP response with a valid response.
                        # The sources for the minified file are in Minify0_Minify::$_controller->sources.
                        # Unfortunately, they are not accessible as the following print_r() shows.
                        # error_log( 'ob_start():callback():' );
                        # self::print_r( Minify0_Minify::$_controller->sources, 'Minify0_Minify::$_controller->sources' );
                        # Fatal error: Uncaught Error: Cannot access protected property Minify0_Minify::$_controller
                        # However, they are constructed using $_GET[] which is accessible.
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( 'ob_start():callback():' );
                            self::print_r( $_GET, '$_GET' );
                        }
                        # Construct Minify0_Minify::$_controller->sources using $_GET[].
                        # The following extracted from Minify_MinifiedFileRequestHandler::process().
                        # TODO: Verify that Minify_MinifiedFileRequestHandler::process() is always called with the default value for $quiet.
                        $quiet         = FALSE;
                        $config        = \W3TC\Dispatcher::config();
                        $browsercache  = $config->get_boolean( 'browsercache.enabled' );
                        $serve_options = array_merge( $config->get_array( 'minify.options' ), array(
                                'debug' => $config->get_boolean( 'minify.debug' ),
                                'maxAge' => $config->get_integer( 'browsercache.cssjs.lifetime' ),
                                'encodeOutput' => ( $browsercache &&
                                    !defined( 'W3TC_PAGECACHE_OUTPUT_COMPRESSION_OFF' ) &&
                                    !$quiet &&
                                    ( $config->get_boolean( 'browsercache.cssjs.compression' ) ||
                                    $config->get_boolean( 'browsercache.cssjs.brotli' ) ) ),
                                'bubbleCssImports' => ( $config->get_string( 'minify.css.imports' ) == 'bubble' ),
                                'processCssImports' => ( $config->get_string( 'minify.css.imports' ) == 'process' ),
                                'cacheHeaders' => array(
                                    'use_etag' => ( $browsercache && $config->get_boolean( 'browsercache.cssjs.etag' ) ),
                                    'expires_enabled' => ( $browsercache && $config->get_boolean( 'browsercache.cssjs.expires' ) ),
                                    'cacheheaders_enabled' => ( $browsercache && $config->get_boolean( 'browsercache.cssjs.cache.control' ) ),
                                    'cacheheaders' => $config->get_string( 'browsercache.cssjs.cache.policy' )
                                ),
                                'disable_304' => $quiet,   // when requested for service needs - need content instead of 304
                                'quiet' => $quiet
                            ) );
                        if ( array_key_exists( 'g', $_GET ) ) {
                            # This case should not happen in auto JavaScript minification so don't need to set the following.
                            # $serve_options['minApp']['groups'] = $this->get_groups( $theme, $template, $type );
                            error_log( 'MC_Alt_W3TC_Minify Error: ob_start():callback(): $_GET["g"] exists!' );
                        }
                        $w3_minifier                                      = \W3TC\Dispatcher::component( 'Minify_ContentMinifier' );
                        $minifier_type                                    = 'application/x-javascript';
                        if ( $config->get_boolean( 'minify.js.combine.header' ) ) {
                            $engine                                       = 'combinejs';
                        } else {
                            $engine                                       = $config->get_string( 'minify.js.engine' );
                            if ( ! $w3_minifier->exists( $engine ) || ! $w3_minifier->available( $engine ) ) {
                                $engine                                   = 'js';
                            }
                        }
                        # From W3TC version 0.12.0 self::$serve_options will be available.
                        if ( self::$serve_options !== NULL ) {
                            $serve_options = self::$serve_options;
                        } else {
                            $serve_options['minifiers'][$minifier_type]       = $w3_minifier->get_minifier( $engine );
                            $serve_options['minifierOptions'][$minifier_type] = $w3_minifier->get_options( $engine );
                        }
                        $controller = new Minify_Controller_MinApp( );
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( 'ob_start():callback():' );
                            self::print_r( $serve_options, '$serve_options' );
                            error_log( 'ob_start():callback():' );
                            self::print_r( self::$serve_options, 'self::$serve_options' );
                        }
                        $options                                          = $controller->setupSources( $serve_options );
                        $options                                          = $controller->analyzeSources( $options );
                        $options                                          = $controller->mixInDefaultOptions( $options );
                        // Determine the same encoding that Minify0_Minify::serve() uses. (Following extracted from Minify0_Minify::serve().)
                        if ( $options['encodeOutput'] ) {
                            $sendVary = TRUE;
                            if ( $options['encodeMethod'] !== NULL ) {
                                // controller specifically requested this
                                $contentEncoding = $options['encodeMethod'];
                            } else {
                                // sniff request header
                                // depending on what the client accepts, $contentEncoding may be
                                // 'x-gzip' while our internal encodeMethod is 'gzip'. Calling
                                // getAcceptedEncoding(false, false) leaves out compress and deflate as options.
                                list( $options['encodeMethod'], $contentEncoding ) = HTTP_Encoder::getAcceptedEncoding( FALSE, FALSE );
                                $sendVary = ! HTTP_Encoder::isBuggyIe();
                            }
                        } else {
                            self::$_options['encodeMethod'] = ''; // identity (no encoding)
                        }
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( 'ob_start():callback():' );
                            self::print_r( $options, '$options' );
                            error_log( 'ob_start():callback():' );
                            self::print_r( $controller->sources, '$controller->sources' );
                        }
                        # Return the unminified files of $controller->sources.
                        $content = self::combine_minify( $options, $controller );
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( 'ob_start():callback():$content["content"]=' . $content['content'] . '#####' );
                        }
                        $minify = \W3TC\Dispatcher::component( 'Minify_MinifiedFileRequestHandler' );
                        $cache  = $minify->_get_cache();
                        if ( NULL !== $cache && ! $options['debug'] ) {
                            /*
                             * The following is probably wrong because it never runs as $cacheId is already set otherwise.
                            # $cacheId initialization code adapted from W3TC'S Minify0_Minify::_getCacheId() method.
                            $cacheId = md5( serialize( [
                                                           Minify_Source::getDigest( $controller->sources ),
                                                           $options['minifiers'],
                                                           $options['minifierOptions'],
                                                           $options['postprocessor'],
                                                           $options['bubbleCssImports'],
                                                           $options['processCssImports']
                                                       ] ) );
                             */
                            $cacheId = $w3tc_cache_minify_filename;
                            error_log( 'ob_start():callback(): $cacheId=' . $cacheId );
                            # The cache code adapted from W3TC's Minify0_Minify::serve() method.
                            $cache->store( $cacheId, $content );
                            if ( $options['encodeOutput'] ) {
                                if ( function_exists('brotli_compress') && $options['encodeMethod'] === 'br' ) {
                                    $compressed            = $content;
                                    $compressed['content'] = brotli_compress($content['content']);
                                    $cache->store( $cacheId . '_' . $options['encodeMethod'], $compressed );
                                }
                                if ( function_exists( 'gzencode' ) && $options['encodeMethod'] && $options['encodeMethod'] !== 'br' ) {
                                    $compressed            = $content;
                                    $compressed['content'] = gzencode($content['content'], $options['encodeLevel'] );
                                    $cache->store( $cacheId . '_' . $options['encodeMethod'], $compressed ) ;
                                }
                            }
                        }
                        // TODO: Some HTTP headers are missing or wrong! (Find out how W3TC is setting the HTTP headers.)

                        // Determine the same HTTP headers that Minify0_Minify::serve() generates. (Following extracted from Minify0_Minify::serve().)
/*
                        $cgOptions = [
                                         'lastModifiedTime' => $options['lastModifiedTime'],
                                         'cacheHeaders'     => $options['cacheHeaders'],
                                         'isPublic'         => $options['isPublic'],
                                         'encoding'         => $options['encodeMethod']
                                     ];
                        if ( $options['maxAge'] > 0 ) {
                            $cgOptions['maxAge']     = $options['maxAge'];
                        } elseif ( $options['debug'] ) {
                            $cgOptions['invalidate'] = TRUE;
                        }
                        $cg      = new HTTP_ConditionalGet( $cgOptions );
                        $headers = $cg->getHeaders( );
                        unset( $cg );

                        ... # TODO: still more needed here

                        foreach ( $headers as $name => $val ) {
                            header( $name . ': ' . $val );
                        }

 */
                        header( 'Content-Type: application/x-javascript' );   // TODO: This is a temporary hack.
                        return $content['content'];
                    }   # if ( array_key_exists( 'ext', $_GET ) && $_GET['ext'] === 'js' ) {
                }   # if ( $response_code == 500 ) {
                return $buffer;
           } );   # ob_start( function( $buffer ) {
        }   # if ( substr( $_SERVER['REQUEST_URI'], 0, strlen( $prefix ) ) === $prefix && ( $ob_level = ob_get_level() ) <= 2
        set_exception_handler( function( $ex ) {
            error_log( 'Exception:$ex=' . print_r( $ex, true ) );
        } );
        if ( empty( $_SERVER['SCRIPT_NAME'] ) || $_SERVER['SCRIPT_NAME'] !== 'wp-cli.phar' ) {
            # error_log( 'MC_Alt_W3TC_Minify::monitor_minify_autojs():register_shutdown_function():called' );
            register_shutdown_function( function( ) use ( $w3tc_cache_minify_dir_prefix, $w3tc_cache_minify_dir_prefix_len, $w3tc_cache_minify_filename ) {
                # The following shows that when shutdown functions are called output buffering has already been completely unwound.
                # $ob_status = ob_get_status( TRUE );
                # error_log( 'register_shutdown_function():callback():$ob_status=' . print_r( $ob_status, true ) );
                # $headers = getallheaders();
                # error_log( 'getallheaders()=' . print_r( $headers, true ) );
                # $response_headers = apache_response_headers( );
                # error_log( 'apache_response_headers()=' . print_r( $response_headers, true ) );
                $response_code = http_response_code( );
                # error_log( 'register_shutdown_function():callback():http_response_code()=' . $response_code );
                if ( $response_code == 500 ) {
                    if ( substr( $_SERVER['REQUEST_URI'], 0, $w3tc_cache_minify_dir_prefix_len ) === $w3tc_cache_minify_dir_prefix ) {
                        # This is a failed HTTP request for a W3TC minified file.
                        $filename = \W3TC\Util_Environment::remove_query_all( substr( $_SERVER['REQUEST_URI'], strlen( $prefix ) ) );
                        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                            error_log( "register_shutdown_function():callback():HTTP request for \"{$_SERVER['REQUEST_URI']}\" failed." );
                            error_log( "register_shutdown_function():callback():HTTP request for minified file \"$w3tc_cache_minify_filename\" failed." );
                        }
                        self::add_notice( self::PLUGIN_NAME . ": HTTP request for minified file \"$w3tc_cache_minify_filename\" failed.", TRUE );
                    }
                }
            } );
        }   # if ( empty( $_SERVER['SCRIPT_NAME'] ) || $_SERVER['SCRIPT_NAME'] !== 'wp-cli.phar' ) {
        return TRUE;
    }   # public static function monitor_minify_autojs( ) {
    public static function purge_auto_minify_cache( ) {
        # Since this only removes the disk files it must be called as a 'w3tc_flush_minify' action
        # as W3TC's code is needed to remove the corresponding map entries.
        # $filename = self::MINIFY_FILENAME_PREFIX . md5( $content ) . '.js';
        foreach ( glob( self::MINIFY_FILENAME_PREFIX . '*.js' ) as $filename ) {
            # error_log( 'MC_Alt_W3TC_Minify::purge_auto_minify_cache():$filename = "' . $filename . '"' );
            @unlink( $filename );
        }
    }
    public static function set_monitor_minify_autojs_options( $name, $value ) {
        $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ ] );
        if ( $value ) {
            $options[ $name ] = TRUE;
        } else {
            unset( $options[ $name ] );
        }
        if ( $options ) {
            update_option( self::OPTION_MONITOR_MINIFY_AUTOJS, $options );
        } else {
            delete_option( self::OPTION_MONITOR_MINIFY_AUTOJS );
        }
    }
    public static function clear_monitor_minify_autojs_options( ) {
        $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ ] );
        if ( ! empty( $options[ self::AUTO_MINIFY_OPTION ] ) ) {
            update_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ self::AUTO_MINIFY_OPTION => TRUE ] );
        } else {
            delete_option( self::OPTION_MONITOR_MINIFY_AUTOJS );
        }
    }
    public static function toggle_auto_minify_option( ) {
        $options = get_option( self::OPTION_MONITOR_MINIFY_AUTOJS, [ ] );
        if ( empty( $options[ self::AUTO_MINIFY_OPTION ] ) ) {
            $options[ self::AUTO_MINIFY_OPTION ] = TRUE;
            $enabled = TRUE;
        } else {
            unset( $options[ self::AUTO_MINIFY_OPTION ] );
            $enabled = FALSE;
        }
        if ( $options ) {
            update_option( self::OPTION_MONITOR_MINIFY_AUTOJS, $options );
        } else {
            delete_option( self::OPTION_MONITOR_MINIFY_AUTOJS );
        }
        return $enabled;
    }
    private static function check_for_conditional_html( $buffer ) {
        # if ( preg_match_all( '#<!--(\[if\s.+?\])>.*?(<script.+?</script>).*?<!\[endif\]-->#s', $buffer, $matches, PREG_SET_ORDER ) ) {
        if ( preg_match_all( '#<!--(\[if\s.+?\])>(.*?)<!\[endif\]-->#s', $buffer, $matches, PREG_SET_ORDER ) ) {
            # error_log( 'MC_Alt_W3TC_Minify::check_for_conditional_html():' );
            # self::print_r( $matches, '$matches' );
            foreach ( $matches as &$match ) {
                if ( strpos( $match[2], '<script' ) === FALSE ) {
                    $match = FALSE;
                }
            }
            $matches = array_values( array_filter( $matches ) );
            # error_log( 'MC_Alt_W3TC_Minify::check_for_conditional_html():' );
            # self::print_r( $matches, '$matches' );
            return $matches;
        }
        return FALSE;
    }
    # is_minified_javascript() tries to determine if a JavaScript file has already been minified.
    # It simply looks at the length of variable names.
    protected static function is_minified_javascript( $buffer ) {
        # Sanitize $buffer by removing all comments and emptying all strings.
        $buffer     = self::sanitize_for_var_statment_processing( $buffer );
        $length     = strlen( $buffer );
        $statistics = (object) [ 'count' => 0, 'total_length' => 0, 'max' => 0 ];
        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
            $statistics->names = [];
        }
        # if ( preg_match_all( '#var\s([^;]+;)#', $buffer, $matches, PREG_PATTERN_ORDER ) ) {
        # The above will not work e.g. var a=function(){var b;}; - N.B. ; inside function does not terminate the var statement
        $offset = 0;
        while ( ( $offset = strpos( $buffer, 'var ', $offset ) ) !== FALSE ) {
            if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                error_log( 'MC_Alt_W3TC_Minify():is_minified_javascript(): $buffer=' . substr( $buffer, $offset, 256 ) );
            }
            $offset = self::parse_js_var_statement( $buffer, $offset + 4, $length, $statistics );
        }
        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
            error_log( 'MC_Alt_W3TC_Minify():is_minified_javascript(): ' );
            self::print_r( $statistics, '$statistics' );
        }
        return $statistics->count > 0 ? ( $statistics->max < 4 && $statistics->total_length / $statistics->count < 3 ) : NULL;
    }
    # sanitize_for_var_statment_processing() removes all comments and empties all strings.
    protected static function sanitize_for_var_statment_processing( $buffer ) {
        $sanitized = '';
        $length    = strlen( $buffer );
        $j = $i    = 0;
        while ( $i < $length ) {
            if ( $buffer[ $i ] === '/' && $buffer[ $i + 1 ] === '/' ) {
                if ( $i > $j ) {
                    $sanitized .= substr( $buffer, $j, $i - $j );
                }
                $j = $i = strpos( $buffer, "\n", $i + 2 ) + 1;
            } else if ( $buffer[ $i ] === '/' && $buffer[ $i + 1 ] === '*' ) {
                if ( $i > $j ) {
                    $sanitized .= substr( $buffer, $j, $i - $j );
                }
                $j = $i = strpos( $buffer, '*/', $i + 2 ) + 2;
            } else if ( $buffer[ $i ] === '\'' or $buffer[ $i ] === '"' ) {
                if ( $i > $j ) {
                    $sanitized .= substr( $buffer, $j, $i - $j );
                }
                $sanitized .= $buffer[ $i ];
                $sanitized .= $buffer[ $i ];
                $j = $i = self::parse_js_string( $buffer, $i + 1, $length, $buffer[ $i ] );
            } else if ( $buffer[ $i ] === '/' ) {
                # Possible regular expression
                for ( $k = $i - 1; $k >= 0; $k-- ) {
                    if ( ! ctype_space( $buffer[ $k ] ) ) {
                        break;
                    }
                }
                if ( $k >= 0 && in_array ( $buffer[ $k ], [ '=', '(', '[', ',', ':' ] ) ) {
                    if ( $i > $j ) {
                        $sanitized .= substr( $buffer, $j, $i - $j );
                    }
                    $sanitized .= '//';
                    $j = $i = self::parse_js_string( $buffer, $i + 1, $length, '/' );
                } else {
                    # Division operator
                    ++$i;
                }
            } else {
                ++$i;
            }
        }
        if ( $i > $j ) {
            $sanitized .= substr( $buffer, $j, $i - $j );
        }
        return $sanitized;
    }
    # The big problem with parsing var statements are statements like:
    # var a = 1, b = function(){...}, c = 2;
    # The function definition can contain any JavaScript so parsing a var statement requires parsing all of JavaScript.
    # It cannot be done with a regular expression and requires a real parser.
    protected static function parse_js_var_statement( $buffer, $offset, $length, $statistics ) {
        while ( $offset < $length ) {
            $offset = self::parse_js_spaces( $buffer, $offset, $length );
            $offset = self::parse_js_name( $buffer, $offset, $length, $statistics );
            $offset = self::parse_js_spaces( $buffer, $offset, $length );
            $char   = $buffer[ $offset ];
            if ( $char === '=' ) {
                $offset = self::parse_js_expression( $buffer, $offset + 1, $length );
            }
            if ( $offset >= $length ) {
                error_log( 'MC_Alt_W3TC_Minify Error: parse_js_var_statement():Illegal string offset.' );
                error_log( 'MC_Alt_W3TC_Minify Error: parse_js_var_statement():$offset=' . $offset );
                error_log( 'MC_Alt_W3TC_Minify Error: parse_js_var_statement():$buffer=' . $buffer );
                return $offset;
            }
            $char = $buffer[ $offset ];
            if ( $char === ',' ) {
                ++$offset;
                continue;
            }
            if ( $char === ';' ) {
                return $offset + 1;
            }
        }
        # For a correct var statement this should not happen.
        return $offset;
    }
    private static function parse_js_name( $buffer, $offset, $length, $statistics ) {
        $char = $buffer[ $offset ];
        if ( ctype_alpha( $char ) || $char === '$' || $char === '_' ) {
            $name = $char;
            ++$offset;
        } else {
            # For a correct var statement this should not happen.
            return $length;
        }
        while ( $offset < $length ) {
            $char = $buffer[ $offset ];
            if ( ctype_alnum( $char ) || $char === '_' ) {
                $name .= $char;
                ++$offset;
                continue;
            }
            break;
        }
        $statistics->count        += 1;
        $name_length               = strlen( $name );
        $statistics->total_length += $name_length;
        if ( $statistics->max < $name_length ) {
            $statistics->max = $name_length;
        }
        if ( property_exists( $statistics, 'names' ) ) {
            $statistics->names[] = $name;
        }
        return $offset;
    }
    private static function parse_js_expression( $buffer, $offset, $length ) {
        $start = $offset;
        while ( $offset < $length ) {
            $char = $buffer[ $offset ];
            if ( $char === ',' || $char === ';' ) {
                return $offset;
            }
            if ( $char === '\'' || $char === '"' ) {
                $offset = self::parse_js_string( $buffer, $offset + 1, $length, $char );
                continue;
            }
            if ( $char === '/' ) {
                # Possible regular expression
                for ( $k = $offset - 1; $k >= $start; $k-- ) {
                    if ( ! ctype_space( $buffer[ $k ] ) ) {
                        break;
                    }
                }
                if ( $k >= $start && in_array ( $buffer[ $k ], [ '=', '(', '[', ',', ':' ] ) ) {
                    $offset = self::parse_js_string( $buffer, $offset + 1, $length, '/' );
                    continue;
                }
            } else if ( $char === '{' || $char === '[' || $char === '(' ) {
                $offset = self::parse_js_group( $buffer, $offset + 1, $length, $char );
                continue;
            }
            ++$offset;
        }
        # For a correct var statement this should not happen.
        return $offset;
    }
    private static function parse_js_string( $buffer, $offset, $length, $delim ) {
        $start = $offset;
        while ( $offset < $length ) {
            $pos = strpos( $buffer, $delim, $offset );
            if ( $pos === FALSE ) {
                # This should not happen in a correct JavaScript file. There must be something wrong somewhere with my parser!
                if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                    error_log( 'MC_Alt_W3TC_Minify Error: parse_js_string(): Unmatched . \'' . $delim .'\'' );
                    error_log( 'MC_Alt_W3TC_Minify Error: parse_js_string(): $buffer=' . substr( $buffer, $offset, 256 ) );
                }
                return $length;
            }
            # Is this an escaped delimiter?
            # Be careful because in a JavaScript string or regular expression the backslash '\' can be itself escaped e.g. var a=/'|\\/g;
            if ( $pos > $start && $buffer[ $pos - 1 ] === '\\' && ( $pos < $start + 2 || $buffer[ $pos - 2 ] !== '\\' ) ) {
                $offset = $pos + 1;
                continue;
            }
            break;
        }
        return $pos + 1;
    }
    private static function parse_js_group( $buffer, $offset, $length, $delim ) {
        static $end_delim_map = [ '{' => '}', '[' => ']', '(' => ')' ];
        $end_delim = $end_delim_map[ $delim ];
        $start = $offset;
        while ( $offset < $length ) {
            $char = $buffer[ $offset ];
            if ( $char === $end_delim ) {
                return $offset + 1;
            }
            if ( $char === '\'' || $char === '"' ) {
                $offset = self::parse_js_string( $buffer, $offset + 1, $length, $char );
                continue;
            }
            if ( $char === '/' ) {
                # Possible regular expression
                for ( $k = $offset - 1; $k >= $start; $k-- ) {
                    if ( ! ctype_space( $buffer[ $k ] ) ) {
                        break;
                    }
                }
                if ( $k >= $start && in_array ( $buffer[ $k ], [ '=', '(', '[', ',', ':' ] ) ) {
                    $offset = self::parse_js_string( $buffer, $offset + 1, $length, '/' );
                    continue;
                }
            } else if ( $char === '{' || $char === '[' || $char === '(' ) {
                $offset = self::parse_js_group( $buffer, $offset + 1, $length, $char );
                continue;
            }
            ++$offset;
        }
        # For a correct var statement this should not happen.
        return $offset;
    }
    private static function parse_js_spaces( $buffer, $offset, $length ) {
        while ( $offset < $length ) {
            if ( ctype_space( $buffer[ $offset ] ) ) {
                ++$offset;
                continue;
            }
            break;
        }
        return $offset;
    }
    # non_short_circuit_or() implements an or where all conditions are always evaluated.
    # This is useful when the conditions have side effects.
    private static function non_short_circuit_or( ...$conditions ) {
        foreach ( $conditions as $condition ) {
            if ( $condition ) {
                return TRUE;
            }
        }
        return FALSE;
    }
    private static function callable_to_string( $callable ) {
        if ( is_string( $callable ) ) {
            return $callable;
        }
        if ( is_array( $callable ) ) {
            if ( is_string( $callable[0] ) && is_string( $callable[1] ) ) {
                return $callable[0] . '::' . $callable[1];
            }
        }
        # The other cases do not occur in this application.
        return 'Error: callable_to_string() failed.';
    }
    # combine_minify() is adapted from Minify0_Minify::_combineMinify().
    # combine_minify() is called only when Minify0_Minify::_combineMinify() has failed - thrown an exception.
    # So, the point is to do it differently and avoid throwing the exception.
    private static function combine_minify( $options, $controller ) {
        $type = $options['contentType'];   # $type should always be Minify0_Minify::TYPE_JS
        // when combining scripts, make sure all statements separated and
        // trailing single line comment is terminated
        $implodeSeparator = "\n;";
        // allow the user to pass a particular array of options to each
        // minifier (designated by type). source objects may still override
        // these
        // if minifier not set, default is no minification. source objects
        // may still override this
        $defaultMinifier = isset( $options['minifiers'][ $type ] )       ? $options['minifiers'][$type]         : FALSE;
        $default_options = isset( $options['minifierOptions'][ $type ] ) ? $options['minifierOptions'][ $type ] : [];
        if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
            error_log( 'MC_Alt_W3TC_Minify::combine_minify(): $defaultMinifier=' . self::callable_to_string( $defaultMinifier ) );
            error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' );
            self::print_r( $default_options, '$default_options' );
        }
        $content        = [];
        $originalLength = 0;
        foreach ( $controller->sources as $source ) {
            if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' );
                self::print_r( $source,  '$source' );
                error_log( 'MC_Alt_W3TC_Minify::combine_minify():(NULL !== $source->minifier)=' . ( ( NULL !== $source->minifier ) ? 'TRUE' : 'FALSE' ) );
                error_log( 'MC_Alt_W3TC_Minify::combine_minify():gettype( $source->minifier )=' . gettype( $source->minifier ) );
            }
            $sourceContent   = $source->getContent();
            $originalLength += strlen($sourceContent);
            // allow the source to override our minifier and options
            $minifier         = ( NULL !== $source->minifier )      ? $source->minifier                                       : $defaultMinifier;
            $minifier_options = ( NULL !== $source->minifyOptions ) ? array_merge( $default_options, $source->minifyOptions ) : $default_options;
            if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' );
                self::print_r( $source,  '$source' );
                error_log( 'MC_Alt_W3TC_Minify::combine_minify():$minifier=' . self::callable_to_string( $minifier ) );
                error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' );
                self::print_r( $minifier_options, '$minifier_options' );
            }
            # Skip already minified JavaScript files, especially since the "YUI Compressor" aborts on some minified JavaScript files.
            if ( $minifier && self::is_minified_javascript( $sourceContent ) !== TRUE ) {
                try {
                    $content[] = call_user_func( $minifier, $sourceContent, $minifier_options );
                } catch ( Exception $e ) {
                    # Minification failed so just emit the unminified JavaScript file.
                    $content[] = $sourceContent;
                    $callable = self::callable_to_string( $minifier );
                    if ( self::add_notice( self::PLUGIN_NAME . ": Minify of file \"{$source->filepath}\" by {$callable}() failed.", TRUE ) ) {
                        # Hint to exclude this file from minification.
                        self::add_notice( self::PLUGIN_NAME . ": Consider excluding file \"{$source->filepath}\" from minification.", TRUE );
                    }
                    if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                        error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' );
                        self::print_r( $e, 'Exception $e' );
                        error_log( 'MC_Alt_W3TC_Minify::combine_minify(): ' . "Minify of file \"{$source->filepath}\" by {$callable}() failed." );
                    }
                }
            } else {
                $content[] = $sourceContent;
                if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_AUTO_JS_MINIFY_ERROR_HANDLER ) {
                    error_log( 'MC_Alt_W3TC_Minify::combine_minify(): Minify of file \"' . $source->filepath . '" skipped.' );
                }
            }
        }
        $content = implode($implodeSeparator, $content);
        // do any post-processing (esp. for editing build URIs)
        if ( $options['postprocessorRequire'] ) {
            require_once $options['postprocessorRequire'];
        }
        if ( $options['postprocessor'] ) {
            $content = call_user_func( $options['postprocessor'], $content, $type );
        }
        return [ 'originalLength' => $originalLength, 'content' => $content ];
    }   # private static function combine_minify( $options, $controller ) {
    # This print_r() is necessary since the real print_r() uses output buffering and this causes the following error:
    #     PHP Fatal error:  print_r(): Cannot use output buffering in output buffering display handlers ...
    # when the real print_r() is used in some W3TC filters e.g. 'w3tc_minify_js_step' which are executed in output
    # buffering display handlers. N.B. this print_r() does not have the second boolean argument.
    public static function print_r( $var, $name = '' ) {
        static $depth        = 0;
        static $done_objects = [];
        static $tabs = '';
        ++$depth;
        $tabs .= '    ';
        $delim = '[';
        while ( TRUE ) {
            if ( is_object( $var ) ) {
                $object_hash = spl_object_hash( $var );
                if ( in_array( $object_hash, $done_objects ) ) {
                    error_log( "{$tabs}{$name} = *** RECURSION ***" );
                    break;
                }
                $done_objects[] = $object_hash;
                $class_name = get_class( $var );
                $var        = (array) $var;
                $delim      = '{';
            }
            if ( is_array( $var ) ) {
                error_log( "{$tabs}{$name} = " . ( $delim === '[' ? 'Array' : $class_name ) . '[' . sizeof( $var ) . "] = {$delim}" );
                foreach ( $var as $index => $value ) {
                    if ( ! ctype_print( $index ) ) {
                        # Fix protected and private property names of objects
                        $index = str_replace( "\x0", '-', $index );
                    }
                    self::print_r( $value, "[$index]" );
                }
                error_log( "{$tabs}" . ( $delim === '{' ? '}' : ']' ) );
            } else {
                error_log( "{$tabs}{$name} = "
                    . ( is_string( $var ) ? '(String[' . strlen($var) . "]) = \"{$var}\""
                                                . ( strpos( $var, "\n" ) !== FALSE ? ' = (String[' . strlen($var) . "]) = {$name}" : '' )
                                          : ( $var === NULL ? 'NULL'
                                                            : ( is_bool ( $var ) ? ( $var ? 'TRUE' : 'FALSE' )
                                                                                 : "(Scalar) = {$var}" ) ) ) );
            }
            break;
        }   # while ( TRUE ) {
        $tabs = substr( $tabs, 0, -4 );
        if ( --$depth === 0 ) {
            $done_objects = [];
        }
    }   # public static function print_r( $var, $name = '' ) {
}   # MC_Alt_W3TC_Minify

if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & (  MC_AWM_191208_DEBUG_MINIFIER_INLINE_BEFORE_SCRIPT_TEST
        | MC_AWM_191208_DEBUG_MINIFIER_INLINE_AFTER_SCRIPT_TEST | MC_AWM_191208_DEBUG_MINIFIER_CONDITIONAL_SCRIPT_TEST
        | MC_AWM_191208_DEBUG_MINIFIER_LOCALIZE_SCRIPT_TEST     | MC_AWM_191208_DEBUG_MINIFIER_IN_FOOTER_SCRIPT_TEST
        | MC_AWM_191208_DEBUG_MINIFIER_HEAD_SCRIPT_TEST         | MC_AWM_191208_DEBUG_MINIFIER_FOOTER_SCRIPT_TEST
        | MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TEST        | MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TRANSLATIONS_TEST
        | MC_AWM_191208_DEBUG_MINIFIER_ASYNC_SCRIPT_TEST        | MC_AWM_191208_DEBUG_MINIFIER_DEFER_SCRIPT_TEST ) ) {

class MC_Alt_W3TC_Minify_Script_Tester extends MC_Alt_W3TC_Minify {
    # This is for testing my auto minifier against specified cases.
    public static function init() {
        add_action( 'wp_head', function( ) {
            printf( "\n<!-- ##### MC_AWM_191208_DEBUG = 0x%016X ##### -->\n", MC_AWM_191208_DEBUG );
        }, 8 );
        add_action( 'wp_enqueue_scripts', function( ) {
            $in_footer = (boolean) ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_IN_FOOTER_SCRIPT_TEST );
            wp_enqueue_script( 'mc_w3tcm-test-head', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-test-head.js', [], FALSE, $in_footer );
            wp_enqueue_script( 'mc_w3tcm-test', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-test.js', ['mc_w3tcm-test-head'], FALSE, $in_footer );
            wp_enqueue_script( 'mc_w3tcm-test-tail', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-test-tail.js', ['mc_w3tcm-test'], FALSE, $in_footer );
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_INLINE_BEFORE_SCRIPT_TEST ) {
                # inject a inline JavaScript before the test script
                wp_add_inline_script( 'mc_w3tcm-test', 'var mcW3tcmBeforeTest="BEFORE";', 'before' );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_INLINE_AFTER_SCRIPT_TEST ) {
                # inject a inline JavaScript after the test script
                wp_add_inline_script( 'mc_w3tcm-test', 'var mcW3tcmAfterTest="AFTER";',  'after' );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_CONDITIONAL_SCRIPT_TEST ) {
                # inject a conditional JavaScript file for test script
                wp_script_add_data( 'mc_w3tcm-test', 'conditional', 'lt IE 9' );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_LOCALIZE_SCRIPT_TEST ) {
                # inject a localize inline JavaScript for test script
                wp_localize_script( 'mc_w3tcm-test', 'mcW3tcmLocalizeTest', [ 'alpha' => 'Hello', 'beta' => 'World' ] );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TRANSLATIONS_TEST ) {
                # inject translations for test script
                wp_set_script_translations( 'mc_w3tcm-test', 'mc_w3tcm-test', __DIR__ . '/languages' );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_ASYNC_SCRIPT_TEST ) {
                wp_enqueue_script( 'mc_w3tcm-async-test', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-async-test.js', ['mc_w3tcm-test'],
                    FALSE, $in_footer );
                wp_enqueue_script( 'mc_w3tcm-async-test-tail', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-async-test-tail.js',
                    ['mc_w3tcm-async-test'], FALSE, $in_footer );
                add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
                    return $handle === 'mc_w3tcm-async-test' ? preg_replace( '#>#', ' async>', $tag, 1 ) : $tag;
                }, 10, 3 );
            }
            if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_DEFER_SCRIPT_TEST ) {
                wp_enqueue_script( 'mc_w3tcm-defer-test', plugin_dir_url(__FILE__) . 'test/mc_w3tcm-defer-test.js', ['mc_w3tcm-test'],
                    FALSE, $in_footer );
                add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
                    return $handle === 'mc_w3tcm-defer-test' ? preg_replace( '#>#', ' defer>', $tag, 1 ) : $tag;
                }, 10, 3 );
            }
        } );   # add_action( 'wp_enqueue_scripts', function( ) {
        if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_PRINT_SCRIPT_TEST ) {
            add_action( 'wp_print_scripts', function( ) {
?>
<script>
    var mcW3tcmActionPrintScriptTest = "wp_print_scripts";
</script>
<?php
            } );
        }
        if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_HEAD_SCRIPT_TEST ) {
            # inject a inline JavaScript using action wp_head
            add_action('wp_head', function( ) {
?>
<script>
    var mcW3tcmActionHeadTest = "wp_head";
</script>
<?php
            } );
        }
        if ( MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_FOOTER_SCRIPT_TEST ) {
            # inject a inline JavaScript using action wp_footer
            add_action('wp_footer', function( ) {
?>
<script>
    var mcW3tcmActionFooterTest = "wp_footer";
</script>
<?php
            } );
        }
    }   # public static function init() {
}   # class MC_Alt_W3TC_Minify_Script_Tester extends MC_Alt_W3TC_Minify {
MC_Alt_W3TC_Minify_Script_Tester::init();

}   # if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & ( MC_AWM_191208_DEBUG_MINIFIER_INLINE_BEFORE_SCRIPT_TEST

if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_WP_CLI_UNIT_TESTER ) {

class MC_Alt_W3TC_Minify_Unit_Tester extends MC_Alt_W3TC_Minify {
    # The following is for unit testing MC_Alt_W3TC_Minify::is_minified_javascript() using WP-CLI.
    # php wp-cli.phar eval 'MC_Alt_W3TC_Minify_Unit_Tester::wp_cli_test_is_minified_javascript("unit-test-1.js");'
    public static function wp_cli_test_is_minified_javascript( $file ) {
        $buffer = file_get_contents( $file );
        $ret    = self::is_minified_javascript( $buffer );
        echo 'is_minified_javascript()=' . ( is_null( $ret ) ? 'NULL' : ( $ret ? 'TRUE' : 'FALSE' ) );
    }
    # The following is for unit testing MC_Alt_W3TC_Minify::sanitize_for_var_statment_processing() using WP-CLI.
    # php wp-cli.phar eval 'MC_Alt_W3TC_Minify_Unit_Tester::wp_cli_test_sanitize_for_var_statment_processing("unit-test-1.js");'
    public static function wp_cli_test_sanitize_for_var_statment_processing( $file ) {
        $buffer   = file_get_contents( $file );
        $name     = basename( $file, 'js' );
        $new_file = str_replace( $name, $name . 'sanitized.', $file );
        file_put_contents( $new_file, self::sanitize_for_var_statment_processing( $buffer ) );
    }
    # The following is for unit testing MC_Alt_W3TC_Minify::parse_js_var_statement() using WP-CLI.
    # php wp-cli.phar eval 'MC_Alt_W3TC_Minify_Unit_Tester::wp_cli_test_parse_js_var_statement();'
    # php wp-cli.phar eval 'MC_Alt_W3TC_Minify_Unit_Tester::wp_cli_test_parse_js_var_statement();' < unit-test-2.js
    public static function wp_cli_test_parse_js_var_statement( ) {
        while ( TRUE ) {
            fwrite( STDERR, '> ' );
            if ( ( $buffer = fgets( STDIN, 1024 ) ) === FALSE ) {
                break;
            }
            $buffer     = self::sanitize_for_var_statment_processing( trim( $buffer, "\r\n" ) );
            $length     = strlen( $buffer );
            $statistics = (object) [ 'count' => 0, 'total_length' => 0, 'max' => 0, 'names' => [] ];
            $offset     = 0;
            while ( ( $offset = strpos( $buffer, 'var ', $offset ) ) !== FALSE ) {
                $offset = self::parse_js_var_statement( $buffer, $offset + 4, $length, $statistics );
            }
            fwrite( STDERR, print_r( $statistics, TRUE ) );
        }
    }
}   # class MC_Alt_W3TC_Minify_Unit_Tester extends MC_Alt_W3TC_Minify {

}   # if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_WP_CLI_UNIT_TESTER ) {

MC_Alt_W3TC_Minify::on_activate();

# Although "version 3/w3tc-minify-helper-v3.php" is written as a plugin, its current location in a subdirectory of this plugin's
# directory means WordPress will not see it as a plugin. "version 3/w3tc-minify-helper-v3.php" currently is only used to fix a
# dangerous dependency on how WP_Scripts::do_item() is implemented by canonicalizing the output of WP_Scripts::do_item().
if ( ! empty( get_option( MC_Alt_W3TC_Minify::OPTION_MONITOR_MINIFY_AUTOJS, [ ] )[ MC_Alt_W3TC_Minify::AUTO_MINIFY_OPTION ] ) ) {
    # Version 3 is needed only if auto minify is enabled.
    include_once( 'version 3/w3tc-minify-helper-v3.php' );
}

# Abort execution if the W3 Total Cache plugin is not activated.
if ( defined( 'WP_ADMIN' ) ) {
    add_action( 'admin_init', function( ) {
        if ( is_plugin_active( MC_Alt_W3TC_Minify::W3TC_FILE ) ) {
            MC_Alt_W3TC_Minify::admin_init( );
        } else {
            add_action( 'admin_notices', function( ) {
    ?>
    <div class="notice notice-info is-dismissible">
        Execution of the W3TC Minify Helper plugin aborted because the required W3 Total Cache plugin is not activated.
    </div>
    <?php
            } );
        }
    } );
} else {
    // add_action( 'wp_loaded', function() {
    # MC_Alt_W3TC_Minify::init() must run before Minify_Plugin::init()
    add_action( 'init', function( ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( MC_Alt_W3TC_Minify::W3TC_FILE ) ) {
            MC_Alt_W3TC_Minify::init( );
        }
    // } );
    }, 9 );
}
# Below for unit testing only.
if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_UNIT_TEST ) {
    if ( ! defined( 'WP_ADMIN' ) ) {
        add_action( 'wp_loaded', function() {
            if ( ! is_plugin_active( MC_Alt_W3TC_Minify::W3TC_FILE )
                || \W3TC\Dispatcher::config()->get_boolean( 'minify.auto' ) ) {
                return;
            }
            $source0 = <<<EOD
function omega( alpha, beta ) {
    var gamma;
    gamma = alpha + beta;
    return gamma;
}
EOD;
            $source1 = <<<EOD
function omicron( alpha, beta ) {
    var gamma = 1;
    return = gamma * alpha * beta;
}
EOD;
            error_log( 'MC_Alt_W3TC_Minify::minify():$source0='          .  $source0 );
            error_log( 'MC_Alt_W3TC_Minify::minify():$source1='          .  $source1 );
            $minified = MC_Alt_W3TC_Minify::minify( [ $source0, $source1 ] );
            error_log( 'MC_Alt_W3TC_Minify::minify():$original_length=' . $minified['original_length'] );
            error_log( 'MC_Alt_W3TC_Minify::minify():$content='         . $minified['content'] );
            error_log( 'MC_Alt_W3TC_Minify::minify():$cache_id='        . $minified['cache_id'] );
            # Also verify that files have been created in ".../wp-content/cache/minify".
        } );
    }
}   # if ( defined( 'MC_AWM_191208_DEBUG' ) && MC_AWM_191208_DEBUG & MC_AWM_191208_DEBUG_MINIFIER_UNIT_TEST ) {
