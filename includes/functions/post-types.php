<?php
/**
 * تعریف پست تایپ پیام‌ها و تکسونومی‌های مربوطه
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 15:00:12
 */

if (!defined('ABSPATH')) {
    exit;
}

// ثبت پست تایپ پیام‌ها
function msg_system_register_post_type() {
    $labels = array(
        'name'               => __('پیام‌ها', 'msg-system'),
        'singular_name'      => __('پیام', 'msg-system'),
        'menu_name'         => __('پیام‌ها', 'msg-system'),
        'add_new'           => __('ارسال پیام جدید', 'msg-system'),
        'add_new_item'      => __('ارسال پیام جدید', 'msg-system'),
        'edit_item'         => __('ویرایش پیام', 'msg-system'),
        'new_item'          => __('پیام جدید', 'msg-system'),
        'view_item'         => __('مشاهده پیام', 'msg-system'),
        'search_items'      => __('جستجوی پیام‌ها', 'msg-system'),
        'not_found'         => __('پیامی یافت نشد', 'msg-system'),
        'not_found_in_trash'=> __('پیامی در سطل زباله یافت نشد', 'msg-system'),
        'parent_item_colon' => '',
        'all_items'         => __('همه پیام‌ها', 'msg-system'),
    );

    $capabilities = array(
        'edit_post'          => 'edit_message',
        'read_post'          => 'read_message',
        'delete_post'        => 'delete_message',
        'edit_posts'         => 'edit_messages',
        'edit_others_posts'  => 'edit_others_messages',
        'publish_posts'      => 'publish_messages',
        'read_private_posts' => 'read_private_messages',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'dashicons-email-alt',
        'capability_type'    => array('message', 'messages'),
        'capabilities'       => $capabilities,
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => array(
            'title',
            'editor',
            'author',
            'revisions'
        ),
        'has_archive'        => false,
        'rewrite'           => false,
        'show_in_rest'      => false,
        'can_export'        => true,
        'delete_with_user'  => true,
    );

    register_post_type('msg_system_message', $args);

    // اضافه کردن قابلیت‌های پیش‌فرض به نقش‌های مختلف
    $roles = array('administrator', 'message_manager');
    foreach ($roles as $role) {
        $role_obj = get_role($role);
        if ($role_obj) {
            foreach ($capabilities as $cap) {
                $role_obj->add_cap($cap);
            }
        }
    }
}
add_action('init', 'msg_system_register_post_type');

/**
 * ثبت تکسونومی برچسب‌های پیام
 */
function msg_system_register_taxonomies() {
    $labels = array(
        'name'              => __('برچسب‌های پیام', 'msg-system'),
        'singular_name'     => __('برچسب پیام', 'msg-system'),
        'search_items'      => __('جستجوی برچسب‌ها', 'msg-system'),
        'all_items'         => __('همه برچسب‌ها', 'msg-system'),
        'edit_item'         => __('ویرایش برچسب', 'msg-system'),
        'update_item'       => __('بروزرسانی برچسب', 'msg-system'),
        'add_new_item'      => __('افزودن برچسب جدید', 'msg-system'),
        'new_item_name'     => __('نام برچسب جدید', 'msg-system'),
        'menu_name'         => __('برچسب‌ها', 'msg-system'),
    );

    register_taxonomy('msg_label', 'msg_system_message', array(
        'hierarchical'      => false,
        'labels'           => $labels,
        'show_ui'          => true,
        'show_admin_column'=> true,
        'query_var'        => true,
        'rewrite'          => false,
        'show_in_rest'     => false,
    ));
}
add_action('init', 'msg_system_register_taxonomies');

/**
 * اضافه کردن ستون‌های سفارشی به لیست پیام‌ها
 */
function msg_system_add_custom_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'date') {
            $new_columns['sender'] = __('فرستنده', 'msg-system');
            $new_columns['recipient'] = __('گیرنده', 'msg-system');
            $new_columns['status'] = __('وضعیت', 'msg-system');
            $new_columns['priority'] = __('اولویت', 'msg-system');
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}
add_filter('manage_msg_system_message_posts_columns', 'msg_system_add_custom_columns');

/**
 * نمایش محتوای ستون‌های سفارشی
 */
