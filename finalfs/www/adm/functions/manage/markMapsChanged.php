<?php

	// Takes a database handle and an array of map-ids. Sets column "changed" to "t" in the database for the given maps.
	function markMapsChanged(&$dbh, $mapIds)
	{
		require("./constants/configSchema.php");
		if (empty($mapIds))
		{
			return;
		}
		$placeholders = array();
		$params = array_values($mapIds);
		foreach (array_keys($params) as $index)
		{
			$placeholders[] = '$'.($index + 1);
		}
		$table = pg_escape_identifier($dbh, $configSchema).'.'.pg_escape_identifier($dbh, 'maps');
		$sql = "UPDATE $table SET changed = 't' WHERE map_id IN (".implode(', ', $placeholders).')';
		$result=pg_query_params($dbh, $sql, $params);
		if (!$result)
		{
			sqlQueryError($dbh);
		}
	}