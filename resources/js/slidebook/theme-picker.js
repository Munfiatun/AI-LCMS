const slidebookThemes = [
    {
        value: 'indigo-dark',
        label: 'Indigo Dark',
        description: 'Gelap profesional dengan aksen indigo dan violet.',
        previewClass: 'bg-slate-950 border-slate-700',
        surfaceClass: 'bg-slate-900',
        accentClass: 'bg-indigo-500',
        textClass: 'bg-slate-200',
        mutedClass: 'bg-slate-500',
    },
    {
        value: 'modern-tech',
        label: 'Modern Tech',
        description: 'Nuansa teknologi gelap dengan aksen cyan dan biru.',
        previewClass: 'bg-zinc-950 border-zinc-700',
        surfaceClass: 'bg-zinc-900',
        accentClass: 'bg-cyan-500',
        textClass: 'bg-zinc-200',
        mutedClass: 'bg-zinc-500',
    },
    {
        value: 'academic-blue',
        label: 'Academic Blue',
        description: 'Tampilan akademik terang, rapi, dan formal.',
        previewClass: 'bg-slate-50 border-slate-300',
        surfaceClass: 'bg-white',
        accentClass: 'bg-blue-600',
        textClass: 'bg-slate-700',
        mutedClass: 'bg-slate-400',
    },
    {
        value: 'creative-education',
        label: 'Creative Education',
        description: 'Lebih ekspresif dengan aksen rose dan pink.',
        previewClass: 'bg-rose-50 border-rose-200',
        surfaceClass: 'bg-white',
        accentClass: 'bg-rose-500',
        textClass: 'bg-rose-950',
        mutedClass: 'bg-rose-300',
    },
    {
        value: 'fresh-learning',
        label: 'Fresh Learning',
        description: 'Segar dan ramah belajar dengan palet hijau.',
        previewClass: 'bg-green-50 border-green-200',
        surfaceClass: 'bg-white',
        accentClass: 'bg-emerald-500',
        textClass: 'bg-green-900',
        mutedClass: 'bg-green-300',
    },
    {
        value: 'minimalist',
        label: 'Minimalist',
        description: 'Netral, bersih, dan fokus pada isi materi.',
        previewClass: 'bg-white border-gray-300',
        surfaceClass: 'bg-gray-50',
        accentClass: 'bg-gray-700',
        textClass: 'bg-gray-800',
        mutedClass: 'bg-gray-300',
    },
];

function themePreview(theme) {
    const preview = document.createElement('div');
    preview.className = `h-24 rounded-xl border p-3 overflow-hidden ${theme.previewClass}`;
    preview.setAttribute('aria-hidden', 'true');

    preview.innerHTML = `
        <div class="h-full rounded-lg ${theme.surfaceClass} p-3 shadow-sm flex flex-col justify-between">
            <div>
                <div class="h-2 w-10 rounded-full ${theme.accentClass} mb-2"></div>
                <div class="h-2 w-3/4 rounded-full ${theme.textClass} opacity-90 mb-1.5"></div>
                <div class="h-1.5 w-1/2 rounded-full ${theme.mutedClass} opacity-70"></div>
            </div>
            <div class="flex gap-1.5">
                <span class="h-1.5 w-8 rounded-full ${theme.accentClass} opacity-80"></span>
                <span class="h-1.5 w-5 rounded-full ${theme.mutedClass} opacity-60"></span>
            </div>
        </div>
    `;

    return preview;
}

