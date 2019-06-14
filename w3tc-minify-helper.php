<?php
/*
 * Plugin Name: W3TC Minify Helper
 * Description: record the sent order of the JavaScript files and use this to create a W3TC Minify configuration
 * Version: 1.1
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
 * The following WP-CLI commands will display the database data of this plugin:
 *
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_log"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_skipped"));'
 *     php wp-cli.phar eval 'print_r(get_option("mc_alt_w3tc_minify_theme_map"));'
 *     php wp-cli.phar eval 'print_r(get_transient("mc_alt_w3tc_minify"));'
 *
 * The second command is useful in verifying that a view of a representative web
 * page has been done for each of your templates.
 * 
 * The following WP-CLI commands will clear the database data of this plugin:
 * 
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_log");'
 *     php wp-cli.phar eval 'delete_option("mc_alt_w3tc_minify_skipped");'
 * 
 */

class MC_Alt_W3TC_Minify {
    const PLUGIN_NAME            = 'W3TC Minify Helper';
    const OPTION_NAME            = 'mc_alt_w3tc_minify';
    const CONF_FILE_NAME         = 'mc_alt_w3tc_minify.json';
    const OPTION_LOG_NAME        = 'mc_alt_w3tc_minify_log';
    const OPTION_SKIPPED_NAME    = 'mc_alt_w3tc_minify_skipped';
    const OPTION_THEME_MAP       = 'mc_alt_w3tc_minify_theme_map';
    const OPTION_USE_INCLUDE     = 'mc_alt_w3tc_minify_use_include';
    const TRANSIENT_NAME         = 'mc_alt_w3tc_minify';
    const NOTICE_ID              = 'mc_alt_w3tc_minify_notice_id';
    const DO_NOT_MINIFY          = 'DO NOT MINIFY';
    const OVERRIDE_DO_NOT_MINIFY = 'mc_ignore_do_not_minify_flag';           # query parameter to ignore 'do not minify' flag
    const AJAX_RESET             = 'mc_alt_w3tc_minify_reset';
    const AJAX_SET_TEMPLATE_SKIP = 'mc_alt_w3tc_minify_set_template_skip';
    const AJAX_GET_THEME_MAP     = 'mc_alt_w3tc_minify_get_theme_map';
    const AJAX_GET_LOG           = 'mc_alt_w3tc_minify_get_log';
    private static $theme        = NULL;   # MD5 of the current theme
    private static $basename     = NULL;   # the basename of the current template in the current theme
    private static $the_data     = NULL;   # the database of this plugin
    private static $files        = [ 'include' => [ 'files' => [] ], 'include-footer' => [ 'files' => [] ] ];
    # admin-bar.js is a problem because every time the logged in status changes the "Admin Bar" will be inserted
    # or removed causing admin-bar.js to be added or removed from the ordered list of JavaScript files. This will
    # trigger a rebuild of the W3TC configuration file. To solve this we will omit admin-bar.js from the ordered
    # list of JavaScript files. Other files that need to be omitted can be entered into $files_to_skip.
    private static $files_to_skip = [
        "/wp-includes/js/admin-bar.js"
    ];
    # By default processing is skipped. The filter 'template_include' will conditionally enable processing.
    private static $skip          = TRUE;
    # $use_include sets whether to use 'include' or 'include-body' for header scripts
    private static $use_include   = FALSE;
    public static function init() {
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
        # The filter 'template_include' has important side effects.
        # It is used to set the current theme - self::$theme - and the current template - self::$basename.
        # It conditionally enables processing by setting self::$skip = FALSE which is by default set to TRUE.
        add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
            # self::$theme is a MD5 hash of the theme path, the template and the stylesheet. 
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
        }, PHP_INT_MAX, 1 );
        # When each JavaScript file is sent by the server add an entry to the ordered list of JavaScript files for
        # the current theme and template.
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
                if ( wp_scripts()->get_data( $handle, 'data' ) ) {
                    $has_localize_script = TRUE;
                    # error_log( "FILTER::script_loader_tag(): '$src' has a localize script." );
                }
                # check if there is a translation, before or after script for this script.
                # error_log( 'FILTER::script_loader_tag(): $tag=' . $tag );
                $matched = preg_match_all( '#<script.*?</script>#s', $tag, $matches,  PREG_SET_ORDER );
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
                # Localize, translation and before scripts should be emitted before their corresponding script.
                # After scripts should be emitted after their corresponding script. If this order is not 
                # preserved issue a warning.
                if (   ( self::$use_include && ! empty( $has_localize_script ) && ( $position = 'localize' )
                            && ( $order = 'after' ) )
                    || ( self::$use_include && ! empty( $has_before_script ) && ( $position = 'before' )
                            && ( $order = 'after' ) )
                    || ( ! self::$use_include && ! empty( $has_after_script ) && ( $position = 'after' )
                            && ( $order = 'before' ) )
                ) {
                    $theme     = self::$theme;
                    $basename  = self::$basename;
                    $notice_id = md5( $theme . $basename . $src . $position . $order );
                    $ajax_url  = admin_url( 'admin-ajax.php', 'relative' )
                                        . '?action='                  . self::AJAX_SET_TEMPLATE_SKIP 
                                        . '&theme='                   . self::$theme
                                        . '&basename='                . self::$basename
                                        . '&' . self::NOTICE_ID . '=' . $notice_id
                                        . '&_wpnonce='                . wp_create_nonce( self::AJAX_SET_TEMPLATE_SKIP );
                    # error_log( 'FILTER::script_loader_tag(): $ajax_url=' . $ajax_url );
                    self::add_notice( self::PLUGIN_NAME . <<<EOD
: WARNING: In template "$theme.$basename" the script "$src" has a $position script which will be emitted $order itself.
An action is required to resolve this. Either
<a href="{$ajax_url}&skip=1">Do not minify this template.</a>
or
<a href="{$ajax_url}&skip=0">Safe to minify this template.</a>
EOD
                    );
                }
            }
            return $tag;
        }, 10, 3 );
        # On shutdown update the ordered list of Javascript files for the current theme and template if it is
        # different from its previous value and rebuild the W3TC configuration file if neccessary.
        add_action( 'shutdown', function() {
            if ( ! self::$skip ) {
                self::update_database();            
            }
        } );
    }
    public static function admin_init() {
        # This plugin doesn't require much user interactivity so it doesn't have a GUI.
        # Rather some non standard plugin action links are provided.
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
            if ( file_exists( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME ) ) {
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
                    . '&_wpnonce=' . wp_create_nonce( self::AJAX_RESET ) . '" title="Clear the database.">Reset</a>'
            );
            # Another abusive use of AJAX (sent as a normal HTTP request not as XHR) to dump the log in a web page.
            array_push( $links,
                '<a href="' . admin_url( 'admin-ajax.php', 'relative' ) . '?action=' . self::AJAX_GET_LOG
                    . '" title="Dump actions on templates in themes." target="_blank">Dump Log</a>'
            );
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
        # Let the user remove everything created by this plugin by AJAX request.
        # N.B. This AJAX request was not sent by XHR but as a normal HTTP request
        # and will require special handling as a page needs to be returned.
        add_action( 'wp_ajax_' . self::AJAX_RESET, function() {
            check_ajax_referer( self::AJAX_RESET );
            self::reset();
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
            foreach ( $notices as $i => $notice ) {
                if ( strpos( $notice, $notice_id ) !== FALSE ) {
                    unset( $notices[ $i ] );
                    break;
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
        # On deactivation remove everything created by this plugin. 
        register_deactivation_hook( __FILE__, function() {
            self::reset();
        } );
    }
    # reset() will remove everything created by this plugin.
    private static function reset() {
        delete_transient( self::TRANSIENT_NAME );
        delete_option( self::OPTION_NAME );
        delete_option( self::OPTION_LOG_NAME );
        delete_option( self::OPTION_SKIPPED_NAME );
        delete_option( self::OPTION_THEME_MAP );
        @unlink( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME );
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
        # Check if the ordered JavaScript file list has changed for the current theme and template.
        $datum =& $data[ self::$theme ][ self::$basename ];
        if ( ( ! empty( $_REQUEST[ self::OVERRIDE_DO_NOT_MINIFY ] ) || $datum !== self::DO_NOT_MINIFY )
            && self::$files !== $datum ) {
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
                                 .     self::AJAX_GET_THEME_MAP . '" target="_blank">' . self::$theme 
                                 . '</a>'
                                 . '" and the template: "' . self::$basename . '" has been updated.' );
        }
    }
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
        \W3TC\Util_File::file_put_contents_atomic( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME, $config );
    }
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
        $log[ self::$theme ][ self::$basename ][] = current_time( 'mysql' ) . ": $entry ";
        update_option( self::OPTION_LOG_NAME, $log );
    }
    private static function add_notice( $notice ) {
        $notices = get_transient( self::TRANSIENT_NAME );
        if ( $notices === FALSE ) {
            $notices = [ $notice ];
        } else {
            $notices[] = $notice;
        }
        set_transient( self::TRANSIENT_NAME, $notices );
    }
}
# Abort execution if the W3 Total Cache plugin is not activated.
if ( defined( 'WP_ADMIN' ) ) {
    add_action( 'admin_init', function() {
        if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
            MC_Alt_W3TC_Minify::admin_init();
        } else {
            add_action( 'admin_notices', function() {
    ?>
    <div class="notice notice-info is-dismissible">
        Execution of the W3TC Minify Helper plugin aborted because the required W3 Total Cache plugin is not activated.
    </div>
    <?php
            } );
        }
    } );
} else {
    add_action( 'wp_loaded', function() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
            MC_Alt_W3TC_Minify::init();
        }
    } );
}
