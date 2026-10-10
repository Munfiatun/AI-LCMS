<style>
/* ============================================================
   VISUAL-RICH SLIDEBOOK LAYER
   Presentation-only enrichment. It never changes learning content.
   Loaded after the base presentation design system intentionally.
   ============================================================ */

.slide-presentation .visual-rich-slide {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    min-height: min(700px, calc(100vh - 12rem));
    padding: clamp(1.75rem, 4vw, 4rem);
    border: 1px solid var(--slide-border);
    border-radius: clamp(1.4rem, 2.6vw, 2.2rem);
    background:
        linear-gradient(145deg, var(--slide-surface), var(--slide-bg));
    box-shadow: 0 28px 70px color-mix(in srgb, var(--slide-bg) 82%, transparent);
}

.slide-presentation .visual-rich-slide::before,
.slide-presentation .visual-rich-slide::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    pointer-events: none;
    z-index: -1;
}

.slide-presentation .visual-rich-slide::before {
    width: clamp(220px, 34vw, 470px);
    aspect-ratio: 1;
    right: clamp(-190px, -12vw, -70px);
    top: clamp(-180px, -11vw, -65px);
    background: radial-gradient(circle at 35% 35%, var(--slide-accent-soft), transparent 67%);
    opacity: 0.72;
}

.slide-presentation .visual-rich-slide::after {
    width: clamp(170px, 24vw, 330px);
    aspect-ratio: 1;
    left: clamp(-160px, -10vw, -55px);
    bottom: clamp(-160px, -10vw, -60px);
    border: 1px solid var(--slide-accent-soft);
    box-shadow:
        0 0 0 26px color-mix(in srgb, var(--slide-accent-soft) 24%, transparent),
        0 0 0 54px color-mix(in srgb, var(--slide-accent-soft) 10%, transparent);
    opacity: 0.45;
}

.slide-presentation .visual-rich-slide > * {
    position: relative;
    z-index: 1;
}

/* --- Cover / section hero art --- */
.slide-presentation .visual-hero-art {
    display: grid;
    place-items: center;
    min-height: 280px;
    position: relative;
}

.slide-presentation .visual-hero-orb {
    position: relative;
    display: grid;
    place-items: center;
    width: clamp(190px, 23vw, 310px);
    aspect-ratio: 1;
    border-radius: 34% 66% 61% 39% / 42% 39% 61% 58%;
    color: var(--slide-text-heading);
    background:
        linear-gradient(145deg, var(--slide-gradient-start), var(--slide-gradient-end));
    box-shadow:
        0 28px 60px color-mix(in srgb, var(--slide-primary) 32%, transparent),
        inset 0 1px 0 color-mix(in srgb, white 24%, transparent);
    transform: rotate(-5deg);
}

.slide-presentation .visual-hero-orb::before,
.slide-presentation .visual-hero-orb::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    border: 1px solid var(--slide-accent-soft);
}

.slide-presentation .visual-hero-orb::before {
    inset: -18%;
}

.slide-presentation .visual-hero-orb::after {
    inset: -34%;
    border-style: dashed;
    opacity: 0.5;
}

.slide-presentation .visual-hero-orb svg {
    width: 38%;
    height: 38%;
    transform: rotate(5deg);
    filter: drop-shadow(0 10px 24px color-mix(in srgb, var(--slide-bg) 35%, transparent));
}

.slide-presentation .visual-float-dot {
    position: absolute;
    width: 14px;
    height: 14px;
    border-radius: 999px;
    background: var(--slide-accent);
    box-shadow: 0 0 0 8px var(--slide-accent-soft);
}
.slide-presentation .visual-float-dot.dot-a { top: 15%; left: 7%; }
.slide-presentation .visual-float-dot.dot-b { right: 3%; bottom: 21%; width: 9px; height: 9px; }

.slide-presentation .layout-cover.visual-rich-slide {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(250px, 0.85fr);
    grid-template-areas:
        'heading art'
        'content art'
        'takeaway takeaway'
        'source source';
    align-content: center;
    column-gap: clamp(2rem, 6vw, 6rem);
}
.slide-presentation .layout-cover .slide-heading { grid-area: heading; align-self: end; margin-bottom: 1.5rem; }
.slide-presentation .layout-cover .slide-composition { grid-area: content; align-self: start; }
.slide-presentation .layout-cover .visual-hero-art { grid-area: art; }
.slide-presentation .layout-cover .slide-takeaway { grid-area: takeaway; }
.slide-presentation .layout-cover .slide-source { grid-area: source; }
.slide-presentation .layout-cover .slide-heading h2 {
    max-width: 13ch;
    font-size: clamp(2.6rem, 6vw, 5.7rem);
    line-height: 0.98;
    letter-spacing: -0.055em;
}
.slide-presentation .layout-cover .slide-subtitle {
    max-width: 44ch;
    font-size: clamp(1.05rem, 1.7vw, 1.35rem);
}
.slide-presentation .layout-cover .slide-blocks {
    max-width: 52ch;
}
.slide-presentation .layout-cover .learning-point {
    padding: 0;
    border: 0;
    background: transparent;
    box-shadow: none;
}

