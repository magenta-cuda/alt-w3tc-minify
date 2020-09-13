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
        # error_log('AWM_WP_Scripts::do_item():$obj->src=' . $obj->src);
        ob_start(function($buffer) {
            return preg_replace('#</script>.*?<!\[endif\]-->#ms', "</script>\n<![endif]--><!-- canonicalized by W3TC minify helper v3 -->", $buffer);
        });
        $return = parent::do_item($handle, $group);
        ob_end_flush();
        return $return;
    }
}

class AWM_Init {
    const TRANSIENT_NAME = 'mc_alt_w3tc_minify_v3';

    public static function init() {
        add_action('widgets_init', function() {
            global $wp_scripts;
            # error_log('$wp_scripts=' . print_r($wp_scripts, true));
            # In order to overload WP_Scripts this code must execute before the first call to function wp_scripts() in file
            # ".../wp-includes/functions.wp-scripts.php". Currently (WordPress 5.5.1) the action 'widgets_init' with priority 1
            # is a good way to do this.
            if (!($wp_scripts instanceof WP_Scripts)) {
                $wp_scripts = new AWM_WP_Scripts();
            } else {
                $notice = 'Plugin W3TC Minify Helper V3 failed to create AWM_WP_Scripts as the global $wp_scripts.';
                error_log($notice);
                set_transient(self::TRANSIENT_NAME, $notice);
            }
        }, 1);
        add_action('admin_init', function() {
            if (is_admin() && !wp_doing_ajax() && ($notice = get_transient(self::TRANSIENT_NAME))) {
                add_action('admin_notices', function() use ($notice) {
                    ?>
<div class="notice notice-error is-dismissible"><?php echo $notice; ?></div>
                    <?php
                });
                delete_transient(self::TRANSIENT_NAME);
            }
        });
    }
}

AWM_Init::init();
