function closeTopFrame() {
	window.parent.postMessage({ action: 'close' }, window.location.origin);
}