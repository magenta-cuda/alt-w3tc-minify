<?php
/*
 * Plugin Name: W3TC Minify Helper V3
 * Description: better JavaScript minification for W3TC's auto minify mode
 * Version: 3
 */

add_action('widgets_init', function() {
	global $wp_scripts;
    error_log('$wp_scripts=' . print_r($wp_scripts, true));
}, 1);
