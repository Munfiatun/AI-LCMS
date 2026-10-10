<style>
/* ======================================================
   VISUAL LEARNING ENGINE — AGE-AWARE EDUCATIONAL VISUALS
   ====================================================== */
.slide-presentation .learning-scene {
    width: min(100%, 920px);
    margin: 0 auto clamp(1.25rem, 3vw, 2.4rem);
    padding: clamp(0.7rem, 1.5vw, 1.1rem);
    border: 1px solid var(--slide-border);
    border-radius: calc(var(--slide-radius) + 0.35rem);
    background:
        radial-gradient(circle at 15% 20%, var(--slide-accent-soft), transparent 28%),
        linear-gradient(145deg, var(--slide-surface-raised), var(--slide-surface));
    overflow: hidden;
}

.slide-presentation .learning-scene svg {
    display: block;
    width: 100%;
    height: auto;
    max-height: 340px;
}

.slide-presentation .learning-scene .scene-node {
    fill: var(--slide-surface);
    stroke: var(--slide-border);
    stroke-width: 2;
}

.slide-presentation .learning-scene .scene-node-emphasis {
    stroke: var(--slide-primary);
    stroke-width: 2.5;
}

.slide-presentation .learning-scene .scene-screen {
    fill: var(--slide-surface-raised);
    stroke: var(--slide-accent-soft);
    stroke-width: 2;
}

.slide-presentation .learning-scene .scene-accent-fill,
.slide-presentation .learning-scene .scene-dot {
    fill: var(--slide-primary);
    opacity: 0.92;
}

.slide-presentation .learning-scene .scene-shield {
    fill: var(--slide-surface-raised);
    stroke: var(--slide-primary);
    stroke-width: 3;
}

.slide-presentation .learning-scene .scene-icon-line,
.slide-presentation .learning-scene .scene-connector path {
    fill: none;
    stroke: var(--slide-accent);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.slide-presentation .learning-scene .scene-icon-heavy {
    stroke-width: 5;
}

.slide-presentation .learning-scene .scene-connector path {
    stroke: var(--slide-primary);
    opacity: 0.75;
    stroke-width: 2.5;
}

.slide-presentation .learning-scene .scene-arrow-head {
    fill: var(--slide-primary);
}

.slide-presentation .learning-scene .scene-label {
    fill: var(--slide-text-heading);
    font-size: 15px;
    font-weight: 750;
    letter-spacing: 0.01em;
}

/* Age-aware density: SMP prioritises larger visuals and fewer simultaneous ideas. */
.slide-presentation.audience-smp .learning-scene {
    width: min(100%, 980px);
    padding: clamp(0.8rem, 2vw, 1.35rem);
    border-width: 2px;
}

.slide-presentation.audience-smp .learning-scene svg {
    max-height: 380px;
}

.slide-presentation.audience-smp .learning-scene .scene-label {
    font-size: 17px;
}

.slide-presentation.audience-smp .slide-heading h2 {
    max-width: 22ch;
    font-size: clamp(2rem, 4vw, 3.25rem);
}

.slide-presentation.audience-smp .slide-subtitle {
    max-width: 58ch;
    font-size: clamp(1.05rem, 1.8vw, 1.28rem);
}

.slide-presentation.audience-smp .learning-point {
    padding: clamp(1.25rem, 2vw, 1.8rem);
}

.slide-presentation.audience-smp .point-text,
.slide-presentation.audience-smp .learning-callout p {
    font-size: clamp(1.05rem, 1.6vw, 1.2rem);
    line-height: 1.75;
}

/* SMA/SMK keeps the visual explanation prominent while allowing denser detail. */
.slide-presentation.audience-sma_smk .learning-scene {
    max-width: 900px;
}

/* Scene becomes a two-column explanation for suitable concept slides. */
@media (min-width: 900px) {
    .slide-presentation .visual-learning-with-scene:not(.layout-cover):not(.layout-section-divider):not(.layout-closing) .visual-learning-main {
        display: grid;
        grid-template-columns: minmax(300px, 0.9fr) minmax(0, 1.15fr);
        gap: clamp(1.4rem, 3vw, 3rem);
        align-items: start;
    }

    .slide-presentation .visual-learning-with-scene:not(.layout-cover):not(.layout-section-divider):not(.layout-closing) .learning-scene {
        position: sticky;
        top: 1rem;
        margin-bottom: 0;
    }

    .slide-presentation.audience-smp .visual-learning-with-scene:not(.layout-cover):not(.layout-section-divider):not(.layout-closing) .visual-learning-main {
        grid-template-columns: minmax(340px, 1fr) minmax(0, 1fr);
    }
}

@media (max-width: 640px) {
    .slide-presentation .learning-scene {
        padding: 0.35rem;
        border-radius: var(--slide-radius);
    }

    .slide-presentation .learning-scene .scene-label {
        font-size: 18px;
    }

    .slide-presentation .learning-scene svg {
        min-width: 600px;
        width: 600px;
    }

    .slide-presentation .learning-scene {
        overflow-x: auto;
        overscroll-behavior-inline: contain;
    }
}

@media (prefers-reduced-motion: reduce) {
    .slide-presentation .learning-scene *,
    .slide-presentation .learning-scene {
        animation: none !important;
        transition: none !important;
    }
}
</style>
