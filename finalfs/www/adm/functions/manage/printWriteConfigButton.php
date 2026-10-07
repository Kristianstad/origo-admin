<?php

	// Takes a map-id and (optionally) a $changed-value, and prints a "Write configuration to disk (json)"-button.
	function printWriteConfigButton($mapId, $changed='f')
	{
		if ($changed == 't')
		{
			$changeClass=' change';
		}
		else
		{
			$changeClass='';
		}
		$confirmStr="Är du säker att du vill skriva över den befintliga konfigurationen för $mapId?";
		$confirmAttribute=escapeHtml($confirmStr);
		$mapIdAttribute=escapeHtml($mapId);
		echo <<<HERE
			<button title="Skriv konfiguration till disk (json)" class="updateButton{$changeClass}" type="button" data-manage-action="write-config" data-confirm="{$confirmAttribute}" data-map-id="{$mapIdAttribute}">
				Skriv kartkonfiguration
			</button>
		HERE;
	}