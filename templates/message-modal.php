<?php
/**
 * قالب نمایش پیام در مودال
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 14:14:28
 */

if (!defined('ABSPATH')) {
    exit;
}

$sender = get_userdata($message->post_author);
$recipient = get_userdata($recipient_id);
$label_key = get_post_meta($message_id, '_msg_label', true);
$labels = get_option('msg_system_labels', array());
$label = isset($labels[$label_key]) ? $labels[$label_key] : null;
$attachments = get_post_meta($message_id, '_msg_attachments', true);
?>
<div class="message-details">
    <h3><?php echo esc_html($message->post_title); ?></h3>
    
    <div class="message-meta">
        <div class="sender-info">
            <?php echo msg_system_get_avatar($message->post_author, 40); ?>
            <div class="user-details">
                <p>
                    <strong><?php _e('فرستنده:', 'msg-system'); ?></strong> 
                    <?php echo $sender ? esc_html($sender->display_name) : __('کاربر نامشخص', 'msg-system'); ?>
                </p>
                <p>
                    <strong><?php _e('تاریخ:', 'msg-system'); ?></strong> 
                    <?php echo get_the_date('Y/m/d H:i', $message_id); ?>
                </p>
            </div>
        </div>
        
        <div class="recipient-info">
            <?php echo msg_system_get_avatar($recipient_id, 40); ?>
            <div class="user-details">
                <p>
                    <strong><?php _e('گیرنده:', 'msg-system'); ?></strong> 
                    <?php echo $recipient ? esc_html($recipient->display_name) : __('کاربر نامشخص', 'msg-system'); ?>
                </p>
                <?php if ($label) : ?>
                    <p>
                        <strong><?php _e('برچسب:', 'msg-system'); ?></strong>
                        <span class="msg-label" style="color: <?php echo esc_attr($label['color']); ?>">
                            <?php echo esc_html($label['text']); ?>
                        </span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="message-body">
        <?php echo wpautop($message->post_content); ?>
    </div>

    <?php if (!empty($attachments)) : ?>
        <div class="message-attachments">
            <h4><?php _e('فایل‌های پیوست:', 'msg-system'); ?></h4>
            <ul>
                <?php foreach ($attachments as $attachment_id) : 
                    $file_url = wp_get_attachment_url($attachment_id);
                    $file_name = basename(get_attached_file($attachment_id));
                    $file_type = wp_check_filetype($file_name);
                    if ($file_url && $file_name) :
                ?>
                    <li class="attachment-item <?php echo esc_attr($file_type['ext']); ?>">
                        <a href="<?php echo esc_url($file_url); ?>" target="_blank">
                            <?php 
                            $icon = 'dashicons-media-default';
                            if (wp_attachment_is_image($attachment_id)) {
                                $icon = 'dashicons-format-image';
                            } elseif (in_array($file_type['ext'], array('pdf', 'doc', 'docx'))) {
                                $icon = 'dashicons-media-document';
                            }
                            ?>
                            <span class="dashicons <?php echo $icon; ?>"></span>
                            <?php echo esc_html($file_name); ?>
                        </a>
                    </li>
                <?php 
                    endif;
                endforeach; 
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="message-actions">
        <button onclick="jQuery('#message-modal').hide();" class="button">
            <?php _e('بستن', 'msg-system'); ?>
        </button>
        
        <?php if ($message->post_author !== get_current_user_id()) : ?>
            <button class="reply-message button button-primary" 
                    data-recipient="<?php echo esc_attr($message->post_author); ?>"
                    data-subject="<?php echo esc_attr('پاسخ: ' . $message->post_title); ?>">
                <?php _e('پاسخ', 'msg-system'); ?>
            </button>
        <?php endif; ?>

        <?php if (msg_system_can_delete_message($message_id)) : ?>
            <button class="delete-message button button-link-delete" data-id="<?php echo esc_attr($message_id); ?>">
                <?php _e('حذف', 'msg-system'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>