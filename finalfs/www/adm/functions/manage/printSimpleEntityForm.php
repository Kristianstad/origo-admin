<?php

function printSimpleEntityForm($target, $type, $fields, $inheritPosts, $helps=array(), $extras=array())
{
	if (!isFullTarget($target))
	{
		die("print".ucfirst($type)."Form failed!");
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
	printUpdateButton($type, $inheritPosts['_formChanged'] ?? false);
	printCopyButton($type);
	$basicTarget=makeTargetBasic($target);
	printInfoButton($basicTarget);
	if (isset($extras['inlineButtons']))
	{
		$extras['inlineButtons']($basicTarget);
	}
	$deleteConfirmStr=$extras['deleteConfirm']($basicTarget);
	printDeleteButton($basicTarget, $deleteConfirmStr, $inheritPosts);
	echo '</div></form></div></div>';
	if (isset($extras['afterFormSections']))
	{
		echo '<div class="addRemoveDiv">';
		$extras['afterFormSections']($basicTarget, $inheritPosts);
		echo '</div>';
	}
}