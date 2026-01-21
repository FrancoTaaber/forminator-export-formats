/**
 * Forminator Export Formats - Admin JavaScript
 * 
 * @package Forminator_Export_Formats
 * @version 1.1.0
 */

(function ($) {
    'use strict';

    var FEF = {
        // Storage key for remembering last format
        STORAGE_KEY: 'fef_last_format',

        /**
         * Initialize
         */
        init: function () {
            var self = this;

            // Wait for page to fully load and Forminator to initialize
            $(window).on('load', function () {
                setTimeout(function () {
                    self.hijackExportButton();
                    self.bindModalEvents();
                    self.bindFormatSelection();
                    self.bindExportSubmit();
                    self.restoreLastFormat();
                }, 500);
            });

            // Also try immediately in case window already loaded
            $(document).ready(function () {
                setTimeout(function () {
                    self.hijackExportButton();
                    self.bindModalEvents();
                    self.bindFormatSelection();
                    self.bindExportSubmit();
                    self.restoreLastFormat();
                }, 1000);
            });
        },

        /**
         * Hijack the original export button
         */
        hijackExportButton: function () {
            // The exact selector from Forminator's entries page
            var $btn = $('a.wpmudev-open-modal[data-modal="exports-schedule"]');

            if (!$btn.length) {
                console.log('FEF: Export button not found (using wpmudev-open-modal selector)');
                // Try alternative
                $btn = $('a.sui-button-ghost[data-modal="exports-schedule"]');
            }

            if (!$btn.length) {
                console.log('FEF: Export button not found (using sui-button-ghost selector)');
                // Try by text content
                $btn = $('a.sui-button:contains("Export")').filter(function () {
                    return $(this).text().trim() === 'Export' ||
                        $(this).find('.sui-icon-paperclip').length > 0;
                });
            }

            if ($btn.length) {
                console.log('FEF: Export button found, hijacking...');

                // Remove Forminator's event handlers completely
                $btn.off('click');
                $btn.removeAttr('data-modal');
                $btn.removeClass('wpmudev-open-modal');

                // Prevent default and stop propagation
                $btn.on('click.fef', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    console.log('FEF: Export button clicked');

                    // Update form_id from URL
                    var urlParams = new URLSearchParams(window.location.search);
                    var formId = urlParams.get('form_id');
                    var formType = urlParams.get('form_type') || 'forminator_forms';

                    if (formId) {
                        $('#fef-export-form input[name="form_id"]').val(formId);
                    }
                    $('#fef-export-form input[name="form_type"]').val(formType);

                    // Open our modal
                    FEF.openModal();

                    return false;
                });

                console.log('FEF: Export button handler attached successfully');
            } else {
                console.log('FEF: No export button found on this page');
            }
        },

        /**
         * Open the export modal
         */
        openModal: function () {
            var $modal = $('#forminator-export-formats-modal');
            if ($modal.length) {
                $modal.fadeIn(200);
                this.restoreLastFormat();
                console.log('FEF: Modal opened');
            } else {
                console.log('FEF: Modal element not found');
            }
        },

        /**
         * Close the export modal
         */
        closeModal: function () {
            var $modal = $('#forminator-export-formats-modal');
            $modal.fadeOut(200);
            this.hideLoading();
        },

        /**
         * Bind modal events
         */
        bindModalEvents: function () {
            var self = this;

            // Close button
            $(document).off('click.fef-close').on('click.fef-close', '.fef-close-modal, [data-modal-close]', function (e) {
                e.preventDefault();
                self.closeModal();
            });

            // Close on overlay click
            $('#forminator-export-formats-modal').off('click.fef-overlay').on('click.fef-overlay', function (e) {
                if (e.target === this || $(e.target).hasClass('sui-modal-content')) {
                    self.closeModal();
                }
            });

            // Close on ESC
            $(document).off('keydown.fef-esc').on('keydown.fef-esc', function (e) {
                if (e.key === 'Escape' && $('#forminator-export-formats-modal').is(':visible')) {
                    self.closeModal();
                }
            });
        },

        /**
         * Bind format selection events
         */
        bindFormatSelection: function () {
            var self = this;

            // Format card click
            $(document).off('click.fef-format').on('click.fef-format', '.fef-format-option', function (e) {
                var $option = $(this);
                var $radio = $option.find('input[type="radio"]');

                // Update visual state
                $('.fef-format-option').removeClass('selected');
                $option.addClass('selected');

                // Check the radio
                $radio.prop('checked', true);

                // Load format options
                var format = $radio.val();
                self.loadFormatOptions(format);

                // Save to localStorage
                self.saveLastFormat(format);
            });

            // Radio change
            $(document).off('change.fef-radio').on('change.fef-radio', 'input[name="export_format"]', function () {
                var format = $(this).val();
                $('.fef-format-option').removeClass('selected');
                $(this).closest('.fef-format-option').addClass('selected');
                self.loadFormatOptions(format);
                self.saveLastFormat(format);
            });
        },

        /**
         * Bind export form submit
         */
        bindExportSubmit: function () {
            var self = this;

            $(document).off('submit.fef-export').on('submit.fef-export', '#fef-export-form', function () {
                self.showLoading();

                // Show success message after a delay (form submits normally)
                setTimeout(function () {
                    self.hideLoading();
                    self.showNotification('success', 'Export started! Your download should begin shortly.');
                }, 1500);
            });
        },

        /**
         * Save last selected format to localStorage
         */
        saveLastFormat: function (format) {
            try {
                localStorage.setItem(this.STORAGE_KEY, format);
            } catch (e) {
                console.log('FEF: Could not save format to localStorage');
            }
        },

        /**
         * Restore last selected format from localStorage
         */
        restoreLastFormat: function () {
            try {
                var lastFormat = localStorage.getItem(this.STORAGE_KEY);
                if (lastFormat) {
                    var $radio = $('input[name="export_format"][value="' + lastFormat + '"]');
                    if ($radio.length && !$radio.prop('disabled')) {
                        $radio.prop('checked', true);
                        $('.fef-format-option').removeClass('selected');
                        $radio.closest('.fef-format-option').addClass('selected');
                        this.loadFormatOptions(lastFormat);
                        console.log('FEF: Restored last format:', lastFormat);
                    }
                }
            } catch (e) {
                console.log('FEF: Could not restore format from localStorage');
            }
        },

        /**
         * Show loading overlay
         */
        showLoading: function () {
            var $modal = $('#forminator-export-formats-modal');
            if (!$modal.find('.fef-loading-overlay').length) {
                $modal.find('.fef-modal-body').append(
                    '<div class="fef-loading-overlay">' +
                    '<div class="fef-loading-spinner">' +
                    '<span class="spinner is-active"></span>' +
                    '<p>Exporting...</p>' +
                    '</div>' +
                    '</div>'
                );
            }
            $modal.find('.fef-loading-overlay').fadeIn(200);
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function () {
            $('.fef-loading-overlay').fadeOut(200);
        },

        /**
         * Show notification
         */
        showNotification: function (type, message) {
            var $notice = $(
                '<div class="notice notice-' + type + ' is-dismissible fef-notice">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"></button>' +
                '</div>'
            );

            // Insert after the page title
            var $target = $('.wrap h1').first();
            if ($target.length) {
                $target.after($notice);
            } else {
                $('body').prepend($notice);
            }

            // Auto-dismiss after 5 seconds
            setTimeout(function () {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 5000);

            // Dismiss button
            $notice.find('.notice-dismiss').on('click', function () {
                $notice.fadeOut(300, function () {
                    $(this).remove();
                });
            });
        },

        /**
         * Load format options via AJAX
         */
        loadFormatOptions: function (format) {
            var $container = $('#fef-format-options');
            var $content = $('#fef-format-options-content');

            if (typeof forminatorExportFormats === 'undefined') {
                console.log('FEF: forminatorExportFormats not defined');
                return;
            }

            // Show loading
            $content.html('<span class="spinner is-active" style="float:none;"></span>');
            $container.show();

            $.ajax({
                url: forminatorExportFormats.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'forminator_export_formats_get_options',
                    nonce: forminatorExportFormats.nonce,
                    format: format
                },
                success: function (response) {
                    if (response.success && response.data.html) {
                        $content.html(response.data.html);
                        if (response.data.html.indexOf('No additional options') !== -1) {
                            $container.hide();
                        }
                    } else {
                        $container.hide();
                    }
                },
                error: function () {
                    $container.hide();
                }
            });
        }
    };

    // Initialize
    FEF.init();

    // Expose globally for debugging
    window.ForminatorExportFormats = FEF;

})(jQuery);

