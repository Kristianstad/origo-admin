<?php

	function targetConfig($target, $configTablesOrDbh=null)
	{
		if (isTarget($target))
		{
			if (isFullTarget($target))
			{
				$config=current($target);
			}
			elseif (isset($configTablesOrDbh))
			{
				$config=arrayColumnSearch(targetId($target), targetIdColumn($target), tableConfigs(targetTable($target), $configTablesOrDbh));
			}
			else
			{
				die("targetConfig($target, $configTablesOrDbh=null) failed!");
			}
			return $config;
		}
		else
		{
			die("targetConfig($target, $configTablesOrDbh=null) failed!");
		}
	}