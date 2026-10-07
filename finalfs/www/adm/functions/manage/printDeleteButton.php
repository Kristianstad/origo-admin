<?php

	// Takes a basic target array, a confirmation string, and inheritPosts (array).
	// Prints an icon-only delete button in the surrounding entity form.
	// The button lauches a confirmation popup with the given confirmation string. 
	// If OK is pressed in the confirmation popup then the target is posted to manage.php for deletion.
	function printDeleteButton($target, $deleteConfirmStr, $inheritPosts)
	{
		if (($inheritPosts['_viewDepth'] ?? 1) == 1)
		{
			$targetType=targetType($target);
			$targetId=targetId($target);
			/*
			if ($targetType == 'map')
			{
				$inheritPosts=array();
			}
			elseif ($targetType == 'group')
			{
				$groupIdsArr=explode(',', $inheritPosts['groupIds']);
				foreach (array_reverse($groupIdsArr, true) as $k => $v)
				{
					unset($groupIdsArr[$k]);
					if ($v == $targetId)
					{
						break;
					}
				}
				$inheritPosts['groupIds']=implode(',', $groupIdsArr);
			}
			else
			{
				foreach ($inheritPosts as $k => $v)
				{
					unset($inheritPosts[$k]);
					if ($k == $targetType.'Id' && $v == $targetId)
					{
						break;
					}
				}
			}
			*/
			$targetTypeSwe=toSwedish($targetType);
			$targetIdEsc=escapeHtml($targetId);
			$deleteConfirmJs=jsonForInlineJs($deleteConfirmStr);
			echo <<<HERE
					<input type="hidden" name="{$targetType}IdDel" value="{$targetIdEsc}">
					<button title='Radera {$targetTypeSwe}' aria-label='Radera {$targetTypeSwe}' class='deleteButton historyButton' type='submit' name='{$targetType}Button' value='delete' onclick='return confirm({$deleteConfirmJs});'><span aria-hidden='true'>&#x1F5D1;&#xFE0E;</span></button>
			HERE;
			printHiddenInputs($inheritPosts);
		}
	}