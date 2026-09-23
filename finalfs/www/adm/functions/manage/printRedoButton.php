<?php

function printRedoButton($target, $visible=true)
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
		<form method='post' onsubmit='return confirm("Gör om senaste ändringen för {$id}?");'>
			<input type='hidden' name='target_key' value='{$targetKey}'>
			<input type='hidden' name='target_table' value='{$targetTable}'>
			<input type='hidden' name='target_id' value='{$id}'>
			<button title='Gör om' class='historyButton' type='submit' name='{$type}Button' value='redo'>↷</button>
		</form>
	HERE;
}