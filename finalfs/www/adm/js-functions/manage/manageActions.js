function manageActions() {
	document.addEventListener('click', function(event) {
		const button = event.target.closest('[data-manage-action]');
		if (!button) {
			return;
		}

		switch (button.dataset.manageAction) {
			case 'activate-skin':
				document.cookie = 'origo_admin_skin=' + encodeURIComponent(button.dataset.skinId) + ';path=/;max-age=31536000;samesite=lax';
				location.reload();
				break;
			case 'info':
				toggleTopFrame('info');
				document.getElementById('topFrame').src = 'info.php?type=' + encodeURIComponent(button.dataset.infoType) + '&id=' + encodeURIComponent(button.dataset.infoId);
				break;
			case 'read-db-schemas':
				if (!confirm(button.dataset.confirm)) {
					return;
				}
				document.getElementById('hiddenFrame').src = 'read_db_schemas.php?database=' + encodeURIComponent(button.dataset.databaseId);
				setTimeout(function() {
					document.getElementById('databasesHeadForm').submit();
				}, 1000);
				break;
			case 'read-schema-tables':
				if (!confirm(button.dataset.confirm)) {
					return;
				}
				document.getElementById('hiddenFrame').src = 'read_schema_tables.php?schema=' + encodeURIComponent(button.dataset.schemaId);
				setTimeout(function() {
					const form = document.getElementById('schemas1HeadForm') || document.getElementById('schemasHeadForm');
					form.submit();
				}, 1000);
				break;
			case 'write-config':
				if (!confirm(button.dataset.confirm)) {
					return;
				}
				button.classList.remove('change');
				document.getElementById('hiddenFrame').src = 'writeConfig.php?map=' + encodeURIComponent(button.dataset.mapId);
				break;
		}
	});
}