<?php
/*
 * Plugin Name: W3TC Minify Helper V3
 * Description: better JavaScript minification for W3TC's auto minify mode by overloading WP_Scripts::do_item()
 * Version: 3
 */

class AWM_WP_Scripts extends WP_Scripts {
    public function do_item($handle, $group = false) {
        $obj = $this->registered[$handle];
        error_log('AWM_WP_Scripts::do_item():$obj->src=' . $obj->src);
        return parent::do_item($handle, $group);
    }
}

add_action('widgets_init', function() {
    global $wp_scripts;
    error_log('$wp_scripts=' . print_r($wp_scripts, true));
    if (!($wp_scripts instanceof WP_Scripts)) {
        $wp_scripts = new AWM_WP_Scripts();
    }
}, 1);
