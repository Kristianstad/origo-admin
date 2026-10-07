function updateSqlInputState(fileInput) {
    const sqlInput=document.getElementById('sql');
    const hasFile=fileInput.files && fileInput.files.length > 0;
    sqlInput.value=hasFile ? '' : sqlInput.value;
    sqlInput.disabled=hasFile;
}