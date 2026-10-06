<?php

	// Takes an operation ('add' or 'remove'), a basic child target (array), and a full parent target (array) as parameters.
	// Returns a sql-query string that updates the database by adding or removing the child from the appropriate field of the given parent 
	function sqlForOperation($operation, $child, $parent)
	{
		require("./constants/configSchema.php");
		if (!isTarget($child) || isFullTarget($child) || !isFullTarget($parent))
		{
			invalidTarget(__FUNCTION__);
		}
		$parentColumn=typeTableName(targetType($child));
		$parentConfig=targetConfig($parent);
		$parentColumnArray=pgArrayToPhp($parentConfig[$parentColumn]);
		$parentTable=targetTable($parent);
		$parentPkColumn=targetIdColumn($parent);
		$parentId=targetId($parent);
		$childId=targetId($child);
		if (!isset($parentColumnArray[0]))
		{
			$parentColumnArray=array();
		}
		if ($operation == 'add' && !in_array($childId, $parentColumnArray))
		{
			$parentColumnArray[]=$childId;
		}
		elseif ($operation == 'remove' && ($key = array_search($childId, $parentColumnArray)) !== false)
		{
			unset($parentColumnArray[$key]);
		}
		$parentColumnNewValue='{'.implode(',', $parentColumnArray).'}';
		$sql="UPDATE $configSchema.$parentTable SET $parentColumn = $1 WHERE $parentPkColumn = $2";
		return array('sql' => $sql, 'params' => array($parentColumnNewValue, $parentId));
	}