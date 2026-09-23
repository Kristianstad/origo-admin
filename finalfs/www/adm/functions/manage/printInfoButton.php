<?php

	// Takes a basic target array.
	// Prints a form with a button labeled "Info" as only visible element. The button toggles the topFrame-iframe with contents from info.php.
	// The type and id of the given target is posted (method=get) to info.php
	function printInfoButton($basicTarget)
	{
		$type=targetType($basicTarget);
		$id=targetId($basicTarget);
		$typeJs=json_encode($type, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		$idJs=json_encode($id, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
		echo <<<HERE
			<button title="Visa/dölj ytterligare information" class="updateButton" type="button" onclick='toggleTopFrame("info"); document.getElementById("topFrame").src="info.php?type="+encodeURIComponent({$typeJs})+"&id="+encodeURIComponent({$idJs});'>
				Info
			</button>
		HERE;
	}