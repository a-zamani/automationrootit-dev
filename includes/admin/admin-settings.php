<?php
/**
 * تنظیمات سیستم پیام‌رسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-16 06:08:20
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * نمایش صفحه تنظیمات اصلی
 */
function msg_system_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'msg-system'));
    }
    ?>
    <div class="msg-system-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php msg_system_settings_tabs('settings'); ?>

        <form method="post" action="options.php" class="msg-system-form">
            <?php
            settings_fields('msg_system_settings');
            wp_nonce_field('msg_system_settings_nonce', 'msg_system_settings_nonce');
            do_settings_sections('message-system');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * نمایش صفحه گروه‌های کاربری
 */
function msg_system_groups_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'msg-system'));
    }

    $groups = get_option('msg_system_user_groups', array());
    ?>
    <div class="msg-system-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php msg_system_settings_tabs('groups'); ?>

        <form method="post" action="options.php" class="msg-system-form">
            <?php
            settings_fields('msg_system_groups');
            wp_nonce_field('msg_system_groups_nonce', 'msg_system_groups_nonce');
            submit_button();
            ?>
            <table class="wp-list-table widefat fixed messages-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('نام گروه', 'msg-system'); ?></th>
                        <th><?php esc_html_e('توضیحات', 'msg-system'); ?></th>
                        <th><?php esc_html_e('دسترسی‌ها', 'msg-system'); ?></th>
                        <th><?php esc_html_e('عملیات', 'msg-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($groups)) :
                        foreach ($groups as $key => $group) : 
                    ?>
                        <tr>
                            <td>
                                <input type="text" 
                                       name="msg_system_user_groups[<?php echo esc_attr($key); ?>][name]" 
                                       value="<?php echo esc_attr($group['name']); ?>" 
                                       class="regular-text">
                            </td>
                            <td>
                                <textarea name="msg_system_user_groups[<?php echo esc_attr($key); ?>][description]" 
                                          rows="2" 
                                          class="large-text"><?php echo esc_textarea($group['description']); ?></textarea>
                            </td>
                            <td>
                                <?php
                                $permissions = array(
                                    'send' => __('ارسال پیام', 'msg-system'),
                                    'receive' => __('دریافت پیام', 'msg-system'),
                                    'attach' => __('پیوست فایل', 'msg-system'),
                                    'manage' => __('مدیریت', 'msg-system')
                                );
                                
                                foreach ($permissions as $permission => $label) : 
                                ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="msg_system_user_groups[<?php echo esc_attr($key); ?>][permissions][]" 
                                               value="<?php echo esc_attr($permission); ?>" 
                                               <?php checked(in_array($permission, isset($group['permissions']) ? $group['permissions'] : array())); ?>>
                                        <?php echo esc_html($label); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="button delete-group" 
                                        data-group="<?php echo esc_attr($key); ?>">
                                    <?php esc_html_e('حذف', 'msg-system'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else :
                    ?>
                        <tr>
                            <td colspan="4">
                                <?php esc_html_e('هیچ گروهی تعریف نشده است.', 'msg-system'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <button type="button" class="button add-group">
                                <?php esc_html_e('افزودن گروه جدید', 'msg-system'); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
    <?php
}

/**
 * نمایش صفحه برچسب‌ها
 */
function msg_system_labels_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'msg-system'));
    }

    $labels = get_option('msg_system_labels', array());
    ?>
    <div class="msg-system-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php msg_system_settings_tabs('labels'); ?>

        <form method="post" action="options.php" class="msg-system-form">
            <?php
            settings_fields('msg_system_labels');
            wp_nonce_field('msg_system_labels_nonce', 'msg_system_labels_nonce');
            submit_button();
            ?>
            <table class="wp-list-table widefat fixed messages-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('عنوان', 'msg-system'); ?></th>
                        <th><?php esc_html_e('رنگ', 'msg-system'); ?></th>
                        <th><?php esc_html_e('آیکون', 'msg-system'); ?></th>
                        <th><?php esc_html_e('عملیات', 'msg-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($labels)) :
                        foreach ($labels as $key => $label) : 
                    ?>
                        <tr>
                            <td>
                                <input type="text" 
                                       name="msg_system_labels[<?php echo esc_attr($key); ?>][text]" 
                                       value="<?php echo esc_attr($label['text']); ?>" 
                                       class="regular-text">
                            </td>
                            <td>
                                <input type="color" 
                                       name="msg_system_labels[<?php echo esc_attr($key); ?>][color]" 
                                       value="<?php echo esc_attr($label['color']); ?>">
                                <span class="msg-label-color" style="background-color: <?php echo esc_attr($label['color']); ?>"></span>
                            </td>
                            <td>
                                <select name="msg_system_labels[<?php echo esc_attr($key); ?>][icon]">
                                    <?php
                                    $icons = array(
                                        'dashicons-warning' => __('هشدار', 'msg-system'),
                                        'dashicons-star-filled' => __('ستاره', 'msg-system'),
                                        'dashicons-email' => __('ایمیل', 'msg-system'),
                                        'dashicons-flag' => __('پرچم', 'msg-system'),
                                        'dashicons-awards' => __('مدال', 'msg-system')
                                    );
                                    
                                    foreach ($icons as $icon => $label) {
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($icon),
                                            selected($label['icon'], $icon, false),
                                            esc_html($label)
                                        );
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" 
                                        class="button delete-label" 
                                        data-label="<?php echo esc_attr($key); ?>">
                                    <?php esc_html_e('حذف', 'msg-system'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    else :
                    ?>
                        <tr>
                            <td colspan="4">
                                <?php esc_html_e('هیچ برچسبی تعریف نشده است.', 'msg-system'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">
                            <button type="button" class="button add-label">
                                <?php esc_html_e('افزودن برچسب جدید', 'msg-system'); ?>
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
    <?php
}

/**
 * نمایش تب‌های تنظیمات
 */
function msg_system_settings_tabs($current = 'settings') {
    $tabs = array(
        'settings' => __('تنظیمات عمومی', 'msg-system'),
        'groups' => __('گروه‌های کاربری', 'msg-system'),
        'labels' => __('برچسب‌ها', 'msg-system')
    );
    ?>
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab => $name) : ?>
            <a href="?page=message-system<?php echo $tab !== 'settings' ? '-' . $tab : ''; ?>" 
               class="nav-tab <?php echo $current === $tab ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($name); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    <?php
}

/**
 * کال‌بک‌های فیلدها
 */
function msg_system_general_settings_section_callback() {
    echo '<p class="description">' . 
         esc_html__('تنظیمات عمومی سیستم پیام‌رسانی را در این بخش تعیین کنید.', 'msg-system') . 
         '</p>';
}

function msg_system_max_file_size_field_callback() {
    $value = get_option('msg_system_max_file_size', 20);
    ?>
    <input type="number" 
           id="msg_system_max_file_size" 
           name="msg_system_max_file_size" 
           value="<?php echo esc_attr($value); ?>" 
           min="1" 
           max="100" 
           class="small-text">
    <p class="description">
        <?php esc_html_e('حداکثر حجم مجاز برای هر فایل پیوست به مگابایت', 'msg-system'); ?>
    </p>
    <?php
}

function msg_system_max_file_count_field_callback() {
    $value = get_option('msg_system_max_file_count', 3);
    ?>
    <input type="number" 
           id="msg_system_max_file_count" 
           name="msg_system_max_file_count" 
           value="<?php echo esc_attr($value); ?>" 
           min="1" 
           max="10" 
           class="small-text">
    <p class="description">
        <?php esc_html_e('حداکثر تعداد فایل‌های مجاز برای هر پیام', 'msg-system'); ?>
    </p>
    <?php
}

function msg_system_allowed_extensions_field_callback() {
    $value = get_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf,doc,docx');
    ?>
    <input type="text" 
           id="msg_system_allowed_extensions" 
           name="msg_system_allowed_extensions" 
           value="<?php echo esc_attr($value); ?>" 
           class="large-text">
    <p class="description">
        <?php esc_html_e('پسوندهای مجاز را با کاما از هم جدا کنید (مثال: jpg,pdf,doc)', 'msg-system'); ?>
    </p>
    <?php
}

function msg_system_email_notifications_field_callback() {
    $value = get_option('msg_system_email_notifications', 1);
    ?>
    <label>
        <input type="checkbox" 
               name="msg_system_email_notifications" 
               value="1" 
               <?php checked(1, $value); ?>>
        <?php esc_html_e('ارسال اعلان ایمیل برای پیام‌های جدید', 'msg-system'); ?>
    </label>
    <?php
}
/**
 * افزودن منوها به پنل مدیریت
 */
function msg_system_admin_menu() {
    add_menu_page(
        __('سیستم پیام‌رسانی', 'msg-system'),
        __('پیام‌رسانی', 'msg-system'),
        'manage_options',
        'message-system',
        'msg_system_settings_page',
        'dashicons-email',
        30
    );

    add_submenu_page(
        'message-system',
        __('تنظیمات عمومی', 'msg-system'),
        __('تنظیمات عمومی', 'msg-system'),
        'manage_options',
        'message-system',
        'msg_system_settings_page'
    );

    add_submenu_page(
        'message-system',
        __('گروه‌های کاربری', 'msg-system'),
        __('گروه‌های کاربری', 'msg-system'),
        'manage_options',
        'message-system-groups',
        'msg_system_groups_page'
    );

    add_submenu_page(
        'message-system',
        __('برچسب‌ها', 'msg-system'),
        __('برچسب‌ها', 'msg-system'),
        'manage_options',
        'message-system-labels',
        'msg_system_labels_page'
    );
}
add_action('admin_menu', 'msg_system_admin_menu');

/**
 * ثبت تنظیمات
 */
function msg_system_register_settings() {
    // ثبت تنظیمات عمومی
    register_setting(
        'msg_system_settings',
        'msg_system_max_file_size',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 20
        )
    );

    register_setting(
        'msg_system_settings',
        'msg_system_max_file_count',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 3
        )
    );

    register_setting(
        'msg_system_settings',
        'msg_system_allowed_extensions',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'jpg,jpeg,png,pdf,doc,docx'
        )
    );

    register_setting(
        'msg_system_settings',
        'msg_system_email_notifications',
        array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => true
        )
    );

    // ثبت تنظیمات گروه‌ها
    register_setting(
        'msg_system_groups',
        'msg_system_user_groups',
        array(
            'type' => 'array',
            'sanitize_callback' => 'msg_system_sanitize_groups',
            'default' => array()
        )
    );

    // ثبت تنظیمات برچسب‌ها
    register_setting(
        'msg_system_labels',
        'msg_system_labels',
        array(
            'type' => 'array',
            'sanitize_callback' => 'msg_system_sanitize_labels',
            'default' => array()
        )
    );

    // اضافه کردن بخش تنظیمات عمومی
    add_settings_section(
        'msg_system_general_settings',
        __('تنظیمات عمومی', 'msg-system'),
        'msg_system_general_settings_section_callback',
        'message-system'
    );

    // اضافه کردن فیلدهای تنظیمات
    add_settings_field(
        'msg_system_max_file_size',
        __('حداکثر حجم فایل (MB)', 'msg-system'),
        'msg_system_max_file_size_field_callback',
        'message-system',
        'msg_system_general_settings'
    );

    add_settings_field(
        'msg_system_max_file_count',
        __('حداکثر تعداد فایل', 'msg-system'),
        'msg_system_max_file_count_field_callback',
        'message-system',
        'msg_system_general_settings'
    );

    add_settings_field(
        'msg_system_allowed_extensions',
        __('پسوندهای مجاز', 'msg-system'),
        'msg_system_allowed_extensions_field_callback',
        'message-system',
        'msg_system_general_settings'
    );

    add_settings_field(
        'msg_system_email_notifications',
        __('اعلان‌های ایمیل', 'msg-system'),
        'msg_system_email_notifications_field_callback',
        'message-system',
        'msg_system_general_settings'
    );
}
add_action('admin_init', 'msg_system_register_settings');

