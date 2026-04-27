document.querySelectorAll('[data-autohide]').forEach((element) => {
    window.setTimeout(() => {
        element.remove();
    }, 4500);
});
