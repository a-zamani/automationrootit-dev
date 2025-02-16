<?php
/**
 * کلاس نصب و راه‌اندازی پلاگین
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 08:47:27
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSG_System_Install {
    
    public static function install() {
        // ایجاد جداول مورد نیاز
        self::create_tables();
        
        // ایجاد دایرکتوری آپلود
        self::create_upload_directory();
        
        // تنظیمات پیش‌فرض
        self::add_default_options();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول پیام‌ها
        $table_messages = $wpdb->prefix . 'msg_system_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            recipient_id bigint(20) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'unread',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // جدول پیوست‌ها
        $table_attachments = $wpdb->prefix . 'msg_system_attachments';
        $sql_attachments = "CREATE TABLE IF NOT EXISTS $table_attachments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            message_id bigint(20) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_type varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_messages);
        dbDelta($sql_attachments);
    }
    
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $msg_system_dir = $upload_dir['basedir'] . '/messaging-system';
        
        if (!file_exists($msg_system_dir)) {
            wp_mkdir_p($msg_system_dir);
        }
    }
    
    private static function add_default_options() {
        $default_settings = array(
            'max_file_size' => 5, // مگابایت
            'allowed_extensions' => 'jpg,jpeg,png,pdf,doc,docx',
            'user_roles' => array('administrator'),
            'labels' => array(
                'form_title' => 'ارسال پیام جدید',
                'recipient_group' => 'گروه کاربران',
                'user_selection' => 'انتخاب کاربر',
                'message' => 'پیام',
                'attachments' => 'اسناد پیوستی',
                'submit' => 'ارسال پیام'
            )
        );
        
        foreach ($default_settings as $option_name => $option_value) {
            if (get_option('msg_system_' . $option_name) === false) {
                add_option('msg_system_' . $option_name, $option_value);
            }
        }
    }
}