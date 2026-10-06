<?php

	// Takes a database handle and a target, and returns an array of all direct parents.
	function findAllParents(&$dbh, $target)
	{
		if (isTarget($target))
		{
			$target=makeTargetBasic($target);
		}
		else
		{
			die("findAllParents(\$dbh, $target) failed! Child not a target.");
		}
		
		require("./constants/configSchema.php");
		$targetType=targetType($target);
		$allParents=array();
		if ($targetType != 'map')
		{
 			if ($targetType == 'group' || $targetType == 'layer' || $targetType == 'control' || $targetType == 'keyword' || $targetType == 'plugin')
			{
				$allParents['maps']['maps']=findParents(array('maps'=>allFromTable($dbh, $configSchema, 'maps')), $target);
			}	
			if ($targetType == 'group' || $targetType == 'layer' || $targetType == 'keyword')
			{
				$allParents['groups']['groups']=findParents(array('groups'=>allFromTable($dbh, $configSchema, 'groups')), $target);
			}
			if ($targetType == 'layer' || $targetType == 'source' || $targetType == 'contact' || $targetType == 'export' || $targetType == 'update' || $targetType == 'origin' || $targetType == 'table' || $targetType == 'keyword')
			{
				$allParents['layers']['layers']=findParents(array('layers'=>allFromTable($dbh, $configSchema, 'layers')), $target);
			}
			if ($targetType == 'layer')
			{
				$allParents['layers']['exports']=findParents(array('layers'=>allFromTable($dbh, $configSchema, 'layers')), makeTargetBasic(array('export'=>targetId($target))));
			}
			if ($targetType == 'contact' || $targetType == 'keyword')
			{
				$allParents['schemas']['schemas']=findParents(array('schemas'=>allFromTable($dbh, $configSchema, 'schemas')), $target);
				$allParents['tables']['tables']=findParents(array('tables'=>allFromTable($dbh, $configSchema, 'tables')), $target);
			}
			if ($targetType == 'contact' || $targetType == 'service' || $targetType == 'tilegrid' || $targetType == 'table')
			{
				$allParents['sources']['sources']=findParents(array('sources'=>allFromTable($dbh, $configSchema, 'sources')), $target);
			}
		}
		return $allParents;
	}