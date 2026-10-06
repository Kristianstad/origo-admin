<?php

function printSimpleEntityForm($target, $type, $fields, $inheritPosts, $helps=array(), $extras=array())
{
	if (!isFullTarget($target))
	{
		invalidTarget('print'.ucfirst($type).'Form');
	}
	$sizePosts=sizePosts($inheritPosts);
	echo '<div><div class="printXFormDiv"><form method="post">';
	foreach ($fields as $field)
	{
		$help = in_array($field['name'], $helps);
		if (isset($field['type']) && $field['type'] === 'select')
		{
			printUpdateSelect($target, array($field['name']=>$field['options']), $field['class'], $field['label'], $help);
		}
		else
		{
			$readonly = isset($field['readonly']) ? $field['readonly'] : false;
			printTextarea($target, $field['name'], $field['class'], $field['label'], $help, $sizePosts, $readonly);
		}
	}
	printHiddenInputs($inheritPosts);
	if (!empty($extras['separator']))
	{
		echo '<hr class="dashedHr">';
	}
	echo '<div class="buttonDiv">';
	$basicTarget=toBasicTarget($target);
	if (isset($extras['leadingButtons']))
	{
		$extras['leadingButtons']($basicTarget);
	}
	printHistoryButtons($basicTarget);
	printUpdateButton($type, $inheritPosts['_formChanged'] ?? false);
	if (!isset($extras['showCopy']) || $extras['showCopy'])
	{
		printCopyButton($type);
	}
	printInfoButton($basicTarget);
	if (isset($extras['inlineButtons']))
	{
		$extras['inlineButtons']($basicTarget);
	}
	if (!isset($extras['showDelete']) || $extras['showDelete'])
	{
		$deleteConfirmStr=$extras['deleteConfirm']($basicTarget);
		printDeleteButton($basicTarget, $deleteConfirmStr, $inheritPosts);
	}
	echo '</div></form></div></div>';
	if (isset($extras['afterFormSections']))
	{
		echo '<div class="addRemoveDiv">';
		$extras['afterFormSections']($basicTarget, $inheritPosts);
		echo '</div>';
	}
}