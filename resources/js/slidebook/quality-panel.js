const REVIEW_PATH = /^\/instructor\/materials\/\d+\/slidebook\/review\/?$/;

const statusMeta = {
    good: {
        label: 'Siap Ditinjau',
        className: 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30',
    },
    needs_attention: {
        label: 'Perlu Perhatian',
        className: 'bg-amber-500/10 text-amber-300 border-amber-500/30',
    },
    blocked: {
        label: 'Perlu Diperbaiki',
        className: 'bg-rose-500/10 text-rose-300 border-rose-500/30',
    },
};

function element(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== '') node.textContent = text;
    return node;
}

function audienceLabel(audience) {
    if (audience === 'smp') return 'SMP';
    if (audience === 'general') return 'Umum';
    return 'SMA/SMK';
}

function metric(label, value) {
    const card = element('div', 'rounded-xl bg-slate-950/70 border border-slate-800 px-3 py-2.5');
    card.append(
        element('div', 'text-lg font-extrabold text-white', String(value)),
        element('div', 'text-[10px] uppercase tracking-wider text-slate-500 mt-0.5', label),
    );
    return card;
}

function issueRow(issue) {
    const critical = issue.severity === 'critical';
    const row = element(
        'div',
        `flex items-start gap-3 rounded-xl border px-3 py-2.5 ${critical
            ? 'bg-rose-950/20 border-rose-900/40'
            : 'bg-amber-950/15 border-amber-900/30'}`,
    );
    row.append(
        element(
            'span',
            `mt-0.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black ${critical
                ? 'bg-rose-500/20 text-rose-300'
                : 'bg-amber-500/20 text-amber-300'}`,
            critical ? '!' : 'i',
        ),
        element('p', `text-xs leading-relaxed ${critical ? 'text-rose-100' : 'text-amber-100/90'}`, issue.message),
    );
    return row;
}

function renderQualityPanel(report) {
    const container = document.querySelector('main > div');
    if (!container) return;

    document.querySelector('[data-slidebook-quality-panel]')?.remove();

    const panel = element('section', 'rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl overflow-hidden');
    panel.dataset.slidebookQualityPanel = 'true';

    const header = element('div', 'p-5 border-b border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4');
    const intro = element('div');
    intro.append(
        element('div', 'text-[10px] uppercase tracking-[0.18em] font-bold text-cyan-400', 'Slide Quality Check'),
        element('h2', 'text-base font-extrabold text-white mt-1', 'Pemeriksaan Kualitas Slidebook'),
        element(
            'p',
            'text-xs text-slate-400 mt-1',
            `Pemeriksaan otomatis untuk target ${audienceLabel(report.audience)}. Warning membantu guru meninjau kualitas; masalah kritis harus diperbaiki sebelum persetujuan.`,
        ),
    );

    const scoreWrap = element('div', 'flex items-center gap-3');
    const meta = statusMeta[report.status] || statusMeta.needs_attention;
    const badge = element('span', `px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase ${meta.className}`, meta.label);
    const score = element('div', 'text-right');
    score.append(
        element('div', 'text-3xl font-black text-white leading-none', `${report.score}/100`),
        element('div', 'text-[10px] uppercase tracking-wider text-slate-500 mt-1', 'Quality Score'),
    );
    scoreWrap.append(badge, score);
    header.append(intro, scoreWrap);

    const body = element('div', 'p-5 space-y-4');
    const metrics = element('div', 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2');
    metrics.append(
        metric('Slide Aktif', report.summary.slides),
        metric('Masalah Kritis', report.summary.critical_count),
        metric('Warning', report.summary.warning_count),
        metric('Perlu Dicek Guru', report.summary.needs_review_count),
        metric('Cakupan Sumber', `${report.summary.source_coverage_percent}%`),
        metric('Variasi Tampilan', report.summary.unique_layouts),
    );
    body.append(metrics);

    const issues = Array.isArray(report.issues) ? report.issues : [];
    const issueSection = element('div', 'space-y-2');
    if (issues.length === 0) {
        issueSection.append(element('div', 'rounded-xl bg-emerald-950/20 border border-emerald-900/30 px-4 py-3 text-xs text-emerald-200', 'Tidak ada masalah kualitas yang terdeteksi. Tetap lakukan pengecekan akhir sebelum menerbitkan.'));
    } else {
        const heading = element('div', 'flex items-center justify-between gap-3');
        heading.append(
            element('h3', 'text-xs font-bold text-white', 'Temuan yang Perlu Diperiksa'),
            element('span', 'text-[10px] text-slate-500', `${issues.length} temuan`),
        );
        issueSection.append(heading);
        issues.slice(0, 8).forEach((issue) => issueSection.append(issueRow(issue)));
        if (issues.length > 8) {
            issueSection.append(element('p', 'text-[11px] text-slate-500', `+ ${issues.length - 8} temuan lain. Perbaiki temuan utama terlebih dahulu lalu muat ulang halaman.`));
        }
    }
    body.append(issueSection);

    panel.append(header, body);
    container.prepend(panel);
}

async function loadQualityReport() {
    const url = new URL(window.location.href);
    url.searchParams.set('quality', '1');

    const response = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) return null;
    return response.json();
}

export async function enhanceSlidebookQualityPanel() {
    if (!REVIEW_PATH.test(window.location.pathname)) return;

    try {
        const report = await loadQualityReport();
        if (report) renderQualityPanel(report);
    } catch (error) {
        console.warn('Slidebook quality panel could not be loaded.', error);
    }
}
