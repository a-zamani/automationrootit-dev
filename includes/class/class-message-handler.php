<?php
/**
 * کلاس اصلی مدیریت پیام ها
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @version 1.0.0
 * @since 2025-02-13
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSG_System_Handler {
    /**
     * @var نمونه یکتای کلاس
     */
    private static $instance = null;

    /**
     * دریافت نمونه یکتا از کلاس
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * راه اندازی هوک ها
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('wp_ajax_msg_system_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_msg_system_delete_message', array($this, 'handle_delete_message'));
        add_action('wp_ajax_msg_system_mark_as_read', array($this, 'handle_mark_as_read'));
    }

    /**
     * ثبت پست تایپ پیام ها
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'پیام ها',
            'singular_name' => 'پیام',
            'add_new' => 'افزودن پیام جدید',
            'add_new_item' => 'افزودن پیام جدید',
            'edit_item' => 'ویرایش پیام',
            'view_item' => 'مشاهده پیام',
            'search_items' => 'جستجوی پیام ها',
            'not_found' => 'پیامی یافت نشد',
            'not_found_in_trash' => 'پیامی در سطل زباله یافت نشد'
        );

        register_post_type('msg_system', array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title', 'editor'),
            'has_archive' => false
        ));
    }

    /**
     * ارسال پیام جدید
     */
    public function send_message($data) {
        // بررسی داده های ورودی
        if (empty($data['recipient_id']) || empty($data['message'])) {
            return new WP_Error('invalid_data', 'داده های ورودی ناقص است.');
        }

        // ایجاد پست جدید
        $post_data = array(
            'post_title' => sprintf('پیام به %s - %s', 
                get_userdata($data['recipient_id'])->display_name,
                current_time('Y-m-d H:i:s')
            ),
            'post_content' => wp_kses_post($data['message']),
            'post_type' => 'msg_system',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // ذخیره متادیتا
        update_post_meta($post_id, '_msg_recipient_id', $data['recipient_id']);
        update_post_meta($post_id, '_msg_read_status', 0);
        
        if (!empty($data['group'])) {
            update_post_meta($post_id, '_msg_group', sanitize_text_field($data['group']));
        }

        // ذخیره پیوست ها
        if (!empty($data['attachments'])) {
            $this->handle_attachments($post_id, $data['attachments']);
        }

        do_action('msg_system_after_send_message', $post_id, $data);

        return $post_id;
    }

    /**
     * مدیریت پیوست ها
     */
    private function handle_attachments($post_id, $files) {
        $attachments = array();
        $max_size = get_option('msg_system_max_file_size', 20) * 1024 * 1024; // تبدیل به بایت
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf');

        foreach ($files as $file) {
            // بررسی نوع و حجم فایل
            $file_type = wp_check_filetype($file['name']);
            if (!in_array(strtolower($file_type['ext']), $allowed_types)) {
                continue;
            }

            if ($file['size'] > $max_size) {
                continue;
            }

            // آپلود فایل
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (!isset($upload['error'])) {
                $attachments[] = array(
                    'url' => $upload['url'],
                    'file' => $upload['file'],
                    'type' => $file_type['type'],
                    'name' => sanitize_file_name($file['name'])
                );
            }
        }

        if (!empty($attachments)) {
            update_post_meta($post_id, '_msg_attachments', $attachments);
        }

        return $attachments;
    }

    /**
     * دریافت پیام های کاربر
     */
    public function get_user_messages($user_id = null, $type = 'received', $args = array()) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $default_args = array(
            'post_type' => 'msg_system',
            'posts_per_page' => 10,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $default_args);

        // افزودن متا کوئری
        if ($type === 'received') {
            $args['meta_query'] = array(
                array(
                    'key' => '_msg_recipient_id',
                    'value' => $user_id
                )
            );
        } else {
            $args['author'] = $user_id;
        }

        return new WP_Query($args);
    }

    /**
     * علامت گذاری پیام به عنوان خوانده شده
     */
    public function mark_as_read($post_id) {
        $recipient_id = get_post_meta($post_id, '_msg_recipient_id', true);
        
        if ($recipient_id != get_current_user_id()) {
            return false;
        }

        return update_post_meta($post_id, '_msg_read_status', 1);
    }

    /**
     * حذف پیام
     */
    public function delete_message($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'msg_system') {
            return false;
        }

        // بررسی دسترسی
        $recipient_id = get_post_meta($post_id, '_msg_recipient_id', true);
        if ($post->post_author != get_current_user_id() && $recipient_id != get_current_user_id()) {
            return false;
        }

        // حذف پیوست ها
        $attachments = get_post_meta($post_id, '_msg_attachments', true);
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['file']) && file_exists($attachment['file'])) {
                    unlink($attachment['file']);
                }
            }
        }

        return wp_delete_post($post_id, true);
    }

    /**
     * هندلر AJAX برای ارسال پیام
     */
    public function handle_send_message() {
        check_ajax_referer('msg_system_nonce', 'nonce');

        $response = $this->send_message($_POST);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => 'پیام با موفقیت ارسال شد.',
                'post_id' => $response
            ));
        }
    }

    /**
     * هندلر AJAX برای حذف پیام
     */
    public function handle_delete_message() {
        check_ajax_referer('msg_system_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        
        if ($this->delete_message($post_id)) {
            wp_send_json_success('پیام با موفقیت حذف شد.');
        } else {
            wp_send_json_error('خطا در حذف پیام.');
        }
    }

    /**
     * هندلر AJAX برای علامت گذاری پیام
     */
    public function handle_mark_as_read() {
        check_ajax_referer('msg_system_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        
        if ($this->mark_as_read($post_id)) {
            wp_send_json_success('پیام به عنوان خوانده شده علامت گذاری شد.');
        } else {
            wp_send_json_error('خطا در علامت گذاری پیام.');
        }
    }
}

// راه اندازی نمونه کلاس
function MSG_System() {
    return MSG_System_Handler::get_instance();
}

// راه اندازی سیستم
MSG_System();
