document.querySelectorAll('[data-autohide]').forEach((element) => {
    window.setTimeout(() => {
        element.remove();
    }, 4500);
});

const heroHeader = document.querySelector('[data-hero-header]');

if (heroHeader) {
    let frameRequested = false;

    const syncHeroHeaderState = () => {
        heroHeader.classList.toggle('is-scrolled', window.scrollY > 8);
        frameRequested = false;
    };

    const requestHeroHeaderState = () => {
        if (!frameRequested) {
            frameRequested = true;
            window.requestAnimationFrame(syncHeroHeaderState);
        }
    };

    syncHeroHeaderState();
    window.addEventListener('scroll', requestHeroHeaderState, { passive: true });
}
