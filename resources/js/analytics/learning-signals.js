const COURSE_ANALYTICS_PATH = /^\/instructor\/courses\/\d+\/analytics\/?$/;

const signalMeta = {
    high: {
        label: 'Prioritas Tinggi',
        className: 'border-rose-500/30 bg-rose-500/10 text-rose-200',
    },
    medium: {
        label: 'Perlu Ditinjau',
        className: 'border-amber-500/30 bg-amber-500/10 text-amber-100',
    },
    positive: {
        label: 'Stabil',
        className: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-100',
    },
    info: {
        label: 'Informasi',
        className: 'border-indigo-500/30 bg-indigo-500/10 text-indigo-100',
    },
};

function element(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== '') node.textContent = text;
    return node;
}

function metric(label, value, hint = '') {
    const card = element('div', 'rounded-xl border border-slate-800 bg-slate-950/60 p-3');
    card.append(
        element('div', 'text-xl font-extrabold text-white', String(value)),
        element('div', 'mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-500', label),
    );
    if (hint) card.append(element('div', 'mt-1 text-[10px] text-slate-600', hint));
    return card;
}

function signalCard(signal) {
    const meta = signalMeta[signal.severity] || signalMeta.info;
    const card = element('div', `rounded-xl border p-4 ${meta.className}`);
    card.append(
        element('div', 'text-[10px] font-bold uppercase tracking-wider opacity-75', meta.label),
        element('h4', 'mt-1.5 text-sm font-bold text-white', signal.title),
        element('p', 'mt-2 text-xs leading-relaxed opacity-80', signal.message),
    );
    return card;
}

function questionRow(question) {
    const row = element('div', 'rounded-xl border border-slate-800 bg-slate-950/50 p-4');
    const top = element('div', 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between');
    const copy = element('div', 'min-w-0');
    copy.append(
        element('p', 'text-sm font-semibold leading-relaxed text-white', question.question_text),
        element(
            'p',
            'mt-1 text-[11px] text-slate-500',
            `${question.responses} respons • ${question.difficulty || 'unknown'}${question.topic ? ` • ${question.topic}` : ''}`,
        ),
    );

    const rateClass = question.sample_sufficient
        ? (question.correct_rate < 50 ? 'border-rose-500/30 bg-rose-500/10 text-rose-300'
            : (question.correct_rate < 70 ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'))
        : 'border-slate-700 bg-slate-800/60 text-slate-400';
    const rate = element('span', `shrink-0 rounded-full border px-2.5 py-1 text-xs font-bold ${rateClass}`,
        question.sample_sufficient ? `${question.correct_rate}% benar` : 'Data terbatas');
    top.append(copy, rate);
    row.append(top);

    if (question.sample_sufficient && question.correct_rate < 70) {
        row.append(element(
            'p',
            'mt-3 text-xs leading-relaxed text-slate-400',
            'Tinjau kesesuaian materi, kejelasan stem, kunci jawaban, dan kualitas distraktor sebelum menyimpulkan bahwa konsep belum dikuasai.',
        ));
    }

    return row;
}

function renderInsights(data) {
    const container = document.querySelector('main > div');
    if (!container) return;

    document.querySelector('[data-learning-signals-panel]')?.remove();

    const panel = element('section', 'rounded-2xl border border-slate-800 bg-slate-900/80 shadow-xl overflow-hidden');
    panel.dataset.learningSignalsPanel = 'true';

    const header = element('div', 'border-b border-slate-800 p-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between');
    const title = element('div');
    title.append(
        element('div', 'text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-400', 'Learning Signals'),
        element('h3', 'mt-1 text-lg font-extrabold text-white', 'Sinyal Pembelajaran Berbasis Data'),
        element('p', 'mt-1 max-w-3xl text-xs leading-relaxed text-slate-400', 'Ringkasan ini memakai progres dan hasil assessment nyata. Sinyal adalah alat bantu tindak lanjut, bukan diagnosis otomatis terhadap kemampuan siswa.'),
    );

    const evidenceBadgeClass = data.evidence.level === 'adequate'
        ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
        : (data.evidence.level === 'developing'
            ? 'border-amber-500/30 bg-amber-500/10 text-amber-300'
            : 'border-slate-700 bg-slate-800/60 text-slate-400');
    const evidenceBadge = element('span', `inline-flex shrink-0 rounded-full border px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide ${evidenceBadgeClass}`, data.evidence.label);
    header.append(title, evidenceBadge);

    const body = element('div', 'p-5 space-y-5');
    const metrics = element('div', 'grid grid-cols-2 gap-2 lg:grid-cols-4');
    metrics.append(
        metric('Cakupan Peserta', `${data.evidence.participant_coverage_percent}%`),
        metric('Attempt Representatif', data.evidence.representative_attempts, 'Terbaru per siswa/Quiz'),
        metric('Respons Butir', data.evidence.question_responses),
        metric('Pass Rate Terbaru', `${data.evidence.latest_attempt_pass_rate}%`, 'Attempt terbaru per siswa/Quiz'),
    );
    body.append(metrics);

    const signals = element('div', 'grid grid-cols-1 gap-3 lg:grid-cols-2');
    (data.learning_signals || []).forEach((signal) => signals.append(signalCard(signal)));
    body.append(signals);

    const questions = data.priority_questions || [];
    const questionSection = element('div', 'border-t border-slate-800 pt-5');
    const questionHeader = element('div', 'mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between');
    const questionTitle = element('div');
    questionTitle.append(
        element('h4', 'text-sm font-bold text-white', 'Butir yang Perlu Ditinjau'),
        element('p', 'mt-1 text-[11px] text-slate-500', `Hanya butir dengan minimal ${data.evidence.minimum_item_responses} respons yang diberi label tindakan.`),
    );
    questionHeader.append(questionTitle);
    questionSection.append(questionHeader);

    const list = element('div', 'space-y-2');
    if (questions.length === 0) {
        list.append(element('div', 'rounded-xl border border-dashed border-slate-800 px-4 py-5 text-center text-xs text-slate-500', 'Belum ada butir dengan bukti cukup yang memerlukan tinjauan khusus.'));
    } else {
        questions.forEach((question) => list.append(questionRow(question)));
    }
    questionSection.append(list);
    body.append(questionSection);

    panel.append(header, body);

    const interventionHeading = Array.from(container.querySelectorAll('h3'))
        .find((heading) => heading.textContent.includes('Ringkasan Intervensi'));
    const anchor = interventionHeading?.closest('.rounded-2xl');
    if (anchor) anchor.insertAdjacentElement('afterend', panel);
    else container.append(panel);
}

async function loadInsights() {
    const url = new URL(window.location.href);
    url.searchParams.set('insights', '1');

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

export async function enhanceLearningAnalytics() {
    if (!COURSE_ANALYTICS_PATH.test(window.location.pathname)) return;

    try {
        const data = await loadInsights();
        if (data) renderInsights(data);
    } catch (error) {
        console.warn('Learning Signals could not be loaded.', error);
    }
}
