function clearSelection() {
    document.querySelector('#selection').value = '';
    const selectbox = document.querySelector('#selectbox');
    selectbox.setAttribute('data-sorted-values', '');
    selectbox.value = '';
    selectbox.querySelectorAll('option').forEach(function(option) {
        option.removeAttribute('selected');
    });
}