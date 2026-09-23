<?php

	function printReadDbSchemasButton($databaseId)
	{
		$confirmStr="Läser in eventuella nya scheman från databasen (utan tabeller). Befintiga metadata påverkas ej.";
		$confirmJs=json_encode($confirmStr, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		$databaseIdJs=json_encode($databaseId, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Uppdatera verktyget med nya scheman från databasen" class="updateButton" type="button" onclick='if (!confirm({$confirmJs})) return false; document.getElementById("hiddenFrame").src="read_db_schemas.php?database="+encodeURIComponent({$databaseIdJs}); setTimeout(function() {document.getElementById("databasesHeadForm").submit();}, 1000);'>
				Läs in nya scheman
			</button>
		HERE;
	}