<?php

	function printExportJsonButton($mapId)
	{
		$mapIdJs=json_encode($mapId, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Ladda ner konfiguration" class="updateButton" type="button" onclick='document.getElementById("hiddenFrame").src="writeConfig.php?getJson=y&amp;download=y&amp;map="+encodeURIComponent({$mapIdJs});'>
				Exportera JSON
			</button>
		HERE;
	}