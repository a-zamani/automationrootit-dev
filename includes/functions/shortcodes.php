<?php
/**
 * شورت‌کدهای سیستم پیام‌رسانی
 * 
 * @package Messaging System
 * @author RoOtit-dev
 * @version 1.0.0
 * @since 2025-02-15 14:10:40
 */

if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های پیش‌فرض
if (!defined('MSG_SYSTEM_DEFAULT_PER_PAGE')) {
    define('MSG_SYSTEM_DEFAULT_PER_PAGE', 10);
}

/**
 * اضافه کردن فیلتر برچسب‌ها به کوئری
 */
function msg_system_add_label_filter($query_args) {
    if (isset($_GET['msg_label']) && !empty($_GET['msg_label'])) {
        $label = sanitize_text_field($_GET['msg_label']);
        $query_args['meta_query'][] = array(
            'key' => '_msg_label',
            'value' => $label
        );
    }
    return $query_args;
}
add_filter('msg_system_messages_query_args', 'msg_system_add_label_filter');

/**
 * ثبت اسکریپت‌ها و استایل‌ها
 */
function msg_system_enqueue_frontend_scripts() {
    if (has_shortcode(get_post()->post_content, 'msg_form') || 
        has_shortcode(get_post()->post_content, 'msg_sent') || 
        has_shortcode(get_post()->post_content, 'msg_received')) {
        
        wp_enqueue_style('msg-system-style');
        wp_enqueue_script('msg-system-script');
        
        wp_localize_script('msg-system-script', 'msgSystemAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('msg_system_nonce'),
            'confirmDelete' => __('آیا از حذف این پیام اطمینان دارید؟', 'msg-system'),
            'deleteSuccess' => __('پیام با موفقیت حذف شد.', 'msg-system'),
            'deleteError' => __('خطا در حذف پیام.', 'msg-system')
        ));
    }
}
add_action('wp_enqueue_scripts', 'msg_system_enqueue_scripts');

/**
 * شورت‌کد فرم ارسال پیام
 */
add_shortcode('msg_form', 'msg_system_form_shortcode');
function msg_system_form_shortcode() {
    ob_start();
    
    if (!is_user_logged_in()) {
        return msg_system_show_error(__('برای ارسال پیام باید وارد سایت شوید.', 'msg-system'));
    }

    $current_user = wp_get_current_user();
    $users = get_users(['exclude' => [$current_user->ID]]);
    $labels = get_option('msg_system_labels', array());

    // پردازش فرم ارسال پیام
    if (isset($_POST['msg_system_submit']) && wp_verify_nonce($_POST['msg_system_nonce'], 'msg_system_send_message')) {
        $to = intval($_POST['msg_to']);
        $subject = sanitize_text_field($_POST['msg_subject']);
        $content = wp_kses_post($_POST['msg_content']);
        $label = sanitize_text_field($_POST['msg_label'] ?? '');

        // بررسی محدودیت‌های ارسال پیام
        if (!msg_system_check_sending_limits()) {
            return msg_system_show_error(__('شما به محدودیت ارسال پیام رسیده‌اید.', 'msg-system'));
        }

        // ذخیره پیام در دیتابیس
        $message_id = wp_insert_post(array(
            'post_type' => 'msg_system_message',
            'post_title' => $subject,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ));

        if ($message_id) {
            // ذخیره متادیتا
            update_post_meta($message_id, '_msg_to', $to);
            update_post_meta($message_id, '_msg_label', $label);
            update_post_meta($message_id, '_msg_status', 'unread');
            
            // پردازش فایل‌های پیوست
            if (!empty($_FILES['msg_attachment']['name'][0])) {
                $attachment_ids = msg_system_handle_attachments($message_id);
                if (!empty($attachment_ids)) {
                    update_post_meta($message_id, '_msg_attachments', $attachment_ids);
                }
            }
            
            // ارسال اعلان ایمیل به گیرنده
            msg_system_send_notification_email($to, $message_id);
            
            echo msg_system_show_success(__('پیام با موفقیت ارسال شد.', 'msg-system'));
        }
    }
    
    // نمایش فرم ارسال پیام
    include MSG_SYSTEM_PLUGIN_DIR . 'templates/form.php';
    
    return ob_get_clean();
}

/**
 * شورت‌کد پیام‌های ارسالی
 */
add_shortcode('msg_sent', 'msg_system_sent_messages_shortcode');
function msg_system_sent_messages_shortcode() {
    ob_start();
    
    if (!is_user_logged_in()) {
        return msg_system_show_error(__('برای مشاهده پیام‌های ارسالی باید وارد سایت شوید.', 'msg-system'));
    }

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type' => 'msg_system_message',
        'posts_per_page' => MSG_SYSTEM_DEFAULT_PER_PAGE,
        'paged' => $paged,
        'author' => get_current_user_id(),
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $args = apply_filters('msg_system_messages_query_args', $args);
    $messages = new WP_Query($args);
    
    // نمایش جدول پیام‌های ارسالی
    include MSG_SYSTEM_PLUGIN_DIR . 'templates/sent-messages.php';
    
    return ob_get_clean();
}

