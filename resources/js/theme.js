const THEME_STORAGE_KEY = 'ai-lcms-theme';
const VALID_THEMES = new Set(['dark', 'light']);

function storedTheme() {
    try {
        const value = window.localStorage.getItem(THEME_STORAGE_KEY);
        return VALID_THEMES.has(value) ? value : 'dark';
    } catch {
        return 'dark';
    }
}

export function applyTheme(theme) {
    const nextTheme = VALID_THEMES.has(theme) ? theme : 'dark';
    document.documentElement.dataset.theme = nextTheme;
    document.documentElement.style.colorScheme = nextTheme;

    try {
        window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
    } catch {
        // The selected theme still applies for the current page when storage is unavailable.
    }

    document.dispatchEvent(new CustomEvent('ai-lcms-theme-changed', { detail: { theme: nextTheme } }));
    return nextTheme;
}

// Apply as soon as this module is evaluated to reduce theme flash between pages.
applyTheme(storedTheme());

function themeIcon(theme) {
    return theme === 'light'
        ? '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42m0-12.72-1.42 1.42M7.06 16.94l-1.42 1.42"/><circle cx="12" cy="12" r="4"/></svg>'
        : '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.5 14.2A8.5 8.5 0 019.8 3.5 8.5 8.5 0 1020.5 14.2Z"/></svg>';
}

function updateThemeControls(theme) {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.innerHTML = `${themeIcon(theme)}<span class="hidden sm:inline">${theme === 'light' ? 'Terang' : 'Gelap'}</span>`;
        button.setAttribute('aria-label', `Tema saat ini ${theme === 'light' ? 'terang' : 'gelap'}. Klik untuk mengganti tema.`);
        button.setAttribute('title', theme === 'light' ? 'Ganti ke tema gelap' : 'Ganti ke tema terang');
    });

    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        const active = button.dataset.themeChoice === theme;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('ring-2', active);
        button.classList.toggle('ring-indigo-500', active);
        button.classList.toggle('border-indigo-500', active);
    });
}

function toggleTheme() {
    const next = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
    updateThemeControls(applyTheme(next));
}

function bindThemeToggles() {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        if (button.dataset.themeBound === 'true') {
            return;
        }

        button.dataset.themeBound = 'true';
        button.addEventListener('click', toggleTheme);
    });
}

function createHeaderControls() {
    const header = document.querySelector('header');
    if (!header || header.querySelector('[data-theme-toggle]')) {
        return;
    }

    const existingHomeLink = Array.from(header.children).find((element) => element.tagName === 'A');
    if (!existingHomeLink) {
        return;
    }

    const controls = document.createElement('div');
    controls.className = 'flex items-center gap-2';

    const themeButton = document.createElement('button');
    themeButton.type = 'button';
    themeButton.dataset.themeToggle = 'true';
    themeButton.className = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-3 text-sm font-semibold text-slate-300 transition hover:bg-slate-700';

    const profileLink = document.createElement('a');
    profileLink.href = '/profile';
    profileLink.className = 'inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-800 px-3 text-sm font-semibold text-slate-300 transition hover:bg-slate-700';
    profileLink.setAttribute('aria-label', 'Buka profil');
    profileLink.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21a8 8 0 0116 0"/></svg><span class="hidden md:inline">Profil</span>';

    existingHomeLink.classList.add('hidden', 'xl:inline-flex');
    header.replaceChild(controls, existingHomeLink);
    controls.append(themeButton, profileLink, existingHomeLink);
}

function bindProfileThemeChoices() {
    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
        button.addEventListener('click', () => {
            updateThemeControls(applyTheme(button.dataset.themeChoice));
        });
    });
}

function enhanceEducationMotion() {
    const brandBook = document.querySelector('aside > div:first-child a svg');
    if (brandBook) {
        brandBook.classList.add('education-book-float');
    }
}

export function enhanceThemeExperience() {
    createHeaderControls();
    bindThemeToggles();
    bindProfileThemeChoices();
    enhanceEducationMotion();
    updateThemeControls(document.documentElement.dataset.theme || storedTheme());
}
