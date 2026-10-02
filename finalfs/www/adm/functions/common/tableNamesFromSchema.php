<?php

	function tableNamesFromSchema($dbh, $schema)
	{
		$result=pg_query_params($dbh, "SELECT table_name FROM information_schema.tables WHERE table_schema = $1 AND table_type = 'BASE TABLE'", array($schema));
		if (!$result)
		{
			sqlQueryError($dbh);
		}
		return pg_fetch_all_columns($result);
	}