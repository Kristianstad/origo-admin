function resizeParentFrame() {
    if (window.parent !== window) {
        window.addEventListener('load', function() {
            window.parent.postMessage({ action: 'resize' }, window.location.origin);
        });
    }
}