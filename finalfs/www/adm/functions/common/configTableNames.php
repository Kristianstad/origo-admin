<?php

function configTableNames($dbh): array
{
	static $cache = array();
	$cacheKey = implode("\0", array(pg_host($dbh), pg_port($dbh), pg_dbname($dbh)));
	if (isset($cache[$cacheKey]))
	{
		return $cache[$cacheKey];
	}

	require("./constants/configSchema.php");
	$sql = "SELECT t.table_name, c.column_name
		FROM information_schema.tables AS t
		JOIN information_schema.columns AS c
			ON c.table_schema = t.table_schema
			AND c.table_name = t.table_name
		WHERE t.table_schema = $1
			AND t.table_type = 'BASE TABLE'";
	$result = pg_query_params($dbh, $sql, array($configSchema));
	if ($result === false)
	{
		sqlQueryError($dbh);
	}

	$tableNames = array();
	while ($row = pg_fetch_assoc($result))
	{
		$tableName = $row['table_name'];
		if (in_array($tableName, array('object_identity', 'edit_cursor'), true))
		{
			continue;
		}
		if ($row['column_name'] === pkColumnOfTable($tableName))
		{
			$tableNames[$tableName] = true;
		}
	}
	return $cache[$cacheKey] = array_keys($tableNames);
}