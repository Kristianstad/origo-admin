<?php

	function printAddOperation($target, $addToTable, $buttontext, $inheritPosts)
	{
		$addToTableType=rtrim(key($addToTable), 's');
		$str=ucfirst($addToTableType);
		echo '<form class="addForm" method="post">';
		echo '<select class="addSelect" name="to'.$str.'Id">';
		printSelectOptions(array_merge(array(""),current($addToTable)));
		echo '</select>&nbsp;';
		printHiddenInputs($inheritPosts);
		$targetType=targetType($target);
		$targetTypeSwe=toSwedish($targetType);
		$addToTableTypeSwe=toSwedish($addToTableType);
		$buttonTitle=htmlspecialchars('Lägg till '.$targetTypeSwe.' i '.$addToTableTypeSwe, ENT_QUOTES, 'UTF-8');
		$buttonLabel=htmlspecialchars($buttontext, ENT_QUOTES, 'UTF-8');
		$buttonCaption=htmlspecialchars(preg_replace('/^Lägg till\s*/u', '', $buttontext), ENT_QUOTES, 'UTF-8');
		echo '<button title="'.$buttonTitle.'" aria-label="'.$buttonLabel.'" class="operationButton" type="submit" name="'.$targetType.'Button" value="operation"><span class="operationSymbol" aria-hidden="true">+</span><span class="operationCaption">'.$buttonCaption.'</span></button>';
		echo '</form>';
	}