<?php

	// Takes a basic target array.
	// Prints an icon-only button that toggles the topFrame-iframe with contents from info.php.
	// The type and id of the given target is posted (method=get) to info.php
	function printInfoButton($basicTarget)
	{
		$type=targetType($basicTarget);
		$id=targetId($basicTarget);
		$typeJs=json_encode($type, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		$idJs=json_encode($id, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Visa/dölj ytterligare information" aria-label="Visa/dölj ytterligare information" class="updateButton historyButton" type="button" onclick='toggleTopFrame("info"); document.getElementById("topFrame").src="info.php?type="+encodeURIComponent({$typeJs})+"&id="+encodeURIComponent({$idJs});'>
				<span aria-hidden="true">&#x24D8;</span>
			</button>
		HERE;
	}