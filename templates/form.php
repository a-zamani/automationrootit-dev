<?php
/**
 * قالب فرم ارسال پیام
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 14:14:28
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="msg-system-form-wrap">
    <form method="post" class="msg-system-form" enctype="multipart/form-data">
        <?php wp_nonce_field('msg_system_send_message', 'msg_system_nonce'); ?>
        
        <div class="form-group">
            <label for="msg_to"><?php _e('گیرنده:', 'msg-system'); ?></label>
            <select name="msg_to" id="msg_to" required>
                <option value=""><?php _e('انتخاب گیرنده...', 'msg-system'); ?></option>
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>">
                        <?php echo msg_system_get_avatar($user->ID, 20) . ' ' . esc_html($user->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="msg_subject"><?php _e('موضوع:', 'msg-system'); ?></label>
            <input type="text" name="msg_subject" id="msg_subject" required 
                   value="<?php echo isset($_GET['reply_to']) ? esc_attr('پاسخ: ' . get_the_title($_GET['reply_to'])) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="msg_content"><?php _e('متن پیام:', 'msg-system'); ?></label>
            <?php 
            wp_editor('', 'msg_content', array(
                'textarea_name' => 'msg_content',
                'media_buttons' => false,
                'textarea_rows' => 10,
                'teeny' => true,
                'quicktags' => array('buttons' => 'strong,em,link,ul,ol,li,close')
            ));
            ?>
        </div>

        <?php if (!empty($labels)) : ?>
        <div class="form-group">
            <label for="msg_label"><?php _e('برچسب:', 'msg-system'); ?></label>
            <select name="msg_label" id="msg_label">
                <option value=""><?php _e('بدون برچسب', 'msg-system'); ?></option>
                <?php foreach ($labels as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" 
                            style="color: <?php echo esc_attr($label['color']); ?>">
                        <?php echo esc_html($label['text']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="msg_attachment"><?php _e('فایل پیوست:', 'msg-system'); ?></label>
            <input type="file" name="msg_attachment[]" id="msg_attachment" multiple>
            <p class="description">
                <?php 
                printf(
                    __('حداکثر %d فایل با فرمت‌های مجاز: %s - حداکثر حجم هر فایل: %dMB', 'msg-system'),
                    get_option('msg_system_max_file_count', 3),
                    get_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf,doc,docx'),
                    get_option('msg_system_max_file_size', 20)
                ); 
                ?>
            </p>
        </div>

        <div class="form-submit">
            <button type="submit" name="msg_system_submit" class="button button-primary">
                <?php _e('ارسال پیام', 'msg-system'); ?>
            </button>
        </div>
    </form>
</div>