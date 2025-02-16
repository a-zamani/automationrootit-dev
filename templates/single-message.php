<?php
/**
 * قالب نمایش تک پیام
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-13 10:45:10
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی دسترسی کاربر
if (!is_user_logged_in()) {
    echo msg_system_show_error(__('لطفاً برای مشاهده پیام وارد شوید', 'msg-system'));
    return;
}

global $post;
$current_user = wp_get_current_user();
$sender_id = get_post_meta($post->ID, '_msg_sender_id', true);
$recipient_id = get_post_meta($post->ID, '_msg_recipient_id', true);
$attachments = get_post_meta($post->ID, '_msg_attachments', true);

// بررسی دسترسی به پیام
if ($current_user->ID != $sender_id && $current_user->ID != $recipient_id) {
    echo msg_system_show_error(__('شما اجازه دسترسی به این پیام را ندارید', 'msg-system'));
    return;
}

// علامت‌گذاری به عنوان خوانده شده
if ($current_user->ID == $recipient_id) {
    update_post_meta($post->ID, '_msg_read_status', '1');
}

$sender = get_userdata($sender_id);
?>

<div class="msg-system-single">
    <div class="msg-system-header">
        <a href="<?php echo esc_url(get_permalink(get_option('msg_system_page_messages'))); ?>" 
           class="button">
            <?php _e('← بازگشت به لیست پیام‌ها', 'msg-system'); ?>
        </a>
    </div>

    <div class="msg-system-message">
        <div class="msg-meta">
            <span class="msg-from">
                <?php _e('فرستنده:', 'msg-system'); ?> 
                <?php echo esc_html($sender->display_name); ?>
            </span>
            <span class="msg-date">
                <?php echo msg_system_format_date($post->post_date); ?>
            </span>
        </div>

        <h2 class="msg-subject"><?php the_title(); ?></h2>

        <div class="msg-content">
            <?php the_content(); ?>
        </div>

        <?php if (!empty($attachments)) : ?>
            <div class="msg-attachments">
                <h4><?php _e('فایل‌های پیوست:', 'msg-system'); ?></h4>
                <ul>
                    <?php foreach ($attachments as $attachment) : ?>
                        <li>
                            <a href="<?php echo esc_url($attachment['url']); ?>" 
                               target="_blank"
                               download>
                                <?php echo esc_html($attachment['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($current_user->ID == $recipient_id) : ?>
            <div class="msg-actions">
                <a href="#reply" class="button button-primary msg-reply">
                    <?php _e('پاسخ', 'msg-system'); ?>
                </a>
            </div>

            <div id="reply" class="msg-reply-form" style="display: none;">
                <h3><?php _e('ارسال پاسخ', 'msg-system'); ?></h3>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('msg_system_reply_nonce'); ?>
                    <input type="hidden" name="parent_id" value="<?php echo $post->ID; ?>">
                    <input type="hidden" name="recipient_id" value="<?php echo $sender_id; ?>">
                    
                    <div class="form-row">
                        <label for="msg_content">
                            <?php _e('متن پاسخ:', 'msg-system'); ?>
                        </label>
                        <textarea name="content" 
                                  id="msg_content" 
                                  rows="5" 
                                  required></textarea>
                    </div>

                    <div class="form-row">
                        <label for="msg_attachments">
                            <?php _e('فایل‌های پیوست:', 'msg-system'); ?>
                        </label>
                        <input type="file" 
                               name="attachments[]" 
                               id="msg_attachments" 
                               multiple>
                    </div>

                    <div class="form-row">
                        <button type="submit" 
                                name="msg_reply" 
                                class="button button-primary">
                            <?php _e('ارسال پاسخ', 'msg-system'); ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.msg-reply').on('click', function(e) {
        e.preventDefault();
        $('.msg-reply-form').slideToggle();
    });
});
</script>