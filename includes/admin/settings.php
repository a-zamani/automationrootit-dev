<?php
/**
 * تنظیمات پلاگین سیستم پیام‌رسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @version 1.0.0
 * @since 2025-02-15 14:23:31
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس تنظیمات پلاگین
 */
class MSG_System_Settings {
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * افزودن منو به بخش مدیریت
     */
    public function add_admin_menu() {
        add_menu_page(
            __('سیستم پیام‌رسانی', 'msg-system'),
            __('پیام‌رسانی', 'msg-system'),
            'manage_options',
            'msg_system_settings',
            array($this, 'render_settings_page'),
            'dashicons-email',
            30
        );
        
        add_submenu_page(
            'msg_system_settings',
            __('تنظیمات', 'msg-system'),
            __('تنظیمات', 'msg-system'),
            'manage_options',
            'msg_system_settings'
        );
        
        add_submenu_page(
            'msg_system_settings',
            __('برچسب‌ها', 'msg-system'),
            __('برچسب‌ها', 'msg-system'),
            'manage_options',
            'msg_system_labels',
            array($this, 'render_labels_page')
        );
        
        add_submenu_page(
            'msg_system_settings',
            __('آمار', 'msg-system'),
            __('آمار', 'msg-system'),
            'manage_options',
            'msg_system_stats',
            array($this, 'render_stats_page')
        );
    }
    
    /**
     * ثبت تنظیمات
     */
    public function init_settings() {
        register_setting('msg_system_settings', 'msg_system_settings');
        
        // بخش تنظیمات عمومی
        add_settings_section(
            'msg_system_general',
            __('تنظیمات عمومی', 'msg-system'),
            array($this, 'render_general_section'),
            'msg_system_settings'
        );
        
        // تنظیمات فایل‌ها
        add_settings_field(
            'max_file_size',
            __('حداکثر حجم فایل (MB)', 'msg-system'),
            array($this, 'render_number_field'),
            'msg_system_settings',
            'msg_system_general',
            array(
                'label_for' => 'max_file_size',
                'default' => 20,
                'min' => 1,
                'max' => 100
            )
        );
        
        add_settings_field(
            'max_file_count',
            __('حداکثر تعداد فایل', 'msg-system'),
            array($this, 'render_number_field'),
            'msg_system_settings',
            'msg_system_general',
            array(
                'label_for' => 'max_file_count',
                'default' => 3,
                'min' => 1,
                'max' => 10
            )
        );
        
        add_settings_field(
            'allowed_extensions',
            __('پسوندهای مجاز', 'msg-system'),
            array($this, 'render_text_field'),
            'msg_system_settings',
            'msg_system_general',
            array(
                'label_for' => 'allowed_extensions',
                'default' => 'jpg,jpeg,png,pdf,doc,docx',
                'description' => __('پسوندها را با کاما جدا کنید', 'msg-system')
            )
        );
        
        // تنظیمات اعلان‌ها
        add_settings_field(
            'email_notifications',
            __('اعلان ایمیل', 'msg-system'),
            array($this, 'render_checkbox_field'),
            'msg_system_settings',
            'msg_system_general',
            array(
                'label_for' => 'email_notifications',
                'default' => 1,
                'description' => __('ارسال ایمیل برای پیام‌های جدید', 'msg-system')
            )
        );
        
        // محدودیت‌های ارسال
        add_settings_field(
            'daily_limit',
            __('محدودیت روزانه', 'msg-system'),
            array($this, 'render_number_field'),
            'msg_system_settings',
            'msg_system_general',
            array(
                'label_for' => 'daily_limit',
                'default' => 50,
                'min' => 1,
                'max' => 1000,
                'description' => __('حداکثر تعداد پیام قابل ارسال در روز', 'msg-system')
            )
        );
    }
    
    /**
     * نمایش صفحه تنظیمات
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('msg_system_settings');
                do_settings_sections('msg_system_settings');
                submit_button(__('ذخیره تنظیمات', 'msg-system'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * نمایش صفحه برچسب‌ها
     */
    public function render_labels_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // ذخیره برچسب جدید
        if (isset($_POST['msg_system_add_label']) && wp_verify_nonce($_POST['msg_system_label_nonce'], 'msg_system_add_label')) {
            $text = sanitize_text_field($_POST['label_text']);
            $color = sanitize_hex_color($_POST['label_color']);
            
            if (!empty($text) && !empty($color)) {
                $labels = get_option('msg_system_labels', array());
                $key = sanitize_title($text);
                $labels[$key] = array(
                    'text' => $text,
                    'color' => $color
                );
                update_option('msg_system_labels', $labels);
                
                echo '<div class="notice notice-success"><p>' . __('برچسب با موفقیت اضافه شد.', 'msg-system') . '</p></div>';
            }
        }
        
