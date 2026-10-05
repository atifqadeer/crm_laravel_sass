{{-- Styles and script for sales.partials.requirement-picker; include once per page (sale form, admin preview). --}}
@once
    <style>
        /* Requirement picker cards */
        .req-card {
            height: 100%;
            padding: 1rem 1.125rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-body-bg);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .req-card:focus-within {
            border-color: rgba(var(--bs-primary-rgb), .5);
            box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .08);
        }

        .req-card:has(.req-value.is-invalid) {
            border-color: var(--bs-danger);
        }

        .req-card-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            font-size: 20px;
            border-radius: 10px;
            background: var(--bs-primary-bg-subtle);
            color: var(--bs-primary);
        }

        .req-card .min-w-0 {
            min-width: 0;
        }

        .req-group-title {
            margin: .875rem 0 .4rem;
            font-size: .6875rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--bs-secondary-color);
        }

        .req-chips {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .req-chips:empty {
            display: none;
        }

        .req-chip {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            margin: 0;
            padding: .3rem .75rem;
            font-size: .8125rem;
            line-height: 1.4;
            color: var(--bs-body-color);
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 50rem;
            cursor: pointer;
            user-select: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .req-chip:hover {
            border-color: var(--bs-primary);
        }

        .req-chip-tick {
            display: none;
            font-size: 15px;
        }

        .btn-check:checked+.req-chip {
            color: #fff;
            background: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-check:checked+.req-chip .req-chip-tick {
            display: inline-block;
        }

        .btn-check:focus-visible+.req-chip {
            outline: 2px solid var(--bs-primary);
            outline-offset: 2px;
        }

        .req-chip-wrap {
            position: relative;
            display: inline-flex;
        }

        .req-chip-wrap .req-chip {
            padding-right: 3.1rem;
        }

        /* Edit / remove buttons on custom chips */
        .req-chip-actions {
            position: absolute;
            top: 50%;
            right: .45rem;
            display: inline-flex;
            gap: .2rem;
            transform: translateY(-50%);
        }

        .req-chip-actions button {
            display: inline-flex;
            padding: 0;
            color: var(--bs-secondary-color);
            background: transparent;
            border: 0;
            opacity: .85;
        }

        .req-chip-actions button:hover {
            opacity: 1;
        }

        .btn-check:checked~.req-chip-actions button {
            color: #fff;
        }

        .req-chip-wrap.is-editing .req-chip,
        .req-chip-wrap.is-editing .req-chip-actions {
            display: none;
        }

        .req-chip-input {
            min-width: 140px;
            padding: .25rem .75rem;
            font-size: .8125rem;
            border-radius: 50rem;
        }

        /* Helper inputs inside pickers are never submitted, so skip the green "valid" state */
        .was-validated .req-card .form-control:valid {
            border-color: var(--bs-border-color);
            background-image: none;
        }

        .req-preview {
            display: flex;
            gap: .5rem;
            margin-top: .875rem;
            padding: .625rem .75rem;
            font-size: .8125rem;
            color: var(--bs-body-color);
            background: var(--bs-tertiary-bg);
            border: 1px dashed var(--bs-border-color);
            border-radius: 8px;
        }

        .req-preview.is-empty .req-preview-text {
            font-style: italic;
            color: var(--bs-secondary-color);
        }

        .req-group-lead {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .45rem;
            font-size: .8125rem;
        }

        .req-group-lead .req-qty {
            width: 76px;
            flex: 0 0 auto;
        }

        .req-group-prefix {
            font-weight: 600;
        }

        .req-prefix-display {
            display: flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .65rem;
            font-size: .8125rem;
            color: var(--bs-body-color);
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
        }

        .req-preview.is-legacy {
            border-color: var(--bs-warning);
            background: var(--bs-warning-bg-subtle);
        }

        .req-preview-tag {
            display: block;
            margin-bottom: .15rem;
            font-size: .6875rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--bs-warning-text-emphasis);
        }
    </style>
    <script>
        /**
         * Requirement pickers (timing, experience, benefits, qualification).
         * Composes the selected chips of each picker into its hidden input as
         * "<prefix> <lead> in A, B and C. <hours> hours per week."
         */
        const SaleRequirements = (function() {
            const cards = () => document.querySelectorAll('[data-req-field]');
            let customSeq = 0;

            function joinList(items) {
                if (items.length <= 1) return items[0] || '';
                return items.slice(0, -1).join(', ') + ' and ' + items[items.length - 1];
            }

            // Words get a leading space; punctuation like "; experience in" attaches directly
            const escapeRegExp = text => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

            function glue(text) {
                if (!text) return '';
                return /^[;,.:]/.test(text) ? text : ' ' + text;
            }

            // Groups with a prefix and/or number read as their own part: "3 double shift 10 am to 11:30 pm"
            const isLedGroup = group => !!(group.dataset.prefix || '').trim() || group.dataset.quantity === '1';

            /**
             * Sentence parts in group order. Chips from plain groups and custom chips form one list
             * ("Day Shifts, Night Shifts and Weekends"); each led group adds "[number] [prefix] A and B".
             */
            function bodyParts(card) {
                const parts = [];
                const plain = [];
                let plainIndex = -1;
                const addPlain = values => {
                    if (plainIndex < 0) {
                        plainIndex = parts.length;
                        parts.push('');
                    }
                    plain.push(...values);
                };

                card.querySelectorAll('.req-group').forEach(group => {
                    const checked = Array.from(group.querySelectorAll('.req-option:checked')).map(el => el.value);
                    if (!isLedGroup(group)) {
                        addPlain(checked);
                        return;
                    }
                    const qty = group.querySelector('.req-qty')?.value.trim() || '';
                    if (!checked.length && !qty) return;
                    parts.push([qty, (group.dataset.prefix || '').trim(), joinList(checked)].filter(Boolean).join(' '));
                });
                addPlain(Array.from(card.querySelectorAll('.req-custom-list .req-option:checked')).map(el => el.value));
                parts[plainIndex] = joinList(plain);

                return parts.filter(Boolean);
            }

            function compose(card) {
                const prefix = card.querySelector('.req-prefix').value.trim();
                const lead = card.querySelector('.req-lead:checked');
                const parts = bodyParts(card);
                const hours = card.querySelector('.req-hours')?.value.trim();

                let body = parts.join(', ');
                if (lead) {
                    const connector = (lead.dataset.connector ?? card.dataset.connector ?? '').trim();
                    const suffix = (lead.dataset.suffix || '').trim();
                    body = parts.length ?
                        lead.value + glue(connector) + ' ' + body + glue(suffix) :
                        lead.value;
                }

                const sentences = [];
                if (body) sentences.push(body + '.');
                if (hours) sentences.push(`${hours} hours per week.`);

                return sentences.length ? `${prefix ? prefix + ' ' : ''}${sentences.join(' ')}` : '';
            }

            function refresh(card) {
                const composed = compose(card);
                const hidden = card.querySelector('.req-value');
                const preview = card.querySelector('.req-preview');
                const previewText = preview.querySelector('.req-preview-text');
                const count = card.querySelector('.req-count');
                const selected = card.querySelectorAll('.req-lead:checked, .req-option:checked').length;

                // Saved text that couldn't be matched to options is kept until something is selected
                const legacy = !composed && card.dataset.legacy ? card.dataset.legacy : '';
                const text = composed || legacy;

                hidden.value = text;
                if (text) hidden.classList.remove('is-invalid');

                preview.classList.toggle('is-empty', !text);
                preview.classList.toggle('is-legacy', !!legacy);
                previewText.textContent = legacy ? plainText(legacy) : (text ||
                    'Nothing selected yet — this is how it will appear on the job portal.');
                if (legacy) {
                    const tag = document.createElement('span');
                    tag.className = 'req-preview-tag';
                    tag.textContent = 'Current saved text — kept unless you select options';
                    previewText.prepend(tag);
                }

                count.classList.toggle('d-none', !selected);
                count.textContent = `${selected} selected`;
            }

            // Text content of stored HTML; DOMParser documents are inert (no scripts or image loads)
            function plainText(raw) {
                const doc = new DOMParser().parseFromString(String(raw), 'text/html');
                return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
            }

            function resetSelection(card) {
                card.querySelectorAll('.req-lead, .req-option').forEach(el => el.checked = false);
                card.querySelector('.req-custom-list').innerHTML = '';
                const hours = card.querySelector('.req-hours');
                if (hours) hours.value = '';
                card.querySelectorAll('.req-qty').forEach(el => el.value = '');
            }

            // Text after "Something:" — the part that has to match for a saved sentence to be re-selected
            const withoutPrefix = text => text.replace(/^[^.:]{1,80}:\s+/, '');

            /**
             * Re-select chips from a sentence saved by this picker. Returns false (and selects nothing)
             * when the text is older free text or doesn't recompose to the same sentence.
             */
            function prefill(card, raw) {
                if (/<[a-z][\s\S]*>/i.test(raw)) return false;
                const text = plainText(raw);
                if (!text.endsWith('.')) return false;

                const prefix = card.querySelector('.req-prefix').value.trim();
                let rest = text;
                if (prefix && rest.startsWith(prefix)) {
                    rest = rest.slice(prefix.length).trim();
                } else if (/^[^.:]{1,80}:\s+/.test(rest)) {
                    rest = withoutPrefix(rest); // saved before the prefix was changed
                } else if (prefix) {
                    return false;
                }

                const hoursInput = card.querySelector('.req-hours');
                const hoursMatch = rest.match(/(?:^|\s)(\d+(?:\.\d+)?) hours per week\.$/);
                if (hoursInput && hoursMatch) {
                    hoursInput.value = hoursMatch[1];
                    rest = rest.slice(0, hoursMatch.index).trim();
                }
                rest = rest.replace(/\.$/, '').trim();

                const leads = Array.from(card.querySelectorAll('.req-lead'))
                    .sort((a, b) => b.value.length - a.value.length);
                const lead = leads.find(el => rest.startsWith(el.value));
                if (lead) {
                    lead.checked = true;
                    rest = rest.slice(lead.value.length).trim();
                    const suffix = (lead.dataset.suffix || '').trim();
                    const connector = (lead.dataset.connector ?? card.dataset.connector ?? '').trim();
                    if (suffix && rest.endsWith(suffix)) rest = rest.slice(0, -suffix.length).trim();
                    if (connector && rest.startsWith(connector)) rest = rest.slice(connector.length).trim();
                }

                if (rest) {
                    // Split on ", " / " and " but keep labels that themselves contain those separators
                    const parts = rest.split(/(, | and )/);
                    const groups = Array.from(card.querySelectorAll('.req-group'));
                    const optionsOf = list => new Map(list.flatMap(g => Array.from(g.querySelectorAll('.req-option')))
                        .map(el => [el.value.toLowerCase(), el]));
                    const plainKnown = optionsOf(groups.filter(g => !isLedGroup(g)));
                    const ledGroups = groups.filter(isLedGroup)
                        .sort((a, b) => (b.dataset.prefix || '').length - (a.dataset.prefix || '').length);

                    // Longest run of parts from i (optionally replacing the first part) that is a known label
                    const matchLabel = (known, i, first = parts[i]) => {
                        for (let j = parts.length - 1; j >= i; j -= 2) {
                            const candidate = (first + parts.slice(i + 1, j + 1).join('')).toLowerCase();
                            if (known.has(candidate)) return {el: known.get(candidate), end: j};
                        }
                        return null;
                    };

                    // "[number] [prefix] [first chip]" at the start of a part → the led group it belongs to
                    const ledStart = token => {
                        for (const group of ledGroups) {
                            const prefix = (group.dataset.prefix || '').trim();
                            const quantity = group.dataset.quantity === '1';
                            const match = token.match(new RegExp('^' + (quantity ? '(\\d+(?:\\.\\d+)?)' : '()') +
                                (prefix ? (quantity ? '\\s+' : '') + escapeRegExp(prefix) : '') + '(?:\\s+(.*))?$'));
                            if (!match) continue;
                            // Without a prefix the number must be followed by one of the group's own chips
                            if (!prefix && match[2] && !optionsOf([group]).has(match[2].toLowerCase())) continue;
                            return {group, qty: match[1], first: match[2] || ''};
                        }
                        return null;
                    };

                    for (let i = 0; i < parts.length; i += 2) {
                        const start = ledStart(parts[i]);
                        if (start) {
                            const own = optionsOf([start.group]);
                            let end = i;
                            if (start.first) {
                                const first = matchLabel(own, i, start.first);
                                if (!first) {
                                    addCustom(card, parts[i]);
                                    continue;
                                }
                                first.el.checked = true;
                                end = first.end;
                                // More chips of the same group: "... 10 am to 11:30 pm and 2 pm to 10 pm"
                                while (end + 2 < parts.length && !ledStart(parts[end + 2])) {
                                    const next = matchLabel(own, end + 2);
                                    if (!next) break;
                                    next.el.checked = true;
                                    end = next.end;
                                }
                            }
                            const qtyInput = start.group.querySelector('.req-qty');
                            if (qtyInput && start.qty) qtyInput.value = start.qty;
                            i = end;
                            continue;
                        }

                        const plain = matchLabel(plainKnown, i);
                        if (plain) {
                            plain.el.checked = true;
                            i = plain.end;
                        } else {
                            addCustom(card, parts[i]);
                        }
                    }
                }

                // Same pieces in any order (custom chips are listed last when re-rendered)
                const pieces = t => withoutPrefix(t).split(/, | and |; |\. /)
                    .map(s => s.replace(/\.$/, '').trim().toLowerCase()).sort().join('|');
                if (pieces(compose(card)) !== pieces(text)) {
                    resetSelection(card);
                    return false;
                }
                return true;
            }

            function addCustom(card, raw) {
                const value = raw.replace(/\s+/g, ' ').trim();
                if (!value) return;

                // Re-use an existing chip with the same text instead of duplicating it
                const existing = Array.from(card.querySelectorAll('.req-option'))
                    .find(el => el.value.toLowerCase() === value.toLowerCase());
                if (existing) {
                    existing.checked = true;
                    refresh(card);
                    return;
                }

                const id = `${card.dataset.reqField}_custom_${++customSeq}`;
                const wrap = document.createElement('span');
                wrap.className = 'req-chip-wrap';

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'btn-check req-option';
                input.id = id;
                input.value = value;
                input.checked = true;
                input.autocomplete = 'off';

                const label = document.createElement('label');
                label.className = 'req-chip';
                label.htmlFor = id;
                label.title = 'Double-click to edit';
                label.textContent = value;
                label.addEventListener('dblclick', e => {
                    e.preventDefault();
                    editCustom(card, wrap);
                });

                const actionButton = (title, icon, onClick) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.title = title;
                    button.setAttribute('aria-label', `${title} ${value}`);
                    button.innerHTML = `<iconify-icon icon="${icon}" class="fs-16"></iconify-icon>`;
                    button.addEventListener('click', onClick);
                    return button;
                };
                const actions = document.createElement('span');
                actions.className = 'req-chip-actions';
                actions.append(
                    actionButton('Edit', 'solar:pen-2-bold', () => editCustom(card, wrap)),
                    actionButton('Remove', 'solar:close-circle-bold', () => {
                        wrap.remove();
                        refresh(card);
                    })
                );

                wrap.append(input, label, actions);
                card.querySelector('.req-custom-list').appendChild(wrap);
                refresh(card);
            }

            /**
             * Inline edit of a custom chip: Enter or leaving the box saves, Esc cancels,
             * an empty value removes the chip and a value matching another chip merges into it.
             */
            function editCustom(card, wrap) {
                if (wrap.classList.contains('is-editing')) return;
                const option = wrap.querySelector('.req-option');
                const label = wrap.querySelector('.req-chip');

                const box = document.createElement('input');
                box.type = 'text';
                box.className = 'form-control form-control-sm req-chip-input';
                box.maxLength = 150;
                box.value = option.value;
                box.style.width = `${Math.max(label.offsetWidth, 140)}px`;
                box.setAttribute('aria-label', 'Edit option');

                wrap.classList.add('is-editing');
                label.after(box);
                box.focus();
                box.select();

                let finished = false;
                const finish = save => {
                    if (finished) return;
                    finished = true;
                    const value = box.value.replace(/\s+/g, ' ').trim();
                    box.remove();
                    wrap.classList.remove('is-editing');
                    if (!save || value === option.value) return;

                    if (!value) {
                        wrap.remove();
                    } else {
                        const duplicate = Array.from(card.querySelectorAll('.req-option'))
                            .find(el => el !== option && el.value.toLowerCase() === value.toLowerCase());
                        if (duplicate) {
                            duplicate.checked = true;
                            wrap.remove();
                        } else {
                            option.value = value;
                            option.checked = true;
                            label.textContent = value;
                            wrap.querySelectorAll('.req-chip-actions button')
                                .forEach(button => button.setAttribute('aria-label', `${button.title} ${value}`));
                        }
                    }
                    refresh(card);
                };

                box.addEventListener('keydown', e => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        finish(true);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        finish(false);
                    }
                });
                box.addEventListener('blur', () => finish(true));
            }

            // Safe to call again after pickers are re-rendered; each card is wired once
            function init() {
                cards().forEach(card => {
                    if (card.dataset.reqReady) return;
                    card.dataset.reqReady = '1';
                    const customInput = card.querySelector('.req-custom-input');

                    card.addEventListener('change', e => {
                        // Lead group is single choice but can be deselected
                        if (e.target.classList.contains('req-lead') && e.target.checked) {
                            card.querySelectorAll('.req-lead').forEach(el => {
                                if (el !== e.target) el.checked = false;
                            });
                        }
                        refresh(card);
                    });
                    card.addEventListener('input', e => {
                        if (e.target.matches('.req-hours, .req-qty')) refresh(card);
                    });

                    customInput.addEventListener('keydown', e => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addCustom(card, customInput.value);
                            customInput.value = '';
                        }
                    });
                    card.querySelector('.req-custom-add').addEventListener('click', () => {
                        addCustom(card, customInput.value);
                        customInput.value = '';
                        customInput.focus();
                    });

                    card.querySelector('.req-clear').addEventListener('click', () => {
                        resetSelection(card);
                        delete card.dataset.legacy;
                        refresh(card);
                    });

                    // Edit form: re-select the saved sentence, or keep older free text as-is
                    if (card.dataset.initial && !prefill(card, card.dataset.initial)) {
                        card.dataset.legacy = card.dataset.initial;
                    }

                    refresh(card);
                });
            }

            // Recompose every picker and flag empty required ones; returns true when valid
            function validate() {
                let firstInvalid = null;
                cards().forEach(card => {
                    refresh(card);
                    const hidden = card.querySelector('.req-value');
                    if (hidden.dataset.required === '1' && !hidden.value) {
                        hidden.classList.add('is-invalid');
                        const feedback = card.querySelector('.invalid-feedback');
                        if (!feedback.textContent.trim()) {
                            feedback.textContent = 'Please select at least one option';
                        }
                        firstInvalid = firstInvalid || card;
                    }
                });

                if (firstInvalid) {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
                return !firstInvalid;
            }

            return {
                init,
                validate
            };
        })();
    </script>
@endonce
