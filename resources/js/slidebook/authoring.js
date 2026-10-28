function layoutPreviewMarkup(layout) {
    const base = 'rounded border border-slate-700/70 bg-slate-900';
    const line = 'rounded-full bg-slate-500/80';
    const accent = 'rounded-full bg-indigo-400/90';

    const templates = {
        'cover': `<div class="flex h-full flex-col items-center justify-center gap-2"><span class="h-2 w-12 ${accent}"></span><span class="h-2 w-3/4 ${line}"></span><span class="h-1.5 w-1/2 ${line}"></span></div>`,
        'section-divider': `<div class="flex h-full items-center"><div class="w-full space-y-2"><span class="block h-1.5 w-10 ${accent}"></span><span class="block h-2 w-2/3 ${line}"></span></div></div>`,
        'comparison': `<div class="grid h-full grid-cols-2 gap-2"><div class="${base} p-2"><span class="block h-1.5 w-1/2 ${accent}"></span><span class="mt-2 block h-1 w-full ${line}"></span></div><div class="${base} p-2"><span class="block h-1.5 w-1/2 ${accent}"></span><span class="mt-2 block h-1 w-full ${line}"></span></div></div>`,
        'process': `<div class="flex h-full items-center justify-between gap-1">${[1, 2, 3, 4].map(() => `<span class="h-6 w-6 rounded-full border border-indigo-400/60 bg-indigo-500/10"></span>`).join('<span class="h-px flex-1 bg-slate-600"></span>')}</div>`,
        'timeline': `<div class="relative flex h-full items-center"><div class="h-px w-full bg-slate-600"></div><span class="absolute left-2 h-3 w-3 rounded-full bg-indigo-400"></span><span class="absolute left-1/2 h-3 w-3 rounded-full bg-indigo-400"></span><span class="absolute right-2 h-3 w-3 rounded-full bg-indigo-400"></span></div>`,
        'image-focus': `<div class="grid h-full grid-cols-5 gap-2"><div class="col-span-3 rounded bg-slate-700/80"></div><div class="col-span-2 flex flex-col justify-center gap-2"><span class="h-2 w-full ${accent}"></span><span class="h-1.5 w-full ${line}"></span><span class="h-1.5 w-2/3 ${line}"></span></div></div>`,
        'quote': `<div class="flex h-full items-center gap-2"><span class="text-2xl font-black text-indigo-400">“</span><div class="w-full space-y-2"><span class="block h-2 w-full ${line}"></span><span class="block h-1.5 w-2/3 ${line}"></span></div></div>`,
        'code': `<div class="h-full rounded bg-slate-950 p-2 font-mono"><span class="block h-1.5 w-1/2 rounded bg-emerald-400/70"></span><span class="mt-2 block h-1.5 w-3/4 rounded bg-cyan-400/50"></span><span class="mt-2 block h-1.5 w-2/3 rounded bg-slate-500/60"></span></div>`,
        'diagram': `<div class="flex h-full items-center justify-center gap-2"><span class="h-7 w-12 ${base}"></span><span class="text-slate-500">→</span><span class="h-7 w-12 ${base}"></span><span class="text-slate-500">→</span><span class="h-7 w-12 ${base}"></span></div>`,
        'checkpoint': `<div class="flex h-full flex-col justify-center gap-2"><span class="h-2 w-2/3 ${accent}"></span><span class="h-6 w-full ${base}"></span><span class="h-6 w-full ${base}"></span></div>`,
        'key-points': `<div class="flex h-full flex-col justify-center gap-2">${[1, 2, 3].map(() => `<div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-indigo-400"></span><span class="h-1.5 flex-1 ${line}"></span></div>`).join('')}</div>`,
    };

    return templates[layout] ?? `<div class="flex h-full flex-col justify-center gap-2"><span class="h-2 w-1/2 ${accent}"></span><span class="h-1.5 w-full ${line}"></span><span class="h-1.5 w-5/6 ${line}"></span><span class="h-1.5 w-2/3 ${line}"></span></div>`;
}

