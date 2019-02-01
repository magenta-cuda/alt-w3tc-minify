<?php
/*
 * Plugin Name: W3TC Minify Helper
 * Description: record the sent order of the JavaScript files and use this to create a W3TC Minify configuration
 * Version: 1.0
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
 *     php wp-cli.phar eval 'print_r(get_transient("mc_alt_w3tc_minify"));'
 *
 * The second command is useful in verifying that a view of a representative web
 * page has been done for each of your templates.
 */

class MC_Alt_W3TC_Minify {
    const PLUGIN_NAME         = 'W3TC Minify Helper';
    const OPTION_NAME         = 'mc_alt_w3tc_minify';
    const CONF_FILE_NAME      = 'mc_alt_w3tc_minify.json';
    const OPTION_LOG_NAME     = 'mc_alt_w3tc_minify_log';
    const OPTION_SKIPPED_NAME = 'mc_alt_w3tc_minify_skipped';
    const TRANSIENT_NAME      = 'mc_alt_w3tc_minify';
    private static $theme;
    private static $basename;
    private static $files = [ 'include' => [ 'files' => [] ], 'include-footer' => [ 'files' => [] ] ];
    # admin-bar.js is a problem because every time the logged in status changes the "Admin Bar" will be inserted
    # or removed causing admin-bar.js to be added or removed from the ordered list of JavaScript files. This will
    # trigger a rebuild of the W3TC configuration file. To solve this we will omit admin-bar.js from the ordered
    # list of JavaScript files. Other files that need to be omitted can be entered into $files_to_skip.
    private static $files_to_skip = [
        "/wp-includes/js/admin-bar.js"
    ];
    private static $skip = TRUE;
    public static function init() {
        # Get additional files to skip.
        $files_to_skip = file( __DIR__ . '/files-to-omit.ini', FILE_IGNORE_NEW_LINES );
        $files_to_skip = $files_to_skip === FALSE ? [] : $files_to_skip;
        $files_to_skip = array_filter( $files_to_skip, function( $line ) {
            return $line[0] !== '#';
        } );
        $files_to_skip = array_map( function( $file ) {
            return trim( $file );
        }, $files_to_skip );
        self::$files_to_skip = array_merge( self::$files_to_skip, $files_to_skip );
        $initial_template = NULL;
        add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
            $initial_template = $template;
            return $template;
        }, 0, 1 );
        add_filter( 'template_include', function( $template ) use ( &$initial_template ) {
            # Get the current theme and template.
            # $theme is a MD5 hash of the theme path, template and stylesheet. 
            self::$theme    = \W3TC\Util_Theme::get_theme_key( get_theme_root(), get_template(), get_stylesheet() );
            self::$basename = basename( $template, '.php' );
            # W3TC cannot handle templates included using the filter 'template_include' so log it and send an error notice.
            if ( $template !== $initial_template ) {
                $skipped = get_option( self::OPTION_SKIPPED_NAME, [] );
                if ( ! array_key_exists( self::$theme, $skipped ) ) {
                    $skipped[ self::$theme ] = [];
                }
                if ( ! in_array( self::$basename, $skipped[ self::$theme ] ) ) {
                    self::add_log_entry( "Skipped because it is an override of $initial_template." );
                    self::add_notice( self::PLUGIN_NAME . <<<EOD
: Template "$template" cannot be minified because it was included using the filter 'template_include'
to override the template "$initial_template". W3TC cannot handle templates included using the filter 'template_include'.
EOD
                    );
                    $skipped[ self::$theme ][] = self::$basename;
                    update_option( self::OPTION_SKIPPED_NAME, $skipped );
                }
            } else {
                self::$skip = FALSE;
            }
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
            }
            if ( doing_action( 'wp_head' ) ) {
                self::$files['include']['files'][] = $src;
            }
            return $tag;
        }, 10, 3 );
        # On shutdown update the ordered list of Javascript files for the current theme and template if it is
        # different from its previous value.
        add_action( 'shutdown', function() {
            if ( self::$skip ) {
                return;
            }
            # The option value is a two dimensional array indexed first by theme then by template.
            # The array values are arrays of JavaScript file names.
            # Deleting this option will force a rebuild of W3TC configuration file.
            # However, this will require again viewing a web page for all templates.
            $data = get_option( self::OPTION_NAME, [] );
            if ( ! array_key_exists( self::$theme, $data ) ) {
                $data[ self::$theme ] = [];
            }
            # Check if the ordered JavaScript file list has changed for the current theme and template.
            $datum =& $data[ self::$theme ][ self::$basename ];
            if ( self::$files !== $datum ) {
                # Update the array item for the current theme and template.
                $datum = self::$files;
                # error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::new $data=' . print_r( $data, TRUE ) );
                # The minify JavaScript configuration has changed so save the new configuration into the database
                # and generate a new W3TC configuration file.
                update_option( self::OPTION_NAME, $data );
                self::update_config_file( $data );
                # Update the history of changes to the ordered list of Javascript files for themes and templates.
                self::add_log_entry( 'Updated.' );
                # Create or update the transient notices. 
                self::add_notice( self::PLUGIN_NAME .': The ordered list of JavaScript files for the theme: "' . self::$theme
                                      . '" and the template: "' . self::$basename . '" has been updated.' );
            }
        } );
    }
    public static function admin_init() {
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
            if ( file_exists( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME ) ) {
                # Add the download link for the generated conf file after the "Deactivate" link.
                array_push( $links,
                    '<a href="' . WP_CONTENT_URL . '/w3tc-config/' . self::CONF_FILE_NAME
                        . '">Download New W3TC Conf File</a>'
                );
            }
            return $links;
        } );
        # If the minify JavaScript configuration has changed display an admin notice.
        if ( is_admin() && ! wp_doing_ajax() && ( $notices = get_transient( self::TRANSIENT_NAME ) ) ) {
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
    <br>The new configuration file can be downloaded from <a href="<?php echo $url; ?>"><?php echo $url; ?></a>.
<?php
                }
?>
</div>
<?php
            } );
            delete_transient( self::TRANSIENT_NAME );
        }
        # On deactivation remove everything created by this plugin. 
        register_deactivation_hook( __FILE__, function() {
            delete_transient( self::TRANSIENT_NAME );
            delete_option( self::OPTION_NAME );
            delete_option( self::OPTION_LOG_NAME );
            delete_option( self::OPTION_SKIPPED_NAME );
            @unlink( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME );
        } );
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
        # error_log( 'MC_Alt_W3TC_Minify::update_config_file():new $config=' . print_r( $config, TRUE ) );
        # Save the new configuration to a disk file which can be downloaded.
        if ( defined( 'JSON_PRETTY_PRINT' ) ) {
            $config = json_encode( $config, JSON_PRETTY_PRINT );
        } else {  // for older php versions
            $config = json_encode( $config );
        }
        \W3TC\Util_File::file_put_contents_atomic( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME, $config );
    }
    private static function add_log_entry( $entry ) {
        $log = get_option( self::OPTION_LOG_NAME, [] );
        if ( ! array_key_exists( self::$theme, $log ) ) {
            $log[ self::$theme ] = [];
        }
        $log[ self::$theme ][ self::$basename ] = current_time( 'mysql' ) . ": $entry ";
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