<?php

	// Takes an array of potentian parents and a target, and returns the actual parents.
	function findParents($potentialParents, $target)
	{
		if (isTarget($target))
		{
			$target=toBasicTarget($target);
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
		$targetType=targetType($target);
		$targetId=targetId($target);
		$parentTable=key($potentialParents);
		$parentIdColumn=pkColumnOfTable($parentTable);
		require("./constants/arrayColumns.php");
		$parents=array();
		foreach (current($potentialParents) as $potentialParent)
		{
			$targetTable=typeTableName($targetType);
			if (in_array($targetTable, $arrayColumns))
			{
				if (in_array($targetId, pgArrayToPhp($potentialParent[$targetTable])))
				{
					$parents[]=$potentialParent[$parentIdColumn];
				}
			}
			else
			{
				if ($targetId == $potentialParent[$targetType])
				{
					$parents[]=$potentialParent[$parentIdColumn];
				}
			}
		}
		return $parents;
	}