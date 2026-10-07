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
	$confirmJs='return confirm("Gör om senaste ändringen för "+'.jsonForInlineJs($id).'+"?");';
	echo '<input type="hidden" name="target_key" value="'.escapeHtml($targetKey).'">';
	echo '<input type="hidden" name="target_table" value="'.escapeHtml($targetTable).'">';
	echo '<input type="hidden" name="target_id" value="'.escapeHtml($id).'">';
	echo renderIconButton(array(
		'title' => 'Gör om',
		'class' => 'historyButton',
		'type' => 'submit',
		'name' => $type.'Button',
		'value' => 'redo',
		'onclick' => $confirmJs
	), '↷');
}