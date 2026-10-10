import { enhanceAccessibility } from './accessibility';
import { enhanceSlidebookThemePicker } from './slidebook/theme-picker';
import { enhanceSlidebookAuthoring } from './slidebook/authoring';
import { enhanceSlidebookQualityPanel } from './slidebook/quality-panel';

function initializeEnhancements() {
    enhanceAccessibility();
    enhanceSlidebookThemePicker();
    enhanceSlidebookAuthoring();
    enhanceSlidebookQualityPanel();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEnhancements, { once: true });
} else {
    initializeEnhancements();
}
