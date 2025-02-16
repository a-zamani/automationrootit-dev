<?php
/**
 * کلاس مدیریت Post Type ها
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 09:17:33
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSG_System_Post_Types {
    public static function register() {
        register_post_type('msg_system', array(
            'labels' => array(
                'name'               => __('پیام‌ها', 'msg-system'),
                'singular_name'      => __('پیام', 'msg-system'),
                'add_new'           => __('ارسال پیام جدید', 'msg-system'),
                'add_new_item'      => __('ارسال پیام جدید', 'msg-system'),
                'edit_item'         => __('ویرایش پیام', 'msg-system'),
                'view_item'         => __('مشاهده پیام', 'msg-system'),
                'search_items'      => __('جستجوی پیام‌ها', 'msg-system'),
                'not_found'         => __('پیامی یافت نشد', 'msg-system'),
                'not_found_in_trash'=> __('پیامی در سطل زباله یافت نشد', 'msg-system')
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-email-alt',
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'author'),
            'menu_position'       => 30
        ));

        register_taxonomy('message_group', 'msg_system', array(
            'labels' => array(
                'name'              => __('گروه‌های پیام', 'msg-system'),
                'singular_name'     => __('گروه پیام', 'msg-system'),
                'search_items'      => __('جستجوی گروه‌ها', 'msg-system'),
                'all_items'         => __('همه گروه‌ها', 'msg-system'),
                'edit_item'         => __('ویرایش گروه', 'msg-system'),
                'update_item'       => __('به‌روزرسانی گروه', 'msg-system'),
                'add_new_item'      => __('افزودن گروه جدید', 'msg-system'),
                'new_item_name'     => __('نام گروه جدید', 'msg-system'),
                'menu_name'         => __('گروه‌ها', 'msg-system')
            ),
            'hierarchical'        => true,
            'show_ui'             => true,
            'show_admin_column'   => true,
            'query_var'           => true
        ));
    }
}