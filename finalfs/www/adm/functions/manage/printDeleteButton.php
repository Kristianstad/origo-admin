<?php

	// Takes a basic target array, a confirmation string, and inheritPosts (array).
	// Prints a form with a button labeled "Radera" as only visible element. 
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
			$targetIdEsc=htmlspecialchars($targetId, ENT_QUOTES, 'UTF-8');
			$deleteConfirmJs=json_encode($deleteConfirmStr, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
			echo <<<HERE
					<input type="hidden" name="{$targetType}IdDel" value="{$targetIdEsc}">
					<button title='Radera {$targetTypeSwe}' class='deleteButton' type='submit' name='{$targetType}Button' value='delete' onclick='return confirm({$deleteConfirmJs});'>Radera</button>
			HERE;
			printHiddenInputs($inheritPosts);
		}
	}