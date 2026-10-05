const iframeResizeObservers = new WeakMap();

function resizeIframe(iframe, autoResize = false) {
	if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document.body) {
        return;
    }
    const childDocument = iframe.contentWindow.document;
    const observerInfo = iframeResizeObservers.get(iframe);
    if (typeof ResizeObserver === 'function' && (observerInfo?.document !== childDocument || observerInfo?.autoResize !== autoResize)) {
        observerInfo?.observer.disconnect();
        const observer = new ResizeObserver(function() {
            requestAnimationFrame(function() {
                if (iframe.contentDocument !== childDocument) {
                    return;
                }
                const newHeight = (autoResize ? childDocument.body.scrollHeight : childDocument.body.parentElement.scrollHeight) + 1;
                if (autoResize || newHeight > iframe.clientHeight) {
                    iframe.style.height = newHeight + 'px';
                }
            });
        });
        childDocument.querySelectorAll('textarea').forEach(function(textarea) {
            observer.observe(textarea);
        });
        iframeResizeObservers.set(iframe, { document: childDocument, autoResize: autoResize, observer: observer });
    }
    iframe.style.resize = autoResize ? 'none' : 'vertical';
    if (autoResize) {
        requestAnimationFrame(function() {
            if (iframe.contentDocument === childDocument) {
                iframe.style.height = childDocument.body.scrollHeight + 1 + 'px';
            }
        });
        return;
    }
    iframe.style.height = '1px';
    requestAnimationFrame(function() {
        const newHeight = childDocument.body.parentElement.scrollHeight + 1;
        iframe.style.height = newHeight + 'px';
    });
}
