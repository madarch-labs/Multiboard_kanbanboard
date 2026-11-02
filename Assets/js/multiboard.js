(function () {
    'use strict';

    var STORAGE_KEY = 'multiboard_filters_hidden';

    function form() {
        return document.getElementById('multiboard-filters');
    }

    function items(group) {
        return form().querySelectorAll('.js-multiboard-item[data-group="' + group + '"]');
    }

    function storage(value) {
        try {
            if (value === undefined) {
                return window.localStorage.getItem(STORAGE_KEY);
            }
            window.localStorage.setItem(STORAGE_KEY, value);
        } catch (e) {
            return null;
        }
    }

    function onSubmit() {
        // Собираем выбранные чекбоксы в одно поле со списком через запятую.
        // Если выбрано всё — параметр не передаём (значит «все», в т.ч. новые проекты/колонки).
        form().querySelectorAll('.js-multiboard-value').forEach(function (input) {
            var all = items(input.dataset.group);
            var checked = [];

            all.forEach(function (checkbox) {
                if (checkbox.checked) {
                    checked.push(checkbox.value);
                }
            });

            if (checked.length === all.length) {
                input.disabled = true;
            } else {
                input.disabled = false;
                input.value = checked.length ? checked.join(',') : input.dataset.none;
            }
        });

        // Значения по умолчанию в URL не пишем.
        form().querySelectorAll('.js-multiboard-default').forEach(function (input) {
            input.disabled = input.value.trim() === input.dataset.default;
        });
    }

    function copyLink(link) {
        var url = window.location.href;
        var done = function () {
            var label = link.lastChild;
            var original = label.textContent;
            label.textContent = link.dataset.copied;
            setTimeout(function () { label.textContent = original; }, 1500);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done);
        } else {
            var input = document.createElement('textarea');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            done();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (! form()) {
            return;
        }

        if (storage() === '1') {
            form().classList.add('multiboard-filters-hidden');
        }

        form().addEventListener('submit', onSubmit);

        document.addEventListener('click', function (event) {
            var target = event.target.closest('.js-multiboard-check, .js-multiboard-toggle-filters, .js-multiboard-copy-link');

            if (! target) {
                return;
            }

            event.preventDefault();

            if (target.classList.contains('js-multiboard-check')) {
                items(target.dataset.group).forEach(function (checkbox) {
                    checkbox.checked = target.dataset.checked === '1';
                });
            } else if (target.classList.contains('js-multiboard-toggle-filters')) {
                var hidden = form().classList.toggle('multiboard-filters-hidden');
                storage(hidden ? '1' : '0');
            } else {
                copyLink(target);
            }
        });
    });
})();