export function enhanceSlidebookThemePicker() {
    const select = document.querySelector('form[action*="/slidebooks/"][action$="/design"] select[name="preset"]');
    if (!select || select.dataset.visualThemePicker === 'ready') {
        return;
    }

    const form = select.closest('form');
    const fieldContainer = select.parentElement;
    const studentPreviewLink = document.querySelector('a[href*="/slidebooks/"][href$="/preview"]');
    const submitButton = form?.querySelector('button[type="submit"]');
    if (!form || !fieldContainer) {
        return;
    }

    select.dataset.visualThemePicker = 'ready';
    select.hidden = true;

    const panel = document.createElement('div');
    panel.className = 'space-y-4';

    const intro = document.createElement('div');
    intro.className = 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between';
    intro.innerHTML = `
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider theme-body">Pilih Tema Visual</p>
            <p class="mt-1 text-sm theme-muted">Tema hanya mengubah tampilan. Konten dan layout slide tetap dipertahankan.</p>
        </div>
        <span data-theme-state class="inline-flex w-fit items-center rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-success-text">Tersimpan</span>
    `;

    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3';

    const livePreview = document.createElement('section');
    livePreview.className = 'rounded-2xl border teacher-card p-4';
    livePreview.innerHTML = `
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider theme-body">Live Design Preview</p>
                <p class="mt-1 text-sm theme-muted">Pratinjau ini sementara. Tema belum diterapkan ke data sampai tombol Simpan Desain ditekan.</p>
            </div>
            <span data-preview-theme class="inline-flex w-fit rounded-full border theme-accent-border theme-accent-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-accent-text"></span>
        </div>
        <div class="relative overflow-hidden rounded-xl border teacher-card shadow-inner">
            <div data-preview-loading class="absolute inset-0 z-10 flex items-center justify-center theme-surface text-sm font-semibold theme-body transition-opacity">Memuat pratinjau…</div>
            <div class="aspect-video min-h-[280px] w-full">
                <iframe data-theme-live-preview class="h-full min-h-[280px] w-full border-0 theme-surface" title="Live preview desain Slidebook" loading="lazy" sandbox="allow-scripts allow-same-origin"></iframe>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm theme-muted">
            <span>Gunakan preview penuh untuk mengecek semua 18 slide.</span>
            <div class="flex items-center gap-3">
                <button type="button" data-reset-theme class="hidden font-semibold theme-body theme-heading-hover">Kembalikan tema tersimpan</button>
                <a data-open-full-preview target="_blank" rel="noopener" class="font-semibold theme-accent-text theme-accent-text-hover">Buka Preview Penuh ↗</a>
            </div>
        </div>
    `;

    const stateBadge = intro.querySelector('[data-theme-state]');
    const previewThemeBadge = livePreview.querySelector('[data-preview-theme]');
    const previewFrame = livePreview.querySelector('[data-theme-live-preview]');
    const previewLoading = livePreview.querySelector('[data-preview-loading]');
    const fullPreviewLink = livePreview.querySelector('[data-open-full-preview]');
    const resetButton = livePreview.querySelector('[data-reset-theme]');
    const cards = new Map();
    const initialValue = select.value;
    let isDirty = false;

    const beforeUnloadHandler = (event) => {
        if (!isDirty) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    };

    window.addEventListener('beforeunload', beforeUnloadHandler);
    form.addEventListener('submit', () => {
        isDirty = false;
        window.removeEventListener('beforeunload', beforeUnloadHandler);
    });

    const previewUrlFor = (preset) => {
        if (!studentPreviewLink) {
            return null;
        }

        const url = new URL(studentPreviewLink.href, window.location.origin);
        url.searchParams.set('preset', preset);
        url.searchParams.set('embedded', '1');
        url.searchParams.set('_preview', Date.now().toString());
        return url;
    };

    const refreshLivePreview = () => {
        const theme = slidebookThemes.find((item) => item.value === select.value);
        if (previewThemeBadge) {
            previewThemeBadge.textContent = theme?.label ?? select.value;
        }

        const previewUrl = previewUrlFor(select.value);
        if (!previewUrl) {
            livePreview.classList.add('hidden');
            return;
        }

        livePreview.classList.remove('hidden');
        if (previewFrame) {
            previewLoading?.classList.remove('pointer-events-none', 'opacity-0');
            previewFrame.onload = () => previewLoading?.classList.add('pointer-events-none', 'opacity-0');
            previewFrame.src = previewUrl.toString();
        }
        if (fullPreviewLink) {
            const fullUrl = new URL(previewUrl.toString());
            fullUrl.searchParams.delete('embedded');
            fullPreviewLink.href = fullUrl.toString();
        }
    };

    const renderSelection = () => {
        cards.forEach((button, value) => {
            const active = value === select.value;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.className = active
                ? 'group rounded-2xl border theme-accent-border theme-accent-surface p-3 text-left shadow-lg shadow-indigo-950/20 ring-2 ring-indigo-500/30 transition-all'
                : 'group rounded-2xl border teacher-card p-3 text-left hover:-translate-y-0.5 theme-border-hover theme-surface-hover transition-all';

            const marker = button.querySelector('[data-theme-active]');
            if (marker) {
                marker.classList.toggle('opacity-0', !active);
                marker.classList.toggle('opacity-100', active);
            }
        });

        isDirty = select.value !== initialValue;
        form.dataset.themeDirty = isDirty ? 'true' : 'false';

        if (stateBadge) {
            stateBadge.textContent = isDirty ? 'Belum disimpan' : 'Tersimpan';
            stateBadge.className = isDirty
                ? 'inline-flex w-fit items-center rounded-full border theme-warning-border theme-warning-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-warning-text'
                : 'inline-flex w-fit items-center rounded-full border theme-success-border theme-success-surface px-2.5 py-1 text-sm font-bold uppercase tracking-wide theme-success-text';
        }

        if (submitButton) {
            submitButton.disabled = !isDirty;
            submitButton.textContent = isDirty ? 'Simpan Desain' : 'Desain Tersimpan';
            submitButton.classList.toggle('opacity-50', !isDirty);
            submitButton.classList.toggle('cursor-not-allowed', !isDirty);
        }

        resetButton?.classList.toggle('hidden', !isDirty);
        refreshLivePreview();
    };

    resetButton?.addEventListener('click', () => {
        select.value = initialValue;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        renderSelection();
    });

    slidebookThemes.forEach((theme) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.preset = theme.value;
        button.setAttribute('aria-label', `Gunakan tema ${theme.label}`);

        const preview = themePreview(theme);
        const meta = document.createElement('div');
        meta.className = 'mt-3 flex items-start justify-between gap-3';
        meta.innerHTML = `
            <div>
                <p class="text-sm font-bold theme-heading">${theme.label}</p>
                <p class="mt-1 text-sm leading-relaxed theme-body">${theme.description}</p>
            </div>
            <span data-theme-active class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full theme-accent-fill teacher-on-solid opacity-0 transition-opacity" aria-hidden="true">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42 0l-3.75-3.78a1 1 0 1 1 1.42-1.408l3.04 3.064 6.54-6.594a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" /></svg>
            </span>
        `;

        button.append(preview, meta);
        button.addEventListener('click', () => {
            select.value = theme.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderSelection();
        });

        cards.set(theme.value, button);
        grid.appendChild(button);
    });

    panel.append(intro, grid, livePreview);
    fieldContainer.insertBefore(panel, select);
    renderSelection();
}
