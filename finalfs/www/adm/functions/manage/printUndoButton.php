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
	$confirmJs='return confirm("Ångra senaste ändringen för "+'.jsonForInlineJs($id).'+"?");';
	echo '<input type="hidden" name="target_key" value="'.escapeHtml($targetKey).'">';
	echo '<input type="hidden" name="target_table" value="'.escapeHtml($targetTable).'">';
	echo '<input type="hidden" name="target_id" value="'.escapeHtml($id).'">';
	echo renderIconButton(array(
		'title' => 'Backa',
		'class' => 'historyButton',
		'type' => 'submit',
		'name' => $type.'Button',
		'value' => 'undo',
		'onclick' => $confirmJs
	), '↶');
}