/**
 * شورت‌کد پیام‌های دریافتی
 */
add_shortcode('msg_received', 'msg_system_received_messages_shortcode');
function msg_system_received_messages_shortcode() {
    ob_start();
    
    if (!is_user_logged_in()) {
        return msg_system_show_error(__('برای مشاهده پیام‌های دریافتی باید وارد سایت شوید.', 'msg-system'));
    }

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type' => 'msg_system_message',
        'posts_per_page' => MSG_SYSTEM_DEFAULT_PER_PAGE,
        'paged' => $paged,
        'meta_query' => array(
            array(
                'key' => '_msg_to',
                'value' => get_current_user_id()
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $args = apply_filters('msg_system_messages_query_args', $args);
    $messages = new WP_Query($args);
    
    // نمایش جدول پیام‌های دریافتی
    include MSG_SYSTEM_PLUGIN_DIR . 'templates/received-messages.php';
    
    return ob_get_clean();
}

/**
 * Ajax handler برای نمایش محتوای پیام
 */
add_action('wp_ajax_msg_system_get_message', 'msg_system_ajax_get_message');
function msg_system_ajax_get_message() {
    check_ajax_referer('msg_system_nonce', 'nonce');
    
    $message_id = intval($_POST['message_id']);
    $message = get_post($message_id);
    
    if (!$message || $message->post_type !== 'msg_system_message') {
        wp_send_json_error(__('پیام یافت نشد.', 'msg-system'));
    }
    
    // بررسی دسترسی کاربر
    $current_user_id = get_current_user_id();
    $recipient_id = get_post_meta($message_id, '_msg_to', true);
    
    if ($message->post_author !== $current_user_id && $recipient_id !== $current_user_id) {
        wp_send_json_error(__('شما اجازه دسترسی به این پیام را ندارید.', 'msg-system'));
    }
    
    // به‌روزرسانی وضعیت خوانده شدن
    if ($recipient_id === $current_user_id && get_post_meta($message_id, '_msg_status', true) === 'unread') {
        update_post_meta($message_id, '_msg_status', 'read');
    }
    
    ob_start();
    include MSG_SYSTEM_PLUGIN_DIR . 'templates/message-modal.php';
    $content = ob_get_clean();
    
    wp_send_json_success($content);
}

/**
 * Ajax handler برای حذف پیام
 */
add_action('wp_ajax_msg_system_delete_message', 'msg_system_ajax_delete_message');
function msg_system_ajax_delete_message() {
    check_ajax_referer('msg_system_nonce', 'nonce');
    
    $message_id = intval($_POST['message_id']);
    $message = get_post($message_id);
    
    if (!$message || $message->post_type !== 'msg_system_message') {
        wp_send_json_error(__('پیام یافت نشد.', 'msg-system'));
    }
    
    // بررسی دسترسی کاربر
    $current_user_id = get_current_user_id();
    $recipient_id = get_post_meta($message_id, '_msg_to', true);
    
    if ($message->post_author !== $current_user_id && $recipient_id !== $current_user_id) {
        wp_send_json_error(__('شما اجازه حذف این پیام را ندارید.', 'msg-system'));
    }
    
    // حذف پیام
    if (wp_delete_post($message_id, true)) {
        wp_send_json_success(__('پیام با موفقیت حذف شد.', 'msg-system'));
    } else {
        wp_send_json_error(__('خطا در حذف پیام.', 'msg-system'));
    }
}

/**
 * توابع کمکی
 */
function msg_system_check_sending_limits() {
    $current_user_id = get_current_user_id();
    $daily_limit = get_option('msg_system_daily_limit', 50);
    
    // بررسی تعداد پیام‌های ارسال شده در روز جاری
    $args = array(
        'post_type' => 'msg_system_message',
        'author' => $current_user_id,
        'date_query' => array(
            array(
                'after' => '1 day ago'
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    
    $daily_messages = get_posts($args);
    return count($daily_messages) < $daily_limit;
}

function msg_system_handle_attachments($message_id) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $max_files = get_option('msg_system_max_file_count', 3);
    $max_size = get_option('msg_system_max_file_size', 20) * 1024 * 1024;
    $allowed_types = explode(',', get_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf,doc,docx'));
    
    $files = $_FILES['msg_attachment'];
    $attachment_ids = array();
    
    for ($i = 0; $i < min(count($files['name']), $max_files); $i++) {
        if ($files['size'][$i] > $max_size) {
            continue;
        }
        
        $file = array(
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        );
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (!isset($upload['error'])) {
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id = wp_insert_attachment($attachment, $upload['file'], $message_id);
            if (!is_wp_error($attach_id)) {
                wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $upload['file']));
                $attachment_ids[] = $attach_id;
            }
        }
    }
    
    return $attachment_ids;
}

function msg_system_send_notification_email($recipient_id, $message_id) {
    if (!get_option('msg_system_email_notifications', 1)) {
        return;
    }
    
    $recipient = get_userdata($recipient_id);
    if (!$recipient) {
        return;
    }
    
    $message = get_post($message_id);
    $sender = wp_get_current_user();
    
    $subject = sprintf(__('پیام جدید از %s', 'msg-system'), $sender->display_name);
$content = sprintf(
        __("پیام جدیدی از %s دریافت کردید.\n\nموضوع: %s\n\nبرای مشاهده پیام به سایت مراجعه کنید:\n%s", 'msg-system'),
        $sender->display_name,
        $message->post_title,
        get_permalink($message_id)
    );
    
    wp_mail($recipient->user_email, $subject, $content);
}

function msg_system_show_error($message) {
    return sprintf('<div class="msg-system-error">%s</div>', esc_html($message));
}

function msg_system_show_success($message) {
    return sprintf('<div class="msg-system-success">%s</div>', esc_html($message));
}

/**
 * نمایش تعداد پیام‌های خوانده نشده در منو
 */
add_action('admin_bar_menu', 'msg_system_show_unread_count', 999);
function msg_system_show_unread_count($wp_admin_bar) {
    if (!is_user_logged_in()) {
        return;
    }
    
    $unread_count = msg_system_get_unread_count();
    if ($unread_count > 0) {
        $wp_admin_bar->add_node(array(
            'id' => 'msg_system_unread',
            'title' => sprintf(
                __('پیام‌های خوانده نشده: %d', 'msg-system'),
                $unread_count
            ),
            'href' => get_permalink(get_option('msg_system_inbox_page'))
        ));
    }
}

/**
 * دریافت تعداد پیام‌های خوانده نشده
 */
function msg_system_get_unread_count() {
    $args = array(
        'post_type' => 'msg_system_message',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_msg_to',
                'value' => get_current_user_id()
            ),
            array(
                'key' => '_msg_status',
                'value' => 'unread'
            )
        ),
        'fields' => 'ids'
    );
    
    $unread_messages = get_posts($args);
    return count($unread_messages);
}

/**
 * اضافه کردن کلاس به پیام‌های خوانده نشده
 */
function msg_system_message_row_class($message_id) {
    $status = get_post_meta($message_id, '_msg_status', true);
    $classes = array('message-row');
    
    if ($status === 'unread') {
        $classes[] = 'unread';
    }
    
    return implode(' ', $classes);
}

/**
 * دریافت متن وضعیت پیام
 */
function msg_system_get_status_text($status) {
    $statuses = array(
        'unread' => __('خوانده نشده', 'msg-system'),
        'read' => __('خوانده شده', 'msg-system'),
        'replied' => __('پاسخ داده شده', 'msg-system'),
        'deleted' => __('حذف شده', 'msg-system')
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * بررسی امکان حذف پیام
 */
function msg_system_can_delete_message($message_id) {
    $message = get_post($message_id);
    if (!$message) {
        return false;
    }
    
    $current_user_id = get_current_user_id();
    $recipient_id = get_post_meta($message_id, '_msg_to', true);
    
    return $message->post_author === $current_user_id || $recipient_id === $current_user_id;
}

/**
 * اضافه کردن نشانگر پیام‌های جدید به منوی داشبورد
 */
add_action('admin_menu', 'msg_system_add_menu_notification');
function msg_system_add_menu_notification() {
    global $menu;
    
    $unread_count = msg_system_get_unread_count();
    if ($unread_count > 0) {
        foreach ($menu as $key => $value) {
            if ($value[2] === 'msg_system') {
                $menu[$key][0] .= sprintf(
                    ' <span class="update-plugins count-%d"><span class="plugin-count">%d</span></span>',
                    $unread_count,
                    $unread_count
                );
                break;
            }
        }
    }
}

/**
 * فیلتر پیام‌ها بر اساس تاریخ
 */
function msg_system_add_date_filter($query_args) {
    if (isset($_GET['msg_date']) && !empty($_GET['msg_date'])) {
        $date = sanitize_text_field($_GET['msg_date']);
        
        switch ($date) {
            case 'today':
                $query_args['date_query'] = array(
                    array('after' => '1 day ago')
                );
                break;
            case 'week':
                $query_args['date_query'] = array(
                    array('after' => '1 week ago')
                );
                break;
            case 'month':
                $query_args['date_query'] = array(
                    array('after' => '1 month ago')
                );
                break;
        }
    }
    return $query_args;
}
add_filter('msg_system_messages_query_args', 'msg_system_add_date_filter');

/**
 * دریافت آواتار کاربر
 */
function msg_system_get_avatar($user_id, $size = 32) {
    return get_avatar($user_id, $size, '', '', array(
        'class' => 'msg-avatar',
        'extra_attr' => 'title="' . esc_attr(get_userdata($user_id)->display_name) . '"'
    ));
}