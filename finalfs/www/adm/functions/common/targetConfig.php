<?php

	function targetConfig($target, $configTablesOrDbh=null)
	{
		if (!isTarget($target))
		{
			invalidTarget(__FUNCTION__);
		}
		$type = array_key_first($target);
		if (isFullTarget($target))
		{
			return $target[array_key_first($target)];
		}
		if (!isset($configTablesOrDbh))
		{
			invalidTarget(__FUNCTION__);
		}
		$config = arrayColumnSearch(
			targetId($target),
			targetIdColumn($target),
			tableConfigs(targetTable($target), $configTablesOrDbh)
		);
		if (empty($config))
		{
			invalidTarget(__FUNCTION__);
		}
		return $config;
	}