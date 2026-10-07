<?php

	function printReadSchemaTablesButton($schemaId)
	{
		$confirmStr="Läser in eventuella nya tabeller från schemat. Befintiga metadata påverkas ej.";
		$confirmAttribute=escapeHtml($confirmStr);
		$schemaIdAttribute=escapeHtml($schemaId);
		echo <<<HERE
			<button title="Uppdatera verktyget med nya tabeller från schemat" class="updateButton" type="button" data-manage-action="read-schema-tables" data-confirm="{$confirmAttribute}" data-schema-id="{$schemaIdAttribute}">
				Läs in nya tabeller
			</button>
		HERE;
	}