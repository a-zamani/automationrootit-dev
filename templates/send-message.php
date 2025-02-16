<?php
/**
 * قالب فرم ارسال پیام
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @version 1.0.0
 * @since 2025-02-15
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی لاگین بودن کاربر
if (!is_user_logged_in()) {
    return '<div class="msg-error">لطفا برای دسترسی به سیستم پیامرسانی وارد شوید.</div>';
}

// بررسی محدودیتهای ارسال
$limit_check = msg_system_check_limits();
if (is_wp_error($limit_check)) {
    return '<div class="msg-error">' . $limit_check->get_error_message() . '</div>';
}

// دریافت تنظیمات و برچسبها
$groups = msg_system_get_groups();
$max_file_size = get_option('msg_system_max_file_size', 20);
$max_file_count = get_option('msg_system_max_file_count', 3);
$allowed_extensions = get_option('msg_system_allowed_extensions', 'jpg,jpeg,png,pdf');
?>

<div class="msg-system-container">
    <div class="msg-form-wrapper">
        <h2><?php _e('ارسال پیام جدید', 'msg-system'); ?></h2>
        
        <form id="msg-send-form" class="msg-form" method="post" enctype="multipart/form-data">
            <!-- بخش انتخاب گروه -->
            <div class="form-group">
                <label for="group-select"><?php _e('انتخاب گروه گیرنده', 'msg-system'); ?></label>
                <select name="group" id="group-select" class="form-control" required>
                    <option value=""><?php _e('لطفاً گروه را انتخاب کنید', 'msg-system'); ?></option>
                    <?php foreach ($groups as $key => $value): ?>
                        <option value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($value); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- بخش انتخاب کاربر -->
            <div class="form-group user-selection" style="display: none;">
                <label for="recipient-select"><?php _e('انتخاب گیرنده', 'msg-system'); ?></label>
                <div class="user-search-container">
                    <input type="text" id="user-search" class="user-search-input" 
                           placeholder="<?php _e('جستجوی نام کاربر...', 'msg-system'); ?>">
                    <select name="recipient_id" id="recipient-select" required disabled>
                        <option value=""><?php _e('لطفا یک کاربر را انتخاب کنید', 'msg-system'); ?></option>
                    </select>
                </div>
            </div>

            <!-- بخش موضوع -->
            <div class="form-group">
                <label for="message-subject"><?php _e('موضوع پیام', 'msg-system'); ?></label>
                <input type="text" id="message-subject" name="subject" class="form-control" required>
            </div>

            <!-- بخش متن پیام -->
            <div class="form-group">
                <label for="message_content"><?php _e('متن پیام', 'msg-system'); ?></label>
                <?php
                wp_editor('', 'message_content', array(
                    'media_buttons' => true,
                    'textarea_rows' => 10,
                    'teeny' => false,
                    'quicktags' => true,
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,forecolor,backcolor,undo,redo',
                        'toolbar2' => 'alignleft,aligncenter,alignright,strikethrough,hr,removeformat,charmap,emoticons',
                        'content_style' => 'body { font-family: Tahoma, Arial, sans-serif; font-size: 14px; }',
                        'plugins' => 'textcolor,lists,link,paste,charmap,emoticons'
                    )
                ));
                ?>
            </div>

            <!-- بخش پیوستها -->
            <div class="form-group attachment-section">
                <label><?php _e('فایل‌های پیوست', 'msg-system'); ?></label>
                <div class="attachment-container">
                    <div class="attachment-dropzone" id="attachment-dropzone">
                        <input type="file" name="attachments[]" id="file-input" multiple 
                               accept="<?php echo esc_attr('.' . str_replace(',', ',.', $allowed_extensions)); ?>" 
                               style="display: none;">
                        <div class="dropzone-content">
                            <span class="dashicons dashicons-upload"></span>
                            <p><?php _e('فایل‌ها را اینجا رها کنید یا کلیک کنید', 'msg-system'); ?></p>
                            <small>
                                <?php 
                                printf(
                                    __('فرمت‌های مجاز: %s<br>حداکثر حجم هر فایل: %s مگابایت<br>حداکثر تعداد فایل: %s عدد', 'msg-system'),
                                    str_replace(',', '، ', $allowed_extensions),
                                    $max_file_size,
                                    $max_file_count
                                );
                                ?>
                            </small>
                        </div>
                    </div>
                    <div id="selected-files" class="selected-files"></div>
                </div>
            </div>

            <!-- دکمه‌های فرم -->
            <div class="form-actions">
                <button type="submit" class="msg-submit-btn">
                    <span class="btn-text"><?php _e('ارسال درخواست من', 'msg-system'); ?></span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner"></span>
                        <?php _e('در حال ارسال...', 'msg-system'); ?>
                    </span>
                </button>
                <button type="reset" class="msg-reset-btn"><?php _e('پاک کردن فرم', 'msg-system'); ?></button>
            </div>

            <?php wp_nonce_field('msg_system_nonce', 'message_nonce'); ?>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // متغیرهای عمومی
    var dropzone = $('#attachment-dropzone');
    var fileInput = $('#file-input');
    var selectedFiles = $('#selected-files');
    var maxFileSize = <?php echo $max_file_size * 1024 * 1024; ?>;
    var maxFiles = <?php echo $max_file_count; ?>;
    var allowedTypes = '<?php echo $allowed_extensions; ?>'.split(',').map(function(ext) {
        return '.' + ext.trim();
    });

    // مدیریت انتخاب گروه
    $('#group-select').change(function() {
        var selectedGroup = $(this).val();
        var recipientSelect = $('#recipient-select');
        
        if (selectedGroup) {
            $('.user-selection').slideDown();
            recipientSelect.prop('disabled', true);
            loadGroupUsers(selectedGroup);
        } else {
            $('.user-selection').slideUp();
            recipientSelect.prop('disabled', true);
        }
    });

    // بارگذاری کاربران گروه
    function loadGroupUsers(group) {
        $.ajax({
            url: msgSystemAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_get_group_users',
                group: group,
                nonce: $('#message_nonce').val()
            },
            beforeSend: function() {
                $('#recipient-select').html('<option value="">' + 
                    '<?php _e('در حال بارگذاری...', 'msg-system'); ?>' + '</option>');
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">' + 
                        '<?php _e('لطفا یک کاربر را انتخاب کنید', 'msg-system'); ?>' + '</option>';
                    
                    $.each(response.data, function(id, name) {
                        options += '<option value="' + id + '">' + name + '</option>';
                    });
                    
                    $('#recipient-select')
                        .html(options)
                        .prop('disabled', false);
                }
            }
        });
    }

    // جستجوی کاربر
    $('#user-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('#recipient-select option').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    });

    // مدیریت Drag & Drop
    dropzone
        .on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        })
        .on('dragleave', function() {
            $(this).removeClass('dragover');
        })
        .on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            handleFiles(e.originalEvent.dataTransfer.files);
        })
        .on('click', function() {
            fileInput.click();
        });

    fileInput.change(function() {
        handleFiles(this.files);
    });

    // مدیریت فایل‌ها
    function handleFiles(files) {
        var currentFiles = selectedFiles.children().length;
        var newFiles = Array.from(files);

        if (currentFiles + newFiles.length > maxFiles) {
            alert('<?php _e('حداکثر تعداد فایل مجاز: ', 'msg-system'); ?>' + maxFiles);
            return;
        }

        newFiles.forEach(function(file) {
            var extension = '.' + file.name.split('.').pop().toLowerCase();
            
            if (!allowedTypes.includes(extension)) {
                alert('<?php _e('فرمت فایل مجاز نیست: ', 'msg-system'); ?>' + file.name);
                return;
            }

            if (file.size > maxFileSize) {
                alert('<?php _e('حجم فایل بیشتر از حد مجاز است: ', 'msg-system'); ?>' + file.name);
                return;
            }

            var fileSize = (file.size / (1024 * 1024)).toFixed(2);
            var fileElement = $('<div class="selected-file">' +
                '<span class="file-name">' + file.name + ' (' + fileSize + ' MB)</span>' +
                '<button type="button" class="remove-file">&times;</button>' +
                '</div>');

            selectedFiles.append(fileElement);
        });
    }

    // حذف فایل
    $(document).on('click', '.remove-file', function() {
        $(this).parent().remove();
    });

    // ارسال فرم
    $('#msg-send-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.msg-submit-btn');
        var formData = new FormData(this);
        
        // افزودن محتوای ویرایشگر
        if (typeof tinymce !== 'undefined' && tinymce.get('message_content')) {
            formData.append('message', tinymce.get('message_content').getContent());
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                submitBtn.prop('disabled', true)
                    .find('.btn-text').hide()
                    .siblings('.btn-loading').show();
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('پیام با موفقیت ارسال شد', 'msg-system'); ?>');
                    form[0].reset();
                    if (typeof tinymce !== 'undefined' && tinymce.get('message_content')) {
                        tinymce.get('message_content').setContent('');
                    }
                    selectedFiles.empty();
                    $('.user-selection').slideUp();
                } else {
                    alert('<?php _e('خطا: ', 'msg-system'); ?>' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('خطا در ارسال پیام', 'msg-system'); ?>');
            },
            complete: function() {
                submitBtn.prop('disabled', false)
                    .find('.btn-text').show()
                    .siblings('.btn-loading').hide();
            }
        });
    });
});
</script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var msgSystemAjax = {
    ajaxurl: ajaxurl,
    nonce: '<?php echo wp_create_nonce('msg_system_nonce'); ?>'
};