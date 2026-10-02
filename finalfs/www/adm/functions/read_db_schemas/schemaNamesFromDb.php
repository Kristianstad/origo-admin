<?php

	function schemaNamesFromDb(&$dbh)
	{
		$result=pg_query($dbh, "select schema_name from information_schema.schemata where not schema_name = 'information_schema' and not schema_name like 'pg_%';");
		if (!$result)
		{
			sqlQueryError($dbh);
		}
		$schemas=pg_fetch_all_columns($result);
		return $schemas;
	}