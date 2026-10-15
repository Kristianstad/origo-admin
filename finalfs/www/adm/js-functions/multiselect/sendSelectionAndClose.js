function sendSelectionAndClose(targetId) {
    if (!targetId) {
        console.warn('No targetId provided – cannot send selection');
        return;
    }
    const value = getCurrentSelection();
    window.parent.postMessage({ targetId: targetId, value: value, action: 'close' }, window.location.origin);
}
