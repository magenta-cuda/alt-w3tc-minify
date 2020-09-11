<?php
/*
 * Plugin Name: W3TC Minify Helper V3
 * Description: better JavaScript minification for W3TC's auto minify mode by overloading WP_Scripts::do_item()
 * Version: 3
 * Author: Magenta Cuda
 *
 * Copyright (c) 2020 Magenta Cuda
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

# Version 2 has a dangerous dependency on the actual WordPress implementation of WP_Scripts::do_item()
# Overloading this method is one way of removing this dependency.
# Moreover, subclassing class WP_Scripts brings the possibility of totally controlling how WordPress emits scripts.

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
