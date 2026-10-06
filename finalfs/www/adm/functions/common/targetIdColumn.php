<?php

	function targetIdColumn($target)
	{
		if (!isTarget($target))
		{
			invalidTarget(__FUNCTION__);
		}
		return pkColumnOfTable(targetTable($target));
	}