function msg_system_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'sender':
            $sender_id = get_post_meta($post_id, '_msg_sender_id', true);
            $sender = get_userdata($sender_id);
            echo $sender ? esc_html($sender->display_name) : __('نامشخص', 'msg-system');
            break;
            
        case 'recipient':
            $recipient_id = get_post_meta($post_id, '_msg_recipient_id', true);
            $recipient = get_userdata($recipient_id);
            echo $recipient ? esc_html($recipient->display_name) : __('نامشخص', 'msg-system');
            break;
            
        case 'status':
            $status = get_post_meta($post_id, '_msg_status', true);
            $statuses = array(
                'unread' => __('خوانده نشده', 'msg-system'),
                'read' => __('خوانده شده', 'msg-system'),
                'replied' => __('پاسخ داده شده', 'msg-system'),
            );
            echo isset($statuses[$status]) ? $statuses[$status] : __('نامشخص', 'msg-system');
            break;

        case 'priority':
            $priority = get_post_meta($post_id, '_msg_priority', true);
            $priorities = array(
                'low' => __('کم', 'msg-system'),
                'normal' => __('معمولی', 'msg-system'),
                'high' => __('مهم', 'msg-system'),
                'urgent' => __('فوری', 'msg-system'),
            );
            echo isset($priorities[$priority]) ? $priorities[$priority] : __('معمولی', 'msg-system');
            break;
    }
}
add_action('manage_msg_system_message_posts_custom_column', 'msg_system_custom_column_content', 10, 2);

/**
 * اضافه کردن متاباکس اطلاعات پیام
 */
