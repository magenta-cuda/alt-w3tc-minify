<?php
/*
 * Plugin Name: Alternative W3TC Minify Configuration Script
 * Description: record the sent order of the JavaScript files and use this to create a W3TC Minify configuration
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
 * Every view of a web page sets the ordered list of JavaScript files of the
 * template of that web page. If this ordered list has changed a new W3TC
 * configuration file is generated by modifying the value of the field
 * 'minify.js.groups' of the current W3TC configuration to be compatible with
 * the new ordered list of JavaScript files for that template. This process is
 * cumulative so after viewing web pages for all templates a complete W3TC
 * configuration file will be built. Further, if the ordered list of JavaScript
 * files for a template changes then this will also build a new compatible W3TC
 * configuration file. This of course assumes that the ordered list of JavaScript
 * files for a template is fixed. If your web pages are dynamically cumputing
 * the JavaScript files then this will not work.
 * 
 * This generated JSON configuration file can be downloaded and further edited to
 * fine tune the minify process and imported back into W3TC. The download link is
 * on the "Installed Plugins" admin page after the "Deactivate" link.
 */

class MC_Alt_W3TC_Minify {
    const OPTION_NAME    = 'mc_alt_w3tc_minify';
    const CONF_FILE_NAME = 'mc_alt_w3tc_minify.json';
    private static $theme;
    private static $basename;
    private static $files = [ 'include' => [ 'files' => [] ], 'include-footer' => [ 'files' => [] ] ];
    public static function init() {
        add_filter( 'template_include', function( $template ) {
            # $theme is a MD5 hash of the theme path, template and stylesheet 
            self::$theme    = \W3TC\Util_Theme::get_theme_key( get_theme_root(), get_template(), get_stylesheet() );
            self::$basename = basename( $template, '.php' );
        } );
        add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
            if ( doing_action( 'wp_print_footer_scripts' ) ) {
                self::$files['include-footer']['files'][] = $src;
            }
            if ( doing_action( 'wp_head' ) ) {
                self::$files['include']['files'][] = $src;
            }
        }, 10, 3 );
        add_action( 'shutdown', function() {
            if ( ! self::$theme ) {
                return;
            }
            # The option value is a two dimensional array indexed first by theme then by template.
            # The array values are arrays of JavaScript file names.
            # Deleting this option will force a rebuild of W3TC configuration file.
            # However, this will require again viewing a web page for all templates.
            $new_data = $old_data = get_option( self::OPTION_NAME, [] );
            if ( ! array_key_exists( self::$theme, $new_data ) ) {
                $new_data[ self::$theme ] = [];
            }
            # update the array item for the current theme and template
            $new_data[ self::$theme ][ self::$basename ] = self::$files;
            # error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::$new_data=' . print_r( $new_data, true ) );
            if ( $new_data !== $old_data ) {
                # if the minify JavaScript configuration has changed save the new configuration and generate a new W3TC configuration file
                update_option( self::OPTION_NAME, $new_data );
                self::update_config_file( $new_data );
            }
        } );
        add_filter( 'plugin_action_links_alt-w3tc-minify/alt-w3tc-minify.php', function( $links ) {
            if ( file_exists( W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME ) ) {
                array_push( $links,
                    '<a href="' . WP_CONTENT_URL . '/w3tc-config/' . self::CONF_FILE_NAME . '">Download Alt W3TC Conf File</a>'
                );
            }
            return $links;
        } );
    }
    private static function update_config_file( $new_data ) {
        $config = \W3TC\Config::util_array_from_storage( 0, FALSE );
        # error_log( 'MC_Alt_W3TC_Minify::update_config_file():old $config=' . print_r( $config, TRUE ) );
        foreach( $config['minify.js.groups'] as $theme => &$data ) {
            if ( ! empty( $new_data[ $theme ] ) ) {
                # replace matching template items for this theme
                $data = array_merge( $data, $new_data[ $theme ] );
            }
        }
        # error_log( 'MC_Alt_W3TC_Minify::update_config_file():new $config=' . print_r( $config, TRUE ) );
        if ( defined( 'JSON_PRETTY_PRINT' ) ) {
            $config = json_encode( $config, JSON_PRETTY_PRINT );
        } else {  // for older php versions
            $config = json_encode( $config );
        }
        $filename = W3TC_CONFIG_DIR . '/' . self::CONF_FILE_NAME;
        $url      = WP_CONTENT_URL . '/w3tc-config/' . self::CONF_FILE_NAME;
        \W3TC\Util_File::file_put_contents_atomic( $filename, $config );
    }
}

MC_Alt_W3TC_Minify::init();
