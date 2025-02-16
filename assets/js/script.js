/**
 * اسکریپتهای اصلی سیستم پیامرسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @version 1.0.0
 * @since 2025-02-15 14:42:32
 */

(function($) {
    'use strict';

    // تنظیمات پیشفرض
    const defaults = {
        maxFileSize: msgSystemVars.maxFileSize || 5, // مگابایت
        maxFiles: msgSystemVars.maxFiles || 3,
        allowedTypes: ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        refreshInterval: 60000 // یک دقیقه
    };

    // کلاس اصلی سیستم پیامرسانی
    class MessagingSystem {
        constructor() {
            this.initializeElements();
            this.attachEventListeners();
            this.setupAutoRefresh();
        }

        // مقداردهی اولیه المانها
        initializeElements() {
            this.elements = {
                container: $('.msg-system-container'),
                messagesList: $('.messages-list'),
                searchInput: $('#message-search'),
                dateFilter: $('#date-filter'),
                statusFilter: $('#status-filter'),
                bulkActions: $('#bulk-action'),
                messageModal: $('#message-modal'),
                replyModal: $('#reply-modal')
            };

            // آپلودر فایل
            if ($('#message-attachments').length) {
                this.initializeFileUploader();
            }
        }

        // راه‌اندازی آپلودر فایل
        initializeFileUploader() {
            const self = this;
            const dropZone = $('#attachment-dropzone');
            const fileInput = $('#message-attachments');
            const preview = $('#attachment-preview');

            dropZone.on('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('dragover');
            }).on('dragleave drop', function(e) {
                e.preventDefault();
                $(this).removeClass('dragover');
            });

            fileInput.on('change', function(e) {
                self.handleFileSelect(e.target.files);
            });

            dropZone.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                self.handleFileSelect(files);
            });
        }

        // مدیریت فایلهای انتخاب شده
        handleFileSelect(files) {
            const self = this;
            const totalFiles = files.length;
            
            // بررسی تعداد فایلها
            if (totalFiles > defaults.maxFiles) {
                alert(msgSystemVars.i18n.tooManyFiles);
                return;
            }

            // بررسی هر فایل
            Array.from(files).forEach(file => {
                // بررسی سایز
                if (file.size > defaults.maxFileSize * 1024 * 1024) {
                    alert(msgSystemVars.i18n.fileTooBig);
                    return;
                }

                // بررسی نوع فایل
                const extension = file.name.split('.').pop().toLowerCase();
                if (!defaults.allowedTypes.includes(extension)) {
                    alert(msgSystemVars.i18n.invalidFileType);
                    return;
                }

                // نمایش پیشنمایش
                self.addFilePreview(file);
            });
        }

        // افزودن پیشنمایش فایل
        addFilePreview(file) {
            const reader = new FileReader();
            const preview = $('#attachment-preview');
            
            reader.onload = function(e) {
                const isImage = file.type.startsWith('image/');
                const html = `
                    <div class="attachment-item" data-name="${file.name}">
                        ${isImage ? `<img src="${e.target.result}" alt="${file.name}">` : ''}
                        <div class="attachment-info">
                            <span class="attachment-name">${file.name}</span>
                            <span class="attachment-size">${this.formatFileSize(file.size)}</span>
                        </div>
                        <button type="button" class="remove-attachment">&times;</button>
                    </div>
                `;
                preview.append(html);
            }.bind(this);

            reader.readAsDataURL(file);
        }

        // فرمت سایز فایل
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // افزودن event listener ها
        attachEventListeners() {
            const self = this;

            // جستجو
            this.elements.searchInput.on('input', this.debounce(function() {
                self.filterMessages();
            }, 300));

            // فیلترها
            this.elements.dateFilter.on('change', () => this.filterMessages());
            this.elements.statusFilter.on('change', () => this.filterMessages());

            // عملیات گروهی
            this.elements.bulkActions.on('change', function() {
                const action = $(this).val();
                if (action) {
                    self.handleBulkAction(action);
                }
            });

            // مشاهده پیام
            $(document).on('click', '.view-message', function(e) {
                e.preventDefault();
                const messageId = $(this).closest('.message-item').data('id');
                self.viewMessage(messageId);
            });

            // حذف پیام
            $(document).on('click', '.delete-message', function(e) {
                e.preventDefault();
                if (confirm(msgSystemVars.i18n.confirmDelete)) {
                    const messageId = $(this).closest('.message-item').data('id');
                    self.deleteMessage(messageId);
                }
            });

            // پاسخ به پیام
            $(document).on('click', '.reply-message', function(e) {
                e.preventDefault();
                const messageId = $(this).closest('.message-item').data('id');
                self.openReplyModal(messageId);
            });

            // بستن مودال‌ها
            $('.modal-close, .close-modal').on('click', function() {
                $(this).closest('.msg-modal').fadeOut();
            });

            // حذف پیوست
            $(document).on('click', '.remove-attachment', function() {
                $(this).closest('.attachment-item').remove();
            });
        }

        // فیلتر کردن پیام‌ها
        filterMessages() {
            const searchTerm = this.elements.searchInput.val().toLowerCase();
            const dateFilter = this.elements.dateFilter.val();
            const statusFilter = this.elements.statusFilter.val();

            $('.message-item').each(function() {
                const $message = $(this);
                let show = true;

                // فیلتر جستجو
                if (searchTerm) {
                    const text = $message.text().toLowerCase();
                    show = text.includes(searchTerm);
                }

                // فیلتر تاریخ
                if (show && dateFilter) {
                    const messageDate = new Date($message.data('date'));
                    const now = new Date();
                    
                    switch(dateFilter) {
                        case 'today':
                            show = messageDate.toDateString() === now.toDateString();
                            break;
                        case 'week':
                            const weekAgo = new Date(now.setDate(now.getDate() - 7));
                            show = messageDate >= weekAgo;
                            break;
                        case 'month':
                            const monthAgo = new Date(now.setMonth(now.getMonth() - 1));
                            show = messageDate >= monthAgo;
                            break;
                    }
                }

                // فیلتر وضعیت
                if (show && statusFilter) {
                    show = $message.hasClass(statusFilter);
                }

                $message.toggle(show);
            });
        }

        // مدیریت عملیات گروهی
        handleBulkAction(action) {
            const selectedIds = $('.message-select:checked').map(function() {
                return $(this).val();
            }).get();

            if (!selectedIds.length) {
                alert(msgSystemVars.i18n.selectMessage);
                return;
            }

            switch(action) {
                case 'delete':
                    if (confirm(msgSystemVars.i18n.confirmBulkDelete)) {
                        this.deleteBulkMessages(selectedIds);
                    }
                    break;
                case 'mark-read':
                    this.markMessagesAsRead(selectedIds);
                    break;
            }
        }

        // حذف گروهی پیام‌ها
        deleteBulkMessages(messageIds) {
            const self = this;
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_delete_bulk_messages',
                    message_ids: messageIds,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        messageIds.forEach(id => {
                            $(`.message-item[data-id="${id}"]`).fadeOut(function() {
                                $(this).remove();
                            });
                        });
                        
                        if ($('.message-item').length === messageIds.length) {
                            location.reload();
                        }
                    } else {
                        alert(msgSystemVars.i18n.deleteError);
                    }
                },
                error: function() {
                    alert(msgSystemVars.i18n.deleteError);
                }
            });
        }

        // علامت‌گذاری گروهی پیام‌ها به عنوان خوانده شده
        markMessagesAsRead(messageIds) {
            const self = this;
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_mark_bulk_read',
                    message_ids: messageIds,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        let unreadCount = 0;
                        messageIds.forEach(id => {
                            const $message = $(`.message-item[data-id="${id}"]`);
                            if ($message.hasClass('unread')) {
                                $message.removeClass('unread').addClass('read');
                                unreadCount++;
                            }
                        });
                        self.updateUnreadCount(-unreadCount);
                    } else {
                        alert(msgSystemVars.i18n.markReadError);
                    }
                },
                error: function() {
                    alert(msgSystemVars.i18n.markReadError);
                }
            });
        }

        // نمایش پیام
        viewMessage(messageId) {
            const self = this;
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_get_message',
                    message_id: messageId,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.elements.messageModal.find('.modal-body').html(response.data);
                        self.elements.messageModal.fadeIn();
                        
                        // علامتگذاری به عنوان خوانده شده
                        self.markMessageAsRead(messageId);
                    }
                }
            });
        }

        // باز کردن مودال پاسخ
        openReplyModal(messageId) {
            const self = this;
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_get_reply_form',
                    message_id: messageId,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.elements.replyModal.find('.modal-body').html(response.data);
                        self.elements.replyModal.fadeIn();
                        
                        // راه‌اندازی آپلودر فایل در فرم پاسخ
                        if ($('#reply-attachments').length) {
                            self.initializeFileUploader();
                        }

                        // ارسال فرم پاسخ
                        $('#reply-form').on('submit', function(e) {
                            e.preventDefault();
                            self.submitReply($(this), messageId);
                        });
                    }
                }
            });
        }

        // ارسال پاسخ
        submitReply(form, messageId) {
            const self = this;
            const formData = new FormData(form[0]);
            
            formData.append('action', 'msg_system_submit_reply');
            formData.append('message_id', messageId);
            formData.append('nonce', msgSystemVars.nonce);

            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    form.addClass('msg-system-loading');
                },
                success: function(response) {
                    if (response.success) {
                        self.elements.replyModal.fadeOut();
                        // بروزرسانی لیست پیام‌ها
                        self.refreshMessages();
                        alert(msgSystemVars.i18n.replySuccess);
                    } else {
                        alert(response.data.message || msgSystemVars.i18n.replyError);
                    }
                },
                error: function() {
                    alert(msgSystemVars.i18n.replyError);
                },
                complete: function() {
                    form.removeClass('msg-system-loading');
                }
            });
        }

