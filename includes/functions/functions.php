/**
 * بررسی دسترسی کاربر برای عملیات خاص
 */
function msg_system_user_can($permission, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    // دسترسی کامل برای مدیران
    if (user_can($user_id, 'administrator')) {
        return true;
    }
    
    $groups = msg_system_get_groups();
    foreach ($groups as $group => $data) {
        if (user_can($user_id, $group) && in_array($permission, $data['permissions'])) {
            return true;
        }
    }
    
    return false;
}

/**
 * ارسال پیام جدید
 */
function msg_system_send_message($args = array()) {
    $defaults = array(
        'to' => '',
        'subject' => '',
        'content' => '',
        'label' => '',
        'attachments' => array()
    );
    
    $args = wp_parse_args($args, $defaults);
    
    if (empty($args['to']) || empty($args['subject']) || empty($args['content'])) {
        return new WP_Error('invalid_data', __('اطلاعات پیام ناقص است.', 'msg-system'));
    }
    
    // بررسی محدودیت‌ها
    if (!msg_system_check_limits()) {
        return new WP_Error('limit_exceeded', __('محدودیت ارسال پیام', 'msg-system'));
    }
    
    // ایجاد پیام
    $message_id = wp_insert_post(array(
        'post_type' => 'msg_system_message',
        'post_title' => $args['subject'],
        'post_content' => $args['content'],
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    ));
    
    if (is_wp_error($message_id)) {
        return $message_id;
    }
    
    // ذخیره متادیتا
    update_post_meta($message_id, '_msg_to', $args['to']);
    update_post_meta($message_id, '_msg_status', 'unread');
    
    if (!empty($args['label'])) {
        update_post_meta($message_id, '_msg_label', $args['label']);
    }
    
    // ذخیره پیوست‌ها
    if (!empty($args['attachments'])) {
        update_post_meta($message_id, '_msg_attachments', $args['attachments']);
    }
    
    // ارسال اعلان
    do_action('msg_system_message_sent', $message_id, $args);
    
    return $message_id;
}

/**
 * حذف پیام
 */
function msg_system_delete_message($message_id) {
    $message = get_post($message_id);
    
    if (!$message || $message->post_type !== 'msg_system_message') {
        return new WP_Error('invalid_message', __('پیام نامعتبر است.', 'msg-system'));
    }
    
    if (!msg_system_can_delete_message($message_id)) {
        return new WP_Error('permission_denied', __('شما اجازه حذف این پیام را ندارید.', 'msg-system'));
    }
    
    // حذف پیوست‌ها
    $attachments = get_post_meta($message_id, '_msg_attachments', true);
    if (!empty($attachments)) {
        foreach ($attachments as $attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
    }
    
    // حذف پیام
    $result = wp_delete_post($message_id, true);
    
    if (!$result) {
        return new WP_Error('delete_failed', __('حذف پیام با خطا مواجه شد.', 'msg-system'));
    }
    
    do_action('msg_system_message_deleted', $message_id);
    
    return true;
}

/**
 * علامت‌گذاری پیام به عنوان خوانده شده
 */
function msg_system_mark_as_read($message_id) {
    $message = get_post($message_id);
    
    if (!$message || $message->post_type !== 'msg_system_message') {
        return false;
    }
    
    $recipient_id = get_post_meta($message_id, '_msg_to', true);
    
    if ($recipient_id !== get_current_user_id()) {
        return false;
    }
    
    $current_status = get_post_meta($message_id, '_msg_status', true);
    
    if ($current_status === 'unread') {
        update_post_meta($message_id, '_msg_status', 'read');
        do_action('msg_system_message_read', $message_id);
        return true;
    }
    
    return false;
}

/**
 * دریافت تعداد پیام‌های خوانده نشده کاربر
 */
function msg_system_get_unread_count($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    $args = array(
        'post_type' => 'msg_system_message',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_msg_to',
                'value' => $user_id
            ),
            array(
                'key' => '_msg_status',
                'value' => 'unread'
            )
        ),
        'fields' => 'ids'
    );
    
    $query = new WP_Query($args);
    return $query->post_count;
}

/**
 * آپلود فایل پیوست
 */
function msg_system_handle_attachment($file) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $upload = wp_handle_upload(
        $file,
        array('test_form' => false)
    );
    
    if (isset($upload['error'])) {
        return new WP_Error('upload_error', $upload['error']);
    }
    
    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', basename($upload['file'])),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    
    if (is_wp_error($attach_id)) {
        return $attach_id;
    }
    
    wp_update_attachment_metadata(
        $attach_id,
        wp_generate_attachment_metadata($attach_id, $upload['file'])
    );
    
    return $attach_id;
}

/**
 * دریافت تاریخ به فرمت فارسی
 */
function msg_system_get_date($timestamp) {
    $now = current_time('timestamp');
    $diff = $now - $timestamp;
    
    if ($diff < HOUR_IN_SECONDS) {
        $mins = round($diff / MINUTE_IN_SECONDS);
        return sprintf(_n('%d دقیقه پیش', '%d دقیقه پیش', $mins, 'msg-system'), $mins);
    } elseif ($diff < DAY_IN_SECONDS) {
        $hours = round($diff / HOUR_IN_SECONDS);
        return sprintf(_n('%d ساعت پیش', '%d ساعت پیش', $hours, 'msg-system'), $hours);
    } elseif ($diff < WEEK_IN_SECONDS) {
        $days = round($diff / DAY_IN_SECONDS);
        return sprintf(_n('%d روز پیش', '%d روز پیش', $days, 'msg-system'), $days);
    } else {
        return date_i18n(get_option('date_format'), $timestamp);
    }
}

/**
 * بررسی محدودیت حجم فایل
 */
function msg_system_check_file_size($file) {
    $max_size = get_option('msg_system_max_file_size', 20) * 1024 * 1024; // تبدیل به بایت
    return $file['size'] <= $max_size;
}

/**
 * بررسی پسوند مجاز فایل
 */
function msg_system_check_file_type($file) {
    $allowed_types = explode(',', get_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf,doc,docx'));
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($file_ext, $allowed_types);
}

/**
 * دریافت آمار کلی سیستم پیام‌رسانی
 */
function msg_system_get_stats() {
    global $wpdb;
    
    $stats = array(
        'total_messages' => 0,
        'total_users' => 0,
        'unread_messages' => 0,
        'attachments' => 0
    );
    
    // تعداد کل پیام‌ها
    $stats['total_messages'] = wp_count_posts('msg_system_message')->publish;
    
    // تعداد کاربران فعال
    $stats['total_users'] = msg_system_get_active_users_count();
    
    // تعداد پیام‌های خوانده نشده
    $args = array(
        'post_type' => 'msg_system_message',
        'meta_key' => '_msg_status',
        'meta_value' => 'unread',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $unread_query = new WP_Query($args);
    $stats['unread_messages'] = $unread_query->post_count;
    
    // تعداد پیوست‌ها
    $args = array(
        'post_type' => 'msg_system_message',
        'meta_key' => '_msg_attachments',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $attachment_query = new WP_Query($args);
    $stats['attachments'] = $attachment_query->post_count;
    
    return $stats;
}
