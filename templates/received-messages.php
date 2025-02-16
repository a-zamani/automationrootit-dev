<?php
/**
 * قالب نمایش پیام‌های دریافتی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 14:17:26
 */

if (!defined('ABSPATH')) {
    exit;
}

// نمایش فیلتر برچسب‌ها و تاریخ
$labels = get_option('msg_system_labels', array());
$current_label = isset($_GET['msg_label']) ? $_GET['msg_label'] : '';
$current_date = isset($_GET['msg_date']) ? $_GET['msg_date'] : '';
$current_status = isset($_GET['msg_status']) ? $_GET['msg_status'] : '';
?>

<div class="msg-system-filters">
    <form method="get" class="msg-filters-form">
        <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
        
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
            <label for="msg_status"><?php _e('وضعیت:', 'msg-system'); ?></label>
            <select name="msg_status" id="msg_status">
                <option value=""><?php _e('همه وضعیت‌ها', 'msg-system'); ?></option>
                <option value="unread" <?php selected($current_status, 'unread'); ?>>
                    <?php _e('خوانده نشده', 'msg-system'); ?>
                </option>
                <option value="read" <?php selected($current_status, 'read'); ?>>
                    <?php _e('خوانده شده', 'msg-system'); ?>
                </option>
                <option value="replied" <?php selected($current_status, 'replied'); ?>>
                    <?php _e('پاسخ داده شده', 'msg-system'); ?>
                </option>
            </select>
        </div>

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
            <?php if (!empty($_GET['msg_label']) || !empty($_GET['msg_date']) || !empty($_GET['msg_status'])) : ?>
                <a href="<?php echo esc_url(remove_query_arg(array('msg_label', 'msg_date', 'msg_status'))); ?>" class="button">
                    <?php _e('حذف فیلترها', 'msg-system'); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="msg-system-messages received-messages">
    <h2>
        <?php _e('پیام‌های دریافتی', 'msg-system'); ?>
        <?php 
        $unread_count = msg_system_get_unread_count();
        if ($unread_count > 0) :
        ?>
            <span class="unread-count">(<?php printf(__('%d پیام خوانده نشده', 'msg-system'), $unread_count); ?>)</span>
        <?php endif; ?>
    </h2>
    
    <?php if ($messages->have_posts()) : ?>
        <div class="bulk-actions">
            <button type="button" class="mark-all-read button" data-nonce="<?php echo wp_create_nonce('msg_system_mark_all_read'); ?>">
                <?php _e('علامت‌گذاری همه به عنوان خوانده شده', 'msg-system'); ?>
            </button>
        </div>

        <table class="messages-table">
            <thead>
                <tr>
                    <th class="column-status"></th>
                    <th class="column-date"><?php _e('تاریخ', 'msg-system'); ?></th>
                    <th class="column-sender"><?php _e('فرستنده', 'msg-system'); ?></th>
                    <th class="column-subject"><?php _e('موضوع', 'msg-system'); ?></th>
                    <th class="column-label"><?php _e('برچسب', 'msg-system'); ?></th>
                    <th class="column-actions"><?php _e('عملیات', 'msg-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($messages->have_posts()) : $messages->the_post(); 
                    $sender = get_userdata(get_post_field('post_author', get_the_ID()));
                    $label_key = get_post_meta(get_the_ID(), '_msg_label', true);
                    $status = get_post_meta(get_the_ID(), '_msg_status', true);
                    $label = isset($labels[$label_key]) ? $labels[$label_key] : null;
                    $has_attachments = !empty(get_post_meta(get_the_ID(), '_msg_attachments', true));
                ?>
                    <tr class="<?php echo msg_system_message_row_class(get_the_ID()); ?>">
                        <td class="column-status">
                            <span class="status-indicator" title="<?php echo msg_system_get_status_text($status); ?>"></span>
                        </td>
                        <td class="column-date">
                            <span class="msg-date"><?php echo get_the_date('Y/m/d'); ?></span>
                            <span class="msg-time"><?php echo get_the_date('H:i'); ?></span>
                        </td>
                        <td class="column-sender">
                            <?php if ($sender) : ?>
                                <div class="sender-info">
                                    <?php echo msg_system_get_avatar($sender->ID, 32); ?>
                                    <span class="sender-name"><?php echo esc_html($sender->display_name); ?></span>
                                </div>
                            <?php else : ?>
                                <span class="sender-unknown"><?php _e('کاربر نامشخص', 'msg-system'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-subject">
                            <div class="message-title">
                                <?php the_title(); ?>
                                <?php if ($has_attachments) : ?>
                                    <span class="dashicons dashicons-paperclip" title="<?php _e('دارای پیوست', 'msg-system'); ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="message-excerpt">
                                <?php echo wp_trim_words(get_the_content(), 10); ?>
                            </div>
                        </td>
                        <td class="column-label">
                            <?php if ($label) : ?>
                                <span class="msg-label" style="color: <?php echo esc_attr($label['color']); ?>">
                                    <?php echo esc_html($label['text']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions">
                            <div class="message-actions">
                                <button type="button" class="view-message button button-small" 
                                        data-id="<?php the_ID(); ?>">
                                    <?php _e('مشاهده', 'msg-system'); ?>
                                </button>
                                <button type="button" class="reply-message button button-small" 
                                        data-id="<?php the_ID(); ?>"
                                        data-recipient="<?php echo esc_attr($sender->ID); ?>"
                                        data-subject="<?php echo esc_attr('پاسخ: ' . get_the_title()); ?>">
                                    <?php _e('پاسخ', 'msg-system'); ?>
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

        <?php if ($messages->max_num_pages > 1) : ?>
            <div class="msg-pagination">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; قبلی', 'msg-system'),
                    'next_text' => __('بعدی &raquo;', 'msg-system'),
                    'total' => $messages->max_num_pages,
                    'current' => $paged,
                    'type' => 'list'
                ));
                ?>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <div class="msg-no-messages">
            <div class="no-messages-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <p><?php _e('هیچ پیامی یافت نشد.', 'msg-system'); ?></p>
            <?php if (!empty($_GET)) : ?>
                <p>
                    <a href="<?php echo esc_url(remove_query_arg(array('msg_label', 'msg_date', 'msg_status', 'paged'))); ?>" class="button">
                        <?php _e('نمایش همه پیام‌ها', 'msg-system'); ?>
                    </a>
                </p>
            <?php endif; ?>
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