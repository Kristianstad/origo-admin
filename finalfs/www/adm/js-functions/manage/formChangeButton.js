function formChangeButton() 
{
    document.querySelectorAll('form').forEach(form => {
        const updateBtn = form.querySelector('button[type="submit"][value="update"]');
        if (updateBtn) { // Ensure the button exists in the form
            form.addEventListener('input', (event) => {
                if (event.target.type !== 'hidden') {
                    updateBtn.classList.add('change');
                }
            });
        }
    });
}
