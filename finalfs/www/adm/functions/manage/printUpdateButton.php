<?php

	// Takes a type (string) and prints an icon-only form submit-button for updating.
	function printUpdateButton($type, $formChanged=false)
	{
		if ($formChanged)
		{
			$changeClass=' change';
		}
		else
		{
			$changeClass='';
		}
		echo '<button title="Skriv ändringar till databas" aria-label="Uppdatera" class="updateButton'.$changeClass.' historyButton" type="submit" name="'.$type.'Button" value="update"><span aria-hidden="true">&#x1F4BE;&#xFE0E;</span></button>';
	}