<?php
/**
 * کلاس مدیریت درخواست‌های AJAX
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 09:17:33
 */

if (!defined('ABSPATH')) {
    exit;
}

class MSG_System_Ajax {
    public function __construct() {
        add_action('wp_ajax_msg_system_send_message', array($this, 'send_message'));
        add_action('wp_ajax_msg_system_delete_message', array($this, 'delete_message'));
        add_action('wp_ajax_msg_system_mark_as_read', array($this, 'mark_as_read'));
    }

    public function send_message() {
        check_ajax_referer('msg_system_nonce', 'nonce');

        $recipient_id = intval($_POST['recipient_id']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);

        if (empty($recipient_id) || empty($subject) || empty($content)) {
            wp_send_json_error(__('لطفاً تمام فیلدهای ضروری را پر کنید', 'msg-system'));
        }

        $message_id = msg_system_message_handler()->send_message(array(
            'recipient_id' => $recipient_id,
            'subject'      => $subject,
            'content'      => $content,
            'attachments'  => isset($_FILES['attachments']) ? $_FILES['attachments'] : array()
        ));

        if (is_wp_error($message_id)) {
            wp_send_json_error($message_id->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('پیام با موفقیت ارسال شد', 'msg-system'),
            'id'      => $message_id
        ));
    }

    public function delete_message() {
        check_ajax_referer('msg_system_nonce', 'nonce');
        
        $message_id = intval($_POST['message_id']);
        
        if (msg_system_message_handler()->delete_message($message_id)) {
            wp_send_json_success(__('پیام با موفقیت حذف شد', 'msg-system'));
        } else {
            wp_send_json_error(__('خطا در حذف پیام', 'msg-system'));
        }
    }

    public function mark_as_read() {
        check_ajax_referer('msg_system_nonce', 'nonce');
        
        $message_id = intval($_POST['message_id']);
        
        if (msg_system_message_handler()->mark_as_read($message_id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}