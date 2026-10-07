<?php

	function printRemoveOperation($targetToRemove, $tableToRemoveFrom, $buttontext, $inheritPosts)
	{
		$tableToRemoveFromType=tableType(key($tableToRemoveFrom));
		$str=ucfirst($tableToRemoveFromType);
		$parents=findParents($tableToRemoveFrom, $targetToRemove);
		if (!empty($parents))
		{
			echo '<form class="addForm" method="post">';
			echo '<select class="addSelect" name="from'.$str.'Id">';
			printSelectOptions(array_merge(array(""), $parents));
			echo '</select>&nbsp;';
			printHiddenInputs($inheritPosts);
			$targetToRemoveType=targetType($targetToRemove);
			$targetToRemoveTypeSwe=toSwedish($targetToRemoveType);
			$tableToRemoveFromTypeSwe=toSwedish($tableToRemoveFromType);
			$buttonTitle='Ta bort '.$targetToRemoveTypeSwe.' från '.$tableToRemoveFromTypeSwe;
			$buttonCaption=preg_replace('/^Ta bort\s*/u', '', $buttontext);
			echo renderIconButton(array(
				'title' => $buttonTitle,
				'aria-label' => $buttontext,
				'class' => 'operationButton',
				'type' => 'submit',
				'name' => $targetToRemoveType.'Button',
				'value' => 'operation'
			), '−', $buttonCaption, 'operationSymbol');
			echo '</form>';
		}
	}