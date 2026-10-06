<?php

	// Takes a full target-array and an updatePosts-array and returns a sql-query string that updates
	// the database configuration (table) for the given target with values from the updatePosts
	function sqlForUpdate($fullTarget, $updatePosts)
	{
		require("./constants/configSchema.php");
		if (isFullTarget($fullTarget))
		{
			$targetTable=targetTable($fullTarget);
			$targetId=targetId($fullTarget);
			$fullTarget=updatedFullTarget($fullTarget, $updatePosts);
			$sql="UPDATE $configSchema.$targetTable SET";
			$statement=appendUpdatedColumnsToSql(targetConfig($fullTarget), $sql);
			$sql=$statement['sql'];
			$params=$statement['params'];
			$targetIdColumn=targetIdColumn($fullTarget);
			$params[]=$targetId;
			$sql=$sql." WHERE $targetIdColumn = $".count($params);
			return array('sql' => $sql, 'params' => $params);
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}