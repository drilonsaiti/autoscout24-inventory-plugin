(() => {
    'use strict';

    const cfg = window.DinvInventory || {};
    const labels = cfg.labels || {};
    const debounce = (fn, delay = 300) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), delay);
        };
    };

    const parseJsonDataset = (value, fallback = {}) => {
        if (!value) return fallback;
        try {
            const parsed = JSON.parse(value);
            return parsed && typeof parsed === 'object' ? parsed : fallback;
        } catch (_) {
            return fallback;
        }
    };

    const initInventory = (root) => {
        const form = root.querySelector('[data-dinv-form]');

        // Static selections (no filters, sort or pagination) are fully
        // server-rendered and never trigger REST requests.
        if (!form || root.dataset.interactive === '0') return;

        const resultsEl = root.querySelector('[data-dinv-results]');
        const countEl = root.querySelector('[data-dinv-count]');
        const paginationEl = root.querySelector('[data-dinv-pagination]');
        const skeletonEl = root.querySelector('[data-dinv-skeleton]');
        const emptyEl = root.querySelector('[data-dinv-empty]');
        const errorEl = root.querySelector('[data-dinv-error]');
        const loadingEl = root.querySelector('[data-dinv-loading]');
        const sortEl = root.querySelector('[data-dinv-sort]');
        const makeEl = root.querySelector('[data-dinv-make]');
        const dialog = root.querySelector('[data-dinv-dialog]');
        // Instance settings that differ from the site defaults; sent with every
        // REST request so refreshed results match the server-rendered page.
        const instanceConfig = parseJsonDataset(root.dataset.config, {});
        let inventoryVersion = root.dataset.version || '0';
        const instance = (root.dataset.instance || '').replace(/[^a-z0-9_-]/gi, '').toLowerCase();
        const showPagination =
            root.dataset.showPagination !== '0';
        const countLabelEl = root.querySelector('[data-dinv-count-label]');
        const useUrlState = root.dataset.urlState !== '0';
        const presetParams = parseJsonDataset(root.dataset.presetParams, {});
        const presetSort = root.dataset.presetSort || 'newest';
        let controller = null;
        let initialRefreshAllowed = true;
        let currentPage = Number(
            paginationEl?.querySelector('[aria-current="page"]')?.dataset.page || 1
        );
        const initialPage = currentPage;

        /*
         * Keep already loaded pages in memory. This makes Back/Next feel
         * immediate and avoids paying the WordPress REST bootstrap cost again
         * when the visitor returns to a page they have already seen.
         */
        const responseCache = new Map();
        const prefetchPromises = new Map();

        const markupPageNumbers = paginationEl
            ? Array.from(paginationEl.querySelectorAll('[data-page]'))
                .map((button) => Number(button.dataset.page || 0))
                .filter((page) => Number.isFinite(page) && page > 0)
            : [];

        let knownTotalPages = markupPageNumbers.length
            ? Math.max(...markupPageNumbers)
            : 1;

        const genericHistoryKeys = [
            'dinv_category', 'dinv_make', 'dinv_version', 'dinv_fuel',
            'dinv_transmission', 'dinv_body', 'dinv_drive', 'dinv_condition', 'dinv_warranty',
            'dinv_price_from', 'dinv_price_to', 'dinv_year_from', 'dinv_year_to',
            'dinv_mileage_from', 'dinv_mileage_to', 'dinv_power_from', 'dinv_power_to',
            'dinv_page', 'dinv_sort'
        ];

        // Current page URL without inventory parameters; pagination links are
        // built on it so they keep unrelated query arguments (e.g. ?lang=fr).
        const baseUrl = () => {
            const url = new URL(window.location.href);
            Array.from(url.searchParams.keys())
                .filter((key) => key.startsWith('dinv_'))
                .forEach((key) => url.searchParams.delete(key));
            return url.pathname + url.search;
        };

        const historyKey = (key) => {
            if (!instance || !key.startsWith('dinv_')) return key;
            return `dinv_${instance}_${key.slice(5)}`;
        };

        const syncMirrors = () => {
            form.querySelectorAll('[data-mirror-name]').forEach((mirror) => {
                const target = form.querySelector(`[name="${CSS.escape(mirror.dataset.mirrorName)}"]`);
                if (target) target.value = mirror.value;
            });
        };

        const syncAdvancedFromBasic = () => {
            form.querySelectorAll('[data-mirror-name]').forEach((mirror) => {
                const target = form.querySelector(`[name="${CSS.escape(mirror.dataset.mirrorName)}"]`);
                if (target) mirror.value = target.value;
            });
        };

        const buildParams = (page = 1) => {
            const data = new FormData(form);
            const params = new URLSearchParams();
            for (const [key, value] of data.entries()) {
                if (!key.startsWith('dinv_') || key.endsWith('_advanced') || value === '') continue;
                params.set(key, String(value));
            }
            // Always send the sort explicitly. Omitting a value (previously
            // "newest") made the server fall back to the instance default, so
            // choosing "Newest" had no effect when another default was set.
            params.set('dinv_sort', sortEl?.value || presetSort);

            if (page > 1) params.set('dinv_page', String(page));
            return params;
        };

        const setLoading = (loading, interactive = false) => {
            const resultsWrap = root.querySelector('.dinv-results');

            resultsWrap?.setAttribute(
                'aria-busy',
                loading ? 'true' : 'false'
            );

            resultsWrap?.classList.toggle(
                'is-loading',
                Boolean(loading && interactive)
            );

            if (loadingEl) {
                loadingEl.hidden = !(loading && interactive);
            }

            // Keep server-rendered cards visible during background freshness
            // checks. On an actual user interaction the overlay above gives
            // immediate feedback while preserving the layout underneath.
            const hasResults = Boolean(
                resultsEl &&
                resultsEl.children.length
            );

            if (skeletonEl) {
                skeletonEl.hidden =
                    !loading || hasResults;
            }

            if (loading) {
                if (emptyEl) emptyEl.hidden = true;
                if (errorEl) errorEl.hidden = true;
            }
        };

        const updateHistory = (
            params
        ) => {

            if (!useUrlState) {
                return;
            }

            const url =
                new URL(
                    window.location.href
                );

            /*
             * First remove all previous inventory
             * state, including any old dinv_page.
             */
            genericHistoryKeys.forEach(
                (key) => {

                    url.searchParams.delete(
                        historyKey(key)
                    );
                }
            );

            /*
             * Add the current filters and sorting.
             *
             * IMPORTANT:
             * Never persist pagination in the URL.
             */
            params.forEach(
                (
                    value,
                    key
                ) => {

                    // Page 1 and the default sort are implicit.
                    if (
                        (key === 'dinv_page' && value === '1') ||
                        (key === 'dinv_sort' && value === presetSort)
                    ) {
                        return;
                    }

                    url.searchParams.set(
                        historyKey(key),
                        value
                    );
                }
            );

            window.history.pushState(
                {
                    dinv: true,
                    instance
                },
                '',
                `${url.pathname}${url.search}${url.hash}`
            );
        };

        const requestParams = (page = 1) => {
            const params = buildParams(page);

            params.set('v', inventoryVersion);
            if (root.dataset.locale) params.set('dinv_locale', root.dataset.locale);
            if (useUrlState) {
                params.set('dinv_base', baseUrl());
                if (instance) params.set('dinv_instance', instance);
            }
            Object.entries(instanceConfig).forEach(([key, value]) => {
                params.set(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
            });

            return params;
        };

        const requestKey = (params) => params.toString();

        const fetchPageData = async (params, signal = undefined) => {
            const networkParams = new URLSearchParams(params);

            // The inventory version ("v") is part of every request, so the
            // browser or a CDN may cache responses: a new sync changes the URL.
            const response = await fetch(
                `${cfg.restBase}/vehicles?${networkParams.toString()}`,
                {
                    method: 'GET',
                    headers: {Accept: 'application/json'},
                    credentials: 'same-origin',
                    signal
                }
            );

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error('Request failed');
            }

            return data;
        };

        const statusEl = root.querySelector('[data-dinv-status]');

        // One polite announcement per user action, e.g. "24 vehicles".
        const announce = (data) => {
            if (!statusEl || data.total === undefined || data.total === null) return;
            const text = `${Number(data.total).toLocaleString(document.documentElement.lang || undefined)} ${data.countLabel || ''}`.trim();
            statusEl.textContent = '';
            window.setTimeout(() => { statusEl.textContent = text; }, 50);
        };

        const renderData = (data, page, pushState, interactive = true) => {
            if (resultsEl) {
                resultsEl.innerHTML = data.html || '';
                resultsEl.hidden = false;
            }

            if (interactive) announce(data);

            if (paginationEl) {
                paginationEl.innerHTML = showPagination
                    ? (data.pagination || '')
                    : '';
                paginationEl.hidden = !showPagination;
            }

            if (countEl && data.total !== undefined && data.total !== null) {
                countEl.dataset.total = String(data.total);
                countEl.textContent = Number(data.total).toLocaleString(document.documentElement.lang || undefined);
                if (countLabelEl && data.countLabel) countLabelEl.textContent = data.countLabel;
            }

            if (emptyEl && data.total !== undefined && data.total !== null) {
                emptyEl.hidden = Number(data.total || 0) !== 0;
            }

            if (errorEl) {
                errorEl.hidden = true;
            }

            if (skeletonEl) {
                skeletonEl.hidden = true;
            }

            if (Number(data.totalPages) > 0) {
                knownTotalPages = Number(data.totalPages);
            }

            currentPage = page;

            if (pushState) {
                updateHistory(buildParams(page));
            }
        };

        const prefetchPage = (page) => {
            if (
                !cfg.restBase ||
                page < 1 ||
                (knownTotalPages > 0 && page > knownTotalPages)
            ) {
                return Promise.resolve(null);
            }

            const params = requestParams(page);
            const key = requestKey(params);

            if (responseCache.has(key)) {
                return Promise.resolve(responseCache.get(key));
            }

            if (prefetchPromises.has(key)) {
                return prefetchPromises.get(key);
            }

            const promise = fetchPageData(params)
                .then((data) => {
                    responseCache.set(key, data);

                    if (Number(data.totalPages) > 0) {
                        knownTotalPages = Number(data.totalPages);
                    }

                    return data;
                })
                .catch(() => null)
                .finally(() => {
                    prefetchPromises.delete(key);
                });

            prefetchPromises.set(key, promise);
            return promise;
        };

        const schedulePrefetch = (page) => {
            const run = () => {
                if (page > 1) {
                    void prefetchPage(page - 1);
                }

                if (knownTotalPages === 0 || page < knownTotalPages) {
                    void prefetchPage(page + 1);
                }
            };

            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(run, {timeout: 900});
            } else {
                window.setTimeout(run, 250);
            }
        };

        const load = async (
            page = 1,
            pushState = true,
            {interactive = true, forceNetwork = false} = {}
        ) => {
            if (!cfg.restBase) return;

            const params = requestParams(page);
            const key = requestKey(params);

            if (!forceNetwork && responseCache.has(key)) {
                renderData(responseCache.get(key), page, pushState, interactive);
                schedulePrefetch(page);
                return;
            }

            if (controller) {
                controller.abort();
            }

            controller = new AbortController();
            setLoading(true, interactive);

            try {
                let data = null;

                /*
                 * If an idle prefetch for this page is already running, reuse
                 * that exact request instead of starting a duplicate one.
                 */
                if (!forceNetwork && prefetchPromises.has(key)) {
                    data = await prefetchPromises.get(key);
                }

                if (!data) {
                    data = await fetchPageData(
                        params,
                        controller.signal
                    );
                }

                responseCache.set(key, data);
                renderData(data, page, pushState, interactive);
                schedulePrefetch(page);
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                console.error(
                    'AutoScout24 inventory load failed:',
                    error
                );

                if (skeletonEl) {
                    skeletonEl.hidden = true;
                }

                const hasResults = Boolean(
                    resultsEl &&
                    resultsEl.children.length
                );

                // The server-rendered inventory is a valid fallback. Only show a
                // hard error when there are no cards available at all.
                if (!hasResults) {
                    if (paginationEl) {
                        paginationEl.hidden = true;
                    }

                    if (emptyEl) {
                        emptyEl.hidden = true;
                    }

                    if (errorEl) {
                        errorEl.hidden = false;
                    }
                }
            } finally {
                setLoading(false, interactive);
            }
        };

        const restorePresets = async () => {

            /*
             * Reset all filter fields.
             *
             * Sort is NOT inside the filter form,
             * therefore it is restored separately below.
             */
            form.querySelectorAll('input, select').forEach((field) => {

                if (field.hasAttribute('data-mirror-name')) {
                    return;
                }

                if (
                    field instanceof HTMLInputElement &&
                    (
                        field.type === 'checkbox' ||
                        field.type === 'radio'
                    )
                ) {
                    field.checked = false;
                    return;
                }

                if (
                    field instanceof HTMLInputElement ||
                    field instanceof HTMLSelectElement
                ) {
                    field.value = '';
                }
            });

            /*
             * Restore shortcode preset filters.
             */
            Object.entries(presetParams).forEach(
                ([key, value]) => {

                    const field =
                        form.querySelector(
                            `[name="${CSS.escape(key)}"]`
                        );

                    if (!field) {
                        return;
                    }

                    if (
                        field instanceof HTMLInputElement &&
                        (
                            field.type === 'checkbox' ||
                            field.type === 'radio'
                        )
                    ) {
                        field.checked = [
                            '1',
                            'true',
                            'yes',
                            'on'
                        ].includes(
                            String(value).toLowerCase()
                        );

                        return;
                    }

                    field.value = String(value);
                }
            );

            /*
             * IMPORTANT:
             * Restore the shortcode/default sorting.
             *
             * Never leave the select with no selected option.
             */
            if (sortEl) {

                const hasPresetOption =
                    Array.from(sortEl.options).some(
                        (option) =>
                            option.value === presetSort
                    );

                if (hasPresetOption) {
                    sortEl.value = presetSort;
                } else {

                    /*
                     * Defensive fallback.
                     */
                    const defaultOption =
                        Array.from(
                            sortEl.options
                        ).find(
                            (option) =>
                                option.value ===
                                'make_model_asc'
                        );

                    if (defaultOption) {

                        sortEl.value =
                            'make_model_asc';

                    } else if (
                        sortEl.options.length
                    ) {

                        sortEl.selectedIndex = 0;
                    }
                }
            }

            /*
             * Synchronise advanced/basic fields and the custom make picker.
             */
            syncAdvancedFromBasic();
            syncMakePickerFromSelect();
        };
        const loadFromInteraction = (page = 1, pushState = true) => {
            initialRefreshAllowed = false;
            return load(
                page,
                pushState,
                {interactive: true}
            );
        };

        /*
         * Lightweight make-only picker.
         *
         * The data comes from the server-rendered <select>; there is no second
         * The make list is server-rendered; no separate selector bundle or model REST request is needed.
         */
        const makePicker = root.querySelector('[data-dinv-make-picker]');
        const makeTrigger = makePicker?.querySelector('[data-dinv-make-trigger]');
        const makeTriggerText = makePicker?.querySelector('[data-dinv-make-trigger-text]');
        const makePanel = makePicker?.querySelector('[data-dinv-make-panel]');
        const makeSearch = makePicker?.querySelector('[data-dinv-make-search]');
        const makeEmpty = makePicker?.querySelector('[data-dinv-make-empty]');
        const makeOptions = makePicker
            ? Array.from(makePicker.querySelectorAll('[data-dinv-make-option]'))
            : [];

        const normalizeMakeText = (value) =>
            String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLocaleLowerCase();

        const setMakePickerOpen = (open) => {
            if (!makePicker || !makeTrigger || !makePanel) return;

            makePicker.classList.toggle('is-open', Boolean(open));
            makeOptions.forEach((option) => {
                option.tabIndex = option.getAttribute('aria-selected') === 'true' ? 0 : -1;
            });
            makeTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
            makePanel.hidden = !open;

            if (open && makeSearch) {
                window.requestAnimationFrame(() => {
                    makeSearch.focus({preventScroll: true});
                });
            }
        };

        const filterMakeOptions = () => {
            if (!makeSearch) return;

            const query = normalizeMakeText(makeSearch.value);
            let visible = 0;

            makeOptions.forEach((option) => {
                const label = normalizeMakeText(option.dataset.label || option.textContent);
                const show = !query || label.includes(query);
                option.hidden = !show;
                if (show) visible += 1;
            });

            if (makeEmpty) {
                makeEmpty.hidden = visible !== 0;
            }
        };

        const syncMakePickerFromSelect = () => {
            if (!makeEl || !makeTriggerText) return;

            const selectedValue = String(makeEl.value || '');
            let selectedLabel = labels.all || '';

            makeOptions.forEach((option) => {
                const isSelected =
                    String(option.dataset.value || '') === selectedValue;

                option.classList.toggle('is-selected', isSelected);
                option.setAttribute('aria-selected', isSelected ? 'true' : 'false');

                if (isSelected) {
                    selectedLabel =
                        option.dataset.label ||
                        option.querySelector('span')?.textContent ||
                        selectedLabel;
                }
            });

            makeTriggerText.textContent = selectedLabel;
        };

        makeTrigger?.addEventListener('click', () => {
            const open = makePanel ? makePanel.hidden : true;
            setMakePickerOpen(open);
        });

        makeSearch?.addEventListener('input', filterMakeOptions);

        const visibleMakeOptions = () => makeOptions.filter((option) => !option.hidden);

        const focusMakeOption = (index) => {
            const options = visibleMakeOptions();
            if (!options.length) return;
            const target = options[Math.max(0, Math.min(options.length - 1, index))];
            options.forEach((option) => { option.tabIndex = option === target ? 0 : -1; });
            target.focus();
        };

        makeTrigger?.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                setMakePickerOpen(true);
            }
        });

        makeSearch?.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                setMakePickerOpen(false);
                makeTrigger?.focus();
            } else if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusMakeOption(0);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                visibleMakeOptions()[1]?.click();
            }
        });

        // Listbox keyboard support: arrows, Home/End, Escape; Enter/Space
        // activate the focused option (they are buttons).
        makePanel?.addEventListener('keydown', (event) => {
            const options = visibleMakeOptions();
            const index = options.indexOf(document.activeElement);
            if (index === -1) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusMakeOption(index + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index === 0) makeSearch?.focus(); else focusMakeOption(index - 1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                focusMakeOption(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                focusMakeOption(options.length - 1);
            } else if (event.key === 'Escape') {
                event.preventDefault();
                setMakePickerOpen(false);
                makeTrigger?.focus();
            } else if (event.key === 'Tab') {
                setMakePickerOpen(false);
            }
        });

        makeOptions.forEach((option) => {
            option.addEventListener('click', () => {
                if (!makeEl) return;

                const value = String(option.dataset.value || '');
                const changed = makeEl.value !== value;

                makeEl.value = value;
                syncMakePickerFromSelect();

                if (makeSearch) {
                    makeSearch.value = '';
                    filterMakeOptions();
                }

                setMakePickerOpen(false);
                makeTrigger?.focus();

                if (changed) {
                    makeEl.dispatchEvent(
                        new Event('change', {bubbles: true})
                    );
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (
                makePicker &&
                makePanel &&
                !makePanel.hidden &&
                !makePicker.contains(event.target)
            ) {
                setMakePickerOpen(false);
            }
        });

        makeEl?.addEventListener('change', syncMakePickerFromSelect);
        syncMakePickerFromSelect();

        const debouncedLoad = debounce(() => loadFromInteraction(1), 340);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (dialog?.open) {
                syncMirrors();
                dialog.close();
            }
            loadFromInteraction(1);
        });

        form.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) return;
            if (target.closest('[data-dinv-dialog]')) return;
            loadFromInteraction(1);
        });

        form.addEventListener('input', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) return;
            if (target.matches('[data-dinv-make-search]')) return;
            if (target.type === 'search') debouncedLoad();
        });

        sortEl?.addEventListener('change', () => loadFromInteraction(1));

        root.addEventListener('click', async (event) => {
            const pageButton = event.target.closest('[data-page]');
            if (pageButton) {
                event.preventDefault();
                loadFromInteraction(Number(pageButton.dataset.page || 1)).then(() => {
                    // The clicked link was replaced; move focus to the results.
                    resultsEl?.focus({preventScroll: true});
                });
                root.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'start'
                });
                return;
            }

            const advancedButton = event.target.closest('[data-dinv-advanced]');
            if (advancedButton && dialog) {
                syncAdvancedFromBasic();
                if (typeof dialog.showModal === 'function') dialog.showModal();
                else dialog.setAttribute('open', '');
                return;
            }

            if (event.target.closest('[data-dinv-close]') && dialog) {
                dialog.close?.();
                dialog.removeAttribute('open');
                return;
            }

            if (event.target.closest('[data-dinv-reset]')) {
                await restorePresets();
                dialog?.close?.();
                loadFromInteraction(1);
                return;
            }

            if (event.target.closest('[data-dinv-retry]')) loadFromInteraction(1, false);
        });

        dialog?.addEventListener(
            'click',
            (event) => {
                if (
                    event.target ===
                    dialog
                ) {
                    dialog.close();
                }
            }
        );

        /*
         * Prime page 1 from the already server-rendered HTML. Returning from
         * page 2 to page 1 therefore never needs another network request.
         */
        if (resultsEl) {
            const initialParams = requestParams(initialPage);
            const initialTotal = countEl
                ? Number(countEl.dataset.total ?? countEl.textContent ?? 0)
                : resultsEl.children.length;

            responseCache.set(
                requestKey(initialParams),
                {
                    success: true,
                    total: Number.isFinite(initialTotal)
                        ? initialTotal
                        : null,
                    page: initialPage,
                    totalPages: knownTotalPages,
                    html: resultsEl.innerHTML,
                    pagination: paginationEl
                        ? paginationEl.innerHTML
                        : ''
                }
            );
        }

        /*
         * Make sure a page served from a full-page cache does not show an
         * outdated list after a synchronization.
         */
        const scheduleInitialRefresh = () => {
            const refresh = () => {
                if (initialRefreshAllowed && currentPage === initialPage) {
                    void load(
                        initialPage,
                        false,
                        {
                            interactive: false,
                            forceNetwork: true
                        }
                    );
                }
            };

            const run = async () => {
                // HTML rendered moments ago is fresh: no request needed.
                const age = Date.now() / 1000 - Number(root.dataset.rendered || 0);
                if (Math.abs(age) < 300) {
                    schedulePrefetch(currentPage);
                    return;
                }

                // Possibly a page-cache copy: ask for the inventory version
                // (a tiny request) and refresh only when it changed.
                try {
                    const response = await fetch(`${cfg.restBase}/status`, {
                        headers: {Accept: 'application/json'},
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    const status = await response.json();
                    if (String(status.version) !== inventoryVersion) {
                        inventoryVersion = String(status.version);
                        responseCache.clear();
                        refresh();
                        return;
                    }
                } catch (_) {
                    // Keep the rendered results.
                }
                schedulePrefetch(currentPage);
            };

            window.requestAnimationFrame(() => {
                if ('requestIdleCallback' in window) {
                    window.requestIdleCallback(run, {timeout: 1000});
                } else {
                    window.setTimeout(run, 300);
                }
            });
        };

        scheduleInitialRefresh();
    };

    document.querySelectorAll('.dinv-inventory').forEach((root) => {
        initInventory(root);
    });

    window.addEventListener('popstate', () => window.location.reload());
})();