.slide-presentation .layout-section-divider.visual-rich-slide,
.slide-presentation .layout-closing.visual-rich-slide {
    display: grid;
    place-items: center;
    align-content: center;
    text-align: center;
}
.slide-presentation .layout-section-divider .slide-heading h2,
.slide-presentation .layout-closing .slide-heading h2 {
    margin-inline: auto;
    max-width: 18ch;
    font-size: clamp(2.4rem, 5vw, 4.8rem);
}
.slide-presentation .layout-section-divider .slide-subtitle,
.slide-presentation .layout-closing .slide-subtitle {
    margin-inline: auto;
}
.slide-presentation .layout-section-divider .visual-hero-art,
.slide-presentation .layout-closing .visual-hero-art {
    min-height: 185px;
    margin-bottom: 1rem;
}
.slide-presentation .layout-section-divider .visual-hero-orb,
.slide-presentation .layout-closing .visual-hero-orb {
    width: clamp(120px, 14vw, 180px);
}

/* --- Concept: stronger visual anchor --- */
.slide-presentation .layout-concept .concept-mark {
    position: relative;
    border-radius: 36% 64% 59% 41% / 45% 38% 62% 55%;
    background: linear-gradient(145deg, var(--slide-surface-raised), var(--slide-surface));
    box-shadow: 0 22px 45px color-mix(in srgb, var(--slide-primary) 14%, transparent);
}
.slide-presentation .layout-concept .concept-mark::before {
    content: '';
    position: absolute;
    inset: -16px;
    border: 1px dashed var(--slide-accent-soft);
    border-radius: inherit;
    transform: rotate(9deg);
    pointer-events: none;
}

/* --- Key points: editorial card rhythm --- */
.slide-presentation .layout-key-points .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.slide-presentation .layout-key-points .slide-block:nth-child(3n + 1) .learning-point {
    background: linear-gradient(135deg, var(--slide-surface-raised), var(--slide-surface));
    border-top: 3px solid var(--slide-primary);
}
.slide-presentation .layout-key-points .slide-block:nth-child(3n + 2) .learning-point {
    border-top: 3px solid var(--slide-accent);
}
.slide-presentation .layout-key-points .slide-block:nth-child(3n) .learning-point {
    background: linear-gradient(145deg, var(--slide-surface), var(--slide-surface-raised));
}
.slide-presentation .layout-key-points .learning-point {
    min-height: 145px;
}

/* --- Process: horizontal visual journey on wide screens --- */
.slide-presentation .layout-process .slide-blocks {
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 1.1rem;
    position: relative;
}
.slide-presentation .layout-process .slide-block {
    position: relative;
}
.slide-presentation .layout-process .learning-point {
    flex-direction: column;
    padding-top: 1.25rem;
    background: linear-gradient(155deg, var(--slide-surface-raised), var(--slide-surface));
}
.slide-presentation .layout-process .step-marker {
    width: 2.8rem;
    height: 2.8rem;
    display: grid;
    place-items: center;
    border-radius: 0.9rem;
    background: linear-gradient(135deg, var(--slide-gradient-start), var(--slide-gradient-end));
    color: white;
    font-weight: 800;
    box-shadow: 0 10px 22px color-mix(in srgb, var(--slide-primary) 25%, transparent);
}

/* --- Timeline: rail + milestones --- */
.slide-presentation .layout-timeline .slide-blocks {
    position: relative;
    gap: 0.85rem;
    padding-left: 2rem;
}
.slide-presentation .layout-timeline .slide-blocks::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 1rem;
    bottom: 1rem;
    width: 2px;
    background: linear-gradient(var(--slide-gradient-start), var(--slide-gradient-end));
    opacity: 0.7;
}
.slide-presentation .layout-timeline .slide-block {
    position: relative;
}
.slide-presentation .layout-timeline .slide-block::before {
    content: '';
    position: absolute;
    left: -1.82rem;
    top: 1.7rem;
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
    background: var(--slide-accent);
    box-shadow: 0 0 0 6px var(--slide-accent-soft);
}

/* --- Comparison: intentional split cards --- */
.slide-presentation .layout-comparison .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}
.slide-presentation .layout-comparison .slide-block:nth-child(odd) .learning-point {
    border-top: 3px solid var(--slide-primary);
}
.slide-presentation .layout-comparison .slide-block:nth-child(even) .learning-point {
    border-top: 3px solid var(--slide-accent);
}
.slide-presentation .layout-comparison .comparison-scroll {
    grid-column: 1 / -1;
}