function msg_system_add_meta_boxes() {
    add_meta_box(
        'msg_system_details',
        __('جزئیات پیام', 'msg-system'),
        'msg_system_details_meta_box',
        'msg_system_message',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'msg_system_add_meta_boxes');

/**
 * نمایش محتوای متاباکس جزئیات پیام
 */
function msg_system_details_meta_box($post) {
    // دریافت مقادیر ذخیره شده
    $recipient_id = get_post_meta($post->ID, '_msg_recipient_id', true);
    $status = get_post_meta($post->ID, '_msg_status', true);
    $priority = get_post_meta($post->ID, '_msg_priority', true);
    
    // ایجاد nonce برای امنیت
    wp_nonce_field('msg_system_save_details', 'msg_system_details_nonce');
    
    // نمایش فرم
    ?>
    <div class="msg-details-form">
        <p>
            <label for="msg_recipient"><?php _e('گیرنده:', 'msg-system'); ?></label>
            <?php
            wp_dropdown_users(array(
                'name' => 'msg_recipient_id',
                'selected' => $recipient_id,
                'show_option_none' => __('انتخاب کنید', 'msg-system'),
                'class' => 'widefat'
            ));
            ?>
        </p>
        <p>
            <label for="msg_status"><?php _e('وضعیت:', 'msg-system'); ?></label>
            <select name="msg_status" id="msg_status" class="widefat">
                <option value="unread" <?php selected($status, 'unread'); ?>><?php _e('خوانده نشده', 'msg-system'); ?></option>
                <option value="read" <?php selected($status, 'read'); ?>><?php _e('خوانده شده', 'msg-system'); ?></option>
                <option value="replied" <?php selected($status, 'replied'); ?>><?php _e('پاسخ داده شده', 'msg-system'); ?></option>
            </select>
        </p>
        <p>
            <label for="msg_priority"><?php _e('اولویت:', 'msg-system'); ?></label>
            <select name="msg_priority" id="msg_priority" class="widefat">
                <option value="low" <?php selected($priority, 'low'); ?>><?php _e('کم', 'msg-system'); ?></option>
                <option value="normal" <?php selected($priority, 'normal'); ?>><?php _e('معمولی', 'msg-system'); ?></option>
                <option value="high" <?php selected($priority, 'high'); ?>><?php _e('مهم', 'msg-system'); ?></option>
                <option value="urgent" <?php selected($priority, 'urgent'); ?>><?php _e('فوری', 'msg-system'); ?></option>
            </select>
        </p>
    </div>
    <?php
}

/**
 * ذخیره اطلاعات متاباکس
 */
function msg_system_save_details($post_id) {
    // بررسی nonce
    if (!isset($_POST['msg_system_details_nonce']) || 
        !wp_verify_nonce($_POST['msg_system_details_nonce'], 'msg_system_save_details')) {
        return;
    }

    // بررسی دسترسی
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // ذخیره مقادیر
    if (isset($_POST['msg_recipient_id'])) {
        update_post_meta($post_id, '_msg_recipient_id', sanitize_text_field($_POST['msg_recipient_id']));
    }
    
    if (isset($_POST['msg_status'])) {
        update_post_meta($post_id, '_msg_status', sanitize_text_field($_POST['msg_status']));
    }
    
    if (isset($_POST['msg_priority'])) {
        update_post_meta($post_id, '_msg_priority', sanitize_text_field($_POST['msg_priority']));
    }
}
add_action('save_post_msg_system_message', 'msg_system_save_details');

/**
 * اضافه کردن قابلیت مرتب‌سازی به ستون‌های سفارشی
 */
function msg_system_sortable_columns($columns) {
    $columns['sender'] = 'sender';
    $columns['recipient'] = 'recipient';
    $columns['status'] = 'status';
    $columns['priority'] = 'priority';
    return $columns;
}
add_filter('manage_edit-msg_system_message_sortable_columns', 'msg_system_sortable_columns');

/**
 * تنظیم کوئری برای مرتب‌سازی
 */
function msg_system_sort_columns($query) {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'msg_system_message') {
        return;
    }

    $orderby = $query->get('orderby');

    switch($orderby) {
        case 'sender':
            $query->set('meta_key', '_msg_sender_id');
            $query->set('orderby', 'meta_value_num');
            break;
            
        case 'recipient':
            $query->set('meta_key', '_msg_recipient_id');
            $query->set('orderby', 'meta_value_num');
            break;
            
        case 'status':
            $query->set('meta_key', '_msg_status');
            $query->set('orderby', 'meta_value');
            break;

        case 'priority':
            $query->set('meta_key', '_msg_priority');
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'msg_system_sort_columns');
/**
 * اضافه کردن فیلتر برای وضعیت و اولویت پیام‌ها
 */
function msg_system_add_filters() {
    global $typenow;
    if ($typenow === 'msg_system_message') {
        // فیلتر وضعیت
        $current_status = isset($_GET['msg_status']) ? $_GET['msg_status'] : '';
        $statuses = array(
            'unread' => __('خوانده نشده', 'msg-system'),
            'read' => __('خوانده شده', 'msg-system'),
            'replied' => __('پاسخ داده شده', 'msg-system')
        );

        echo '<select name="msg_status">';
        echo '<option value="">' . __('همه وضعیت‌ها', 'msg-system') . '</option>';
        foreach ($statuses as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current_status, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        // فیلتر اولویت
        $current_priority = isset($_GET['msg_priority']) ? $_GET['msg_priority'] : '';
        $priorities = array(
            'low' => __('کم', 'msg-system'),
            'normal' => __('معمولی', 'msg-system'),
            'high' => __('مهم', 'msg-system'),
            'urgent' => __('فوری', 'msg-system')
        );

        echo '<select name="msg_priority">';
        echo '<option value="">' . __('همه اولویت‌ها', 'msg-system') . '</option>';
        foreach ($priorities as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current_priority, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';

        // فیلتر فرستنده
        $current_sender = isset($_GET['msg_sender']) ? $_GET['msg_sender'] : '';
        wp_dropdown_users(array(
            'name' => 'msg_sender',
            'selected' => $current_sender,
            'show_option_none' => __('همه فرستنده‌ها', 'msg-system'),
            'class' => 'postform'
        ));

        // فیلتر گیرنده
        $current_recipient = isset($_GET['msg_recipient']) ? $_GET['msg_recipient'] : '';
        wp_dropdown_users(array(
            'name' => 'msg_recipient',
            'selected' => $current_recipient,
            'show_option_none' => __('همه گیرنده‌ها', 'msg-system'),
            'class' => 'postform'
        ));
    }
}
add_action('restrict_manage_posts', 'msg_system_add_filters');

/**
 * اعمال فیلترها در کوئری
 */
function msg_system_filter_messages($query) {
    global $pagenow, $typenow;
    
    if (is_admin() && $pagenow === 'edit.php' && $typenow === 'msg_system_message') {
        $meta_query = array();
        
        // فیلتر وضعیت
        if (!empty($_GET['msg_status'])) {
            $meta_query[] = array(
                'key' => '_msg_status',
                'value' => sanitize_text_field($_GET['msg_status']),
                'compare' => '='
            );
        }
        
        // فیلتر اولویت
        if (!empty($_GET['msg_priority'])) {
            $meta_query[] = array(
                'key' => '_msg_priority',
                'value' => sanitize_text_field($_GET['msg_priority']),
                'compare' => '='
            );
        }
        
        // فیلتر فرستنده
        if (!empty($_GET['msg_sender'])) {
            $meta_query[] = array(
                'key' => '_msg_sender_id',
                'value' => absint($_GET['msg_sender']),
                'compare' => '='
            );
        }
        
        // فیلتر گیرنده
        if (!empty($_GET['msg_recipient'])) {
            $meta_query[] = array(
                'key' => '_msg_recipient_id',
                'value' => absint($_GET['msg_recipient']),
                'compare' => '='
            );
        }
        
        // اعمال meta query اگر فیلتری انتخاب شده باشد
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }
}
add_action('pre_get_posts', 'msg_system_filter_messages');

/**
 * افزودن CSS سفارشی برای فیلترها
 */
function msg_system_admin_head() {
    global $typenow;
    if ($typenow === 'msg_system_message') {
        ?>
        <style>
            .tablenav select[name^="msg_"] {
                float: left;
                margin: 0 5px 0 0;
                max-width: 200px;
            }
            @media screen and (max-width: 782px) {
                .tablenav select[name^="msg_"] {
                    display: block;
                    margin-bottom: 5px;
                    width: 100%;
                    max-width: none;
                }
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'msg_system_admin_head');