<?php

	function printReadSchemaTablesButton($schemaId)
	{
		$confirmStr="Läser in eventuella nya tabeller från schemat. Befintiga metadata påverkas ej.";
		$confirmJs=json_encode($confirmStr, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		$schemaIdJs=json_encode($schemaId, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Uppdatera verktyget med nya tabeller från schemat" class="updateButton" type="button" onclick='if (!confirm({$confirmJs})) return false; document.getElementById("hiddenFrame").src="read_schema_tables.php?schema="+encodeURIComponent({$schemaIdJs}); setTimeout(function() {if (document.getElementById("schemas1HeadForm") != null) {document.getElementById("schemas1HeadForm").submit();} else {document.getElementById("schemasHeadForm").submit();}}, 1000);'>
				Läs in nya tabeller
			</button>
		HERE;
	}