/* --- Images and diagrams feel like presentation visuals, not attachments --- */
.slide-presentation .layout-image-focus .slide-image,
.slide-presentation .layout-diagram .slide-image {
    overflow: hidden;
    border-radius: 1.4rem;
    border: 1px solid var(--slide-border);
    background: var(--slide-surface-raised);
    box-shadow: 0 24px 50px color-mix(in srgb, var(--slide-bg) 45%, transparent);
}
.slide-presentation .layout-image-focus .slide-image img,
.slide-presentation .layout-diagram .slide-image img {
    width: 100%;
    max-height: min(52vh, 520px);
    object-fit: contain;
}

/* --- Summary: visual checklist --- */
.slide-presentation .layout-summary .slide-blocks {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.slide-presentation .layout-summary .learning-point {
    background: linear-gradient(145deg, var(--slide-surface-raised), var(--slide-surface));
}

/* --- Source-faithful percentage chart --- */
.slide-presentation .visual-stat-chart {
    margin-top: clamp(1.5rem, 3vw, 2.5rem);
    padding: clamp(1.25rem, 2.5vw, 2rem);
    border: 1px solid var(--slide-border);
    border-radius: 1.35rem;
    background: linear-gradient(145deg, var(--slide-surface-raised), var(--slide-surface));
}
.slide-presentation .visual-stat-chart-header {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.slide-presentation .visual-stat-chart-title {
    color: var(--slide-text-heading);
    font-size: 1rem;
    font-weight: 750;
}
.slide-presentation .visual-stat-chart-note {
    color: var(--slide-text-muted);
    font-size: 0.72rem;
    text-align: right;
}
.slide-presentation .visual-stat-list {
    display: grid;
    gap: 0.95rem;
}
.slide-presentation .visual-stat-row {
    display: grid;
    grid-template-columns: minmax(110px, 0.8fr) minmax(160px, 2fr) auto;
    align-items: center;
    gap: 0.85rem;
}
.slide-presentation .visual-stat-label {
    color: var(--slide-text);
    font-size: 0.82rem;
    font-weight: 650;
    line-height: 1.35;
}
.slide-presentation .visual-stat-track {
    height: 0.78rem;
    overflow: hidden;
    border-radius: 999px;
    background: var(--slide-border);
}
.slide-presentation .visual-stat-fill {
    display: block;
    height: 100%;
    width: var(--visual-stat-value);
    border-radius: inherit;
    background: linear-gradient(90deg, var(--slide-gradient-start), var(--slide-gradient-end));
    box-shadow: 0 0 18px color-mix(in srgb, var(--slide-primary) 25%, transparent);
}
.slide-presentation .visual-stat-value {
    min-width: 3.5rem;
    color: var(--slide-text-heading);
    font-size: 0.85rem;
    font-weight: 800;
    text-align: right;
}

/* --- Takeaway becomes a deliberate visual footer --- */
.slide-presentation .visual-rich-slide .slide-takeaway {
    border-left: 4px solid var(--slide-primary);
    background: linear-gradient(90deg, var(--slide-surface-raised), var(--slide-surface));
}

@media (max-width: 860px) {
    .slide-presentation .visual-rich-slide {
        min-height: auto;
        padding: 1.35rem;
        border-radius: 1.35rem;
    }

    .slide-presentation .layout-cover.visual-rich-slide {
        display: block;
    }

    .slide-presentation .layout-cover .visual-hero-art {
        min-height: 200px;
        margin: 1rem 0 1.5rem;
    }

    .slide-presentation .layout-cover .visual-hero-orb {
        width: 170px;
    }

    .slide-presentation .layout-key-points .slide-blocks,
    .slide-presentation .layout-comparison .slide-blocks,
    .slide-presentation .layout-summary .slide-blocks {
        grid-template-columns: 1fr;
    }

    .slide-presentation .layout-process .slide-blocks {
        grid-template-columns: 1fr;
    }

    .slide-presentation .visual-stat-row {
        grid-template-columns: 1fr auto;
        gap: 0.4rem 0.75rem;
    }

    .slide-presentation .visual-stat-track {
        grid-column: 1 / -1;
        grid-row: 2;
    }

    .slide-presentation .visual-stat-chart-header {
        align-items: start;
        flex-direction: column;
    }

    .slide-presentation .visual-stat-chart-note {
        text-align: left;
    }
}

@media (prefers-reduced-motion: reduce) {
    .slide-presentation .visual-hero-orb,
    .slide-presentation .visual-rich-slide::before,
    .slide-presentation .visual-rich-slide::after {
        transform: none;
    }
}
</style>
