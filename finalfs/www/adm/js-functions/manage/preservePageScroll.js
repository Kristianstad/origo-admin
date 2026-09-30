function preservePageScroll() 
{
	document.addEventListener('submit', event => {
		if (event.target.target && event.target.target !== '_self') {
			return;
		}
		sessionStorage.setItem('scrollPosition', window.scrollY);
	}, true);

	const savedScrollPosition = sessionStorage.getItem('scrollPosition');
	if (savedScrollPosition === null) {
		return;
	}

	const scrollPosition = Number.parseInt(savedScrollPosition, 10);
	if (!Number.isFinite(scrollPosition) || scrollPosition <= 0) {
		sessionStorage.removeItem('scrollPosition');
		return;
	}

	document.documentElement.style.visibility = 'hidden';
	document.addEventListener('DOMContentLoaded', () => {
		window.scrollTo(0, scrollPosition);
		sessionStorage.removeItem('scrollPosition');
		document.documentElement.style.removeProperty('visibility');
	}, { once: true });
}
