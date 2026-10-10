<script>
function studentSlidePresentation(total, initialVisibleBlocks = 5) {
    const safeInitialVisibleBlocks = Number.isInteger(initialVisibleBlocks)
        ? Math.min(Math.max(initialVisibleBlocks, 2), 6)
        : 5;

    return {
        total,
        currentIndex: 0,
        enteringSlide: 0,
        initialVisibleBlocks: safeInitialVisibleBlocks,
        visibleBlocks: safeInitialVisibleBlocks,
        announcement: '',
        fullscreenMessage: '',

        goToSlide(index) {
            if (!Number.isInteger(index) || index < 0 || index >= this.total || index === this.currentIndex) return;

            // Reset content density for the selected audience on each slide.
            this.visibleBlocks = this.initialVisibleBlocks;

            // Set entering state for animation
            this.enteringSlide = index;
            this.currentIndex = index;

            this.$nextTick(() => {
                const slide = this.$root.querySelector(`[data-slide-index="${index}"]`);
                const title = slide?.querySelector('h2');
                this.announcement = `Slide ${index + 1} dari ${this.total}: ${title?.textContent || ''}`;
                title?.focus({ preventScroll: true });
                window.scrollTo({ top: 0, behavior: 'instant' });

                // Re-trigger block reveal animations
                const blocks = slide?.querySelectorAll('[data-reveal]');
                if (blocks) {
                    blocks.forEach(block => {
                        block.style.animation = 'none';
                        block.offsetHeight; // force reflow
                        block.style.animation = '';
                    });
                }

                // Clear entering state after animation completes
                setTimeout(() => { this.enteringSlide = -1; }, 500);
            });
        },

        handleKey(event) {
            if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
            if (event.target.closest('input, textarea, select, button, a, summary, [contenteditable], pre, [role="region"]')) return;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                this.goToSlide(this.currentIndex + (event.key === 'ArrowRight' ? 1 : -1));
            } else if (event.key.toLowerCase() === 'f') {
                this.toggleFullscreen();
            } else if (event.key === 'Home') {
                event.preventDefault();
                this.goToSlide(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                this.goToSlide(this.total - 1);
            }
        },

        async toggleFullscreen() {
            this.fullscreenMessage = '';
            try {
                if (document.fullscreenElement) await document.exitFullscreen();
                else if (this.$root.requestFullscreen) await this.$root.requestFullscreen();
                else this.fullscreenMessage = 'Layar penuh tidak tersedia di browser ini.';
            } catch {
                this.fullscreenMessage = 'Layar penuh tidak tersedia. Anda tetap dapat membaca semua slide.';
            }
        }
    };
}
</script>
