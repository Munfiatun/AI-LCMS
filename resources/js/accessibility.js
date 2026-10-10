function ensureMainLandmark() {
    const main = document.querySelector('main');
    if (!main) return;

    if (!main.id) main.id = 'main-content';
    if (!main.hasAttribute('tabindex')) main.setAttribute('tabindex', '-1');

    if (!document.querySelector('.a11y-skip-link')) {
        const link = document.createElement('a');
        link.href = `#${main.id}`;
        link.className = 'a11y-skip-link';
        link.textContent = 'Lewati ke konten utama';
        document.body.prepend(link);

        link.addEventListener('click', () => {
            window.requestAnimationFrame(() => main.focus({ preventScroll: true }));
        });
    }
}

function markCurrentNavigation() {
    const currentPath = window.location.pathname.replace(/\/$/, '') || '/';

    document.querySelectorAll('nav a[href]').forEach((link) => {
        try {
            const target = new URL(link.href, window.location.origin);
            const targetPath = target.pathname.replace(/\/$/, '') || '/';
            if (target.origin === window.location.origin && targetPath === currentPath) {
                link.setAttribute('aria-current', 'page');
            }
        } catch {
            // Ignore malformed or non-navigational links.
        }
    });
}

function enhanceQuizAccessibility() {
    const form = document.getElementById('examForm');
    if (!form) return;

    const questionSections = Array.from(form.querySelectorAll('section[x-show*="currentQuestion"]'));

    questionSections.forEach((section, index) => {
        const questionText = section.querySelector('.prose');
        if (!questionText) return;

        const questionId = `quiz-question-${index + 1}`;
        questionText.id = questionId;
        questionText.setAttribute('tabindex', '-1');
        section.setAttribute('role', 'region');
        section.setAttribute('aria-labelledby', questionId);

        const firstRadio = section.querySelector('input[type="radio"]');
        const optionGroup = firstRadio?.closest('.space-y-3');
        if (optionGroup) {
            optionGroup.setAttribute('role', 'radiogroup');
            optionGroup.setAttribute('aria-labelledby', questionId);
        }

        const observer = new MutationObserver(() => {
            const visible = window.getComputedStyle(section).display !== 'none';
            if (visible && section.dataset.a11yWasVisible !== 'true') {
                questionSections.forEach((candidate) => { candidate.dataset.a11yWasVisible = 'false'; });
                section.dataset.a11yWasVisible = 'true';
                window.requestAnimationFrame(() => questionText.focus({ preventScroll: true }));
            }
        });
        observer.observe(section, { attributes: true, attributeFilter: ['style', 'class'] });

        section.dataset.a11yWasVisible = window.getComputedStyle(section).display !== 'none' ? 'true' : 'false';
    });

    const timer = Array.from(form.querySelectorAll('p')).find((node) => node.hasAttribute('x-text') && node.getAttribute('x-text') === 'formattedTime');
    if (timer) {
        timer.setAttribute('role', 'timer');
        timer.setAttribute('aria-label', 'Sisa waktu pengerjaan');
        timer.setAttribute('aria-live', 'off');
    }

    form.querySelectorAll('aside button[type="button"]').forEach((button) => {
        const number = button.textContent.trim();
        if (/^\d+$/.test(number) && !button.hasAttribute('aria-label')) {
            button.setAttribute('aria-label', `Buka soal ${number}`);
        }
    });
}

function enhanceSlidebookAccessibility() {
    const presentation = document.querySelector('.slide-presentation');
    if (!presentation) return;

    const dots = Array.from(presentation.querySelectorAll('.progress-dot'));
    if (dots.length === 0) return;

    const syncCurrentDot = () => {
        dots.forEach((dot) => {
            const active = dot.classList.contains('active');
            if (active) dot.setAttribute('aria-current', 'step');
            else dot.removeAttribute('aria-current');
        });
    };

    dots.forEach((dot) => {
        new MutationObserver(syncCurrentDot).observe(dot, { attributes: true, attributeFilter: ['class'] });
    });
    syncCurrentDot();
}

function enhanceDialoglessStatus() {
    document.querySelectorAll('[role="status"]').forEach((status) => {
        if (!status.hasAttribute('aria-live')) status.setAttribute('aria-live', 'polite');
        if (!status.hasAttribute('aria-atomic')) status.setAttribute('aria-atomic', 'true');
    });
}

export function enhanceAccessibility() {
    ensureMainLandmark();
    markCurrentNavigation();
    enhanceQuizAccessibility();
    enhanceSlidebookAccessibility();
    enhanceDialoglessStatus();
}
