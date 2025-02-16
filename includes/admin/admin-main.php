<?php
/**
 * صفحه اصلی مدیریت پیام‌رسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-16 06:04:28
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * نمایش صفحه اصلی مدیریت
 */
function msg_system_admin_main_page() {
    // بررسی دسترسی کاربر
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'msg-system'));
    }

    // نمایش محتوا
    msg_system_admin_display_header();
    msg_system_admin_display_stats();
    msg_system_admin_display_quick_actions();
    msg_system_admin_display_recent_messages();
    msg_system_admin_display_help();
}

/**
 * نمایش هدر صفحه
 */
function msg_system_admin_display_header() {
    ?>
    <div class="msg-system-wrap">
        <h1>
            <span class="dashicons dashicons-email-alt"></span>
            <?php echo esc_html__('سیستم پیام‌رسانی داخلی', 'msg-system'); ?>
        </h1>
    <?php
}

/**
 * نمایش آمار کلی سیستم
 */
function msg_system_admin_display_stats() {
    $total_messages = wp_count_posts('msg_system_message');
    $unread_count = msg_system_get_unread_count();
    $total_users = count_users();
    $active_users = msg_system_get_active_users_count();
    ?>
    <div class="msg-system-stats">
        <div class="stat-box">
            <h3><?php esc_html_e('کل پیام‌ها', 'msg-system'); ?></h3>
            <span class="stat-number"><?php echo esc_html($total_messages->publish); ?></span>
        </div>
        <div class="stat-box">
            <h3><?php esc_html_e('پیام‌های نخوانده', 'msg-system'); ?></h3>
            <span class="stat-number"><?php echo esc_html($unread_count); ?></span>
        </div>
        <div class="stat-box">
            <h3><?php esc_html_e('کاربران فعال', 'msg-system'); ?></h3>
            <span class="stat-number"><?php echo esc_html($active_users); ?></span>
        </div>
        <div class="stat-box">
            <h3><?php esc_html_e('کل کاربران', 'msg-system'); ?></h3>
            <span class="stat-number"><?php echo esc_html($total_users['total_users']); ?></span>
        </div>
    </div>
    <?php
}

/**
 * نمایش دسترسی‌های سریع
 */
function msg_system_admin_display_quick_actions() {
    ?>
    <div class="msg-system-quick-actions">
        <h2><?php esc_html_e('دسترسی سریع', 'msg-system'); ?></h2>
        <div class="quick-actions-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=message-system-options')); ?>" class="stat-box">
                <span class="dashicons dashicons-admin-settings"></span>
                <span class="action-title"><?php esc_html_e('تنظیمات', 'msg-system'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=msg_system_message')); ?>" class="stat-box">
                <span class="dashicons dashicons-email"></span>
                <span class="action-title"><?php esc_html_e('مدیریت پیام‌ها', 'msg-system'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=msg_system_group&post_type=msg_system_message')); ?>" class="stat-box">
                <span class="dashicons dashicons-groups"></span>
                <span class="action-title"><?php esc_html_e('گروه‌های کاربری', 'msg-system'); ?></span>
            </a>
            <a href="<?php echo esc_url(get_permalink(get_option('msg_system_messages_page_id'))); ?>" class="stat-box">
                <span class="dashicons dashicons-visibility"></span>
                <span class="action-title"><?php esc_html_e('مشاهده صفحه پیام‌ها', 'msg-system'); ?></span>
            </a>
        </div>
    </div>
    <?php
}

/**
 * نمایش آخرین پیام‌ها
 */
function msg_system_admin_display_recent_messages() {
    $recent_messages = get_posts(array(
        'post_type' => 'msg_system_message',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    ?>
    <div class="msg-system-recent">
        <h2><?php esc_html_e('آخرین پیام‌ها', 'msg-system'); ?></h2>
        <?php if ($recent_messages) : ?>
            <table class="messages-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('فرستنده', 'msg-system'); ?></th>
                        <th><?php esc_html_e('گیرنده', 'msg-system'); ?></th>
                        <th><?php esc_html_e('موضوع', 'msg-system'); ?></th>
                        <th><?php esc_html_e('تاریخ', 'msg-system'); ?></th>
                        <th><?php esc_html_e('وضعیت', 'msg-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_messages as $message) : 
                        $message_data = msg_system_get_message_data($message->ID);
                    ?>
                        <tr>
                            <td><?php echo esc_html($message_data['sender']->display_name); ?></td>
                            <td><?php echo esc_html($message_data['recipient']->display_name); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($message->ID)); ?>">
                                    <?php echo esc_html($message->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(get_the_date('Y/m/d H:i', $message)); ?></td>
                            <td class="status-<?php echo esc_attr($message_data['status']); ?>">
                                <span class="status-indicator"></span>
                                <?php echo esc_html(msg_system_get_status_label($message_data['status'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="msg-no-items">
                <?php esc_html_e('هنوز هیچ پیامی ارسال نشده است.', 'msg-system'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * نمایش راهنمای سریع
 */
function msg_system_admin_display_help() {
    ?>
    <div class="msg-system-help">
        <h2><?php esc_html_e('راهنمای سریع', 'msg-system'); ?></h2>
        <div class="help-content">
            <p><?php esc_html_e('برای شروع کار با سیستم پیام‌رسانی:', 'msg-system'); ?></p>
            <ol>
                <li><?php esc_html_e('از بخش "تنظیمات" پیکربندی اولیه را انجام دهید', 'msg-system'); ?></li>
                <li><?php esc_html_e('گروه‌های کاربری مورد نیاز را ایجاد کنید', 'msg-system'); ?></li>
                <li><?php esc_html_e('دسترسی‌های لازم را برای کاربران تنظیم کنید', 'msg-system'); ?></li>
                <li><?php esc_html_e('صفحات پیام‌رسانی را در منوی سایت قرار دهید', 'msg-system'); ?></li>
            </ol>
            <p>
                <a href="https://github.com/akamsafirrootit/messaging-system/wiki" target="_blank">
                    <?php esc_html_e('مشاهده مستندات کامل', 'msg-system'); ?> →
                </a>
            </p>
        </div>
    </div>
    </div><!-- .msg-system-wrap -->
    <?php
}

// Hook برای اجرای صفحه اصلی
add_action('admin_menu', 'msg_system_admin_menu');

/**
 * افزودن منوی مدیریت
 */
function msg_system_admin_menu() {
    add_menu_page(
        __('سیستم پیام‌رسانی', 'msg-system'),
        __('پیام‌ها', 'msg-system'),
        'manage_options',
        'msg-system',
        'msg_system_admin_main_page',
        'dashicons-email',
        25
    );
}