<?php
/*
Plugin Name: Alternative W3TC Minify Configuration Script
*/

class MC_Alt_W3TC_Minify {
    private static $theme;
    private static $basename;
    private static $files = [ 'include' => [], 'include_footer' => [] ];
    public static function init() {
        add_filter( 'template_include', function( $template ) {
            error_log( 'FILTER::template_include():$template=' . $template );
            error_log( 'FILTER::template_include():get_theme_root()=' . get_theme_root() );
            error_log( 'FILTER::template_include():get_template()=' . get_template() );
            error_log( 'FILTER::template_include():get_stylesheet()=' . get_stylesheet() );
			self::$theme    = \W3TC\Util_Theme::get_theme_key( get_theme_root(), get_template(), get_stylesheet() );
			self::$basename = basename( $template, '.php' );
        } );
        add_filter( 'script_loader_tag', function( $tag, $handle, $src ) {
            if ( doing_action( 'wp_print_footer_scripts' ) ) {
                error_log( 'FILTER::script_loader_tag():ACTION::wp_print_footer_scripts' );
                self::$files['include_footer'][] = $src;
            }
            if ( doing_action( 'wp_head' ) ) {
                error_log( 'FILTER::script_loader_tag():ACTION::wp_head' );
                self::$files['include'][] = $src;
            }
            error_log( 'FILTER::script_loader_tag():$tag=' . $tag );
            error_log( 'FILTER::script_loader_tag():$handle=' . $handle );
            error_log( 'FILTER::script_loader_tag():$src=' . $src );
        }, 10, 3 );
        add_action( 'shutdown', function() {
            if ( self::$theme ) {
                error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::$theme=' . print_r( self::$theme, true ) );
                error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::$basename=' . print_r( self::$basename, true ) );
                error_log( 'ACTION::shutdown():MC_Alt_W3TC_Minify::$files=' . print_r( self::$files, true ) );
            }
        } );
    }
}

MC_Alt_W3TC_Minify::init();