function enhanceLayoutSelect(select) {
    if (select.dataset.visualLayoutPicker === 'ready') {
        return null;
    }

    const options = Array.from(select.options);
    if (options.length < 2) {
        return null;
    }

    select.dataset.visualLayoutPicker = 'ready';

    const wrapper = document.createElement('div');
    wrapper.className = 'space-y-2';

    const toolbar = document.createElement('div');
    toolbar.className = 'flex items-center justify-between gap-3';
    toolbar.innerHTML = `
        <p class="text-sm theme-muted">Pilih komposisi visual. Konten slide tidak berubah saat layout diganti.</p>
        <span data-layout-current class="shrink-0 rounded-full border theme-accent-border theme-accent-surface px-2 py-1 text-sm font-bold uppercase tracking-wide theme-accent-text"></span>
    `;

    const grid = document.createElement('div');
    grid.className = 'grid max-h-72 grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3';

    const cards = new Map();
    options.forEach((option) => {
        const value = option.value;
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.layoutValue = value;
        button.className = 'rounded-xl border teacher-card p-2 text-left transition-all theme-border-hover';
        button.innerHTML = `
            <div class="h-14 rounded-lg border teacher-card p-2" aria-hidden="true">${layoutPreviewMarkup(value)}</div>
            <div class="mt-2 flex items-center justify-between gap-2">
                <span class="truncate text-sm font-semibold theme-body">${option.textContent}</span>
                <span data-layout-check class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full theme-accent-fill text-sm font-bold teacher-on-solid opacity-0">✓</span>
            </div>
        `;

        button.addEventListener('click', () => {
            select.value = value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderSelection();
        });

        cards.set(value, button);
        grid.appendChild(button);
    });

    const currentBadge = toolbar.querySelector('[data-layout-current]');
    const renderSelection = () => {
        cards.forEach((button, value) => {
            const active = value === select.value;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.className = active
                ? 'rounded-xl border theme-accent-border theme-accent-surface p-2 text-left ring-2 ring-indigo-500/30 transition-all'
                : 'rounded-xl border teacher-card p-2 text-left transition-all theme-border-hover';
            button.querySelector('[data-layout-check]')?.classList.toggle('opacity-0', !active);
        });

        const selected = options.find((option) => option.value === select.value);
        if (currentBadge) {
            currentBadge.textContent = selected?.textContent || 'Otomatis';
        }
    };

    wrapper.append(toolbar, grid);
    select.insertAdjacentElement('afterend', wrapper);
    select.classList.add('sr-only');
    select.setAttribute('aria-hidden', 'true');
    renderSelection();

    return renderSelection;
}

function enhanceLayoutPickers() {
    const pickers = Array.from(document.querySelectorAll('select[name="layout"]'))
        .map((select) => enhanceLayoutSelect(select))
        .filter(Boolean);

    if (pickers.length === 0) {
        return;
    }

    document.addEventListener('click', () => {
        window.setTimeout(() => pickers.forEach((render) => render()), 0);
    });
}

function cleanDemoOrdinalDisplay(card) {
    const referenceBadge = Array.from(card.querySelectorAll('span')).find((span) => span.textContent.includes('"demo":true'));
    if (!referenceBadge) {
        return false;
    }

    const title = card.querySelector('h3');
    if (title) {
        title.textContent = title.textContent.replace(/^\s*\d+\.\s*/, '');
    }

    return true;
}

function enhanceDemoEditTitles() {
    const editButtons = Array.from(document.querySelectorAll('button[title="Edit Slide"]'));

    editButtons.forEach((button) => {
        const card = button.closest('div.rounded-xl.border');
        if (!card || !cleanDemoOrdinalDisplay(card)) {
            return;
        }

        button.addEventListener('click', () => {
            window.setTimeout(() => {
                const form = Array.from(document.querySelectorAll('form[action*="/instructor/slides/"]'))
                    .find((candidate) => candidate.querySelector('input[name="title"]') && candidate.querySelector('textarea[name="content"]'));
                const titleInput = form?.querySelector('input[name="title"]');

                if (!form || !titleInput) {
                    return;
                }

                const originalTitle = titleInput.value;
                const cleanTitle = originalTitle.replace(/^\s*\d+\.\s*/, '');
                if (cleanTitle === originalTitle) {
                    return;
                }

                titleInput.dataset.demoOriginalTitle = originalTitle;
                titleInput.dataset.demoCleanTitle = cleanTitle;
                titleInput.value = cleanTitle;
                titleInput.dispatchEvent(new Event('input', { bubbles: true }));

                if (form.dataset.demoTitleGuard === 'ready') {
                    return;
                }

                form.dataset.demoTitleGuard = 'ready';
                form.addEventListener('submit', () => {
                    const input = form.querySelector('input[name="title"]');
                    if (!input?.dataset.demoOriginalTitle) {
                        return;
                    }

                    if (input.value.trim() === input.dataset.demoCleanTitle.trim()) {
                        input.value = input.dataset.demoOriginalTitle;
                    }
                });
            }, 0);
        });
    });
}

