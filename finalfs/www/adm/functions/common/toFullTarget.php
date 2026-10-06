<?php

	// Resolves a basic target from configTables or a database handle; full targets pass through unchanged.
	function toFullTarget($target, $configTablesOrDbh)
	{
		if (isTarget($target))
		{
			return array(targetType($target)=>targetConfig($target, $configTablesOrDbh));
		}
		else
		{
			invalidTarget(__FUNCTION__);
		}
	}