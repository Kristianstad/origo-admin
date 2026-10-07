function textareaState() {
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('textarea[data-config-textarea]').forEach(function(textarea) {
			const widthInput = document.getElementById(textarea.id + '_width');
			const heightInput = document.getElementById(textarea.id + '_height');
			const scrollInput = document.getElementById(textarea.id + '_scroll');
			const updateSize = function() {
				if (widthInput) {
					widthInput.value = textarea.offsetWidth;
				}
				if (heightInput) {
					heightInput.value = textarea.offsetHeight;
				}
			};

			textarea.addEventListener('mouseup', updateSize);
			textarea.addEventListener('mouseover', updateSize);
			textarea.addEventListener('keydown', function(event) {
				if (event.key !== 'Tab') {
					return;
				}
				event.preventDefault();
				textarea.setRangeText('\t', textarea.selectionStart, textarea.selectionEnd, 'end');
			});
			textarea.addEventListener('scroll', function() {
				if (scrollInput) {
					scrollInput.value = textarea.scrollTop;
				}
			});

			if (textarea.hasAttribute('data-scroll-top')) {
				textarea.scrollTop = Number.parseInt(textarea.dataset.scrollTop, 10) || 0;
			}
		});
	});
}