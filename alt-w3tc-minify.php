<?php
/*
Plugin Name: Alternative W3TC Minify Configuration Script
*/

class MC_Alt_W3TC_Minify {
    const OPTION_NAME = 'mc_alt_w3tc_minify';
    private static $theme;
    private static $basename;
    private static $files = [ 'include' => [], 'include_footer' => [] ];
    public static function init() {
        add_filter( 'template_include', function( $template ) {
			self::$theme    = \W3TC\Util_Theme::get_theme_key( get_theme_root(), get_template(), get_stylesheet() );
			self::$basename = basename( $template, '.php' );
        } );
        add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
            if ( doing_action( 'wp_print_footer_scripts' ) ) {
                self::$files['include_footer'][] = $src;
            }
            if ( doing_action( 'wp_head' ) ) {
                self::$files['include'][] = $src;
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
    }
    private static function update_config_file( $new_data ) {
        $old_config = \W3TC\Config::util_array_from_storage( 0, FALSE );
        error_log( 'MC_Alt_W3TC_Minify::update_config_file():$old_config=' . print_r( $old_config, TRUE ) );
    }
}

MC_Alt_W3TC_Minify::init();
