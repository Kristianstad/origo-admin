<?php

	// Reads all rows from an allowlisted table in the configuration schema.
	// Invalid schema or table names receive HTTP 400.
	function allFromTable($dbh, $schema, $table)
	{
		require("./constants/configSchema.php");
		if ($schema !== $configSchema || !in_array($table, configTableNames($dbh), true))
		{
			http_response_code(400);
			exit('Invalid table');
		}
		$tableWithSchema=pg_escape_identifier($dbh, $schema).'.'.pg_escape_identifier($dbh, $table);
		$result=pg_query($dbh, "SELECT * FROM $tableWithSchema ORDER BY 1");
		if (!$result)
		{
			sqlQueryError($dbh);
		}
		return pg_fetch_all($result);
	}