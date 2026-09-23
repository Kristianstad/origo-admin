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
	$idEsc=htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
	$targetKeyEsc=htmlspecialchars($targetKey, ENT_QUOTES, 'UTF-8');
	$targetTableEsc=htmlspecialchars($targetTable, ENT_QUOTES, 'UTF-8');
	$idJs=json_encode($id, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
	echo <<<HERE
		<input type='hidden' name='target_key' value='{$targetKeyEsc}'>
		<input type='hidden' name='target_table' value='{$targetTableEsc}'>
		<input type='hidden' name='target_id' value='{$idEsc}'>
		<button title='Gör om' class='historyButton' type='submit' name='{$type}Button' value='redo' onclick='return confirm("Gör om senaste ändringen för "+{$idJs}+"?");'>↷</button>
	HERE;
}