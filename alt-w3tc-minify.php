<?php
/*
 * Plugin Name: Alternative W3TC Minify Configuration Script
 * Description: record the sent order of the JavaScript files and use this to create a W3TC Minify configuration
 * Author: Magenta Cuda
 * Author URI: http://magentacuda.com
 */

class MC_Alt_W3TC_Minify {
    const OPTION_NAME    = 'mc_alt_w3tc_minify';
    const CONF_FILE_NAME = 'mc_alt_w3tc_minify.json';
    private static $theme;
    private static $basename;
    private static $files = [ 'include' => [ 'files' => [] ], 'include_footer' => [ 'files' => [] ] ];
    public static function init() {
        add_filter( 'template_include', function( $template ) {
            self::$theme    = \W3TC\Util_Theme::get_theme_key( get_theme_root(), get_template(), get_stylesheet() );
            self::$basename = basename( $template, '.php' );
        } );
        add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
            if ( doing_action( 'wp_print_footer_scripts' ) ) {
                self::$files['include_footer']['files'][] = $src;
            }
            if ( doing_action( 'wp_head' ) ) {
                self::$files['include']['files'][] = $src;
            }
        }, 10, 3 );
        add_action( 'shutdown', function() {
            if ( ! self::$theme ) {
                return;
            }
            $new_data = $old_data = get_option( self::OPTION_NAME, [] );
            if ( ! array_key_exists( self::$theme, $new_data ) ) {
                $new_data[ self::$theme ] = [];
            }
            $new_data[ self::$theme ][ self::$basename ] = self::$files;
            error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::$new_data=' . print_r( $new_data, true ) );
            if ( $new_data !== $old_data ) {
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
        error_log( 'MC_Alt_W3TC_Minify::update_config_file():old $config=' . print_r( $config, TRUE ) );
        foreach( $config['minify.js.groups'] as $theme => &$data ) {
            if ( ! empty( $new_data[ $theme ] ) ) {
                $data = array_merge( $data, $new_data[ $theme ] );
            }
        }
        error_log( 'MC_Alt_W3TC_Minify::update_config_file():new $config=' . print_r( $config, TRUE ) );
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
