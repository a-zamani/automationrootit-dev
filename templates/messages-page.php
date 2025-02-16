<?php
/**
 * قالب صفحه اصلی پیام‌ها
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 10:45:10
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی دسترسی کاربر
if (!is_user_logged_in()) {
    echo msg_system_show_error(__('لطفاً برای مشاهده پیام‌ها وارد شوید', 'msg-system'));
    return;
}

$current_user = wp_get_current_user();
$inbox_count = msg_system_count_unread_messages();
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'inbox';
?>

<div class="msg-system-container">
    <div class="msg-system-header">
        <h2><?php _e('پیام‌های من', 'msg-system'); ?></h2>
        <a href="<?php echo esc_url(get_permalink(get_option('msg_system_page_send_message'))); ?>" 
           class="button button-primary">
            <?php _e('ارسال پیام جدید', 'msg-system'); ?>
        </a>
    </div>

    <div class="msg-system-tabs">
        <a href="?tab=inbox" 
           class="<?php echo $current_tab === 'inbox' ? 'active' : ''; ?>">
            <?php _e('صندوق ورودی', 'msg-system'); ?>
            <?php if ($inbox_count > 0) : ?>
                <span class="msg-count"><?php echo $inbox_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=sent" 
           class="<?php echo $current_tab === 'sent' ? 'active' : ''; ?>">
            <?php _e('پیام‌های ارسالی', 'msg-system'); ?>
        </a>
    </div>

    <div class="msg-system-content">
        <?php if ($current_tab === 'inbox') : ?>
            <?php include MSG_SYSTEM_PATH . 'templates/received-messages.php'; ?>
        <?php else : ?>
            <?php include MSG_SYSTEM_PATH . 'templates/sent-messages.php'; ?>
        <?php endif; ?>
    </div>

    <div id="msg-system-modal" class="msg-modal">
        <div class="msg-modal-content">
            <span class="msg-close">&times;</span>
            <div id="msg-modal-body"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // حذف پیام
    $('.msg-delete').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php _e('آیا از حذف این پیام اطمینان دارید؟', 'msg-system'); ?>')) {
            return;
        }
        
        var $this = $(this);
        var messageId = $this.data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_delete_message',
                message_id: messageId,
                nonce: '<?php echo wp_create_nonce('msg_system_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $this.closest('tr').fadeOut();
                } else {
                    alert(response.data);
                }
            }
        });
    });

    // نمایش مودال پیام
    $('.msg-view').on('click', function(e) {
        e.preventDefault();
        var messageId = $(this).data('id');
        var modal = $('#msg-system-modal');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_get_message',
                message_id: messageId,
                nonce: '<?php echo wp_create_nonce('msg_system_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#msg-modal-body').html(response.data.content);
                    modal.show();
                }
            }
        });
    });

    // بستن مودال
    $('.msg-close').on('click', function() {
        $('#msg-system-modal').hide();
    });
});
</script>