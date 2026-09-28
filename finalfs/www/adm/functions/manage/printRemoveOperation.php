<?php

	function printRemoveOperation($targetToRemove, $tableToRemoveFrom, $buttontext, $inheritPosts)
	{
		$tableToRemoveFromType=rtrim(key($tableToRemoveFrom), 's');
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
			$buttonTitle=htmlspecialchars('Ta bort '.$targetToRemoveTypeSwe.' från '.$tableToRemoveFromTypeSwe, ENT_QUOTES, 'UTF-8');
			$buttonLabel=htmlspecialchars($buttontext, ENT_QUOTES, 'UTF-8');
			$buttonCaption=htmlspecialchars(preg_replace('/^Ta bort\s*/u', '', $buttontext), ENT_QUOTES, 'UTF-8');
			echo '<button title="'.$buttonTitle.'" aria-label="'.$buttonLabel.'" class="operationButton" type="submit" name="'.$targetToRemoveType.'Button" value="operation"><span class="operationSymbol" aria-hidden="true">−</span><span class="operationCaption">'.$buttonCaption.'</span></button>';
			echo '</form>';
		}
	}