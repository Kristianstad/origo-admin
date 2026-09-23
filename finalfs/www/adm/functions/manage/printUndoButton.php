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
	echo <<<HERE
		<form method='post' onsubmit='return confirm("Ångra senaste ändringen för {$id}?");'>
			<input type='hidden' name='target_key' value='{$targetKey}'>
			<input type='hidden' name='target_table' value='{$targetTable}'>
			<input type='hidden' name='target_id' value='{$id}'>
			<button title='Backa' class='historyButton' type='submit' name='{$type}Button' value='undo'>↶</button>
		</form>
	HERE;
}