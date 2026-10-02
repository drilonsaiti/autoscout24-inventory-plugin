/* Design screen: color pickers, preset application and preview. */
(function ($) {
    'use strict';

    var ratios = {'3-2': '3 / 2', '4-3': '4 / 3', '16-10': '16 / 10', '16-9': '16 / 9', '1-1': '1 / 1'};
    var fonts = {
        inherit: 'inherit',
        system: 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
        serif: 'Georgia, "Times New Roman", serif'
    };

    function value(name, fallback) {
        var $field = $('[name="' + name + '"]').not('[type="hidden"]');
        return $field.length && $field.val() ? $field.val() : fallback;
    }

    function updatePreview() {
        var $preview = $('[data-dinv-preview-card]');
        if (!$preview.length) {
            return;
        }

        $preview.css({
            '--preview-accent': value('design_accent', '#2457D6'),
            '--preview-accent-hover': value('design_accent_hover', '#1B46A8'),
            '--preview-on-accent': value('design_on_accent', '#FFFFFF'),
            '--preview-bg': value('design_bg', '#FFFFFF'),
            '--preview-bg-alt': value('design_bg_alt', '#F6F7F9'),
            '--preview-panel': value('design_panel', '#F1F3F6'),
            '--preview-panel-2': value('design_panel_2', '#FFFFFF'),
            '--preview-text': value('design_text', '#14171C'),
            '--preview-muted': value('design_muted', '#5B6370'),
            '--preview-border': value('design_border', '#DDE1E7'),
            '--preview-border-hi': value('design_border_hi', '#B6BDC8'),
            '--preview-radius': value('design_radius', '12') + 'px',
            '--preview-radius-lg': value('design_radius_large', '16') + 'px',
            '--preview-radius-sm': value('design_radius_small', '8') + 'px',
            '--preview-font': fonts[value('design_font', 'inherit')] || 'inherit',
            '--preview-image-ratio': ratios[value('design_image_ratio', '4-3')] || '4 / 3'
        });
    }

    function applyPreset() {
        var presets = (window.DinvAdmin && window.DinvAdmin.presets) || {};
        var preset = presets[$('#dinv-design-preset').val()];
        if (!preset) {
            return;
        }

        Object.keys(preset).forEach(function (name) {
            if (name === 'label') {
                return;
            }
            var $field = $('[name="' + name + '"]');
            if ($field.hasClass('dinv-color-field') && typeof $field.wpColorPicker === 'function') {
                $field.wpColorPicker('color', preset[name]);
            } else {
                $field.val(preset[name]);
            }
        });

        updatePreview();
    }

    $(function () {
        if (typeof $.fn.wpColorPicker === 'function') {
            $('.dinv-color-field').wpColorPicker({
                change: function () { window.setTimeout(updatePreview, 0); },
                clear: function () { window.setTimeout(updatePreview, 0); }
            });
        }

        $('[data-dinv-preview], [name^="design_"]').on('input change', updatePreview);
        $('#dinv-apply-preset').on('click', function (event) {
            event.preventDefault();
            applyPreset();
        });
        $('[data-dinv-confirm]').on('click', function (event) {
            // eslint-disable-next-line no-alert
            if (!window.confirm($(this).data('dinv-confirm'))) {
                event.preventDefault();
            }
        });

        updatePreview();
    });
})(jQuery);
