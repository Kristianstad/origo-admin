<?php

function printAddRemoveOperations($target, $operationTables, $inheritPosts, $labels=array())
{
	require('./constants/exclusiveOperationGroups.php');
	foreach ($operationTables as $table => $parents)
	{
		$parentType=tableType($table);
		$parentTypeSwe=toSwedish($parentType);
		$addLabel=isset($labels['add'][$table]) ? $labels['add'][$table] : 'Lägg till i '.$parentTypeSwe;
		$removeLabel=isset($labels['remove'][$table]) ? $labels['remove'][$table] : 'Ta bort från '.$parentTypeSwe;
		$canAdd=true;
		foreach ($exclusiveOperationGroups as $entry)
		{
			$group=is_array($entry) ? $entry : array($entry);
			if (in_array($table, $group))
			{
				foreach ($group as $exclusiveTable)
				{
					if (isset($operationTables[$exclusiveTable]) && !empty(findParents(array($exclusiveTable => $operationTables[$exclusiveTable]), $target)))
					{
						$canAdd=false;
						break 2;
					}
				}
			}
		}
		if ($canAdd)
		{
			printAddOperation($target, array($table => array_column($parents, pkColumnOfTable($table))), $addLabel, $inheritPosts);
		}
		printRemoveOperation($target, array($table => $parents), $removeLabel, $inheritPosts);
	}
}