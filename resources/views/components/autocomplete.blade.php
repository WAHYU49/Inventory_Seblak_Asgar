<style>
    .main-content .autocomplete {
        position: relative;
    }

    .main-content .autocomplete-input {
        width: 100%;
    }

    .main-content .autocomplete-suggestions {
        position: absolute;
        z-index: 20;
        top: calc(100% + 2px);
        right: 0;
        left: 0;
        max-height: 240px;
        overflow-y: auto;
        border: 1px solid #d7dfd9;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .main-content .autocomplete-option {
        display: block;
        width: 100%;
        padding: 9px 12px;
        border: 0;
        border-bottom: 1px solid #edf1ee;
        background: #fff;
        color: #26352c;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }

    .main-content .autocomplete-option:hover,
    .main-content .autocomplete-option[aria-selected="true"] {
        background: #f3f6f4;
    }

    .main-content .autocomplete-option-meta {
        display: block;
        margin-top: 2px;
        color: #526258;
        font-size: 12px;
    }

    .main-content .autocomplete-message {
        padding: 9px 12px;
        color: #526258;
        font-size: 14px;
    }
</style>

<script>
    (() => {
        const containerSelector = '[data-autocomplete]';
        const states = new WeakMap();
        let nextOptionId = 0;

        function stateFor(container) {
            let state = states.get(container);

            if (!state) {
                state = {
                    container: container,
                    input: container.querySelector('[data-autocomplete-input]'),
                    list: container.querySelector('[role="listbox"]'),
                    timer: null,
                    controller: null,
                    items: [],
                    activeIndex: -1
                };
                states.set(container, state);
            }

            return state;
        }

        function closeSuggestions(state) {
            window.clearTimeout(state.timer);
            if (state.controller) {
                state.controller.abort();
            }
            state.list.hidden = true;
            state.input.setAttribute('aria-expanded', 'false');
            state.input.removeAttribute('aria-activedescendant');
            state.activeIndex = -1;
        }

        function clearTargetSelection(state) {
            const targetId = state.container.dataset.autocompleteTarget;

            if (!targetId) {
                return;
            }

            const select = document.getElementById(targetId);

            if (!select) {
                return;
            }

            Array.from(select.options).forEach(option => {
                if (option.value !== '') {
                    option.remove();
                }
            });

            if (select.options.length > 0) {
                select.selectedIndex = 0;
            }
        }

        function renderSuggestions(state, items, message) {
            state.items = items;
            state.activeIndex = -1;
            state.list.replaceChildren();

            if (message) {
                const status = document.createElement('div');
                status.className = 'autocomplete-message';
                status.setAttribute('role', 'status');
                status.textContent = message;
                state.list.appendChild(status);
            } else {
                items.forEach((item, index) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'autocomplete-option';
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');
                    option.id = 'autocomplete-option-' + nextOptionId++;
                    option.dataset.autocompleteIndex = String(index);

                    const name = document.createElement('span');
                    name.textContent = item.name;
                    option.appendChild(name);

                    const metaField = state.container.dataset.autocompleteMeta;
                    if (metaField && item[metaField]) {
                        const meta = document.createElement('span');
                        meta.className = 'autocomplete-option-meta';
                        meta.textContent = item[metaField];
                        option.appendChild(meta);
                    }

                    state.list.appendChild(option);
                });
            }

            state.list.hidden = false;
            state.input.setAttribute('aria-expanded', 'true');
        }

        function setActiveOption(state, index) {
            const options = Array.from(
                state.list.querySelectorAll('[data-autocomplete-index]')
            );

            if (options.length === 0) {
                return;
            }

            state.activeIndex = (index + options.length) % options.length;

            options.forEach((option, optionIndex) => {
                const isActive = optionIndex === state.activeIndex;
                option.setAttribute('aria-selected', String(isActive));

                if (isActive) {
                    state.input.setAttribute('aria-activedescendant', option.id);
                    option.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        function chooseSuggestion(state, index) {
            const item = state.items[index];

            if (!item) {
                return;
            }

            state.input.value = item.name;

            const targetId = state.container.dataset.autocompleteTarget;
            if (targetId) {
                const select = document.getElementById(targetId);

                if (select) {
                    const placeholder = Array.from(select.options)
                        .find(option => option.value === '');
                    select.replaceChildren(
                        placeholder ? placeholder.cloneNode(true) : document.createElement('option')
                    );

                    const option = document.createElement('option');
                    option.value = String(item.id);
                    option.textContent = item.unit
                        ? item.name + ' (' + item.unit + ')'
                        : item.name;
                    option.selected = true;
                    select.appendChild(option);
                    select.value = String(item.id);
                }
            }

            closeSuggestions(state);

            if (state.container.dataset.autocompleteSubmit === 'true') {
                const form = state.input.closest('form');

                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }
        }

        async function fetchSuggestions(state, term) {
            const url = new URL(
                state.container.dataset.autocompleteUrl,
                window.location.href
            );
            url.searchParams.set('q', term);

            if (state.container.dataset.autocompleteActiveOnly === 'true') {
                url.searchParams.set('active_only', '1');
            }

            const formId = state.container.dataset.autocompleteForm;
            const form = formId ? document.getElementById(formId) : null;

            if (form) {
                ['category', 'stock_status'].forEach(name => {
                    const field = form.elements.namedItem(name);

                    if (field && field.value !== '') {
                        url.searchParams.set(name, field.value);
                    }
                });
            }

            state.controller = new AbortController();

            try {
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: state.controller.signal
                });

                if (!response.ok) {
                    throw new Error('Autocomplete request failed.');
                }

                const payload = await response.json();

                if (state.input.value.trim() !== term) {
                    return;
                }

                if (!payload || !Array.isArray(payload.data)) {
                    throw new Error('Autocomplete response is invalid.');
                }

                if (payload.data.length === 0) {
                    renderSuggestions(state, [], 'Data tidak ditemukan');
                } else {
                    renderSuggestions(state, payload.data);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }

                renderSuggestions(state, [], 'Pencarian gagal. Silakan coba lagi.');
            }
        }

        function scheduleSearch(state) {
            const term = state.input.value.trim();

            window.clearTimeout(state.timer);
            if (state.controller) {
                state.controller.abort();
            }

            clearTargetSelection(state);
            state.items = [];
            state.list.replaceChildren();

            if (term.length < 2) {
                closeSuggestions(state);
                return;
            }

            closeSuggestions(state);
            state.timer = window.setTimeout(
                () => fetchSuggestions(state, term),
                250
            );
        }

        document.addEventListener('input', event => {
            const input = event.target.closest('[data-autocomplete-input]');

            if (!input) {
                return;
            }

            scheduleSearch(stateFor(input.closest(containerSelector)));
        });

        document.addEventListener('keydown', event => {
            const input = event.target.closest('[data-autocomplete-input]');

            if (!input) {
                return;
            }

            const state = stateFor(input.closest(containerSelector));
            const options = state.list.querySelectorAll('[data-autocomplete-index]');

            if (event.key === 'ArrowDown' && !state.list.hidden && options.length > 0) {
                event.preventDefault();
                setActiveOption(state, state.activeIndex + 1);
            } else if (event.key === 'ArrowUp' && !state.list.hidden && options.length > 0) {
                event.preventDefault();
                const previousIndex = state.activeIndex < 0
                    ? options.length - 1
                    : state.activeIndex - 1;
                setActiveOption(state, previousIndex);
            } else if (
                event.key === 'Enter'
                && !state.list.hidden
                && state.activeIndex >= 0
            ) {
                event.preventDefault();
                chooseSuggestion(state, state.activeIndex);
            } else if (event.key === 'Escape') {
                closeSuggestions(state);
            }
        });

        document.addEventListener('mousedown', event => {
            if (event.target.closest('.autocomplete-option')) {
                event.preventDefault();
            }
        });

        document.addEventListener('click', event => {
            const option = event.target.closest('.autocomplete-option');

            if (option) {
                const state = stateFor(option.closest(containerSelector));
                chooseSuggestion(state, Number(option.dataset.autocompleteIndex));
                return;
            }

            document.querySelectorAll(containerSelector).forEach(container => {
                if (!container.contains(event.target)) {
                    closeSuggestions(stateFor(container));
                }
            });
        });
    })();
</script>
