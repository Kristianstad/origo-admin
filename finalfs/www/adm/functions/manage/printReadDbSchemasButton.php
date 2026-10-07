<?php

	function printReadDbSchemasButton($databaseId)
	{
		$confirmStr="Läser in eventuella nya scheman från databasen (utan tabeller). Befintiga metadata påverkas ej.";
		$confirmAttribute=escapeHtml($confirmStr);
		$databaseIdAttribute=escapeHtml($databaseId);
		echo <<<HERE
			<button title="Uppdatera verktyget med nya scheman från databasen" class="updateButton" type="button" data-manage-action="read-db-schemas" data-confirm="{$confirmAttribute}" data-database-id="{$databaseIdAttribute}">
				Läs in nya scheman
			</button>
		HERE;
	}