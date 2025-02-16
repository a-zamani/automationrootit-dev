<?php
/**
 * کلاس مدیریت قالب‌ها
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 09:17:33
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSG_System_Templates {
    public static function get_template($template_name, $args = array()) {
        if ($args && is_array($args)) {
            extract($args);
        }

        $template = locate_template(
            'messaging-system/' . $template_name,
            false
        );

        if (!$template) {
            $template = MSG_SYSTEM_PATH . 'templates/' . $template_name;
        }

        if (file_exists($template)) {
            include $template;
        }
    }

    public static function get_template_part($slug, $name = '') {
        $template = '';

        if ($name) {
            $template = locate_template(array(
                "messaging-system/{$slug}-{$name}.php",
                "messaging-system/{$slug}.php"
            ));
        }

        if (!$template) {
            if ($name) {
                $fallback = MSG_SYSTEM_PATH . "templates/{$slug}-{$name}.php";
                $template = file_exists($fallback) ? $fallback : '';
            }

            if (!$template) {
                $fallback = MSG_SYSTEM_PATH . "templates/{$slug}.php";
                $template = file_exists($fallback) ? $fallback : '';
            }
        }

        if ($template) {
            load_template($template, false);
        }
    }
}