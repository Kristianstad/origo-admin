<?php

	function printAddOperation($target, $addToTable, $buttontext, $inheritPosts)
	{
		$addToTableType=tableType(key($addToTable));
		$str=ucfirst($addToTableType);
		echo '<form class="addForm" method="post">';
		echo '<select class="addSelect" name="to'.$str.'Id">';
		printSelectOptions(array_merge(array(""),current($addToTable)));
		echo '</select>&nbsp;';
		printHiddenInputs($inheritPosts);
		$targetType=targetType($target);
		$targetTypeSwe=toSwedish($targetType);
		$addToTableTypeSwe=toSwedish($addToTableType);
		$buttonTitle='Lägg till '.$targetTypeSwe.' i '.$addToTableTypeSwe;
		$buttonCaption=preg_replace('/^Lägg till\s*/u', '', $buttontext);
		echo renderIconButton(array(
			'title' => $buttonTitle,
			'aria-label' => $buttontext,
			'class' => 'operationButton',
			'type' => 'submit',
			'name' => $targetType.'Button',
			'value' => 'operation'
		), '+', $buttonCaption, 'operationSymbol');
		echo '</form>';
	}