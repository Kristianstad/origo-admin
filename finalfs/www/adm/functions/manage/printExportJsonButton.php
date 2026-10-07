<?php

	function printExportJsonButton($mapId)
	{
		$mapIdJs=jsonForInlineJs($mapId);
		echo <<<HERE
			<button title="Ladda ner konfiguration" class="updateButton" type="button" onclick='document.getElementById("hiddenFrame").src="writeConfig.php?getJson=y&amp;download=y&amp;map="+encodeURIComponent({$mapIdJs});'>
				Exportera JSON
			</button>
		HERE;
	}