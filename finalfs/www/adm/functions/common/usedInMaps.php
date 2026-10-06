<?php

	// Takes a database handle and a target, and returns an array of maps that uses this target.
	function usedInMaps(&$dbh, $target, $checkedTargets=array(), $usedInMaps=array())
	{
		if (!isTarget($target))
		{
			invalidTarget(__FUNCTION__);
		}
		$target = toBasicTarget($target);
		if (in_array($target, $checkedTargets))
		{
			return $usedInMaps;
		}
		else
		{
			$checkedTargets[]=$target;
		}
		$targetType=targetType($target);
		$targetId=targetId($target);
		if ($targetType == 'map')
		{
			$usedInMaps[]=$targetId;
		}
		else
		{
			$targetParents=findAllParents($dbh, $target);
			foreach ($targetParents as $parentsTable=>$options)
			{
				$parentIds=assocArrayValues($options);
				if ($parentsTable=='maps')
				{
					$usedInMaps=array_merge($usedInMaps, $parentIds);
				}
				else
				{
					foreach ($parentIds as $parentId)
					{
						$usedInMaps=usedInMaps($dbh, toBasicTarget(array(tableType($parentsTable)=>$parentId)), $checkedTargets, $usedInMaps);
					}
				}
			}
		}
		return array_unique($usedInMaps);
	}