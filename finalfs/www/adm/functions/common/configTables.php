<?php

	// Loads rows from configuration tables with the expected id column.
	// Returns an associative array keyed by table name.
	function configTables(&$dbh)
	{
		require("./constants/configSchema.php");
		$configTables=array();
		foreach (configTableNames($dbh) as $table)
		{
			$configTables[$table]=all_from_table($dbh, $configSchema, $table);
		}
		return $configTables;
	}