// علامتگذاری پیام به عنوان خوانده شده
        markMessageAsRead(messageId) {
            const self = this;
            const messageElement = $(`.message-item[data-id="${messageId}"]`);
            
            if (messageElement.hasClass('unread')) {
                $.ajax({
                    url: msgSystemVars.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'msg_system_mark_as_read',
                        message_id: messageId,
                        nonce: msgSystemVars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            messageElement.removeClass('unread').addClass('read');
                            self.updateUnreadCount(-1);
                        } else {
                            alert(msgSystemVars.i18n.markReadError);
                        }
                    },
                    error: function() {
                        alert(msgSystemVars.i18n.markReadError);
                    }
                });
            }
        }

        // بهروزرسانی تعداد پیامهای نخوانده
        updateUnreadCount(change) {
            const countElement = $('.unread-count');
            const currentCount = parseInt(countElement.text()) || 0;
            const newCount = Math.max(0, currentCount + change);
            countElement.text(newCount);
        }

        // حذف پیام
        deleteMessage(messageId) {
            const self = this;
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_delete_message',
                    message_id: messageId,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $(`.message-item[data-id="${messageId}"]`).fadeOut(function() {
                            $(this).remove();
                            if ($('.message-item').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        alert(msgSystemVars.i18n.deleteError);
                    }
                },
                error: function() {
                    alert(msgSystemVars.i18n.deleteError);
                }
            });
        }

        // تابع debounce برای بهینهسازی جستجو
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // بهروزرسانی خودکار
        setupAutoRefresh() {
            if (this.elements.messagesList.length) {
                setInterval(() => {
                    this.refreshMessages();
                }, defaults.refreshInterval);
            }
        }

        // بهروزرسانی لیست پیامها
        refreshMessages() {
            const self = this;
            const lastUpdate = this.elements.messagesList.data('last-update');
            
            $.ajax({
                url: msgSystemVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'msg_system_check_updates',
                    last_update: lastUpdate,
                    nonce: msgSystemVars.nonce
                },
                success: function(response) {
                    if (response.success && response.data.hasUpdates) {
                        self.elements.messagesList.html(response.data.html);
                        self.elements.messagesList.data('last-update', response.data.timestamp);
                    }
                }
            });
        }
    }

    // راه‌اندازی سیستم
    $(document).ready(function() {
        window.msgSystem = new MessagingSystem();
    });

})(jQuery);