function hardenNeedsReviewCheckbox() {
    const checkbox = document.querySelector('input[type="checkbox"][name="needs_review"]');
    if (!checkbox || checkbox.dataset.falseFallback === 'ready') {
        return;
    }

    checkbox.dataset.falseFallback = 'ready';
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'needs_review';
    hidden.value = '0';
    checkbox.insertAdjacentElement('beforebegin', hidden);
}

function enhanceSlideDeckOrdering() {
    const designForm = document.querySelector('form[action*="/slidebooks/"][action$="/design"]');
    const deleteForms = Array.from(document.querySelectorAll('form[action*="/instructor/slides/"]'));

    if (!designForm || deleteForms.length < 2 || document.querySelector('[data-slide-order-toolbar]')) {
        return;
    }

    const parsedCards = deleteForms
        .map((form) => {
            const match = form.action.match(/\/instructor\/slides\/(\d+)$/);
            const card = form.closest('div.rounded-xl.border');
            return match && card ? { id: Number(match[1]), card } : null;
        })
        .filter(Boolean);

    const uniqueCards = Array.from(new Map(parsedCards.map((item) => [item.id, item])).values());
    if (uniqueCards.length < 2) {
        return;
    }

    const deck = uniqueCards[0].card.parentElement;
    if (!deck || !uniqueCards.every((item) => item.card.parentElement === deck)) {
        return;
    }

    const designUrl = new URL(designForm.action, window.location.origin);
    const reorderUrl = new URL(designUrl.toString());
    reorderUrl.pathname = reorderUrl.pathname.replace(/\/design$/, '/slides/reorder');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    let initialIds = uniqueCards.map((item) => item.id);
    let isDirty = false;
    let draggedCard = null;

    const toolbar = document.createElement('div');
    toolbar.dataset.slideOrderToolbar = 'true';
    toolbar.className = 'mb-4 flex flex-col gap-3 rounded-xl border teacher-card p-3 sm:flex-row sm:items-center sm:justify-between';
    toolbar.innerHTML = `
        <div>
            <p class="text-sm font-bold uppercase tracking-wider theme-heading">Urutan Slide</p>
            <p class="mt-1 text-sm theme-muted">Seret pegangan atau gunakan tombol naik/turun. Perubahan baru diterapkan setelah disimpan.</p>
        </div>
        <div class="flex items-center gap-2">
            <span data-order-state class="rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-success-text">Tersimpan</span>
            <button type="button" data-save-order disabled class="rounded-lg theme-accent-fill px-3 py-1.5 text-sm font-bold teacher-on-solid transition-colors disabled:cursor-not-allowed disabled:opacity-50">Simpan Urutan</button>
        </div>
    `;
    deck.parentElement?.insertBefore(toolbar, deck);

    const stateBadge = toolbar.querySelector('[data-order-state]');
    const saveButton = toolbar.querySelector('[data-save-order]');
    const currentCards = () => Array.from(deck.children).filter((element) => element.dataset.slideId);
    const currentIds = () => currentCards().map((card) => Number(card.dataset.slideId));

    const updateVisualOrder = () => {
        const cards = currentCards();
        cards.forEach((card, index) => {
            const badge = card.querySelector('[data-original-order-badge]');
            if (badge) {
                badge.textContent = String(index + 1);
            }

            const moveUp = card.querySelector('[data-move-slide="up"]');
            const moveDown = card.querySelector('[data-move-slide="down"]');
            if (moveUp) moveUp.disabled = index === 0;
            if (moveDown) moveDown.disabled = index === cards.length - 1;
        });

        isDirty = JSON.stringify(currentIds()) !== JSON.stringify(initialIds);
        if (stateBadge) {
            stateBadge.textContent = isDirty ? 'Belum disimpan' : 'Tersimpan';
            stateBadge.className = isDirty
                ? 'rounded-full border theme-warning-border theme-warning-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-warning-text'
                : 'rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-success-text';
        }
        if (saveButton) {
            saveButton.disabled = !isDirty;
            if (!isDirty) saveButton.textContent = 'Simpan Urutan';
        }
    };

    const beforeUnloadHandler = (event) => {
        if (!isDirty) return;
        event.preventDefault();
        event.returnValue = '';
    };
    window.addEventListener('beforeunload', beforeUnloadHandler);

    uniqueCards.forEach(({ id, card }) => {
        card.dataset.slideId = String(id);
        cleanDemoOrdinalDisplay(card);

        const orderBadge = card.querySelector('.w-6.h-6');
        if (orderBadge) {
            orderBadge.dataset.originalOrderBadge = 'true';
        }

        const controls = document.createElement('div');
        controls.className = 'mb-3 flex items-center justify-between gap-2 rounded-lg border teacher-card px-2.5 py-2';
        controls.innerHTML = `
            <button type="button" draggable="true" data-drag-handle class="flex cursor-grab items-center gap-2 rounded-md px-2 py-1 text-sm font-semibold theme-body theme-muted-surface-hover theme-heading-hover active:cursor-grabbing" title="Seret untuk mengubah urutan">
                <span aria-hidden="true">⠿</span><span>Geser slide</span>
            </button>
            <div class="flex items-center gap-1">
                <button type="button" data-move-slide="up" class="rounded-md px-2 py-1 text-sm theme-body theme-muted-surface-hover theme-heading-hover disabled:cursor-not-allowed disabled:opacity-30" aria-label="Pindahkan slide ke atas">↑</button>
                <button type="button" data-move-slide="down" class="rounded-md px-2 py-1 text-sm theme-body theme-muted-surface-hover theme-heading-hover disabled:cursor-not-allowed disabled:opacity-30" aria-label="Pindahkan slide ke bawah">↓</button>
            </div>
        `;
        card.insertBefore(controls, card.firstChild);

        const handle = controls.querySelector('[data-drag-handle]');
        handle?.addEventListener('dragstart', (event) => {
            draggedCard = card;
            card.classList.add('opacity-60', 'ring-2', 'ring-indigo-500/40');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(id));
        });
        handle?.addEventListener('dragend', () => {
            card.classList.remove('opacity-60', 'ring-2', 'ring-indigo-500/40');
            draggedCard = null;
            updateVisualOrder();
        });

        card.addEventListener('dragover', (event) => {
            if (!draggedCard || draggedCard === card) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            const rect = card.getBoundingClientRect();
            const placeAfter = event.clientY > rect.top + rect.height / 2;
            deck.insertBefore(draggedCard, placeAfter ? card.nextSibling : card);
        });

        controls.querySelector('[data-move-slide="up"]')?.addEventListener('click', () => {
            const previous = card.previousElementSibling;
            if (previous?.dataset.slideId) {
                deck.insertBefore(card, previous);
                updateVisualOrder();
            }
        });

        controls.querySelector('[data-move-slide="down"]')?.addEventListener('click', () => {
            const next = card.nextElementSibling;
            if (next?.dataset.slideId) {
                deck.insertBefore(next, card);
                updateVisualOrder();
            }
        });
    });

    saveButton?.addEventListener('click', async () => {
        if (!isDirty || !csrfToken) return;

        saveButton.disabled = true;
        saveButton.textContent = 'Menyimpan…';
        stateBadge.textContent = 'Menyimpan';

        try {
            const response = await fetch(reorderUrl.toString(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ slide_ids: currentIds() }),
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload?.message || 'Urutan slide gagal disimpan.');
            }

            initialIds = currentIds();
            isDirty = false;
            window.removeEventListener('beforeunload', beforeUnloadHandler);
            stateBadge.textContent = 'Tersimpan';
            stateBadge.className = 'rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-success-text';
            saveButton.textContent = 'Urutan Tersimpan';
            window.setTimeout(() => window.location.reload(), 450);
        } catch (error) {
            stateBadge.textContent = 'Gagal menyimpan';
            stateBadge.className = 'rounded-full border theme-danger-border theme-danger-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-danger-text';
            saveButton.disabled = false;
            saveButton.textContent = 'Coba Simpan Lagi';
            window.alert(error.message);
        }
    });

    updateVisualOrder();
}

export function enhanceSlidebookAuthoring() {
    enhanceLayoutPickers();
    enhanceDemoEditTitles();
    hardenNeedsReviewCheckbox();
    enhanceSlideDeckOrdering();
}
