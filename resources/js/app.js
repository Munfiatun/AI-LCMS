import { enhanceSlidebookThemePicker } from './slidebook/theme-picker';
import { enhanceSlidebookAuthoring } from './slidebook/authoring';
import { enhanceSlidebookQualityPanel } from './slidebook/quality-panel';

function initializeSlidebookEnhancements() {
    enhanceSlidebookThemePicker();
    enhanceSlidebookAuthoring();
    enhanceSlidebookQualityPanel();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSlidebookEnhancements, { once: true });
} else {
    initializeSlidebookEnhancements();
}
