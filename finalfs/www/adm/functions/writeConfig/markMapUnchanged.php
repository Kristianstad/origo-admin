<?php

	// Takes a database handle and an map-id. Sets column "changed" to "f" in the database for the given map.
	function markMapUnchanged(&$dbh, $mapId)
	{
		require("./constants/configSchema.php");
		$table = pg_escape_identifier($dbh, $configSchema).'.'.pg_escape_identifier($dbh, 'maps');
		$sql="UPDATE $table SET changed = 'f' WHERE map_id = $1";
		$result=pg_query_params($dbh, $sql, array($mapId));
		if (!$result)
		{
			sqlQueryError($dbh);
		}
	}