<?php

	// Takes a basic target array.
	// Prints an icon-only button that toggles the topFrame-iframe with contents from info.php.
	// The type and id of the given target is posted (method=get) to info.php
	function printInfoButton($basicTarget)
	{
		$type=targetType($basicTarget);
		$id=targetId($basicTarget);
		$typeAttribute=escapeHtml($type);
		$idAttribute=escapeHtml($id);
		echo <<<HERE
			<button title="Visa/dölj ytterligare information" aria-label="Visa/dölj ytterligare information" class="updateButton historyButton" type="button" data-manage-action="info" data-info-type="{$typeAttribute}" data-info-id="{$idAttribute}">
				<span aria-hidden="true">&#x24D8;</span>
			</button>
		HERE;
	}