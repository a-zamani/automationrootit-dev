/**
 * اسکریپت‌های بخش مدیریت سیستم پیام‌رسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-15 14:33:12
 */

(function($) {
    'use strict';

    // متغیرهای عمومی
    const MSG_System_Admin = {
        init: function() {
            this.initLabels();
            this.initStats();
            this.initMessages();
            this.initUploads();
            this.initFilters();
        },

        // مدیریت برچسب‌ها
        initLabels: function() {
            // افزودن برچسب جدید
            $('#add-label-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const data = {
                    action: 'msg_system_add_label',
                    nonce: msgSystemAdmin.nonce,
                    text: $('#label-text').val(),
                    color: $('#label-color').val()
                };

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: data,
                    beforeSend: function() {
                        $form.addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || msgSystemAdmin.saveError);
                        }
                    },
                    error: function() {
                        alert(msgSystemAdmin.saveError);
                    },
                    complete: function() {
                        $form.removeClass('msg-system-loading');
                    }
                });
            });

            // حذف برچسب
            $('.delete-label').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm(msgSystemAdmin.confirmDelete)) {
                    return;
                }

                const $button = $(this);
                const labelId = $button.data('id');

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_delete_label',
                        nonce: msgSystemAdmin.nonce,
                        label_id: labelId
                    },
                    beforeSend: function() {
                        $button.addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('tr').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || msgSystemAdmin.deleteError);
                        }
                    },
                    error: function() {
                        alert(msgSystemAdmin.deleteError);
                    },
                    complete: function() {
                        $button.removeClass('msg-system-loading');
                    }
                });
            });
        },

        // مدیریت آمار
        initStats: function() {
            const updateStats = function() {
                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_get_stats',
                        nonce: msgSystemAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            Object.keys(response.data).forEach(function(key) {
                                $(`.stat-${key} .stat-number`).text(response.data[key]);
                            });
                        }
                    }
                });
            };

            // بروزرسانی خودکار آمار هر 5 دقیقه
            setInterval(updateStats, 300000);
        },

        // مدیریت پیام‌ها
        initMessages: function() {
            // علامت‌گذاری همه به عنوان خوانده شده
            $('.mark-all-read').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_mark_all_read',
                        nonce: msgSystemAdmin.nonce
                    },
                    beforeSend: function() {
                        $button.addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || msgSystemAdmin.saveError);
                        }
                    },
                    error: function() {
                        alert(msgSystemAdmin.saveError);
                    },
                    complete: function() {
                        $button.removeClass('msg-system-loading');
                    }
                });
            });

            // حذف پیام
            $('.delete-message').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm(msgSystemAdmin.confirmDelete)) {
                    return;
                }

                const $button = $(this);
                const messageId = $button.data('id');

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_delete_message',
                        nonce: msgSystemAdmin.nonce,
                        message_id: messageId
                    },
                    beforeSend: function() {
                        $button.addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.closest('tr').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert(response.data.message || msgSystemAdmin.deleteError);
                        }
                    },
                    error: function() {
                        alert(msgSystemAdmin.deleteError);
                    },
                    complete: function() {
                        $button.removeClass('msg-system-loading');
                    }
                });
            });

            // مشاهده پیام در مودال
            $('.view-message').on('click', function(e) {
                e.preventDefault();
                
                const messageId = $(this).data('id');
                const $modal = $('#message-modal');

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_get_message',
                        nonce: msgSystemAdmin.nonce,
                        message_id: messageId
                    },
                    beforeSend: function() {
                        $modal.find('.message-content').html('<div class="msg-system-loading"></div>');
                        $modal.fadeIn(300);
                    },
                    success: function(response) {
                        if (response.success) {
                            $modal.find('.message-content').html(response.data.content);
                        } else {
                            $modal.find('.message-content').html(
                                '<div class="msg-system-error">' + 
                                (response.data.message || msgSystemAdmin.loadError) + 
                                '</div>'
                            );
                        }
                    },
                    error: function() {
                        $modal.find('.message-content').html(
                            '<div class="msg-system-error">' + msgSystemAdmin.loadError + '</div>'
                        );
                    }
                });
            });

            // بستن مودال
            $('.msg-close').on('click', function() {
                $('#message-modal').fadeOut(300);
            });
        },

        // مدیریت آپلود فایل‌ها
        initUploads: function() {
            const $uploadArea = $('.msg-system-upload');
            
            // رویدادهای Drag & Drop
            $uploadArea.on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });

            $uploadArea.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });

            $uploadArea.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                handleFiles(files);
            });

            // آپلود با کلیک
            $('#file-input').on('change', function() {
                handleFiles(this.files);
            });

            function handleFiles(files) {
                const formData = new FormData();
                formData.append('action', 'msg_system_upload_files');
                formData.append('nonce', msgSystemAdmin.nonce);

                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $uploadArea.addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            updateAttachmentsList(response.data.attachments);
                        } else {
                            alert(response.data.message || msgSystemAdmin.uploadError);
                        }
                    },
                    error: function() {
                        alert(msgSystemAdmin.uploadError);
                    },
                    complete: function() {
                        $uploadArea.removeClass('msg-system-loading');
                    }
                });
            }

            function updateAttachmentsList(attachments) {
                const $list = $('.attachments-list');
                attachments.forEach(function(attachment) {
                    const $item = $(
                        `<div class="attachment-item">
                            <span class="attachment-name">${attachment.name}</span>
                            <span class="attachment-size">${attachment.size}</span>
                            <button type="button" class="remove-attachment" data-id="${attachment.id}">
                                ${msgSystemAdmin.removeText}
                            </button>
                        </div>`
                    );
                    $list.append($item);
                });
            }
        },

        // مدیریت فیلترها
        initFilters: function() {
            const $filterForm = $('.msg-filters-form');
            
            // اعمال فیلترها با Ajax
            $filterForm.on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const data = $form.serialize() + '&action=msg_system_filter_messages&nonce=' + msgSystemAdmin.nonce;

                $.ajax({
                    url: msgSystemAdmin.ajaxurl,
                    type: 'POST',
                    data: data,
                    beforeSend: function() {
                        $('.messages-table').addClass('msg-system-loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.messages-table tbody').html(response.data.html);
                            updatePagination(response.data.pagination);
                        }
                    },
                    complete: function() {
                        $('.messages-table').removeClass('msg-system-loading');
                    }
                });
            });

            function updatePagination(paginationHtml) {
                $('.msg-pagination').html(paginationHtml);
            }
        }
    };

    // راه‌اندازی اسکریپت‌ها
    $(document).ready(function() {
        MSG_System_Admin.init();
    });

})(jQuery);