<?php

	// Takes a type (string) and prints an icon-only form submit-button for copying.
	function printCopyButton($type)
	{
		$typeSwe=toSwedish($type);
		echo '<button title="Spara kopia av '.$typeSwe.' till databas" aria-label="Spara kopia av '.$typeSwe.'" class="updateButton historyButton" type="submit" name="'.$type.'Button" value="copy"><span aria-hidden="true">&#x29C9;</span></button>';
	}