/**
 * پاکسازی داده‌های گروه‌ها
 */
function msg_system_sanitize_groups($groups) {
    if (!is_array($groups)) {
        return array();
    }

    $clean_groups = array();
    foreach ($groups as $key => $group) {
        if (empty($group['name'])) {
            continue;
        }

        $clean_groups[$key] = array(
            'name' => sanitize_text_field($group['name']),
            'description' => sanitize_textarea_field($group['description']),
            'permissions' => isset($group['permissions']) ? array_map('sanitize_text_field', $group['permissions']) : array()
        );
    }

    return $clean_groups;
}

/**
 * پاکسازی داده‌های برچسب‌ها
 */
function msg_system_sanitize_labels($labels) {
    if (!is_array($labels)) {
        return array();
    }

    $clean_labels = array();
    foreach ($labels as $key => $label) {
        if (empty($label['text'])) {
            continue;
        }

        $clean_labels[$key] = array(
            'text' => sanitize_text_field($label['text']),
            'color' => sanitize_hex_color($label['color']),
            'icon' => sanitize_text_field($label['icon'])
        );
    }

    return $clean_labels;
}

/**
 * افزودن اسکریپت‌های مورد نیاز
 */
function msg_system_admin_scripts($hook) {
    if (strpos($hook, 'message-system') === false) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    wp_enqueue_script(
        'msg-system-admin',
        MSG_SYSTEM_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery', 'wp-color-picker'),
        '1.0.0',
        true
    );

    wp_localize_script('msg-system-admin', 'msgSystemAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('msg_system_admin_nonce'),
        'texts' => array(
            'confirm_delete' => __('آیا از حذف این مورد اطمینان دارید؟', 'msg-system'),
            'error' => __('خطا در انجام عملیات', 'msg-system'),
            'success' => __('عملیات با موفقیت انجام شد', 'msg-system')
        )
    ));
}
add_action('admin_enqueue_scripts', 'msg_system_admin_scripts');

/**
 * بررسی اعتبار درخواست‌های ذخیره تنظیمات
 */
function msg_system_verify_settings_nonce() {
    if (
        !isset($_POST['msg_system_settings_nonce']) || 
        !wp_verify_nonce($_POST['msg_system_settings_nonce'], 'msg_system_settings_nonce')
    ) {
        wp_die(__('دسترسی غیرمجاز', 'msg-system'));
    }
}
add_action('admin_init', 'msg_system_verify_settings_nonce');
