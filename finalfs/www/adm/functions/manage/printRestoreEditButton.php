<?php

function printRestoreEditButton($editId, $targetId)
{
	$editIdEsc=htmlspecialchars((string)$editId, ENT_QUOTES, 'UTF-8');
	$targetIdJs=json_encode((string)$targetId, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
	echo <<<HERE
		<input type="hidden" name="restoreEditId" value="{$editIdEsc}">
		<button title="Återställ raderat objekt" class="historyButton" type="submit" name="editButton" value="restore" onclick='return confirm("Återställ det raderade objektet "+{$targetIdJs}+"?");'><span aria-hidden="true">↶</span></button>
	HERE;
}