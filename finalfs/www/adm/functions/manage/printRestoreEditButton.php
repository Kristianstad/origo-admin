<?php

function printRestoreEditButton($editId, $targetId)
{
	$editIdEsc=escapeHtml((string)$editId);
	$targetIdJs=jsonForInlineJs((string)$targetId);
	echo <<<HERE
		<input type="hidden" name="restoreEditId" value="{$editIdEsc}">
		<button title="Återställ raderat objekt" class="historyButton" type="submit" name="editButton" value="restore" onclick='return confirm("Återställ det raderade objektet "+{$targetIdJs}+"?");'><span aria-hidden="true">↶</span></button>
	HERE;
}