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
		$confirmJs=json_encode($confirmStr, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		$mapIdJs=json_encode($mapId, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Skriv konfiguration till disk (json)" class="updateButton{$changeClass}" type="button" onclick='if (!confirm({$confirmJs})) return false; this.classList.remove("change"); document.getElementById("hiddenFrame").src="writeConfig.php?map="+encodeURIComponent({$mapIdJs});'>
				Skriv kartkonfiguration
			</button>
		HERE;
	}