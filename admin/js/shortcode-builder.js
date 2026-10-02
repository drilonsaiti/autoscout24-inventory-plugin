/*
 * Shortcode builder.
 *
 * Every field is rendered from the settings schema with a data-default
 * attribute (the site-wide value). Only attributes that differ from it are
 * written into the shortcode, so the result stays short and keeps following
 * future changes of the site-wide Display settings.
 */
(function () {
    'use strict';

    var config = window.DinvBuilder || {tag: 'dealer_inventory', copied: 'Copied'};

    function quote(value) {
        return String(value).replace(/["\[\]]/g, '').trim();
    }

    function fieldValue(field) {
        if (field.type === 'checkbox') {
            return field.checked ? 'yes' : 'no';
        }
        return String(field.value || '').trim();
    }

    function build(form) {
        var parts = ['[' + config.tag];

        form.querySelectorAll('[name][data-default]').forEach(function (field) {
            var current = fieldValue(field);
            var initial = String(field.dataset.default || '');
            if (current === initial || (current === '' && field.type !== 'checkbox')) {
                return;
            }
            parts.push(field.name + '="' + quote(current) + '"');
        });

        return parts.join(' ') + ']';
    }

    function init(form) {
        var output = form.querySelector('[data-dinv-shortcode-output]');
        var status = form.querySelector('[data-dinv-copy-status]');

        var update = function () {
            output.value = build(form);
        };

        form.addEventListener('input', update);
        form.addEventListener('change', update);

        form.querySelector('[data-dinv-builder-reset]').addEventListener('click', function () {
            form.querySelectorAll('[name][data-default]').forEach(function (field) {
                if (field.type === 'checkbox') {
                    field.checked = field.dataset.default === 'yes';
                } else {
                    field.value = field.dataset.default || '';
                }
            });
            update();
        });

        form.querySelector('[data-dinv-copy-shortcode]').addEventListener('click', function () {
            var done = function () {
                status.textContent = config.copied;
                window.setTimeout(function () { status.textContent = ''; }, 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(output.value).then(done);
            } else {
                output.select();
                document.execCommand('copy');
                done();
            }
        });

        update();
    }

    document.querySelectorAll('[data-dinv-shortcode-builder]').forEach(init);
})();
