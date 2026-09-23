<?php

function printUndoButton($target, $visible=true)
{
	if (!$visible)
	{
		return;
	}
	$type=targetType($target);
	$id=targetId($target);
	$targetKey=objectHistoryKey($target);
	$targetTable=targetTable($target);
	// A wrapping <form> here would be silently dropped by the browser (this button is
	// always rendered inside the entity's own <form>, and forms cannot nest), which
	// discards the onsubmit confirm entirely. Confirm via the button's onclick instead.
	echo <<<HERE
		<input type='hidden' name='target_key' value='{$targetKey}'>
		<input type='hidden' name='target_table' value='{$targetTable}'>
		<input type='hidden' name='target_id' value='{$id}'>
		<button title='Backa' class='historyButton' type='submit' name='{$type}Button' value='undo' onclick='return confirm("Ångra senaste ändringen för {$id}?");'>↶</button>
	HERE;
}