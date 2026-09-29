const textareaResizeObservers = new WeakMap();

function resizeIframe(iframe) {
	if (!iframe || !iframe.contentWindow || !iframe.contentWindow.document.body) {
        return;
    }
    const childDocument = iframe.contentWindow.document;
    const observerInfo = textareaResizeObservers.get(iframe);
    if (typeof ResizeObserver === 'function' && observerInfo?.document !== childDocument) {
        observerInfo?.observer.disconnect();
        const observer = new ResizeObserver(function() {
            requestAnimationFrame(function() {
                if (iframe.contentDocument !== childDocument) {
                    return;
                }
                const newHeight = childDocument.body.parentElement.scrollHeight + 1;
                if (newHeight > iframe.clientHeight) {
                    iframe.style.height = newHeight + 'px';
                }
            });
        });
        childDocument.querySelectorAll('textarea').forEach(function(textarea) {
            observer.observe(textarea);
        });
        textareaResizeObservers.set(iframe, { document: childDocument, observer: observer });
    }
    iframe.style.height = '1px';
    requestAnimationFrame(function() {
        const newHeight = childDocument.body.parentElement.scrollHeight + 1;
        iframe.style.height = newHeight + 'px';
    });
}
