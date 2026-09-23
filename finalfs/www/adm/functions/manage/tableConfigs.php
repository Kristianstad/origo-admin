<?php

	function tableConfigs($table, $configTablesOrDbh)
	{
		// pg_connect() returns a PgSql\Connection object since PHP 8.1, not a resource
		if (is_resource($configTablesOrDbh) || $configTablesOrDbh instanceof \PgSql\Connection)
		{
			require("./constants/configSchema.php");
			return all_from_table($configTablesOrDbh, $configSchema, $table);
		}
		elseif (is_array($configTablesOrDbh) && !empty($configTablesOrDbh[$table]))
		{
			return $configTablesOrDbh[$table];
		}
		else
		{
			exit(1);
		}
	}