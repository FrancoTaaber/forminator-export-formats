/**
 * Forminator Export Formats - Admin JavaScript
 *
 * @package Forminator_Export_Formats
 */

(function ($) {
    'use strict';

    var FEF = {
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
                }, 500);
            });

            // Also try immediately in case window already loaded
            $(document).ready(function () {
                setTimeout(function () {
                    self.hijackExportButton();
                    self.bindModalEvents();
                    self.bindFormatSelection();
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
                if (e.key === 'Escape' && $('#forminator-export-formats-modal').hasClass('sui-active')) {
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
            });

            // Radio change
            $(document).off('change.fef-radio').on('change.fef-radio', 'input[name="export_format"]', function () {
                var format = $(this).val();
                $('.fef-format-option').removeClass('selected');
                $(this).closest('.fef-format-option').addClass('selected');
                self.loadFormatOptions(format);
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