        // حذف برچسب
        if (isset($_GET['delete_label']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_label_' . $_GET['delete_label'])) {
            $labels = get_option('msg_system_labels', array());
            $key = sanitize_title($_GET['delete_label']);
            
            if (isset($labels[$key])) {
                unset($labels[$key]);
                update_option('msg_system_labels', $labels);
                
                echo '<div class="notice notice-success"><p>' . __('برچسب با موفقیت حذف شد.', 'msg-system') . '</p></div>';
            }
        }
        
        $labels = get_option('msg_system_labels', array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="msg-system-labels-form">
                <h2><?php _e('افزودن برچسب جدید', 'msg-system'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('msg_system_add_label', 'msg_system_label_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="label_text"><?php _e('متن برچسب:', 'msg-system'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="label_text" id="label_text" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="label_color"><?php _e('رنگ برچسب:', 'msg-system'); ?></label>
                            </th>
                            <td>
                                <input type="color" name="label_color" id="label_color" value="#000000" required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="msg_system_add_label" class="button button-primary" 
                               value="<?php _e('افزودن برچسب', 'msg-system'); ?>">
                    </p>
                </form>
            </div>
            
            <?php if (!empty($labels)) : ?>
                <div class="msg-system-labels-list">
                    <h2><?php _e('برچسب‌های موجود', 'msg-system'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('برچسب', 'msg-system'); ?></th>
                                <th><?php _e('رنگ', 'msg-system'); ?></th>
                                <th><?php _e('عملیات', 'msg-system'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($labels as $key => $label) : ?>
                                <tr>
                                    <td>
                                        <span class="msg-label" style="color: <?php echo esc_attr($label['color']); ?>">
                                            <?php echo esc_html($label['text']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="msg-label-color" style="background-color: <?php echo esc_attr($label['color']); ?>">
                                            <?php echo esc_html($label['color']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $delete_url = wp_nonce_url(
                                            add_query_arg(array('delete_label' => $key)),
                                            'delete_label_' . $key
                                        );
                                        ?>
                                        <a href="<?php echo esc_url($delete_url); ?>" 
                                           class="button button-small button-link-delete"
                                           onclick="return confirm('<?php _e('آیا از حذف این برچسب اطمینان دارید؟', 'msg-system'); ?>');">
                                            <?php _e('حذف', 'msg-system'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * نمایش صفحه آمار
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $stats = msg_system_get_stats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="msg-system-stats">
                <div class="stat-box">
                    <h3><?php _e('تعداد کل پیام‌ها', 'msg-system'); ?></h3>
                    <span class="stat-number"><?php echo number_format_i18n($stats['total_messages']); ?></span>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('کاربران فعال', 'msg-system'); ?></h3>
                    <span class="stat-number"><?php echo number_format_i18n($stats['total_users']); ?></span>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('پیام‌های خوانده نشده', 'msg-system'); ?></h3>
                    <span class="stat-number"><?php echo number_format_i18n($stats['unread_messages']); ?></span>
                </div>
                
                <div class="stat-box">
                    <h3><?php _e('تعداد پیوست‌ها', 'msg-system'); ?></h3>
                    <span class="stat-number"><?php echo number_format_i18n($stats['attachments']); ?></span>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * نمایش فیلد عددی
     */
    public function render_number_field($args) {
        $options = get_option('msg_system_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <input type="number" 
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="msg_system_settings[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($args['min']); ?>"
               max="<?php echo esc_attr($args['max']); ?>"
               class="small-text">
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
    
/**
     * نمایش فیلد متنی
     */
    public function render_text_field($args) {
        $options = get_option('msg_system_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <input type="text" 
               id="<?php echo esc_attr($args['label_for']); ?>"
               name="msg_system_settings[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <?php
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * نمایش فیلد چک‌باکس
     */
    public function render_checkbox_field($args) {
        $options = get_option('msg_system_settings');
        $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : $args['default'];
        ?>
        <label>
            <input type="checkbox" 
                   id="<?php echo esc_attr($args['label_for']); ?>"
                   name="msg_system_settings[<?php echo esc_attr($args['label_for']); ?>]"
                   value="1"
                   <?php checked(1, $value); ?>>
            <?php if (isset($args['description'])) echo esc_html($args['description']); ?>
        </label>
        <?php
    }

    /**
     * نمایش بخش تنظیمات عمومی
     */
    public function render_general_section($args) {
        ?>
        <p><?php _e('تنظیمات عمومی سیستم پیام‌رسانی را در اینجا تعیین کنید.', 'msg-system'); ?></p>
        <?php
    }

    /**
     * اضافه کردن استایل‌ها و اسکریپت‌های مدیریت
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'msg_system_') === false) {
            return;
        }

        wp_enqueue_style(
            'msg-system-admin',
            MSG_SYSTEM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MSG_SYSTEM_VERSION
        );

        wp_enqueue_script(
            'msg-system-admin',
            MSG_SYSTEM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MSG_SYSTEM_VERSION,
            true
        );

        wp_localize_script('msg-system-admin', 'msgSystemAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('msg_system_admin_nonce'),
            'confirmDelete' => __('آیا از حذف این مورد اطمینان دارید؟', 'msg-system'),
            'deleteSuccess' => __('با موفقیت حذف شد.', 'msg-system'),
            'deleteError' => __('خطا در حذف.', 'msg-system'),
            'saveSuccess' => __('تنظیمات با موفقیت ذخیره شد.', 'msg-system'),
            'saveError' => __('خطا در ذخیره تنظیمات.', 'msg-system')
        ));
    }
}

// ایجاد نمونه از کلاس تنظیمات
new MSG_System_Settings();

/**
 * تابع کمکی برای دریافت تنظیمات
 */
function msg_system_get_setting($key, $default = '') {
    $options = get_option('msg_system_settings');
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * اضافه کردن لینک تنظیمات به صفحه افزونه‌ها
 */
add_filter('plugin_action_links_' . plugin_basename(MSG_SYSTEM_FILE), 'msg_system_add_settings_link');
function msg_system_add_settings_link($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=msg_system_settings'),
        __('تنظیمات', 'msg-system')
    );
    array_unshift($links, $settings_link);
    return $links;
}