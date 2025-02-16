<?php
/*
Plugin Name: سیستم پیام‌رسانی
Plugin URI: https://jetengine.ir
Description: افزونه پیام‌رسانی داخلی برای وردپرس
Version: 1.0.0
Author: Akam Safir
Author URI: https://jetengine.ir
Text Domain: msg-system
Domain Path: /languages
*/

// جلوگیری از دسترسی مستقیم به فایل
if (!defined('ABSPATH')) {
    exit('دسترسی مستقیم غیرمجاز است!');
}

// تعریف ثابت‌های افزونه
define('MSG_SYSTEM_VERSION', '1.0.0');
define('MSG_SYSTEM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MSG_SYSTEM_PLUGIN_URL', plugin_dir_url(__FILE__));

// لود کردن فایل‌های مورد نیاز
function msg_system_load_files() {
    require_once MSG_SYSTEM_PLUGIN_DIR . 'includes/functions/functions.php';
    require_once MSG_SYSTEM_PLUGIN_DIR . 'includes/functions/post-types.php';
    require_once MSG_SYSTEM_PLUGIN_DIR . 'includes/functions/shortcodes.php';
    require_once MSG_SYSTEM_PLUGIN_DIR . 'includes/admin/admin-settings.php';
}
add_action('plugins_loaded', 'msg_system_load_files');

// افزودن CSS و JS در پنل مدیریت
function msg_system_admin_enqueue_scripts($hook) {
    // فقط در صفحات مربوط به پلاگین لود شود
    if (strpos($hook, 'message-system') !== false) {
        // استایل‌های ادمین
        wp_enqueue_style(
            'msg-system-admin-style',
            MSG_SYSTEM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MSG_SYSTEM_VERSION
        );

        // اسکریپت‌های ادمین
        wp_enqueue_script(
            'msg-system-admin-script',
            MSG_SYSTEM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            MSG_SYSTEM_VERSION,
            true
        );

        // افزودن color picker
        wp_enqueue_style('wp-color-picker');
    }
}
add_action('admin_enqueue_scripts', 'msg_system_admin_enqueue_scripts');

// فعال‌سازی افزونه
function msg_system_activate() {
    // تنظیمات پیش‌فرض فایل‌ها
    add_option('msg_system_max_file_size', 20);
    add_option('msg_system_max_file_count', 3);
    add_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf,doc,docx');
    add_option('msg_system_email_notifications', 1);

    // گروه‌های کاربری پیش‌فرض
    $default_groups = array(
        'admins' => array(
            'name' => __('مدیران', 'msg-system'),
            'description' => __('گروه مدیران سیستم', 'msg-system'),
            'permissions' => array('send', 'receive', 'attach', 'manage')
        ),
        'editors' => array(
            'name' => __('ویراستاران', 'msg-system'),
            'description' => __('گروه ویراستاران سایت', 'msg-system'),
            'permissions' => array('send', 'receive', 'attach')
        ),
        'users' => array(
            'name' => __('کاربران عادی', 'msg-system'),
            'description' => __('گروه کاربران عادی', 'msg-system'),
            'permissions' => array('send', 'receive')
        )
    );
    add_option('msg_system_user_groups', $default_groups);

    // برچسب‌های پیش‌فرض
    $default_labels = array(
        'urgent' => array(
            'text' => __('فوری', 'msg-system'),
            'color' => '#ff0000',
            'icon' => 'dashicons-warning'
        ),
        'important' => array(
            'text' => __('مهم', 'msg-system'),
            'color' => '#ff6600',
            'icon' => 'dashicons-star-filled'
        ),
        'normal' => array(
            'text' => __('عادی', 'msg-system'),
            'color' => '#00aa00',
            'icon' => 'dashicons-email'
        ),
        'low' => array(
            'text' => __('کم اهمیت', 'msg-system'),
            'color' => '#666666',
            'icon' => 'dashicons-minus'
        )
    );
    add_option('msg_system_labels', $default_labels);

    // ایجاد جداول مورد نیاز
    msg_system_create_tables();

    // پاکسازی rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'msg_system_activate');

// ایجاد جداول مورد نیاز
function msg_system_create_tables() {
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
        label varchar(50),
        status varchar(20) DEFAULT 'unread',
        attachment_ids text,
        date_sent datetime DEFAULT CURRENT_TIMESTAMP,
        date_read datetime,
        PRIMARY KEY  (id),
        KEY sender_id (sender_id),
        KEY recipient_id (recipient_id),
        KEY status (status)
    ) $charset_collate;";

    // جدول متای پیام‌ها
    $table_message_meta = $wpdb->prefix . 'msg_system_message_meta';
    $sql_meta = "CREATE TABLE IF NOT EXISTS $table_message_meta (
        meta_id bigint(20) NOT NULL AUTO_INCREMENT,
        message_id bigint(20) NOT NULL,
        meta_key varchar(255),
        meta_value longtext,
        PRIMARY KEY  (meta_id),
        KEY message_id (message_id),
        KEY meta_key (meta_key)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_messages);
    dbDelta($sql_meta);
}

