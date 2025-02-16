<?php
/**
 * قالب نمایش پیام‌های ارسالی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 14:16:14
 */

if (!defined('ABSPATH')) {
    exit;
}

// نمایش فیلتر برچسب‌ها و تاریخ
$labels = get_option('msg_system_labels', array());
$current_label = isset($_GET['msg_label']) ? $_GET['msg_label'] : '';
$current_date = isset($_GET['msg_date']) ? $_GET['msg_date'] : '';
?>

<div class="msg-system-filters">
    <form method="get" class="msg-filters-form">
        <?php if (!empty($labels)) : ?>
            <div class="filter-group">
                <label for="msg_label"><?php _e('برچسب:', 'msg-system'); ?></label>
                <select name="msg_label" id="msg_label">
                    <option value=""><?php _e('همه برچسب‌ها', 'msg-system'); ?></option>
                    <?php foreach ($labels as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" 
                                <?php selected($current_label, $key); ?>>
                            <?php echo esc_html($label['text']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="filter-group">
            <label for="msg_date"><?php _e('تاریخ:', 'msg-system'); ?></label>
            <select name="msg_date" id="msg_date">
                <option value=""><?php _e('همه زمان‌ها', 'msg-system'); ?></option>
                <option value="today" <?php selected($current_date, 'today'); ?>>
                    <?php _e('امروز', 'msg-system'); ?>
                </option>
                <option value="week" <?php selected($current_date, 'week'); ?>>
                    <?php _e('هفته گذشته', 'msg-system'); ?>
                </option>
                <option value="month" <?php selected($current_date, 'month'); ?>>
                    <?php _e('ماه گذشته', 'msg-system'); ?>
                </option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="button"><?php _e('اعمال فیلتر', 'msg-system'); ?></button>
            <?php if (!empty($_GET['msg_label']) || !empty($_GET['msg_date'])) : ?>
                <a href="<?php echo esc_url(remove_query_arg(array('msg_label', 'msg_date'))); ?>" class="button">
                    <?php _e('حذف فیلترها', 'msg-system'); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="msg-system-messages sent-messages">
    <h2><?php _e('پیام‌های ارسالی', 'msg-system'); ?></h2>
    
    <?php if ($messages->have_posts()) : ?>
        <table class="messages-table">
            <thead>
                <tr>
                    <th class="column-date"><?php _e('تاریخ', 'msg-system'); ?></th>
                    <th class="column-recipient"><?php _e('گیرنده', 'msg-system'); ?></th>
                    <th class="column-subject"><?php _e('موضوع', 'msg-system'); ?></th>
                    <th class="column-label"><?php _e('برچسب', 'msg-system'); ?></th>
                    <th class="column-status"><?php _e('وضعیت', 'msg-system'); ?></th>
                    <th class="column-actions"><?php _e('عملیات', 'msg-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($messages->have_posts()) : $messages->the_post(); 
                    $recipient_id = get_post_meta(get_the_ID(), '_msg_to', true);
                    $recipient = get_userdata($recipient_id);
                    $label_key = get_post_meta(get_the_ID(), '_msg_label', true);
                    $status = get_post_meta(get_the_ID(), '_msg_status', true);
                    $label = isset($labels[$label_key]) ? $labels[$label_key] : null;
                ?>
                    <tr class="<?php echo msg_system_message_row_class(get_the_ID()); ?>">
                        <td class="column-date">
                            <span class="msg-date"><?php echo get_the_date('Y/m/d'); ?></span>
                            <span class="msg-time"><?php echo get_the_date('H:i'); ?></span>
                        </td>
                        <td class="column-recipient">
                            <?php if ($recipient) : ?>
                                <?php echo msg_system_get_avatar($recipient_id, 32); ?>
                                <span class="recipient-name"><?php echo esc_html($recipient->display_name); ?></span>
                            <?php else : ?>
                                <span class="recipient-unknown"><?php _e('کاربر نامشخص', 'msg-system'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-subject">
                            <?php the_title(); ?>
                            <?php 
                            $attachments = get_post_meta(get_the_ID(), '_msg_attachments', true);
                            if (!empty($attachments)) : 
                            ?>
                                <span class="dashicons dashicons-paperclip" title="<?php _e('دارای پیوست', 'msg-system'); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-label">
                            <?php if ($label) : ?>
                                <span class="msg-label" style="color: <?php echo esc_attr($label['color']); ?>">
                                    <?php echo esc_html($label['text']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <span class="msg-status status-<?php echo esc_attr($status); ?>">
                                <?php echo msg_system_get_status_text($status); ?>
                            </span>
                        </td>
                        <td class="column-actions">
                            <div class="message-actions">
                                <button type="button" class="view-message button button-small" 
                                        data-id="<?php the_ID(); ?>">
                                    <?php _e('مشاهده', 'msg-system'); ?>
                                </button>
                                <?php if (msg_system_can_delete_message(get_the_ID())) : ?>
                                    <button type="button" class="delete-message button button-small button-link-delete" 
                                            data-id="<?php the_ID(); ?>">
                                        <?php _e('حذف', 'msg-system'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="msg-pagination">
            <?php
            echo paginate_links(array(
                'total' => $messages->max_num_pages,
                'current' => $paged,
                'prev_text' => __('قبلی', 'msg-system'),
                'next_text' => __('بعدی', 'msg-system'),
                'type' => 'list'
            ));
            ?>
        </div>
    <?php else : ?>
        <div class="msg-no-messages">
            <p><?php _e('هیچ پیامی یافت نشد.', 'msg-system'); ?></p>
        </div>
    <?php endif; ?>
    <?php wp_reset_postdata(); ?>
</div>

<!-- Modal برای نمایش پیام -->
<div id="message-modal" class="msg-modal">
    <div class="msg-modal-content">
        <span class="msg-close">&times;</span>
        <div class="message-content"></div>
    </div>
</div>