// غیرفعال‌سازی افزونه
function msg_system_deactivate() {
    wp_cache_flush();
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'msg_system_deactivate');

// حذف افزونه
function msg_system_uninstall() {
    // حذف تنظیمات
    delete_option('msg_system_max_file_size');
    delete_option('msg_system_max_file_count');
    delete_option('msg_system_allowed_extensions');
    delete_option('msg_system_email_notifications');
    delete_option('msg_system_user_groups');
    delete_option('msg_system_labels');

    // حذف جداول
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}msg_system_messages");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}msg_system_message_meta");

    // حذف پست‌های مربوط به پیام‌ها
    $messages = get_posts(array(
        'post_type' => 'message',
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    foreach ($messages as $message) {
        wp_delete_post($message->ID, true);
    }

    // پاکسازی کش و قوانین بازنویسی
    wp_cache_flush();
    flush_rewrite_rules();
}
register_uninstall_hook(__FILE__, 'msg_system_uninstall');

// افزودن لینک تنظیمات در صفحه افزونه‌ها
function msg_system_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=message-system') . '">' . __('تنظیمات', 'msg-system') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'msg_system_add_settings_link');

// لود کردن ترجمه‌ها
function msg_system_load_textdomain() {
    load_plugin_textdomain('msg-system', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'msg_system_load_textdomain');

// اضافه کردن نقش‌های سفارشی
function msg_system_add_roles() {
    add_role('message_manager', __('مدیر پیام‌ها', 'msg-system'), array(
        'read' => true,
        'manage_messages' => true,
        'send_messages' => true,
        'delete_messages' => true
    ));
}
add_action('init', 'msg_system_add_roles');

// حذف نقش‌های سفارشی هنگام حذف افزونه
function msg_system_remove_roles() {
    remove_role('message_manager');
}
register_deactivation_hook(__FILE__, 'msg_system_remove_roles');

// اصلاح تابع enqueue_scripts موجود
function msg_system_enqueue_scripts() {
    if (is_user_logged_in()) {
        // اضافه کردن Dashicons
        wp_enqueue_style('dashicons');

        // استایل‌های اصلی
        wp_enqueue_style(
            'msg-system-style', 
            MSG_SYSTEM_PLUGIN_URL . 'assets/css/style.css',
            array(),
            MSG_SYSTEM_VERSION
        );

        // استایل‌های جدید سیستم پیام‌رسانی
        wp_enqueue_style(
            'msg-system-messages',
            MSG_SYSTEM_PLUGIN_URL . 'assets/css/message-system.css',
            array('dashicons'),
            MSG_SYSTEM_VERSION
        );

        // اسکریپت‌های اصلی
        wp_enqueue_script(
            'msg-system-script',
            MSG_SYSTEM_PLUGIN_URL . 'assets/js/script.js',
            array('jquery'),
            MSG_SYSTEM_VERSION,
            true
        );

        // افزودن TinyMCE در صفحات مورد نیاز
        if (is_page() || is_single()) {
            wp_enqueue_editor();
            wp_enqueue_media();
        }

        // متغیرهای مورد نیاز جاوااسکریپت
        wp_localize_script('msg-system-script', 'msgSystemAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('msg_system_nonce'),
            'error_message' => __('خطایی رخ داد. لطفا مجددا تلاش کنید.', 'msg-system'),
            'success_message' => __('عملیات با موفقیت انجام شد.', 'msg-system')
        ));
    }
}
add_action('wp_enqueue_scripts', 'msg_system_enqueue_scripts');